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
 * Library of callbacks and functions for mod_leitnerflow.
 *
 * @package    mod_leitnerflow
 * @copyright  2024 eLeDia GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use mod_leitnerflow\engine\leitner_engine;

// -----------------------------------------------------------------------
// Required activity module callbacks
// -----------------------------------------------------------------------

function leitnerflow_add_instance(stdClass $data, $mform = null): int {
    global $DB;
    $data->timecreated  = time();
    $data->timemodified = time();
    _leitnerflow_process_categories($data);
    $data->id = $DB->insert_record('leitnerflow', $data);
    leitnerflow_grade_item_update($data);
    return $data->id;
}

function leitnerflow_update_instance(stdClass $data, $mform = null): bool {
    global $DB;
    $data->id           = $data->instance;
    $data->timemodified = time();
    _leitnerflow_process_categories($data);
    $DB->update_record('leitnerflow', $data);
    leitnerflow_grade_item_update($data);
    return true;
}

/**
 * Convert the form's category array into comma-separated string for DB storage.
 *
 * Also keeps the legacy questioncategoryid field in sync (first selected category).
 *
 * @param stdClass $data Form data object (modified in place)
 */
function _leitnerflow_process_categories(stdClass &$data): void {
    if (!empty($data->questioncategoryids_array) && is_array($data->questioncategoryids_array)) {
        $ids = array_filter(array_map('intval', $data->questioncategoryids_array), fn($id) => $id > 0);
        $data->questioncategoryids = implode(',', $ids);
        // Keep legacy field in sync.
        $data->questioncategoryid = !empty($ids) ? reset($ids) : 0;
        // Remove the form-only field before DB insert.
        unset($data->questioncategoryids_array);
    }
}

function leitnerflow_delete_instance(int $id): bool {
    global $DB;

    // Fetch the full record before deleting (needed for grade_item_delete).
    $leitnerflow = $DB->get_record('leitnerflow', ['id' => $id]);
    if (!$leitnerflow) {
        return false;
    }

    // Clean up all sessions (includes question_usages cleanup).
    $sessions = $DB->get_records('leitnerflow_sessions', ['leitnerflowid' => $id]);
    foreach ($sessions as $session) {
        if (!empty($session->qubaid)) {
            question_engine::delete_questions_usage_by_activity($session->qubaid);
        }
    }
    $DB->delete_records('leitnerflow_sessions',   ['leitnerflowid' => $id]);
    $DB->delete_records('leitnerflow_card_state', ['leitnerflowid' => $id]);
    $DB->delete_records('leitnerflow',            ['id' => $id]);

    // Pass the full object with 'course' property to avoid fatal error in grade_update().
    leitnerflow_grade_item_delete($leitnerflow);
    return true;
}

function leitnerflow_supports(string $feature): ?bool {
    return match ($feature) {
        FEATURE_MOD_INTRO              => true,
        FEATURE_SHOW_DESCRIPTION       => true,
        FEATURE_GRADE_HAS_GRADE        => true,
        FEATURE_COMPLETION_TRACKS_VIEWS => true,
        FEATURE_BACKUP_MOODLE2         => true,
        FEATURE_MOD_PURPOSE            => MOD_PURPOSE_ASSESSMENT,
        default                        => null,
    };
}

// -----------------------------------------------------------------------
// Gradebook integration
// -----------------------------------------------------------------------

function leitnerflow_grade_item_update(stdClass $leitnerflow, $grades = null): int {
    global $CFG;
    require_once($CFG->libdir . '/gradelib.php');

    $params = [
        'itemname'  => $leitnerflow->name,
        'idnumber'  => $leitnerflow->cmidnumber ?? '',
    ];

    if (!isset($leitnerflow->grademethod) || (int)$leitnerflow->grademethod === 0) {
        $params['gradetype'] = GRADE_TYPE_NONE;
    } else {
        $params['gradetype'] = GRADE_TYPE_VALUE;
        $params['grademax']  = 100;
        $params['grademin']  = 0;
    }

    if ($grades === 'reset') {
        $params['reset'] = true;
        $grades = null;
    }

    return grade_update(
        'mod/leitnerflow',
        $leitnerflow->course,
        'mod',
        'leitnerflow',
        $leitnerflow->id,
        0,
        $grades,
        $params
    );
}

