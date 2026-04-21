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
 * Post-install tasks for mod_eledialeitnerflow.
 *
 * @package    mod_eledialeitnerflow
 * @copyright  2026 eLeDia GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Execute post-install tasks for mod_eledialeitnerflow.
 *
 * Ensures the multilang filter is active. Tours are imported lazily
 * on first view (via tour_installer) or explicitly via the behat context
 * step. Importing them here causes tool_usertours/manager.php to crash
 * during behat/phpunit init when the tours table rows have null steps.
 *
 * @return void
 */
function xmldb_eledialeitnerflow_install() {
    // Ensure the core multi-language content filter is active (needed for tour translations).
    _eledialeitnerflow_ensure_multilang_filter();
}

/**
 * Ensure the Moodle core multi-language content filter is enabled.
 *
 * The LeitnerFlow user tour uses <span class="multilang"> tags which require
 * the core 'multilang' filter to be active site-wide.
 */
function _eledialeitnerflow_ensure_multilang_filter(): void {
    global $CFG;
    require_once($CFG->libdir . '/filterlib.php');

    // TEXTFILTER_ON = 1. Only enable if currently off or disabled.
    $currentstate = filter_get_global_states();
    if (!isset($currentstate['multilang']) || $currentstate['multilang']->active != TEXTFILTER_ON) {
        filter_set_global_state('multilang', TEXTFILTER_ON);
    }
}

/**
 * Import a LeitnerFlow user tour from a bundled JSON file.
 *
 * @param string $tourname Base name of the JSON file (without .json extension).
 */
function _eledialeitnerflow_import_user_tour(string $tourname = 'eledialeitnerflow_intro'): void {
    $tourfile = __DIR__ . '/usertours/' . $tourname . '.json';
    if (!file_exists($tourfile)) {
        return;
    }
    // Only import if tool_usertours is available.
    if (!class_exists('\tool_usertours\manager')) {
        return;
    }
    try {
        $json = file_get_contents($tourfile);
        \tool_usertours\manager::import_tour_from_json($json);
    } catch (\Throwable $e) {
        // Non-critical — tour import failure should not block install/upgrade.
        debugging('LeitnerFlow: Could not import user tour: ' . $e->getMessage(), DEBUG_DEVELOPER);
    }
}
