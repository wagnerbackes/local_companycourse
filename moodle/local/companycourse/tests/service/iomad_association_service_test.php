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

namespace local_companycourse\service;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/local/companycourse/tests/helpers.php');

/**
 * Tests for IOMAD association.
 *
 * @package    local_companycourse
 * @category   test
 * @copyright  2026 Wagner Dos Santos Backes <wagnerbackes@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \local_companycourse\service\iomad_association_service
 */
final class iomad_association_service_test extends \advanced_testcase {

    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
        $this->setAdminUser();
    }

    /**
     * Reuses company.category when the course category exists.
     */
    public function test_resolve_category_reuses_existing(): void {
        $company = local_companycourse_create_test_company(['shortname' => 'reuse']);
        $service = new iomad_association_service();

        $categoryid = $service->resolve_category_id((int) $company->id);
        $this->assertEquals((int) $company->category, $categoryid);
        $this->assertEquals($categoryid, $service->resolve_category_id((int) $company->id));
    }

    /**
     * Creates a category when company.category is invalid.
     */
    public function test_resolve_category_creates_when_missing(): void {
        global $DB;

        $company = local_companycourse_create_test_company(['shortname' => 'nocat']);
        $DB->set_field('company', 'category', 0, ['id' => $company->id]);

        $service = new iomad_association_service();
        $categoryid = $service->resolve_category_id((int) $company->id);

        $this->assertGreaterThan(0, $categoryid);
        $this->assertTrue($DB->record_exists('course_categories', ['id' => $categoryid]));
        $this->assertEquals($categoryid, (int) $DB->get_field('company', 'category', ['id' => $company->id]));
    }

    /**
     * Duplicate detection follows successful log rows.
     */
    public function test_is_duplicate_from_log(): void {
        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $company = local_companycourse_create_test_company(['shortname' => 'dup07']);

        $logs = new log_service();
        $service = new iomad_association_service(null, $logs);

        $this->assertFalse($service->is_duplicate((int) $course->id, (int) $company->id));

        $logs->log_result((int) $user->id, new \local_companycourse\local\replication_result(
            (int) $company->id,
            (int) $course->id,
            \local_companycourse\local\replication_status::SUCCESS,
            'ok',
            1
        ));
        $this->assertTrue($service->is_duplicate((int) $course->id, (int) $company->id));
    }

    /**
     * associate_course links via company::add_course and keeps course in company category.
     */
    public function test_associate_course_creates_iomad_links(): void {
        global $DB;

        $company = local_companycourse_create_test_company(['shortname' => 'assoc']);
        $othercat = \core_course_category::create((object) ['name' => 'Other', 'parent' => 0]);
        $course = $this->getDataGenerator()->create_course(['category' => $othercat->id]);

        $service = new iomad_association_service();
        $service->associate_course((int) $company->id, (int) $course->id);

        $course = $DB->get_record('course', ['id' => $course->id], '*', MUST_EXIST);
        $this->assertEquals((int) $company->category, (int) $course->category);

        $this->assertTrue($DB->record_exists('company_course', [
            'companyid' => $company->id,
            'courseid' => $course->id,
        ]));
        $this->assertTrue($DB->record_exists('iomad_courses', [
            'courseid' => $course->id,
            'shared' => 0,
        ]));
        $this->assertTrue($DB->record_exists('company_created_courses', [
            'companyid' => $company->id,
            'courseid' => $course->id,
        ]));
    }

    /**
     * Missing restored course fails with domain exception.
     */
    public function test_associate_course_missing_course(): void {
        $company = local_companycourse_create_test_company(['shortname' => 'miss']);
        $service = new iomad_association_service();

        try {
            $service->associate_course((int) $company->id, 999999);
            $this->fail('Expected replication_exception');
        } catch (\local_companycourse\exception\replication_exception $e) {
            $this->assertEquals('error_course_not_found', $e->errorcode);
        }
    }
}
