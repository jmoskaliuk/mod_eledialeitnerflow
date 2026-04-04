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
 * Teacher report page for mod_eledialeitnerflow.
 *
 * Overview of all enrolled students' Leitner progress with pagination,
 * search, and sortable columns — suitable for courses with 100+ participants.
 *
 * @package    mod_eledialeitnerflow
 * @copyright  2024 eLeDia GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

use mod_eledialeitnerflow\engine\leitner_engine;

$cmid      = required_param('id', PARAM_INT);
$resetuid  = optional_param('resetuserid', 0, PARAM_INT);
$page      = optional_param('page', 0, PARAM_INT);
$perpage   = optional_param('perpage', 25, PARAM_INT);
$tsort     = optional_param('tsort', 'fullname', PARAM_ALPHA);
$tdir      = optional_param('tdir', 'ASC', PARAM_ALPHA);
$search    = optional_param('search', '', PARAM_TEXT);

// Sanitise sort direction.
$tdir = (strtoupper($tdir) === 'DESC') ? 'DESC' : 'ASC';

// Allowed sort columns.
$allowedsorts = ['fullname', 'learned', 'progress', 'sessions', 'lastsession'];
if (!in_array($tsort, $allowedsorts)) {
    $tsort = 'fullname';
}

$cm          = get_coursemodule_from_id('eledialeitnerflow', $cmid, 0, false, MUST_EXIST);
$course      = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$leitnerflow = $DB->get_record('eledialeitnerflow', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, true, $cm);
$context = \core\context\module::instance($cm->id);
require_capability('mod/eledialeitnerflow:viewreport', $context);

// ---- Handle reset ----------------------------------------------------------
if ($resetuid > 0) {
    require_sesskey();
    require_capability('mod/eledialeitnerflow:resetprogress', $context);
    leitner_engine::delete_user_data($leitnerflow->id, $resetuid);

    $event = \mod_eledialeitnerflow\event\progress_reset::create([
        'objectid'      => $leitnerflow->id,
        'context'       => $context,
        'relateduserid' => $resetuid,
    ]);
    $event->trigger();

    redirect(
        new moodle_url('/mod/eledialeitnerflow/report.php', ['id' => $cmid]),
        get_string('progressreset', 'mod_eledialeitnerflow'),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

// ---- Page setup ------------------------------------------------------------
$baseurl = new moodle_url('/mod/eledialeitnerflow/report.php', [
    'id' => $cmid, 'tsort' => $tsort, 'tdir' => $tdir, 'perpage' => $perpage, 'search' => $search,
]);

$PAGE->set_url('/mod/eledialeitnerflow/report.php', ['id' => $cmid]);
$PAGE->set_title(format_string($leitnerflow->name) . ': ' . get_string('report', 'mod_eledialeitnerflow'));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);
$PAGE->activityheader->set_description('');

$viewurl = new moodle_url('/mod/eledialeitnerflow/view.php', ['id' => $cmid]);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('report', 'mod_eledialeitnerflow'), 4);

// ---- Load student data -----------------------------------------------------
$students = leitner_engine::get_all_students_stats($leitnerflow, $course->id, $context);

if (empty($students)) {
    echo $OUTPUT->notification(get_string('nostudents', 'mod_eledialeitnerflow'), 'info');
    echo $OUTPUT->single_button($viewurl, get_string('back'), 'get');
    echo $OUTPUT->footer();
    exit;
}

// ---- Filter by search term -------------------------------------------------
if ($search !== '') {
    $searchlower = core_text::strtolower($search);
    $students = array_filter($students, function($s) use ($searchlower) {
        return str_contains(core_text::strtolower($s->fullname), $searchlower);
    });
}

// ---- Sort ------------------------------------------------------------------
usort($students, function($a, $b) use ($tsort, $tdir) {
    switch ($tsort) {
        case 'learned':
            $cmp = $a->stats->learned <=> $b->stats->learned;
            break;
        case 'progress':
            $cmp = $a->stats->percent_learned <=> $b->stats->percent_learned;
            break;
        case 'sessions':
            $cmp = $a->sessions <=> $b->sessions;
            break;
        case 'lastsession':
            $cmp = ($a->lastsession ?? 0) <=> ($b->lastsession ?? 0);
            break;
        default: // fullname.
            $cmp = strcmp(
                core_text::strtolower($a->fullname),
                core_text::strtolower($b->fullname)
            );
    }
    return ($tdir === 'DESC') ? -$cmp : $cmp;
});

$totalcount    = count($students);
$allstudents   = $students; // Keep full set for summary cards.
$students      = array_slice($students, $page * $perpage, $perpage);

// ---- Summary cards (course-wide, computed from all students) ----------------
$totallearned = 0;
$totalcards   = 0;
foreach ($allstudents as $s) {
    $totallearned += $s->stats->learned;
    $totalcards   += $s->stats->total;
}
$avgpct = $totalcount > 0
    ? round(array_sum(array_map(fn($s) => $s->stats->percent_learned, $allstudents)) / $totalcount)
    : 0;

$questioncount = !empty($allstudents) ? reset($allstudents)->stats->total : 0;

