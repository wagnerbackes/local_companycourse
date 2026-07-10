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

namespace local_companycourse\form;

defined('MOODLE_INTERNAL') || die();

/**
 * Tests for replicate_form validation.
 *
 * @package    local_companycourse
 * @category   test
 * @copyright  2026 Inicie
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \local_companycourse\form\replicate_form
 */
final class replicate_form_test extends \advanced_testcase {

    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
        $this->setAdminUser();
    }

    /**
     * Form validation rejects empty course/companies.
     */
    public function test_validation_requires_course_and_companies(): void {
        $form = new replicate_form(null, [
            'courses' => [2 => 'Course A'],
            'companies' => [1 => 'Acme'],
        ], 'post', '', null, true, null, true);

        $errors = $form->validation([
            'courseid' => 0,
            'companyids' => [],
            'includeusers' => 0,
        ], []);
        $this->assertArrayHasKey('courseid', $errors);
        $this->assertArrayHasKey('companyids', $errors);

        $errors = $form->validation([
            'courseid' => 2,
            'companyids' => [1],
            'includeusers' => 0,
        ], []);
        $this->assertArrayNotHasKey('courseid', $errors);
        $this->assertArrayNotHasKey('companyids', $errors);
    }

    /**
     * includeusers defaults to disabled.
     */
    public function test_includeusers_default_off(): void {
        $form = new replicate_form(null, [
            'courses' => [2 => 'Course A'],
            'companies' => [1 => 'Acme'],
        ], 'post', '', null, true, null, true);

        $reflection = new \ReflectionClass($form);
        $property = $reflection->getProperty('_form');
        $defaults = $property->getValue($form)->_defaultValues;
        $this->assertSame(0, (int) ($defaults['includeusers'] ?? -1));
    }
}
