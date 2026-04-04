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
 * Activity settings form for mod_leitnerflow.
 *
 * @package    mod_leitnerflow
 * @copyright  2024 eLeDia GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/moodleform_mod.php');

class mod_leitnerflow_mod_form extends moodleform_mod {

    public function definition(): void {
        global $CFG, $DB, $COURSE;

        $mform = $this->_form;

        // ---- General -------------------------------------------------------
        $mform->addElement('header', 'general', get_string('general', 'form'));

        $mform->addElement('text', 'name', get_string('name'), ['size' => 64]);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required');

        $this->standard_intro_elements();

        // ---- Question Bank -------------------------------------------------
        $mform->addElement('header', 'questionbanksettings', get_string('questioncategory', 'mod_leitnerflow'));

        // Load ALL question categories (simplest possible query).
        $categories = [];
        $debuginfo = '';
        try {
            $allcats = $DB->get_records('question_categories', [], 'name ASC', 'id, name, contextid, parent');
            $debuginfo .= 'Found ' . count($allcats) . ' total categories. ';
            foreach ($allcats as $cat) {
                if ($cat->name === 'top') {
                    continue;
                }
                $qcount = $DB->count_records('question_bank_entries', ['questioncategoryid' => $cat->id]);
                $categories[$cat->id] = $cat->name . " ({$qcount} questions)";
            }
            $debuginfo .= count($categories) . ' shown in dropdown.';
        } catch (\Exception $e) {
            $debuginfo = 'ERROR: ' . $e->getMessage();
        }

        if (empty($categories)) {
            $mform->addElement('static', 'nocategory_warning', '',
                \html_writer::tag('div',
                    get_string('nocategory', 'mod_leitnerflow'),
                    ['class' => 'alert alert-warning']
                )
            );
            $categories = [0 => '---'];
        }

        $mform->addElement('autocomplete', 'questioncategoryids_array',
            get_string('questioncategory', 'mod_leitnerflow'), $categories,
            ['multiple' => true]);
        $mform->addHelpButton('questioncategoryids_array', 'questioncategory', 'mod_leitnerflow');
        $mform->addRule('questioncategoryids_array', null, 'required');

        // Question rotation
        $rotationoptions = [
            1 => get_string('questionrotation_dynamic', 'mod_leitnerflow'),
            0 => get_string('questionrotation_fixed',   'mod_leitnerflow'),
        ];
        $mform->addElement('select', 'questionrotation',
            get_string('questionrotation', 'mod_leitnerflow'), $rotationoptions);
        $mform->addHelpButton('questionrotation', 'questionrotation', 'mod_leitnerflow');
        $mform->setDefault('questionrotation', 1);

        // ---- Session settings ----------------------------------------------
        $mform->addElement('header', 'sessionsettingsheader',
            get_string('sessionsettings', 'mod_leitnerflow'));

        $mform->addElement('text', 'sessionsize',
            get_string('sessionsize', 'mod_leitnerflow'), ['size' => 4]);
        $mform->setType('sessionsize', PARAM_INT);
        $mform->setDefault('sessionsize', 20);
        $mform->addHelpButton('sessionsize', 'sessionsize', 'mod_leitnerflow');
        $mform->addRule('sessionsize', null, 'required');
        $mform->addRule('sessionsize', null, 'numeric');

        $priorityoptions = [
            0 => get_string('prioritystrategy_prio',  'mod_leitnerflow'),
            1 => get_string('prioritystrategy_mixed', 'mod_leitnerflow'),
        ];
        $mform->addElement('select', 'prioritystrategy',
            get_string('prioritystrategy', 'mod_leitnerflow'), $priorityoptions);
        $mform->addHelpButton('prioritystrategy', 'prioritystrategy', 'mod_leitnerflow');
        $mform->setDefault('prioritystrategy', 0);

        // ---- Leitner System settings ---------------------------------------
        $mform->addElement('header', 'leitnersettingsheader',
            get_string('leitnersettings', 'mod_leitnerflow'));

        $boxoptions = [3 => '3', 4 => '4', 5 => '5'];
        $mform->addElement('select', 'boxcount',
            get_string('boxcount', 'mod_leitnerflow'), $boxoptions);
        $mform->addHelpButton('boxcount', 'boxcount', 'mod_leitnerflow');
        $mform->setDefault('boxcount', 3);

        $mform->addElement('text', 'correcttolearn',
            get_string('correcttolearn', 'mod_leitnerflow'), ['size' => 4]);
        $mform->setType('correcttolearn', PARAM_INT);
        $mform->setDefault('correcttolearn', 3);
        $mform->addHelpButton('correcttolearn', 'correcttolearn', 'mod_leitnerflow');
        $mform->addRule('correcttolearn', null, 'required');
        $mform->addRule('correcttolearn', null, 'numeric');

        $wrongoptions = [
            0 => get_string('wrongbehavior_reset',    'mod_leitnerflow'),
            1 => get_string('wrongbehavior_back1',    'mod_leitnerflow'),
            2 => get_string('wrongbehavior_nochange', 'mod_leitnerflow'),
        ];
        $mform->addElement('select', 'wrongbehavior',
            get_string('wrongbehavior', 'mod_leitnerflow'), $wrongoptions);
        $mform->addHelpButton('wrongbehavior', 'wrongbehavior', 'mod_leitnerflow');
        $mform->setDefault('wrongbehavior', 0);

        // ---- Grading -------------------------------------------------------
        $mform->addElement('header', 'gradingsettingsheader',
            get_string('gradingsettings', 'mod_leitnerflow'));

        $gradeoptions = [
            0 => get_string('grademethod_none',    'mod_leitnerflow'),
            1 => get_string('grademethod_percent', 'mod_leitnerflow'),
        ];
        $mform->addElement('select', 'grademethod',
            get_string('grademethod', 'mod_leitnerflow'), $gradeoptions);
        $mform->setDefault('grademethod', 0);

        $this->standard_coursemodule_elements();
        $this->add_action_buttons();
    }

    /**
     * Pre-process form data for display — convert comma-separated IDs to array.
     */
    public function data_preprocessing(&$defaultvalues): void {
        if (!empty($defaultvalues['questioncategoryids'])) {
            $defaultvalues['questioncategoryids_array'] = explode(',', $defaultvalues['questioncategoryids']);
        } else if (!empty($defaultvalues['questioncategoryid'])) {
            // Legacy fallback.
            $defaultvalues['questioncategoryids_array'] = [(string) $defaultvalues['questioncategoryid']];
        }
    }

    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);

        if (isset($data['sessionsize']) && (int)$data['sessionsize'] < 1) {
            $errors['sessionsize'] = get_string('error', 'moodle');
        }
        if (isset($data['correcttolearn']) && (int)$data['correcttolearn'] < 1) {
            $errors['correcttolearn'] = get_string('error', 'moodle');
        }

        return $errors;
    }
}
