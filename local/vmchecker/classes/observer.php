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
        global $COURSE, $USER, $PAGE, $DB;

        // Get the item instance (the id of the grade_item)
        $cmid = $PAGE->cm->id;
        $modinfo = get_fast_modinfo($COURSE->id);
        $cm = $modinfo->get_cm($cmid);
        $iteminstance = $cm->instance;

        // Get the assignment details from the database
        list($assignment_id, $submission_path, $mimetype) = local_vmchecker_observer::get_submission_details($event);

        // Sends a POST request with the assignment to vmchecker
        $curl = curl_init();
        $curl_url = 'http://10.0.2.2:5000/api/submits/';

        $post_data = array(
            'assignment_id' => $assignment_id,
            'file' => new CurlFile($submission_path, $mimetype),
        );

        $token = local_vmchecker_observer::get_token();
        if ($token != null) {
            $post_data['callback_data'] = json_encode(array(
                'iteminstance' => $iteminstance,
                'course_id' => $COURSE->id,
            ), JSON_NUMERIC_CHECK);
            $post_data['callback_url'] = new moodle_url(
                '/webservice/xmlrpc/server.php', array('wstoken' => $token->token));
            $post_data['callback_type'] = 'xmlrpc';
            $post_data['callback_fn'] = 'local_vmchecker_grade_assignment';
        }

        // Set the cURL options
        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => $curl_url,
            CURLOPT_USERAGENT => get_string('curluseragent', 'local_vmchecker'),
            CURLOPT_USERPWD => "admin:123456",
            CURLOPT_POST => 1,
            CURLOPT_POSTFIELDS => $post_data
        ));

        // Send the request
        curl_exec($curl);
        // Close the handler
        curl_close($curl);
    }

    /**
     *  Gets the user token
     *
     * @return string The token used for authentication
     */
    private static function get_token() {
        global $USER, $COURSE, $DB;

        $service = $DB->get_record('external_services', array('shortname' => 'vmchecker', 'enabled' => 1));
        if (empty($service)) {
            echo '<pre>There is no vmchecker web service</pre>';
            // if the external service is not found or if it doesn't exist then
            // we don't support asynchronous results
            return null;
        }

        if (!has_capability($service->requiredcapability,
                            context_course::instance($COURSE->id),
                            $USER)) {
            echo '<pre>The current user doesn\'t have acces to the webservice</pre>';
            return null;
        }

        if (!has_capability('moodle/webservice:createtoken',
                            context_course::instance($COURSE->id),
                            $USER)) {
            echo '<pre>The current user can\'t create tokens</pre>';
        }

        $service_record = $DB->get_record(
            'external_services', array(
                'shortname'=>'vmchecker',
                'enabled'=>1),
            '*',
            MUST_EXIST);

        // Create a new token.
        $token = new stdClass;
        // TODO check why openssl_* functions are not present
        // because we need a cryptographically safe function
        //$token_bytes = openssl_random_pseudobytes(64, true);
        //$token->token = bin2hex($token_bytes);
        $token->token = hash('sha256', uniqid(rand(), 1));
        $token->userid = $USER->id;
        $token->tokentype = EXTERNAL_TOKEN_PERMANENT;
        $token->contextid = context_course::instance($COURSE->id)->id;
        $token->creatorid = $USER->id;
        $token->timecreated = time();
        $token->externalserviceid = $service_record->id;
        // the token is valid for, at most, one day
        $token->validuntil = $token->timecreated + DAYSECS;
        $token->id = $DB->insert_record('external_tokens', $token);

        $params = array(
            'objectid' => $token->id,
            'relateduserid' => $USER->id,
            'other' => array(
                'auto' => true
            )
        );

        $event = \core\event\webservice_token_created::create($params);
        $event->add_record_snapshot('external_tokens', $token);
        $event->trigger();

        return $token;
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
