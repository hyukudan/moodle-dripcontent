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
 * Scheduled task to check for content unlocks and send notifications.
 *
 * @package    availability_dripcontent
 * @copyright  2024 Prepara Oposiciones
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace availability_dripcontent\task;

defined('MOODLE_INTERNAL') || die();

/**
 * Task to check for drip content unlocks and notify users.
 *
 * @package    availability_dripcontent
 * @copyright  2024 Prepara Oposiciones
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class check_unlocks extends \core\task\scheduled_task {

    /**
     * Get the name of the task.
     *
     * @return string Task name.
     */
    public function get_name() {
        return get_string('task_check_unlocks', 'availability_dripcontent');
    }

    /**
     * Execute the task.
     */
    public function execute() {
        global $DB, $CFG;

        // Check if notifications are enabled.
        $enabled = get_config('availability_dripcontent', 'notify_enabled');
        $method = get_config('availability_dripcontent', 'notify_method');

        if (!$enabled || $method === 'none') {
            mtrace('Drip content notifications are disabled.');
            return;
        }

        require_once($CFG->dirroot . '/availability/classes/info.php');
        require_once($CFG->dirroot . '/availability/classes/info_module.php');

        mtrace('Checking for drip content unlocks...');

        // Get all course modules with dripcontent availability, grouped by course.
        $modules = $this->get_modules_with_dripcontent();

        if (empty($modules)) {
            mtrace('No modules with dripcontent conditions found.');
            return;
        }

        // Group modules by course for efficient user fetching.
        $coursemodules = [];
        foreach ($modules as $cm) {
            $coursemodules[$cm->course][] = $cm;
        }

        mtrace('Found ' . count($modules) . ' modules across ' . count($coursemodules) . ' courses.');

        $notificationssent = 0;
        $errors = 0;

        foreach ($coursemodules as $courseid => $cms) {
            try {
                $notifications = $this->check_course_unlocks($courseid, $cms, $method);
                $notificationssent += $notifications;
            } catch (\Exception $e) {
                $errors++;
                mtrace("Error processing course $courseid: " . $e->getMessage());
                // Continue with other courses despite error.
            }
        }

        mtrace("Sent $notificationssent unlock notifications.");
        if ($errors > 0) {
            mtrace("Encountered $errors errors during processing.");
        }
    }

    /**
     * Get all course modules that have dripcontent availability conditions.
     *
     * @return array Array of course module records.
     */
    protected function get_modules_with_dripcontent() {
        global $DB;

        // Find modules with dripcontent in their availability JSON.
        // Note: The pattern is hardcoded and safe from injection.
        $sql = "SELECT cm.id, cm.course, cm.module, cm.instance, cm.availability, c.fullname as coursename
                FROM {course_modules} cm
                JOIN {course} c ON c.id = cm.course
                WHERE cm.availability LIKE :pattern
                  AND cm.deletioninprogress = 0
                  AND cm.visible = 1
                ORDER BY cm.course";

        return $DB->get_records_sql($sql, ['pattern' => '%"type":"dripcontent"%']);
    }

    /**
     * Check for unlocks on all modules in a course and send notifications.
     *
     * This method fetches enrolled users once per course (not per module)
     * to avoid N+1 query performance issues.
     *
     * @param int $courseid Course ID.
     * @param array $cms Array of course module records.
     * @param string $method Notification method.
     * @return int Number of notifications sent.
     */
    protected function check_course_unlocks($courseid, $cms, $method) {
        $notificationssent = 0;

        // Get enrolled users once for the entire course.
        $context = \context_course::instance($courseid);
        $users = get_enrolled_users($context, '', 0, 'u.id, u.firstname, u.lastname, u.email');

        if (empty($users)) {
            return 0;
        }

        // Get module info once for the course.
        $modinfo = get_fast_modinfo($courseid);

        foreach ($cms as $cm) {
            try {
                if (!isset($modinfo->cms[$cm->id])) {
                    continue;
                }

                $cminfo = $modinfo->cms[$cm->id];
                $info = new \core_availability\info_module($cminfo);

                foreach ($users as $user) {
                    // Check if we already notified this user for this module.
                    if ($this->user_already_notified($user->id, $cm->id)) {
                        continue;
                    }

                    // Check if the module is now available for this user.
                    // The first parameter receives availability info (not used here).
                    $notused = null;
                    $available = $info->is_available($notused, false, $user->id);

                    if ($available) {
                        // Send notification and mark as notified atomically.
                        if ($this->send_and_mark_notification($user, $cminfo, $cm->coursename, $method, $cm->id)) {
                            $notificationssent++;
                        }
                    }
                }
            } catch (\Exception $e) {
                mtrace("Error processing module {$cm->id}: " . $e->getMessage());
                // Continue with other modules despite error.
            }
        }

        return $notificationssent;
    }

    /**
     * Check if user was already notified for this module.
     *
     * @param int $userid User ID.
     * @param int $cmid Course module ID.
     * @return bool True if already notified.
     */
    protected function user_already_notified($userid, $cmid) {
        global $DB;

        return $DB->record_exists('availability_dripcontent_ntf', [
            'userid' => $userid,
            'cmid' => $cmid,
        ]);
    }

    /**
     * Send notification and mark user as notified atomically.
     *
     * Uses try/catch to handle race conditions where another process
     * may have already inserted the notification record.
     *
     * @param \stdClass $user User object.
     * @param \cm_info $cminfo Course module info.
     * @param string $coursename Course name.
     * @param string $method Notification method.
     * @param int $cmid Course module ID.
     * @return bool True if notification was sent, false if already sent.
     */
    protected function send_and_mark_notification($user, $cminfo, $coursename, $method, $cmid) {
        global $DB;

        // Try to insert first (atomic check-and-insert).
        $record = new \stdClass();
        $record->userid = $user->id;
        $record->cmid = $cmid;
        $record->timecreated = time();

        try {
            // This will fail if record already exists (unique index).
            $DB->insert_record('availability_dripcontent_ntf', $record);
        } catch (\dml_write_exception $e) {
            // Record already exists - another process beat us to it.
            return false;
        }

        // Record inserted successfully, now send notification.
        try {
            $this->send_notification($user, $cminfo, $coursename, $method);
            return true;
        } catch (\Exception $e) {
            // Notification failed, but record is already inserted.
            // This prevents retry spam. Log the error.
            mtrace("Failed to send notification to user {$user->id} for module {$cmid}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send unlock notification to user.
     *
     * @param \stdClass $user User object.
     * @param \cm_info $cminfo Course module info.
     * @param string $coursename Course name.
     * @param string $method Notification method (email, popup, both).
     */
    protected function send_notification($user, $cminfo, $coursename, $method) {
        global $SITE;

        // Sanitize user-controlled data for safe display.
        $activityname = format_string($cminfo->name);
        $coursename = format_string($coursename);
        $url = new \moodle_url('/mod/' . $cminfo->modname . '/view.php', ['id' => $cminfo->id]);

        // Prepare message data.
        $a = new \stdClass();
        $a->username = fullname($user);
        $a->activityname = $activityname;
        $a->coursename = $coursename;
        $a->url = $url->out(false);
        $a->sitename = format_string($SITE->fullname);

        $message = new \core\message\message();
        $message->component = 'availability_dripcontent';
        $message->name = 'content_unlocked';
        $message->userfrom = \core_user::get_noreply_user();
        $message->userto = $user;
        $message->subject = get_string('notification_subject', 'availability_dripcontent', $a);
        $message->fullmessage = get_string('notification_body', 'availability_dripcontent', $a);
        $message->fullmessageformat = FORMAT_PLAIN;
        $message->fullmessagehtml = format_text(
            get_string('notification_body', 'availability_dripcontent', $a),
            FORMAT_PLAIN
        );
        $message->smallmessage = get_string('notification_small', 'availability_dripcontent', $a);
        $message->notification = 1;
        $message->contexturl = $url;
        $message->contexturlname = $activityname;

        // Set notification preferences based on method.
        if ($method === 'email') {
            $message->set_additional_content('email', [
                '*' => [
                    'header' => '',
                    'footer' => '',
                ],
            ]);
        }

        message_send($message);
    }
}
