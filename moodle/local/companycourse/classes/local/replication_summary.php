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

namespace local_companycourse\local;

defined('MOODLE_INTERNAL') || die();

/**
 * Aggregates per-company {@see replication_result} items.
 *
 * @package    local_companycourse
 * @copyright  2026 Inicie
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class replication_summary {

    public int $courseid;

    public int $userid;

    /** @var replication_result[] */
    public array $results = [];

    /**
     * @param int $courseid
     * @param int $userid
     */
    public function __construct(int $courseid, int $userid) {
        $this->courseid = $courseid;
        $this->userid = $userid;
    }

    /**
     * @param replication_result $result
     */
    public function add_result(replication_result $result): void {
        $this->results[] = $result;
    }

    /**
     * @param string $status
     * @return int
     */
    public function count_by_status(string $status): int {
        $count = 0;
        foreach ($this->results as $result) {
            if ($result->status === $status) {
                $count++;
            }
        }
        return $count;
    }
}
