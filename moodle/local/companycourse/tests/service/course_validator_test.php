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
 * Tests for course_validator.
 *
 * @package    local_companycourse
 * @category   test
 * @copyright  2026 Inicie
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \local_companycourse\service\course_validator
 */
final class course_validator_test extends \advanced_testcase {

    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * Valid course is returned.
     */
    public function test_require_base_course(): void {
        $validator = new course_validator();
        $course = $this->getDataGenerator()->create_course();

        $loaded = $validator->require_base_course((int) $course->id);
        $this->assertEquals($course->id, $loaded->id);
    }

    /**
     * Site course cannot be used as base.
     */
    public function test_require_base_course_rejects_site(): void {
        $validator = new course_validator();
        try {
            $validator->require_base_course(SITEID);
            $this->fail('Expected replication_exception for site course');
        } catch (\local_companycourse\exception\replication_exception $e) {
            $this->assertEquals('error_course_not_found', $e->errorcode);
        }
    }

    /**
     * Missing course id raises domain exception.
     */
    public function test_require_base_course_missing(): void {
        $validator = new course_validator();
        try {
            $validator->require_base_course(999999);
            $this->fail('Expected replication_exception for missing course');
        } catch (\local_companycourse\exception\replication_exception $e) {
            $this->assertEquals('error_course_not_found', $e->errorcode);
        }
    }

    /**
     * List excludes the site course and includes created courses.
     */
    public function test_list_base_courses(): void {
        $course = $this->getDataGenerator()->create_course(['fullname' => 'Alpha Base', 'shortname' => 'AB1']);
        $options = (new course_validator())->list_base_courses();

        $this->assertArrayNotHasKey(SITEID, $options);
        $this->assertArrayHasKey($course->id, $options);
        $this->assertStringContainsString('Alpha Base', $options[$course->id]);
        $this->assertStringContainsString(get_string('basecourse_nocompany', 'local_companycourse'), $options[$course->id]);
    }

    /**
     * Linked company name appears in the base course label.
     */
    public function test_list_base_courses_includes_company(): void {
        global $DB;

        $company = local_companycourse_create_test_company(['name' => 'Acme Corp', 'shortname' => 'acme']);
        $course = $this->getDataGenerator()->create_course(['fullname' => 'Linked Course', 'shortname' => 'LC1']);
        $DB->insert_record('company_course', (object) [
            'companyid' => $company->id,
            'courseid' => $course->id,
            'departmentid' => 0,
        ]);

        $options = (new course_validator())->list_base_courses();
        $this->assertArrayHasKey($course->id, $options);
        $this->assertStringContainsString('Linked Course', $options[$course->id]);
        $this->assertStringContainsString('Acme Corp', $options[$course->id]);
    }
}
