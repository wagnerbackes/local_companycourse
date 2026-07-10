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

namespace local_companycourse\local;

/**
 * DTO / status value object tests.
 *
 * @package    local_companycourse
 * @category   test
 * @copyright  2026 Wagner Dos Santos Backes <wagnerbackes@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \local_companycourse\local\replication_result
 * @covers \local_companycourse\local\replication_summary
 * @covers \local_companycourse\local\replication_status
 */
final class replication_result_test extends \basic_testcase {

    /**
     * Result and summary helpers behave as expected.
     */
    public function test_result_and_summary(): void {
        $result = new replication_result(1, 2, replication_status::SUCCESS, 'ok', 3);
        $this->assertTrue($result->is_success());

        $error = new replication_result(1, 2, replication_status::ERROR, 'fail');
        $this->assertFalse($error->is_success());

        $summary = new replication_summary(2, 9);
        $summary->add_result($result);
        $summary->add_result($error);
        $this->assertEquals(1, $summary->count_by_status(replication_status::SUCCESS));
        $this->assertEquals(1, $summary->count_by_status(replication_status::ERROR));
        $this->assertEquals(0, $summary->count_by_status(replication_status::SKIPPED));
    }
}
