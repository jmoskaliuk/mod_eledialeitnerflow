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
 * List all LeitnerFlow instances in a course.
 *
 * @package    mod_eledialeitnerflow
 * @copyright  2026 eLeDia GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

$courseid = required_param('id', PARAM_INT);
$course   = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);

require_login($course);
$PAGE->set_url('/mod/eledialeitnerflow/index.php', ['id' => $courseid]);
$PAGE->set_title(format_string($course->shortname) . ': ' . get_string('modulenameplural', 'mod_eledialeitnerflow'));
$PAGE->set_heading(format_string($course->fullname));

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('modulenameplural', 'mod_eledialeitnerflow'));

$instances = get_all_instances_in_course('eledialeitnerflow', $course);
if (empty($instances)) {
    notice(
        get_string('thereareno', 'moodle', get_string('modulenameplural', 'mod_eledialeitnerflow')),
        new moodle_url('/course/view.php', ['id' => $courseid])
    );
}

$table          = new html_table();
$table->head    = [get_string('name')];
$table->colclasses = ['leftalign'];

foreach ($instances as $lq) {
    $link = html_writer::link(
        new moodle_url('/mod/eledialeitnerflow/view.php', ['id' => $lq->coursemodule]),
        format_string($lq->name)
    );
    $table->data[] = [$link];
}
echo html_writer::table($table);
echo $OUTPUT->footer();
