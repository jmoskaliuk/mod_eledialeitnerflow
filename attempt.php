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
 * Question attempt page for mod_leitnerflow.
 *
 * Uses Moodle's immediatefeedback Check button (no plugin duplicate).
 * UI uses Moodle Component Library (Bootstrap) where possible.
 *
 * @package    mod_leitnerflow
 * @copyright  2024 eLeDia GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');
require_once($CFG->dirroot . '/question/engine/lib.php');

use mod_leitnerflow\engine\leitner_engine;

$cmid   = required_param('id',    PARAM_INT);
$sessid = optional_param('sessid', 0,  PARAM_INT);
$start  = optional_param('start',  0,  PARAM_INT);

$cm          = get_coursemodule_from_id('leitnerflow', $cmid, 0, false, MUST_EXIST);
$course      = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$leitnerflow = $DB->get_record('leitnerflow', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, true, $cm);
require_capability('mod/leitnerflow:attempt', \core\context\module::instance($cm->id));

$context = \core\context\module::instance($cm->id);
$viewurl = new moodle_url('/mod/leitnerflow/view.php', ['id' => $cm->id]);

// ---- Start a new session ---------------------------------------------------
if ($start) {
    // Complete any stale active session (save partial results rather than deleting).
    $stale = $DB->get_records('leitnerflow_sessions', [
        'leitnerflowid' => $leitnerflow->id,
        'userid'        => $USER->id,
        'status'        => 0,
    ]);
    foreach ($stale as $s) {
        $s->status        = 1; // Mark as completed.
        $s->timecompleted = time();
        $DB->update_record('leitnerflow_sessions', $s);
        // Clean up quba to free resources.
        if (!empty($s->qubaid)) {
            question_engine::delete_questions_usage_by_activity($s->qubaid);
            $s->qubaid = null;
            $DB->set_field('leitnerflow_sessions', 'qubaid', null, ['id' => $s->id]);
        }
    }

    // Select questions for this session.
    $questionids = leitner_engine::select_session_questions($leitnerflow, $USER->id);

    if (empty($questionids)) {
        redirect($viewurl, get_string('nounlearnedcards', 'mod_leitnerflow'),
            null, \core\output\notification::NOTIFY_INFO);
    }

    // Build question_usage_by_activity (quba).
    $quba = question_engine::make_questions_usage_by_activity('mod_leitnerflow', $context);
    $quba->set_preferred_behaviour('immediatefeedback');

    foreach ($questionids as $qid) {
        $question = question_bank::load_question($qid);
        $quba->add_question($question, 1);
    }
    $quba->start_all_questions();
    question_engine::save_questions_usage_by_activity($quba);

    // Save session record.
    $session = new stdClass();
    $session->leitnerflowid  = $leitnerflow->id;
    $session->userid         = $USER->id;
    $session->qubaid         = $quba->get_id();
    $session->questionids    = json_encode($questionids);
    $session->currentindex   = 0;
    $session->questionsasked = 0;
    $session->questionscorrect = 0;
    $session->status         = 0;
    $session->timecreated    = time();
    $session->id = $DB->insert_record('leitnerflow_sessions', $session);

    // Fire session_started event.
    $event = \mod_leitnerflow\event\session_started::create([
        'objectid' => $session->id,
        'context'  => $context,
        'other'    => ['questioncount' => count($questionids)],
    ]);
    $event->trigger();

    redirect(new moodle_url('/mod/leitnerflow/attempt.php', ['id' => $cmid, 'sessid' => $session->id]));
}

// ---- Load existing session -------------------------------------------------
if (!$sessid) {
    redirect($viewurl);
}
$session = $DB->get_record('leitnerflow_sessions', ['id' => $sessid, 'userid' => $USER->id], '*', MUST_EXIST);

if ((int)$session->status === 1) {
    redirect($viewurl);
}

$questionids   = json_decode($session->questionids, true);
$totalquestions = count($questionids);
$currentindex  = (int)$session->currentindex;

// Session complete?
if ($currentindex >= $totalquestions) {
    _leitnerflow_finish_session($session, $leitnerflow, $cm, $course);
    exit;
}

$quba = question_engine::load_questions_usage_by_activity($session->qubaid);
$slot = $currentindex + 1; // quba slots are 1-based.

