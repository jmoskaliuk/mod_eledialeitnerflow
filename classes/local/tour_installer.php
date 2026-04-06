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

namespace mod_eledialeitnerflow\local;

/**
 * Tour installer for mod_eledialeitnerflow.
 *
 * Manages import and cleanup of bundled user tours shipped as JSON
 * files in db/usertours/. This class is autoloaded so that both
 * db/install.php and the behat context can call it without require_once
 * (which is forbidden in db/ files by Moodle coding standards).
 *
 * @package    mod_eledialeitnerflow
 * @copyright  2024 eLeDia GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tour_installer {
    /**
     * Imports every JSON tour from db/usertours/ via tool_usertours.
     *
     * Skipped silently if tool_usertours is disabled or unavailable
     * (e.g. in minimal test environments or during phpunit init when
     * the tool_usertours tables don't yet exist).
     */
    public static function install_bundled_tours(): void {
        global $CFG, $DB;

        if (!class_exists('\\tool_usertours\\manager')) {
            return;
        }

        // Mod plugins install before tool_* plugins, so on a fresh
        // site (and during phpunit init) the tool_usertours tables
        // don't exist yet. Bail out silently — tool_usertours auto-
        // imports plugin-bundled tours on its own install later.
        try {
            $dbman = $DB->get_manager();
            if (
                !$dbman->table_exists('tool_usertours_tours')
                || !$dbman->table_exists('tool_usertours_steps')
            ) {
                return;
            }
        } catch (\Throwable $e) {
            return;
        }

        $toursdir = $CFG->dirroot . '/mod/eledialeitnerflow/db/usertours';
        if (!is_dir($toursdir)) {
            return;
        }

        foreach (glob($toursdir . '/*.json') as $file) {
            try {
                $json = file_get_contents($file);
                if ($json === false) {
                    continue;
                }
                \tool_usertours\manager::import_tour_from_json($json);
            } catch (\Throwable $e) {
                debugging(
                    'mod_eledialeitnerflow: could not import tour ' .
                    basename($file) . ': ' . $e->getMessage(),
                    DEBUG_DEVELOPER
                );
            }
        }
    }

    /**
     * Removes all plugin-bundled tours that match the LeitnerFlow name
     * pattern, then reimports them from the current JSON files.
     *
     * This is the standard upgrade-step action: remove old tours
     * (which may have stale steps/pathmatch/strings) and reimport
     * the current version from db/usertours/.
     *
     * The name filter ('LeitnerFlow' or 'Leitner') prevents accidental
     * deletion of foreign tours on generic pathmatch patterns.
     */
    public static function reimport_bundled_tours(): void {
        global $DB;

        if (!class_exists('\\tool_usertours\\tour')) {
            return;
        }

        try {
            $dbman = $DB->get_manager();
            if (!$dbman->table_exists('tool_usertours_tours')) {
                return;
            }
        } catch (\Throwable $e) {
            return;
        }

        $patterns = [
            '/mod/eledialeitnerflow/%',
            '/admin/settings.php?section=modsettingeledialeitnerflow%',
        ];

        foreach ($patterns as $pattern) {
            $oldtours = $DB->get_records_select(
                'tool_usertours_tours',
                $DB->sql_like('pathmatch', ':path'),
                ['path' => $pattern]
            );
            foreach ($oldtours as $record) {
                // Only remove tours with LeitnerFlow prefix in name.
                if (
                    stripos((string) $record->name, 'leitner') === false
                ) {
                    continue;
                }
                try {
                    $tour = \tool_usertours\tour::load_from_record($record);
                    $tour->remove();
                } catch (\Throwable $e) {
                    debugging(
                        'mod_eledialeitnerflow: could not remove old tour ' .
                        ($record->name ?? $record->id) . ': ' .
                        $e->getMessage(),
                        DEBUG_DEVELOPER
                    );
                }
            }
        }

        self::install_bundled_tours();
    }
}
