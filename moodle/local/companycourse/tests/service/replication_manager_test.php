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
 * Tests for replication_manager orchestration.
 *
 * Uses injected fakes for backup/restore and IOMAD where isolation is needed.
 *
 * @package    local_companycourse
 * @category   test
 * @copyright  2026 Inicie
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \local_companycourse\service\replication_manager
 */
final class replication_manager_test extends \advanced_testcase {

    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * Empty company list fails before any side effect.
     */
    public function test_replicate_requires_companies(): void {
        $course = $this->getDataGenerator()->create_course();
        $manager = new replication_manager();

        $this->expectException(\local_companycourse\exception\replication_exception::class);
        $manager->replicate((int) $course->id, [], 2);
    }

    /**
     * Invalid base course fails before iterating companies.
     */
    public function test_replicate_requires_valid_course(): void {
        $company = local_companycourse_create_test_company(['shortname' => 'rqco']);
        $manager = new replication_manager();

        $this->expectException(\local_companycourse\exception\replication_exception::class);
        $manager->replicate(999999, [(int) $company->id], 2);
    }

    /**
     * Successful path with injected replication and association services.
     */
    public function test_replicate_success_with_fakes(): void {
        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $company = local_companycourse_create_test_company(['shortname' => 'okrep']);

        $backupfile = make_temp_directory('local_companycourse_test') . '/backup.mbz';
        file_put_contents($backupfile, 'fake');

        $replication = $this->createMock(course_replication_service::class);
        $replication->expects($this->once())
            ->method('create_backup')
            ->with((int) $course->id)
            ->willReturn($backupfile);
        $replication->expects($this->once())
            ->method('restore_to_category')
            ->with($backupfile, (int) $company->category)
            ->willReturn(555);

        $iomad = $this->getMockBuilder(iomad_association_service::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['is_duplicate', 'resolve_category_id', 'associate_course'])
            ->getMock();
        $iomad->method('is_duplicate')->willReturn(false);
        $iomad->method('resolve_category_id')->with((int) $company->id)->willReturn((int) $company->category);
        $iomad->expects($this->once())->method('associate_course')->with((int) $company->id, 555);

        $logs = new log_service();
        $manager = new replication_manager(null, null, $replication, $iomad, $logs);
        $summary = $manager->replicate((int) $course->id, [(int) $company->id], (int) $user->id);

        $this->assertCount(1, $summary->results);
        $this->assertTrue($summary->results[0]->is_success());
        $this->assertEquals(555, $summary->results[0]->newcourseid);
        $this->assertTrue($logs->has_successful_replication((int) $course->id, (int) $company->id));
        $this->assertFalse(is_readable($backupfile));
    }

    /**
     * Duplicate company is skipped and logged without calling backup.
     */
    public function test_replicate_skips_duplicate(): void {
        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $company = local_companycourse_create_test_company(['shortname' => 'dupco']);

        $replication = $this->createMock(course_replication_service::class);
        $replication->expects($this->never())->method('create_backup');

        $iomad = $this->getMockBuilder(iomad_association_service::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['is_duplicate', 'resolve_category_id', 'associate_course'])
            ->getMock();
        $iomad->method('is_duplicate')->willReturn(true);

        $logs = new log_service();
        $manager = new replication_manager(null, null, $replication, $iomad, $logs);
        $summary = $manager->replicate((int) $course->id, [(int) $company->id], (int) $user->id);

        $this->assertEquals(
            \local_companycourse\local\replication_status::SKIPPED,
            $summary->results[0]->status
        );
        $history = $logs->get_history(1);
        $row = reset($history);
        $this->assertEquals(\local_companycourse\local\replication_status::SKIPPED, $row->status);
    }

    /**
     * Failure on one company does not abort the next company.
     */
    public function test_replicate_isolates_company_failures(): void {
        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $failing = local_companycourse_create_test_company(['shortname' => 'failco']);
        $ok = local_companycourse_create_test_company(['shortname' => 'passco']);

        $backupfile = make_temp_directory('local_companycourse_test') . '/backup2.mbz';
        file_put_contents($backupfile, 'fake');

        $replication = $this->createMock(course_replication_service::class);
        $replication->method('create_backup')->willReturn($backupfile);
        $replication->method('restore_to_category')->willReturn(777);

        $iomad = $this->getMockBuilder(iomad_association_service::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['is_duplicate', 'resolve_category_id', 'associate_course'])
            ->getMock();
        $iomad->method('is_duplicate')->willReturn(false);
        $iomad->method('resolve_category_id')->willReturnCallback(
            static function (int $companyid) use ($failing, $ok): int {
                if ($companyid === (int) $failing->id) {
                    throw new \local_companycourse\exception\replication_exception(
                        'error_association_failed',
                        'simulated association failure'
                    );
                }
                return (int) $ok->category;
            }
        );
        $iomad->expects($this->once())->method('associate_course');

        $manager = new replication_manager(null, null, $replication, $iomad, new log_service());
        $summary = $manager->replicate(
            (int) $course->id,
            [(int) $failing->id, (int) $ok->id],
            (int) $user->id
        );

        $this->assertCount(2, $summary->results);
        $this->assertEquals(
            \local_companycourse\local\replication_status::ERROR,
            $summary->results[0]->status
        );
        $this->assertEquals(
            \local_companycourse\local\replication_status::SUCCESS,
            $summary->results[1]->status
        );
        $this->assertEquals(1, $summary->count_by_status(\local_companycourse\local\replication_status::ERROR));
        $this->assertEquals(1, $summary->count_by_status(\local_companycourse\local\replication_status::SUCCESS));
    }

