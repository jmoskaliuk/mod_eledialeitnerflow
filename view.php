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
 * Main view page for mod_leitnerflow activity.
 *
 * @package    mod_leitnerflow
 * @copyright  2024 eLeDia GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');
require_once($CFG->dirroot . '/question/engine/lib.php');

use mod_leitnerflow\engine\leitner_engine;

$id = required_param('id', PARAM_INT); // course module id

$cm         = get_coursemodule_from_id('leitnerflow', $id, 0, false, MUST_EXIST);
$course     = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$leitnerflow = $DB->get_record('leitnerflow', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, true, $cm);
$context = \core\context\module::instance($cm->id);

// Completion tracking.
$completion = new completion_info($course);
$completion->set_module_viewed($cm);

$PAGE->set_url('/mod/leitnerflow/view.php', ['id' => $cm->id]);
$PAGE->set_title(format_string($leitnerflow->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);
$PAGE->add_body_class('mod-leitnerflow');

// ---- Handle reset action (teacher only) ------------------------------------
if (optional_param('resetuserid', 0, PARAM_INT) > 0) {
    require_capability('mod/leitnerflow:resetprogress', $context);
    require_sesskey();
    $resetuid = required_param('resetuserid', PARAM_INT);
    leitner_engine::delete_user_data($leitnerflow->id, $resetuid);
    redirect(new moodle_url('/mod/leitnerflow/view.php', ['id' => $cm->id]),
        get_string('progressreset', 'mod_leitnerflow'), null, \core\output\notification::NOTIFY_SUCCESS);
}

// ---- Handle cancel session action ------------------------------------------
if (optional_param('cancelsession', 0, PARAM_INT)) {
    require_sesskey();
    $stale = $DB->get_records('leitnerflow_sessions', [
        'leitnerflowid' => $leitnerflow->id,
        'userid'        => $USER->id,
        'status'        => 0,
    ]);
    foreach ($stale as $s) {
        if (!empty($s->qubaid)) {
            question_engine::delete_questions_usage_by_activity($s->qubaid);
        }
    }
    $DB->delete_records('leitnerflow_sessions', [
        'leitnerflowid' => $leitnerflow->id,
        'userid'        => $USER->id,
        'status'        => 0,
    ]);
    redirect(new moodle_url('/mod/leitnerflow/view.php', ['id' => $cm->id]),
        get_string('sessioncancelled', 'mod_leitnerflow'), null, \core\output\notification::NOTIFY_INFO);
}

// ---- Determine role --------------------------------------------------------
$isteacher  = has_capability('mod/leitnerflow:viewreport', $context);
$canattempt = has_capability('mod/leitnerflow:attempt', $context);

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($leitnerflow->name));

// Show intro.
if ($leitnerflow->intro) {
    echo $OUTPUT->box(format_module_intro('leitnerflow', $leitnerflow, $cm->id), 'generalbox');
}

