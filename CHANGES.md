# Changelog

All notable changes to **mod_eledialeitnerflow** (LeitnerFlow) are documented in
this file. Dates use ISO 8601. This project adheres to
[Semantic Versioning](https://semver.org/).

## [2.3.0] – 2026-04-04

### Added
- Multi-category support: activities can draw cards from multiple question-bank
  categories at once via a new `questioncategoryids` TEXT field.
- Box-to-box animation on the student view, with configurable duration
  (`animationdelay`, 500–3000 ms, default 1200 ms) and global on/off
  (`showanimation`).
- Five feedback styles selectable per activity — `off`, `minimal`, `encouraging`,
  `animated`, `gamified` — with current/best streak tracking in gamified mode.
- Interactive user tours (student + teacher) delivered via `tool_usertours`,
  auto-imported on plugin install/upgrade, translatable through the core
  multilang HTML filter.
- Teacher dashboard (`report.php`) with per-student progress bars, session
  counts, and reset-per-user action.

### Changed
- Upgraded to PHPUnit 11 attributes (`#[CoversClass]`, `#[DataProvider]`),
  eliminating all deprecation warnings.
- `calculate_box()` now uses a linear spread so box distribution is monotonic
  across `correcttolearn` boundaries.
- CSS selectors path-scoped under `.path-mod-eledialeitnerflow` to prevent
  cross-plugin leakage.

### Fixed
- Orphaned `grade_items` rows left over from the pre-rename component
  (`leitnerflow`) are migrated or cleaned up in upgrade step 2024120124,
  resolving a `coding_exception` raised during course/module edits.
- All 55+ Moodle Codechecker warnings and the lone error resolved — plugin is
  now 0 errors / 0 warnings on `phpcs --standard=moodle`.
- All 15 previously failing PHPUnit tests pass; full suite is 31 tests /
  70 assertions, green.

### Internal
- Added `bin/precheck.sh`: runs the nine Moodle Plugin Directory prechecks
  (phplint, phpcs, phpdoc, savepoint, js, css, mustache, grunt amd, thirdparty)
  plus PHPUnit inside the Docker webserver container.
- Added plugin-side PHPUnit data provider helpers and a dedicated generator.

## [2.2.0] – 2026-03-18

### Added
- Gradebook integration (`grademethod` setting: `none` or `percent_learned`).
- Report page listing all students with progress bars, session counts, and
  per-user reset button.
- Privacy API implementation covering both personal-data tables.

### Changed
- `eledialeitnerflow_sessions` table extended with `questionsasked`,
  `questionscorrect`, `timecompleted` for session-level analytics.

## [2.1.0] – 2026-02-10

### Added
- Backup/restore support (activity module backup API).
- `prioritystrategy` setting: `priority_box1` (default) or `random` card pick.
- `wrongbehavior` setting: reset to box 1, demote one box, or no penalty.

## [2.0.0] – 2026-01-22

### Changed
- **Renamed** from `mod_leitnerflow` to `mod_eledialeitnerflow` to match the
  eLeDia frankenstyle convention. Migration handled automatically in
  upgrade.php.

## [1.0.0] – 2024-12-05

### Added
- Initial release: Leitner spaced-repetition activity using the Moodle
  Question Engine as the card source and renderer. Supports 3–5 boxes,
  configurable `correcttolearn`, session size, and dynamic vs. fixed pool
  question rotation.
- Companion sidebar block `block_eledialeitnerflow` showing per-course
  progress for students and aggregate statistics for teachers.