function leitnerflow_grade_item_delete(stdClass $leitnerflow): int {
    global $CFG;
    require_once($CFG->libdir . '/gradelib.php');
    return grade_update(
        'mod/leitnerflow',
        $leitnerflow->course,
        'mod',
        'leitnerflow',
        $leitnerflow->id,
        0,
        null,
        ['deleted' => 1]
    );
}

function leitnerflow_update_grades(stdClass $leitnerflow, int $userid = 0, bool $nullifnone = true): void {
    global $CFG;
    require_once($CFG->libdir . '/gradelib.php');

    if ((int)$leitnerflow->grademethod === 0) {
        return;
    }

    if ($userid > 0) {
        $grades = leitnerflow_get_user_grade($leitnerflow, $userid);
    } else {
        $grades = leitnerflow_get_all_grades($leitnerflow);
    }

    leitnerflow_grade_item_update($leitnerflow, $grades);
}

function leitnerflow_get_user_grade(stdClass $leitnerflow, int $userid): array {
    $categoryids = leitner_engine::get_category_ids($leitnerflow);
    $stats = leitner_engine::get_user_stats(
        $leitnerflow->id,
        $userid,
        $categoryids
    );

    $grade           = new stdClass();
    $grade->userid   = $userid;
    $grade->rawgrade = $stats->percent_learned;

    return [$userid => $grade];
}

function leitnerflow_get_all_grades(stdClass $leitnerflow): array {
    global $DB;
    $cm      = get_coursemodule_from_instance('leitnerflow', $leitnerflow->id);
    $context = \core\context\module::instance($cm->id);
    $students = get_enrolled_users($context, 'mod/leitnerflow:attempt');

    $categoryids = leitner_engine::get_category_ids($leitnerflow);
    $grades = [];
    foreach ($students as $student) {
        $grade           = new stdClass();
        $grade->userid   = $student->id;
        $stats = leitner_engine::get_user_stats(
            $leitnerflow->id,
            $student->id,
            $categoryids
        );
        $grade->rawgrade = $stats->percent_learned;
        $grades[$student->id] = $grade;
    }
    return $grades;
}

// -----------------------------------------------------------------------
// Course module info (for mobile app compatibility)
// -----------------------------------------------------------------------

function leitnerflow_get_coursemodule_info(stdClass $coursemodule): cached_cm_info {
    global $DB;
    $lq = $DB->get_record('leitnerflow', ['id' => $coursemodule->instance],
        'id, name, intro, introformat');
    if (!$lq) {
        return new cached_cm_info();
    }
    $info = new cached_cm_info();
    $info->name = $lq->name;
    if ($coursemodule->showdescription) {
        $info->content = format_module_intro('leitnerflow', $lq, $coursemodule->id, false);
    }
    return $info;
}

// -----------------------------------------------------------------------
// Reset course (teacher)
// -----------------------------------------------------------------------

function leitnerflow_reset_course_form_definition(&$mform): void {
    $mform->addElement('header', 'leitnerflowheader', get_string('modulename', 'mod_leitnerflow'));
    $mform->addElement('checkbox', 'reset_leitnerflow', get_string('resetprogress', 'mod_leitnerflow'));
}

function leitnerflow_reset_userdata(stdClass $data): array {
    global $DB;

    $status = [];
    if (!empty($data->reset_leitnerflow)) {
        $quizzes = $DB->get_records('leitnerflow', ['course' => $data->courseid]);
        foreach ($quizzes as $quiz) {
            $sessions = $DB->get_records('leitnerflow_sessions', ['leitnerflowid' => $quiz->id]);
            foreach ($sessions as $session) {
                if (!empty($session->qubaid)) {
                    question_engine::delete_questions_usage_by_activity($session->qubaid);
                }
            }
            $DB->delete_records('leitnerflow_sessions',   ['leitnerflowid' => $quiz->id]);
            $DB->delete_records('leitnerflow_card_state', ['leitnerflowid' => $quiz->id]);
        }
        $status[] = [
            'component' => get_string('modulename', 'mod_leitnerflow'),
            'item'      => get_string('resetprogress', 'mod_leitnerflow'),
            'error'     => false,
        ];
    }
    return $status;
}
