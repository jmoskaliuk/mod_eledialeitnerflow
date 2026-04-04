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
 * Leitner engine — implements the spaced repetition logic.
 *
 * @package    mod_leitnerflow
 * @copyright  2024 eLeDia GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_leitnerflow\engine;

defined('MOODLE_INTERNAL') || die();

/**
 * Leitner engine — implements the spaced repetition logic.
 *
 * Box transitions:
 *   - Correct answer: correctcount++. If correctcount >= correcttolearn → learned.
 *                     Otherwise: box = ceil(correctcount / threshold_per_box).
 *   - Wrong answer:   depends on wrongbehavior setting.
 *
 * Box assignment:
 *   correctcount 0            → box 1
 *   correctcount 1..N-1       → box 2..boxcount (evenly spread)
 *   correctcount >= threshold → learned
 */
class leitner_engine {

    /** Card status constants */
    const STATUS_OPEN    = 0;
    const STATUS_LEARNED = 1;
    const STATUS_ERROR   = 2;

    /** Wrong-answer behavior constants */
    const WRONG_RESET   = 0; // reset correctcount to 0 → box 1
    const WRONG_BACK1   = 1; // subtract one step
    const WRONG_NOCHANGE = 2; // no change to correctcount

    /**
     * Calculate which box a card belongs to based on its correctcount.
     *
     * @param int $correctcount
     * @param int $correcttolearn
     * @param int $boxcount
     * @return int box number (1-based)
     */
    public static function calculate_box(int $correctcount, int $correcttolearn, int $boxcount): int {
        if ($correctcount <= 0) {
            return 1;
        }
        if ($correctcount >= $correcttolearn) {
            return $boxcount; // Will be marked learned separately
        }
        // Spread correctcount 1..($correcttolearn-1) across boxes 1..$boxcount
        $step = ($correcttolearn > 1) ? ($correcttolearn - 1) / $boxcount : 1;
        $box  = (int) ceil($correctcount / $step);
        return max(1, min($box, $boxcount));
    }

    /**
     * Process a student's answer and return the updated card state.
     *
     * @param \stdClass $state  The current leitnerflow_card_state record (or null if new)
     * @param bool      $correct Whether the answer was correct
     * @param \stdClass $leitnerflow  The leitnerflow instance settings
     * @param int       $questionid
     * @param int       $userid
     * @return \stdClass Updated state object (not yet saved to DB)
     */
    public static function process_answer(
        ?\stdClass $state,
        bool $correct,
        \stdClass $leitnerflow,
        int $questionid,
        int $userid
    ): \stdClass {
        global $DB;

        $now = time();

        if ($state === null) {
            // First attempt for this card
            $state                = new \stdClass();
            $state->leitnerflowid = $leitnerflow->id;
            $state->userid        = $userid;
            $state->questionid    = $questionid;
            $state->correctcount  = 0;
            $state->attemptcount  = 0;
            $state->currentbox    = 1;
            $state->status        = self::STATUS_OPEN;
            $state->timecreated   = $now;
        }

        $state->attemptcount++;
        $state->timemodified = $now;

        if ($correct) {
            $state->correctcount++;
            // Check if learned
            if ($state->correctcount >= $leitnerflow->correcttolearn) {
                $state->status     = self::STATUS_LEARNED;
                $state->currentbox = $leitnerflow->boxcount;
            } else {
                $state->status     = self::STATUS_OPEN;
                $state->currentbox = self::calculate_box(
                    $state->correctcount,
                    $leitnerflow->correcttolearn,
                    $leitnerflow->boxcount
                );
            }
        } else {
            // Wrong answer — apply configured behavior
            switch ((int) $leitnerflow->wrongbehavior) {
                case self::WRONG_RESET:
                    $state->correctcount = 0;
                    $state->currentbox   = 1;
                    $state->status       = self::STATUS_ERROR;
                    break;

                case self::WRONG_BACK1:
                    $state->correctcount = max(0, $state->correctcount - 1);
                    $state->currentbox   = self::calculate_box(
                        $state->correctcount,
                        $leitnerflow->correcttolearn,
                        $leitnerflow->boxcount
                    );
                    $state->status = ($state->correctcount === 0) ? self::STATUS_ERROR : self::STATUS_OPEN;
                    break;

                case self::WRONG_NOCHANGE:
                default:
                    // correctcount stays, but status reflects error for display
                    $state->status = self::STATUS_ERROR;
                    break;
            }
        }

        return $state;
    }

