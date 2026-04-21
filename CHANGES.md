# Changelog

All notable changes to **mod_eledialeitnerflow** (LeitnerFlow) are documented in
this file. Dates use ISO 8601. This project adheres to
[Semantic Versioning](https://semver.org/).

## [1.0.0] – 2026-04-21

First public release, submitted to the Moodle Plugins Directory / Marketplace.
Earlier 1.x–2.x iterations existed as internal eLeDia pre-releases and are not
reflected here; the full development history is preserved in the git log.

### Activity (students)

- Leitner spaced-repetition flashcard activity backed by the Moodle Question
  Engine (`immediatefeedback` behaviour). Each question in the selected
  Question Bank categories becomes a virtual card that moves between boxes as
  the student answers.
- Configurable Leitner board: 1–5 boxes, `correcttolearn` threshold, questions
  per session, wrong-answer behaviour (reset to Box 1, back one box, no change),
  and card-selection strategy (lower boxes first, or mixed random).
- Visual student dashboard with per-box card counts, multi-segment progress
  bar, session history, per-session correct rate and duration, and a recent-
  vs.-all-time trend indicator.
- Five feedback styles — **Off**, **Minimal**, **Animated**, **Detailed**,
  **Gamified** — with configurable animation delay (500–3000 ms). Gamified mode
  tracks current and best streaks per session.
- Clickable boxes let students practise a single box in isolation.
- Guided first-visit user tour delivered via `tool_usertours`, auto-imported
  on plugin install/upgrade and translatable through the core multilang HTML
  filter (tour content ships in English and German).

### Activity (teachers)

- Multi-category question sourcing: activities can draw cards from one or
  many Question Bank categories simultaneously.
- Dynamic vs. fixed question pools — either always pull fresh from the
  Question Bank, or lock the pool the first time a student starts.
- Teacher report (`report.php`) showing per-student learned/open/error counts,
  progress bars, session totals, last-session timestamp, and a
  reset-progress action per participant.
- Summary dashboard: participant count, total questions in pool, average
  percentage of cards learned across all students.
- Optional gradebook integration: no grade, or `percentage of cards learned`.

### Platform integration

- Backup and restore via the activity-module backup API.
- Privacy API / GDPR compliant — `classes/privacy/provider.php` implements
  both `core_privacy\local\metadata\provider` and
  `core_privacy\local\request\plugin\provider`, plus
  `core_userlist_provider`; user export and deletion are fully supported.
- Course-reset support: per-activity progress and session history can be
  wiped through the standard course-reset form.
- Event logging: `session_started`, `session_completed`, `progress_reset`.
- Capabilities: `view`, `attempt`, `viewreport`, `manage`, `resetprogress`,
  `addinstance`.

### Code quality

- Moodle coding style: 0 errors, 0 warnings on `phpcs --standard=moodle`.
- PHPUnit: 37 tests / 81 assertions, all green (Moodle 5.1 + PHP 8.3).
- Behat acceptance tests covering the student attempt flow and the teacher
  report page.
- CSS scoped under `.path-mod-eledialeitnerflow` and the `lf-` prefix to
  prevent cross-plugin style leakage.
- User-tour installer is idempotent — re-calling it never produces duplicate
  tours, and an upgrade step de-duplicates any historical accumulation.
