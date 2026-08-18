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
 * Per-company replication result.
 *
 * @package    local_companycourse
 * @copyright  2026 Inicie
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class replication_result {

    public int $companyid;

    public int $courseid;

    public ?int $newcourseid;

    public string $status;

    public string $message;

    /**
     * @param int $companyid
     * @param int $courseid
     * @param string $status
     * @param string $message
     * @param int|null $newcourseid
     */
    public function __construct(
        int $companyid,
        int $courseid,
        string $status,
        string $message,
        ?int $newcourseid = null
    ) {
        $this->companyid = $companyid;
        $this->courseid = $courseid;
        $this->status = $status;
        $this->message = $message;
        $this->newcourseid = $newcourseid;
    }

    /**
     * @return bool
     */
    public function is_success(): bool {
        return $this->status === replication_status::SUCCESS;
    }
}