    /**
     * Persist the card state to the database (insert or update).
     *
     * @param \stdClass $state
     * @return \stdClass State with updated id field
     */
    public static function save_card_state(\stdClass $state): \stdClass {
        global $DB;

        if (!empty($state->id)) {
            $DB->update_record('leitnerflow_card_state', $state);
        } else {
            $state->id = $DB->insert_record('leitnerflow_card_state', $state);
        }
        return $state;
    }

    /**
     * Get card state for a specific user/question, or null if not attempted.
     *
     * @param int $leitnerflowid
     * @param int $userid
     * @param int $questionid
     * @return \stdClass|null
     */
    public static function get_card_state(int $leitnerflowid, int $userid, int $questionid): ?\stdClass {
        global $DB;
        return $DB->get_record('leitnerflow_card_state', [
            'leitnerflowid' => $leitnerflowid,
            'userid'        => $userid,
            'questionid'    => $questionid,
        ]) ?: null;
    }

    /**
     * Get all card states for a user in one quiz instance.
     *
     * @param int $leitnerflowid
     * @param int $userid
     * @return array questionid => state record
     */
    public static function get_all_card_states(int $leitnerflowid, int $userid): array {
        global $DB;
        $records = $DB->get_records('leitnerflow_card_state', [
            'leitnerflowid' => $leitnerflowid,
            'userid'        => $userid,
        ]);
        $indexed = [];
        foreach ($records as $r) {
            $indexed[$r->questionid] = $r;
        }
        return $indexed;
    }

    /**
     * Get the list of question category IDs from a leitnerflow instance.
     *
     * Handles both the new questioncategoryids (comma-separated) field
     * and the legacy questioncategoryid (single int) field.
     *
     * @param \stdClass $leitnerflow
     * @return array of category IDs
     */
    public static function get_category_ids(\stdClass $leitnerflow): array {
        // Prefer the new multi-category field.
        if (!empty($leitnerflow->questioncategoryids)) {
            $ids = array_map('intval', explode(',', $leitnerflow->questioncategoryids));
            return array_filter($ids, fn($id) => $id > 0);
        }
        // Fall back to legacy single field.
        if (!empty($leitnerflow->questioncategoryid) && (int) $leitnerflow->questioncategoryid > 0) {
            return [(int) $leitnerflow->questioncategoryid];
        }
        return [];
    }

    /**
     * Get all question IDs from a question category (direct members, no sub-categories).
     *
     * Uses the Moodle 4.x+ Question Bank API (question_bank_entries + question_versions)
     * instead of querying the {question} table directly.
     *
     * @param int $categoryid
     * @return array of question IDs
     */
    public static function get_questions_from_category(int $categoryid): array {
        global $DB;
        if ($categoryid <= 0) {
            return [];
        }

        // Join question_bank_entries → question_versions → question
        // to get the latest version of each question in this category.
        $sql = "SELECT q.id
                  FROM {question} q
                  JOIN {question_versions} qv ON qv.questionid = q.id
                  JOIN {question_bank_entries} qbe ON qbe.id = qv.questionbankentryid
                 WHERE qbe.questioncategoryid = :categoryid
                   AND qv.status = :status
                   AND qv.version = (
                       SELECT MAX(qv2.version)
                         FROM {question_versions} qv2
                        WHERE qv2.questionbankentryid = qv.questionbankentryid
                   )
              ORDER BY q.id";

        $params = [
            'categoryid' => $categoryid,
            'status' => 'ready',
        ];

        return array_keys($DB->get_records_sql($sql, $params));
    }

    /**
     * Get all question IDs from multiple question categories.
     *
     * @param array $categoryids Array of category IDs
     * @return array of unique question IDs
     */
    public static function get_questions_from_categories(array $categoryids): array {
        $allids = [];
        foreach ($categoryids as $catid) {
            $ids = self::get_questions_from_category((int) $catid);
            $allids = array_merge($allids, $ids);
        }
        return array_values(array_unique($allids));
    }

