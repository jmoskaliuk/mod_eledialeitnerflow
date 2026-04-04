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
 * Backup steps for mod_leitnerflow.
 *
 * @package    mod_leitnerflow
 * @copyright  2024 eLeDia GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class backup_leitnerflow_activity_structure_step extends backup_activity_structure_step {

    protected function define_structure(): backup_nested_element {
        $userinfo = $this->get_setting_value('userinfo');

        // Root: leitnerflow instance
        $leitnerflow = new backup_nested_element('leitnerflow', ['id'], [
            'name', 'intro', 'introformat',
            'questioncategoryid', 'questioncategoryids', 'sessionsize', 'boxcount',
            'correcttolearn', 'wrongbehavior', 'questionrotation',
            'prioritystrategy', 'grade', 'grademethod', 'showanimation',
            'timecreated', 'timemodified',
        ]);

        // Card states per user
        $cardstates = new backup_nested_element('card_states');
        $cardstate  = new backup_nested_element('card_state', ['id'], [
            'userid', 'questionid', 'currentbox', 'correctcount',
            'attemptcount', 'status', 'timecreated', 'timemodified',
        ]);

        // Sessions per user
        $sessions = new backup_nested_element('sessions');
        $session  = new backup_nested_element('session', ['id'], [
            'userid', 'questionids', 'currentindex',
            'questionsasked', 'questionscorrect', 'status',
            'timecreated', 'timecompleted',
        ]);

        // Build tree
        $leitnerflow->add_child($cardstates);
        $cardstates->add_child($cardstate);
        $leitnerflow->add_child($sessions);
        $sessions->add_child($session);

        // Data sources
        $leitnerflow->set_source_table('leitnerflow', ['id' => backup::VAR_ACTIVITYID]);

        if ($userinfo) {
            $cardstate->set_source_table('leitnerflow_card_state',
                ['leitnerflowid' => backup::VAR_PARENTID]);
            $cardstate->annotate_ids('user', 'userid');
            $cardstate->annotate_ids('question', 'questionid');

            $session->set_source_table('leitnerflow_sessions',
                ['leitnerflowid' => backup::VAR_PARENTID]);
            $session->annotate_ids('user', 'userid');
        }

        $leitnerflow->annotate_files('mod_leitnerflow', 'intro', null);

        return $this->prepare_activity_structure($leitnerflow);
    }
}
