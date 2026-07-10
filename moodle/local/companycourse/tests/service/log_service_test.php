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

/**
 * Tests for log_service.
 *
 * @package    local_companycourse
 * @category   test
 * @copyright  2026 Wagner Dos Santos Backes <wagnerbackes@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \local_companycourse\service\log_service
 */
final class log_service_test extends \advanced_testcase {

    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * Persists a result and reads it back from history.
     */
    public function test_log_result_and_history(): void {
        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();

        $result = new \local_companycourse\local\replication_result(
            10,
            (int) $course->id,
            \local_companycourse\local\replication_status::SUCCESS,
            'ok',
            99
        );

        $service = new log_service();
        $logid = $service->log_result((int) $user->id, $result);
        $this->assertGreaterThan(0, $logid);

        $history = $service->get_history(10);
        $this->assertNotEmpty($history);
        $row = reset($history);
        $this->assertEquals($user->id, $row->userid);
        $this->assertEquals($course->id, $row->courseid);
        $this->assertEquals(99, $row->newcourseid);
        $this->assertEquals(10, $row->companyid);
        $this->assertEquals(\local_companycourse\local\replication_status::SUCCESS, $row->status);
        $this->assertEquals('ok', $row->message);
        $this->assertNotEmpty($row->timecreated);
    }

    /**
     * Successful replication detection for duplicate checks.
     */
    public function test_has_successful_replication(): void {
        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $service = new log_service();

        $this->assertFalse($service->has_successful_replication((int) $course->id, 5));

        $service->log_result((int) $user->id, new \local_companycourse\local\replication_result(
            5,
            (int) $course->id,
            \local_companycourse\local\replication_status::ERROR,
            'fail',
            null
        ));
        $this->assertFalse($service->has_successful_replication((int) $course->id, 5));

        $service->log_result((int) $user->id, new \local_companycourse\local\replication_result(
            5,
            (int) $course->id,
            \local_companycourse\local\replication_status::SUCCESS,
            'ok',
            42
        ));
        $this->assertTrue($service->has_successful_replication((int) $course->id, 5));
    }
}
