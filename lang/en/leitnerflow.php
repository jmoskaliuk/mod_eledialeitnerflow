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
 * English language strings for mod_leitnerflow.
 *
 * Core Moodle strings are reused where available via get_string('key') without component.
 * Strings that exist in Moodle core and should NOT be redefined here:
 *   - 'question' (core)           — used as get_string('question')
 *   - 'progress' (core)           — used as get_string('progress')
 *   - 'continue' (core)           — used as get_string('continue')
 *   - 'cancel' (core)             — used as get_string('cancel')
 *   - 'participants' (core)       — used as get_string('participants')
 *   - 'questionbank' (core_question) — used as get_string('questionbank', 'question')
 *
 * @package    mod_leitnerflow
 * @copyright  2024 eLeDia GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Plugin metadata.
$string['pluginname']           = 'LeitnerFlow Activity';
$string['modulename']           = 'LeitnerFlow Activity';
$string['modulenameplural']     = 'LeitnerFlow Activities';
$string['modulename_help']      = 'The Leitner Flow uses spaced repetition (Leitner system) to help students efficiently memorise question content from the Question Bank. Cards move through boxes based on correct answers and are repeated until fully learned.';
$string['pluginadministration'] = 'LeitnerFlow administration';

// Settings form — short labels, details in _help tooltips.
$string['questioncategory']          = 'Question categories';
$string['questioncategory_help']     = 'Select one or more Question Bank categories from which cards are drawn. All questions in the selected categories (excluding sub-categories) become flashcards. Create categories via the course Question Bank.';
$string['sessionsize']               = 'Questions per session';
$string['sessionsize_help']          = 'Maximum number of questions shown in one study session. Students can always finish early. Recommended: 5–20 for short, focused practice.';
$string['boxcount']                  = 'Number of boxes';
$string['boxcount_help']             = 'How many Leitner boxes (levels) to use. Cards progress from Box 1 → Box N → Learned. More boxes = finer-grained spacing between reviews. 3 boxes is a good default; 5 gives more granularity for larger question pools.';
$string['correcttolearn']            = 'Correct answers required';
$string['correcttolearn_help']       = 'Total correct answers needed before a card is marked as "learned" and removed from active rotation. Higher values mean more repetition. Example: with 3, a student must answer correctly 3 times (across all sessions) before the card graduates.';
$string['wrongbehavior']             = 'On wrong answer';
$string['wrongbehavior_help']        = 'What happens when a student answers incorrectly:<br><b>Reset to Box 1</b> — strictest, card restarts from scratch.<br><b>Back one box</b> — moderate, card drops one level.<br><b>No change</b> — lenient, card stays in current box but correct-count does not increase.';
$string['wrongbehavior_reset']       = 'Reset to Box 1';
$string['wrongbehavior_back1']       = 'Back one box';
$string['wrongbehavior_nochange']    = 'No change';
$string['questionrotation']          = 'Question rotation';
$string['questionrotation_help']     = '<b>Dynamic</b>: questions are always fetched fresh from the Question Bank — new questions appear automatically, deleted ones disappear.<br><b>Fixed</b>: the question pool is locked when the student first starts. Later changes to the bank are not reflected.';
$string['questionrotation_dynamic']  = 'Dynamic';
$string['questionrotation_fixed']    = 'Fixed pool';
$string['prioritystrategy']          = 'Card selection';
$string['prioritystrategy_help']     = '<b>Prioritise lower boxes</b>: questions in Box 1 (least known) are shown first — good for focused review.<br><b>Mixed random</b>: questions from all boxes are mixed randomly — good for variety.';
$string['prioritystrategy_prio']     = 'Lower boxes first';
$string['prioritystrategy_mixed']    = 'Mixed random';
$string['grademethod']               = 'Grading';
$string['grademethod_none']          = 'No grade';
$string['grademethod_percent']       = '% of cards learned';
$string['gradingsettings']           = 'Grading';
$string['leitnersettings']           = 'Leitner system';
$string['sessionsettings']           = 'Session';

// View page.
$string['startsession']       = 'Start study session';
$string['continuesession']    = 'Continue session';
$string['newsession']         = 'New session';
$string['cancelsession']      = 'Cancel session';
$string['sessioncancelled']   = 'Session cancelled.';
$string['sessioninprogress']  = 'Session in progress';
$string['nosessionactive']    = 'No active session';
$string['yourprogress']       = 'Your learning progress';
$string['learned']            = 'Learned';
$string['open']               = 'Open';
$string['witherrors']         = 'With errors';
$string['viewreport']         = 'View full report';
$string['sessioncomplete']    = 'Session complete!';
$string['sessionresult']      = 'You answered {$a->correct} of {$a->total} questions correctly.';
$string['alllearned']         = 'All questions learned!';
$string['nocardsinpool']      = 'No questions found in the selected category. Please add questions to the Question Bank first.';
$string['nounlearnedcards']   = 'All cards are already learned! You can reset your progress to start over.';
$string['boxdistribution']    = 'Leitner box distribution';
$string['activesessioninfo']  = 'Active session: {$a->answered} of {$a->total} answered, {$a->correct} correct';
$string['resetandrestart']    = 'Reset and start over';
$string['current']            = 'current';

