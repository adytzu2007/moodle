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
        global $COURSE; //, $USER, $PAGE, $DB;

        // Get the course id and user id
        $courseid = $COURSE->id;

        // TODO del - not needed anymore:
        // $userid = $USER->id;
        // // Get the item instance (the id of the grade_item)
        // $cmid = $PAGE->cm->id;
        // $modinfo = get_fast_modinfo($courseid);
        // $cm = $modinfo->get_cm($cmid);
        // $iteminstance = $cm->instance;


        // Get the assignment details from the database
        list($assignment_id, $submission_path, $mimetype) = local_vmchecker_observer::get_submission_details($event);

        // Sends a POST request with the assignment to vmchecker
        $curl = curl_init();
        $curl_url = 'http://10.0.2.2:5000/api/submits/';
        // $curl_url = 'http://46.249.77.153:5000/api/submits/';

        // TODO get token - currently not working
        // $token = local_vmchecker_observer::get_token();
        $token = "7fa3c64f4f4b4a24e160b0d11d6bce0e";

        // Set the cURL options
        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => $curl_url,
            CURLOPT_USERAGENT => get_string('curluseragent', 'local_vmchecker'),
            CURLOPT_USERPWD => "admin:123456",
            CURLOPT_POST => 1,
            CURLOPT_POSTFIELDS => array(
                'callback_data' => json_encode(array (
                    1 => array('grade'),
                    2 => array('comments'),
                    3 => array('assignment_id', $assignment_id),
                    4 => array('course_id', $courseid),
                    5 => array('user_id', $event->userid),
                ), JSON_NUMERIC_CHECK),
                'assignment_id' => $assignment_id,
                'file' => new CurlFile($submission_path, $mimetype),
                'callback_url' => new moodle_url('/webservice/xmlrpc/server.php',
                     array('wstoken' => $token)),
                'callback_type' => 'xmlrpc',
                'callback_function' => 'local_vmchecker_grade_assignments',
            )
        ));

        // Send the request
        $curl_response = curl_exec($curl);
        // Close the handler
        curl_close($curl);

        // TODO del
        echo "<pre>";
        print_r($curl_response);
        echo "</pre>";

        // // Decode the response from vmchecker
        // $results = json_decode($curl_response);
        // return array($results->grade, $results->comments);

        // if ($sent_assignment) {
        //     return get_string('curlpostsuccessful', 'local_vmchecker');
        // } else {
        //     return get_string('curlpostfailed', 'local_vmchecker');
        // }
    }

    /**
     *  Gets the user token
     *
     * @return string The token used for authentication
     */
    private static function get_token() {
        $curl = curl_init();
        $curl_url = "https://localhost:8080/moodle/login/token.php?username=vmchecker&password=Vmch3ckr!&service=vmchecker_grade_assignments";

        // Set the cURL options
        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => $curl_url,
            CURLOPT_USERAGENT => get_string('curluseragent', 'local_vmchecker'),
        ));

        // Send the request
        $curl_response = curl_exec($curl);

        // TODO del debug info
        echo "<pre>tokenresponse: <br />";
        print_r($curl_response);
        echo "end";
        echo "</pre>";

        // Close the handler
        curl_close($curl);

        // TODO extract and return token
        // $results = json_decode($curl_response);
        // $token = $results->token;
        // return $token;
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
