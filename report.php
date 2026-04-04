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
 * Teacher report page for mod_leitnerflow.
 *
 * Overview of all enrolled students' Leitner progress.
 *
 * @package    mod_leitnerflow
 * @copyright  2024 eLeDia GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

use mod_leitnerflow\engine\leitner_engine;

$cmid      = required_param('id', PARAM_INT);
$resetuid  = optional_param('resetuserid', 0, PARAM_INT);

$cm          = get_coursemodule_from_id('leitnerflow', $cmid, 0, false, MUST_EXIST);
$course      = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$leitnerflow = $DB->get_record('leitnerflow', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, true, $cm);
$context = \core\context\module::instance($cm->id);
require_capability('mod/leitnerflow:viewreport', $context);

// ---- Handle reset ----------------------------------------------------------
if ($resetuid > 0) {
    require_sesskey();
    require_capability('mod/leitnerflow:resetprogress', $context);
    leitner_engine::delete_user_data($leitnerflow->id, $resetuid);

    // Fire progress_reset event.
    $event = \mod_leitnerflow\event\progress_reset::create([
        'objectid'      => $leitnerflow->id,
        'context'       => $context,
        'relateduserid' => $resetuid,
    ]);
    $event->trigger();

    redirect(
        new moodle_url('/mod/leitnerflow/report.php', ['id' => $cmid]),
        get_string('progressreset', 'mod_leitnerflow'),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

// ---- Page setup ------------------------------------------------------------
$PAGE->set_url('/mod/leitnerflow/report.php', ['id' => $cmid]);
$PAGE->set_title(format_string($leitnerflow->name) . ': ' . get_string('report', 'mod_leitnerflow'));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

$viewurl = new moodle_url('/mod/leitnerflow/view.php', ['id' => $cmid]);

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($leitnerflow->name));
echo $OUTPUT->heading(get_string('report', 'mod_leitnerflow'), 4);

// ---- Load student data -----------------------------------------------------
$students = leitner_engine::get_all_students_stats($leitnerflow, $course->id, $context);

if (empty($students)) {
    echo $OUTPUT->notification(get_string('nostudents', 'mod_leitnerflow'), 'info');
    echo $OUTPUT->single_button($viewurl, get_string('back'), 'get');
    echo $OUTPUT->footer();
    exit;
}

// ---- Summary cards (course-wide) -------------------------------------------
$totallearned = 0;
$totalcards   = 0;
foreach ($students as $s) {
    $totallearned += $s->stats->learned;
    $totalcards   += $s->stats->total;
}
$avgpct = count($students) > 0
    ? round(array_sum(array_map(fn($s) => $s->stats->percent_learned, $students)) / count($students))
    : 0;

// Total questions in pool (same for all students).
$questioncount = !empty($students) ? reset($students)->stats->total : 0;

echo html_writer::start_div('row mb-4');
$summaries = [
    [get_string('participants'), count($students), 'bg-primary'],
    [get_string('questionsinpool', 'mod_leitnerflow'), $questioncount, 'bg-secondary'],
    [get_string('avglearnedpercent', 'mod_leitnerflow'), $avgpct . ' %', 'bg-success'],
];
foreach ($summaries as $sum) {
    echo html_writer::start_div('col-md-4 mb-2');
    echo html_writer::start_div('card text-white ' . $sum[2]);
    echo html_writer::start_div('card-body text-center py-2');
    echo html_writer::tag('div', $sum[1], ['class' => 'h3 mb-0']);
    echo html_writer::tag('small', $sum[0]);
    echo html_writer::end_div();
    echo html_writer::end_div();
    echo html_writer::end_div();
}
echo html_writer::end_div();

// ---- Student table ---------------------------------------------------------
echo html_writer::start_tag('table', ['class' => 'table table-striped table-hover generaltable']);
echo html_writer::start_tag('thead');
echo html_writer::start_tag('tr');
$headers = [
    get_string('participants'),
    get_string('learned',     'mod_leitnerflow'),
    get_string('open',        'mod_leitnerflow'),
    get_string('witherrors',  'mod_leitnerflow'),
    get_string('progress'),
    get_string('sessionhistory', 'mod_leitnerflow'),
    get_string('lastsession', 'mod_leitnerflow'),
    '',
];
foreach ($headers as $h) {
    echo html_writer::tag('th', $h);
}
echo html_writer::end_tag('tr');
echo html_writer::end_tag('thead');
echo html_writer::start_tag('tbody');

foreach ($students as $student) {
    $stats = $student->stats;
    $pct   = $stats->percent_learned;

    $progressbar = html_writer::start_div('progress', ['style' => 'height: 14px; min-width: 80px;']);
    $progressbar .= html_writer::div('', 'progress-bar bg-success',
        ['style' => "width:{$pct}%", 'title' => "{$pct}%"]);
    $progressbar .= html_writer::end_div();

    $lastsessionstr = $student->lastsession
        ? userdate($student->lastsession, get_string('strftimedate', 'langconfig'))
        : '—';

    $reseturl = new moodle_url('/mod/leitnerflow/report.php', [
        'id'          => $cmid,
        'resetuserid' => $student->userid,
        'sesskey'     => sesskey(),
    ]);

    $resetbtn = '';
    if (has_capability('mod/leitnerflow:resetprogress', $context)) {
        $resetbtn = html_writer::link(
            $reseturl,
            get_string('resetprogress', 'mod_leitnerflow'),
            [
                'class'        => 'btn btn-sm btn-outline-danger',
                'data-confirm' => get_string('resetconfirm', 'mod_leitnerflow', $student->fullname),
            ]
        );
    }

    echo html_writer::start_tag('tr');
    // Student name + picture (pass full user object to avoid missing property warnings).
    $userpic = $OUTPUT->user_picture($student->user, ['size' => 24]);
    $profileurl = new moodle_url('/user/view.php', ['id' => $student->userid, 'course' => $course->id]);
    echo html_writer::tag('td',
        $userpic . ' ' . html_writer::link($profileurl, $student->fullname));
    echo html_writer::tag('td', html_writer::span($stats->learned, 'badge bg-success text-white'));
    echo html_writer::tag('td', html_writer::span($stats->open,    'badge bg-secondary text-white'));
    echo html_writer::tag('td', html_writer::span($stats->errors,  'badge bg-danger text-white'));
    echo html_writer::tag('td', $progressbar . html_writer::span(" {$pct} %", 'ms-1 small'));
    echo html_writer::tag('td', $student->sessions);
    echo html_writer::tag('td', $lastsessionstr);
    echo html_writer::tag('td', $resetbtn);
    echo html_writer::end_tag('tr');
}

echo html_writer::end_tag('tbody');
echo html_writer::end_tag('table');

// ---- Back button -----------------------------------------------------------
echo html_writer::div(
    $OUTPUT->single_button($viewurl, get_string('back'), 'get'),
    'mt-3'
);

// Confirm-dialog JS for reset buttons (proper AMD module, no inline JS).
$PAGE->requires->js_call_amd('mod_leitnerflow/confirm_reset', 'init');

echo $OUTPUT->footer();
