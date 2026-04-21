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
 * Privacy API provider for mod_eledialeitnerflow.
 *
 * @package    mod_eledialeitnerflow
 * @copyright  2026 eLeDia GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_eledialeitnerflow\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\deletion_criteria;
use core_privacy\local\request\helper;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

/**
 * Privacy provider for mod_eledialeitnerflow.
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\core_userlist_provider,
    \core_privacy\local\request\plugin\provider {
    /**
     * Get metadata about this plugin's privacy data.
     *
     * @param collection $collection The metadata collection.
     * @return collection The updated metadata collection.
     */
    public static function get_metadata(collection $collection): collection {

        $collection->add_database_table(
            'eledialeitnerflow_card_state',
            [
                'userid'        => 'privacy:metadata:eledialeitnerflow_card_state:userid',
                'questionid'    => 'privacy:metadata:eledialeitnerflow_card_state:questionid',
                'currentbox'    => 'privacy:metadata:eledialeitnerflow_card_state:currentbox',
                'correctcount'  => 'privacy:metadata:eledialeitnerflow_card_state:correctcount',
                'attemptcount'  => 'privacy:metadata:eledialeitnerflow_card_state:attemptcount',
                'status'        => 'privacy:metadata:eledialeitnerflow_card_state:status',
            ],
            'privacy:metadata:eledialeitnerflow_card_state'
        );

        $collection->add_database_table(
            'eledialeitnerflow_sessions',
            [
                'userid'            => 'privacy:metadata:eledialeitnerflow_sessions:userid',
                'timecreated'       => 'privacy:metadata:eledialeitnerflow_sessions:timecreated',
                'timecompleted'     => 'privacy:metadata:eledialeitnerflow_sessions:timecompleted',
                'questionsasked'    => 'privacy:metadata:eledialeitnerflow_sessions:questionsasked',
                'questionscorrect'  => 'privacy:metadata:eledialeitnerflow_sessions:questionscorrect',
            ],
            'privacy:metadata:eledialeitnerflow_sessions'
        );

        return $collection;
    }

    /**
     * Get contexts for a specific user.
     *
     * @param int $userid The user ID.
     * @return contextlist The context list for the user.
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();
        $sql = "SELECT ctx.id
                  FROM {context} ctx
                  JOIN {course_modules} cm ON cm.id = ctx.instanceid AND ctx.contextlevel = :ctxmodule
                  JOIN {eledialeitnerflow} lq    ON lq.id = cm.instance
                  JOIN {eledialeitnerflow_card_state} cs ON cs.eledialeitnerflowid = lq.id AND cs.userid = :userid";
        $contextlist->add_from_sql($sql, ['ctxmodule' => CONTEXT_MODULE, 'userid' => $userid]);
        return $contextlist;
    }

    /**
     * Get users in a context.
     *
     * @param userlist $userlist The user list object.
     * @return void
     */
    public static function get_users_in_context(userlist $userlist): void {
        $context = $userlist->get_context();
        if ($context->contextlevel !== CONTEXT_MODULE) {
            return;
        }
        $sql = "SELECT cs.userid
                  FROM {eledialeitnerflow_card_state} cs
                  JOIN {course_modules} cm ON cm.instance = cs.eledialeitnerflowid
                 WHERE cm.id = :cmid";
        $userlist->add_from_sql('userid', $sql, ['cmid' => $context->instanceid]);
    }

    /**
     * Export user data for a context list.
     *
     * @param approved_contextlist $contextlist The approved context list.
     * @return void
     */
    public static function export_user_data(approved_contextlist $contextlist): void {
        global $DB;

        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel !== CONTEXT_MODULE) {
                continue;
            }
            $cm = get_coursemodule_from_id('eledialeitnerflow', $context->instanceid);
            if (!$cm) {
                continue;
            }
            $userid = $contextlist->get_user()->id;

            // Export card states.
            $states = $DB->get_records('eledialeitnerflow_card_state', [
                'eledialeitnerflowid' => $cm->instance,
                'userid'        => $userid,
            ]);
            if ($states) {
                writer::with_context($context)->export_data(
                    [get_string('privacy:metadata:eledialeitnerflow_card_state', 'mod_eledialeitnerflow')],
                    (object)['card_states' => array_values($states)]
                );
            }

            // Export sessions.
            $sessions = $DB->get_records('eledialeitnerflow_sessions', [
                'eledialeitnerflowid' => $cm->instance,
                'userid'        => $userid,
            ]);
            if ($sessions) {
                writer::with_context($context)->export_data(
                    [get_string('privacy:metadata:eledialeitnerflow_sessions', 'mod_eledialeitnerflow')],
                    (object)['sessions' => array_values($sessions)]
                );
            }
        }
    }

    /**
     * Delete all user data for a context.
     *
     * @param \context $context The context to delete data from.
     * @return void
     */
    public static function delete_data_for_all_users_in_context(\context $context): void {
        global $DB;
        if ($context->contextlevel !== CONTEXT_MODULE) {
            return;
        }
        $cm = get_coursemodule_from_id('eledialeitnerflow', $context->instanceid);
        if (!$cm) {
            return;
        }

        $sessions = $DB->get_records('eledialeitnerflow_sessions', ['eledialeitnerflowid' => $cm->instance]);
        foreach ($sessions as $session) {
            if (!empty($session->qubaid)) {
                \question_engine::delete_questions_usage_by_activity($session->qubaid);
            }
        }
        $DB->delete_records('eledialeitnerflow_sessions', ['eledialeitnerflowid' => $cm->instance]);
        $DB->delete_records('eledialeitnerflow_card_state', ['eledialeitnerflowid' => $cm->instance]);
    }

    /**
     * Delete data for a specific user.
     *
     * @param approved_contextlist $contextlist The approved context list for the user.
     * @return void
     */
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        global $DB;
        $userid = $contextlist->get_user()->id;

        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel !== CONTEXT_MODULE) {
                continue;
            }
            $cm = get_coursemodule_from_id('eledialeitnerflow', $context->instanceid);
            if (!$cm) {
                continue;
            }
            \mod_eledialeitnerflow\engine\leitner_engine::delete_user_data($cm->instance, $userid);
        }
    }

    /**
     * Delete data for multiple users.
     *
     * @param approved_userlist $userlist The approved user list.
     * @return void
     */
    public static function delete_data_for_users(approved_userlist $userlist): void {
        $context = $userlist->get_context();
        if ($context->contextlevel !== CONTEXT_MODULE) {
            return;
        }
        $cm = get_coursemodule_from_id('eledialeitnerflow', $context->instanceid);
        if (!$cm) {
            return;
        }
        foreach ($userlist->get_userids() as $userid) {
            \mod_eledialeitnerflow\engine\leitner_engine::delete_user_data($cm->instance, $userid);
        }
    }
}
