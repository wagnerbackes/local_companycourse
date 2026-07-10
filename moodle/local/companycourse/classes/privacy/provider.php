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
 * Privacy subsystem implementation for local_companycourse.
 *
 * @package    local_companycourse
 * @copyright  2026 Inicie
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_companycourse\privacy;

defined('MOODLE_INTERNAL') || die();

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\transform;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;
use context_system;

/**
 * Privacy provider for replication operation logs.
 *
 * @package    local_companycourse
 * @copyright  2026 Inicie
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\core_userlist_provider,
    \core_privacy\local\request\plugin\provider {

    /**
     * Describe personal data stored by this plugin.
     *
     * @param collection $collection Metadata collection.
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table(
            'local_companycourse_log',
            [
                'userid' => 'privacy:metadata:local_companycourse_log:userid',
                'courseid' => 'privacy:metadata:local_companycourse_log:courseid',
                'newcourseid' => 'privacy:metadata:local_companycourse_log:newcourseid',
                'companyid' => 'privacy:metadata:local_companycourse_log:companyid',
                'status' => 'privacy:metadata:local_companycourse_log:status',
                'message' => 'privacy:metadata:local_companycourse_log:message',
                'timecreated' => 'privacy:metadata:local_companycourse_log:timecreated',
            ],
            'privacy:metadata:local_companycourse_log'
        );

        return $collection;
    }

    /**
     * List contexts that contain user data.
     *
     * @param int $userid User id.
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        global $DB;

        $contextlist = new contextlist();
        if ($DB->record_exists('local_companycourse_log', ['userid' => $userid])) {
            $contextlist->add_system_context();
        }

        return $contextlist;
    }

    /**
     * Export user data for approved contexts.
     *
     * @param approved_contextlist $contextlist Approved contexts.
     */
    public static function export_user_data(approved_contextlist $contextlist): void {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $user = $contextlist->get_user();
        $context = context_system::instance();
        $hasystem = false;
        foreach ($contextlist->get_contexts() as $ctx) {
            if ($ctx->id === $context->id) {
                $hasystem = true;
                break;
            }
        }
        if (!$hasystem) {
            return;
        }

        $records = $DB->get_records('local_companycourse_log', ['userid' => $user->id], 'timecreated DESC');
        if (!$records) {
            return;
        }

        $export = [];
        foreach ($records as $record) {
            $export[] = (object) [
                'courseid' => $record->courseid,
                'newcourseid' => $record->newcourseid,
                'companyid' => $record->companyid,
                'status' => $record->status,
                'message' => $record->message,
                'timecreated' => transform::datetime((int) $record->timecreated),
            ];
        }

        writer::with_context($context)->export_data(
            [get_string('privacy:export:logs', 'local_companycourse')],
            (object) ['logs' => $export]
        );
    }

    /**
     * Delete all data for all users in the context.
     *
     * @param \context $context Context.
     */
    public static function delete_data_for_all_users_in_context(\context $context): void {
        global $DB;

        if ($context->contextlevel !== CONTEXT_SYSTEM) {
            return;
        }

        $DB->delete_records('local_companycourse_log');
    }

    /**
     * Delete all user data for the specified user in the approved contexts.
     *
     * @param approved_contextlist $contextlist Approved contexts.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $userid = $contextlist->get_user()->id;
        $systemid = context_system::instance()->id;
        foreach ($contextlist->get_contextids() as $contextid) {
            if ((int) $contextid === (int) $systemid) {
                $DB->delete_records('local_companycourse_log', ['userid' => $userid]);
                return;
            }
        }
    }

    /**
     * Get users who have data in a context.
     *
     * @param userlist $userlist User list.
     */
    public static function get_users_in_context(userlist $userlist): void {
        $context = $userlist->get_context();
        if ($context->contextlevel !== CONTEXT_SYSTEM) {
            return;
        }

        $sql = "SELECT userid FROM {local_companycourse_log}";
        $userlist->add_from_sql('userid', $sql, []);
    }

    /**
     * Delete data for multiple users in a single context.
     *
     * @param approved_userlist $userlist Approved users.
     */
    public static function delete_data_for_users(approved_userlist $userlist): void {
        global $DB;

        $context = $userlist->get_context();
        if ($context->contextlevel !== CONTEXT_SYSTEM) {
            return;
        }

        $userids = $userlist->get_userids();
        if ($userids === []) {
            return;
        }

        list($insql, $params) = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);
        $DB->delete_records_select('local_companycourse_log', "userid {$insql}", $params);
    }
}
