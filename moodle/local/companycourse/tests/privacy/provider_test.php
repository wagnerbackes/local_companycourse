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

namespace local_companycourse\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\writer;
use local_companycourse\local\replication_result;
use local_companycourse\local\replication_status;
use local_companycourse\service\log_service;

/**
 * Privacy provider tests.
 *
 * @package    local_companycourse
 * @category   test
 * @copyright  2026 Inicie
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \local_companycourse\privacy\provider
 */
final class provider_test extends \core_privacy\tests\provider_testcase {

    /**
     * Metadata describes the log table fields.
     */
    public function test_get_metadata(): void {
        $this->resetAfterTest();
        $collection = new collection('local_companycourse');
        $collection = provider::get_metadata($collection);
        $items = $collection->get_collection();
        $this->assertNotEmpty($items);
        $this->assertEquals('local_companycourse_log', $items[0]->get_name());
    }

    /**
     * User with log rows has system context; export and delete work.
     */
    public function test_export_and_delete_user_data(): void {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();

        (new log_service())->log_result((int) $user->id, new replication_result(
            10,
            (int) $course->id,
            replication_status::SUCCESS,
            'ok',
            99
        ));

        $contextlist = provider::get_contexts_for_userid((int) $user->id);
        $this->assertCount(1, $contextlist);
        $this->assertEquals(\context_system::instance()->id, $contextlist->current()->id);

        $approved = new approved_contextlist($user, 'local_companycourse', $contextlist->get_contextids());
        provider::export_user_data($approved);
        $writer = writer::with_context(\context_system::instance());
        $this->assertTrue($writer->has_any_data());

        provider::delete_data_for_user($approved);
        $this->assertFalse((new log_service())->has_successful_replication((int) $course->id, 10));
        $this->assertCount(0, provider::get_contexts_for_userid((int) $user->id));
    }
}
