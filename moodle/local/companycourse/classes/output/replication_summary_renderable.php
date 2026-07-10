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
 * Renderable for the replication summary.
 *
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

    /** @var replication_summary */
    private replication_summary $summary;

    /** @var array companyid => name */
    private array $companynames;

    /** @var string Base course label. */
    private string $coursename;

    /**
     * @param replication_summary $summary Batch summary.
     * @param string $coursename Course display name.
     * @param array $companynames Map of company id => name.
     */
    public function __construct(replication_summary $summary, string $coursename, array $companynames = []) {
        $this->summary = $summary;
        $this->coursename = $coursename;
        $this->companynames = $companynames;
    }

    /**
     * Export for template.
     *
     * @param renderer_base $output Renderer.
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
            'historyurl' => (new \moodle_url('/local/companycourse/history.php'))->out(false),
        ];
    }
}
