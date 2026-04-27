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
 * Activity settings form for mod_eledialeitnerflow.
 *
 * @package    mod_eledialeitnerflow
 * @copyright  2026 eLeDia GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/moodleform_mod.php');

/**
 * Module settings form for eLeDia Leitner Flow.
 *
 * @package    mod_eledialeitnerflow
 * @copyright  2026 eLeDia GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_eledialeitnerflow_mod_form extends moodleform_mod {
    /**
     * Form definition.
     *
     * @return void
     */
    public function definition(): void {
        global $CFG, $DB, $COURSE;

        $mform = $this->_form;

        // General section.
        $mform->addElement('header', 'general', get_string('general', 'form'));

        $mform->addElement('text', 'name', get_string('activityname', 'mod_eledialeitnerflow'), ['size' => 64]);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required');

        $this->standard_intro_elements();

        // Question bank settings.
        $mform->addElement('header', 'questionbanksettings', get_string('questioncategory', 'mod_eledialeitnerflow'));

        // Load question categories from the current course context only.
        $categories = [];
        $debuginfo = '';
        try {
            $coursecontext = \core\context\course::instance($COURSE->id);
            $sql = "SELECT qc.id, qc.name, COUNT(qbe.id) AS qcount
                      FROM {question_categories} qc
                 LEFT JOIN {question_bank_entries} qbe ON qbe.questioncategoryid = qc.id
                     WHERE qc.contextid = :contextid
                       AND qc.parent <> 0
                  GROUP BY qc.id, qc.name
                  ORDER BY qc.name ASC";
            $coursecats = $DB->get_records_sql($sql, ['contextid' => $coursecontext->id]);
            $debuginfo .= 'Found ' . count($coursecats) . ' course categories. ';
            foreach ($coursecats as $cat) {
                $categories[$cat->id] = $cat->name . " ({$cat->qcount} questions)";
            }
            $debuginfo .= count($categories) . ' shown in dropdown.';
        } catch (\Exception $e) {
            $debuginfo = 'ERROR: ' . $e->getMessage();
        }

        if (empty($categories)) {
            $mform->addElement(
                'static',
                'nocategory_warning',
                '',
                \html_writer::tag(
                    'div',
                    get_string('nocategory', 'mod_eledialeitnerflow'),
                    ['class' => 'alert alert-warning']
                )
            );
            $categories = [0 => '---'];
        }

        $mform->addElement(
            'autocomplete',
            'questioncategoryids_array',
            get_string('categories'),
            $categories,
            ['multiple' => true]
        );
        $mform->addHelpButton('questioncategoryids_array', 'questioncategory', 'mod_eledialeitnerflow');
        $mform->addRule('questioncategoryids_array', null, 'required');

        // Question rotation.
        $rotationoptions = [
            1 => get_string('questionrotation_dynamic', 'mod_eledialeitnerflow'),
            0 => get_string('questionrotation_fixed', 'mod_eledialeitnerflow'),
        ];
        $mform->addElement(
            'select',
            'questionrotation',
            get_string('questionrotation', 'mod_eledialeitnerflow'),
            $rotationoptions
        );
        $mform->addHelpButton('questionrotation', 'questionrotation', 'mod_eledialeitnerflow');
        $mform->setDefault('questionrotation', 1);

        // Session settings.
        $mform->addElement(
            'header',
            'sessionsettingsheader',
            get_string('sessionsettings', 'mod_eledialeitnerflow')
        );

        $mform->addElement(
            'text',
            'sessionsize',
            get_string('sessionsize', 'mod_eledialeitnerflow'),
            ['size' => 4]
        );
        $mform->setType('sessionsize', PARAM_INT);
        $mform->setDefault('sessionsize', 20);
        $mform->addHelpButton('sessionsize', 'sessionsize', 'mod_eledialeitnerflow');
        $mform->addRule('sessionsize', null, 'required');
        $mform->addRule('sessionsize', null, 'numeric');

        // Leitner box settings.
        $mform->addElement(
            'header',
            'leitnersettingsheader',
            get_string('leitnersettings', 'mod_eledialeitnerflow')
        );
        $mform->setExpanded('leitnersettingsheader', true);

        $boxoptions = [1 => '1', 2 => '2', 3 => '3', 4 => '4', 5 => '5'];
        $mform->addElement(
            'select',
            'boxcount',
            get_string('boxcount', 'mod_eledialeitnerflow'),
            $boxoptions
        );
        $mform->addHelpButton('boxcount', 'boxcount', 'mod_eledialeitnerflow');
        $mform->setDefault('boxcount', 3);

        $mform->addElement(
            'text',
            'correcttolearn',
            get_string('correcttolearn', 'mod_eledialeitnerflow'),
            ['size' => 4]
        );
        $mform->setType('correcttolearn', PARAM_INT);
        $mform->setDefault('correcttolearn', 3);
        $mform->addHelpButton('correcttolearn', 'correcttolearn', 'mod_eledialeitnerflow');
        $mform->addRule('correcttolearn', null, 'required');
        $mform->addRule('correcttolearn', null, 'numeric');

        $wrongoptions = [
            0 => get_string('wrongbehavior_reset', 'mod_eledialeitnerflow'),
            1 => get_string('wrongbehavior_back1', 'mod_eledialeitnerflow'),
            2 => get_string('wrongbehavior_nochange', 'mod_eledialeitnerflow'),
        ];
        $mform->addElement(
            'select',
            'wrongbehavior',
            get_string('wrongbehavior', 'mod_eledialeitnerflow'),
            $wrongoptions
        );
        $mform->addHelpButton('wrongbehavior', 'wrongbehavior', 'mod_eledialeitnerflow');
        $mform->setDefault('wrongbehavior', 0);

        $priorityoptions = [
            0 => get_string('prioritystrategy_prio', 'mod_eledialeitnerflow'),
            1 => get_string('prioritystrategy_mixed', 'mod_eledialeitnerflow'),
        ];
        $mform->addElement(
            'select',
            'prioritystrategy',
            get_string('cardselection', 'mod_eledialeitnerflow'),
            $priorityoptions
        );
        $mform->addHelpButton('prioritystrategy', 'prioritystrategy', 'mod_eledialeitnerflow');
        $mform->setDefault('prioritystrategy', 0);

        // Display & feedback settings (within Leitner System section).
        $mform->addElement(
            'selectyesno',
            'showanimation',
            get_string('showanimation', 'mod_eledialeitnerflow')
        );
        $mform->addHelpButton('showanimation', 'showanimation', 'mod_eledialeitnerflow');
        $mform->setDefault('showanimation', 1);

        $feedbackoptions = [
            0 => get_string('feedbackstyle_off', 'mod_eledialeitnerflow'),
            1 => get_string('feedbackstyle_minimal', 'mod_eledialeitnerflow'),
            2 => get_string('feedbackstyle_animated', 'mod_eledialeitnerflow'),
            3 => get_string('feedbackstyle_detailed', 'mod_eledialeitnerflow'),
            4 => get_string('feedbackstyle_gamified', 'mod_eledialeitnerflow'),
        ];
        $mform->addElement(
            'select',
            'feedbackstyle',
            get_string('feedbackstyle', 'mod_eledialeitnerflow'),
            $feedbackoptions
        );
        $mform->addHelpButton('feedbackstyle', 'feedbackstyle', 'mod_eledialeitnerflow');
        $mform->setDefault('feedbackstyle', 2);

        $delayoptions = [
            500  => '0,5 s',
            1000 => '1 s',
            1500 => '1,5 s',
            2000 => '2 s',
            3000 => '3 s',
            5000 => '5 s',
        ];
        $mform->addElement(
            'select',
            'animationdelay',
            get_string('animationdelay', 'mod_eledialeitnerflow'),
            $delayoptions
        );
        $mform->addHelpButton('animationdelay', 'animationdelay', 'mod_eledialeitnerflow');
        $mform->setDefault('animationdelay', 1000);

        $mform->addElement(
            'selectyesno',
            'showtour',
            get_string('showtour', 'mod_eledialeitnerflow')
        );
        $mform->addHelpButton('showtour', 'showtour', 'mod_eledialeitnerflow');
        $mform->setDefault('showtour', 1);

        // Grading settings.
        $mform->addElement(
            'header',
            'gradingsettingsheader',
            get_string('gradingsettings', 'mod_eledialeitnerflow')
        );

        $gradeoptions = [
            0 => get_string('grademethod_none', 'mod_eledialeitnerflow'),
            1 => get_string('grademethod_percent', 'mod_eledialeitnerflow'),
        ];
        $mform->addElement(
            'select',
            'grademethod',
            get_string('grademethod', 'mod_eledialeitnerflow'),
            $gradeoptions
        );
        $mform->setDefault('grademethod', 0);

        $mform->addElement('float', 'gradepass', get_string('gradepass', 'grades'));
        $mform->addHelpButton('gradepass', 'gradepass', 'grades');
        $mform->setDefault('gradepass', '');
        $mform->hideIf('gradepass', 'grademethod', 'eq', '0');

        $this->standard_coursemodule_elements();
        $this->add_action_buttons();
    }

    /**
     * Pre-process form data for display — convert comma-separated IDs to array.
     *
     * @param array $defaultvalues The default form values to pre-process (passed by reference).
     */
    public function data_preprocessing(&$defaultvalues): void {
        if (!empty($defaultvalues['questioncategoryids'])) {
            $defaultvalues['questioncategoryids_array'] = explode(',', $defaultvalues['questioncategoryids']);
        } else if (!empty($defaultvalues['questioncategoryid'])) {
            // Legacy fallback.
            $defaultvalues['questioncategoryids_array'] = [(string) $defaultvalues['questioncategoryid']];
        }
    }

    /**
     * Validate form data.
     *
     * @param array $data The form data to validate.
     * @param array $files The files array.
     * @return array Validation errors.
     */
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);

        if (isset($data['sessionsize']) && (int)$data['sessionsize'] < 1) {
            $errors['sessionsize'] = get_string('error', 'moodle');
        }
        if (isset($data['correcttolearn']) && (int)$data['correcttolearn'] < 1) {
            $errors['correcttolearn'] = get_string('error', 'moodle');
        }
        if (!empty($data['grademethod']) && isset($data['gradepass']) && $data['gradepass'] !== '') {
            $gradepass = unformat_float($data['gradepass'], true);
            if ($gradepass !== false && $gradepass < 0) {
                $errors['gradepass'] = get_string('gradepassmustbenonnegative', 'mod_eledialeitnerflow');
            } else if ($gradepass !== false && $gradepass > 100) {
                $errors['gradepass'] = get_string('gradepassgreaterthangrade', 'grades', 100);
            }
        }

        return $errors;
    }
}
