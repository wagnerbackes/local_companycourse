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

namespace local_companycourse\form;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/formslib.php');

/**
 * Form to select base course, companies and optional user data.
 *
 * @package    local_companycourse
 * @copyright  2026 Inicie
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class replicate_form extends \moodleform {

    /**
     * @return void
     */
    public function definition(): void {
        $mform = $this->_form;
        $courses = $this->_customdata['courses'] ?? [];
        $companies = $this->_customdata['companies'] ?? [];

        $mform->addElement('header', 'replicationheader', get_string('manage', 'local_companycourse'));

        $courseoptions = ['' => get_string('choosedots')] + $courses;
        $mform->addElement('select', 'courseid', get_string('basecourse', 'local_companycourse'), $courseoptions);
        $mform->setType('courseid', PARAM_INT);
        $mform->addRule('courseid', get_string('required'), 'required', null, 'client');

        $mform->addElement(
            'autocomplete',
            'companyids',
            get_string('destinationcompanies', 'local_companycourse'),
            $companies,
            [
                'multiple' => true,
                'noselectionstring' => get_string('selectcompanies', 'local_companycourse'),
            ]
        );
        $mform->setType('companyids', PARAM_INT);
        $mform->addRule('companyids', get_string('required'), 'required', null, 'client');

        $mform->addElement(
            'advcheckbox',
            'includeusers',
            get_string('includeusers', 'local_companycourse'),
            get_string('includeusers_label', 'local_companycourse')
        );
        $mform->setDefault('includeusers', 0);
        $mform->setType('includeusers', PARAM_BOOL);
        $mform->addHelpButton('includeusers', 'includeusers', 'local_companycourse');

        $mform->addElement('hidden', 'dashboard', 0);
        $mform->setType('dashboard', PARAM_BOOL);

        $this->add_action_buttons(true, get_string('executereplication', 'local_companycourse'));
    }

    /**
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);

        if (empty($data['courseid'])) {
            $errors['courseid'] = get_string('error_course_required', 'local_companycourse');
        }

        if (empty($data['companyids']) || !is_array($data['companyids'])) {
            $errors['companyids'] = get_string('error_no_companies', 'local_companycourse');
        }

        return $errors;
    }
}
