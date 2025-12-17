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
 * Language strings - English.
 *
 * @package    availability_dripcontent
 * @copyright  2024 Prepara Oposiciones
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Drip Content';
$string['title'] = 'Drip Content';
$string['description'] = 'Control access based on time in course, active subscription time, or specific dates.';

// Condition title.
$string['conditiontitle'] = 'Drip Content';

// Mode selection.
$string['mode'] = 'Mode';
$string['mode_coursedays'] = 'Time in course';
$string['mode_coursedays_help'] = 'Count days/months since the user first enrolled in the course.';
$string['mode_subscriptiondays'] = 'Active subscription time';
$string['mode_subscriptiondays_help'] = 'Only count days/months when the user has an active (paid) enrolment. Gaps in subscription are not counted.';
$string['mode_daterange'] = 'Date range';
$string['mode_daterange_help'] = 'Available between specific dates.';

// Unit selection.
$string['unit'] = 'Unit';
$string['unit_days'] = 'Days';
$string['unit_months'] = 'Months';

// Value input.
$string['value'] = 'Value';
$string['aftervalue'] = 'After {$a->value} {$a->unit}';

// Date range.
$string['fromdate'] = 'From date';
$string['todate'] = 'To date';
$string['betweendates'] = 'Between {$a->from} and {$a->to}';
$string['afterdate'] = 'After {$a->from}';
$string['beforedate'] = 'Before {$a->to}';

// Descriptions.
$string['full_days_course'] = 'Available after {$a} day(s) in the course';
$string['full_months_course'] = 'Available after {$a} month(s) in the course';
$string['full_days_subscription'] = 'Available after {$a} day(s) of active subscription';
$string['full_months_subscription'] = 'Available after {$a} month(s) of active subscription';
$string['full_daterange'] = 'Available from {$a->from} to {$a->to}';
$string['full_afterdate'] = 'Available after {$a}';
$string['full_beforedate'] = 'Available until {$a}';

$string['short_days_course'] = 'After {$a} days in course';
$string['short_months_course'] = 'After {$a} months in course';
$string['short_days_subscription'] = 'After {$a} days subscribed';
$string['short_months_subscription'] = 'After {$a} months subscribed';
$string['short_daterange'] = '{$a->from} - {$a->to}';

// Error messages.
$string['error_invalidvalue'] = 'Please enter a valid number.';
$string['error_invaliddate'] = 'Please select a valid date.';
$string['error_dateorder'] = 'The "From" date must be before the "To" date.';

// Privacy.
$string['privacy:metadata'] = 'The Drip Content availability condition does not store any personal data.';
