<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// @package    mod_leitnerflow
// @copyright  2024 eLeDia GmbH
// @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

/**
 * CLI script to generate test data for LeitnerFlow development.
 *
 * Creates courses, users, question categories with questions,
 * and LeitnerFlow activity instances.
 *
 * Usage: php mod/leitnerflow/cli/generate_testdata.php [--clean]
 *   --clean  Remove previously generated test data before creating new data
 */

define('CLI_SCRIPT', true);
require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->libdir . '/questionlib.php');
require_once($CFG->dirroot . '/question/engine/lib.php');
require_once($CFG->dirroot . '/mod/leitnerflow/lib.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/user/lib.php');

// Parse CLI options.
list($options, $unrecognised) = cli_get_params(
    ['clean' => false, 'help' => false],
    ['c' => 'clean', 'h' => 'help']
);

if ($unrecognised) {
    $unrecognised = implode(PHP_EOL . '  ', $unrecognised);
    cli_error("Unrecognised options: {$unrecognised}");
}

if ($options['help']) {
    echo "Generate LeitnerFlow test data.

Options:
  --clean, -c   Remove previously generated test data first
  --help, -h    Show this help

";
    exit(0);
}

// Marker to identify generated test data.
$MARKER = 'LFLOW_TESTDATA';

// ─── Clean mode ────────────────────────────────────────────────────────────────
if ($options['clean']) {
    cli_heading('Cleaning previous test data');

    // Find courses with our marker in idnumber.
    $courses = $DB->get_records_select('course', "idnumber LIKE ?", ["{$MARKER}_%"]);
    foreach ($courses as $course) {
        cli_writeln("  Deleting course: {$course->fullname} (id={$course->id})");
        delete_course($course->id, false);
    }

    // Find users with our marker.
    $users = $DB->get_records_select('user', "idnumber LIKE ?", ["{$MARKER}_%"]);
    foreach ($users as $user) {
        cli_writeln("  Deleting user: {$user->username} (id={$user->id})");
        delete_user($user);
    }

    if (empty($courses) && empty($users)) {
        cli_writeln("  Nothing to clean.");
    } else {
        cli_writeln("Cleanup complete.\n");
    }
}

