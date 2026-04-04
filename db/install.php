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
 * Post-install tasks for mod_leitnerflow.
 *
 * @package    mod_leitnerflow
 * @copyright  2024 eLeDia GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

function xmldb_leitnerflow_install() {
    // Ensure the core multi-language content filter is active (needed for tour translations).
    _leitnerflow_ensure_multilang_filter();
    // Import the introductory user tour.
    _leitnerflow_import_user_tour();
}

/**
 * Ensure the Moodle core multi-language content filter is enabled.
 *
 * The LeitnerFlow user tour uses <span class="multilang"> tags which require
 * the core 'multilang' filter to be active site-wide.
 */
function _leitnerflow_ensure_multilang_filter(): void {
    global $CFG;
    require_once($CFG->libdir . '/filterlib.php');

    // TEXTFILTER_ON = 1. Only enable if currently off or disabled.
    $currentstate = filter_get_global_states();
    if (!isset($currentstate['multilang']) || $currentstate['multilang']->active != TEXTFILTER_ON) {
        filter_set_global_state('multilang', TEXTFILTER_ON);
    }
}

/**
 * Import the LeitnerFlow user tour from the bundled JSON file.
 */
function _leitnerflow_import_user_tour(): void {
    $tourfile = __DIR__ . '/usertours/leitnerflow_intro.json';
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
