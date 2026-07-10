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
 * Replication history page.
 *
 * @package    local_companycourse
 * @copyright  2026 Inicie
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

use local_companycourse\service\log_service;

require_login();

$dashboard = optional_param('dashboard', 0, PARAM_BOOL);

$context = context_system::instance();
require_capability('local/companycourse:manage', $context);

$url = new moodle_url('/local/companycourse/history.php', $dashboard ? ['dashboard' => 1] : []);
$PAGE->set_context($context);
$PAGE->set_url($url);
$PAGE->set_pagelayout($dashboard ? 'base' : 'admin');
$PAGE->set_title(get_string('history', 'local_companycourse'));
$PAGE->set_heading(get_string('history', 'local_companycourse'));

if ($dashboard) {
    $PAGE->navbar->add(get_string('dashboard', 'block_iomad_company_admin'),
        new moodle_url('/blocks/iomad_company_admin/index.php'));
    $PAGE->navbar->add(get_string('history', 'local_companycourse'), $url);
    \block_iomad_company_admin\event\dashboard_page_viewed::create_from_url($PAGE->url->out())->trigger();
}

global $DB;

$logs = (new log_service())->get_history(50);
$isempty = ($logs === []);
$intro = '';
$tablehtml = '';

if ($isempty) {
    $intro = get_string('history_empty', 'local_companycourse');
} else {
    $intro = get_string('history_count', 'local_companycourse', count($logs));

    $userids = [];
    $courseids = [];
    $companyids = [];
    foreach ($logs as $log) {
        $userids[(int) $log->userid] = (int) $log->userid;
        $courseids[(int) $log->courseid] = (int) $log->courseid;
        if (!empty($log->newcourseid)) {
            $courseids[(int) $log->newcourseid] = (int) $log->newcourseid;
        }
        $companyids[(int) $log->companyid] = (int) $log->companyid;
    }

    $usernames = [];
    if ($userids) {
        $users = $DB->get_records_list('user', 'id', $userids, '', 'id, firstname, lastname');
        foreach ($users as $user) {
            $usernames[(int) $user->id] = fullname($user);
        }
    }

    $coursenames = [];
    if ($courseids) {
        $courses = $DB->get_records_list('course', 'id', $courseids, '', 'id, fullname, shortname');
        foreach ($courses as $course) {
            $coursenames[(int) $course->id] = format_string($course->fullname) . ' (' . format_string($course->shortname) . ')';
        }
    }

    $companynames = [];
    if ($companyids) {
        $companies = $DB->get_records_list('company', 'id', $companyids, '', 'id, name');
        foreach ($companies as $company) {
            $companynames[(int) $company->id] = format_string($company->name);
        }
    }

    $table = new html_table();
    $table->attributes['class'] = 'generaltable';
    $table->head = [
        get_string('history_user', 'local_companycourse'),
        get_string('basecourse', 'local_companycourse'),
        get_string('newcourseid', 'local_companycourse'),
        get_string('company', 'local_companycourse'),
        get_string('status', 'local_companycourse'),
        get_string('message', 'local_companycourse'),
        get_string('history_timecreated', 'local_companycourse'),
    ];
    $table->data = [];

    foreach ($logs as $log) {
        $userid = (int) $log->userid;
        $courseid = (int) $log->courseid;
        $newcourseid = !empty($log->newcourseid) ? (int) $log->newcourseid : null;
        $companyid = (int) $log->companyid;
        $statuskey = 'status_' . $log->status;
        $statuslabel = get_string_manager()->string_exists($statuskey, 'local_companycourse')
            ? get_string($statuskey, 'local_companycourse')
            : (string) $log->status;

        $userlabel = $usernames[$userid] ?? get_string('history_missing_user', 'local_companycourse', $userid);
        $courselabel = $coursenames[$courseid]
            ?? get_string('history_missing_course', 'local_companycourse', $courseid);
        $newcourselabel = $newcourseid === null
            ? '—'
            : ($coursenames[$newcourseid]
                ?? get_string('history_missing_course', 'local_companycourse', $newcourseid));
        $companylabel = $companynames[$companyid]
            ?? get_string('history_missing_company', 'local_companycourse', $companyid);

        $table->data[] = [
            s($userlabel),
            s($courselabel),
            s($newcourselabel),
            s($companylabel),
            s($statuslabel),
            s((string) $log->message),
            userdate((int) $log->timecreated),
        ];
    }

    $tablehtml = html_writer::table($table);
}

$renderer = $PAGE->get_renderer('local_companycourse');

echo $OUTPUT->header();

if ($dashboard) {
    echo $renderer->render_history_page((object) [
        'title' => get_string('history', 'local_companycourse'),
        'intro' => $isempty ? '' : $intro,
        'isempty' => $isempty,
        'emptytext' => get_string('history_empty', 'local_companycourse'),
        'tablehtml' => $tablehtml,
        'manageurl' => (new moodle_url('/local/companycourse/index.php', ['dashboard' => 1]))->out(false),
        'dashboardurl' => (new moodle_url('/blocks/iomad_company_admin/index.php'))->out(false),
    ]);
} else {
    echo $OUTPUT->heading(get_string('history', 'local_companycourse'));
    if ($isempty) {
        echo html_writer::tag('p', get_string('history_empty', 'local_companycourse'));
    } else {
        echo html_writer::tag('p', $intro);
        echo $tablehtml;
    }
}

echo $OUTPUT->footer();