// ─── Configuration ─────────────────────────────────────────────────────────────
$coursedata = [
    [
        'fullname'  => 'Mathematik Grundkurs',
        'shortname' => 'Mathe-GK',
        'questions' => [
            ['name' => 'Was ist 7 × 8?', 'answer' => '56', 'wrong' => ['48', '54', '64']],
            ['name' => 'Was ist die Quadratwurzel von 144?', 'answer' => '12', 'wrong' => ['11', '13', '14']],
            ['name' => 'Wie viel ist 15% von 200?', 'answer' => '30', 'wrong' => ['20', '25', '35']],
            ['name' => 'Was ergibt 3² + 4²?', 'answer' => '25', 'wrong' => ['12', '14', '7']],
            ['name' => 'Wie lautet die Formel für den Kreisumfang?', 'answer' => '2πr', 'wrong' => ['πr²', '2r', 'πd²']],
            ['name' => 'Was ist der GGT von 12 und 18?', 'answer' => '6', 'wrong' => ['3', '9', '12']],
            ['name' => 'Wie viele Grad hat ein rechter Winkel?', 'answer' => '90', 'wrong' => ['45', '60', '180']],
            ['name' => 'Was ergibt (-3) × (-4)?', 'answer' => '12', 'wrong' => ['-12', '-7', '7']],
        ],
    ],
    [
        'fullname'  => 'Englisch Vokabeltrainer',
        'shortname' => 'Eng-Vokab',
        'questions' => [
            ['name' => 'What is "Schmetterling" in English?', 'answer' => 'butterfly', 'wrong' => ['dragonfly', 'moth', 'beetle']],
            ['name' => 'What is "Kühlschrank" in English?', 'answer' => 'refrigerator', 'wrong' => ['freezer', 'microwave', 'dishwasher']],
            ['name' => 'What is "Wissenschaft" in English?', 'answer' => 'science', 'wrong' => ['knowledge', 'research', 'wisdom']],
            ['name' => 'What is "Krankenhaus" in English?', 'answer' => 'hospital', 'wrong' => ['clinic', 'pharmacy', 'ambulance']],
            ['name' => 'What is "Geschwindigkeit" in English?', 'answer' => 'speed', 'wrong' => ['history', 'story', 'velocity']],
            ['name' => 'What is "Umwelt" in English?', 'answer' => 'environment', 'wrong' => ['surroundings', 'nature', 'world']],
            ['name' => 'What is "Gleichung" in English?', 'answer' => 'equation', 'wrong' => ['equality', 'balance', 'formula']],
            ['name' => 'What is "Tastatur" in English?', 'answer' => 'keyboard', 'wrong' => ['mouse', 'monitor', 'printer']],
            ['name' => 'What is "Aufgabe" in English?', 'answer' => 'task', 'wrong' => ['gift', 'question', 'exam']],
            ['name' => 'What is "Ergebnis" in English?', 'answer' => 'result', 'wrong' => ['event', 'experience', 'effort']],
        ],
    ],
    [
        'fullname'  => 'Biologie Zellkunde',
        'shortname' => 'Bio-Zelle',
        'questions' => [
            ['name' => 'Welches Organell ist das "Kraftwerk" der Zelle?', 'answer' => 'Mitochondrium', 'wrong' => ['Ribosom', 'Zellkern', 'Golgi-Apparat']],
            ['name' => 'Was ist die Funktion der Ribosomen?', 'answer' => 'Proteinbiosynthese', 'wrong' => ['Zellatmung', 'Fotosynthese', 'DNA-Replikation']],
            ['name' => 'Welche Struktur umgibt den Zellkern?', 'answer' => 'Kernmembran', 'wrong' => ['Zellwand', 'Zellmembran', 'Vakuole']],
            ['name' => 'Was speichert die DNA?', 'answer' => 'Erbinformation', 'wrong' => ['Energie', 'Proteine', 'Wasser']],
            ['name' => 'In welchem Organell findet die Fotosynthese statt?', 'answer' => 'Chloroplast', 'wrong' => ['Mitochondrium', 'Ribosom', 'Lysosom']],
            ['name' => 'Was ist die Hauptfunktion der Vakuole in Pflanzenzellen?', 'answer' => 'Speicherung', 'wrong' => ['Zellteilung', 'Energieproduktion', 'Bewegung']],
        ],
    ],
    [
        'fullname'  => 'Geschichte Moderne',
        'shortname' => 'Gesch-Mod',
        'questions' => [
            ['name' => 'In welchem Jahr fiel die Berliner Mauer?', 'answer' => '1989', 'wrong' => ['1987', '1990', '1991']],
            ['name' => 'Wer war der erste Bundeskanzler der BRD?', 'answer' => 'Konrad Adenauer', 'wrong' => ['Willy Brandt', 'Ludwig Erhard', 'Helmut Schmidt']],
            ['name' => 'Wann wurde die EU gegründet (Maastricht)?', 'answer' => '1992', 'wrong' => ['1990', '1995', '1989']],
            ['name' => 'Welches Ereignis war am 11. September 2001?', 'answer' => 'Terroranschläge in den USA', 'wrong' => ['Mondlandung', 'Mauerfall', 'Tsunami']],
            ['name' => 'Wann begann der Erste Weltkrieg?', 'answer' => '1914', 'wrong' => ['1912', '1916', '1918']],
            ['name' => 'Wann endete der Zweite Weltkrieg in Europa?', 'answer' => '1945', 'wrong' => ['1944', '1946', '1943']],
            ['name' => 'Wer hielt die Rede "Ich bin ein Berliner"?', 'answer' => 'John F. Kennedy', 'wrong' => ['Ronald Reagan', 'Winston Churchill', 'Willy Brandt']],
        ],
    ],
];

// Student names (German-sounding).
$studentnames = [
    ['Max', 'Müller'], ['Anna', 'Schmidt'], ['Lukas', 'Weber'],
    ['Sophie', 'Fischer'], ['Leon', 'Wagner'], ['Emma', 'Becker'],
    ['Felix', 'Hoffmann'], ['Mia', 'Schäfer'], ['Paul', 'Koch'],
    ['Lena', 'Bauer'], ['Tim', 'Richter'], ['Laura', 'Klein'],
    ['Jonas', 'Wolf'], ['Hannah', 'Schröder'], ['Ben', 'Neumann'],
    ['Lea', 'Schwarz'], ['Finn', 'Braun'], ['Marie', 'Zimmermann'],
    ['Elias', 'Hartmann'], ['Clara', 'Krüger'],
];

