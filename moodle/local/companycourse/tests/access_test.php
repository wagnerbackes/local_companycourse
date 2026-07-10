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
 * Capability tests for local/companycourse:manage.
 *
 * @package    local_companycourse
 * @category   test
 * @copyright  2026 Inicie
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversNothing
 */
final class access_test extends \advanced_testcase {

    /**
     * Capability is registered at system context as write.
     */
    public function test_manage_capability_defined(): void {
        $this->resetAfterTest();

        $capability = get_capability_info('local/companycourse:manage');
        $this->assertNotEmpty($capability);
        $this->assertEquals(CONTEXT_SYSTEM, (int) $capability->contextlevel);
        $this->assertEquals('write', $capability->captype);

        $managerroles = get_archetype_roles('manager');
        $this->assertNotEmpty($managerroles);
        $role = reset($managerroles);

        global $DB;
        $permission = $DB->get_field('role_capabilities', 'permission', [
            'roleid' => $role->id,
            'capability' => 'local/companycourse:manage',
            'contextid' => \context_system::instance()->id,
        ]);
        $this->assertEquals(CAP_ALLOW, (int) $permission);
    }

    /**
     * User without the capability is denied; manager archetype is allowed.
     */
    public function test_manage_capability_assignment(): void {
        $this->resetAfterTest();

        $context = \context_system::instance();
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $this->assertFalse(has_capability('local/companycourse:manage', $context));

        $managerroles = get_archetype_roles('manager');
        $role = reset($managerroles);
        role_assign($role->id, $user->id, $context->id);
        $this->assertTrue(has_capability('local/companycourse:manage', $context));
    }
}
