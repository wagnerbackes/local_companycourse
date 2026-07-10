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

/**
 * Shared helpers for local_companycourse PHPUnit tests.
 *
 * @package    local_companycourse
 * @category   test
 * @copyright  2026 Inicie
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Create a minimal IOMAD company row suitable for unit tests.
 *
 * @param array $overrides Optional field overrides.
 * @return \stdClass Company DB record.
 */
function local_companycourse_create_test_company(array $overrides = []): \stdClass {
    global $DB, $CFG;

    require_once($CFG->dirroot . '/local/iomad/lib/company.php');

    $category = \core_course_category::create((object) [
        'name' => $overrides['categoryname'] ?? 'Company category ' . uniqid(),
        'parent' => 0,
    ]);

    $profile = (object) [
        'name' => $overrides['shortname'] ?? ('c' . uniqid()),
        'sortorder' => $DB->count_records('user_info_category') + 1,
    ];
    $profileid = $DB->insert_record('user_info_category', $profile);

    $suffix = substr(uniqid(), -6);
    $record = (object) [
        'name' => $overrides['name'] ?? ('Test Company ' . $suffix),
        'shortname' => $overrides['shortname'] ?? ('tc' . $suffix),
        'city' => $overrides['city'] ?? 'Testville',
        'country' => $overrides['country'] ?? 'BR',
        'theme' => $overrides['theme'] ?? 'iomadboost',
        'category' => $overrides['category'] ?? $category->id,
        'profileid' => $overrides['profileid'] ?? $profileid,
        'suspended' => $overrides['suspended'] ?? 0,
        'supervisorprofileid' => 0,
        'managernotify' => 0,
        'parentid' => 0,
        'ecommerce' => 0,
        'managerdigestday' => 0,
        'previousroletemplateid' => 0,
        'previousemailtemplateid' => 0,
        'maxusers' => 0,
        'companyterminated' => 0,
        'departmentprofileid' => 0,
    ];

    $record->id = $DB->insert_record('company', $record);
    \core\context\company::instance($record->id);
    \company::initialise_departments($record->id);

    return $DB->get_record('company', ['id' => $record->id], '*', MUST_EXIST);
}
