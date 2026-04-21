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
 * Restore task for mod_eledialeitnerflow.
 *
 * @package    mod_eledialeitnerflow
 * @copyright  2026 eLeDia GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/eledialeitnerflow/backup/moodle2/restore_eledialeitnerflow_stepslib.php');

/**
 * Restore activity task for mod_eledialeitnerflow.
 *
 * @package    mod_eledialeitnerflow
 * @copyright  2026 eLeDia GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_eledialeitnerflow_activity_task extends restore_activity_task {
    /**
     * Define settings for restoration.
     *
     * @return void
     */
    protected function define_my_settings(): void {
    }

    /**
     * Define steps for restoration.
     *
     * @return void
     */
    protected function define_my_steps(): void {
        $this->add_step(new restore_eledialeitnerflow_activity_structure_step(
            'eledialeitnerflow_structure',
            'eledialeitnerflow.xml'
        ));
    }

    /**
     * Define content to decode during restoration.
     *
     * @return array Restore decode content objects.
     */
    public static function define_decode_contents(): array {
        $contents = [];
        $contents[] = new restore_decode_content('eledialeitnerflow', ['intro'], 'eledialeitnerflow');
        return $contents;
    }

    /**
     * Define rules for decoding content links.
     *
     * @return array Restore decode rule objects.
     */
    public static function define_decode_rules(): array {
        $rules = [];
        $rules[] = new restore_decode_rule(
            'LEITNERFLOWVIEWBYID',
            '/mod/eledialeitnerflow/view.php?id=$1',
            'course_module'
        );
        return $rules;
    }

    /**
     * Define restoration log rules.
     *
     * @return array Restore log rule objects.
     */
    public static function define_restore_log_rules(): array {
        return [];
    }

    /**
     * Define course-level restoration log rules.
     *
     * @return array Restore log rule objects.
     */
    public static function define_restore_log_rules_for_course(): array {
        return [];
    }
}
