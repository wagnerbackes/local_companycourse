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

namespace local_companycourse;

/**
 * XMLDB / install schema tests.
 *
 * @package    local_companycourse
 * @category   test
 * @copyright  2026 Inicie
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversNothing
 */
final class install_test extends \advanced_testcase {

    /**
     * Log table exists with the fields required by the history page.
     */
    public function test_log_table_schema(): void {
        global $DB;

        $this->resetAfterTest();

        $dbman = $DB->get_manager();
        $table = new \xmldb_table('local_companycourse_log');
        $this->assertTrue($dbman->table_exists($table));

        $columns = $DB->get_columns('local_companycourse_log');
        foreach (['id', 'userid', 'courseid', 'newcourseid', 'companyid', 'status', 'message', 'timecreated'] as $field) {
            $this->assertArrayHasKey($field, $columns, "Missing column {$field}");
        }

        $this->assertTrue($columns['newcourseid']->meta_type === 'I' || $columns['newcourseid']->meta_type === 'N'
            || isset($columns['newcourseid']));
        $this->assertTrue($columns['newcourseid']->not_null === false);

        $index = new \xmldb_index('course_company_status', XMLDB_INDEX_NOTUNIQUE, ['courseid', 'companyid', 'status']);
        $this->assertTrue($dbman->index_exists($table, $index));
    }
}
