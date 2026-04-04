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

use mod_leitnerflow\engine\leitner_engine;

$id = required_param('id', PARAM_INT); // course module id

$cm         = get_coursemodule_from_id('leitnerflow', $id, 0, false, MUST_EXIST);
$course     = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$leitnerflow = $DB->get_record('leitnerflow', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, true, $cm);
$context = \core\context\module::instance($cm->id);

// Completion tracking
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

// ---- Determine role --------------------------------------------------------
$isteacher = has_capability('mod/leitnerflow:viewreport', $context);
$canAttempt = has_capability('mod/leitnerflow:attempt', $context);

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($leitnerflow->name));

// Show intro
if ($leitnerflow->intro) {
    echo $OUTPUT->box(format_module_intro('leitnerflow', $leitnerflow, $cm->id), 'generalbox');
}

// ---- Student view ----------------------------------------------------------
if ($canAttempt) {
    $stats = leitner_engine::get_user_stats($leitnerflow->id, $USER->id, $leitnerflow->questioncategoryid);

    // Check for active session
    $activesession = $DB->get_record('leitnerflow_sessions', [
        'leitnerflowid' => $leitnerflow->id,
        'userid'        => $USER->id,
        'status'        => 0,
    ]);

    // ---- Dashboard card ----
    echo html_writer::start_div('leitnerflow-dashboard card mb-4');
    echo html_writer::start_div('card-body');
    echo html_writer::tag('h5', get_string('yourprogress', 'mod_leitnerflow'), ['class' => 'card-title']);

    // Stats tiles
    echo html_writer::start_div('leitnerflow-stats');
    $statitems = [
        ['label' => get_string('totalcards', 'mod_leitnerflow'), 'value' => $stats->total,   'css' => 'stat-total'],
        ['label' => get_string('learned',    'mod_leitnerflow'), 'value' => $stats->learned,  'css' => 'stat-learned'],
        ['label' => get_string('open',       'mod_leitnerflow'), 'value' => $stats->open,     'css' => 'stat-open'],
        ['label' => get_string('witherrors', 'mod_leitnerflow'), 'value' => $stats->errors,   'css' => 'stat-errors'],
    ];
    foreach ($statitems as $item) {
        echo html_writer::start_div('leitnerflow-stat-item ' . $item['css']);
        echo html_writer::tag('div', $item['value'], ['class' => 'stat-value']);
        echo html_writer::tag('div', $item['label'], ['class' => 'stat-label']);
        echo html_writer::end_div();
    }
    echo html_writer::end_div();

    // Multi-segment progress bar
    if ($stats->total > 0) {
        $learnedpct = round($stats->learned / $stats->total * 100, 1);
        $openpct    = round($stats->open    / $stats->total * 100, 1);
        $errorpct   = round($stats->errors  / $stats->total * 100, 1);

        echo html_writer::start_div('leitnerflow-progressbar');
        echo html_writer::div('', 'segment segment-learned',
            ['style' => "width:{$learnedpct}%",
             'title' => get_string('learned', 'mod_leitnerflow') . ": {$learnedpct}%"]);
        echo html_writer::div('', 'segment segment-open',
            ['style' => "width:{$openpct}%",
             'title' => get_string('open', 'mod_leitnerflow') . ": {$openpct}%"]);
        echo html_writer::div('', 'segment segment-errors',
            ['style' => "width:{$errorpct}%",
             'title' => get_string('witherrors', 'mod_leitnerflow') . ": {$errorpct}%"]);
        echo html_writer::end_div();
    }

    // ---- Leitner box visualization ----
    $boxdist = leitner_engine::get_box_distribution(
        $leitnerflow->id, $USER->id, $leitnerflow->questioncategoryid, $leitnerflow->boxcount
    );
    $boxcount = (int) $leitnerflow->boxcount;

    echo html_writer::tag('div', get_string('leitnersettings', 'mod_leitnerflow'),
        ['class' => 'leitnerflow-boxes-title']);

    echo html_writer::start_div('leitnerflow-boxes');

    for ($b = 1; $b <= $boxcount; $b++) {
        $count = $boxdist[$b] ?? 0;
        // Box card
        echo html_writer::start_div("leitnerflow-box leitnerflow-box-{$b}");
        echo html_writer::start_div('leitnerflow-box-visual');
        echo html_writer::tag('div', $count, ['class' => 'leitnerflow-box-count']);
        echo html_writer::end_div();
        $boxlabel = ($b === 1)
            ? get_string('box_1', 'mod_leitnerflow')
            : get_string('box_n', 'mod_leitnerflow', $b);
        echo html_writer::tag('div', $boxlabel, ['class' => 'leitnerflow-box-label']);
        echo html_writer::end_div();

        // Arrow between boxes (not after last)
        if ($b < $boxcount) {
            echo html_writer::tag('div', '&#10140;', ['class' => 'leitnerflow-box-arrow']);
        }
    }

    // Arrow before learned box
    echo html_writer::tag('div', '&#10140;', ['class' => 'leitnerflow-box-arrow']);

    // Learned box
    echo html_writer::start_div('leitnerflow-box leitnerflow-box-learned');
    echo html_writer::start_div('leitnerflow-box-visual');
    echo html_writer::tag('div', '&#10003;', ['class' => 'leitnerflow-box-icon']);
    echo html_writer::tag('div', $stats->learned, ['class' => 'leitnerflow-box-count']);
    echo html_writer::end_div();
    echo html_writer::tag('div', get_string('box_learned', 'mod_leitnerflow'),
        ['class' => 'leitnerflow-box-label']);
    echo html_writer::end_div();

    echo html_writer::end_div(); // leitnerflow-boxes

    echo html_writer::end_div(); // card-body
    echo html_writer::end_div(); // card

    // ---- Action area ----
    echo html_writer::start_div('leitnerflow-actions');
    if ($stats->total === 0) {
        echo $OUTPUT->notification(get_string('nocardsinpool', 'mod_leitnerflow'), 'warning');
    } elseif ($stats->learned >= $stats->total) {
        echo html_writer::start_div('leitnerflow-alllearned');
        echo html_writer::tag('div', '&#127881;', ['class' => 'celebration-icon']);
        echo html_writer::tag('div', get_string('alllearned', 'mod_leitnerflow'),
            ['class' => 'celebration-text']);
        echo html_writer::end_div();
    } elseif ($activesession) {
        $continueurl = new moodle_url('/mod/leitnerflow/attempt.php', [
            'id'     => $cm->id,
            'sessid' => $activesession->id,
        ]);
        echo html_writer::div(
            $OUTPUT->single_button($continueurl, get_string('continuesession', 'mod_leitnerflow'), 'get',
                ['class' => 'btn-primary']),
            'mb-3'
        );
    } else {
        $starturl = new moodle_url('/mod/leitnerflow/attempt.php', ['id' => $cm->id, 'start' => 1]);
        echo html_writer::div(
            $OUTPUT->single_button($starturl, get_string('startsession', 'mod_leitnerflow'), 'get',
                ['class' => 'btn-primary']),
            'mb-3'
        );
    }
    echo html_writer::end_div(); // leitnerflow-actions
}

// ---- Teacher view ----------------------------------------------------------
if ($isteacher) {
    $reporturl = new moodle_url('/mod/leitnerflow/report.php', ['id' => $cm->id]);
    echo html_writer::div(
        $OUTPUT->single_button($reporturl, get_string('viewreport', 'mod_leitnerflow'), 'get'),
        'mt-2'
    );
}

echo $OUTPUT->footer();
