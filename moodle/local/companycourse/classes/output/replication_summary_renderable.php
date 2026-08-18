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

namespace local_companycourse\output;

defined('MOODLE_INTERNAL') || die();

use local_companycourse\local\replication_status;
use local_companycourse\local\replication_summary;
use renderable;
use renderer_base;
use templatable;

/**
 * Exports replication_summary for Mustache.
 *
 * @package    local_companycourse
 * @copyright  2026 Inicie
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class replication_summary_renderable implements renderable, templatable {

    private replication_summary $summary;

    private array $companynames;

    private string $coursename;

    private bool $dashboard;

    /**
     * @param replication_summary $summary
     * @param string $coursename
     * @param array $companynames
     * @param bool $dashboard
     */
    public function __construct(
        replication_summary $summary,
        string $coursename,
        array $companynames = [],
        bool $dashboard = false
    ) {
        $this->summary = $summary;
        $this->coursename = $coursename;
        $this->companynames = $companynames;
        $this->dashboard = $dashboard;
    }

    /**
     * @param renderer_base $output
     * @return \stdClass
     */
    public function export_for_template(renderer_base $output): \stdClass {
        $success = $this->summary->count_by_status(replication_status::SUCCESS);
        $error = $this->summary->count_by_status(replication_status::ERROR);
        $skipped = $this->summary->count_by_status(replication_status::SKIPPED);

        $rows = [];
        foreach ($this->summary->results as $result) {
            $rowclass = '';
            if ($result->status === replication_status::SUCCESS) {
                $rowclass = 'table-success';
            } else if ($result->status === replication_status::ERROR) {
                $rowclass = 'table-danger';
            } else if ($result->status === replication_status::SKIPPED) {
                $rowclass = 'table-warning';
            }

            $rows[] = [
                'company' => $this->companynames[$result->companyid] ?? (string) $result->companyid,
                'status' => get_string('status_' . $result->status, 'local_companycourse'),
                'rowclass' => $rowclass,
                'message' => $result->message,
                'newcourseid' => $result->newcourseid,
                'hasnewcourse' => !empty($result->newcourseid),
            ];
        }

        return (object) [
            'coursename' => $this->coursename,
            'courselabel' => get_string('summarycourse', 'local_companycourse', $this->coursename),
            'countslabel' => get_string('summarycounts', 'local_companycourse', (object) [
                'total' => count($this->summary->results),
                'success' => $success,
                'error' => $error,
                'skipped' => $skipped,
            ]),
            'countsuccess' => $success,
            'counterror' => $error,
            'countskipped' => $skipped,
            'counttotal' => count($this->summary->results),
            'rows' => $rows,
            'hassuccess' => $success > 0,
            'haserror' => $error > 0,
            'historyurl' => (new \moodle_url(
                '/local/companycourse/history.php',
                $this->dashboard ? ['dashboard' => 1] : []
            ))->out(false),
        ];
    }
}
