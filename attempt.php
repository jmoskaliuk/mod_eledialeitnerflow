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
 * Question attempt page for mod_eledialeitnerflow.
 *
 * Uses Moodle's immediatefeedback Check button (no plugin duplicate).
 * UI uses Moodle Component Library (Bootstrap) where possible.
 *
 * @package    mod_eledialeitnerflow
 * @copyright  2026 eLeDia GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');
require_once($CFG->dirroot . '/question/engine/lib.php');

use mod_eledialeitnerflow\engine\leitner_engine;

$cmid   = required_param('id', PARAM_INT);
$sessid = optional_param('sessid', 0, PARAM_INT);
$start  = optional_param('start', 0, PARAM_INT);
$box    = optional_param('box', 0, PARAM_INT); // Optional: start session from a specific box.

$cm          = get_coursemodule_from_id('eledialeitnerflow', $cmid, 0, false, MUST_EXIST);
$course      = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$leitnerflow = $DB->get_record('eledialeitnerflow', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, true, $cm);
require_capability('mod/eledialeitnerflow:attempt', \core\context\module::instance($cm->id));

$context = \core\context\module::instance($cm->id);
$viewurl = new moodle_url('/mod/eledialeitnerflow/view.php', ['id' => $cm->id]);

// Start a new session.
if ($start) {
    // Complete any stale active session (save partial results rather than deleting).
    $stale = $DB->get_records('eledialeitnerflow_sessions', [
        'eledialeitnerflowid' => $leitnerflow->id,
        'userid'        => $USER->id,
        'status'        => leitner_engine::SESSION_STATUS_ACTIVE,
    ]);
    foreach ($stale as $s) {
        $s->status        = leitner_engine::SESSION_STATUS_COMPLETED;
        $s->timecompleted = time();
        $DB->update_record('eledialeitnerflow_sessions', $s);
        // Clean up quba to free resources.
        if (!empty($s->qubaid)) {
            question_engine::delete_questions_usage_by_activity($s->qubaid);
            $s->qubaid = null;
            $DB->set_field('eledialeitnerflow_sessions', 'qubaid', null, ['id' => $s->id]);
        }
    }

    // Select questions for this session (optionally filtered by box).
    if ($box > 0) {
        $questionids = leitner_engine::select_questions_from_box($leitnerflow, $USER->id, $box);
    } else {
        $questionids = leitner_engine::select_session_questions($leitnerflow, $USER->id);
    }

    if (empty($questionids)) {
        $msg = ($box > 0)
            ? get_string('nocardsinthisbox', 'mod_eledialeitnerflow', $box)
            : get_string('nounlearnedcards', 'mod_eledialeitnerflow');
        redirect($viewurl, $msg, null, \core\output\notification::NOTIFY_INFO);
    }

    // Build question_usage_by_activity (quba).
    $quba = question_engine::make_questions_usage_by_activity('mod_eledialeitnerflow', $context);
    $quba->set_preferred_behaviour('immediatefeedback');

    foreach ($questionids as $qid) {
        $question = question_bank::load_question($qid);
        $quba->add_question($question, 1);
    }
    $quba->start_all_questions();
    question_engine::save_questions_usage_by_activity($quba);

    // Save session record.
    $session = new stdClass();
    $session->eledialeitnerflowid  = $leitnerflow->id;
    $session->userid         = $USER->id;
    $session->qubaid         = $quba->get_id();
    $session->questionids    = json_encode($questionids);
    $session->currentindex   = 0;
    $session->questionsasked = 0;
    $session->questionscorrect = 0;
    $session->status         = leitner_engine::SESSION_STATUS_ACTIVE;
    $session->timecreated    = time();
    $session->id = $DB->insert_record('eledialeitnerflow_sessions', $session);

    // Fire session_started event.
    $event = \mod_eledialeitnerflow\event\session_started::create([
        'objectid' => $session->id,
        'context'  => $context,
        'other'    => ['questioncount' => count($questionids)],
    ]);
    $event->trigger();

    redirect(new moodle_url('/mod/eledialeitnerflow/attempt.php', ['id' => $cmid, 'sessid' => $session->id]));
}

// Load existing session.
if (!$sessid) {
    redirect($viewurl);
}
$session = $DB->get_record('eledialeitnerflow_sessions', ['id' => $sessid, 'userid' => $USER->id], '*', MUST_EXIST);

if ((int)$session->status === leitner_engine::SESSION_STATUS_COMPLETED) {
    redirect($viewurl);
}

