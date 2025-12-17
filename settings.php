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
 * Plugin settings.
 *
 * @package    availability_dripcontent
 * @copyright  2024 Prepara Oposiciones
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    // Notification settings header.
    $settings->add(new admin_setting_heading(
        'availability_dripcontent/notificationheader',
        get_string('settings_notifications', 'availability_dripcontent'),
        get_string('settings_notifications_desc', 'availability_dripcontent')
    ));

    // Enable notifications.
    $settings->add(new admin_setting_configcheckbox(
        'availability_dripcontent/notify_enabled',
        get_string('notify_enabled', 'availability_dripcontent'),
        get_string('notify_enabled_desc', 'availability_dripcontent'),
        0
    ));

    // Notification method.
    $notifymethods = [
        'none' => get_string('notify_method_none', 'availability_dripcontent'),
        'email' => get_string('notify_method_email', 'availability_dripcontent'),
        'popup' => get_string('notify_method_popup', 'availability_dripcontent'),
        'both' => get_string('notify_method_both', 'availability_dripcontent'),
    ];

    $settings->add(new admin_setting_configselect(
        'availability_dripcontent/notify_method',
        get_string('notify_method', 'availability_dripcontent'),
        get_string('notify_method_desc', 'availability_dripcontent'),
        'both',
        $notifymethods
    ));

    // Maintenance settings header.
    $settings->add(new admin_setting_heading(
        'availability_dripcontent/maintenanceheader',
        get_string('settings_maintenance', 'availability_dripcontent'),
        get_string('settings_maintenance_desc', 'availability_dripcontent')
    ));

    // Notification retention period.
    $settings->add(new admin_setting_configtext(
        'availability_dripcontent/notification_retention',
        get_string('notification_retention', 'availability_dripcontent'),
        get_string('notification_retention_desc', 'availability_dripcontent'),
        90,
        PARAM_INT
    ));
}
