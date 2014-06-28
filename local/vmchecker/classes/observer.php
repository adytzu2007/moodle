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
        $grade = new stdClass();
        $grade->feedback = 'Comentarii luate de la vmchecker.';
        $grade->feedbackformat = 1; // FORMAT_HTML Plain HTML (with some tags stripped)
        $grade->rawgrade = 81.99; // A fixed grade limited to the grademax column setting in grade_items table
        $grade->timemodified = time();
        $grade->userid = $userid;
        $grade->usermodified = $userid;

        // Automatically gives the assessable a fixed grade
        $grade_result = grade_update('mod/assign', $courseid, 'mod', 'assign', $iteminstance, $itemnumber, $grade);

        // $grade_result: GRADE_UPDATE_OK = 0, GRADE_UPDATE_FAILED = 1 
        add_to_log(0, "vmchecker", "log", "/", "Automatic grading of submitted assignment: " . $grade_result);
    }
}