$questionids   = json_decode($session->questionids, true);
$totalquestions = count($questionids);
$currentindex  = (int)$session->currentindex;

// Session complete?
if ($currentindex >= $totalquestions) {
    _eledialeitnerflow_finish_session($session, $leitnerflow, $cm, $course);
    exit;
}

$quba = question_engine::load_questions_usage_by_activity($session->qubaid);
$slot = $currentindex + 1; // Quba slots are 1-based.

// Handle answer submission.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_sesskey();

    $transaction = $DB->start_delegated_transaction();

    $timenow = time();
    $quba->process_all_actions($timenow);
    question_engine::save_questions_usage_by_activity($quba);

    // Was the answer correct?
    $fraction = $quba->get_question_fraction($slot);
    $correct  = ($fraction !== null && $fraction >= 1.0);

    // Update Leitner state.
    $questionid = $questionids[$currentindex];
    $oldstate = leitner_engine::get_card_state($leitnerflow->id, $USER->id, $questionid);
    $oldbox = $oldstate ? (int) $oldstate->currentbox : 1;
    $state = leitner_engine::process_answer($oldstate, $correct, $leitnerflow, $questionid, $USER->id);
    leitner_engine::save_card_state($state);
    $newbox = (int) $state->currentbox;
    $islearned = ((int) $state->status === leitner_engine::STATUS_LEARNED);

    // Update session progress + streak tracking.
    $session->questionsasked++;
    $currentstreak = (int)($session->currentstreak ?? 0);
    $beststreak    = (int)($session->beststreak ?? 0);
    if ($correct) {
        $session->questionscorrect++;
        $currentstreak++;
        if ($currentstreak > $beststreak) {
            $beststreak = $currentstreak;
        }
    } else {
        $currentstreak = 0;
    }
    $session->currentstreak = $currentstreak;
    $session->beststreak    = $beststreak;
    $session->currentindex  = $currentindex + 1;

    $DB->update_record('eledialeitnerflow_sessions', $session);

    $transaction->allow_commit();

    if ($session->currentindex >= $totalquestions) {
        _eledialeitnerflow_finish_session($session, $leitnerflow, $cm, $course);
        exit;
    }

    // Determine feedback mode.
    $feedbackstyle = (int)($leitnerflow->feedbackstyle ?? 2);

    // Mode 0 (off): redirect immediately.
    if ($feedbackstyle === 0) {
        redirect(new moodle_url('/mod/eledialeitnerflow/attempt.php', ['id' => $cmid, 'sessid' => $sessid]));
    }

    // Modes 1-4: render current question with feedback overlay.
    $nexturl = new moodle_url('/mod/eledialeitnerflow/attempt.php', ['id' => $cmid, 'sessid' => $sessid]);

    // Count total learned cards (for gamified milestones).
    $totallearned = 0;
    if ($feedbackstyle === 4) {
        $totallearned = $DB->count_records('eledialeitnerflow_card_state', [
            'eledialeitnerflowid' => $leitnerflow->id,
            'userid' => $USER->id,
            'status' => leitner_engine::STATUS_LEARNED,
        ]);
    }

    _eledialeitnerflow_render_with_feedback(
        $leitnerflow,
        $cm,
        $course,
        $session,
        $quba,
        $slot,
        $currentindex,
        $totalquestions,
        $oldbox,
        $newbox,
        $correct,
        $islearned,
        $nexturl,
        $feedbackstyle,
        $currentstreak,
        $totallearned
    );
    exit;
}

