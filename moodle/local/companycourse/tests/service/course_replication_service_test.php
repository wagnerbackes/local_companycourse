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

/**
 * Tests for native backup/restore course replication.
 *
 * @package    local_companycourse
 * @category   test
 * @copyright  2026 Inicie
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \local_companycourse\service\course_replication_service
 */
final class course_replication_service_test extends \advanced_testcase {

    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
        $this->setAdminUser();
    }

    /**
     * Backup produces a readable .mbz file.
     */
    public function test_create_backup_file(): void {
        $generator = $this->getDataGenerator();
        $course = $generator->create_course(['fullname' => 'Backup Source', 'shortname' => 'BKS']);
        $generator->create_module('page', [
            'course' => $course->id,
            'name' => 'Intro page',
            'content' => 'Hello replication',
        ]);

        $service = new course_replication_service();
        $backupfile = $service->create_backup((int) $course->id);

        $this->assertFileExists($backupfile);
        $this->assertGreaterThan(100, filesize($backupfile));
        @unlink($backupfile);
    }

    /**
     * One backup restored twice yields two distinct courses with modules.
     */
    public function test_one_backup_two_restores(): void {
        global $DB;

        $generator = $this->getDataGenerator();
        $course = $generator->create_course([
            'fullname' => 'Template Course',
            'shortname' => 'TMPL',
            'format' => 'topics',
            'numsections' => 2,
        ], ['createsections' => true]);
        $generator->create_module('page', [
            'course' => $course->id,
            'name' => 'Template page',
            'content' => 'Template body',
        ]);
        $generator->create_module('forum', [
            'course' => $course->id,
            'name' => 'Template forum',
        ]);

        $cat1 = \core_course_category::create((object) ['name' => 'Dest A', 'parent' => 0]);
        $cat2 = \core_course_category::create((object) ['name' => 'Dest B', 'parent' => 0]);

        $service = new course_replication_service();
        $backupfile = $service->create_backup((int) $course->id);

        $new1 = $service->restore_to_category($backupfile, (int) $cat1->id);
        $new2 = $service->restore_to_category($backupfile, (int) $cat2->id);
        @unlink($backupfile);

        $this->assertNotEquals($course->id, $new1);
        $this->assertNotEquals($course->id, $new2);
        $this->assertNotEquals($new1, $new2);

        $course1 = $DB->get_record('course', ['id' => $new1], '*', MUST_EXIST);
        $course2 = $DB->get_record('course', ['id' => $new2], '*', MUST_EXIST);
        $this->assertEquals($cat1->id, (int) $course1->category);
        $this->assertEquals($cat2->id, (int) $course2->category);

        $modinfo1 = get_fast_modinfo($new1);
        $modinfo2 = get_fast_modinfo($new2);
        $this->assertNotEmpty($modinfo1->get_instances_of('page'));
        $this->assertNotEmpty($modinfo1->get_instances_of('forum'));
        $this->assertNotEmpty($modinfo2->get_instances_of('page'));
        $this->assertNotEmpty($modinfo2->get_instances_of('forum'));
    }

    /**
     * Invalid category fails with domain exception.
     */
    public function test_restore_invalid_category(): void {
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $service = new course_replication_service();
        $backupfile = $service->create_backup((int) $course->id);

        try {
            $service->restore_to_category($backupfile, 999999);
            $this->fail('Expected replication_exception');
        } catch (\local_companycourse\exception\replication_exception $e) {
            $this->assertEquals('error_category_not_found', $e->errorcode);
        } finally {
            @unlink($backupfile);
        }
    }

    /**
     * Missing backup file fails with domain exception.
     */
    public function test_restore_missing_file(): void {
        $cat = \core_course_category::create((object) ['name' => 'Dest Missing', 'parent' => 0]);
        $service = new course_replication_service();

        $this->expectException(\local_companycourse\exception\replication_exception::class);
        $service->restore_to_category('/tmp/does-not-exist-' . uniqid() . '.mbz', (int) $cat->id);
    }
}