echo html_writer::start_div('row mb-4');
$summaries = [
    [get_string('participants'), $totalcount, 'bg-primary'],
    [get_string('questionsinpool', 'mod_eledialeitnerflow'), $questioncount, 'lf-bg-darkblue'],
    [get_string('avglearnedpercent', 'mod_eledialeitnerflow'), $avgpct . ' %', 'bg-success'],
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

// ---- Search bar + per-page selector ----------------------------------------
$searchurl = new moodle_url('/mod/eledialeitnerflow/report.php', [
    'id' => $cmid, 'tsort' => $tsort, 'tdir' => $tdir, 'perpage' => $perpage,
]);
echo html_writer::start_tag('form', [
    'method' => 'get',
    'action' => $searchurl->out_omit_querystring(),
    'class'  => 'd-flex gap-2 mb-3 align-items-center flex-wrap',
]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'id', 'value' => $cmid]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'tsort', 'value' => $tsort]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'tdir', 'value' => $tdir]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'perpage', 'value' => $perpage]);
echo html_writer::empty_tag('input', [
    'type'        => 'text',
    'name'        => 'search',
    'value'       => $search,
    'placeholder' => get_string('search'),
    'class'       => 'form-control',
    'style'       => 'max-width: 300px;',
    'aria-label'  => get_string('search'),
]);
echo html_writer::tag('button', get_string('search'), [
    'type' => 'submit', 'class' => 'btn btn-outline-secondary',
]);
if ($search !== '') {
    $clearurl = new moodle_url('/mod/eledialeitnerflow/report.php', [
        'id' => $cmid, 'tsort' => $tsort, 'tdir' => $tdir, 'perpage' => $perpage,
    ]);
    echo html_writer::link($clearurl, get_string('clear'), ['class' => 'btn btn-link']);
}

// Per-page selector.
echo html_writer::start_div('ms-auto d-flex align-items-center gap-2');
echo html_writer::tag('label', get_string('perpage', 'moodle', ''), [
    'for' => 'report-perpage', 'class' => 'small text-muted mb-0',
]);
$perpageoptions = [10 => '10', 25 => '25', 50 => '50', 100 => '100'];
echo html_writer::select($perpageoptions, 'perpage', $perpage, false, [
    'id'       => 'report-perpage',
    'class'    => 'form-select form-select-sm',
    'style'    => 'width: auto;',
    'onchange' => 'this.form.submit();',
]);
echo html_writer::end_div();

echo html_writer::end_tag('form');

// ---- Sortable column headers -----------------------------------------------
/**
 * Build a sort link for a table header.
 *
 * @param string $column   Column key.
 * @param string $label    Display label.
 * @param string $tsort    Current sort column.
 * @param string $tdir     Current sort direction.
 * @param int    $cmid     Course module id.
 * @param int    $perpage  Items per page.
 * @param string $search   Current search term.
 * @return string HTML link.
 */
function _eledialeitnerflow_sort_link(string $column, string $label, string $tsort,
        string $tdir, int $cmid, int $perpage, string $search): string {
    $newdir = ($tsort === $column && $tdir === 'ASC') ? 'DESC' : 'ASC';
    $url = new moodle_url('/mod/eledialeitnerflow/report.php', [
        'id' => $cmid, 'tsort' => $column, 'tdir' => $newdir, 'perpage' => $perpage, 'search' => $search,
    ]);
    $arrow = '';
    if ($tsort === $column) {
        $arrow = ($tdir === 'ASC') ? ' &#9650;' : ' &#9660;';
    }
    return html_writer::link($url, $label . $arrow, ['class' => 'text-nowrap']);
}

// ---- Student table ---------------------------------------------------------
echo html_writer::start_tag('table', ['class' => 'table table-striped table-hover generaltable']);
echo html_writer::start_tag('thead');
echo html_writer::start_tag('tr');
$headers = [
    ['key' => 'fullname',    'label' => get_string('participants')],
    ['key' => 'learned',     'label' => get_string('learned', 'mod_eledialeitnerflow')],
    ['key' => '',            'label' => get_string('open', 'mod_eledialeitnerflow')],
    ['key' => '',            'label' => get_string('witherrors', 'mod_eledialeitnerflow')],
    ['key' => 'progress',    'label' => get_string('progress')],
    ['key' => 'sessions',    'label' => get_string('sessionhistory', 'mod_eledialeitnerflow')],
    ['key' => 'lastsession', 'label' => get_string('lastsession', 'mod_eledialeitnerflow')],
    ['key' => '',            'label' => ''],
];
foreach ($headers as $h) {
    if (!empty($h['key'])) {
        echo html_writer::tag('th',
            _eledialeitnerflow_sort_link($h['key'], $h['label'], $tsort, $tdir, $cmid, $perpage, $search));
    } else {
        echo html_writer::tag('th', $h['label']);
    }
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

    $reseturl = new moodle_url('/mod/eledialeitnerflow/report.php', [
        'id'          => $cmid,
        'resetuserid' => $student->userid,
        'sesskey'     => sesskey(),
    ]);

    $resetbtn = '';
    if (has_capability('mod/eledialeitnerflow:resetprogress', $context)) {
        $resetbtn = html_writer::link(
            $reseturl,
            get_string('resetprogress', 'mod_eledialeitnerflow'),
            [
                'class'        => 'btn btn-sm btn-outline-danger',
                'data-confirm' => get_string('resetconfirm', 'mod_eledialeitnerflow', $student->fullname),
            ]
        );
    }

    echo html_writer::start_tag('tr');
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

// ---- Pagination bar --------------------------------------------------------
echo $OUTPUT->paging_bar($totalcount, $page, $perpage, $baseurl);

// ---- Back button -----------------------------------------------------------
echo html_writer::div(
    $OUTPUT->single_button($viewurl, get_string('back'), 'get'),
    'mt-3'
);

// Confirm-dialog JS for reset buttons.
$PAGE->requires->js_call_amd('mod_eledialeitnerflow/confirm_reset', 'init');

echo $OUTPUT->footer();