// Render the question page.
$PAGE->set_url('/mod/eledialeitnerflow/attempt.php', ['id' => $cmid, 'sessid' => $sessid]);
$PAGE->set_title(format_string($leitnerflow->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);
$PAGE->add_body_class('mod-eledialeitnerflow-attempt');
// Hide activity description on attempt page — not needed during a session.
$PAGE->activityheader->set_description('');

// Display options for question rendering.
$displayoptions = new question_display_options();
$displayoptions->marks           = question_display_options::MAX_ONLY;
$displayoptions->feedback        = question_display_options::HIDDEN;
$displayoptions->generalfeedback = question_display_options::HIDDEN;
$displayoptions->rightanswer     = question_display_options::HIDDEN;
$displayoptions->history         = question_display_options::HIDDEN;
$displayoptions->correctness     = question_display_options::HIDDEN;

$questionid = $questionids[$currentindex];
$cardstate  = leitner_engine::get_card_state($leitnerflow->id, $USER->id, $questionid);
$currentbox = $cardstate ? (int)$cardstate->currentbox : 1;

echo $OUTPUT->header();

// Centered question container (like Moodle Quiz).
echo html_writer::start_div('eledialeitnerflow-attempt-container');

// Box-flow pills (centered, no "Learned" box).
$boxcount = (int) $leitnerflow->boxcount;
echo html_writer::start_div('text-center my-3');
echo html_writer::start_div('d-inline-flex align-items-center gap-2');
for ($b = 1; $b <= $boxcount; $b++) {
    $pillclass = 'badge rounded-pill px-3 py-2 ';
    if ($b === $currentbox) {
        $pillclass .= 'bg-primary fs-6';
    } else {
        $pillclass .= 'bg-light text-dark border';
    }
    echo html_writer::span(
        get_string('box_n', 'mod_eledialeitnerflow', $b),
        $pillclass,
        ['data-box' => $b]
    );
    if ($b < $boxcount) {
        echo html_writer::span('&#10140;', 'text-muted');
    }
}
echo html_writer::end_div();
echo html_writer::end_div();

// Question form.
$actionurl = new moodle_url('/mod/eledialeitnerflow/attempt.php', ['id' => $cmid, 'sessid' => $sessid]);
echo html_writer::start_tag('form', [
    'method'  => 'post',
    'action'  => $actionurl->out(false),
    'enctype' => 'multipart/form-data',
    'class'   => 'eledialeitnerflow-question-form',
    'id'      => 'eledialeitnerflow-question-form',
]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'attempt', 'value' => 1]);

// Render the question directly (Moodle's behaviour provides the Check button).
echo $quba->render_question($slot, $displayoptions, ($currentindex + 1) . '');

echo html_writer::end_tag('form');

// Bottom navigation (like Moodle Quiz: secondary left, info right).
$cancelurl = new moodle_url('/mod/eledialeitnerflow/view.php', [
    'id' => $cm->id,
    'cancelsession' => 1,
    'sesskey' => sesskey(),
]);
echo html_writer::start_div('d-flex justify-content-between align-items-center mt-3 mb-3');
echo html_writer::start_div('d-flex gap-2 align-items-center');
echo $OUTPUT->single_button($viewurl, get_string('backtooverview', 'mod_eledialeitnerflow'), 'get');
echo $OUTPUT->single_button($cancelurl, get_string('endsession', 'mod_eledialeitnerflow'), 'get');
echo html_writer::end_div();
echo html_writer::span(
    get_string('question') . ' ' . ($currentindex + 1) . ' / ' . $totalquestions
    . ' &middot; '
    . html_writer::tag('b', $session->questionscorrect) . ' / ' . $session->questionsasked
    . ' ' . get_string('correct', 'mod_eledialeitnerflow'),
    'text-muted'
);
echo html_writer::end_div();

echo html_writer::end_div(); // Eledialeitnerflow-attempt-container.

echo $OUTPUT->footer();

/**
 * Render current question with feedback overlay.
 *
 * @param stdClass $leitnerflow The leitnerflow module instance.
 * @param stdClass $cm The course module.
 * @param stdClass $course The course.
 * @param stdClass $session The session record.
 * @param question_usage_by_activity $quba The question usage by activity.
 * @param int $slot The question slot.
 * @param int $answeredindex The index of answered question.
 * @param int $totalquestions Total questions in session.
 * @param int $frombox The box moved from.
 * @param int $tobox The box moved to.
 * @param bool $correct Whether answer is correct.
 * @param bool $islearned Whether card is now learned.
 * @param moodle_url $nexturl URL for next question.
 * @param int $feedbackstyle Feedback style mode.
 * @param int $currentstreak Current streak count.
 * @param int $totallearned Total learned cards.
 * @return void
 */
