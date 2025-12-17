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
 * Privacy subsystem implementation.
 *
 * @package    availability_dripcontent
 * @copyright  2024 Prepara Oposiciones
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace availability_dripcontent\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;
use core_privacy\local\request\transform;

defined('MOODLE_INTERNAL') || die();

/**
 * Privacy provider implementation.
 *
 * This plugin stores notification tracking data for users.
 *
 * @package    availability_dripcontent
 * @copyright  2024 Prepara Oposiciones
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider,
    \core_privacy\local\request\core_userlist_provider {

    /**
     * Returns meta data about this system.
     *
     * @param collection $collection The collection to add metadata to.
     * @return collection The updated collection.
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table(
            'availability_dripcontent_ntf',
            [
                'userid' => 'privacy:metadata:availability_dripcontent_ntf:userid',
                'cmid' => 'privacy:metadata:availability_dripcontent_ntf:cmid',
                'timecreated' => 'privacy:metadata:availability_dripcontent_ntf:timecreated',
            ],
            'privacy:metadata:availability_dripcontent_ntf'
        );

        return $collection;
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param int $userid The user to search.
     * @return contextlist The contextlist containing the list of contexts.
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();

        // Get module contexts where user has been notified.
        $sql = "SELECT DISTINCT ctx.id
                FROM {availability_dripcontent_ntf} ntf
                JOIN {course_modules} cm ON cm.id = ntf.cmid
                JOIN {context} ctx ON ctx.instanceid = cm.id AND ctx.contextlevel = :contextlevel
                WHERE ntf.userid = :userid";

        $params = [
            'contextlevel' => CONTEXT_MODULE,
            'userid' => $userid,
        ];

        $contextlist->add_from_sql($sql, $params);

        return $contextlist;
    }

    /**
     * Get the list of users who have data within a context.
     *
     * @param userlist $userlist The userlist containing the list of users.
     */
    public static function get_users_in_context(userlist $userlist): void {
        $context = $userlist->get_context();

        if ($context->contextlevel != CONTEXT_MODULE) {
            return;
        }

        $sql = "SELECT ntf.userid
                FROM {availability_dripcontent_ntf} ntf
                JOIN {course_modules} cm ON cm.id = ntf.cmid
                WHERE cm.id = :cmid";

        $params = ['cmid' => $context->instanceid];

        $userlist->add_from_sql('userid', $sql, $params);
    }

    /**
     * Export all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts to export information for.
     */
    public static function export_user_data(approved_contextlist $contextlist): void {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $user = $contextlist->get_user();
        $userid = $user->id;

        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel != CONTEXT_MODULE) {
                continue;
            }

            // Get notification data for this module and user.
            $sql = "SELECT ntf.id, ntf.cmid, ntf.timecreated
                    FROM {availability_dripcontent_ntf} ntf
                    JOIN {course_modules} cm ON cm.id = ntf.cmid
                    WHERE cm.id = :cmid AND ntf.userid = :userid";

            $params = [
                'cmid' => $context->instanceid,
                'userid' => $userid,
            ];

            $records = $DB->get_records_sql($sql, $params);

            if (!empty($records)) {
                $data = [];
                foreach ($records as $record) {
                    $data[] = [
                        'cmid' => $record->cmid,
                        'timecreated' => transform::datetime($record->timecreated),
                    ];
                }

                writer::with_context($context)->export_data(
                    [get_string('pluginname', 'availability_dripcontent')],
                    (object)['notifications' => $data]
                );
            }
        }
    }

    /**
     * Delete all data for all users in the specified context.
     *
     * @param \context $context The specific context to delete data for.
     */
    public static function delete_data_for_all_users_in_context(\context $context): void {
        global $DB;

        if ($context->contextlevel != CONTEXT_MODULE) {
            return;
        }

        $DB->delete_records('availability_dripcontent_ntf', ['cmid' => $context->instanceid]);
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts and user information.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $userid = $contextlist->get_user()->id;

        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel != CONTEXT_MODULE) {
                continue;
            }

            $DB->delete_records('availability_dripcontent_ntf', [
                'userid' => $userid,
                'cmid' => $context->instanceid,
            ]);
        }
    }

    /**
     * Delete multiple users within a single context.
     *
     * @param approved_userlist $userlist The approved context and user information.
     */
    public static function delete_data_for_users(approved_userlist $userlist): void {
        global $DB;

        $context = $userlist->get_context();

        if ($context->contextlevel != CONTEXT_MODULE) {
            return;
        }

        $userids = $userlist->get_userids();
        if (empty($userids)) {
            return;
        }

        list($insql, $inparams) = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);
        $params = array_merge(['cmid' => $context->instanceid], $inparams);

        $DB->delete_records_select(
            'availability_dripcontent_ntf',
            "cmid = :cmid AND userid $insql",
            $params
        );
    }
}
