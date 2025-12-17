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
$string['mode_coursedays'] = 'Time since enrolment';
$string['mode_coursedays_help'] = 'Count days/weeks/months since the user first enrolled in the course.';
$string['mode_coursestartdays'] = 'Time since course start';
$string['mode_coursestartdays_help'] = 'Count days/weeks/months since the course start date. Same for all users.';
$string['mode_subscriptiondays'] = 'Active subscription time';
$string['mode_subscriptiondays_help'] = 'Only count days/weeks/months when the user has an active (paid) enrolment. Gaps in subscription are not counted.';
$string['mode_daterange'] = 'Date range';
$string['mode_daterange_help'] = 'Available between specific dates.';

// Unit selection.
$string['unit'] = 'Unit';
$string['unit_days'] = 'Days';
$string['unit_weeks'] = 'Weeks';
$string['unit_months'] = 'Months';

// Value input.
$string['value'] = 'Value';
$string['aftervalue'] = 'After {$a->value} {$a->unit}';

// Enrolment methods filter.
$string['enrolmentmethods'] = 'Enrolment methods';
$string['enrolmentmethods_help'] = 'Only count time from these enrolment methods. Leave empty to count all methods.';
$string['allenrolmentmethods'] = 'All enrolment methods';

// Date range.
$string['fromdate'] = 'From date';
$string['todate'] = 'To date';
$string['betweendates'] = 'Between {$a->from} and {$a->to}';
$string['afterdate'] = 'After {$a->from}';
$string['beforedate'] = 'Before {$a->to}';

// Full descriptions - Enrolment mode.
$string['full_days_enrolment'] = 'Available after {$a} day(s) since enrolment';
$string['full_weeks_enrolment'] = 'Available after {$a} week(s) since enrolment';
$string['full_months_enrolment'] = 'Available after {$a} month(s) since enrolment';

// Full descriptions - Course start mode.
$string['full_days_coursestart'] = 'Available after {$a} day(s) since course start';
$string['full_weeks_coursestart'] = 'Available after {$a} week(s) since course start';
$string['full_months_coursestart'] = 'Available after {$a} month(s) since course start';

// Full descriptions - Subscription mode.
$string['full_days_subscription'] = 'Available after {$a} day(s) of active subscription';
$string['full_weeks_subscription'] = 'Available after {$a} week(s) of active subscription';
$string['full_months_subscription'] = 'Available after {$a} month(s) of active subscription';

// Full descriptions - Date range.
$string['full_daterange'] = 'Available from {$a->from} to {$a->to}';
$string['full_afterdate'] = 'Available after {$a}';
$string['full_beforedate'] = 'Available until {$a}';

// Short descriptions - Enrolment mode.
$string['short_days_enrolment'] = 'After {$a} days enrolled';
$string['short_weeks_enrolment'] = 'After {$a} weeks enrolled';
$string['short_months_enrolment'] = 'After {$a} months enrolled';

// Short descriptions - Course start mode.
$string['short_days_coursestart'] = 'After {$a} days from start';
$string['short_weeks_coursestart'] = 'After {$a} weeks from start';
$string['short_months_coursestart'] = 'After {$a} months from start';

// Short descriptions - Subscription mode.
$string['short_days_subscription'] = 'After {$a} days subscribed';
$string['short_weeks_subscription'] = 'After {$a} weeks subscribed';
$string['short_months_subscription'] = 'After {$a} months subscribed';

// Short descriptions - Date range.
$string['short_daterange'] = '{$a->from} - {$a->to}';
$string['short_afterdate'] = 'After {$a}';
$string['short_beforedate'] = 'Until {$a}';

// Remaining time strings.
$string['remaining_months'] = '{$a} month(s) remaining';
$string['remaining_months_days'] = '{$a->months} month(s) and {$a->days} day(s) remaining';
$string['remaining_days'] = '{$a} day(s) remaining';
$string['remaining_days_hours'] = '{$a->days} day(s) and {$a->hours} hour(s) remaining';
$string['remaining_hours'] = '{$a} hour(s) remaining';
$string['remaining_weeks'] = '{$a} week(s) remaining';

// Error messages.
$string['error_invalidvalue'] = 'Please enter a valid number.';
$string['error_invaliddate'] = 'Please select a valid date.';
$string['error_dateorder'] = 'The "From" date must be before the "To" date.';

// Admin settings.
$string['settings'] = 'Drip Content Settings';
$string['settings_notifications'] = 'Notification settings';
$string['settings_notifications_desc'] = 'Configure how users are notified when content becomes available.';
$string['notify_enabled'] = 'Enable unlock notifications';
$string['notify_enabled_desc'] = 'Send notifications to users when content becomes available to them.';
$string['notify_method'] = 'Notification method';
$string['notify_method_desc'] = 'Choose how to notify users when content unlocks.';
$string['notify_method_none'] = 'No notifications';
$string['notify_method_email'] = 'Email only';
$string['notify_method_popup'] = 'Platform notification only';
$string['notify_method_both'] = 'Email and platform notification';

// Notification messages.
$string['notification_subject'] = 'New content available: {$a->activityname}';
$string['notification_body'] = 'Hello {$a->username},

The following content is now available in the course "{$a->coursename}":

{$a->activityname}

Click here to access it: {$a->url}

Best regards,
{$a->sitename}';
$string['notification_small'] = 'Content "{$a->activityname}" is now available';

// Privacy.
$string['privacy:metadata'] = 'The Drip Content availability condition does not store any personal data.';

// Task.
$string['task_check_unlocks'] = 'Check for content unlocks and send notifications';
