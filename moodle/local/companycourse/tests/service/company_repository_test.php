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
 * Tests for company_repository.
 *
 * @package    local_companycourse
 * @category   test
 * @copyright  2026 Inicie
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \local_companycourse\service\company_repository
 */
final class company_repository_test extends \advanced_testcase {

    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * Active companies are listed; suspended ones are excluded.
     */
    public function test_list_active_companies(): void {
        $active = local_companycourse_create_test_company(['name' => 'Active Co', 'shortname' => 'actco']);
        $suspended = local_companycourse_create_test_company([
            'name' => 'Suspended Co',
            'shortname' => 'susco',
            'suspended' => 1,
        ]);

        $list = (new company_repository())->list_active_companies();
        $this->assertArrayHasKey($active->id, $list);
        $this->assertArrayNotHasKey($suspended->id, $list);
        $this->assertEquals('Active Co', $list[$active->id]);
    }

    /**
     * Active company loads; missing and suspended raise exceptions.
     */
    public function test_get_active_company(): void {
        $repo = new company_repository();
        $active = local_companycourse_create_test_company(['name' => 'Ok Co', 'shortname' => 'okco']);

        $company = $repo->get_active_company((int) $active->id);
        $this->assertEquals($active->id, (int) $company->get('id'));
        $this->assertEquals((int) $active->category, $repo->get_course_category_id($company));

        try {
            $repo->get_active_company(999999);
            $this->fail('Expected replication_exception for missing company');
        } catch (\local_companycourse\exception\replication_exception $e) {
            $this->assertEquals('error_company_not_found', $e->errorcode);
        }

        $suspended = local_companycourse_create_test_company([
            'name' => 'Bad Co',
            'shortname' => 'badco',
            'suspended' => 1,
        ]);
        try {
            $repo->get_active_company((int) $suspended->id);
            $this->fail('Expected replication_exception for suspended company');
        } catch (\local_companycourse\exception\replication_exception $e) {
            $this->assertEquals('error_company_suspended', $e->errorcode);
        }
    }
}