// ---- Process POST (answer submission) --------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_sesskey();

    $timenow = time();
    $quba->process_all_actions($timenow);
    question_engine::save_questions_usage_by_activity($quba);

    // Was the answer correct?
    $fraction = $quba->get_question_fraction($slot);
    $correct  = ($fraction !== null && $fraction >= 1.0);

    // Update Leitner state.
    $questionid = $questionids[$currentindex];
    $state = leitner_engine::get_card_state($leitnerflow->id, $USER->id, $questionid);
    $state = leitner_engine::process_answer($state, $correct, $leitnerflow, $questionid, $USER->id);
    leitner_engine::save_card_state($state);

    // Update session progress.
    $session->questionsasked++;
    if ($correct) {
        $session->questionscorrect++;
    }
    $session->currentindex = $currentindex + 1;

    if ($session->currentindex >= $totalquestions) {
        _leitnerflow_finish_session($session, $leitnerflow, $cm, $course);
        exit;
    }

    $DB->update_record('leitnerflow_sessions', $session);

    redirect(new moodle_url('/mod/leitnerflow/attempt.php', ['id' => $cmid, 'sessid' => $sessid]));
}

// ---- Render the current question -------------------------------------------
$PAGE->set_url('/mod/leitnerflow/attempt.php', ['id' => $cmid, 'sessid' => $sessid]);
$PAGE->set_title(format_string($leitnerflow->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);
$PAGE->add_body_class('mod-leitnerflow-attempt');

// Display options for question rendering.
$displayoptions = new question_display_options();
$displayoptions->marks           = question_display_options::MAX_ONLY;
$displayoptions->feedback        = question_display_options::HIDDEN;
$displayoptions->generalfeedback = question_display_options::HIDDEN;
$displayoptions->rightanswer     = question_display_options::HIDDEN;
$displayoptions->history         = question_display_options::HIDDEN;
$displayoptions->correctness     = question_display_options::HIDDEN;

$questionid = $questionids[$currentindex];

echo $OUTPUT->header();

// ---- Back button (like Moodle Quiz) ----
echo $OUTPUT->single_button($viewurl, get_string('back'), 'get');

// ---- Question form ----
$actionurl = new moodle_url('/mod/leitnerflow/attempt.php', ['id' => $cmid, 'sessid' => $sessid]);
echo html_writer::start_tag('form', [
    'method'  => 'post',
    'action'  => $actionurl->out(false),
    'enctype' => 'multipart/form-data',
    'class'   => 'leitnerflow-question-form',
    'id'      => 'leitnerflow-question-form',
]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'attempt', 'value' => 1]);

// Render the question directly (Moodle's behaviour provides the Check button).
echo $quba->render_question($slot, $displayoptions, ($currentindex + 1) . '');

echo html_writer::end_tag('form');

// ---- Bottom navigation (like Moodle Quiz: secondary left, info right) ----
$cancelurl = new moodle_url('/mod/leitnerflow/view.php', [
    'id' => $cm->id,
    'cancelsession' => 1,
    'sesskey' => sesskey(),
]);
echo html_writer::start_div('d-flex justify-content-between align-items-center mt-3 mb-3');
echo $OUTPUT->single_button($cancelurl, get_string('cancelsession', 'mod_leitnerflow'), 'get');
echo html_writer::span(
    get_string('question') . ' ' . ($currentindex + 1) . ' / ' . $totalquestions
    . ' &middot; '
    . html_writer::tag('b', $session->questionscorrect) . ' / ' . $session->questionsasked
    . ' ' . get_string('correct', 'mod_leitnerflow'),
    'text-muted small'
);
echo html_writer::end_div();

echo $OUTPUT->footer();

// ---- Helper: finish session ------------------------------------------------
function _leitnerflow_finish_session(stdClass $session, stdClass $leitnerflow, stdClass $cm, stdClass $course): void {
    global $DB, $OUTPUT, $PAGE, $USER;

    $session->status        = 1;
    $session->timecompleted = time();
    $DB->update_record('leitnerflow_sessions', $session);

    // Fire session_completed event.
    $event = \mod_leitnerflow\event\session_completed::create([
        'objectid' => $session->id,
        'context'  => \core\context\module::instance($cm->id),
        'other'    => [
            'questionsasked' => $session->questionsasked,
            'questionscorrect' => $session->questionscorrect,
        ],
    ]);
    $event->trigger();

    // Update gradebook.
    if ((int)$leitnerflow->grademethod > 0) {
        leitnerflow_update_grades($leitnerflow, $USER->id);
    }

    $viewurl = new moodle_url('/mod/leitnerflow/view.php', ['id' => $cm->id]);

    $PAGE->set_url('/mod/leitnerflow/attempt.php', ['id' => $cm->id]);
    $PAGE->set_title(format_string($leitnerflow->name));
    $PAGE->set_context(\core\context\module::instance($cm->id));
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('sessioncomplete', 'mod_leitnerflow'), 3);

    $result = get_string('sessionresult', 'mod_leitnerflow', [
        'correct' => $session->questionscorrect,
        'total'   => $session->questionsasked,
    ]);
    echo $OUTPUT->notification($result, 'success');

    echo $OUTPUT->single_button($viewurl, get_string('continue'), 'get');
    echo $OUTPUT->footer();
}
