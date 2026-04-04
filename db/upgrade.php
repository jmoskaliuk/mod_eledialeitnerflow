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
 * Upgrade steps for mod_leitnerflow.
 *
 * @package    mod_leitnerflow
 * @copyright  2024 eLeDia GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

function xmldb_leitnerflow_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();

    // Add questioncategoryids TEXT field and migrate existing single-category data.
    if ($oldversion < 2024120106) {
        $table = new xmldb_table('leitnerflow');
        $field = new xmldb_field('questioncategoryids', XMLDB_TYPE_TEXT, null, null, null, null, null, 'questioncategoryid');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Migrate: copy existing questioncategoryid into questioncategoryids.
        $instances = $DB->get_records('leitnerflow', null, '', 'id, questioncategoryid');
        foreach ($instances as $inst) {
            if ((int) $inst->questioncategoryid > 0) {
                $DB->set_field('leitnerflow', 'questioncategoryids',
                    (string) $inst->questioncategoryid, ['id' => $inst->id]);
            }
        }

        upgrade_mod_savepoint(true, 2024120106, 'leitnerflow');
    }

    // Add showanimation field (default: on).
    if ($oldversion < 2024120109) {
        $table = new xmldb_table('leitnerflow');
        $field = new xmldb_field('showanimation', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1', 'grademethod');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2024120109, 'leitnerflow');
    }

    // Import user tour for existing installations.
    if ($oldversion < 2024120111) {
        // Handled in next step.
        upgrade_mod_savepoint(true, 2024120111, 'leitnerflow');
    }

    // Re-import user tour with fixed configdata (replaces broken tour from earlier attempts).
    if ($oldversion < 2024120113) {
        // Delete any previously imported broken tour(s).
        try {
            $oldtours = $DB->get_records_select('tool_usertours_tours',
                $DB->sql_like('name', '?'), ['%LeitnerFlow%']);
            foreach ($oldtours as $tour) {
                $DB->delete_records('tool_usertours_steps', ['tourid' => $tour->id]);
                $DB->delete_records('tool_usertours_tours', ['id' => $tour->id]);
            }
        } catch (\Throwable $e) {
            debugging('LeitnerFlow: Could not clean old tours: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }
        // Re-import with fixed JSON.
        require_once(__DIR__ . '/install.php');
        _leitnerflow_import_user_tour();
        upgrade_mod_savepoint(true, 2024120113, 'leitnerflow');
    }

    // Enable core multilang filter and re-import tour with HTML multilang syntax.
    if ($oldversion < 2024120114) {
        // Ensure the core multilang filter is active.
        require_once(__DIR__ . '/install.php');
        _leitnerflow_ensure_multilang_filter();

        // Delete old tours (they used {mlang} syntax which needs filter_multilang2).
        try {
            $oldtours = $DB->get_records_select('tool_usertours_tours',
                $DB->sql_like('name', '?'), ['%LeitnerFlow%']);
            foreach ($oldtours as $tour) {
                $DB->delete_records('tool_usertours_steps', ['tourid' => $tour->id]);
                $DB->delete_records('tool_usertours_tours', ['id' => $tour->id]);
            }
        } catch (\Throwable $e) {
            debugging('LeitnerFlow: Could not clean old tours: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }

        // Re-import with core multilang HTML syntax.
        _leitnerflow_import_user_tour();

        upgrade_mod_savepoint(true, 2024120114, 'leitnerflow');
    }

    // Fix tour targettype: 1=block (wrong), 0=CSS selector (correct).
    if ($oldversion < 2024120115) {
        require_once(__DIR__ . '/install.php');

        // Delete all LeitnerFlow tours (they had wrong targettype values).
        try {
            $oldtours = $DB->get_records_select('tool_usertours_tours',
                $DB->sql_like('name', '?'), ['%LeitnerFlow%']);
            foreach ($oldtours as $tour) {
                $DB->delete_records('tool_usertours_steps', ['tourid' => $tour->id]);
                $DB->delete_records('tool_usertours_tours', ['id' => $tour->id]);
            }
        } catch (\Throwable $e) {
            debugging('LeitnerFlow: Could not clean old tours: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }

        // Re-import with corrected targettype=0 for CSS selectors.
        _leitnerflow_import_user_tour();

        upgrade_mod_savepoint(true, 2024120115, 'leitnerflow');
    }

    // Re-import tour with "Mein" perspective texts and additional feature steps.
    if ($oldversion < 2024120116) {
        require_once(__DIR__ . '/install.php');

        try {
            $oldtours = $DB->get_records_select('tool_usertours_tours',
                $DB->sql_like('name', '?'), ['%LeitnerFlow%']);
            foreach ($oldtours as $tour) {
                $DB->delete_records('tool_usertours_steps', ['tourid' => $tour->id]);
                $DB->delete_records('tool_usertours_tours', ['id' => $tour->id]);
            }
        } catch (\Throwable $e) {
            debugging('LeitnerFlow: Could not clean old tours: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }

        _leitnerflow_import_user_tour();

        upgrade_mod_savepoint(true, 2024120116, 'leitnerflow');
    }

    // Add feedbackstyle field (0=off, 1=minimal, 2=encouraging).
    if ($oldversion < 2024120117) {
        $table = new xmldb_table('leitnerflow');
        $field = new xmldb_field('feedbackstyle', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1', 'showanimation');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2024120117, 'leitnerflow');
    }

    // Tour update: add "each question = card" hint, target session history by ID.
    if ($oldversion < 2024120118) {
        require_once(__DIR__ . '/install.php');

        try {
            $oldtours = $DB->get_records_select('tool_usertours_tours',
                $DB->sql_like('name', '?'), ['%LeitnerFlow%']);
            foreach ($oldtours as $tour) {
                $DB->delete_records('tool_usertours_steps', ['tourid' => $tour->id]);
                $DB->delete_records('tool_usertours_tours', ['id' => $tour->id]);
            }
        } catch (\Throwable $e) {
            debugging('LeitnerFlow: Could not clean old tours: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }

        _leitnerflow_import_user_tour();

        upgrade_mod_savepoint(true, 2024120118, 'leitnerflow');
    }

    return true;
}
