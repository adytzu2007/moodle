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
 * Vmchecker settings that can be edited by administrators.
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package    local_vmchecker
 * @copyright  2014 Alex Marin <alex.ukf@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

// Required condition
if ($hassiteconfig) {
    $settingnode = new admin_settingpage('vmchecker', get_string('pluginname', 'local_vmchecker'));

    $settingnode->add(new admin_setting_configtext(
    	'local_vmchecker/delay',
    	get_string('delay', 'local_vmchecker'),
    	get_string('delay_help', 'local_vmchecker'),
    	10,
    	PARAM_INT));

    $settingnode->add(new admin_setting_users_with_capability(
    	'local_vmchecker/crashnotify',
    	get_string('crashnotify', 'local_vmchecker'),
    	get_string('crashnotify_help', 'local_vmchecker'),
    	array(),
    	'moodle/site:config'));

    $ADMIN->add('localplugins', $settingnode);
}