    /**
     * Select questions for a session using the Leitner priority algorithm.
     *
     * Priority order (when prioritystrategy = 0):
     *   1. Never-attempted questions (treated as box 1)
     *   2. Box 1 cards (most errors / least known)
     *   3. Box 2, 3 … N-1 cards
     *   Skip: learned (status=1) cards
     *
     * @param \stdClass $leitnerflow
     * @param int       $userid
     * @return array  Selected question IDs (max: $leitnerflow->sessionsize)
     */
    public static function select_session_questions(\stdClass $leitnerflow, int $userid): array {
        $categoryids = self::get_category_ids($leitnerflow);
        $allids      = self::get_questions_from_categories($categoryids);
        $states    = self::get_all_card_states($leitnerflow->id, $userid);
        $sessionsize = (int) $leitnerflow->sessionsize;

        // Separate into buckets
        $buckets = []; // box => [questionids]
        $boxcount = (int) $leitnerflow->boxcount;
        for ($b = 1; $b <= $boxcount; $b++) {
            $buckets[$b] = [];
        }

        foreach ($allids as $qid) {
            if (isset($states[$qid])) {
                $s = $states[$qid];
                if ((int) $s->status === self::STATUS_LEARNED) {
                    continue; // skip learned
                }
                $buckets[(int) $s->currentbox][] = $qid;
            } else {
                // Never attempted → box 1
                $buckets[1][] = $qid;
            }
        }

        // Shuffle within each bucket
        foreach ($buckets as &$bucket) {
            shuffle($bucket);
        }
        unset($bucket);

        if ((int) $leitnerflow->prioritystrategy === 1) {
            // Mixed strategy: merge all and take randomly
            $all = array_merge(...array_values($buckets));
            shuffle($all);
            return array_slice($all, 0, $sessionsize);
        }

        // Priority strategy: fill from box 1 upwards
        $selected = [];
        for ($b = 1; $b <= $boxcount && count($selected) < $sessionsize; $b++) {
            $needed = $sessionsize - count($selected);
            $take   = array_slice($buckets[$b], 0, $needed);
            $selected = array_merge($selected, $take);
        }

        return $selected;
    }

    /**
     * Aggregate progress statistics for a user.
     *
     * @param int $leitnerflowid
     * @param int $userid
     * @param int|array $questioncategoryid Single category ID or array of IDs
     * @return \stdClass  {total, learned, open, errors, percent_learned}
     */
    public static function get_user_stats(int $leitnerflowid, int $userid, int|array $questioncategoryid): \stdClass {
        if (is_array($questioncategoryid)) {
            $allids = self::get_questions_from_categories($questioncategoryid);
        } else {
            $allids = self::get_questions_from_category($questioncategoryid);
        }
        $states = self::get_all_card_states($leitnerflowid, $userid);

        $total   = count($allids);
        $learned = 0;
        $errors  = 0;

        foreach ($allids as $qid) {
            if (isset($states[$qid])) {
                $s = $states[$qid];
                if ((int) $s->status === self::STATUS_LEARNED) {
                    $learned++;
                } elseif ((int) $s->status === self::STATUS_ERROR) {
                    $errors++;
                }
            }
        }

        $open = $total - $learned - $errors;

        $stats = new \stdClass();
        $stats->total           = $total;
        $stats->learned         = $learned;
        $stats->open            = max(0, $open);
        $stats->errors          = $errors;
        $stats->percent_learned = ($total > 0) ? round(($learned / $total) * 100) : 0;

        return $stats;
    }

    /**
     * Get per-box distribution for display.
     *
     * @param int $leitnerflowid
     * @param int $userid
     * @param int|array $questioncategoryid Single category ID or array of IDs
     * @param int $boxcount
     * @return array  [box_number => count]
     */
    public static function get_box_distribution(
        int $leitnerflowid,
        int $userid,
        int|array $questioncategoryid,
        int $boxcount
    ): array {
        if (is_array($questioncategoryid)) {
            $allids = self::get_questions_from_categories($questioncategoryid);
        } else {
            $allids = self::get_questions_from_category($questioncategoryid);
        }
        $states  = self::get_all_card_states($leitnerflowid, $userid);
        $dist    = array_fill(1, $boxcount, 0);

        foreach ($allids as $qid) {
            if (isset($states[$qid]) && (int) $states[$qid]->status !== self::STATUS_LEARNED) {
                $box = (int) $states[$qid]->currentbox;
                if (isset($dist[$box])) {
                    $dist[$box]++;
                }
            } else if (!isset($states[$qid])) {
                $dist[1]++;
            }
        }

        return $dist;
    }

