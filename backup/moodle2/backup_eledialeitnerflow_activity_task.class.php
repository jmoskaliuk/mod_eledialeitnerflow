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
 * Backup task for mod_eledialeitnerflow.
 *
 * @package    mod_eledialeitnerflow
 * @copyright  2026 eLeDia GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/eledialeitnerflow/backup/moodle2/backup_eledialeitnerflow_stepslib.php');

/**
 * Backup activity task for mod_eledialeitnerflow.
 *
 * @package    mod_eledialeitnerflow
 * @copyright  2026 eLeDia GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_eledialeitnerflow_activity_task extends backup_activity_task {
    /**
     * Define settings for backup.
     *
     * @return void
     */
    protected function define_my_settings(): void {
    }

    /**
     * Define steps for backup.
     *
     * @return void
     */
    protected function define_my_steps(): void {
        $this->add_step(new backup_eledialeitnerflow_activity_structure_step(
            'eledialeitnerflow_structure',
            'eledialeitnerflow.xml'
        ));
    }

    /**
     * Encode content links for backup.
     *
     * @param string $content The content to encode.
     * @return string The content with encoded links.
     */
    public static function encode_content_links($content): string {
        global $CFG;
        $base = preg_quote($CFG->wwwroot, '/');

        // View.php links.
        $search  = "/({$base}\/mod\/eledialeitnerflow\/view\.php\?id=)([0-9]+)/";
        $content = preg_replace($search, '$@LEITNERFLOWVIEWBYID*$2@$', $content);

        return $content;
    }
}
