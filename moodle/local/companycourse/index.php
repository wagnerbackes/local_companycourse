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
 * Course replication administration page (site admin and IOMAD dashboard entry).
 *
 * @package    local_companycourse
 * @copyright  2026 Wagner Dos Santos Backes <wagnerbackes@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

use local_companycourse\exception\replication_exception;
use local_companycourse\form\replicate_form;
use local_companycourse\output\replication_summary_renderable;
use local_companycourse\service\company_repository;
use local_companycourse\service\course_validator;
use local_companycourse\service\replication_manager;

require_login();

$dashboard = optional_param('dashboard', 0, PARAM_BOOL);

$context = context_system::instance();
require_capability('local/companycourse:manage', $context);

$url = new moodle_url('/local/companycourse/index.php', $dashboard ? ['dashboard' => 1] : []);
$PAGE->set_context($context);
$PAGE->set_url($url);
$PAGE->set_pagelayout($dashboard ? 'base' : 'admin');
$PAGE->set_title(get_string('manage', 'local_companycourse'));
$PAGE->set_heading(get_string('manage', 'local_companycourse'));

if ($dashboard) {
    $PAGE->navbar->add(get_string('dashboard', 'block_iomad_company_admin'),
        new moodle_url('/blocks/iomad_company_admin/index.php'));
    $PAGE->navbar->add(get_string('manage', 'local_companycourse'), $url);
    \block_iomad_company_admin\event\dashboard_page_viewed::create_from_url($PAGE->url->out())->trigger();
}

$courses = (new course_validator())->list_base_courses();
$companies = (new company_repository())->list_active_companies();

$mform = new replicate_form($url, [
    'courses' => $courses,
    'companies' => $companies,
]);
$mform->set_data(['dashboard' => $dashboard ? 1 : 0]);

$summaryhtml = '';
$runreplication = false;
$pendingdata = null;

if ($mform->is_cancelled()) {
    if ($dashboard) {
        redirect(new moodle_url('/blocks/iomad_company_admin/index.php'));
    }
    redirect(new moodle_url('/admin/search.php'));
} else if ($data = $mform->get_data()) {
    $runreplication = true;
    $pendingdata = $data;
}

$renderer = $PAGE->get_renderer('local_companycourse');

echo $OUTPUT->header();

if ($runreplication && $pendingdata !== null) {
    $includeusers = !empty($pendingdata->includeusers);
    $companyids = array_map('intval', (array) $pendingdata->companyids);
    raise_memory_limit(MEMORY_EXTRA);
    \core_php_time_limit::raise();

    try {
        $manager = new replication_manager();
        $summary = $manager->replicate(
            (int) $pendingdata->courseid,
            $companyids,
            (int) $USER->id,
            $includeusers
        );
        $coursename = $courses[(int) $pendingdata->courseid]
            ?? (string) $pendingdata->courseid;
        $renderable = new replication_summary_renderable($summary, $coursename, $companies);
        $summaryhtml = $renderer->render($renderable);
    } catch (replication_exception $e) {
        \core\notification::error($e->getMessage());
    }
}

if ($dashboard) {
    ob_start();
    $mform->display();
    $formhtml = ob_get_clean();

    echo $renderer->render_dashboard_page((object) [
        'title' => get_string('manage', 'local_companycourse'),
        'intro' => get_string('manage_desc', 'local_companycourse'),
        'counts' => get_string('manage_counts', 'local_companycourse', (object) [
            'courses' => count($courses),
            'companies' => count($companies),
        ]),
        'formhtml' => $formhtml,
        'summaryhtml' => $summaryhtml,
        'hashistory' => true,
        'historyurl' => (new moodle_url('/local/companycourse/history.php'))->out(false),
        'dashboardurl' => (new moodle_url('/blocks/iomad_company_admin/index.php'))->out(false),
    ]);
} else {
    echo $OUTPUT->heading(get_string('manage', 'local_companycourse'));
    echo html_writer::tag('p', get_string('manage_desc', 'local_companycourse'));
    echo html_writer::tag('p', get_string('manage_counts', 'local_companycourse', (object) [
        'courses' => count($courses),
        'companies' => count($companies),
    ]));
    echo $OUTPUT->notification(get_string('includeusers_warning', 'local_companycourse'), 'info');
    $mform->display();
    echo $summaryhtml;
}

echo $OUTPUT->footer();
