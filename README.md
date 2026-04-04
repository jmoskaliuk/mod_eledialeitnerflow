# LeitnerFlow — Spaced Repetition Activity for Moodle

🇩🇪 [Deutsche Version](README-DE.md)

LeitnerFlow brings the proven Leitner flashcard system into Moodle. Questions from the Question Bank become virtual flashcards that move through a series of boxes. Correct answers advance a card; wrong answers send it back. Students work in short, focused sessions and track their progress through a visual dashboard.

**Requires:** Moodle 4.4+
**Current version:** 1.9.0
**License:** [GPL v3+](https://www.gnu.org/copyleft/gpl.html)
**Maintainer:** [eLeDia GmbH](https://eledia.de)

## How it works

A teacher creates a LeitnerFlow activity and selects one or more Question Bank categories. Each question becomes a flashcard that starts in Box 1. When a student answers correctly, the card moves to the next box. Wrong answers send it back (configurable). Once a card has been answered correctly enough times, it graduates to "Learned".

Cards in lower boxes appear more frequently, so students naturally focus on what they find hardest. The system supports three wrong-answer behaviours: full reset to Box 1, back one box, or no change.

## Features

**For students:**

- Visual Leitner box display showing card distribution at a glance
- Multi-segment progress bar (warm-to-cool colour gradient: orange → aqua → blue → green)
- Session history with correct rate, progress bars, and duration
- Trend indicator comparing recent sessions to overall average
- Clickable boxes to practise specific difficulty levels
- Card-move animation with configurable feedback style (minimal, encouraging, or off)
- Guided first-visit User Tour (multilingual: EN/DE)

**For teachers:**

- Flexible question sourcing from multiple Question Bank categories
- Configurable box count (1–5), session size, and correct-answers threshold
- Three wrong-answer strategies: reset, back one, no change
- Dynamic or fixed question pools
- Card selection priority: lower boxes first or mixed random
- Optional gradebook integration (percentage of cards learned)
- Per-student report with progress, session count, and reset capability
- Summary dashboard: participant count, question pool size, average learned percentage

**Technical:**

- Full Moodle Question Engine integration (immediatefeedback behaviour)
- Backup and restore support
- Privacy API / GDPR compliant (export + deletion)
- Event logging (session started, session completed, progress reset)
- Course reset support
- AMD JavaScript modules (keyboard shortcuts, animations, confirmation dialogs)
- 22 PHPUnit tests for the core Leitner engine

## Installation

1. Download or clone this repository into `mod/leitnerflow` within your Moodle installation:
   ```bash
   cd /path/to/moodle/mod
   git clone https://github.com/jmoskaliuk/moodle-mod_leitnerflow.git leitnerflow
   ```
2. Visit **Site Administration → Notifications** to trigger the database upgrade.
3. Create a LeitnerFlow activity in any course and select your Question Bank categories.

## Settings

| Setting | Options | Default |
|---------|---------|---------|
| Question categories | Multi-select from Question Bank | — |
| Question rotation | Dynamic / Fixed pool | Dynamic |
| Questions per session | Any number | 20 |
| Number of boxes | 1–5 | 3 |
| Correct answers required | Any number | 3 |
| On wrong answer | Reset to Box 1 / Back one box / No change | Reset |
| Card selection | Lower boxes first / Mixed random | Lower boxes first |
| Grading | None / % of cards learned | None |
| Card animation | Yes / No | Yes |
| Feedback style | Off / Minimal / Encouraging | Minimal |

## File structure

```
leitnerflow/
├── amd/src/              AMD JavaScript modules
│   ├── card_transition.js    Card-move animation + auto-redirect
│   ├── confirm_reset.js      Reset confirmation dialog
│   └── quiz_session.js       Keyboard shortcuts, auto-focus
├── backup/moodle2/       Backup & restore handlers
├── classes/
│   ├── engine/               Leitner spaced repetition algorithm
│   ├── event/                Session & progress events
│   └── privacy/              GDPR provider
├── cli/                  CLI tools (test data generation)
├── db/
│   ├── access.php            Capabilities
│   ├── install.php           Post-install tasks (User Tour, multilang filter)
│   ├── install.xml           Database schema (3 tables)
│   ├── upgrade.php           Migration steps
│   └── usertours/            User Tour JSON definition
├── lang/                 Language strings (EN + DE)
├── pix/                  Icons (SVG + PNG, Lucide style)
├── tests/                PHPUnit tests (22 test cases)
├── attempt.php           Question attempt page
├── lib.php               Moodle API callbacks
├── mod_form.php          Activity settings form
├── report.php            Teacher report
├── styles.css            Custom styles (eLeDia brand colours)
└── view.php              Student dashboard
```

## Capabilities

| Capability | Description | Default roles |
|------------|-------------|---------------|
| `mod/leitnerflow:addinstance` | Add a new activity | Editing teacher, Manager |
| `mod/leitnerflow:view` | View the activity | Student, Teacher, Manager |
| `mod/leitnerflow:attempt` | Answer questions | Student, Manager |
| `mod/leitnerflow:viewreport` | View student report | Teacher, Manager |
| `mod/leitnerflow:manage` | Manage settings | Editing teacher, Manager |
| `mod/leitnerflow:resetprogress` | Reset student progress | Editing teacher, Manager |

## Running tests

```bash
cd /path/to/moodle
php admin/tool/phpunit/cli/init.php
vendor/bin/phpunit --testsuite mod_leitnerflow_testsuite
```

## Contributing

Contributions are welcome. Please follow [Moodle coding standards](https://moodledev.io/general/development/policies/codingstyle) and include PHPUnit tests for new engine logic.

## License

This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
