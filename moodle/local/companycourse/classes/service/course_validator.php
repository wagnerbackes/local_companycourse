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
 * Course validation helpers for replication.
 *
 * @package    local_companycourse
 * @copyright  2026 Wagner Dos Santos Backes <wagnerbackes@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_companycourse\service;

defined('MOODLE_INTERNAL') || die();

use local_companycourse\exception\replication_exception;

/**
 * Validates base courses used as replication source.
 *
 * @package    local_companycourse
 * @copyright  2026 Wagner Dos Santos Backes <wagnerbackes@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_validator {

    /**
     * Ensure the base course exists and is usable as backup source.
     *
     * @param int $courseid Course id.
     * @return \stdClass Course record.
     * @throws replication_exception
     */
    public function require_base_course(int $courseid): \stdClass {
        global $DB;

        if ($courseid < 1) {
            throw new replication_exception('error_course_not_found', $courseid);
        }

        $course = $DB->get_record('course', ['id' => $courseid], '*', IGNORE_MISSING);
        if (!$course || (int) $course->id === (int) SITEID) {
            throw new replication_exception('error_course_not_found', $courseid);
        }

        return $course;
    }

    /**
     * Courses available as base (excludes site course), for admin select.
     *
     * Labels include the IOMAD company name when the course is linked in company_course.
     *
     * @return array id => fullname (shortname) (company)
     */
    public function list_base_courses(): array {
        global $DB;

        $sql = "SELECT c.id, c.fullname, c.shortname, co.name AS companyname
                  FROM {course} c
             LEFT JOIN {company_course} cc ON cc.courseid = c.id
             LEFT JOIN {company} co ON co.id = cc.companyid
                 WHERE c.id <> :siteid
              ORDER BY c.fullname ASC, co.name ASC";
        $rows = $DB->get_recordset_sql($sql, ['siteid' => SITEID]);

        $companiesbycourse = [];
        $courses = [];
        foreach ($rows as $row) {
            $courseid = (int) $row->id;
            if (!isset($courses[$courseid])) {
                $courses[$courseid] = $row;
                $companiesbycourse[$courseid] = [];
            }
            if (!empty($row->companyname)) {
                $companiesbycourse[$courseid][$row->companyname] = format_string($row->companyname);
            }
        }
        $rows->close();

        $options = [];
        foreach ($courses as $courseid => $course) {
            $label = format_string($course->fullname) . ' (' . format_string($course->shortname) . ')';
            if ($companiesbycourse[$courseid] !== []) {
                $label .= ' (' . implode(', ', array_values($companiesbycourse[$courseid])) . ')';
            } else {
                $label .= ' (' . get_string('basecourse_nocompany', 'local_companycourse') . ')';
            }
            $options[$courseid] = $label;
        }

        return $options;
    }
}
