<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.

/**
 * Event observers used in vmchecker.
 *
 * @package    local_vmchecker
 * @copyright  2014 Alex Marin <alex.ukf@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
 
   
/**
 * Event observer for local_vmchecker.
 */
class local_vmchecker_observer {

    /**
     * Observer for assessable_submitted event.
     *
     * @param \mod_assign\event\assessable_submitted $event
     * @return void
     */
    public static function handle_assessable_submitted(\mod_assign\event\assessable_submitted $event) {
        global $COURSE, $USER, $PAGE;

        // Get the course id and user id
        $courseid = $COURSE->id;
        $userid = $USER->id;

        // Get the item instance (the id of the grade_item)
        $cmid = $PAGE->cm->id;
        $modinfo = get_fast_modinfo($courseid);
        $cm = $modinfo->get_cm($cmid);
        $iteminstance = $cm->instance;

        // There is only one grade
        $itemnumber = 0;

        // Create the grade_grade object
        $status = local_vmchecker_observer::grade_with_vmchecker($event);
        $grade = new stdClass();
        // $grade->rawgrade = 8.88;
        $grade->feedback = $status;
        $grade->feedbackformat = 1; // FORMAT_HTML - Plain HTML with some tags stripped
        $grade->timemodified = time();
        $grade->userid = $userid;
        $grade->usermodified = $userid;

        // Automatically gives the assessable a fixed grade
        $grade_result = grade_update('mod/assign', $courseid, 'mod', 'assign', $iteminstance, $itemnumber, $grade);

        // $grade_result: GRADE_UPDATE_OK = 0, GRADE_UPDATE_FAILED = 1 
        add_to_log(0, "vmchecker", "log", "/", "Assignment submitted for automatic grading "
            . "(0 - OK, 1 - FAILED): " . $grade_result . ".");
    }

    /**
     *  Sends a POST request with the assignment to vmchecker
     *
     * @param \mod_assign\event\assessable_submitted $event
     * @return 
     */
    private static function grade_with_vmchecker(\mod_assign\event\assessable_submitted $event) {
        // Get the assignment details from the database
        list($assignment_id, $submission_path, $mimetype) = local_vmchecker_observer::get_submission_details($event);

        $curl = curl_init();
        $curl_url = 'http://86.127.147.139:5000/v1/submits/';
        // $curl_url = 'http://localhost/test_post.php';

        // Set the cURL options
        curl_setopt_array($curl, array(
            CURLOPT_URL => $curl_url,
            CURLOPT_USERAGENT => get_string('curluseragent', 'local_vmchecker'),
            CURLOPT_POST => 1,
            CURLOPT_POSTFIELDS => array(
                'user_id' => $event->userid,
                'assignment_id' => $assignment_id,
                'file' => new CurlFile($submission_path, $mimetype),
            )
        ));

        // Send the request
        $sent_assignment = curl_exec($curl);
        // Close the handler
        curl_close($curl);

        if ($sent_assignment) {
            return get_string('curlpostsuccessful', 'local_vmchecker');
        } else {
            return get_string('curlpostfailed', 'local_vmchecker');
        }
    }

    /**
     *  Gets the assignment details from the database.
     *
     * @param \mod_assign\event\assessable_submitted $event
     * @return array An array with the assignment_id, submission_path and mimetype of assignment
     */
    private static function get_submission_details(\mod_assign\event\assessable_submitted $event) {
        global $CFG, $DB;

        // Get the assignment id
        $ASSIGN_SUBMISSION_TABLE = 'assign_submission';
        $assignment_details = $DB->get_record_sql(
            'SELECT assignment '
            . ' FROM {' . $ASSIGN_SUBMISSION_TABLE . '}'
            . ' WHERE id = ' . $event->objectid
        );
        $assignment_id = $assignment_details->assignment;

        // Get the submission details from the files table
        $FILES_TABLE = "files";
        $submission_details = $DB->get_records_sql(
            'SELECT id, contenthash, mimetype' 
            . ' FROM {' . $FILES_TABLE . '}' 
            . ' WHERE contextid = ' . $event->contextid
            . ' AND itemid = ' . $event->objectid
            . ' AND userid = ' . $event->userid
            . ' AND component = \'assignsubmission_file\''
            . ' AND filearea = \'submission_files\''
        );

        // There may be multiple assignments that match, make sure to get the latest one
        $last_record = $submission_details[max(array_keys($submission_details))];
        
        // Compute the full filepath of the assignment
        $submission_path = $CFG->dataroot . "/filedir/"
            . substr($last_record->contenthash, 0, 2) . "/"
            . substr($last_record->contenthash, 2, 2) . "/"
            . $last_record->contenthash;

        // Get the mimetype of the assignment
        $mimetype = $last_record->mimetype;

        return array($assignment_id, $submission_path, $mimetype);
    }
}
