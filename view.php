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
 * Main view page for mod_eledialeitnerflow activity.
 *
 * Uses Moodle Component Library (Bootstrap) for standard elements,
 * custom CSS only for Leitner box visualization.
 *
 * @package    mod_eledialeitnerflow
 * @copyright  2026 eLeDia GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');
require_once($CFG->dirroot . '/question/engine/lib.php');

use mod_eledialeitnerflow\engine\leitner_engine;

$id = required_param('id', PARAM_INT); // Course module id.

$cm         = get_coursemodule_from_id('eledialeitnerflow', $id, 0, false, MUST_EXIST);
$course     = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$leitnerflow = $DB->get_record('eledialeitnerflow', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, true, $cm);
$context = \core\context\module::instance($cm->id);

// Trigger module viewed event for access logging.
$event = \mod_eledialeitnerflow\event\course_module_viewed::create([
    'objectid' => $leitnerflow->id,
    'context' => $context,
]);
$event->add_record_snapshot('course', $course);
$event->add_record_snapshot('eledialeitnerflow', $leitnerflow);
$event->trigger();

// Completion tracking.
$completion = new completion_info($course);
$completion->set_module_viewed($cm);

$PAGE->set_url('/mod/eledialeitnerflow/view.php', ['id' => $cm->id]);
$PAGE->set_title(format_string($leitnerflow->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);
$PAGE->add_body_class('mod-eledialeitnerflow');

// Suppress user tour if activity setting disables it.
if (empty($leitnerflow->showtour)) {
    $PAGE->add_body_class('eledialeitnerflow-notour');
} else {
    // Import bundled tours lazily on first view in case db/install.php
    // ran before tool_usertours was available (fresh install or test env).
    \mod_eledialeitnerflow\local\tour_installer::install_bundled_tours();
}

// Handle progress-reset request from teacher.
if (optional_param('resetuserid', 0, PARAM_INT) > 0) {
    require_capability('mod/eledialeitnerflow:resetprogress', $context);
    require_sesskey();
    $resetuid = required_param('resetuserid', PARAM_INT);
    leitner_engine::delete_user_data($leitnerflow->id, $resetuid);
    redirect(
        new moodle_url('/mod/eledialeitnerflow/view.php', ['id' => $cm->id]),
        get_string('progressreset', 'mod_eledialeitnerflow'),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

// Handle session-cancel request from student.
if (optional_param('cancelsession', 0, PARAM_INT)) {
    require_sesskey();
    $stale = $DB->get_records('eledialeitnerflow_sessions', [
        'eledialeitnerflowid' => $leitnerflow->id,
        'userid'        => $USER->id,
        'status'        => 0,
    ]);
    foreach ($stale as $s) {
        if (!empty($s->qubaid)) {
            question_engine::delete_questions_usage_by_activity($s->qubaid);
        }
    }
    $DB->delete_records('eledialeitnerflow_sessions', [
        'eledialeitnerflowid' => $leitnerflow->id,
        'userid'        => $USER->id,
        'status'        => 0,
    ]);
    redirect(
        new moodle_url('/mod/eledialeitnerflow/view.php', ['id' => $cm->id]),
        get_string('sessioncancelled', 'mod_eledialeitnerflow'),
        null,
        \core\output\notification::NOTIFY_INFO
    );
}

// Capability checks and page output.
$isteacher  = has_capability('mod/eledialeitnerflow:viewreport', $context);
$canattempt = has_capability('mod/eledialeitnerflow:attempt', $context);

echo $OUTPUT->header();
// Note: Moodle 4.x+ renders the activity name and intro in the page header
// automatically. Do NOT call format_module_intro() here to avoid duplication.

// Student view: show progress, session entry and recent stats.
if ($canattempt) {
    $categoryids = leitner_engine::get_category_ids($leitnerflow);
    $stats = leitner_engine::get_user_stats($leitnerflow->id, $USER->id, $categoryids);

    // Check for active session.
    $activesession = $DB->get_record('eledialeitnerflow_sessions', [
        'eledialeitnerflowid' => $leitnerflow->id,
        'userid'        => $USER->id,
        'status'        => 0,
    ]);

    // Dashboard card (Moodle card component).
    echo html_writer::start_div('card mb-4');
    echo html_writer::start_div('card-body');
    echo html_writer::tag('h5', get_string('yourprogress', 'mod_eledialeitnerflow'), ['class' => 'card-title']);

    // Leitner box visualization (custom — no Moodle equivalent).
    $boxdist = leitner_engine::get_box_distribution(
        $leitnerflow->id,
        $USER->id,
        $categoryids,
        $leitnerflow->boxcount
    );
    $boxcount = (int) $leitnerflow->boxcount;

    echo html_writer::start_div('eledialeitnerflow-boxes', [
        'role' => 'group',
        'aria-label' => get_string('boxdistribution', 'mod_eledialeitnerflow'),
    ]);

    for ($b = 1; $b <= $boxcount; $b++) {
        $count = $boxdist[$b] ?? 0;
        $boxlabel = get_string('box_n', 'mod_eledialeitnerflow', $b);
        $boxtitle = get_string('practiceboxn', 'mod_eledialeitnerflow', $b);

        // Clickable box: start a session with only questions from this box.
        $boxurl = new moodle_url('/mod/eledialeitnerflow/attempt.php', [
            'id' => $cm->id, 'start' => 1, 'box' => $b,
        ]);
        $boxattrs = [
            'aria-label' => $boxlabel . ': ' . $count,
            'title' => $boxtitle,
        ];
        if ($count > 0) {
            $boxattrs['style'] = 'cursor: pointer;';
            echo html_writer::start_tag('a', array_merge($boxattrs, [
                'href' => $boxurl->out(false),
                'class' => "eledialeitnerflow-box eledialeitnerflow-box-{$b} text-decoration-none",
            ]));
        } else {
            echo html_writer::start_div("eledialeitnerflow-box eledialeitnerflow-box-{$b}", array_merge($boxattrs, [
                'role' => 'status',
            ]));
        }
        echo html_writer::start_div('eledialeitnerflow-box-visual');
        echo html_writer::tag('div', $count, ['class' => 'eledialeitnerflow-box-count']);
        echo html_writer::tag('div', $boxlabel, ['class' => 'eledialeitnerflow-box-label']);
        echo html_writer::end_div();
        if ($count > 0) {
            echo html_writer::end_tag('a');
        } else {
            echo html_writer::end_div();
        }

        if ($b < $boxcount) {
            echo html_writer::tag('div', '&#10140;', [
                'class' => 'eledialeitnerflow-box-arrow',
                'aria-hidden' => 'true',
            ]);
        }
    }

    // Arrow before learned box.
    echo html_writer::tag('div', '&#10140;', [
        'class' => 'eledialeitnerflow-box-arrow',
        'aria-hidden' => 'true',
    ]);

    // Learned box.
    $learnedlabel = get_string('learned', 'mod_eledialeitnerflow');
    $learnedclass = 'eledialeitnerflow-box eledialeitnerflow-box-learned';
    if ($stats->total > 0 && $stats->learned >= $stats->total) {
        $learnedclass .= ' eledialeitnerflow-box-highlight';
    }
    echo html_writer::start_div($learnedclass, [
        'role' => 'status',
        'aria-label' => $learnedlabel . ': ' . $stats->learned,
    ]);
    echo html_writer::start_div('eledialeitnerflow-box-visual');
    echo html_writer::tag('div', $stats->learned, ['class' => 'eledialeitnerflow-box-count']);
    echo html_writer::tag('div', $learnedlabel . ' &#10003;', ['class' => 'eledialeitnerflow-box-label']);
    echo html_writer::end_div();
    echo html_writer::end_div();

    echo html_writer::end_div(); // Eledialeitnerflow-boxes.

    // Per-box progress bar (Moodle progress component).
    if ($stats->total > 0) {
        $pctlearned = round($stats->learned / $stats->total * 100, 1);

        echo html_writer::start_div('progress mb-1 mt-3', ['style' => 'height: 12px;']);
        for ($b = 1; $b <= $boxcount; $b++) {
            $cnt = $boxdist[$b] ?? 0;
            if ($cnt > 0) {
                $pct = round($cnt / $stats->total * 100, 1);
                echo html_writer::div(
                    '',
                    "progress-bar lf-seg-box{$b}",
                    ['style' => "width:{$pct}%", 'role' => 'progressbar',
                    'aria-valuenow' => $pct,
                    'aria-valuemin' => 0,
                    'aria-valuemax' => 100]
                );
            }
        }
        if ($stats->learned > 0) {
            $pct = round($stats->learned / $stats->total * 100, 1);
            echo html_writer::div(
                '',
                'progress-bar lf-seg-learned',
                ['style' => "width:{$pct}%", 'role' => 'progressbar',
                'aria-valuenow' => $pct,
                'aria-valuemin' => 0,
                'aria-valuemax' => 100]
            );
        }
        echo html_writer::end_div();

        // Label below progress bar — centered, prominent.
        echo html_writer::div(
            $stats->learned . ' / ' . $stats->total . ' '
            . get_string('learned', 'mod_eledialeitnerflow')
            . ' (' . round($pctlearned) . '%)',
            'text-center text-muted mt-2 mb-3',
            ['style' => 'font-size: 1.05rem;']
        );
    }

    echo html_writer::end_div(); // Card-body.
    echo html_writer::end_div(); // Card.

    // Session action area.
    echo html_writer::start_div('mt-3 mb-4');

    if ($stats->total === 0) {
        echo $OUTPUT->notification(get_string('nocardsinpool', 'mod_eledialeitnerflow'), 'warning');
    } else if ($stats->learned >= $stats->total) {
        // All learned — Moodle alert + celebration.
        echo html_writer::start_div('alert alert-success d-flex align-items-center', ['role' => 'status']);
        echo html_writer::span('&#127881;', 'mr-2 me-2', ['aria-hidden' => 'true', 'style' => 'font-size: 1.5rem;']);
        echo html_writer::tag('strong', get_string('alllearned', 'mod_eledialeitnerflow'));
        echo html_writer::end_div();

        echo html_writer::start_div('d-flex gap-2 flex-wrap');
        $reseturl = new moodle_url('/mod/eledialeitnerflow/view.php', [
            'id' => $cm->id,
            'resetuserid' => $USER->id,
            'sesskey' => sesskey(),
        ]);
        echo html_writer::link(
            $reseturl,
            get_string('resetandrestart', 'mod_eledialeitnerflow'),
            ['class' => 'btn btn-outline-secondary']
        );
        echo html_writer::end_div();
    } else if ($activesession) {
        // Active session — Moodle alert as session banner.
        $answered = (int) $activesession->questionsasked;
        $totalq   = count(json_decode($activesession->questionids, true));
        $correctq = (int) $activesession->questionscorrect;

        echo html_writer::start_div('alert alert-info', ['role' => 'status']);
        echo get_string('activesessioninfo', 'mod_eledialeitnerflow', (object) [
            'answered' => $answered,
            'total'    => $totalq,
            'correct'  => $correctq,
        ]);
        echo html_writer::end_div();

        echo html_writer::start_div('d-flex gap-2 flex-wrap');

        // Continue session (primary).
        $continueurl = new moodle_url('/mod/eledialeitnerflow/attempt.php', [
            'id'     => $cm->id,
            'sessid' => $activesession->id,
        ]);
        echo html_writer::link(
            $continueurl,
            get_string('continuesession', 'mod_eledialeitnerflow'),
            ['class' => 'btn btn-primary']
        );

        // New session.
        $newurl = new moodle_url('/mod/eledialeitnerflow/attempt.php', ['id' => $cm->id, 'start' => 1]);
        echo html_writer::link(
            $newurl,
            get_string('newsession', 'mod_eledialeitnerflow'),
            ['class' => 'btn btn-outline-secondary']
        );

        // End session.
        $cancelurl = new moodle_url('/mod/eledialeitnerflow/view.php', [
            'id' => $cm->id,
            'cancelsession' => 1,
            'sesskey' => sesskey(),
        ]);
        echo html_writer::link(
            $cancelurl,
            get_string('endsession', 'mod_eledialeitnerflow'),
            ['class' => 'btn btn-outline-danger']
        );

        echo html_writer::end_div();
    } else {
        // No active session — start button.
        echo html_writer::start_div('d-flex');
        $starturl = new moodle_url('/mod/eledialeitnerflow/attempt.php', ['id' => $cm->id, 'start' => 1]);
        echo html_writer::link(
            $starturl,
            get_string('startsession', 'mod_eledialeitnerflow'),
            ['class' => 'btn btn-primary']
        );
        echo html_writer::end_div();
    }

    echo html_writer::end_div(); // Actions.

    // Session history card (Progress Dashboard).
    $sessionhistory = leitner_engine::get_session_history($leitnerflow->id, $USER->id, 5);
    $sessionstats   = leitner_engine::get_session_stats($leitnerflow->id, $USER->id);

    if ($sessionstats->sessioncount > 0) {
        echo html_writer::start_div('card mb-4', ['id' => 'eledialeitnerflow-session-history']);
        echo html_writer::start_div('card-body');

        // Card title with session count and average.
        echo html_writer::start_div('d-flex justify-content-between align-items-center mb-3');
        echo html_writer::tag('h5', get_string('sessionhistory', 'mod_eledialeitnerflow'), ['class' => 'card-title mb-0']);
        echo html_writer::start_div('d-flex gap-3 align-items-center');
        echo html_writer::span(
            get_string('totalsessions', 'mod_eledialeitnerflow', $sessionstats->sessioncount),
            'badge bg-secondary'
        );
        echo html_writer::span(
            get_string('avgcorrect', 'mod_eledialeitnerflow', $sessionstats->avgpercent),
            'small text-muted'
        );
        echo html_writer::end_div();
        echo html_writer::end_div();

        // Trend indicator — compare last 3 sessions avg vs overall avg.
        if (count($sessionhistory) >= 3) {
            $recentcorrect = 0;
            $recenttotal   = 0;
            for ($i = 0; $i < 3; $i++) {
                $recentcorrect += (int) $sessionhistory[$i]->questionscorrect;
                $recenttotal   += (int) $sessionhistory[$i]->questionsasked;
            }
            $recentpct = ($recenttotal > 0) ? round(($recentcorrect / $recenttotal) * 100) : 0;

            $diff = $recentpct - $sessionstats->avgpercent;
            if ($diff > 5) {
                $badgeclass = 'badge bg-success';
                $arrow      = '&#8599;'; // Up-right arrow.
                $diffstr    = '+' . $diff . '%';
            } else if ($diff < -5) {
                $badgeclass = 'badge bg-danger';
                $arrow      = '&#8600;'; // Down-right arrow.
                $diffstr    = $diff . '%';
            } else {
                $badgeclass = 'badge bg-secondary';
                $arrow      = '&#8594;'; // Right arrow.
                $diffstr    = '±' . abs($diff) . '%';
            }
            echo html_writer::div(
                html_writer::span($arrow . ' ' . $diffstr, $badgeclass)
                . ' '
                . html_writer::span(
                    get_string('trend_recent', 'mod_eledialeitnerflow', (object) [
                        'recent' => $recentpct,
                        'avg'    => $sessionstats->avgpercent,
                    ]),
                    'small text-muted'
                ),
                'mb-2'
            );
        }

        // Session history table (Moodle table component).
        echo html_writer::start_tag('table', ['class' => 'table table-sm table-hover mb-0']);
        echo html_writer::start_tag('thead');
        echo html_writer::start_tag('tr');
        echo html_writer::tag('th', get_string('date'), ['class' => 'small text-muted']);
        echo html_writer::tag('th', get_string('correctrate', 'mod_eledialeitnerflow'), ['class' => 'small text-muted']);
        echo html_writer::tag('th', get_string('progress'), ['class' => 'small text-muted', 'style' => 'width: 40%;']);
        echo html_writer::tag(
            'th',
            get_string('sessionduration', 'mod_eledialeitnerflow'),
            ['class' => 'small text-muted text-end']
        );
        echo html_writer::end_tag('tr');
        echo html_writer::end_tag('thead');
        echo html_writer::start_tag('tbody');

        foreach ($sessionhistory as $sess) {
            $asked   = (int) $sess->questionsasked;
            $correct = (int) $sess->questionscorrect;
            $pct     = ($asked > 0) ? round(($correct / $asked) * 100) : 0;

            // Duration.
            $duration = '';
            if (!empty($sess->timecompleted) && !empty($sess->timecreated)) {
                $secs = (int) $sess->timecompleted - (int) $sess->timecreated;
                if ($secs < 60) {
                    $duration = '< 1 min';
                } else if ($secs < 3600) {
                    $duration = round($secs / 60) . ' min';
                } else {
                    $duration = round($secs / 3600, 1) . ' h';
                }
            }

            // Progress bar color based on percent.
            $barclass = 'bg-primary';
            if ($pct >= 80) {
                $barclass = 'lf-seg-learned';
            } else if ($pct >= 50) {
                $barclass = 'lf-seg-box3';
            } else {
                $barclass = 'lf-seg-box1';
            }

            echo html_writer::start_tag('tr');

            // Date.
            echo html_writer::tag(
                'td',
                userdate($sess->timecompleted, get_string('strftimedateshort')),
                ['class' => 'small']
            );

            // Correct count.
            echo html_writer::tag(
                'td',
                get_string('sessioncorrectof', 'mod_eledialeitnerflow', (object) [
                    'correct' => $correct, 'total' => $asked,
                ]) . ' (' . $pct . '%)',
                ['class' => 'small']
            );

            // Mini progress bar.
            echo html_writer::start_tag('td');
            echo html_writer::start_div('progress', ['style' => 'height: 12px;']);
            echo html_writer::div(
                '',
                'progress-bar ' . $barclass,
                ['style' => "width:{$pct}%",
                 'role' => 'progressbar',
                'aria-valuenow' => $pct,
                'aria-valuemin' => 0,
                'aria-valuemax' => 100]
            );
            echo html_writer::end_div();
            echo html_writer::end_tag('td');

            // Duration.
            echo html_writer::tag('td', $duration, ['class' => 'small text-end text-muted']);

            echo html_writer::end_tag('tr');
        }

        echo html_writer::end_tag('tbody');
        echo html_writer::end_tag('table');

        echo html_writer::end_div(); // Card-body.
        echo html_writer::end_div(); // Card.
    }
}

// Teacher view: link to detailed report.
if ($isteacher) {
    $reporturl = new moodle_url('/mod/eledialeitnerflow/report.php', ['id' => $cm->id]);
    echo html_writer::div(
        $OUTPUT->single_button($reporturl, get_string('viewreport', 'mod_eledialeitnerflow'), 'get'),
        'mt-3'
    );
}

echo $OUTPUT->footer();
