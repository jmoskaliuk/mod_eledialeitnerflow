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
 * Backup steps for mod_eledialeitnerflow.
 *
 * @package    mod_eledialeitnerflow
 * @copyright  2026 eLeDia GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Activity backup structure step class.
 *
 * @package    mod_eledialeitnerflow
 * @copyright  2026 eLeDia GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_eledialeitnerflow_activity_structure_step extends backup_activity_structure_step {
    /**
     * Define the structure for backup.
     *
     * @return backup_nested_element The nested element tree structure.
     */
    protected function define_structure(): backup_nested_element {
        $userinfo = $this->get_setting_value('userinfo');

        // Root: leitnerflow instance.
        $leitnerflow = new backup_nested_element('eledialeitnerflow', ['id'], [
            'name', 'intro', 'introformat',
            'questioncategoryid', 'questioncategoryids', 'sessionsize', 'boxcount',
            'correcttolearn', 'wrongbehavior', 'questionrotation',
            'prioritystrategy', 'grade', 'grademethod', 'showanimation', 'feedbackstyle', 'animationdelay', 'showtour',
            'timecreated', 'timemodified',
        ]);

        // Card states per user.
        $cardstates = new backup_nested_element('card_states');
        $cardstate  = new backup_nested_element('card_state', ['id'], [
            'userid', 'questionid', 'currentbox', 'correctcount',
            'attemptcount', 'status', 'timecreated', 'timemodified',
        ]);

        // Sessions per user.
        $sessions = new backup_nested_element('sessions');
        $session  = new backup_nested_element('session', ['id'], [
            'userid', 'questionids', 'currentindex',
            'questionsasked', 'questionscorrect',
            'currentstreak', 'beststreak', 'status',
            'timecreated', 'timecompleted',
        ]);

        // Question category references make Moodle include and map the selected
        // question bank categories during cross-site restores.
        $questioncategoryrefs = new backup_nested_element('question_category_refs');
        $questioncategoryref = new backup_nested_element('question_category_ref', null, ['questioncategoryid']);

        // Build tree.
        $leitnerflow->add_child($questioncategoryrefs);
        $questioncategoryrefs->add_child($questioncategoryref);
        $leitnerflow->add_child($cardstates);
        $cardstates->add_child($cardstate);
        $leitnerflow->add_child($sessions);
        $sessions->add_child($session);

        // Data sources.
        $leitnerflow->set_source_table('eledialeitnerflow', ['id' => backup::VAR_ACTIVITYID]);

        global $DB;
        $selectedids = $DB->sql_concat("','", 'elf.questioncategoryids', "','");
        $categoryidpattern = $DB->sql_concat("'%,'", $DB->sql_cast_to_char('qc.id'), "',%'");
        $questioncategoryref->set_source_sql(
            "SELECT DISTINCT qc.id AS questioncategoryid
               FROM {question_categories} qc
               JOIN {eledialeitnerflow} elf ON elf.id = ?
              WHERE qc.id = elf.questioncategoryid
                 OR $selectedids LIKE $categoryidpattern",
            [backup::VAR_PARENTID]
        );
        $questioncategoryref->annotate_ids('question_categoryref', 'questioncategoryid');

        if ($userinfo) {
            $cardstate->set_source_table(
                'eledialeitnerflow_card_state',
                ['eledialeitnerflowid' => backup::VAR_PARENTID]
            );
            $cardstate->annotate_ids('user', 'userid');
            $cardstate->annotate_ids('question', 'questionid');

            $session->set_source_table(
                'eledialeitnerflow_sessions',
                ['eledialeitnerflowid' => backup::VAR_PARENTID]
            );
            $session->annotate_ids('user', 'userid');
        }

        $leitnerflow->annotate_files('mod_eledialeitnerflow', 'intro', null);

        return $this->prepare_activity_structure($leitnerflow);
    }
}
