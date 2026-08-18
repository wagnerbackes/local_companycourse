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

$string['pluginname'] = 'Company course replication';
$string['privacy:metadata:local_companycourse_log'] = 'Stores audit logs of course replications to IOMAD companies.';
$string['privacy:metadata:local_companycourse_log:userid'] = 'The user who ran the replication.';
$string['privacy:metadata:local_companycourse_log:courseid'] = 'The base course id.';
$string['privacy:metadata:local_companycourse_log:newcourseid'] = 'The created course id, when successful.';
$string['privacy:metadata:local_companycourse_log:companyid'] = 'The destination IOMAD company id.';
$string['privacy:metadata:local_companycourse_log:status'] = 'The outcome status of the operation.';
$string['privacy:metadata:local_companycourse_log:message'] = 'The human-readable result or error message.';
$string['privacy:metadata:local_companycourse_log:timecreated'] = 'The time the operation was recorded.';
$string['privacy:export:logs'] = 'Course replication logs';
$string['companycourse:manage'] = 'Replicate courses to IOMAD companies';
$string['manage'] = 'Replicate courses to companies';
$string['manage_desc'] = 'Select a base course and one or more IOMAD companies to replicate the course.';
$string['manage_counts'] = 'Available for selection: {$a->courses} course(s), {$a->companies} company(ies).';
$string['basecourse'] = 'Base course';
$string['basecourse_nocompany'] = 'no company';
$string['destinationcompanies'] = 'Destination companies';
$string['selectcompanies'] = 'Select one or more companies';
$string['includeusers'] = 'Include user data';
$string['includeusers_label'] = 'Also copy enrolments and related user information from the base course';
$string['includeusers_help'] = 'User data is off by default. Enabling it copies enrolments, role assignments, groups and completion-related information into each company copy. That can duplicate learner records across tenants and is usually unsuitable for a course template. Prefer leaving this disabled unless you intentionally need user data.';
$string['includeusers_warning'] = 'User data is off by default. Enable only if you need enrolments copied into each company copy.';
$string['executereplication'] = 'Replicate course';
$string['backupprogress'] = 'Backing up base course';
$string['restoreprogress'] = 'Restoring course copy';
$string['summaryheading'] = 'Replication summary';
$string['summarycourse'] = 'Base course: {$a}';
$string['summarycounts'] = 'Total: {$a->total} — success: {$a->success}, errors: {$a->error}, skipped: {$a->skipped}';
$string['summaryhaserrors'] = 'One or more companies failed. See the table below and the history page.';
$string['summaryhassuccess'] = 'At least one company received a new course copy.';
$string['company'] = 'Company';
$string['status'] = 'Status';
$string['message'] = 'Message';
$string['newcourseid'] = 'New course ID';
$string['status_success'] = 'Success';
$string['status_error'] = 'Error';
$string['status_skipped'] = 'Skipped';
$string['backtodashboard'] = 'Back to IOMAD dashboard';
$string['error_course_required'] = 'Select a base course.';
$string['history'] = 'Replication history';
$string['history_empty'] = 'No replication operations recorded yet.';
$string['history_count'] = 'Showing the {$a} most recent log entries.';
$string['history_user'] = 'User';
$string['history_timecreated'] = 'Date/time';
$string['history_missing_user'] = 'User #{$a} (removed)';
$string['history_missing_course'] = 'Course #{$a} (removed)';
$string['history_missing_company'] = 'Company #{$a} (removed)';
$string['error_course_not_found'] = 'Base course not found (id: {$a}).';
$string['error_company_not_found'] = 'Company not found (id: {$a}).';
$string['error_company_suspended'] = 'Company "{$a}" is suspended and cannot receive courses.';
$string['error_no_companies'] = 'Select at least one destination company.';
$string['error_association_failed'] = 'Failed to associate the course with the IOMAD company: {$a}';
$string['error_move_course_failed'] = 'Could not move restored course {$a} into the company category.';
$string['error_add_course_failed'] = 'Could not associate the restored course {$a} with the company.';
$string['error_company_course_missing'] = 'The restored course {$a} was not linked to the company.';
$string['error_backup_failed'] = 'Course backup failed: {$a}';
$string['error_restore_failed'] = 'Course restore failed: {$a}';
$string['error_backup_file_missing'] = 'Backup package file is missing or unreadable.';
$string['error_category_not_found'] = 'Destination course category not found (id: {$a}).';
$string['error_backup_no_user'] = 'No authenticated user available to run backup/restore.';
$string['error_backup_no_file'] = 'Backup finished without producing a destination file.';
$string['error_backup_copy_failed'] = 'Could not copy the backup package to a temporary path.';
$string['error_restore_extract_failed'] = 'Could not extract the backup package.';
$string['error_restore_no_course'] = 'Restore finished but the new course was not found.';
$string['emptyvalue'] = '—';
$string['restoredcoursefullname'] = 'Restored course';
$string['error_unexpected'] = 'Unexpected error: {$a}';
$string['error_log_failed'] = 'The operation result was kept, but writing the audit log failed: {$a}';
$string['error_technical_detail'] = 'Technical detail: {$a}';
$string['result_success'] = 'Course replicated successfully (new course id: {$a}).';
$string['result_skipped_duplicate'] = 'Skipped: a successful replication of this base course to this company already exists.';
