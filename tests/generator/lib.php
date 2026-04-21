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
 * Test data generator for mod_eledialeitnerflow.
 *
 * @package    mod_eledialeitnerflow
 * @copyright  2026 eLeDia GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Generator class for mod_eledialeitnerflow PHPUnit tests.
 *
 * Lets test classes create fully configured eledialeitnerflow instances
 * and card states with a single method call.
 *
 * @package    mod_eledialeitnerflow
 * @copyright  2026 eLeDia GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_eledialeitnerflow_generator extends testing_module_generator {
    /**
     * Create an eledialeitnerflow activity with sensible Leitner defaults.
     *
     * All settings can be overridden via $record:
     *   correcttolearn, boxcount, wrongbehavior, sessionsize, etc.
     *
     * @param array|stdClass $record
     * @param array|null     $options
     * @return stdClass  The course-module record (has ->instance for eledialeitnerflow id)
     */
    public function create_instance($record = null, ?array $options = null): stdClass {
        $record = (object)(array)($record ?? []);

        // Default Leitner settings.
        $defaults = [
            'name'               => 'Test Leitner Flow ' . $this->instancecount,
            'intro'              => 'Test intro',
            'introformat'        => FORMAT_HTML,
            'questioncategoryid' => 0, // Will be set by tests that need real questions.
            'sessionsize'        => 10,
            'boxcount'           => 3,
            'correcttolearn'     => 3,
            'wrongbehavior'      => 0, // WRONG_RESET.
            'questionrotation'   => 1,
            'prioritystrategy'   => 0,
            'grade'              => 0,
            'grademethod'        => 0,
        ];

        foreach ($defaults as $key => $val) {
            if (!isset($record->$key)) {
                $record->$key = $val;
            }
        }

        // Allow Behat feature files to pass a category name instead of a numeric ID.
        if (!empty($record->questioncategoryid) && !is_numeric($record->questioncategoryid)) {
            global $DB;
            $catid = $DB->get_field('question_categories', 'id', ['name' => $record->questioncategoryid]);
            if ($catid === false) {
                throw new \coding_exception(
                    "Question category '{$record->questioncategoryid}' not found in the database."
                );
            }
            $record->questioncategoryid = (int) $catid;
        }

        return parent::create_instance($record, $options);
    }

    /**
     * Create a card state record directly in the DB.
     * Useful for tests that need to set up a specific Leitner state.
     *
     * @param array $data  Keys: eledialeitnerflowid, userid, questionid, currentbox,
     *                     correctcount, attemptcount, status
     * @return stdClass  The inserted record
     */
    public function create_card_state(array $data): stdClass {
        global $DB;

        $defaults = [
            'currentbox'   => 1,
            'correctcount' => 0,
            'attemptcount' => 0,
            'status'       => 0, // STATUS_OPEN.
            'timecreated'  => time(),
            'timemodified' => time(),
        ];

        $record = (object)array_merge($defaults, $data);

        // Upsert: delete existing state for same quiz+user+question if present.
        $DB->delete_records('eledialeitnerflow_card_state', [
            'eledialeitnerflowid' => $record->eledialeitnerflowid,
            'userid'        => $record->userid,
            'questionid'    => $record->questionid,
        ]);

        $record->id = $DB->insert_record('eledialeitnerflow_card_state', $record);
        return $record;
    }

    /**
     * Create a completed session record.
     *
     * @param array $data  Keys: eledialeitnerflowid, userid, questionsasked, questionscorrect
     * @return stdClass
     */
    public function create_session(array $data): stdClass {
        global $DB;

        $defaults = [
            'qubaid'           => null,
            'questionids'      => json_encode([]),
            'currentindex'     => 0,
            'questionsasked'   => 5,
            'questionscorrect' => 3,
            'status'           => 1, // Completed.
            'timecreated'      => time() - 3600,
            'timecompleted'    => time(),
        ];

        $record    = (object)array_merge($defaults, $data);
        $record->id = $DB->insert_record('eledialeitnerflow_sessions', $record);
        return $record;
    }
}
