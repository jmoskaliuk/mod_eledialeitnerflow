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
 * Restore steps for mod_eledialeitnerflow.
 *
 * @package    mod_eledialeitnerflow
 * @copyright  2026 eLeDia GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Activity restoration structure step class.
 *
 * @package    mod_eledialeitnerflow
 * @copyright  2026 eLeDia GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_eledialeitnerflow_activity_structure_step extends restore_activity_structure_step {
    /**
     * Define the structure for restoration.
     *
     * @return array Array of restore path elements.
     */
    protected function define_structure(): array {
        $paths   = [];
        $userinfo = $this->get_setting_value('userinfo');

        $paths[] = new restore_path_element('eledialeitnerflow', '/activity/eledialeitnerflow');

        if ($userinfo) {
            $paths[] = new restore_path_element(
                'eledialeitnerflow_card_state',
                '/activity/eledialeitnerflow/card_states/card_state'
            );
            $paths[] = new restore_path_element(
                'eledialeitnerflow_session',
                '/activity/eledialeitnerflow/sessions/session'
            );
        }

        return $this->prepare_activity_structure($paths);
    }

    /**
     * Process eledialeitnerflow activity data during restoration.
     *
     * @param array $data The activity data.
     * @return void
     */
    protected function process_eledialeitnerflow(array $data): void {
        global $DB;

        $data = (object)$data;
        $data->course = $this->get_courseid();
        $data->timecreated  = $this->apply_date_offset($data->timecreated);
        $data->timemodified = $this->apply_date_offset($data->timemodified);

        $categoryids = $this->remap_question_category_ids($data->questioncategoryids ?? '');
        if (!empty($categoryids)) {
            $data->questioncategoryids = implode(',', $categoryids);
            $data->questioncategoryid = reset($categoryids);
        } else {
            $mappedcategoryid = $this->remap_question_category_id((int)($data->questioncategoryid ?? 0));
            $data->questioncategoryid = $mappedcategoryid;
            $data->questioncategoryids = $mappedcategoryid > 0 ? (string)$mappedcategoryid : null;
        }

        $newid = $DB->insert_record('eledialeitnerflow', $data);
        $this->apply_activity_instance($newid);
    }

    /**
     * Process card state data during restoration.
     *
     * @param array $data The card state data.
     * @return void
     */
    protected function process_eledialeitnerflow_card_state(array $data): void {
        global $DB;

        $data = (object)$data;
        $data->eledialeitnerflowid = $this->get_new_parentid('eledialeitnerflow');
        $data->userid        = $this->get_mappingid('user', $data->userid);
        $data->questionid    = $this->get_mappingid('question', $data->questionid);
        $data->timecreated   = $this->apply_date_offset($data->timecreated);
        $data->timemodified  = $this->apply_date_offset($data->timemodified);

        if ($data->userid && $data->questionid) {
            $DB->insert_record('eledialeitnerflow_card_state', $data);
        }
    }

    /**
     * Process session data during restoration.
     *
     * @param array $data The session data.
     * @return void
     */
    protected function process_eledialeitnerflow_session(array $data): void {
        global $DB;

        $data = (object)$data;
        $data->eledialeitnerflowid  = $this->get_new_parentid('eledialeitnerflow');
        $data->userid         = $this->get_mappingid('user', $data->userid);
        $data->timecreated    = $this->apply_date_offset($data->timecreated);
        $data->timecompleted  = !empty($data->timecompleted)
            ? $this->apply_date_offset($data->timecompleted) : null;
        // Qubaid is not restored (question_usages are not portable).
        $data->qubaid = null;
        // Mark as completed so no stale active sessions.
        $data->status = \mod_eledialeitnerflow\engine\leitner_engine::SESSION_STATUS_COMPLETED;

        if ($data->userid) {
            $DB->insert_record('eledialeitnerflow_sessions', $data);
        }
    }

    /**
     * Remap a comma-separated list of question category IDs.
     *
     * @param string|null $categoryids Original category IDs.
     * @return array New category IDs.
     */
    private function remap_question_category_ids(?string $categoryids): array {
        $ids = array_filter(array_map('intval', explode(',', (string)$categoryids)));
        $mappedids = [];
        foreach ($ids as $id) {
            $mappedid = $this->remap_question_category_id($id);
            if ($mappedid > 0) {
                $mappedids[] = $mappedid;
            }
        }
        return array_values(array_unique($mappedids));
    }

    /**
     * Remap one question category ID, preserving same-site IDs only as fallback.
     *
     * @param int $categoryid Original category ID.
     * @return int New category ID, or 0 if unavailable.
     */
    private function remap_question_category_id(int $categoryid): int {
        global $DB;

        if ($categoryid <= 0) {
            return 0;
        }

        $mappedid = $this->get_mappingid('question_category', $categoryid);
        if ($mappedid) {
            return (int)$mappedid;
        }

        if ($this->get_task()->is_samesite() && $DB->record_exists('question_categories', ['id' => $categoryid])) {
            return $categoryid;
        }

        return 0;
    }

    /**
     * Perform actions after restoration is complete.
     *
     * @return void
     */
    protected function after_execute(): void {
        $this->add_related_files('mod_eledialeitnerflow', 'intro', null);
    }
}
