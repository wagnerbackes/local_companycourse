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
 * IOMAD menu registration and admin settings page existence.
 *
 * @package    local_companycourse
 * @category   test
 * @copyright  2026 Wagner Dos Santos Backes <wagnerbackes@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversNothing
 */
final class iomadmenu_test extends \advanced_testcase {

    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
    }

    /**
     * db/iomadmenu.php registers Courses-tab entry pointing to the plugin page.
     */
    public function test_iomadmenu_entry(): void {
        global $CFG;
        require_once($CFG->dirroot . '/local/companycourse/db/iomadmenu.php');

        $menus = local_companycourse_menu();
        $this->assertArrayHasKey('companycoursereplicate', $menus);
        $item = $menus['companycoursereplicate'];
        $this->assertEquals(3, (int) $item['tab']);
        $this->assertEquals('CourseAdmin', $item['category']);
        $this->assertEquals('local/companycourse:manage', $item['cap']);
        $this->assertStringContainsString('/local/companycourse/index.php', $item['url']);
        $this->assertStringContainsString('dashboard=1', $item['url']);
    }

    /**
     * History entry appears on the IOMAD Reports tab with report styling.
     */
    public function test_iomadmenu_history_reports_entry(): void {
        global $CFG;
        require_once($CFG->dirroot . '/local/companycourse/db/iomadmenu.php');

        $menus = local_companycourse_menu();
        $this->assertArrayHasKey('companycoursehistory', $menus);
        $item = $menus['companycoursehistory'];
        $this->assertEquals(8, (int) $item['tab']);
        $this->assertEquals('Reports', $item['category']);
        $this->assertEquals('report', $item['style']);
        $this->assertEquals('local/companycourse:manage', $item['cap']);
        $this->assertStringContainsString('/local/companycourse/history.php', $item['url']);
        $this->assertStringContainsString('dashboard=1', $item['url']);
    }
}
