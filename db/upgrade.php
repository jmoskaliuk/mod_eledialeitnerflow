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
 * Upgrade steps for mod_eledialeitnerflow.
 *
 * @package    mod_eledialeitnerflow
 * @copyright  2026 eLeDia GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Execute upgrade steps for mod_eledialeitnerflow.
 *
 * Handles database schema migrations and configuration updates.
 *
 * @param int $oldversion The previous version number.
 * @return bool True on success.
 */
function xmldb_eledialeitnerflow_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();

    // Add questioncategoryids TEXT field and migrate existing single-category data.
    if ($oldversion < 2024120106) {
        $table = new xmldb_table('eledialeitnerflow');
        $field = new xmldb_field('questioncategoryids', XMLDB_TYPE_TEXT, null, null, null, null, null, 'questioncategoryid');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Migrate: copy existing questioncategoryid into questioncategoryids.
        $instances = $DB->get_records('eledialeitnerflow', null, '', 'id, questioncategoryid');
        foreach ($instances as $inst) {
            if ((int) $inst->questioncategoryid > 0) {
                $DB->set_field(
                    'eledialeitnerflow',
                    'questioncategoryids',
                    (string) $inst->questioncategoryid,
                    ['id' => $inst->id]
                );
            }
        }

        upgrade_mod_savepoint(true, 2024120106, 'eledialeitnerflow');
    }

    // Add showanimation field (default: on).
    if ($oldversion < 2024120109) {
        $table = new xmldb_table('eledialeitnerflow');
        $field = new xmldb_field('showanimation', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1', 'grademethod');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2024120109, 'eledialeitnerflow');
    }

    // Import user tour for existing installations.
    if ($oldversion < 2024120111) {
        // Handled in next step.
        upgrade_mod_savepoint(true, 2024120111, 'eledialeitnerflow');
    }

    // Re-import user tour with fixed configdata (replaces broken tour from earlier attempts).
    if ($oldversion < 2024120113) {
        // Delete any previously imported broken tour(s).
        try {
            $oldtours = $DB->get_records_select(
                'tool_usertours_tours',
                $DB->sql_like('name', '?'),
                ['%LeitnerFlow%']
            );
            foreach ($oldtours as $tour) {
                $DB->delete_records('tool_usertours_steps', ['tourid' => $tour->id]);
                $DB->delete_records('tool_usertours_tours', ['id' => $tour->id]);
            }
        } catch (\Throwable $e) {
            debugging('LeitnerFlow: Could not clean old tours: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }
        // Re-import with fixed JSON.
        require_once(__DIR__ . '/install.php');
        _eledialeitnerflow_import_user_tour();
        upgrade_mod_savepoint(true, 2024120113, 'eledialeitnerflow');
    }

    // Enable core multilang filter and re-import tour with HTML multilang syntax.
    if ($oldversion < 2024120114) {
        // Ensure the core multilang filter is active.
        require_once(__DIR__ . '/install.php');
        _eledialeitnerflow_ensure_multilang_filter();

        // Delete old tours (they used {mlang} syntax which needs filter_multilang2).
        try {
            $oldtours = $DB->get_records_select(
                'tool_usertours_tours',
                $DB->sql_like('name', '?'),
                ['%LeitnerFlow%']
            );
            foreach ($oldtours as $tour) {
                $DB->delete_records('tool_usertours_steps', ['tourid' => $tour->id]);
                $DB->delete_records('tool_usertours_tours', ['id' => $tour->id]);
            }
        } catch (\Throwable $e) {
            debugging('LeitnerFlow: Could not clean old tours: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }

        // Re-import with core multilang HTML syntax.
        _eledialeitnerflow_import_user_tour();

        upgrade_mod_savepoint(true, 2024120114, 'eledialeitnerflow');
    }

    // Fix tour targettype: 1=block (wrong), 0=CSS selector (correct).
    if ($oldversion < 2024120115) {
        require_once(__DIR__ . '/install.php');

        // Delete all LeitnerFlow tours (they had wrong targettype values).
        try {
            $oldtours = $DB->get_records_select(
                'tool_usertours_tours',
                $DB->sql_like('name', '?'),
                ['%LeitnerFlow%']
            );
            foreach ($oldtours as $tour) {
                $DB->delete_records('tool_usertours_steps', ['tourid' => $tour->id]);
                $DB->delete_records('tool_usertours_tours', ['id' => $tour->id]);
            }
        } catch (\Throwable $e) {
            debugging('LeitnerFlow: Could not clean old tours: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }

        // Re-import with corrected targettype=0 for CSS selectors.
        _eledialeitnerflow_import_user_tour();

        upgrade_mod_savepoint(true, 2024120115, 'eledialeitnerflow');
    }

    // Re-import tour with "Mein" perspective texts and additional feature steps.
    if ($oldversion < 2024120116) {
        require_once(__DIR__ . '/install.php');

        try {
            $oldtours = $DB->get_records_select(
                'tool_usertours_tours',
                $DB->sql_like('name', '?'),
                ['%LeitnerFlow%']
            );
            foreach ($oldtours as $tour) {
                $DB->delete_records('tool_usertours_steps', ['tourid' => $tour->id]);
                $DB->delete_records('tool_usertours_tours', ['id' => $tour->id]);
            }
        } catch (\Throwable $e) {
            debugging('LeitnerFlow: Could not clean old tours: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }

        _eledialeitnerflow_import_user_tour();

        upgrade_mod_savepoint(true, 2024120116, 'eledialeitnerflow');
    }

    // Add feedbackstyle field (0=off, 1=minimal, 2=encouraging).
    if ($oldversion < 2024120117) {
        $table = new xmldb_table('eledialeitnerflow');
        $field = new xmldb_field('feedbackstyle', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1', 'showanimation');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2024120117, 'eledialeitnerflow');
    }

    // Tour update: add "each question = card" hint, target session history by ID.
    if ($oldversion < 2024120118) {
        require_once(__DIR__ . '/install.php');

        try {
            $oldtours = $DB->get_records_select(
                'tool_usertours_tours',
                $DB->sql_like('name', '?'),
                ['%LeitnerFlow%']
            );
            foreach ($oldtours as $tour) {
                $DB->delete_records('tool_usertours_steps', ['tourid' => $tour->id]);
                $DB->delete_records('tool_usertours_tours', ['id' => $tour->id]);
            }
        } catch (\Throwable $e) {
            debugging('LeitnerFlow: Could not clean old tours: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }

        _eledialeitnerflow_import_user_tour();

        upgrade_mod_savepoint(true, 2024120118, 'eledialeitnerflow');
    }

    // Extend feedbackstyle to 5 modes (0-4) and add streak fields for gamified mode.
    if ($oldversion < 2024120119) {
        $table = new xmldb_table('eledialeitnerflow_sessions');

        $field1 = new xmldb_field('currentstreak', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '0', 'questionscorrect');
        if (!$dbman->field_exists($table, $field1)) {
            $dbman->add_field($table, $field1);
        }

        $field2 = new xmldb_field('beststreak', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '0', 'currentstreak');
        if (!$dbman->field_exists($table, $field2)) {
            $dbman->add_field($table, $field2);
        }

        // Migrate old feedbackstyle values: 2 (encouraging) → 2 (animated).
        // No actual change needed since the mapping stays the same.

        upgrade_mod_savepoint(true, 2024120119, 'eledialeitnerflow');
    }

    // Add showtour field (default: on).
    if ($oldversion < 2024120120) {
        $table = new xmldb_table('eledialeitnerflow');
        $field = new xmldb_field('showtour', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1', 'feedbackstyle');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2024120120, 'eledialeitnerflow');
    }

    // Add animationdelay field (default: 1000ms) and import teacher tour.
    if ($oldversion < 2024120121) {
        $table = new xmldb_table('eledialeitnerflow');
        $field = new xmldb_field('animationdelay', XMLDB_TYPE_INTEGER, '5', null, XMLDB_NOTNULL, null, '1000', 'feedbackstyle');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Import teacher tour.
        require_once(__DIR__ . '/install.php');
        _eledialeitnerflow_import_user_tour('eledialeitnerflow_teacher');

        upgrade_mod_savepoint(true, 2024120121, 'eledialeitnerflow');
    }

    // Re-import teacher tour (may have been missed if plugin was already at 2024120121).
    if ($oldversion < 2024120123) {
        global $DB;

        // Delete any existing teacher tour to avoid duplicates.
        try {
            $oldtours = $DB->get_records_select(
                'tool_usertours_tours',
                "pathmatch LIKE '%/mod/eledialeitnerflow/report.php%'"
            );
            foreach ($oldtours as $tour) {
                $DB->delete_records('tool_usertours_steps', ['tourid' => $tour->id]);
                $DB->delete_records('tool_usertours_tours', ['id' => $tour->id]);
            }
        } catch (\Exception $e) {
            debugging('LeitnerFlow: Could not clean old teacher tours: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }

        // Re-import teacher tour.
        require_once(__DIR__ . '/install.php');
        _eledialeitnerflow_import_user_tour('eledialeitnerflow_teacher');

        upgrade_mod_savepoint(true, 2024120123, 'eledialeitnerflow');
    }

    // Clean up orphaned grade_items left over from the old 'leitnerflow' plugin name.
    // These cause coding_exception 'Invalid component used in plugin/component_callback():
    // mod_leitnerflow' whenever a course containing them is regraded.
    if ($oldversion < 2024120124) {
        $stale = $DB->get_records('grade_items', ['itemmodule' => 'leitnerflow']);
        foreach ($stale as $gi) {
            if ($DB->record_exists('eledialeitnerflow', ['id' => $gi->iteminstance])) {
                // Matching instance still exists — migrate the grade item to the new component.
                $DB->set_field('grade_items', 'itemmodule', 'eledialeitnerflow', ['id' => $gi->id]);
            } else {
                // True orphan — drop the grade item and its grades.
                $DB->delete_records('grade_grades', ['itemid' => $gi->id]);
                $DB->delete_records('grade_items', ['id' => $gi->id]);
            }
        }
        upgrade_mod_savepoint(true, 2024120124, 'eledialeitnerflow');
    }

    // De-duplicate LeitnerFlow user tours accumulated by the non-idempotent
    // view.php import path. Wipe all LeitnerFlow-matching tours and reimport
    // once from the bundled JSON — install_bundled_tours() is now idempotent.
    if ($oldversion < 2026042101) {
        \mod_eledialeitnerflow\local\tour_installer::reimport_bundled_tours();
        upgrade_mod_savepoint(true, 2026042101, 'eledialeitnerflow');
    }

    return true;
}
