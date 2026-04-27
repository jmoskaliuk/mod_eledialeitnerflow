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
 * Library of callbacks and functions for mod_eledialeitnerflow.
 *
 * @package    mod_eledialeitnerflow
 * @copyright  2026 eLeDia GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_eledialeitnerflow\engine\leitner_engine;

// Required activity module callbacks.

/**
 * Create a new instance of the eledialeitnerflow activity.
 *
 * @param stdClass $data Form data object.
 * @param mixed $mform Optional form object.
 * @return int The ID of the newly created instance.
 */
function eledialeitnerflow_add_instance(stdClass $data, $mform = null): int {
    global $DB;
    $data->timecreated  = time();
    $data->timemodified = time();
    _eledialeitnerflow_process_categories($data);
    $data->id = $DB->insert_record('eledialeitnerflow', $data);
    eledialeitnerflow_grade_item_update($data);
    return $data->id;
}

/**
 * Update an existing instance of the eledialeitnerflow activity.
 *
 * @param stdClass $data Form data object.
 * @param mixed $mform Optional form object.
 * @return bool True if successful.
 */
function eledialeitnerflow_update_instance(stdClass $data, $mform = null): bool {
    global $DB;
    $data->id           = $data->instance;
    $data->timemodified = time();
    _eledialeitnerflow_process_categories($data);
    $DB->update_record('eledialeitnerflow', $data);
    eledialeitnerflow_grade_item_update($data);
    return true;
}

/**
 * Convert the form's category array into comma-separated string for DB storage.
 *
 * Also keeps the legacy questioncategoryid field in sync (first selected category).
 *
 * @param stdClass $data Form data object (modified in place).
 */
function _eledialeitnerflow_process_categories(stdClass &$data): void {
    if (!empty($data->questioncategoryids_array) && is_array($data->questioncategoryids_array)) {
        $ids = array_filter(array_map('intval', $data->questioncategoryids_array), fn($id) => $id > 0);
        $data->questioncategoryids = implode(',', $ids);
        // Keep legacy field in sync.
        $data->questioncategoryid = !empty($ids) ? reset($ids) : 0;
        // Remove the form-only field before DB insert.
        unset($data->questioncategoryids_array);
    }
}

/**
 * Delete an instance of the eledialeitnerflow activity.
 *
 * @param int $id The instance ID to delete.
 * @return bool True if successful, false otherwise.
 */
function eledialeitnerflow_delete_instance(int $id): bool {
    global $DB;

    // Fetch the full record before deleting (needed for grade_item_delete).
    $leitnerflow = $DB->get_record('eledialeitnerflow', ['id' => $id]);
    if (!$leitnerflow) {
        return false;
    }

    // Clean up all sessions (includes question_usages cleanup).
    $sessions = $DB->get_records('eledialeitnerflow_sessions', ['eledialeitnerflowid' => $id]);
    foreach ($sessions as $session) {
        if (!empty($session->qubaid)) {
            question_engine::delete_questions_usage_by_activity($session->qubaid);
        }
    }
    $DB->delete_records('eledialeitnerflow_sessions', ['eledialeitnerflowid' => $id]);
    $DB->delete_records('eledialeitnerflow_card_state', ['eledialeitnerflowid' => $id]);
    $DB->delete_records('eledialeitnerflow', ['id' => $id]);

    // Pass the full object with 'course' property to avoid fatal error in grade_update().
    eledialeitnerflow_grade_item_delete($leitnerflow);
    return true;
}

/**
 * Check if the activity module supports a given feature.
 *
 * @param string $feature The feature name to check.
 * @return bool|string|null The feature support status.
 */