// Progress dashboard — session history.
$string['sessionhistory']     = 'Session history';
$string['sessiondate']        = 'Date';
$string['sessioncorrectof']   = '{$a->correct} / {$a->total}';
$string['sessionpercent']     = '{$a}%';
$string['sessionduration']    = 'Duration';
$string['nosessions']         = 'No completed sessions yet.';
$string['totalsessions']      = '{$a} sessions completed';
$string['avgcorrect']         = 'Average: {$a}% correct';
$string['trend_recent']       = 'Last 3 sessions: {$a->recent}% correct (average: {$a->avg}%)';
$string['correctrate']        = 'Correct';

// Attempt page.
// Note: 'question' string comes from Moodle core — use get_string('question').
$string['cardstatus_box']     = 'Box {$a}';
$string['cardstatus_learned'] = 'Learned';
$string['correct']            = 'correct';
$string['incorrect']          = 'Incorrect.';
$string['nextquestion']       = 'Next question';
$string['finishsession']      = 'Finish session';
$string['correctanswer']      = 'Correct answer';
$string['movedtobox']         = 'Card moved to box {$a}';
$string['cardlearned']        = 'Card marked as learned!';
$string['cardreset']          = 'Card reset to box 1.';
$string['cardbackone']        = 'Card moved back one box.';
$string['nextaftercheck']     = 'Next question after Check';
$string['backtooverview']     = 'Back to overview';
$string['practiceboxn']       = 'Practice Box {$a}';
$string['nocardsinthisbox']   = 'No cards in Box {$a} right now.';

// Report page.
$string['questionsinpool']    = 'Questions in pool';
$string['avglearnedpercent']  = 'Avg. % learned';
$string['endsession']         = 'End session';
$string['report']        = 'Student overview';
$string['lastsession']   = 'Last session';
$string['nostudents']    = 'No students have started this activity yet.';
$string['resetprogress'] = 'Reset progress';
$string['resetconfirm']  = 'Are you sure you want to reset all progress for {$a}? This cannot be undone.';
$string['progressreset'] = 'Progress has been reset.';

// Privacy.
$string['privacy:metadata']                                    = 'The Leitner Flow plugin stores per-student card states and session data.';
$string['privacy:metadata:leitnerflow_card_state']             = 'Tracks each student\'s progress per question (current box, correct count, status).';
$string['privacy:metadata:leitnerflow_card_state:userid']      = 'The ID of the student.';
$string['privacy:metadata:leitnerflow_card_state:questionid']  = 'The ID of the question.';
$string['privacy:metadata:leitnerflow_card_state:currentbox']  = 'The current Leitner box the card is in.';
$string['privacy:metadata:leitnerflow_card_state:correctcount'] = 'How many times the student answered correctly.';
$string['privacy:metadata:leitnerflow_card_state:attemptcount'] = 'Total number of attempts.';
$string['privacy:metadata:leitnerflow_card_state:status']      = 'Card status: open, learned, or has errors.';
$string['privacy:metadata:leitnerflow_sessions']               = 'Records each study session a student completes.';
$string['privacy:metadata:leitnerflow_sessions:userid']        = 'The ID of the student.';
$string['privacy:metadata:leitnerflow_sessions:timecreated']   = 'When the session started.';
$string['privacy:metadata:leitnerflow_sessions:timecompleted'] = 'When the session was completed.';
$string['privacy:metadata:leitnerflow_sessions:questionsasked'] = 'Number of questions in the session.';
$string['privacy:metadata:leitnerflow_sessions:questionscorrect'] = 'Number of correct answers in the session.';

// Capabilities.
$string['leitnerflow:addinstance']   = 'Add a new LeitnerFlow activity';
$string['leitnerflow:view']          = 'View Leitner Flow';
$string['leitnerflow:attempt']       = 'Attempt Leitner Flow';
$string['leitnerflow:viewreport']    = 'View student report';
$string['leitnerflow:manage']        = 'Manage Leitner Flow settings';
$string['leitnerflow:resetprogress'] = 'Reset student progress';

// Box labels.
$string['box_n']       = 'Box {$a}';
$string['box_1']       = 'Box 1 – New / Errors';
$string['box_learned'] = 'Learned';

// Events.
$string['event_session_started']   = 'Learning session started';
$string['event_session_completed'] = 'Learning session completed';
$string['event_progress_reset']    = 'Student progress reset';

// Errors.
$string['invalidsession']  = 'Invalid or expired session.';
$string['nocategory']      = 'No question category configured. Please edit the activity settings.';
$string['error_noattempt'] = 'You do not have permission to attempt this quiz.';
