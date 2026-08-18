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

namespace local_companycourse\service;

defined('MOODLE_INTERNAL') || die();

use backup;
use backup_controller;
use local_companycourse\exception\replication_exception;
use restore_controller;
use restore_dbops;

global $CFG;
require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');

/**
 * Copies a course via Moodle Backup/Restore.
 *
 * @package    local_companycourse
 * @copyright  2026 Inicie
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_replication_service {

    private bool $includeusers = false;

    /**
     * @param bool $includeusers Include enrolments and related user data when true.
     */
    public function __construct(bool $includeusers = false) {
        $this->includeusers = $includeusers;
    }

    /**
     * @param bool $includeusers
     */
    public function set_includeusers(bool $includeusers): void {
        $this->includeusers = $includeusers;
    }

    /**
     * @param int $courseid Base course id.
     * @return string Absolute path to the backup file (.mbz).
     * @throws replication_exception
     */
    public function create_backup(int $courseid): string {
        global $USER, $CFG;

        (new course_validator())->require_base_course($courseid);

        if (empty($USER->id)) {
            throw new replication_exception('error_backup_failed', get_string('error_backup_no_user', 'local_companycourse'));
        }

        raise_memory_limit(MEMORY_EXTRA);
        \core_php_time_limit::raise();

        $CFG->backup_file_logger_level = backup::LOG_NONE;

        $bc = null;
        try {
            $bc = new backup_controller(
                backup::TYPE_1COURSE,
                $courseid,
                backup::FORMAT_MOODLE,
                backup::INTERACTIVE_NO,
                backup::MODE_GENERAL,
                (int) $USER->id
            );
            $bc->set_progress(new \core\progress\display_if_slow(
                get_string('backupprogress', 'local_companycourse')
            ));
            $this->configure_backup_settings($bc);
            $bc->execute_plan();
            $results = $bc->get_results();
            if (empty($results['backup_destination'])) {
                throw new replication_exception('error_backup_failed', get_string('error_backup_no_file', 'local_companycourse'));
            }

            /** @var \stored_file $file */
            $file = $results['backup_destination'];
            $dir = make_temp_directory('local_companycourse/backups');
            $filepath = $dir . '/course' . $courseid . '_' . time() . '_' . uniqid('', true) . '.mbz';
            if (!$file->copy_content_to($filepath) || !is_readable($filepath)) {
                throw new replication_exception('error_backup_failed', get_string('error_backup_copy_failed', 'local_companycourse'));
            }

            return $filepath;
        } catch (replication_exception $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new replication_exception('error_backup_failed', $e->getMessage(), $e->getMessage());
        } finally {
            if ($bc instanceof backup_controller) {
                $bc->destroy();
            }
        }
    }

    /**
     * @param string $backupfile Path returned by {@see create_backup()}.
     * @param int $categoryid Destination course category id.
     * @return int New course id.
     * @throws replication_exception
     */
    public function restore_to_category(string $backupfile, int $categoryid): int {
        global $USER, $DB, $CFG;

        if ($backupfile === '' || !is_readable($backupfile)) {
            throw new replication_exception('error_backup_file_missing');
        }

        if ($categoryid < 1 || !$DB->record_exists('course_categories', ['id' => $categoryid])) {
            throw new replication_exception('error_category_not_found', $categoryid);
        }

        if (empty($USER->id)) {
            throw new replication_exception('error_restore_failed', get_string('error_backup_no_user', 'local_companycourse'));
        }

        raise_memory_limit(MEMORY_EXTRA);
        \core_php_time_limit::raise();

        $CFG->backup_file_logger_level = backup::LOG_NONE;

        $unpackdir = 'local_companycourse_' . uniqid('restore', true);
        $unpackpath = make_backup_temp_directory($unpackdir);
        $newcourseid = 0;
        $rc = null;

        try {
            $packer = get_file_packer('application/vnd.moodle.backup');
            $result = $packer->extract_to_pathname($backupfile, $unpackpath);
            if ($result === false) {
                throw new replication_exception('error_restore_failed', get_string('error_restore_extract_failed', 'local_companycourse'));
            }

            $fullname = get_string('restoredcoursefullname', 'local_companycourse');
            $shortname = $this->unique_shortname('cc');
            $newcourseid = restore_dbops::create_new_course($fullname, $shortname, $categoryid);

            $rc = new restore_controller(
                $unpackdir,
                $newcourseid,
                backup::INTERACTIVE_NO,
                backup::MODE_GENERAL,
                (int) $USER->id,
                backup::TARGET_NEW_COURSE
            );
            $rc->set_progress(new \core\progress\display_if_slow(
                get_string('restoreprogress', 'local_companycourse')
            ));
            $this->configure_restore_settings($rc);

            if (!$rc->execute_precheck()) {
                $precheck = $rc->get_precheck_results();
                $detail = is_array($precheck) ? json_encode($precheck) : (string) $precheck;
                throw new replication_exception('error_restore_failed', $detail, $detail);
            }

            $rc->execute_plan();
            $restoredid = (int) $rc->get_courseid();
            if ($restoredid < 1 || !$DB->record_exists('course', ['id' => $restoredid])) {
                throw new replication_exception('error_restore_failed', get_string('error_restore_no_course', 'local_companycourse'));
            }

            return $restoredid;
        } catch (replication_exception $e) {
            if ($newcourseid > 0 && $DB->record_exists('course', ['id' => $newcourseid])) {
                delete_course($newcourseid, false);
            }
            throw $e;
        } catch (\Throwable $e) {
            if ($newcourseid > 0 && $DB->record_exists('course', ['id' => $newcourseid])) {
                delete_course($newcourseid, false);
            }
            throw new replication_exception('error_restore_failed', $e->getMessage(), $e->getMessage());
        } finally {
            if ($rc instanceof restore_controller) {
                $rc->destroy();
            }
            if (is_dir($unpackpath)) {
                fulldelete($unpackpath);
            }
        }
    }

    /**
     * @param backup_controller $bc
     */
    private function configure_backup_settings(backup_controller $bc): void {
        $plan = $bc->get_plan();
        $uservalue = $this->includeusers ? 1 : 0;

        if ($plan->setting_exists('users')) {
            $setting = $plan->get_setting('users');
            if ($setting->get_status() == \base_setting::NOT_LOCKED) {
                $setting->set_value($uservalue);
            }
        }

        $related = [
            'anonymize', 'role_assignments', 'logs', 'grade_histories', 'comments',
            'userscompletion', 'groups', 'competencies', 'customfield', 'contentbankcontent', 'xapistate',
        ];
        foreach ($related as $name) {
            if ($plan->setting_exists($name)) {
                $setting = $plan->get_setting($name);
                if ($setting->get_status() == \base_setting::NOT_LOCKED) {
                    $setting->set_value($this->includeusers && in_array($name, [
                        'role_assignments', 'groups', 'userscompletion',
                    ], true) ? 1 : 0);
                }
            }
        }
    }

    /**
     * @param restore_controller $rc
     */
    private function configure_restore_settings(restore_controller $rc): void {
        $plan = $rc->get_plan();
        if ($plan->setting_exists('users')) {
            $setting = $plan->get_setting('users');
            if ($setting->get_status() == \base_setting::NOT_LOCKED) {
                $setting->set_value($this->includeusers ? 1 : 0);
            }
        }
        if ($plan->setting_exists('overwrite_conf')) {
            $setting = $plan->get_setting('overwrite_conf');
            if ($setting->get_status() == \base_setting::NOT_LOCKED) {
                $setting->set_value(0);
            }
        }
    }

    /**
     * @param string $prefix
     * @return string
     */
    private function unique_shortname(string $prefix): string {
        global $DB;

        do {
            $shortname = $prefix . '_' . substr(uniqid('', true), -8);
        } while ($DB->record_exists('course', ['shortname' => $shortname]));

        return $shortname;
    }
}
