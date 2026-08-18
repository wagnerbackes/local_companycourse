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

namespace local_companycourse\output;

defined('MOODLE_INTERNAL') || die();

/**
 * Renderer for local_companycourse templates.
 *
 * @package    local_companycourse
 * @copyright  2026 Inicie
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class renderer extends \plugin_renderer_base {

    /**
     * @param \stdClass $data
     * @return string
     */
    public function render_dashboard_page(\stdClass $data): string {
        return $this->render_from_template('local_companycourse/dashboard_page', $data);
    }

    /**
     * @param \stdClass $data
     * @return string
     */
    public function render_history_page(\stdClass $data): string {
        return $this->render_from_template('local_companycourse/history_page', $data);
    }

    /**
     * @param replication_summary_renderable $page
     * @return string
     */
    public function render_replication_summary_renderable(replication_summary_renderable $page): string {
        return $this->render_from_template(
            'local_companycourse/replication_summary',
            $page->export_for_template($this)
        );
    }
}
