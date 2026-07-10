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
 * Domain exception for course replication failures.
 *
 * @package    local_companycourse
 * @copyright  2026 Wagner Dos Santos Backes <wagnerbackes@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_companycourse\exception;

defined('MOODLE_INTERNAL') || die();

/**
 * Controlled failure during validation, replication or IOMAD association.
 *
 * @package    local_companycourse
 * @copyright  2026 Wagner Dos Santos Backes <wagnerbackes@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class replication_exception extends \moodle_exception {

    /**
     * @param string $errorcode Lang string identifier in local_companycourse.
     * @param string|\lang_string|null $a String placeholder data.
     * @param string|null $debuginfo Optional technical detail for logs.
     */
    public function __construct(string $errorcode, $a = null, ?string $debuginfo = null) {
        parent::__construct($errorcode, 'local_companycourse', '', $a, $debuginfo);
    }
}
