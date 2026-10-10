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
 * @package    local_companycourse
 * @copyright  2026 Inicie
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_companycourse\service;

defined('MOODLE_INTERNAL') || die();

use local_companycourse\local\replication_result;
use local_companycourse\local\replication_status;

/**
 * Writes and reads rows in local_companycourse_log.
 *
 * @package    local_companycourse
 * @copyright  2026 Inicie
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class log_service {

    /**
     * @param int $userid
     * @param replication_result $result
     * @return int Inserted log id.
     */
    public function log_result(int $userid, replication_result $result): int {
        global $DB;

        $record = (object) [
            'userid' => $userid,
            'courseid' => $result->courseid,
            'newcourseid' => $result->newcourseid,
            'companyid' => $result->companyid,
            'status' => $result->status,
            'message' => $result->message,
            'timecreated' => time(),
        ];

        return (int) $DB->insert_record('local_companycourse_log', $record);
    }

    /**
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function get_history(int $limit = 100, int $offset = 0): array {
        global $DB;

        return $DB->get_records(
            'local_companycourse_log',
            null,
            'timecreated DESC, id DESC',
            '*',
            $offset,
            $limit
        );
    }

    /**
     * @param int $courseid
     * @param int $companyid
     * @return bool
     */
    public function has_successful_replication(int $courseid, int $companyid): bool {
        global $DB;

        return $DB->record_exists('local_companycourse_log', [
            'courseid' => $courseid,
            'companyid' => $companyid,
            'status' => replication_status::SUCCESS,
        ]);
    }
}
