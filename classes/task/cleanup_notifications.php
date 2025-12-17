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
 * Scheduled task to clean up old notification records.
 *
 * @package    availability_dripcontent
 * @copyright  2024 Prepara Oposiciones
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace availability_dripcontent\task;

defined('MOODLE_INTERNAL') || die();

/**
 * Task to clean up old notification tracking records.
 *
 * This prevents the notification table from growing indefinitely.
 * Records older than the configured retention period are deleted.
 *
 * @package    availability_dripcontent
 * @copyright  2024 Prepara Oposiciones
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cleanup_notifications extends \core\task\scheduled_task {

    /** Default retention period in days. */
    const DEFAULT_RETENTION_DAYS = 90;

    /**
     * Get the name of the task.
     *
     * @return string Task name.
     */
    public function get_name() {
        return get_string('task_cleanup_notifications', 'availability_dripcontent');
    }

    /**
     * Execute the task.
     */
    public function execute() {
        global $DB;

        // Get retention period from settings (default 90 days).
        $retentiondays = get_config('availability_dripcontent', 'notification_retention');
        if ($retentiondays === false || $retentiondays < 0) {
            $retentiondays = self::DEFAULT_RETENTION_DAYS;
        }

        // If retention is 0, keep all records.
        if ($retentiondays == 0) {
            mtrace('Notification cleanup disabled (retention set to 0).');
            return;
        }

        $cutoff = time() - ($retentiondays * DAYSECS);

        mtrace("Cleaning up notification records older than $retentiondays days...");

        // Delete old records.
        $deleted = $DB->delete_records_select(
            'availability_dripcontent_ntf',
            'timecreated < :cutoff',
            ['cutoff' => $cutoff]
        );

        mtrace("Deleted $deleted old notification records.");

        // Also clean up orphaned records (where course module no longer exists).
        $sql = "DELETE FROM {availability_dripcontent_ntf}
                WHERE cmid NOT IN (SELECT id FROM {course_modules})";
        $orphaned = $DB->execute($sql);

        if ($orphaned) {
            mtrace('Cleaned up orphaned notification records.');
        }
    }
}
