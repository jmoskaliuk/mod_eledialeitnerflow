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
 * Behat step definitions for mod_eledialeitnerflow.
 *
 * @package    mod_eledialeitnerflow
 * @copyright  2026 eLeDia GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// NOTE: no MOODLE_INTERNAL check in Behat context files.

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

use Behat\Mink\Exception\ExpectationException;

/**
 * Custom Behat steps for LeitnerFlow.
 */
class behat_mod_eledialeitnerflow extends behat_base {
    /**
     * Installs the bundled LeitnerFlow user tours explicitly.
     *
     * Needed because db/install.php intentionally skips tour import
     * (tool_usertours may not be available yet during moodle-plugin-ci install).
     * Behat scenarios that assert tour presence must call this step first.
     *
     * @Given the eledialeitnerflow bundled tours are installed
     */
    public function the_eledialeitnerflow_bundled_tours_are_installed(): void {
        \mod_eledialeitnerflow\local\tour_installer::install_bundled_tours();
    }

    /**
     * Creates a completed LeitnerFlow session for a user in a named activity.
     *
     * Use this in scenarios that need at least one started session so that
     * report.php renders the participant table rather than the "nobody has
     * started" notice.
     *
     * @Given a LeitnerFlow session exists for :username in :activityname
     *
     * @param string $username    Moodle username (e.g. "student1")
     * @param string $activityname  Display name of the eledialeitnerflow activity
     */
    public function a_leitnerflow_session_exists_for_in(string $username, string $activityname): void {
        global $DB;

        $user = $DB->get_record('user', ['username' => $username], '*', MUST_EXIST);

        $sql = "SELECT cm.instance
                  FROM {course_modules} cm
                  JOIN {modules} m ON m.id = cm.module
                  JOIN {eledialeitnerflow} lf ON lf.id = cm.instance
                 WHERE m.name = 'eledialeitnerflow' AND lf.name = :name";
        $cm = $DB->get_record_sql($sql, ['name' => $activityname], MUST_EXIST);

        $DB->insert_record('eledialeitnerflow_sessions', (object)[
            'eledialeitnerflowid' => $cm->instance,
            'userid'              => $user->id,
            'qubaid'              => null,
            'questionids'         => json_encode([]),
            'currentindex'        => 0,
            'questionsasked'      => 5,
            'questionscorrect'    => 3,
            'status'              => 1,
            'timecreated'         => time() - 3600,
            'timecompleted'       => time(),
        ]);
    }
}
