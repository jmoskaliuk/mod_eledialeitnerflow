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
 * @package    mod_leitnerflow
 * @copyright  2024 eLeDia GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Plugin metadata
$string['pluginname']         = 'LeitnerFlow Activity';
$string['modulename']         = 'LeitnerFlow Activity';
$string['modulenameplural']   = 'LeitnerFlow Activities';
$string['modulename_help']    = 'The Leitner Flow uses spaced repetition (Leitner system) to help students efficiently memorise question content from the Question Bank. Cards move through boxes based on correct answers and are repeated until fully learned.';
$string['pluginadministration'] = 'LeitnerFlow administration';

// Settings form
$string['questioncategory']        = 'Question category';
$string['questioncategory_help']   = 'Select the Question Bank category from which questions will be drawn. All questions in this category (excluding sub-categories) will be available.';
$string['sessionsize']             = 'Questions per session';
$string['sessionsize_help']        = 'How many questions are shown in a single study session.';
$string['boxcount']                = 'Number of Leitner boxes';
$string['boxcount_help']           = 'The number of boxes (levels) in the Leitner system. More boxes give finer-grained progress tracking.';
$string['correcttolearn']          = 'Correct answers to mark as learned';
$string['correcttolearn_help']     = 'How many times a student must answer a question correctly (total) before it is marked as \'learned\' and removed from the active rotation.';
$string['wrongbehavior']           = 'On wrong answer';
$string['wrongbehavior_help']      = 'What happens to a card\'s progress when the student answers incorrectly.';
$string['wrongbehavior_reset']     = 'Reset to Box 1 (full reset)';
$string['wrongbehavior_back1']     = 'Go back one box';
$string['wrongbehavior_nochange']  = 'No change (only stop counting up)';
$string['questionrotation']        = 'Question rotation';
$string['questionrotation_help']   = 'Dynamic: questions are always fetched fresh from the Question Bank (reflects additions/removals). Fixed: the pool is locked when the student first starts.';
$string['questionrotation_dynamic'] = 'Dynamic (always from bank)';
$string['questionrotation_fixed']   = 'Fixed pool (locked on first start)';
$string['prioritystrategy']        = 'Card selection strategy';
$string['prioritystrategy_help']   = 'Prioritised: questions in lower boxes (less known) are shown first. Mixed: random selection across all boxes.';
$string['prioritystrategy_prio']   = 'Prioritise lower boxes first';
$string['prioritystrategy_mixed']  = 'Mixed random selection';
$string['grademethod']             = 'Grading method';
$string['grademethod_none']        = 'No grade';
$string['grademethod_percent']     = 'Percentage of cards learned';
$string['gradingsettings']         = 'Grading settings';
$string['leitnersettings']         = 'Leitner system settings';
$string['sessionsettings']         = 'Session settings';

// View page
$string['startsession']            = 'Start study session';
$string['continuesession']         = 'Continue session';
$string['sessioninprogress']       = 'Session in progress';
$string['nosessionactive']         = 'No active session';
$string['yourprogress']            = 'Your progress';
$string['totalcards']              = 'Total cards';
$string['learned']                 = 'Learned';
$string['open']                    = 'Open';
$string['witherrors']              = 'With errors';
$string['viewreport']              = 'View full report';
$string['sessioncomplete']         = 'Session complete!';
$string['sessionresult']           = 'You answered {$a->correct} of {$a->total} questions correctly.';
$string['alllearned']              = 'All cards learned! Well done.';
$string['nocardsinpool']           = 'No questions found in the selected category. Please add questions to the Question Bank first.';
$string['nounlearnedcards']        = 'All cards are already learned! You can reset your progress to start over.';

// Attempt page
$string['question']                = 'Question';
$string['of']                      = 'of';
$string['cardstatus_box']          = 'Box {$a}';
$string['cardstatus_learned']      = 'Learned';
$string['correct']                 = 'Correct!';
$string['incorrect']               = 'Incorrect.';
$string['nextquestion']            = 'Next question';
$string['finishsession']           = 'Finish session';
$string['check']                   = 'Check answer';
$string['correctanswer']           = 'Correct answer';
$string['movedtobox']              = 'Card moved to box {$a}';
$string['cardlearned']             = 'Card marked as learned!';
$string['cardreset']               = 'Card reset to box 1.';
$string['cardbackone']             = 'Card moved back one box.';

// Report page
$string['report']                  = 'Student overview';
$string['student']                 = 'Student';
$string['progress']                = 'Progress';
$string['sessions']                = 'Sessions';
$string['lastsession']             = 'Last session';
$string['nostudents']              = 'No students have started this activity yet.';
$string['resetprogress']           = 'Reset progress';
$string['resetconfirm']            = 'Are you sure you want to reset all progress for {$a}? This cannot be undone.';
$string['progressreset']           = 'Progress has been reset.';

// Privacy
$string['privacy:metadata']                              = 'The Leitner Flow plugin stores per-student card states and session data.';
$string['privacy:metadata:leitnerflow_card_state']       = 'Tracks each student\'s progress per question (current box, correct count, status).';
$string['privacy:metadata:leitnerflow_card_state:userid']       = 'The ID of the student.';
$string['privacy:metadata:leitnerflow_card_state:questionid']   = 'The ID of the question.';
$string['privacy:metadata:leitnerflow_card_state:currentbox']   = 'The current Leitner box the card is in.';
$string['privacy:metadata:leitnerflow_card_state:correctcount'] = 'How many times the student answered correctly.';
$string['privacy:metadata:leitnerflow_card_state:attemptcount'] = 'Total number of attempts.';
$string['privacy:metadata:leitnerflow_card_state:status']       = 'Card status: open, learned, or has errors.';
$string['privacy:metadata:leitnerflow_sessions']                = 'Records each study session a student completes.';
$string['privacy:metadata:leitnerflow_sessions:userid']         = 'The ID of the student.';
$string['privacy:metadata:leitnerflow_sessions:timecreated']    = 'When the session started.';
$string['privacy:metadata:leitnerflow_sessions:timecompleted']  = 'When the session was completed.';
$string['privacy:metadata:leitnerflow_sessions:questionsasked'] = 'Number of questions in the session.';
$string['privacy:metadata:leitnerflow_sessions:questionscorrect'] = 'Number of correct answers in the session.';

// Capabilities
$string['leitnerflow:addinstance']   = 'Add a new LeitnerFlow activity';
$string['leitnerflow:view']          = 'View Leitner Flow';
$string['leitnerflow:attempt']       = 'Attempt Leitner Flow';
$string['leitnerflow:viewreport']    = 'View student report';
$string['leitnerflow:manage']        = 'Manage Leitner Flow settings';
$string['leitnerflow:resetprogress'] = 'Reset student progress';

// Box labels
$string['box_n']  = 'Box {$a}';
$string['box_1']  = 'Box 1 – New / Errors';
$string['box_learned'] = 'Learned';

// Events
$string['event_session_started']   = 'Learning session started';
$string['event_session_completed'] = 'Learning session completed';
$string['event_progress_reset']    = 'Student progress reset';

// Errors
$string['invalidsession']   = 'Invalid or expired session.';
$string['nocategory']       = 'No question category configured. Please edit the activity settings.';
$string['error_noattempt']  = 'You do not have permission to attempt this quiz.';
