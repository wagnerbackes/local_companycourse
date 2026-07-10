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
 * Orchestrates multi-company course replication.
 *
 * @package    local_companycourse
 * @copyright  2026 Wagner Dos Santos Backes <wagnerbackes@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_companycourse\service;

defined('MOODLE_INTERNAL') || die();

use local_companycourse\exception\replication_exception;
use local_companycourse\local\replication_result;
use local_companycourse\local\replication_status;
use local_companycourse\local\replication_summary;

/**
 * Orchestrates multi-company course replication with per-company failure isolation.
 *
 * @package    local_companycourse
 * @copyright  2026 Wagner Dos Santos Backes <wagnerbackes@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class replication_manager {

    /** @var course_validator */
    private course_validator $courses;

    /** @var company_repository */
    private company_repository $companies;

    /** @var course_replication_service */
    private course_replication_service $replication;

    /** @var iomad_association_service */
    private iomad_association_service $iomad;

    /** @var log_service */
    private log_service $logs;

    /** @var bool */
    private bool $includeusers = false;

    /**
     * @param course_validator|null $courses Course validation.
     * @param company_repository|null $companies Company access.
     * @param course_replication_service|null $replication Backup/restore.
     * @param iomad_association_service|null $iomad IOMAD association.
     * @param log_service|null $logs Log persistence.
     */
    public function __construct(
        ?course_validator $courses = null,
        ?company_repository $companies = null,
        ?course_replication_service $replication = null,
        ?iomad_association_service $iomad = null,
        ?log_service $logs = null
    ) {
        $this->courses = $courses ?? new course_validator();
        $this->companies = $companies ?? new company_repository();
        $this->replication = $replication ?? new course_replication_service();
        $this->logs = $logs ?? new log_service();
        $this->iomad = $iomad ?? new iomad_association_service($this->companies, $this->logs);
    }

    /**
     * Replicate a base course to one or more companies.
     *
     * @param int $courseid Base course id.
     * @param int[] $companyids Destination company ids.
     * @param int $userid Acting user id.
     * @param bool $includeusers Include user data in backup/restore (default false).
     * @return replication_summary
     * @throws replication_exception When course is invalid or company list is empty.
     */
    public function replicate(
        int $courseid,
        array $companyids,
        int $userid,
        bool $includeusers = false
    ): replication_summary {
        $this->courses->require_base_course($courseid);

        $companyids = array_values(array_unique(array_map('intval', $companyids)));
        $companyids = array_filter($companyids, static fn(int $id): bool => $id > 0);
        if ($companyids === []) {
            throw new replication_exception('error_no_companies');
        }

        $this->includeusers = $includeusers;
        $summary = new replication_summary($courseid, $userid);

        $backupfile = null;
        try {
            foreach ($companyids as $companyid) {
                $result = $this->replicate_one($courseid, $companyid, $backupfile);
                $result = $this->persist_log($userid, $result);
                $summary->add_result($result);
            }
        } finally {
            if ($backupfile !== null && is_readable($backupfile)) {
                @unlink($backupfile);
            }
        }

        return $summary;
    }

    /**
     * @param int $userid Acting user id.
     * @param replication_result $result Per-company outcome.
     * @return replication_result
     */
    private function persist_log(int $userid, replication_result $result): replication_result {
        try {
            $this->logs->log_result($userid, $result);
            return $result;
        } catch (\Throwable $e) {
            return new replication_result(
                $result->companyid,
                $result->courseid,
                $result->status,
                $result->message . ' ' . get_string('error_log_failed', 'local_companycourse', $e->getMessage()),
                $result->newcourseid
            );
        }
    }

    /**
     * @param int $courseid Base course id.
     * @param int $companyid Company id.
     * @param string|null $backupfile Shared backup path (created lazily).
     * @return replication_result
     */
    private function replicate_one(int $courseid, int $companyid, ?string &$backupfile): replication_result {
        $newcourseid = null;
        try {
            $this->companies->get_active_company($companyid);

            if ($this->iomad->is_duplicate($courseid, $companyid)) {
                return new replication_result(
                    $companyid,
                    $courseid,
                    replication_status::SKIPPED,
                    get_string('result_skipped_duplicate', 'local_companycourse'),
                    null
                );
            }

            $categoryid = $this->iomad->resolve_category_id($companyid);

            if ($backupfile === null) {
                if (get_class($this->replication) === course_replication_service::class) {
                    $this->replication = new course_replication_service($this->includeusers);
                }
                $backupfile = $this->replication->create_backup($courseid);
            }

            $newcourseid = $this->replication->restore_to_category($backupfile, $categoryid);
            $this->iomad->associate_course($companyid, $newcourseid);

            return new replication_result(
                $companyid,
                $courseid,
                replication_status::SUCCESS,
                get_string('result_success', 'local_companycourse', $newcourseid),
                $newcourseid
            );
        } catch (replication_exception $e) {
            $this->delete_restored_course($newcourseid);
            return new replication_result(
                $companyid,
                $courseid,
                replication_status::ERROR,
                $this->format_exception_message($e),
                null
            );
        } catch (\Throwable $e) {
            $this->delete_restored_course($newcourseid);
            return new replication_result(
                $companyid,
                $courseid,
                replication_status::ERROR,
                get_string('error_unexpected', 'local_companycourse', $e->getMessage()),
                null
            );
        }
    }

    /**
     * @param int|null $courseid Restored course id, if any.
     */
    private function delete_restored_course(?int $courseid): void {
        global $CFG, $DB;

        if ($courseid === null || $courseid < 1) {
            return;
        }

        require_once($CFG->dirroot . '/course/lib.php');
        if ($DB->record_exists('course', ['id' => $courseid])) {
            delete_course($courseid, false);
        }
    }

    /**
     * @param replication_exception $e Domain exception.
     * @return string
     */
    private function format_exception_message(replication_exception $e): string {
        $message = $e->getMessage();
        $debuginfo = trim((string) ($e->debuginfo ?? ''));

        if ($debuginfo !== '' && stripos($message, $debuginfo) === false) {
            $message .= ' ' . get_string('error_technical_detail', 'local_companycourse', $debuginfo);
        }

        $a = $e->a ?? null;
        if (is_string($a)) {
            $adetail = trim($a);
            if ($adetail !== '' && stripos($message, $adetail) === false) {
                $message .= ' ' . get_string('error_technical_detail', 'local_companycourse', $adetail);
            }
        }

        return $message;
    }
}