    /**
     * Get stats for all enrolled students (teacher report).
     *
     * @param \stdClass $leitnerflow
     * @param int       $courseid
     * @param \context  $context
     * @return array of objects with student info + stats
     */
    public static function get_all_students_stats(\stdClass $leitnerflow, int $courseid, \context $context): array {
        global $DB;

        $students = get_enrolled_users($context, 'mod/leitnerflow:attempt');
        $categoryids = self::get_category_ids($leitnerflow);
        $result   = [];

        foreach ($students as $student) {
            $stats = self::get_user_stats(
                $leitnerflow->id,
                $student->id,
                $categoryids
            );

            // Session count + last session
            $sessions = $DB->count_records('leitnerflow_sessions', [
                'leitnerflowid' => $leitnerflow->id,
                'userid'        => $student->id,
                'status'        => 1,
            ]);
            $lastsession = $DB->get_field_sql(
                "SELECT MAX(timecompleted) FROM {leitnerflow_sessions}
                  WHERE leitnerflowid = ? AND userid = ? AND status = 1",
                [$leitnerflow->id, $student->id]
            );

            $entry = new \stdClass();
            $entry->userid       = $student->id;
            $entry->user         = $student; // Full user object for user_picture().
            $entry->fullname     = fullname($student);
            $entry->stats        = $stats;
            $entry->sessions     = (int) $sessions;
            $entry->lastsession  = $lastsession ?: null;

            $result[] = $entry;
        }

        return $result;
    }

    /**
     * Get completed session history for a user, most recent first.
     *
     * @param int $leitnerflowid
     * @param int $userid
     * @param int $limit Maximum sessions to return (0 = all)
     * @return array of session records
     */
    public static function get_session_history(int $leitnerflowid, int $userid, int $limit = 10): array {
        global $DB;

        $sql = "SELECT id, questionsasked, questionscorrect, timecreated, timecompleted
                  FROM {leitnerflow_sessions}
                 WHERE leitnerflowid = :lfid
                   AND userid = :uid
                   AND status = 1
              ORDER BY timecompleted DESC";

        $params = ['lfid' => $leitnerflowid, 'uid' => $userid];

        if ($limit > 0) {
            return array_values($DB->get_records_sql($sql, $params, 0, $limit));
        }
        return array_values($DB->get_records_sql($sql, $params));
    }

    /**
     * Get aggregate session statistics for a user.
     *
     * @param int $leitnerflowid
     * @param int $userid
     * @return \stdClass {sessioncount, totalasked, totalcorrect, avgpercent}
     */
    public static function get_session_stats(int $leitnerflowid, int $userid): \stdClass {
        global $DB;

        $sql = "SELECT COUNT(*) AS sessioncount,
                       COALESCE(SUM(questionsasked), 0) AS totalasked,
                       COALESCE(SUM(questionscorrect), 0) AS totalcorrect
                  FROM {leitnerflow_sessions}
                 WHERE leitnerflowid = :lfid
                   AND userid = :uid
                   AND status = 1";

        $row = $DB->get_record_sql($sql, ['lfid' => $leitnerflowid, 'uid' => $userid]);

        $stats = new \stdClass();
        $stats->sessioncount = (int) $row->sessioncount;
        $stats->totalasked   = (int) $row->totalasked;
        $stats->totalcorrect = (int) $row->totalcorrect;
        $stats->avgpercent   = ($stats->totalasked > 0)
            ? round(($stats->totalcorrect / $stats->totalasked) * 100)
            : 0;

        return $stats;
    }

    /**
     * Delete all data for a specific user in a quiz (used for privacy + reset).
     *
     * @param int $leitnerflowid
     * @param int $userid
     */
    public static function delete_user_data(int $leitnerflowid, int $userid): void {
        global $DB;

        // Sessions reference question_usages via qubaid — clean those up too.
        $sessions = $DB->get_records('leitnerflow_sessions', [
            'leitnerflowid' => $leitnerflowid,
            'userid'        => $userid,
        ]);
        foreach ($sessions as $session) {
            if (!empty($session->qubaid)) {
                \question_engine::delete_questions_usage_by_activity($session->qubaid);
            }
        }

        $DB->delete_records('leitnerflow_sessions',   ['leitnerflowid' => $leitnerflowid, 'userid' => $userid]);
        $DB->delete_records('leitnerflow_card_state', ['leitnerflowid' => $leitnerflowid, 'userid' => $userid]);
    }
}