// ─── Create Users ──────────────────────────────────────────────────────────────
cli_heading('Creating 20 test students');

$students = [];
foreach ($studentnames as $i => $namepair) {
    $username = strtolower($namepair[0]) . '.' . strtolower(str_replace(['ä','ö','ü'], ['ae','oe','ue'], $namepair[1]));

    // Check if already exists.
    $existing = $DB->get_record('user', ['username' => $username]);
    if ($existing) {
        cli_writeln("  User already exists: {$username} (id={$existing->id})");
        $students[] = $existing;
        continue;
    }

    $user = new stdClass();
    $user->username    = $username;
    $user->password    = 'Test1234!';
    $user->firstname   = $namepair[0];
    $user->lastname    = $namepair[1];
    $user->email       = $username . '@example.com';
    $user->confirmed   = 1;
    $user->mnethostid  = $CFG->mnet_localhost_id;
    $user->idnumber    = $MARKER . '_user_' . ($i + 1);
    $user->auth        = 'manual';

    $user->id = user_create_user($user, true, false);
    cli_writeln("  Created user: {$username} (id={$user->id})");
    $students[] = $DB->get_record('user', ['id' => $user->id]);
}

// ─── Create Courses, Questions, LeitnerFlow instances ──────────────────────────
cli_heading('Creating courses with questions and LeitnerFlow activities');

$manualenrol = enrol_get_plugin('manual');