// ---- Student view ----------------------------------------------------------
if ($canattempt) {
    $stats = leitner_engine::get_user_stats($leitnerflow->id, $USER->id, $leitnerflow->questioncategoryid);

    // Check for active session.
    $activesession = $DB->get_record('leitnerflow_sessions', [
        'leitnerflowid' => $leitnerflow->id,
        'userid'        => $USER->id,
        'status'        => 0,
    ]);

    // ---- Dashboard card ----
    echo html_writer::start_div('leitnerflow-dashboard card mb-4');
    echo html_writer::start_div('card-body');
    echo html_writer::tag('h5', get_string('yourprogress', 'mod_leitnerflow'), ['class' => 'card-title']);

    // ---- Leitner box visualization ----
    $boxdist = leitner_engine::get_box_distribution(
        $leitnerflow->id, $USER->id, $leitnerflow->questioncategoryid, $leitnerflow->boxcount
    );
    $boxcount = (int) $leitnerflow->boxcount;

    echo html_writer::start_div('leitnerflow-boxes', [
        'role' => 'group',
        'aria-label' => get_string('boxdistribution', 'mod_leitnerflow'),
    ]);

    for ($b = 1; $b <= $boxcount; $b++) {
        $count = $boxdist[$b] ?? 0;
        $boxlabel = get_string('box_n', 'mod_leitnerflow', $b);

        echo html_writer::start_div("leitnerflow-box leitnerflow-box-{$b}", [
            'role' => 'status',
            'aria-label' => $boxlabel . ': ' . $count,
        ]);
        echo html_writer::start_div('leitnerflow-box-visual');
        echo html_writer::tag('div', $count, ['class' => 'leitnerflow-box-count']);
        echo html_writer::tag('div', $boxlabel, ['class' => 'leitnerflow-box-label']);
        echo html_writer::end_div();
        echo html_writer::end_div();

        // Arrow between boxes.
        if ($b < $boxcount) {
            echo html_writer::tag('div', '&#10140;', [
                'class' => 'leitnerflow-box-arrow',
                'aria-hidden' => 'true',
            ]);
        }
    }

    // Arrow before learned box.
    echo html_writer::tag('div', '&#10140;', [
        'class' => 'leitnerflow-box-arrow',
        'aria-hidden' => 'true',
    ]);

    // Learned box.
    $learnedlabel = get_string('learned', 'mod_leitnerflow');
    echo html_writer::start_div('leitnerflow-box leitnerflow-box-learned', [
        'role' => 'status',
        'aria-label' => $learnedlabel . ': ' . $stats->learned,
    ]);
    $learnedattr = [];
    if ($stats->total > 0 && $stats->learned >= $stats->total) {
        $learnedattr = ['style' => 'transform: scale(1.08); box-shadow: 0 4px 16px rgba(102,153,51,0.3);'];
    }
    echo html_writer::start_div('leitnerflow-box-visual', $learnedattr);
    echo html_writer::tag('div', $stats->learned, ['class' => 'leitnerflow-box-count']);
    echo html_writer::tag('div', $learnedlabel . ' &#10003;', ['class' => 'leitnerflow-box-label']);
    echo html_writer::end_div();
    echo html_writer::end_div();

    echo html_writer::end_div(); // leitnerflow-boxes

    // ---- Per-box progress bar ----
    if ($stats->total > 0) {
        $pctlearned = round($stats->learned / $stats->total * 100, 1);

        echo html_writer::start_div('leitnerflow-progressbar', [
            'role' => 'progressbar',
            'aria-valuenow' => round($pctlearned),
            'aria-valuemin' => '0',
            'aria-valuemax' => '100',
            'aria-label' => round($pctlearned) . '% ' . get_string('learned', 'mod_leitnerflow'),
        ]);

        for ($b = 1; $b <= $boxcount; $b++) {
            $cnt = $boxdist[$b] ?? 0;
            if ($cnt > 0) {
                $pct = round($cnt / $stats->total * 100, 1);
                echo html_writer::div('', "segment seg-box{$b}", ['style' => "width:{$pct}%"]);
            }
        }
        if ($stats->learned > 0) {
            $pct = round($stats->learned / $stats->total * 100, 1);
            echo html_writer::div('', 'segment seg-learned', ['style' => "width:{$pct}%"]);
        }
        echo html_writer::end_div();

        // Labels below progress bar.
        $opencount = $stats->total - $stats->learned;
        echo html_writer::start_div('leitnerflow-progress-label');
        echo html_writer::span($opencount . ' ' . get_string('open', 'mod_leitnerflow'));
        echo html_writer::span(
            $stats->learned . ' / ' . $stats->total . ' '
            . get_string('learned', 'mod_leitnerflow')
            . ' (' . round($pctlearned) . '%)'
        );
        echo html_writer::end_div();
    }

    echo html_writer::end_div(); // card-body
    echo html_writer::end_div(); // card

    // ---- Session action area ----
    echo html_writer::start_div('leitnerflow-actions');

    if ($stats->total === 0) {
        // No questions configured.
        echo $OUTPUT->notification(get_string('nocardsinpool', 'mod_leitnerflow'), 'warning');

    } else if ($stats->learned >= $stats->total) {
        // All learned — celebration.
        echo html_writer::start_div('leitnerflow-alllearned', ['role' => 'status']);
        echo html_writer::tag('div', '&#127881;', ['class' => 'celebration-icon', 'aria-hidden' => 'true']);
        echo html_writer::tag('div', get_string('alllearned', 'mod_leitnerflow'), ['class' => 'celebration-text']);
        echo html_writer::end_div();

        echo html_writer::start_div('leitnerflow-session-buttons');
        $reseturl = new moodle_url('/mod/leitnerflow/view.php', [
            'id' => $cm->id,
            'resetuserid' => $USER->id,
            'sesskey' => sesskey(),
        ]);
        echo html_writer::link($reseturl,
            '&#10227; ' . get_string('resetandrestart', 'mod_leitnerflow'),
            ['class' => 'btn leitnerflow-btn-new']);
        echo html_writer::end_div();

    } else if ($activesession) {
        // Active session — show session banner + 3 buttons.
        $answered = (int) $activesession->questionsasked;
        $totalq   = count(json_decode($activesession->questionids, true));
        $correctq = (int) $activesession->questionscorrect;

        echo html_writer::start_div('leitnerflow-session-banner', ['role' => 'status']);
        echo html_writer::span('', 'pulse-dot', ['aria-hidden' => 'true']);
        echo get_string('activesessioninfo', 'mod_leitnerflow', (object) [
            'answered' => $answered,
            'total'    => $totalq,
            'correct'  => $correctq,
        ]);
        echo html_writer::end_div();

        echo html_writer::start_div('leitnerflow-session-buttons');

        // Continue session (primary).
        $continueurl = new moodle_url('/mod/leitnerflow/attempt.php', [
            'id'     => $cm->id,
            'sessid' => $activesession->id,
        ]);
        echo html_writer::link($continueurl,
            '&#9654; ' . get_string('continuesession', 'mod_leitnerflow'),
            ['class' => 'btn leitnerflow-btn-start']);

        // New session.
        $newurl = new moodle_url('/mod/leitnerflow/attempt.php', ['id' => $cm->id, 'start' => 1]);
        echo html_writer::link($newurl,
            '&#10227; ' . get_string('newsession', 'mod_leitnerflow'),
            ['class' => 'btn leitnerflow-btn-new']);

        // Cancel session.
        $cancelurl = new moodle_url('/mod/leitnerflow/view.php', [
            'id' => $cm->id,
            'cancelsession' => 1,
            'sesskey' => sesskey(),
        ]);
        echo html_writer::link($cancelurl,
            '&#10005; ' . get_string('cancel'),
            ['class' => 'btn leitnerflow-btn-cancel']);

        echo html_writer::end_div();

    } else {
        // No active session — start button.
        echo html_writer::start_div('leitnerflow-session-buttons');
        $starturl = new moodle_url('/mod/leitnerflow/attempt.php', ['id' => $cm->id, 'start' => 1]);
        echo html_writer::link($starturl,
            '&#9654; ' . get_string('startsession', 'mod_leitnerflow'),
            ['class' => 'btn leitnerflow-btn-start']);
        echo html_writer::end_div();
    }

    echo html_writer::end_div(); // leitnerflow-actions
}

// ---- Teacher view ----------------------------------------------------------
if ($isteacher) {
    echo html_writer::start_div('leitnerflow-teacher-actions');
    $reporturl = new moodle_url('/mod/leitnerflow/report.php', ['id' => $cm->id]);
    echo html_writer::div(
        $OUTPUT->single_button($reporturl, get_string('viewreport', 'mod_leitnerflow'), 'get'),
        'mt-2'
    );
    echo html_writer::end_div();
}

echo $OUTPUT->footer();
