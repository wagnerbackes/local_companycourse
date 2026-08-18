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
 * @package    local_companycourse
 * @copyright  2026 Inicie
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $ADMIN->add('localplugins', new admin_category(
        'local_companycourse',
        get_string('pluginname', 'local_companycourse')
    ));

    $ADMIN->add('local_companycourse', new admin_externalpage(
        'local_companycourse_manage',
        get_string('manage', 'local_companycourse'),
        new moodle_url('/local/companycourse/index.php'),
        'local/companycourse:manage'
    ));

    $ADMIN->add('local_companycourse', new admin_externalpage(
        'local_companycourse_history',
        get_string('history', 'local_companycourse'),
        new moodle_url('/local/companycourse/history.php'),
        'local/companycourse:manage'
    ));

    $ADMIN->add('courses', new admin_externalpage(
        'local_companycourse_manage_courses',
        get_string('manage', 'local_companycourse'),
        new moodle_url('/local/companycourse/index.php'),
        'local/companycourse:manage'
    ));

    $ADMIN->add('reports', new admin_externalpage(
        'local_companycourse_history_reports',
        get_string('history', 'local_companycourse'),
        new moodle_url('/local/companycourse/history.php'),
        'local/companycourse:manage'
    ));
}
