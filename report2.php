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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * New report.php for booked users, users on waiting list, deleted users etc.
 *
 * @package   mod_booking
 * @author Bernhard Fischer
 * @copyright 2024 Wunderbyte GmbH <info@wunderbyte.at>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/mod/booking/lib.php');

require_login(0, false);

use mod_booking\output\booked_users;
use mod_booking\singleton_service;

global $PAGE;

$optionid = optional_param('optionid', 0, PARAM_INT);
$cmid = optional_param('cmid', 0, PARAM_INT);
$courseid = optional_param('courseid', 0, PARAM_INT);

if (!empty($optionid)) {
    $scope = 'option'; // If we have an optionid, we want the report for this booking option.
    $scopeid = $optionid;
    $urlparams = ["optionid" => $optionid];
} else if (!empty($cmid)) {
    $scope = 'instance';
    $scopeid = $cmid;
    $urlparams = ["cmid" => $cmid];
} else if (!empty($courseid)) {
    $scope = 'course'; // A moodle course containing (a) booking option(s).
    $scopeid = $courseid;
    $urlparams = ["courseid" => $courseid];
} else {
    $scope = 'system'; // The whole site.
    $scopeid = 0;
    $urlparams = [];
}

$url = new moodle_url('/mod/booking/report2.php', $urlparams);
$PAGE->set_url($url);

echo $OUTPUT->header();

switch ($scope) {
    case 'option':
        $optionsettings = singleton_service::get_instance_of_booking_option_settings($optionid);
        $cmid = $optionsettings->cmid;
        $context = context_module::instance($cmid);
        // Capability checks.
        $isteacher = booking_check_if_teacher($optionid);
        if (!($isteacher || has_capability('mod/booking:viewreports', $context))) {
            require_capability('mod/booking:readresponses', $context);
        }
        break;
    case 'instance':
        $context = context_module::instance($cmid);
        require_capability('mod/booking:managebookedusers_instance', $context);
        break;
    case 'course':
        $context = context_course::instance($courseid);
        require_capability('mod/booking:managebookedusers_course', $context);
        break;
    case 'system':
    default:
        $context = context_system::instance();
        require_capability('mod/booking:managebookedusers_system', $context);
        break;
}

// Now we render the booked users for the provided scope.
$data = new booked_users(
    $scope,
    $scopeid,
    true,
    true,
    true,
    true,
    true
);
$renderer = $PAGE->get_renderer('mod_booking');
echo $renderer->render_booked_users($data);

echo $OUTPUT->footer();
