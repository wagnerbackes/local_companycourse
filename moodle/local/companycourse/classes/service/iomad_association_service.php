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
 * IOMAD company association after course restore.
 *
 * @package    local_companycourse
 * @copyright  2026 Wagner Dos Santos Backes <wagnerbackes@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_companycourse\service;

defined('MOODLE_INTERNAL') || die();

use local_companycourse\exception\replication_exception;

/**
 * Resolves company category, detects duplicates and calls company::add_course().
 *
 * @package    local_companycourse
 * @copyright  2026 Wagner Dos Santos Backes <wagnerbackes@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class iomad_association_service {

    /** @var company_repository */
    private company_repository $companies;

    /** @var log_service */
    private log_service $logs;

    /**
     * @param company_repository|null $companies Company access.
     * @param log_service|null $logs Log access for duplicate checks.
     */
    public function __construct(?company_repository $companies = null, ?log_service $logs = null) {
        $this->companies = $companies ?? new company_repository();
        $this->logs = $logs ?? new log_service();
    }

    /**
     * Resolve destination course category for the company.
     *
     * @param int $companyid Company id.
     * @return int Valid course_categories id.
     * @throws replication_exception
     */
    public function resolve_category_id(int $companyid): int {
        $company = $this->companies->get_active_company($companyid);
        return $this->ensure_course_category($company);
    }

    /**
     * @param int $courseid Base course id.
     * @param int $companyid Company id.
     * @return bool
     */
    public function is_duplicate(int $courseid, int $companyid): bool {
        return $this->logs->has_successful_replication($courseid, $companyid);
    }

    /**
     * Associate restored course to the company via company::add_course().
     *
     * @param int $companyid Company id.
     * @param int $newcourseid Restored course id.
     * @throws replication_exception
     */
    public function associate_course(int $companyid, int $newcourseid): void {
        global $DB, $CFG;

        require_once($CFG->dirroot . '/course/lib.php');
        require_once($CFG->dirroot . '/local/iomad/lib/company.php');

        $company = $this->companies->get_active_company($companyid);

        $course = $DB->get_record('course', ['id' => $newcourseid], '*', IGNORE_MISSING);
        if (!$course || (int) $course->id === (int) SITEID) {
            throw new replication_exception('error_course_not_found', $newcourseid);
        }

        $categoryid = $this->ensure_course_category($company);
        if ((int) $course->category !== $categoryid) {
            if (!move_courses([$newcourseid], $categoryid)) {
                throw new replication_exception(
                    'error_association_failed',
                    get_string('error_move_course_failed', 'local_companycourse', $newcourseid)
                );
            }
            $course = $DB->get_record('course', ['id' => $newcourseid], '*', MUST_EXIST);
        }

        try {
            $ok = $company->add_course($course, 0, true, false);
        } catch (\Throwable $e) {
            throw new replication_exception('error_association_failed', $e->getMessage(), $e->getMessage());
        }

        if ($ok !== true) {
            throw new replication_exception(
                'error_association_failed',
                get_string('error_add_course_failed', 'local_companycourse', $newcourseid)
            );
        }

        if (!$DB->record_exists('company_course', [
            'companyid' => $companyid,
            'courseid' => $newcourseid,
        ])) {
            throw new replication_exception(
                'error_association_failed',
                get_string('error_company_course_missing', 'local_companycourse', $newcourseid)
            );
        }
    }

    /**
     * @param \company $company Active company.
     * @return int Valid course_categories id.
     */
    private function ensure_course_category(\company $company): int {
        global $DB;

        $companyid = (int) $company->get('id');
        $categoryid = $this->companies->get_course_category_id($company);

        if ($categoryid > 0 && $DB->record_exists('course_categories', ['id' => $categoryid])) {
            return $categoryid;
        }

        $category = \core_course_category::create((object) [
            'name' => format_string($company->get('name')),
            'parent' => 0,
        ]);

        $DB->set_field('company', 'category', $category->id, ['id' => $companyid]);

        return (int) $category->id;
    }
}