function _eledialeitnerflow_render_with_feedback(
    stdClass $leitnerflow,
    stdClass $cm,
    stdClass $course,
    stdClass $session,
    question_usage_by_activity $quba,
    int $slot,
    int $answeredindex,
    int $totalquestions,
    int $frombox,
    int $tobox,
    bool $correct,
    bool $islearned,
    moodle_url $nexturl,
    int $feedbackstyle,
    int $currentstreak = 0,
    int $totallearned = 0
): void {
    global $OUTPUT, $PAGE;

    $cmid = $cm->id;
    $PAGE->set_url('/mod/eledialeitnerflow/attempt.php', ['id' => $cmid, 'sessid' => $session->id]);
    $PAGE->set_title(format_string($leitnerflow->name));
    $PAGE->set_heading(format_string($course->fullname));
    $PAGE->set_context(\core\context\module::instance($cmid));
    $PAGE->add_body_class('mod-eledialeitnerflow-attempt');
    $PAGE->activityheader->set_description('');

    // Show correct answer for detailed + gamified modes.
    $showdetail = ($feedbackstyle >= 3);
    $displayoptions = new question_display_options();
    $displayoptions->marks           = question_display_options::MAX_ONLY;
    $displayoptions->feedback        = $showdetail ? question_display_options::VISIBLE : question_display_options::HIDDEN;
    $displayoptions->generalfeedback = question_display_options::HIDDEN;
    $displayoptions->rightanswer     = $showdetail ? question_display_options::VISIBLE : question_display_options::HIDDEN;
    $displayoptions->history         = question_display_options::HIDDEN;
    $displayoptions->correctness     = question_display_options::VISIBLE;
    $displayoptions->readonly        = true;

    echo $OUTPUT->header();
    echo html_writer::start_div('eledialeitnerflow-attempt-container');

    // Feedback banner above the box pills.
    $alertclass = $correct ? 'alert alert-success' : 'alert alert-warning';

    if ($feedbackstyle === 1) {
        // MINIMAL: Short factual text.
        $msg = _eledialeitnerflow_get_feedback_text(1, $correct, $islearned, $frombox, $tobox);
        echo html_writer::div(
            $msg,
            $alertclass . ' text-center lf-feedback-banner mb-2 py-2',
            ['style' => 'font-size: 0.9rem;']
        );
    } else if ($feedbackstyle === 2) {
        // ANIMATED: Encouraging random text.
        $msg = _eledialeitnerflow_get_feedback_text(2, $correct, $islearned, $frombox, $tobox);
        echo html_writer::div(
            $msg,
            $alertclass . ' text-center lf-feedback-banner mb-2 py-2',
            ['style' => 'font-size: 0.9rem;']
        );
    } else if ($feedbackstyle === 3) {
        // DETAILED: Full feedback block with box change info.
        $msg = _eledialeitnerflow_get_detailed_text($correct, $islearned, $frombox, $tobox);
        echo html_writer::div(
            $msg,
            $alertclass . ' text-center mb-2 py-2',
            ['style' => 'font-size: 1rem;']
        );
    } else if ($feedbackstyle === 4) {
        // GAMIFIED: Points + streak + milestones.
        _eledialeitnerflow_render_gamified_feedback(
            $correct,
            $islearned,
            $frombox,
            $tobox,
            $currentstreak,
            $totallearned
        );
    }

    // Box-flow pills (highlight target box).
    $boxcount = (int) $leitnerflow->boxcount;
    echo html_writer::start_div('text-center my-3');
    echo html_writer::start_div('d-inline-flex align-items-center gap-2');
    for ($b = 1; $b <= $boxcount; $b++) {
        $pillclass = 'badge rounded-pill px-3 py-2 ';
        $pillclass .= ($b === $tobox) ? 'bg-primary fs-6' : 'bg-light text-dark border';
        echo html_writer::span(
            get_string('box_n', 'mod_eledialeitnerflow', $b),
            $pillclass,
            ['data-box' => $b]
        );
        if ($b < $boxcount) {
            echo html_writer::span('&#10140;', 'text-muted');
        }
    }
    echo html_writer::end_div();
    echo html_writer::end_div();

    // Render the answered question (readonly).
    echo $quba->render_question($slot, $displayoptions, ($answeredindex + 1) . '');

    // Bottom area.
    if ($feedbackstyle === 3) {
        // DETAILED: "Next question" button instead of auto-redirect.
        echo html_writer::start_div('text-center mt-3 mb-3');
        echo html_writer::link(
            $nexturl->out(false),
            get_string('nextquestionbtn', 'mod_eledialeitnerflow'),
            ['class' => 'btn btn-primary']
        );
        echo html_writer::end_div();
    }

    // Question counter.
    echo html_writer::start_div('d-flex justify-content-between align-items-center mt-3 mb-3');
    echo html_writer::tag('span', '');
    echo html_writer::span(
        get_string('question') . ' ' . ($answeredindex + 1) . ' / ' . $totalquestions
        . ' &middot; '
        . html_writer::tag('b', $session->questionscorrect) . ' / ' . $session->questionsasked
        . ' ' . get_string('correct', 'mod_eledialeitnerflow'),
        'text-muted'
    );
    echo html_writer::end_div();

    echo html_writer::end_div(); // Eledialeitnerflow-attempt-container.

    // JavaScript: Mode-specific behaviour.
    // Detailed mode: no auto-redirect (button handles it).
    // All other modes: auto-redirect after delay.
    $autoredirect = ($feedbackstyle !== 3);
    $delay = (int) ($leitnerflow->animationdelay ?? 1000);

    $PAGE->requires->js_call_amd('mod_eledialeitnerflow/card_transition', 'init', [
        $frombox, $tobox, (int)$correct, (int)$islearned,
        $autoredirect ? $nexturl->out(false) : '',
        $feedbackstyle, $delay,
    ]);

    echo $OUTPUT->footer();
}

