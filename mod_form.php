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
        global $DB, $COURSE;

        $mform = $this->_form;

        // ---- General -------------------------------------------------------
        $mform->addElement('header', 'general', get_string('general', 'form'));

        $mform->addElement('text', 'name', get_string('name'), ['size' => 64]);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required');

        $this->standard_intro_elements();

        // ---- Question Bank -------------------------------------------------
        $mform->addElement('header', 'questionbanksettings', get_string('questioncategory', 'mod_leitnerflow'));

        // Load all question categories accessible from this course.
        // Search in all contexts the course has access to (course, coursecat, system).
        $coursecontext = \core\context\course::instance($COURSE->id);
        $contextids = $coursecontext->get_parent_context_ids(true); // Includes self.

        $categories = [];
        if (!empty($contextids)) {
            list($insql, $inparams) = $DB->get_in_or_equal($contextids, SQL_PARAMS_NAMED);
            $sql = "SELECT qc.id, qc.name, qc.contextid, ctx.contextlevel
                      FROM {question_categories} qc
                      JOIN {context} ctx ON ctx.id = qc.contextid
                     WHERE qc.contextid {$insql}
                  ORDER BY ctx.contextlevel DESC, qc.sortorder, qc.name";
            $cats = $DB->get_records_sql($sql, $inparams);
            foreach ($cats as $cat) {
                $ctx = \context::instance_by_id($cat->contextid);
                $categories[$cat->id] = $cat->name . ' (' . $ctx->get_context_name(false, true) . ')';
            }
        }

        if (empty($categories)) {
            $mform->addElement('static', 'nocategory_warning', '',
                html_writer::tag('div',
                    get_string('nocategory', 'mod_leitnerflow'),
                    ['class' => 'alert alert-warning']
                )
            );
            $categories = [0 => '---'];
        }

        $mform->addElement('select', 'questioncategoryid',
            get_string('questioncategory', 'mod_leitnerflow'), $categories);
        $mform->addHelpButton('questioncategoryid', 'questioncategory', 'mod_leitnerflow');
        $mform->addRule('questioncategoryid', null, 'required');

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
