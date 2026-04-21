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
 * PHPUnit tests for the Leitner engine.
 *
 * Run all:  vendor/bin/phpunit mod/eledialeitnerflow/tests/engine/leitner_engine_test.php
 * Verbose:  vendor/bin/phpunit --testdox mod/eledialeitnerflow/tests/engine/leitner_engine_test.php
 *
 * @package    mod_eledialeitnerflow
 * @copyright  2026 eLeDia GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_eledialeitnerflow;

use mod_eledialeitnerflow\engine\leitner_engine;

/**
 * Unit tests for the Leitner engine.
 *
 * @package    mod_eledialeitnerflow
 * @category   test
 * @copyright  2026 eLeDia GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(leitner_engine::class)]
final class leitner_engine_test extends \advanced_testcase {
    // phpcs:disable moodle.PHPUnit.TestCaseCovers.Missing

    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * Build a minimal leitnerflow stdClass (no DB needed for pure-logic tests).
     *
     * @param array $overrides Optional key-value pairs to override defaults.
     * @return \stdClass
     */
    private function make_lq(array $overrides = []): \stdClass {
        return (object) array_merge([
            'id'                 => 1,
            'questioncategoryid' => 99,
            'sessionsize'        => 10,
            'boxcount'           => 3,
            'correcttolearn'     => 3,
            'wrongbehavior'      => leitner_engine::WRONG_RESET,
            'questionrotation'   => 1,
            'prioritystrategy'   => 0,
        ], $overrides);
    }

    /**
     * Create a real leitnerflow instance in the DB using the generator.
     *
     * @param array $overrides Optional key-value pairs to override instance defaults.
     * @return \stdClass Full eledialeitnerflow DB record.
     */
    private function create_lq(array $overrides = []): \stdClass {
        $course = $this->getDataGenerator()->create_course();
        $gen    = $this->getDataGenerator()->get_plugin_generator('mod_eledialeitnerflow');
        $lq     = $gen->create_instance(array_merge(['course' => $course->id], $overrides));

        global $DB;
        return $DB->get_record('eledialeitnerflow', ['id' => $lq->id], '*', MUST_EXIST);
    }

    // Group 1: calculate_box() — pure function, no DB.

    /**
     * Test calculate_box method with various correct-count and box-count combinations.
     *
     * @param int $correctcount Number of correct answers so far.
     * @param int $correcttolearn Threshold to mark card as learned.
     * @param int $boxcount Total number of boxes.
     * @param int $expectedbox Expected box number (1-based).
     * @param string $description Human-readable scenario description.
     * @dataProvider box_calculation_cases
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('box_calculation_cases')]
    public function test_calculate_box(
        int $correctcount,
        int $correcttolearn,
        int $boxcount,
        int $expectedbox,
        string $description
    ): void {
        $box = leitner_engine::calculate_box($correctcount, $correcttolearn, $boxcount);
        $this->assertEquals($expectedbox, $box, $description);
    }

    /**
     * Data provider: various correct-count / threshold / box-count combinations.
     *
     * @return array Format: [correctcount, correcttolearn, boxcount, expected_box, description]
     */
    public static function box_calculation_cases(): array {
        return [
            // 3 boxes, threshold 3.
            '0 correct, 3 boxes → box 1'    => [0, 3, 3, 1, '0 correct stays in box 1'],
            '1 correct, 3 boxes → box 1'    => [1, 3, 3, 1, '1 of 3 → still box 1'],
            '2 correct, 3 boxes → box 2'    => [2, 3, 3, 2, '2 of 3 → box 2'],
            'at threshold → last box'        => [3, 3, 3, 3, 'At threshold → last box (learned separately)'],
            // 5 boxes, threshold 5.
            '0 correct, 5 boxes → box 1'    => [0, 5, 5, 1, 'Zero always box 1'],
            '1 correct, 5 boxes → box 1'    => [1, 5, 5, 1, '1/5 → box 1'],
            '3 correct, 5 boxes → box 3'    => [3, 5, 5, 3, '3/5 → box 3'],
            '4 correct, 5 boxes → box 4'    => [4, 5, 5, 4, '4/5 → box 4'],
            // Edge: negative input clamped to 1.
            'negative correct → box 1'      => [-1, 3, 3, 1, 'Negative clamped to box 1'],
        ];
    }

    // Group 2: process_answer() — correct answers.

    /**
     * Correct answer on a new card increments correctcount to 1.
     */
    public function test_correct_answer_on_new_card_increments_correctcount(): void {
        $lq    = $this->make_lq(['correcttolearn' => 3, 'boxcount' => 3]);
        $state = leitner_engine::process_answer(null, true, $lq, 42, 1);

        $this->assertEquals(1, $state->correctcount, 'correctcount should be 1 after first correct answer');
        $this->assertEquals(1, $state->attemptcount, 'attemptcount should be 1');
        $this->assertEquals(leitner_engine::STATUS_OPEN, $state->status, 'Not yet learned after 1/3');
    }

    /**
     * Two consecutive correct answers advance the card from box 1 to box 2.
     */
    public function test_correct_answer_advances_card_to_next_box(): void {
        $lqdb = $this->create_lq(['correcttolearn' => 3, 'boxcount' => 3]);

        $state = leitner_engine::process_answer(null, true, $lqdb, 1, 1);
        $this->assertEquals(1, $state->currentbox, 'After 1st correct: still box 1 (threshold spread)');

        $state = leitner_engine::process_answer($state, true, $lqdb, 1, 1);
        $this->assertEquals(2, $state->currentbox, 'After 2nd correct: box 2');
    }

    /**
     * Reaching the correcttolearn threshold marks the card as learned.
     */
    public function test_reaching_threshold_marks_card_as_learned(): void {
        $lq    = $this->make_lq(['correcttolearn' => 3, 'boxcount' => 3]);
        $state = null;
        for ($i = 0; $i < 3; $i++) {
            $state = leitner_engine::process_answer($state, true, $lq, 1, 1);
        }

        $this->assertEquals(leitner_engine::STATUS_LEARNED, $state->status, 'Card should be LEARNED after 3 correct');
        $this->assertEquals(3, $state->correctcount, 'correctcount should equal threshold');
    }

    /**
     * A learned card is placed in the highest box.
     */
    public function test_learned_card_stays_in_highest_box(): void {
        $lq    = $this->make_lq(['correcttolearn' => 3, 'boxcount' => 3]);
        $state = null;
        for ($i = 0; $i < 3; $i++) {
            $state = leitner_engine::process_answer($state, true, $lq, 1, 1);
        }

        $this->assertEquals($lq->boxcount, $state->currentbox, 'Learned card should be in highest box');
    }

    // Group 3: process_answer() — wrong answers, all 3 behaviors.

    /**
     * Wrong answer with WRONG_RESET behavior resets correctcount and box to 1.
     */
    public function test_wrong_answer_with_reset_behavior(): void {
        $lq = $this->make_lq([
            'correcttolearn' => 3,
            'boxcount'       => 3,
            'wrongbehavior'  => leitner_engine::WRONG_RESET,
        ]);

        $state = leitner_engine::process_answer(null, true, $lq, 1, 1);
        $state = leitner_engine::process_answer($state, true, $lq, 1, 1);
        $this->assertEquals(2, $state->correctcount, 'Setup: 2 correct');

        $state = leitner_engine::process_answer($state, false, $lq, 1, 1);

        $this->assertEquals(0, $state->correctcount, 'WRONG_RESET: correctcount must be 0');
        $this->assertEquals(1, $state->currentbox, 'WRONG_RESET: card must return to box 1');
        $this->assertEquals(leitner_engine::STATUS_ERROR, $state->status, 'WRONG_RESET: status must be ERROR');
    }

    /**
     * Wrong answer with WRONG_BACK1 decrements correctcount by one.
     */
    public function test_wrong_answer_with_back_one_behavior(): void {
        $lq = $this->make_lq([
            'correcttolearn' => 3,
            'boxcount'       => 3,
            'wrongbehavior'  => leitner_engine::WRONG_BACK1,
        ]);

        $state = leitner_engine::process_answer(null, true, $lq, 1, 1);
        $state = leitner_engine::process_answer($state, true, $lq, 1, 1);
        $this->assertEquals(2, $state->correctcount, 'Setup: 2 correct');
        $boxbefore = $state->currentbox;

        $state = leitner_engine::process_answer($state, false, $lq, 1, 1);

        $this->assertEquals(1, $state->correctcount, 'WRONG_BACK1: correctcount decremented by 1');
        $this->assertLessThan($boxbefore, $state->currentbox, 'WRONG_BACK1: box should go back');
    }

    /**
     * Wrong answer with WRONG_NOCHANGE leaves correctcount intact.
     */
    public function test_wrong_answer_with_no_change_behavior(): void {
        $lq = $this->make_lq([
            'correcttolearn' => 3,
            'boxcount'       => 3,
            'wrongbehavior'  => leitner_engine::WRONG_NOCHANGE,
        ]);

        $state              = leitner_engine::process_answer(null, true, $lq, 1, 1);
        $state              = leitner_engine::process_answer($state, true, $lq, 1, 1);
        $correctcountbefore = $state->correctcount;

        $state = leitner_engine::process_answer($state, false, $lq, 1, 1);

        $this->assertEquals(
            $correctcountbefore,
            $state->correctcount,
            'WRONG_NOCHANGE: correctcount must not change'
        );
        $this->assertEquals(
            leitner_engine::STATUS_ERROR,
            $state->status,
            'WRONG_NOCHANGE: status should reflect error'
        );
    }

    /**
     * WRONG_BACK1 on a fresh card (correctcount=0) keeps card at box 1 without underflow.
     */
    public function test_wrong_answer_on_fresh_card_with_back1_stays_at_box_1(): void {
        $lq    = $this->make_lq(['wrongbehavior' => leitner_engine::WRONG_BACK1]);
        $state = leitner_engine::process_answer(null, false, $lq, 1, 1);

        $this->assertEquals(0, $state->correctcount, 'correctcount cannot go below 0');
        $this->assertEquals(1, $state->currentbox, 'Box cannot go below 1');
    }

    /**
     * Correct answers after a reset rebuild progress from scratch.
     */
    public function test_correct_after_reset_rebuilds_progress(): void {
        $lq    = $this->make_lq(['correcttolearn' => 3, 'wrongbehavior' => leitner_engine::WRONG_RESET]);
        $state = leitner_engine::process_answer(null, true, $lq, 1, 1);
        $state = leitner_engine::process_answer($state, false, $lq, 1, 1);
        $this->assertEquals(0, $state->correctcount, 'After reset: 0');

        $state = leitner_engine::process_answer($state, true, $lq, 1, 1);
        $this->assertEquals(1, $state->correctcount, 'Rebuilding after reset works');
    }

    // Group 4: DB persistence — save_card_state() + get_card_state().

    /**
     * save_card_state() inserts a new record and get_card_state() retrieves it.
     */
    public function test_save_and_reload_card_state(): void {
        $lqdb = $this->create_lq();
        $user  = $this->getDataGenerator()->create_user();
        $lq    = $this->make_lq(['id' => $lqdb->id]);

        $state = leitner_engine::process_answer(null, true, $lq, 77, $user->id);
        $state = leitner_engine::save_card_state($state);

        $this->assertGreaterThan(0, $state->id, 'Saved state must have a DB id');

        $loaded = leitner_engine::get_card_state($lqdb->id, $user->id, 77);
        $this->assertNotNull($loaded, 'State must be retrievable from DB');
        $this->assertEquals(1, $loaded->correctcount, 'correctcount persisted correctly');
        $this->assertEquals($user->id, (int) $loaded->userid, 'userid persisted correctly');
    }

    /**
     * A second save_card_state() call updates the existing record instead of inserting.
     */
    public function test_save_card_state_updates_existing_record(): void {
        $lqdb = $this->create_lq();
        $user  = $this->getDataGenerator()->create_user();
        $lq    = $this->make_lq(['id' => $lqdb->id]);

        $state   = leitner_engine::process_answer(null, true, $lq, 5, $user->id);
        $state   = leitner_engine::save_card_state($state);
        $firstid = $state->id;

        $state = leitner_engine::process_answer($state, true, $lq, 5, $user->id);
        $state = leitner_engine::save_card_state($state);

        $this->assertEquals($firstid, $state->id, 'Must UPDATE same record, not INSERT a new one');
        $this->assertEquals(2, leitner_engine::get_card_state($lqdb->id, $user->id, 5)->correctcount);
    }

    /**
     * get_card_state() returns null for a card that has never been attempted.
     */
    public function test_get_card_state_returns_null_for_unknown(): void {
        $result = leitner_engine::get_card_state(999, 999, 999);
        $this->assertNull($result, 'Unknown card state should return null, not false or empty object');
    }

    // Group 5: get_all_card_states() and status counts.

    /**
     * Card states created via the generator are correctly counted by status.
     */
    public function test_get_user_stats_counts_correctly(): void {
        $lqdb = $this->create_lq();
        $user  = $this->getDataGenerator()->create_user();
        $gen   = $this->getDataGenerator()->get_plugin_generator('mod_eledialeitnerflow');

        $gen->create_card_state(['eledialeitnerflowid' => $lqdb->id, 'userid' => $user->id,
            'questionid' => 1, 'status' => leitner_engine::STATUS_LEARNED]);
        $gen->create_card_state(['eledialeitnerflowid' => $lqdb->id, 'userid' => $user->id,
            'questionid' => 2, 'status' => leitner_engine::STATUS_LEARNED]);
        $gen->create_card_state(['eledialeitnerflowid' => $lqdb->id, 'userid' => $user->id,
            'questionid' => 3, 'status' => leitner_engine::STATUS_ERROR]);

        $states  = leitner_engine::get_all_card_states($lqdb->id, $user->id);
        $learned = array_filter($states, fn($s) => (int) $s->status === leitner_engine::STATUS_LEARNED);
        $errors  = array_filter($states, fn($s) => (int) $s->status === leitner_engine::STATUS_ERROR);

        $this->assertCount(3, $states, 'Should have 3 card state records');
        $this->assertCount(2, $learned, '2 cards should be learned');
        $this->assertCount(1, $errors, '1 card should have errors');
    }

    /**
     * get_user_stats() returns 0 percent when the question category has no questions.
     */
    public function test_get_user_stats_zero_total_gives_zero_percent(): void {
        $lqdb  = $this->create_lq(['questioncategoryid' => 9999]);
        $user   = $this->getDataGenerator()->create_user();
        $stats  = leitner_engine::get_user_stats($lqdb->id, $user->id, 9999);

        $this->assertEquals(0, $stats->total, 'Total should be 0 when category is empty');
        $this->assertEquals(0, $stats->percent_learned, 'Percentage must be 0, not a division error');
    }

    // Group 6: get_box_distribution().

    /**
     * get_box_distribution() correctly groups cards by box and excludes learned cards.
     */
    public function test_get_box_distribution_groups_cards_correctly(): void {
        $qgen = $this->getDataGenerator()->get_plugin_generator('core_question');
        $qcat = $qgen->create_question_category();
        $q10  = $qgen->create_question('shortanswer', null, ['category' => $qcat->id]);
        $q11  = $qgen->create_question('shortanswer', null, ['category' => $qcat->id]);
        $q12  = $qgen->create_question('shortanswer', null, ['category' => $qcat->id]);
        $q13  = $qgen->create_question('shortanswer', null, ['category' => $qcat->id]);

        $lqdb = $this->create_lq(['boxcount' => 3, 'questioncategoryid' => $qcat->id]);
        $user = $this->getDataGenerator()->create_user();
        $gen  = $this->getDataGenerator()->get_plugin_generator('mod_eledialeitnerflow');

        $gen->create_card_state(['eledialeitnerflowid' => $lqdb->id, 'userid' => $user->id,
            'questionid' => $q10->id, 'currentbox' => 1, 'status' => leitner_engine::STATUS_OPEN]);
        $gen->create_card_state(['eledialeitnerflowid' => $lqdb->id, 'userid' => $user->id,
            'questionid' => $q11->id, 'currentbox' => 1, 'status' => leitner_engine::STATUS_ERROR]);
        $gen->create_card_state(['eledialeitnerflowid' => $lqdb->id, 'userid' => $user->id,
            'questionid' => $q12->id, 'currentbox' => 2, 'status' => leitner_engine::STATUS_OPEN]);
        // Learned card — must be excluded from distribution.
        $gen->create_card_state(['eledialeitnerflowid' => $lqdb->id, 'userid' => $user->id,
            'questionid' => $q13->id, 'currentbox' => 3, 'status' => leitner_engine::STATUS_LEARNED]);

        $dist = leitner_engine::get_box_distribution($lqdb->id, $user->id, $qcat->id, 3);

        $this->assertEquals(2, $dist[1], 'Box 1 should have 2 cards (open + error)');
        $this->assertEquals(1, $dist[2], 'Box 2 should have 1 card');
        $this->assertEquals(0, $dist[3], 'Box 3: learned card must be excluded from distribution');
    }

    // Group 7: Session card eligibility.

    /**
     * Cards set to STATUS_OPEN are all eligible (non-learned).
     */
    public function test_open_cards_are_all_eligible_for_session(): void {
        $lqdb = $this->create_lq(['sessionsize' => 5, 'questioncategoryid' => 9999]);
        $user  = $this->getDataGenerator()->create_user();
        $gen   = $this->getDataGenerator()->get_plugin_generator('mod_eledialeitnerflow');

        foreach ([20, 21, 22] as $qid) {
            $gen->create_card_state(['eledialeitnerflowid' => $lqdb->id, 'userid' => $user->id,
                'questionid' => $qid, 'status' => leitner_engine::STATUS_OPEN]);
        }

        $states     = leitner_engine::get_all_card_states($lqdb->id, $user->id);
        $nonlearned = array_filter($states, fn($s) => (int) $s->status !== leitner_engine::STATUS_LEARNED);

        $this->assertCount(3, $nonlearned, 'All 3 open cards should be eligible');
    }

    /**
     * Learned cards are not counted as non-learned (i.e. excluded from session eligibility).
     */
    public function test_learned_cards_excluded_from_session(): void {
        $lqdb = $this->create_lq(['sessionsize' => 10, 'questioncategoryid' => 9999]);
        $user  = $this->getDataGenerator()->create_user();
        $gen   = $this->getDataGenerator()->get_plugin_generator('mod_eledialeitnerflow');

        foreach ([30, 31] as $qid) {
            $gen->create_card_state(['eledialeitnerflowid' => $lqdb->id, 'userid' => $user->id,
                'questionid' => $qid, 'status' => leitner_engine::STATUS_LEARNED]);
        }
        foreach ([32, 33, 34] as $qid) {
            $gen->create_card_state(['eledialeitnerflowid' => $lqdb->id, 'userid' => $user->id,
                'questionid' => $qid, 'status' => leitner_engine::STATUS_OPEN]);
        }

        $states = leitner_engine::get_all_card_states($lqdb->id, $user->id);
        $open   = array_filter($states, fn($s) => (int) $s->status !== leitner_engine::STATUS_LEARNED);

        $this->assertCount(3, $open, 'Only 3 open cards eligible (2 learned must be excluded)');
    }

    // Group 8: delete_user_data().

    /**
     * delete_user_data() removes card states and sessions for one user only.
     */
    public function test_delete_user_data_removes_all_records(): void {
        global $DB;

        $lqdb  = $this->create_lq();
        $user   = $this->getDataGenerator()->create_user();
        $other  = $this->getDataGenerator()->create_user();
        $gen    = $this->getDataGenerator()->get_plugin_generator('mod_eledialeitnerflow');

        $gen->create_card_state(['eledialeitnerflowid' => $lqdb->id, 'userid' => $user->id, 'questionid' => 1]);
        $gen->create_card_state(['eledialeitnerflowid' => $lqdb->id, 'userid' => $user->id, 'questionid' => 2]);
        $gen->create_card_state(['eledialeitnerflowid' => $lqdb->id, 'userid' => $other->id, 'questionid' => 1]);
        $gen->create_session(['eledialeitnerflowid' => $lqdb->id, 'userid' => $user->id]);
        $gen->create_session(['eledialeitnerflowid' => $lqdb->id, 'userid' => $other->id]);

        leitner_engine::delete_user_data($lqdb->id, $user->id);

        $remainingstates = $DB->count_records(
            'eledialeitnerflow_card_state',
            ['eledialeitnerflowid' => $lqdb->id, 'userid' => $user->id]
        );
        $remainingsessions = $DB->count_records(
            'eledialeitnerflow_sessions',
            ['eledialeitnerflowid' => $lqdb->id, 'userid' => $user->id]
        );
        $otherstates = $DB->count_records(
            'eledialeitnerflow_card_state',
            ['eledialeitnerflowid' => $lqdb->id, 'userid' => $other->id]
        );

        $this->assertEquals(0, $remainingstates, 'All card states for target user must be deleted');
        $this->assertEquals(0, $remainingsessions, 'All sessions for target user must be deleted');
        $this->assertEquals(1, $otherstates, "Other user's data must NOT be deleted");
    }

    // Group 9: Attempt counter always increments.

    /**
     * attemptcount increments on every answer regardless of correctness.
     */
    public function test_attempt_count_increments_on_every_answer(): void {
        $lq    = $this->make_lq();
        $state = leitner_engine::process_answer(null, true, $lq, 1, 1);
        $this->assertEquals(1, $state->attemptcount, 'After 1st answer: 1 attempt');

        $state = leitner_engine::process_answer($state, false, $lq, 1, 1);
        $this->assertEquals(2, $state->attemptcount, 'After 2nd answer: 2 attempts');

        $state = leitner_engine::process_answer($state, true, $lq, 1, 1);
        $this->assertEquals(3, $state->attemptcount, 'After 3rd answer: 3 attempts');
    }

    // Group 10: Multi-user isolation.

    /**
     * Card states for different users in the same quiz are fully independent.
     */
    public function test_card_states_are_isolated_per_user(): void {
        $lqdb  = $this->create_lq();
        $user1  = $this->getDataGenerator()->create_user();
        $user2  = $this->getDataGenerator()->create_user();
        $gen    = $this->getDataGenerator()->get_plugin_generator('mod_eledialeitnerflow');

        $gen->create_card_state(['eledialeitnerflowid' => $lqdb->id, 'userid' => $user1->id,
            'questionid' => 1, 'correctcount' => 3, 'status' => leitner_engine::STATUS_LEARNED]);
        $gen->create_card_state(['eledialeitnerflowid' => $lqdb->id, 'userid' => $user2->id,
            'questionid' => 1, 'correctcount' => 0, 'status' => leitner_engine::STATUS_OPEN]);

        $s1 = leitner_engine::get_card_state($lqdb->id, $user1->id, 1);
        $s2 = leitner_engine::get_card_state($lqdb->id, $user2->id, 1);

        $this->assertEquals(leitner_engine::STATUS_LEARNED, (int) $s1->status, 'User1 should be learned');
        $this->assertEquals(
            leitner_engine::STATUS_OPEN,
            (int) $s2->status,
            'User2 should still be open — states are isolated'
        );
        $this->assertNotEquals(
            $s1->correctcount,
            $s2->correctcount,
            'Users must have independent correctcounts'
        );
    }

    // Group 11: Edge cases.

    /**
     * A card that is already learned stays at STATUS_LEARNED even with more correct answers.
     */
    public function test_process_answer_on_already_learned_card_stays_learned(): void {
        $lq    = $this->make_lq(['correcttolearn' => 3]);
        $state = null;
        for ($i = 0; $i < 3; $i++) {
            $state = leitner_engine::process_answer($state, true, $lq, 1, 1);
        }
        $this->assertEquals(leitner_engine::STATUS_LEARNED, $state->status);

        $state = leitner_engine::process_answer($state, true, $lq, 1, 1);
        $this->assertEquals(
            leitner_engine::STATUS_LEARNED,
            $state->status,
            'Once learned, status must remain LEARNED even with more correct answers'
        );
    }

    /**
     * With 5 boxes and threshold 5, box numbers are non-decreasing as correctcount rises.
     */
    public function test_boxcount_5_distributes_correctly(): void {
        $box0 = leitner_engine::calculate_box(0, 5, 5);
        $box1 = leitner_engine::calculate_box(1, 5, 5);
        $box2 = leitner_engine::calculate_box(2, 5, 5);
        $box3 = leitner_engine::calculate_box(3, 5, 5);
        $box4 = leitner_engine::calculate_box(4, 5, 5);

        $this->assertEquals(1, $box0, 'correctcount 0 → box 1');
        $this->assertGreaterThanOrEqual(1, $box1, 'box must be >= 1');
        $this->assertLessThanOrEqual(5, $box4, 'box must be <= 5');
        $this->assertGreaterThanOrEqual($box1, $box2, 'Boxes must be non-decreasing');
        $this->assertGreaterThanOrEqual($box2, $box3, 'Boxes must be non-decreasing');
        $this->assertGreaterThanOrEqual($box3, $box4, 'Boxes must be non-decreasing');
    }

    // Group 12: get_category_ids().

    /**
     * get_category_ids() returns IDs from the new multi-category field when available.
     */
    public function test_get_category_ids_prefers_new_field(): void {
        $lq = (object) [
            'questioncategoryids' => '5,10,15',
            'questioncategoryid'  => 99,
        ];
        $ids = leitner_engine::get_category_ids($lq);

        $this->assertEqualsCanonicalizing([5, 10, 15], $ids, 'Should return IDs from questioncategoryids');
    }

    /**
     * get_category_ids() falls back to the legacy single-category field when new field is absent.
     */
    public function test_get_category_ids_falls_back_to_legacy_field(): void {
        $lq = (object) [
            'questioncategoryids' => null,
            'questioncategoryid'  => 42,
        ];
        $ids = leitner_engine::get_category_ids($lq);

        $this->assertEquals([42], $ids, 'Should return legacy single category as array');
    }

    /**
     * get_category_ids() returns an empty array when neither field is set.
     */
    public function test_get_category_ids_returns_empty_when_unconfigured(): void {
        $lq = (object) [
            'questioncategoryids' => '',
            'questioncategoryid'  => 0,
        ];
        $ids = leitner_engine::get_category_ids($lq);

        $this->assertSame([], $ids, 'Should return empty array when no category set');
    }

    // Group 13: Session history + stats.

    /**
     * get_session_history() returns completed sessions, most recent first.
     */
    public function test_get_session_history_returns_most_recent_first(): void {
        $lqdb = $this->create_lq();
        $user  = $this->getDataGenerator()->create_user();
        $gen   = $this->getDataGenerator()->get_plugin_generator('mod_eledialeitnerflow');

        $gen->create_session(['eledialeitnerflowid' => $lqdb->id, 'userid' => $user->id,
            'timecompleted' => time() - 7200]);
        $gen->create_session(['eledialeitnerflowid' => $lqdb->id, 'userid' => $user->id,
            'timecompleted' => time() - 3600]);
        $gen->create_session(['eledialeitnerflowid' => $lqdb->id, 'userid' => $user->id,
            'timecompleted' => time() - 60]);

        $history = leitner_engine::get_session_history($lqdb->id, $user->id, 10);

        $this->assertCount(3, $history, 'Should return all 3 completed sessions');
        $this->assertGreaterThan(
            $history[1]->timecompleted,
            $history[0]->timecompleted,
            'First result must be most recent'
        );
    }

    /**
     * get_session_stats() returns all-zero stats for a user with no sessions.
     */
    public function test_get_session_stats_returns_zeros_for_new_user(): void {
        $lqdb  = $this->create_lq();
        $user   = $this->getDataGenerator()->create_user();
        $stats  = leitner_engine::get_session_stats($lqdb->id, $user->id);

        $this->assertEquals(0, $stats->sessioncount, 'No sessions yet');
        $this->assertEquals(0, $stats->totalasked, 'No questions asked yet');
        $this->assertEquals(0, $stats->avgpercent, 'Average must be 0, not a division error');
    }

    /**
     * get_session_stats() correctly aggregates questions asked and correct across sessions.
     */
    public function test_get_session_stats_aggregates_correctly(): void {
        $lqdb = $this->create_lq();
        $user  = $this->getDataGenerator()->create_user();
        $gen   = $this->getDataGenerator()->get_plugin_generator('mod_eledialeitnerflow');

        $gen->create_session(['eledialeitnerflowid' => $lqdb->id, 'userid' => $user->id,
            'questionsasked' => 10, 'questionscorrect' => 8]);
        $gen->create_session(['eledialeitnerflowid' => $lqdb->id, 'userid' => $user->id,
            'questionsasked' => 10, 'questionscorrect' => 6]);

        $stats = leitner_engine::get_session_stats($lqdb->id, $user->id);

        $this->assertEquals(2, $stats->sessioncount, '2 sessions');
        $this->assertEquals(20, $stats->totalasked, '20 questions total');
        $this->assertEquals(14, $stats->totalcorrect, '14 correct total');
        $this->assertEquals(70, $stats->avgpercent, '70% average (14/20)');
    }
}
