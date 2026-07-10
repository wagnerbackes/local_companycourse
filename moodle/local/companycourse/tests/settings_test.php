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

defined('MOODLE_INTERNAL') || die();

/**
 * Admin tree registration for history under Reports (task 11 UI entry).
 *
 * @package    local_companycourse
 * @category   test
 * @copyright  2026 Wagner Dos Santos Backes <wagnerbackes@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversNothing
 */
final class settings_test extends \advanced_testcase {

    /**
     * History page is registered under reports and local plugin category.
     */
    public function test_history_registered_in_reports(): void {
        global $CFG;
        require_once($CFG->libdir . '/adminlib.php');

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $adminroot = admin_get_root(true, true);
        $this->assertNotNull($adminroot->locate('local_companycourse_history'));
        $this->assertNotNull($adminroot->locate('local_companycourse_history_reports'));
        $this->assertNotNull($adminroot->locate('local_companycourse_manage_courses'));
    }
}
