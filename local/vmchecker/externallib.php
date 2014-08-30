<?php

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
 * External web service for grading assignments
 *
 * @package    local_vmchecker
 * @copyright  2014 Alex Marin <alex.ukf@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once($CFG->libdir . "/externallib.php");

class local_vmchecker_external extends external_api {

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function grade_assignment_parameters() {
        return new external_function_parameters(
            array(
                'grade' => new external_value(
                    PARAM_INT,
                    'The assigned grade.',
                    VALUE_REQUIRED
                ),
                'comments' => new external_value(
                    PARAM_TEXT,
                    'The comments associated with the grade.',
                    VALUE_REQUIRED
                ),
                'callback_data' => new external_value(
                    PARAM_TEXT,
                    'Data that must be sent back to identify the submission.',
                    VALUE_REQUIRED
                ),
            )
        );
    }

    /**
     * Updates the grade and comments for an assignment.
     */
    public static function grade_assignment($grade, $comments, $callback_data) {
        global $USER, $DB;

        //Parameter validation
        //REQUIRED
        $params = self::validate_parameters(
            self::grade_assignment_parameters(),
            array(
                'grade' => $grade,
                'comments' => $comments,
                'callback_data' => $callback_data
            )
        );

        $callback_data = json_decode($callback_data);
        $DB->delete_records('external_tokens', array('token' => $_GET['wstoken']));

        return array(
            'grade' => $grade,
            'comments' => $comments,
            'assignment_id' => $callback_data->assignment_id,
            'course_id' => $callback_data->course_id
        );
        // //Context validation
        // //OPTIONAL but in most web service it should present
        // $context = get_context_instance(CONTEXT_USER, $USER->id);
        // self::validate_context($context);

        // //Capability checking
        // //OPTIONAL but in most web service it should present
        // if (! has_capability('moodle/user:viewdetails', $context)) {
        //     throw new moodle_exception('cannotviewprofile');
        // }


        // TODO actual grade
        // local_vmchecker_external::update_grade_and_comments($grade, $comments, $assignment_id, $course_id, $user_id);
    }


    /**
     * Actually does the update of the grade and comments
     */
    public static function update_grade_and_comments($grade_value, $feedback, $iteminstance, $course_id, $userid) {
        global $DB; // TODO - check if this is ok

        // Create the grade_grade object
        $grade = new stdClass();
        $grade->rawgrade = $grade_value;
        $grade->feedback = $feedback;
        $grade->feedbackformat = 1; // FORMAT_HTML - Plain HTML with some tags stripped
        $grade->timemodified = time();
        $grade->userid = $userid;
        $grade->usermodified = $userid;

        // Automatically gives the assessable a fixed grade
        $sql = 'SELECT a.*, cm.idnumber as cmidnumber'
            . ' FROM {assign} a, {course_modules} cm, {modules} m'
            . ' WHERE m.name = \'assign\''
            . ' AND m.id = cm.module'
            . ' AND cm.instance = ' . $iteminstance
            . ' AND a.id = ' . $iteminstance
            . ' AND a.course = ' . $course_id;

        if ($assignment = $DB->get_record_sql($sql)) {
            assign_grade_item_update($assignment, $grade);
        }

        // // Automatically gives the assessable a fixed grade - #2
        // // There is only one grade
        // $itemnumber = 0;
        // $grade_result = grade_update('mod/assign', $courseid, 'mod', 'assign', $iteminstance, $itemnumber, $grade);

        // Update grade and comments for the submitted assignment
        $GRADES_TABLE = 'assign_grades';
        $FEEDBACK_TABLE = 'assignfeedback_comments';
        $sql = 'SELECT *'
            . ' FROM {' . $GRADES_TABLE . '}'
            . ' WHERE assignment = ' . $iteminstance
            . ' AND userid = ' . $userid;

        // If a grade for the submission already exists
        if ($existing_grade = $DB->get_record_sql($sql)) {
            // Update the grade
            $existing_grade->timemodified = time();
            $existing_grade->grade = $grade_value;
            $DB->update_record($GRADES_TABLE, $existing_grade);

            // Update the feedback
            $sql = 'SELECT *'
                . ' FROM {' . $FEEDBACK_TABLE . '}'
                . ' WHERE assignment = ' . $iteminstance
                . ' AND grade = ' . $existing_grade->id;
            if ($existing_comment = $DB->get_record_sql($sql)) {
                $existing_comment->commenttext = $feedback;
                $DB->update_record($FEEDBACK_TABLE, $existing_comment);
            }
        } else {
            // If a grade does not exist, it is inserted
            $new_grade = new stdClass();
            $new_grade->assignment = $iteminstance;
            $new_grade->userid = $userid;
            $new_grade->timecreated = time();
            $new_grade->timemodified = $new_grade->timecreated;
            $new_grade->grader = 2; // TODO get vmchecker id
            $new_grade->grade = $grade_value;
            $new_grade->attemptnumber = 1; // TODO si la update: attentpnumber ++
            $new_grade_id = $DB->insert_record($GRADES_TABLE, $new_grade);

            // Insert the new feedback as well
            $new_comment = new stdClass();
            $new_comment->assignment = $iteminstance;
            $new_comment->grade = $new_grade_id;
            $new_comment->commenttext = $feedback;
            $new_comment->commentformat = 1;
            $DB->insert_record($FEEDBACK_TABLE, $new_comment, false);
        }
    }


    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function grade_assignment_returns() {
        return new external_function_parameters(
            array(
                'grade' => new external_value(
                    PARAM_INT, 'The assigned grade.', VALUE_REQUIRED),
                'comments' => new external_value(
                    PARAM_TEXT, 'The comments associated with the grade.',
                    VALUE_REQUIRED),
                'assignment_id' => new external_value(
                    PARAM_INT, 'The assignment id.', VALUE_REQUIRED),
                'course_id' => new external_value(
                    PARAM_INT, 'The assignment id.', VALUE_REQUIRED),
            )
        );
    }
}