    /**
     * Log persistence failure must not hide the replication outcome.
     */
    public function test_replicate_keeps_result_when_log_fails(): void {
        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $company = local_companycourse_create_test_company(['shortname' => 'logfail']);

        $backupfile = make_temp_directory('local_companycourse_test') . '/backup3.mbz';
        file_put_contents($backupfile, 'fake');

        $replication = $this->createMock(course_replication_service::class);
        $replication->method('create_backup')->willReturn($backupfile);
        $replication->method('restore_to_category')->willReturn(888);

        $iomad = $this->getMockBuilder(iomad_association_service::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['is_duplicate', 'resolve_category_id', 'associate_course'])
            ->getMock();
        $iomad->method('is_duplicate')->willReturn(false);
        $iomad->method('resolve_category_id')->willReturn((int) $company->category);
        $iomad->expects($this->once())->method('associate_course')->with((int) $company->id, 888);

        $logs = $this->createMock(log_service::class);
        $logs->expects($this->once())
            ->method('log_result')
            ->willThrowException(new \RuntimeException('log insert failed'));

        $manager = new replication_manager(null, null, $replication, $iomad, $logs);
        $summary = $manager->replicate((int) $course->id, [(int) $company->id], (int) $user->id);

        $this->assertCount(1, $summary->results);
        $this->assertTrue($summary->results[0]->is_success());
        $this->assertEquals(888, $summary->results[0]->newcourseid);
        $this->assertStringContainsString(
            get_string('result_success', 'local_companycourse', 888),
            $summary->results[0]->message
        );
        $this->assertStringContainsString('log insert failed', $summary->results[0]->message);
    }

    /**
     * Restore failure persists friendly reason and technical detail in the log.
     */
    public function test_replicate_logs_restore_failure_reason_and_detail(): void {
        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $company = local_companycourse_create_test_company(['shortname' => 'rstfail']);

        $backupfile = make_temp_directory('local_companycourse_test') . '/backup4.mbz';
        file_put_contents($backupfile, 'fake');

        $replication = $this->createMock(course_replication_service::class);
        $replication->method('create_backup')->willReturn($backupfile);
        $replication->method('restore_to_category')->willThrowException(
            new \local_companycourse\exception\replication_exception(
                'error_restore_failed',
                'precheck blocked modules',
                'precheck blocked modules'
            )
        );

        $iomad = $this->getMockBuilder(iomad_association_service::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['is_duplicate', 'resolve_category_id', 'associate_course'])
            ->getMock();
        $iomad->method('is_duplicate')->willReturn(false);
        $iomad->method('resolve_category_id')->willReturn((int) $company->category);
        $iomad->expects($this->never())->method('associate_course');

        $logs = new log_service();
        $manager = new replication_manager(null, null, $replication, $iomad, $logs);
        $summary = $manager->replicate((int) $course->id, [(int) $company->id], (int) $user->id);

        $this->assertEquals(
            \local_companycourse\local\replication_status::ERROR,
            $summary->results[0]->status
        );
        $this->assertStringContainsString(
            get_string('error_restore_failed', 'local_companycourse', 'precheck blocked modules'),
            $summary->results[0]->message
        );
        $this->assertStringContainsString('precheck blocked modules', $summary->results[0]->message);

        $history = $logs->get_history(1);
        $row = reset($history);
        $this->assertEquals(\local_companycourse\local\replication_status::ERROR, $row->status);
        $this->assertStringContainsString('precheck blocked modules', $row->message);
        $this->assertStringContainsString(
            get_string('error_restore_failed', 'local_companycourse', 'precheck blocked modules'),
            $row->message
        );
    }

    /**
     * Association failure after restore deletes the orphan course.
     */
    public function test_replicate_deletes_course_when_association_fails(): void {
        global $DB;

        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $company = local_companycourse_create_test_company(['shortname' => 'orphco']);
        $restored = $this->getDataGenerator()->create_course(['category' => (int) $company->category]);

        $backupfile = make_temp_directory('local_companycourse_test') . '/backup5.mbz';
        file_put_contents($backupfile, 'fake');

        $replication = $this->createMock(course_replication_service::class);
        $replication->method('create_backup')->willReturn($backupfile);
        $replication->method('restore_to_category')->willReturn((int) $restored->id);

        $iomad = $this->getMockBuilder(iomad_association_service::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['is_duplicate', 'resolve_category_id', 'associate_course'])
            ->getMock();
        $iomad->method('is_duplicate')->willReturn(false);
        $iomad->method('resolve_category_id')->willReturn((int) $company->category);
        $iomad->method('associate_course')->willThrowException(
            new \local_companycourse\exception\replication_exception(
                'error_association_failed',
                'add_course rejected'
            )
        );

        $logs = new log_service();
        $manager = new replication_manager(null, null, $replication, $iomad, $logs);
        $summary = $manager->replicate((int) $course->id, [(int) $company->id], (int) $user->id);

        $this->assertEquals(
            \local_companycourse\local\replication_status::ERROR,
            $summary->results[0]->status
        );
        $this->assertNull($summary->results[0]->newcourseid);
        $this->assertFalse($DB->record_exists('course', ['id' => $restored->id]));

        $history = $logs->get_history(1);
        $row = reset($history);
        $this->assertEquals(\local_companycourse\local\replication_status::ERROR, $row->status);
        $this->assertStringContainsString('add_course rejected', $row->message);
    }
}
