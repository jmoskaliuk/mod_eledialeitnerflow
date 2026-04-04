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

    return true;
}
