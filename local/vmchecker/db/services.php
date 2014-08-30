<?php
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.
/**
 * Web service local plugin template external functions and service definitions.
 *
 * @package    local_vmchecker
 * @copyright  2014 Alex Marin <alex.ukf@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Define the web service functions to install.
$functions = array(
	'local_vmchecker_grade_assignment' => array(
		'classname' => 'local_vmchecker_external',
		'methodname' => 'grade_assignment',
		'classpath' => 'local/vmchecker/externallib.php',
		'description' => 'Updates the grade and comments of an assignment using the results provided by the client.',
		'type' => 'write',
	)
);

// Define the services to install as pre-build services. A pre-build service is not editable by administrators.
$services = array(
	'vmchecker_grade_assignments' => array(
		'functions' => array ('local_vmchecker_grade_assignment'),
		'requiredcapability' => 'local/vmchecker:grade',
		'restrictedusers' => 0,
		'enabled' => 1,
      'shortname' => 'vmchecker',
	)
);