/**
 * Get feedback text based on style setting.
 *
 * @param int $style The feedback style mode.
 * @param bool $correct Whether answer is correct.
 * @param bool $islearned Whether card is now learned.
 * @param int $frombox The box moved from.
 * @param int $tobox The box moved to.
 * @return string The feedback text.
 */
function _eledialeitnerflow_get_feedback_text(int $style, bool $correct, bool $islearned, int $frombox, int $tobox): string {
    // Style 1 = minimal (factual).
    if ($style === 1) {
        if ($correct) {
            return $islearned
                ? get_string('cardlearned', 'mod_eledialeitnerflow')
                : get_string('movedtobox', 'mod_eledialeitnerflow', $tobox);
        }
        return ($frombox !== $tobox)
            ? get_string('cardbackone', 'mod_eledialeitnerflow')
            : get_string('incorrect', 'mod_eledialeitnerflow');
    }

    // Style 2 = encouraging (random motivational messages).
    if ($correct && $islearned) {
        $pool = ['encourage_learned_1', 'encourage_learned_2', 'encourage_learned_3'];
        $key = $pool[array_rand($pool)];
        return get_string($key, 'mod_eledialeitnerflow');
    }
    if ($correct) {
        $pool = ['encourage_correct_1', 'encourage_correct_2', 'encourage_correct_3',
                 'encourage_correct_4', 'encourage_correct_5'];
        $key = $pool[array_rand($pool)];
        return get_string($key, 'mod_eledialeitnerflow', $tobox);
    }
    if ($frombox !== $tobox) {
        $pool = ['encourage_wrong_back_1', 'encourage_wrong_back_2',
                 'encourage_wrong_back_3', 'encourage_wrong_back_4'];
        $key = $pool[array_rand($pool)];
        return get_string($key, 'mod_eledialeitnerflow', $tobox);
    }
    $pool = ['encourage_wrong_stay_1', 'encourage_wrong_stay_2'];
    $key = $pool[array_rand($pool)];
    return get_string($key, 'mod_eledialeitnerflow', $tobox);
}

/**
 * Get detailed feedback text (mode 3).
 *
 * @param bool $correct Whether answer is correct.
 * @param bool $islearned Whether card is now learned.
 * @param int $frombox The box moved from.
 * @param int $tobox The box moved to.
 * @return string The detailed feedback text.
 */
function _eledialeitnerflow_get_detailed_text(bool $correct, bool $islearned, int $frombox, int $tobox): string {
    if ($correct && $islearned) {
        return get_string('detailed_learned', 'mod_eledialeitnerflow');
    }
    if ($correct && $frombox !== $tobox) {
        return get_string('detailed_correct', 'mod_eledialeitnerflow', (object)['from' => $frombox, 'to' => $tobox]);
    }
    if ($correct) {
        return get_string('detailed_correct_stay', 'mod_eledialeitnerflow', $tobox);
    }
    if ($frombox !== $tobox) {
        return get_string('detailed_wrong_back', 'mod_eledialeitnerflow', (object)['from' => $frombox, 'to' => $tobox]);
    }
    return get_string('detailed_wrong_stay', 'mod_eledialeitnerflow', $tobox);
}

/**
 * Render gamified feedback (mode 4).
 *
 * @param bool $correct Whether answer is correct.
 * @param bool $islearned Whether card is now learned.
 * @param int $frombox The box moved from.
 * @param int $tobox The box moved to.
 * @param int $currentstreak Current streak count.
 * @param int $totallearned Total learned cards.
 * @return void
 */
