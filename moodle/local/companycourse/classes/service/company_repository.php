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
 * Access and validation helpers for IOMAD companies.
 *
 * @package    local_companycourse
 * @copyright  2026 Inicie
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_companycourse\service;

defined('MOODLE_INTERNAL') || die();

use local_companycourse\exception\replication_exception;

/**
 * Lists and validates IOMAD companies for replication.
 *
 * @package    local_companycourse
 * @copyright  2026 Inicie
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class company_repository {

    /**
     * Ensure company.php is loaded once.
     */
    private function require_company_lib(): void {
        global $CFG;

        require_once($CFG->dirroot . '/local/iomad/lib/company.php');
    }

    /**
     * Active companies for admin select (id => name).
     *
     * @return array
     */
    public function list_active_companies(): array {
        global $DB;

        return $DB->get_records_menu(
            'company',
            ['suspended' => 0],
            'name ASC',
            'id, name'
        );
    }

    /**
     * Load and validate an active (non-suspended) company.
     *
     * @param int $companyid Company id.
     * @return \company
     * @throws replication_exception
     */
    public function get_active_company(int $companyid): \company {
        global $DB;

        $this->require_company_lib();

        if ($companyid < 1 || !$DB->record_exists('company', ['id' => $companyid])) {
            throw new replication_exception('error_company_not_found', $companyid);
        }

        $company = new \company($companyid);
        if ((int) $company->get('suspended') !== 0) {
            throw new replication_exception('error_company_suspended', $company->get('name'));
        }

        return $company;
    }

    /**
     * Course category id from company.category.
     *
     * @param \company $company Company instance.
     * @return int Category id (0 if unset).
     */
    public function get_course_category_id(\company $company): int {
        return (int) $company->get('category');
    }
}
