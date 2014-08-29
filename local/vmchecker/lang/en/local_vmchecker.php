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
 * English strings for vmchecker
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package    local_vmchecker
 * @copyright  2014 Alex Marin <alex.ukf@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['curlpostfailed'] = 'The assignment could not be submitted to vmchecker and will be manually graded.';
$string['curlpostsuccessful'] = 'The assignment was submitted to vmchecker for automatic grading.';
$string['curluseragent'] = 'Moodle local vmchecker plugin';
$string['delay'] = 'Delay between requests to the vmchecker service (seconds)';
$string['delay_help'] = 'If the delay between sending requests and getting results is too short, vmchecker will reject the request.';
$string['crashnotify'] = 'Crash notification';
$string['crashnotify_help'] = 'Vmchecker can crash due to software bugs or upgrading. If so, who will receive the notification? It should be a person who can configure the service.';
$string['modulename'] = 'vmchecker';
$string['modulenameplural'] = 'vmcheckers';
$string['modulename_help'] = 'Use the vmchecker module for automatic grading of assignments.';
$string['vmcheckerfieldset'] = 'Custom example fieldset';
$string['vmcheckername'] = 'vmchecker name';
$string['vmcheckername_help'] = 'This is the content of the help tooltip associated with the vmcheckername field. Markdown syntax is supported.';
$string['vmchecker'] = 'vmchecker';
$string['pluginadministration'] = 'vmchecker administration';
$string['pluginname'] = 'vmchecker';
$string['successfulupgrade'] = 'The vmchecker plugin has been successfully upgraded.';
