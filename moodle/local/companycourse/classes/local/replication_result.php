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
 * Outcome of replicating one base course to one company.
 *
 * @package    local_companycourse
 * @copyright  2026 Wagner Dos Santos Backes <wagnerbackes@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_companycourse\local;

defined('MOODLE_INTERNAL') || die();

/**
 * Per-company replication result consumed by UI and log service.
 *
 * @package    local_companycourse
 * @copyright  2026 Wagner Dos Santos Backes <wagnerbackes@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class replication_result {

    /** @var int */
    public int $companyid;

    /** @var int Base course id. */
    public int $courseid;

    /** @var int|null Created course id when successful. */
    public ?int $newcourseid;

    /** @var string One of {@see replication_status} constants. */
    public string $status;

    /** @var string Human-readable message for UI and log. */
    public string $message;

    /**
     * @param int $companyid Destination company id.
     * @param int $courseid Base course id.
     * @param string $status Outcome status.
     * @param string $message Result message.
     * @param int|null $newcourseid New course id on success.
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
     * Whether this item completed successfully.
     *
     * @return bool
     */
    public function is_success(): bool {
        return $this->status === replication_status::SUCCESS;
    }
}