function _eledialeitnerflow_render_gamified_feedback(
    bool $correct,
    bool $islearned,
    int $frombox,
    int $tobox,
    int $currentstreak,
    int $totallearned
): void {
    // Points: Correct = 10, correct + advance = 15, learned = 25.
    $points = 0;
    if ($correct) {
        $points = 10;
        if ($frombox !== $tobox) {
            $points = 15;
        }
        if ($islearned) {
            $points = 25;
        }
    }

    echo html_writer::start_div('lf-gamified-feedback text-center mb-2');

    // Points display (animated via CSS).
    if ($points > 0) {
        echo html_writer::div(
            '+' . get_string('points', 'mod_eledialeitnerflow', $points),
            'lf-points-float ' . ($correct ? 'text-success' : 'text-warning')
        );
    }

    // Streak counter.
    if ($correct && $currentstreak >= 2) {
        echo html_writer::div(
            get_string('streakcounter', 'mod_eledialeitnerflow', $currentstreak),
            'lf-streak-badge badge bg-warning text-dark px-3 py-2 fs-6'
        );
    } else if (!$correct && $currentstreak === 0) {
        // Streak was just broken (it was reset to 0 in POST handler).
        echo html_writer::div(
            get_string('streakbroken', 'mod_eledialeitnerflow'),
            'lf-streak-broken text-muted small'
        );
    }

    // Milestone celebrations.
    $milestone = '';
    if ($currentstreak === 3) {
        $milestone = get_string('milestone_streak3', 'mod_eledialeitnerflow');
    } else if ($currentstreak === 5) {
        $milestone = get_string('milestone_streak5', 'mod_eledialeitnerflow');
    } else if ($currentstreak === 10) {
        $milestone = get_string('milestone_streak10', 'mod_eledialeitnerflow');
    }
    if ($islearned && $totallearned === 5) {
        $milestone = get_string('milestone_5learned', 'mod_eledialeitnerflow');
    } else if ($islearned && $totallearned === 10) {
        $milestone = get_string('milestone_10learned', 'mod_eledialeitnerflow');
    }

    if ($milestone) {
        echo html_writer::div($milestone, 'lf-milestone alert alert-info text-center py-2 mt-1');
    }

    // Regular feedback text (encouraging style).
    $msg = _eledialeitnerflow_get_feedback_text(2, $correct, $islearned, $frombox, $tobox);
    $alertclass = $correct ? 'alert alert-success' : 'alert alert-warning';
    echo html_writer::div(
        $msg,
        $alertclass . ' text-center py-2 mt-1',
        ['style' => 'font-size: 0.9rem;']
    );

    echo html_writer::end_div(); // Lf-gamified-feedback.
}

/**
 * Finish session.
 *
 * @param stdClass $session The session record.
 * @param stdClass $leitnerflow The leitnerflow module instance.
 * @param stdClass $cm The course module.
 * @param stdClass $course The course.
 * @return void
 */
function _eledialeitnerflow_finish_session(stdClass $session, stdClass $leitnerflow, stdClass $cm, stdClass $course): void {
    global $DB, $OUTPUT, $PAGE, $USER;

    $session->status        = leitner_engine::SESSION_STATUS_COMPLETED;
    $session->timecompleted = time();
    $DB->update_record('eledialeitnerflow_sessions', $session);

    // Clean up question usage to keep the database lean.
    if (!empty($session->qubaid)) {
        question_engine::delete_questions_usage_by_activity($session->qubaid);
        $session->qubaid = null;
        $DB->set_field('eledialeitnerflow_sessions', 'qubaid', null, ['id' => $session->id]);
    }

    // Fire session_completed event.
    $event = \mod_eledialeitnerflow\event\session_completed::create([
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
        eledialeitnerflow_update_grades($leitnerflow, $USER->id);
    }

    $viewurl = new moodle_url('/mod/eledialeitnerflow/view.php', ['id' => $cm->id]);

    $PAGE->set_url('/mod/eledialeitnerflow/attempt.php', ['id' => $cm->id]);
    $PAGE->set_title(format_string($leitnerflow->name));
    $PAGE->set_context(\core\context\module::instance($cm->id));
    // Hide activity description on session-complete page.
    $PAGE->activityheader->set_description('');
    echo $OUTPUT->header();

    $result = get_string('sessionresult', 'mod_eledialeitnerflow', [
        'correct' => $session->questionscorrect,
        'total'   => $session->questionsasked,
    ]);
    echo $OUTPUT->notification($result, 'success');

    echo $OUTPUT->single_button($viewurl, get_string('continue'), 'get');
    echo $OUTPUT->footer();
}