foreach ($coursedata as $ci => $cdata) {
    // Check if course already exists.
    $idnumber = $MARKER . '_course_' . ($ci + 1);
    $existing = $DB->get_record('course', ['idnumber' => $idnumber]);

    if ($existing) {
        cli_writeln("\n  Course already exists: {$cdata['fullname']} (id={$existing->id})");
        continue;
    }

    // Create course.
    $newcourse = new stdClass();
    $newcourse->fullname  = $cdata['fullname'];
    $newcourse->shortname = $cdata['shortname'] . '_' . time();
    $newcourse->idnumber  = $idnumber;
    $newcourse->category  = 1; // Default category.
    $newcourse->format    = 'topics';
    $newcourse->numsections = 4;

    $course = create_course($newcourse);
    cli_writeln("\n  Created course: {$course->fullname} (id={$course->id})");

    // Enrol students.
    $enrolinstance = $DB->get_record('enrol', ['courseid' => $course->id, 'enrol' => 'manual']);
    if (!$enrolinstance) {
        $enrolid = $manualenrol->add_instance($course);
        $enrolinstance = $DB->get_record('enrol', ['id' => $enrolid]);
    }

    $studentrole = $DB->get_record('role', ['shortname' => 'student']);
    foreach ($students as $student) {
        $manualenrol->enrol_user($enrolinstance, $student->id, $studentrole->id);
    }
    cli_writeln("    Enrolled {count($students)} students");

    // Create question category.
    $coursecontext = \core\context\course::instance($course->id);
    $qcategory = new stdClass();
    $qcategory->name       = $cdata['fullname'] . ' - Fragen';
    $qcategory->info       = 'Auto-generated test questions';
    $qcategory->infoformat = FORMAT_HTML;
    $qcategory->contextid  = $coursecontext->id;
    $qcategory->parent     = question_get_default_category($coursecontext->id)->id;
    $qcategory->sortorder  = 999;
    $qcategory->stamp      = make_unique_id_code();
    $qcategory->id = $DB->insert_record('question_categories', $qcategory);
    cli_writeln("    Created question category (id={$qcategory->id})");

    // Create multichoice questions.
    foreach ($cdata['questions'] as $qi => $qdata) {
        // Create the question entry.
        $q = new stdClass();
        $q->category       = $qcategory->id;
        $q->parent         = 0;
        $q->name           = $qdata['name'];
        $q->questiontext   = '<p>' . $qdata['name'] . '</p>';
        $q->questiontextformat = FORMAT_HTML;
        $q->generalfeedback = '';
        $q->generalfeedbackformat = FORMAT_HTML;
        $q->defaultmark    = 1.0;
        $q->penalty        = 0.3333333;
        $q->qtype          = 'multichoice';
        $q->length         = 1;
        $q->stamp          = make_unique_id_code();
        $q->version        = make_unique_id_code();
        $q->timecreated    = time();
        $q->timemodified   = time();
        $q->createdby      = 2; // Admin.
        $q->modifiedby     = 2;
        $q->status         = \core_question\local\bank\question_version_status::QUESTION_STATUS_READY;
        $q->id = $DB->insert_record('question', $q);

        // Create question_bank_entry.
        $qbe = new stdClass();
        $qbe->questioncategoryid = $qcategory->id;
        $qbe->idnumber    = null;
        $qbe->ownerid     = 2;
        $qbe->id = $DB->insert_record('question_bank_entries', $qbe);

        // Create question_version.
        $qv = new stdClass();
        $qv->questionbankentryid = $qbe->id;
        $qv->questionid   = $q->id;
        $qv->version      = 1;
        $qv->status       = 'ready';
        $qv->id = $DB->insert_record('question_versions', $qv);

        // Create multichoice options.
        $mc = new stdClass();
        $mc->questionid          = $q->id;
        $mc->layout              = 0;
        $mc->single              = 1;
        $mc->shuffleanswers      = 1;
        $mc->correctfeedback     = 'Richtig!';
        $mc->correctfeedbackformat = FORMAT_HTML;
        $mc->partiallycorrectfeedback = '';
        $mc->partiallycorrectfeedbackformat = FORMAT_HTML;
        $mc->incorrectfeedback   = 'Leider falsch.';
        $mc->incorrectfeedbackformat = FORMAT_HTML;
        $mc->answernumbering     = 'abc';
        $mc->shownumcorrect      = 0;
        $mc->showstandardinstruction = 0;
        $DB->insert_record('qtype_multichoice_options', $mc);

        // Create answers — correct one first.
        $allanswers = array_merge([$qdata['answer']], $qdata['wrong']);
        shuffle($allanswers);

        foreach ($allanswers as $anstext) {
            $ans = new stdClass();
            $ans->question       = $q->id;
            $ans->answer         = $anstext;
            $ans->answerformat   = FORMAT_PLAIN;
            $ans->fraction       = ($anstext === $qdata['answer']) ? 1.0 : 0.0;
            $ans->feedback       = ($anstext === $qdata['answer']) ? 'Richtig!' : 'Falsch.';
            $ans->feedbackformat = FORMAT_HTML;
            $DB->insert_record('question_answers', $ans);
        }
    }
    cli_writeln("    Created " . count($cdata['questions']) . " multichoice questions");

    // Create LeitnerFlow activity in section 1.
    $moduleinfo = new stdClass();
    $moduleinfo->modulename       = 'leitnerflow';
    $moduleinfo->course           = $course->id;
    $moduleinfo->section          = 1;
    $moduleinfo->visible          = 1;
    $moduleinfo->name             = 'LeitnerFlow: ' . $cdata['fullname'];
    $moduleinfo->intro            = '<p>Lerne mit dem Leitner-Karteikasten-System!</p>';
    $moduleinfo->introformat      = FORMAT_HTML;
    $moduleinfo->questioncategoryid = $qcategory->id;
    $moduleinfo->sessionsize      = min(5, count($cdata['questions']));
    $moduleinfo->boxcount         = 3;
    $moduleinfo->correcttolearn   = 2;
    $moduleinfo->wrongbehavior    = 0; // Reset to box 1.
    $moduleinfo->questionrotation = 1; // Dynamic.
    $moduleinfo->prioritystrategy = 0; // Priority.
    $moduleinfo->grade            = 100;
    $moduleinfo->grademethod      = 1; // Percentage learned.

    $moduleinfo = add_moduleinfo($moduleinfo, $course);
    cli_writeln("    Created LeitnerFlow activity (cmid={$moduleinfo->coursemodule})");
}

// ─── Summary ───────────────────────────────────────────────────────────────────
cli_heading('Done!');
cli_writeln("Created:");
cli_writeln("  • " . count($coursedata) . " courses");
cli_writeln("  • " . count($students) . " students (enrolled in all courses)");
cli_writeln("  • Questions and LeitnerFlow activities in each course");
cli_writeln("");
cli_writeln("Student login: username format is vorname.nachname, password: Test1234!");
cli_writeln("Example: max.mueller / Test1234!");
cli_writeln("");
cli_writeln("To remove test data later: php mod/leitnerflow/cli/generate_testdata.php --clean");
