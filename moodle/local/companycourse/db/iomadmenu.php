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
 * IOMAD dashboard menu entries for local_companycourse.
 *
 * @package    local_companycourse
 * @copyright  2026 Wagner Dos Santos Backes <wagnerbackes@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Register menu items on the IOMAD dashboard (Courses + Reports tabs).
 *
 * @return array
 */
function local_companycourse_menu() {
    return [
        'companycoursereplicate' => [
            'category' => 'CourseAdmin',
            'tab' => 3,
            'name' => get_string('manage', 'local_companycourse'),
            'url' => '/local/companycourse/index.php?dashboard=1',
            'cap' => 'local/companycourse:manage',
            'icondefault' => 'createcourse',
            'style' => 'course',
            'icon' => 'fa-copy',
            'iconsmall' => 'fa-building',
        ],
        'companycoursehistory' => [
            'category' => 'Reports',
            'tab' => 8,
            'name' => get_string('history', 'local_companycourse'),
            'url' => '/local/companycourse/history.php?dashboard=1',
            'cap' => 'local/companycourse:manage',
            'icondefault' => 'report',
            'style' => 'report',
            'icon' => 'fa-history',
            'iconsmall' => 'fa-bar-chart-o',
        ],
    ];
}