function eledialeitnerflow_supports(string $feature): bool|string|null {
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

// Gradebook integration.

/**
 * Update the grade item for the activity in the gradebook.
 *
 * @param stdClass $leitnerflow The eledialeitnerflow instance object.
 * @param mixed $grades Optional grades array or 'reset' string.
 * @return int Gradebook update status code.
 */
function eledialeitnerflow_grade_item_update(stdClass $leitnerflow, $grades = null): int {
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

    $result = grade_update(
        'mod/eledialeitnerflow',
        $leitnerflow->course,
        'mod',
        'eledialeitnerflow',
        $leitnerflow->id,
        0,
        $grades,
        $params
    );

    $gradepass = empty($leitnerflow->gradepass) ? 0 : (float)$leitnerflow->gradepass;

    $gradeitem = grade_item::fetch([
        'itemtype' => 'mod',
        'itemmodule' => 'eledialeitnerflow',
        'iteminstance' => $leitnerflow->id,
        'itemnumber' => 0,
        'courseid' => $leitnerflow->course,
    ]);

    if ($gradeitem && (float)$gradeitem->gradepass !== $gradepass) {
        $gradeitem->gradepass = $gradepass;
        $gradeitem->update();
    }

    return $result;
}

/**
 * Delete the grade item for the activity from the gradebook.
 *
 * @param stdClass $leitnerflow The eledialeitnerflow instance object.
 * @return int Gradebook update status code.
 */
function eledialeitnerflow_grade_item_delete(stdClass $leitnerflow): int {
    global $CFG;
    require_once($CFG->libdir . '/gradelib.php');
    return grade_update(
        'mod/eledialeitnerflow',
        $leitnerflow->course,
        'mod',
        'eledialeitnerflow',
        $leitnerflow->id,
        0,
        null,
        ['deleted' => 1]
    );
}

/**
 * Update grades for users in the activity.
 *
 * @param stdClass $leitnerflow The eledialeitnerflow instance object.
 * @param int $userid Optional user ID. If 0, updates all users.
 * @param bool $nullifnone Whether to set null grades if no data exists.
 * @return void
 */
function eledialeitnerflow_update_grades(stdClass $leitnerflow, int $userid = 0, bool $nullifnone = true): void {
    global $CFG;
    require_once($CFG->libdir . '/gradelib.php');

    if ((int)$leitnerflow->grademethod === 0) {
        return;
    }

    if ($userid > 0) {
        $grades = eledialeitnerflow_get_user_grade($leitnerflow, $userid);
    } else {
        $grades = eledialeitnerflow_get_all_grades($leitnerflow);
    }

    eledialeitnerflow_grade_item_update($leitnerflow, $grades);
}

/**
 * Get the grade for a specific user.
 *
 * @param stdClass $leitnerflow The eledialeitnerflow instance object.
 * @param int $userid The user ID.
 * @return array Grade array with userid as key.
 */
function eledialeitnerflow_get_user_grade(stdClass $leitnerflow, int $userid): array {
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

/**
 * Get grades for all enrolled users.
 *
 * @param stdClass $leitnerflow The eledialeitnerflow instance object.
 * @return array Grade array with userid as key for each user.
 */
function eledialeitnerflow_get_all_grades(stdClass $leitnerflow): array {
    global $DB;
    $cm      = get_coursemodule_from_instance('eledialeitnerflow', $leitnerflow->id);
    $context = \core\context\module::instance($cm->id);
    $students = get_enrolled_users($context, 'mod/eledialeitnerflow:attempt');

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

// Course module info (for mobile app compatibility).

/**
 * Get the cached course module info.
 *
 * @param stdClass $coursemodule The course module object.
 * @return cached_cm_info The cached course module info object.
 */
function eledialeitnerflow_get_coursemodule_info(stdClass $coursemodule): cached_cm_info {
    global $DB;
    $lq = $DB->get_record(
        'eledialeitnerflow',
        ['id' => $coursemodule->instance],
        'id, name, intro, introformat'
    );
    if (!$lq) {
        return new cached_cm_info();
    }
    $info = new cached_cm_info();
    $info->name = $lq->name;
    if ($coursemodule->showdescription) {
        $info->content = format_module_intro('eledialeitnerflow', $lq, $coursemodule->id, false);
    }
    return $info;
}

// Reset course (teacher).

/**
 * Add form elements for course reset.
 *
 * @param moodleform $mform The reset form object.
 * @return void
 */
function eledialeitnerflow_reset_course_form_definition(&$mform): void {
    $mform->addElement('header', 'eledialeitnerflowheader', get_string('modulename', 'mod_eledialeitnerflow'));
    $mform->addElement('checkbox', 'reset_eledialeitnerflow', get_string('resetprogress', 'mod_eledialeitnerflow'));
}

/**
 * Process course reset and delete user data.
 *
 * @param stdClass $data The reset data object.
 * @return array Status array with reset result information.
 */
function eledialeitnerflow_reset_userdata(stdClass $data): array {
    global $DB;

    $status = [];
    if (!empty($data->reset_eledialeitnerflow)) {
        $quizzes = $DB->get_records('eledialeitnerflow', ['course' => $data->courseid]);
        foreach ($quizzes as $quiz) {
            $sessions = $DB->get_records('eledialeitnerflow_sessions', ['eledialeitnerflowid' => $quiz->id]);
            foreach ($sessions as $session) {
                if (!empty($session->qubaid)) {
                    question_engine::delete_questions_usage_by_activity($session->qubaid);
                }
            }
            $DB->delete_records('eledialeitnerflow_sessions', ['eledialeitnerflowid' => $quiz->id]);
            $DB->delete_records('eledialeitnerflow_card_state', ['eledialeitnerflowid' => $quiz->id]);
        }
        $status[] = [
            'component' => get_string('modulename', 'mod_eledialeitnerflow'),
            'item'      => get_string('resetprogress', 'mod_eledialeitnerflow'),
            'error'     => false,
        ];
    }
    return $status;
}
