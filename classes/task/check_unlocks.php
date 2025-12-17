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

        // Get all course modules with dripcontent availability.
        $modules = $this->get_modules_with_dripcontent();

        if (empty($modules)) {
            mtrace('No modules with dripcontent conditions found.');
            return;
        }

        mtrace('Found ' . count($modules) . ' modules with dripcontent conditions.');

        $notificationssent = 0;

        foreach ($modules as $cm) {
            $notifications = $this->check_module_unlocks($cm, $method);
            $notificationssent += $notifications;
        }

        mtrace("Sent $notificationssent unlock notifications.");
    }

    /**
     * Get all course modules that have dripcontent availability conditions.
     *
     * @return array Array of course module records.
     */
    protected function get_modules_with_dripcontent() {
        global $DB;

        // Find modules with dripcontent in their availability JSON.
        $sql = "SELECT cm.id, cm.course, cm.module, cm.instance, cm.availability, c.fullname as coursename
                FROM {course_modules} cm
                JOIN {course} c ON c.id = cm.course
                WHERE cm.availability LIKE :pattern
                  AND cm.deletioninprogress = 0
                  AND cm.visible = 1";

        return $DB->get_records_sql($sql, ['pattern' => '%"type":"dripcontent"%']);
    }

    /**
     * Check for unlocks on a specific module and send notifications.
     *
     * @param \stdClass $cm Course module record.
     * @param string $method Notification method.
     * @return int Number of notifications sent.
     */
    protected function check_module_unlocks($cm, $method) {
        global $DB;

        $notificationssent = 0;

        // Get enrolled users in the course.
        $context = \context_course::instance($cm->course);
        $users = get_enrolled_users($context, '', 0, 'u.id, u.firstname, u.lastname, u.email');

        if (empty($users)) {
            return 0;
        }

        // Get the course module info.
        $modinfo = get_fast_modinfo($cm->course);
        if (!isset($modinfo->cms[$cm->id])) {
            return 0;
        }

        $cminfo = $modinfo->cms[$cm->id];
        $info = new \core_availability\info_module($cminfo);

        foreach ($users as $user) {
            // Check if we already notified this user for this module.
            $alreadynotified = $this->user_already_notified($user->id, $cm->id);
            if ($alreadynotified) {
                continue;
            }

            // Check if the module is now available for this user.
            $available = $info->is_available($availabilityinfo, false, $user->id);

            if ($available) {
                // Send notification.
                $this->send_notification($user, $cminfo, $cm->coursename, $method);
                $this->mark_user_notified($user->id, $cm->id);
                $notificationssent++;
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
     * Mark user as notified for this module.
     *
     * @param int $userid User ID.
     * @param int $cmid Course module ID.
     */
    protected function mark_user_notified($userid, $cmid) {
        global $DB;

        $record = new \stdClass();
        $record->userid = $userid;
        $record->cmid = $cmid;
        $record->timecreated = time();

        $DB->insert_record('availability_dripcontent_ntf', $record);
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
        global $CFG, $SITE;

        $activityname = $cminfo->name;
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
        $message->fullmessagehtml = nl2br(get_string('notification_body', 'availability_dripcontent', $a));
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
