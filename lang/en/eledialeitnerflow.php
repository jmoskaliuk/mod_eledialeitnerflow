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
 * English language strings for mod_eledialeitnerflow.
 *
 * Core Moodle strings are reused where available via get_string('key') without component.
 * Strings that exist in Moodle core and should NOT be redefined here:
 *   - 'question' (core)           — used as get_string('question')
 *   - 'progress' (core)           — used as get_string('progress')
 *   - 'continue' (core)           — used as get_string('continue')
 *   - 'cancel' (core)             — used as get_string('cancel')
 *   - 'date' (core)               — used as get_string('date')
 *   - 'participants' (core)       — used as get_string('participants')
 *   - 'questionbank' (core_question) — used as get_string('questionbank', 'question')
 *
 * @package    mod_eledialeitnerflow
 * @copyright  2026 eLeDia GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['activesessioninfo']  = 'Active session: {$a->answered} of {$a->total} answered, {$a->correct} correct';
$string['activityname']       = 'LeitnerFlow';
$string['alllearned']         = 'All questions learned!';
$string['animationdelay']            = 'Animation delay';
$string['animationdelay_help']       = 'How long the feedback message and box animation are shown before the next question loads automatically. Only applies to feedback styles other than "Detailed" (which waits for a button click). Shorter delays speed up sessions; longer delays give students more time to read the feedback.';
$string['avgcorrect']         = 'Average: {$a}% correct';
$string['avglearnedpercent']  = 'Avg. % learned';
$string['backtooverview']     = 'Back to overview';
$string['box_1']       = 'Box 1 – New / Errors';
$string['box_learned'] = 'Learned';
$string['box_n']       = 'Box {$a}';
$string['boxcount']                  = 'Number of boxes';
$string['boxcount_help']             = 'How many Leitner boxes (levels) to use. Cards progress from Box 1 → Box N → Learned. More boxes = finer-grained spacing between reviews. 3 boxes is a good default; 5 gives more granularity for larger question pools.';
$string['boxdistribution']    = 'Leitner box distribution';
$string['cancelsession']      = 'Cancel session';
$string['cardbackone']        = 'Card moved back one box.';
$string['cardlearned']        = 'Card marked as learned!';
$string['cardreset']          = 'Card reset to box 1.';
$string['cardselection']             = 'Card selection';
$string['cardstatus_box']     = 'Box {$a}';
$string['cardstatus_learned'] = 'Learned';
$string['continuesession']    = 'Continue session';
$string['correct']            = 'correct';
$string['correctanswer']      = 'Correct answer';
$string['correctrate']        = 'Correct';
$string['correcttolearn']            = 'Correct answers required';
$string['correcttolearn_help']       = 'Total correct answers needed before a card is marked as "learned" and removed from active rotation. Higher values mean more repetition. Example: with 3, a student must answer correctly 3 times (across all sessions) before the card graduates.';
$string['current']            = 'current';
$string['detailed_correct']          = 'Correct! The card moves from Box {$a->from} to Box {$a->to}.';
$string['detailed_correct_stay']     = 'Correct! The card stays in Box {$a}.';
$string['detailed_learned']          = 'Correct! This card is now fully learned!';
$string['detailed_wrong_back']       = 'Incorrect. The card moves back from Box {$a->from} to Box {$a->to}.';
$string['detailed_wrong_stay']       = 'Incorrect. The card stays in Box {$a}.';
$string['displaysettings']           = 'Display';
$string['eledialeitnerflow:addinstance']   = 'Add a new LeitnerFlow activity';
$string['eledialeitnerflow:attempt']       = 'Attempt Leitner Flow';
$string['eledialeitnerflow:manage']        = 'Manage Leitner Flow settings';
$string['eledialeitnerflow:resetprogress'] = 'Reset student progress';
$string['eledialeitnerflow:view']          = 'View Leitner Flow';
$string['eledialeitnerflow:viewreport']    = 'View teacher overview';
$string['encourage_correct_1']       = 'Great job! Moving to Box {$a}.';
$string['encourage_correct_2']       = 'Nice one — keep it up!';
$string['encourage_correct_3']       = 'Correct! The card advances to Box {$a}.';
$string['encourage_correct_4']       = 'Looking good! Box {$a} is filling up.';
$string['encourage_correct_5']       = 'Perfect — next level!';
$string['encourage_learned_1']       = 'Nailed it! This card is now learned!';
$string['encourage_learned_2']       = 'Mastered! Into the Learned pile!';
$string['encourage_learned_3']       = 'Brilliant — one less card to study!';
$string['encourage_wrong_back_1']    = 'Not quite — back to Box {$a}.';
$string['encourage_wrong_back_2']    = 'Oops, the card slips back.';
$string['encourage_wrong_back_3']    = 'No worries — you\'ll get it next time!';
$string['encourage_wrong_back_4']    = 'Almost! Back one step to Box {$a}.';
$string['encourage_wrong_stay_1']    = 'Close! The card stays in Box {$a}.';
$string['encourage_wrong_stay_2']    = 'Keep practising — it stays where it is.';
$string['endsession']         = 'End session';
$string['error_noattempt'] = 'You do not have permission to attempt this quiz.';
$string['event_progress_reset']    = 'Student progress reset';
$string['event_session_completed'] = 'Learning session completed';
$string['event_session_started']   = 'Learning session started';
$string['feedbackstyle']             = 'Feedback style';
$string['feedbackstyle_animated']    = 'Animated';
$string['feedbackstyle_detailed']    = 'Detailed';
$string['feedbackstyle_gamified']    = 'Gamified';
$string['feedbackstyle_help']        = 'Controls how students receive feedback after answering a question.<br><b>Off</b>: no feedback, advances to the next question immediately.<br><b>Minimal</b>: brief factual message ("Card → Box 3"), fades after 2 seconds.<br><b>Animated</b>: motivational messages that vary each time, plus a glow effect on the target box.<br><b>Detailed</b>: shows the correct answer and the box change (from → to). Stays visible until the student clicks "Next question".<br><b>Gamified</b>: points, streak counter, and celebrations at milestones. Great for younger learners.';
$string['feedbackstyle_minimal']     = 'Minimal';
$string['feedbackstyle_off']         = 'Off';
$string['finishsession']      = 'Finish session';
$string['grademethod']               = 'Grading';
$string['grademethod_none']          = 'No grade';
$string['grademethod_percent']       = '% of cards learned';
$string['gradepassmustbenonnegative'] = 'Grade to pass must be zero or higher.';
$string['gradingsettings']           = 'Grading';
$string['incorrect']          = 'Incorrect.';
$string['invalidsession']  = 'Invalid or expired session.';
$string['lastsession']   = 'Last session';
$string['learned']            = 'Learned';
$string['leitnersettings']           = 'Leitner system';
$string['milestone_10learned']       = '10 cards learned!';
$string['milestone_5learned']        = '5 cards learned!';
$string['milestone_streak10']        = '10 in a row! Unstoppable!';
$string['milestone_streak3']         = '3 in a row!';
$string['milestone_streak5']         = '5 in a row! On fire!';
$string['modulename']           = 'LeitnerFlow';
$string['modulename_help']      = 'LeitnerFlow uses the proven spaced repetition method by Sebastian Leitner to help students learn efficiently. Questions from the Question Bank become virtual flashcards that move through a series of boxes. Correct answers advance a card to the next box; wrong answers send it back. Cards in lower boxes appear more frequently, so students focus on what they find hardest. Once a card has been answered correctly enough times, it graduates to "Learned". Students work in short, focused sessions and can track their progress over time through a visual dashboard with box distribution, progress bar, and session history.';
$string['modulenameplural']     = 'LeitnerFlow';
$string['movedtobox']         = 'Card moved to box {$a}';
$string['newsession']         = 'New session';
$string['nextaftercheck']     = 'Next question after Check';
$string['nextquestion']       = 'Next question';
$string['nextquestionbtn']           = 'Next question';
$string['nocards']             = 'No cards in Box {$a} right now.';
$string['nocardsinpool']      = 'No questions found in the selected category. Please add questions to the Question Bank first.';
$string['nocardsinthisbox']   = 'No cards in Box {$a} right now.';
$string['nocategory']      = 'No question category configured. Please edit the activity settings.';
$string['nosessionactive']    = 'No active session';
$string['nosessions']         = 'No completed sessions yet.';
$string['nostudents']    = 'No students have started this activity yet.';
$string['nounlearnedcards']   = 'All cards are already learned! You can reset your progress to start over.';
$string['open']               = 'Open';
$string['pluginadministration'] = 'LeitnerFlow administration';
$string['pluginname']           = 'LeitnerFlow';
$string['points']                    = '{$a} points';
$string['practiceboxn']       = 'Practice Box {$a}';
$string['prioritystrategy']          = 'Card selection';
$string['prioritystrategy_help']     = '<b>Prioritise lower boxes</b>: questions in Box 1 (least known) are shown first — good for focused review.<br><b>Mixed random</b>: questions from all boxes are mixed randomly — good for variety.';
$string['prioritystrategy_mixed']    = 'Mixed random';
$string['prioritystrategy_prio']     = 'Lower boxes first';
$string['privacy:metadata']                                    = 'The Leitner Flow plugin stores per-student card states and session data.';
$string['privacy:metadata:eledialeitnerflow_card_state']             = 'Tracks each student\'s progress per question (current box, correct count, status).';
$string['privacy:metadata:eledialeitnerflow_card_state:attemptcount'] = 'Total number of attempts.';
$string['privacy:metadata:eledialeitnerflow_card_state:correctcount'] = 'How many times the student answered correctly.';
$string['privacy:metadata:eledialeitnerflow_card_state:currentbox']  = 'The current Leitner box the card is in.';
$string['privacy:metadata:eledialeitnerflow_card_state:questionid']  = 'The ID of the question.';
$string['privacy:metadata:eledialeitnerflow_card_state:status']      = 'Card status: open, learned, or has errors.';
$string['privacy:metadata:eledialeitnerflow_card_state:userid']      = 'The ID of the student.';
$string['privacy:metadata:eledialeitnerflow_sessions']               = 'Records each study session a student completes.';
$string['privacy:metadata:eledialeitnerflow_sessions:questionsasked'] = 'Number of questions in the session.';
$string['privacy:metadata:eledialeitnerflow_sessions:questionscorrect'] = 'Number of correct answers in the session.';
$string['privacy:metadata:eledialeitnerflow_sessions:timecompleted'] = 'When the session was completed.';
$string['privacy:metadata:eledialeitnerflow_sessions:timecreated']   = 'When the session started.';
$string['privacy:metadata:eledialeitnerflow_sessions:userid']        = 'The ID of the student.';
$string['progressreset'] = 'Progress has been reset.';
$string['questioncategory']          = 'Question categories';
$string['questioncategory_help']     = 'Select one or more Question Bank categories from which cards are drawn. All questions in the selected categories (excluding sub-categories) become flashcards. Create categories via the course Question Bank.';
$string['questionrotation']          = 'Question rotation';
$string['questionrotation_dynamic']  = 'Dynamic';
$string['questionrotation_fixed']    = 'Fixed pool';
$string['questionrotation_help']     = '<b>Dynamic</b>: questions are always fetched fresh from the Question Bank — new questions appear automatically, deleted ones disappear.<br><b>Fixed</b>: the question pool is locked when the student first starts. Later changes to the bank are not reflected.';
$string['questionsasked']            = 'Questions asked';
$string['questionsinpool']    = 'Questions in pool';
$string['report']        = 'All participants overview';
$string['resetandrestart']    = 'Reset and start over';
$string['resetconfirm']  = 'Are you sure you want to reset all progress for {$a}? This cannot be undone.';
$string['resetprogress'] = 'Reset progress';
$string['sessioncancelled']   = 'Session cancelled.';
$string['sessioncomplete']    = 'Session complete!';
$string['sessioncorrectof']   = '{$a->correct} / {$a->total}';
$string['sessionduration']    = 'Duration';
$string['sessionhistory']     = 'My sessions';
$string['sessioninprogress']  = 'Session in progress';
$string['sessionpercent']     = '{$a}%';
$string['sessionresult']      = 'You answered {$a->correct} of {$a->total} questions correctly.';
$string['sessionsettings']           = 'Session';
$string['sessionsize']               = 'Questions per session';
$string['sessionsize_help']          = 'Maximum number of questions shown in one study session. Students can always finish early. Recommended: 5–20 for short, focused practice.';
$string['showanimation']             = 'Card animation';
$string['showanimation_help']        = 'When enabled, a brief visual animation shows which Leitner box the card moved to after answering a question. When disabled, the next question appears immediately.';
$string['showtour']                  = 'Show intro tour';
$string['showtour_help']             = 'When enabled, students see a guided tour explaining the LeitnerFlow interface on their first visit. The tour is managed by Moodle\'s User Tours system and is shown once per user across the entire site (not per course). Disable this if you prefer to introduce the activity yourself.';
$string['startsession']       = 'Start study session';
$string['streakbroken']              = 'Streak ended';
$string['streakcounter']             = '{$a}x streak!';
$string['totalsessions']      = '{$a} sessions completed';
$string['trend_recent']       = 'Last 3 sessions: {$a->recent}% correct (average: {$a->avg}%)';
$string['viewreport']         = 'All participants overview';
$string['witherrors']         = 'With errors';
$string['wrongbehavior']             = 'On wrong answer';
$string['wrongbehavior_back1']       = 'Back one box';
$string['wrongbehavior_help']        = 'What happens when a student answers incorrectly:<br><b>Reset to Box 1</b> — strictest, card restarts from scratch.<br><b>Back one box</b> — moderate, card drops one level.<br><b>No change</b> — lenient, card stays in current box but correct-count does not increase.';
$string['wrongbehavior_nochange']    = 'No change';
$string['wrongbehavior_reset']       = 'Reset to Box 1';
$string['yourprogress']       = 'My learning progress';
