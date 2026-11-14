# Handoff: Practicum foundation

## Deployment PHP CLI input limit (2026-11-14)

- Branch fix/deploy-php-cli-input-vars, based on origin/main 0def880 after PR #21
  merged. No plugin version/schema change, production deployment or PR merge.
- deploy.sh previously used PHP_BIN without Moodle's required max_input_vars
  setting. CLI and web SAPIs may load different INI files. New preflight runs as
  WEB_USER before source sync or maintenance, derives max(5000, current CLI value),
  logs binary/version, primary/scanned INI files and the effective setting, and
  verifies the override. All subsequent PHP calls (including lint) receive the
  explicit -d max_input_vars value. Higher limits remain unchanged; no INI file
  is edited. D027 records this deployment contract.
- Failure recovery output now prints upgrade && purge && maintenance-disable
  commands with the same override. Existing maintenance retention and refusal
  to deploy over pre-existing maintenance remain intact. tools/DEPLOY.md includes
  the manual recovery chain for a deployment already stopped by this error.
- Actual checks: bash -n deploy.sh tools/deploy-practicum.sh; six command-mocked
  deployments (1000 raised to 5000; 9000 preserved; upgrade/cache failures;
  unknown plugin type; failed PHP preflight), including consistent argv for
  every PHP call, unchanged installed files on preflight failure, safe recovery
  order and maintenance retention. Existing packaging tests also run.
- Actual Moodle verification: existing isolated Moodle 5.3.1 Build 20261112 /
  PHP 8.4.27 CLI. `python work/check-deploy-cli.py` uses temporary primary/scanned
  INI fixtures, the exact preflight probe extracted from deploy.sh, and Moodle's
  real check_max_input_vars function. At 1000, the required-setting failure was
  reproduced; with the selected override 5000, it passed. At 9000, it passed
  before/after with 9000 preserved. No server INI configuration, user server or
  Moodle DB was modified by those checks. This is not a full root deployment.

## Practicum 0.11.1: PR integration with timed training (2026-11-13)

- Published PR #21: https://github.com/thua48xsen3iemicy-boop/moodle-qtype_ipv4/pull/21
  Open for review; no PR merge or user-server deployment performed.

- Integrated origin/main c0ba17a (merged PR #20) into
  feat/practicum-participant-results before opening the combined PR. Keep its
  optional training time limit, duration/help text, frozen deadlines, unlimited
  training attempts, immediate feedback and no gradebook writes. Final module
  version 2026111302 / 0.11.1; D025/D026 supersede the conflicting timer/save
  parts of D023 while preserving its timed training support.
- Timed training Check waits for a pending autosave, preserves its clicked action
  in a hidden field, stops background saves and uses native form submission.
  This respects Moodle's disabled-button/repeat-submit guards, retains the
  question fragment and feedback, and avoids sending Check as an autosave.
  Updated main's late-response/cron tests to exercise the end of the 30-second
  transport allowance. No schema change or production deployment.
- Revalidated on Moodle 5.3.1 Build 20261112 / PHP 8.4.27 / PostgreSQL 17.12 /
  PHPUnit 11.5.56 with the full three-plugin command documented below:
  **121 tests, 94,692 assertions passed**. Native training browser command
  `python -u work/check-training-moodle-browser.py` passed Check feedback/fragment,
  autosave/reload/frozen questions, timed completion 100/100, expired partial work
  33.33/100, unlimited training and no gradebook entries; no JavaScript errors.
  Eight scenarios in `python -u tools/check-practicum-timer.py` passed, including
  a simulated Moodle repeat-submit guard and disabled question submit button.
  PHP syntax: all 83 files passed; JS syntax and diff checks passed. Packaging
  tests and mocked deployment checks passed. Previous exam and roster native
  browser checks remain recorded in the preceding implementation entries.

## Practicum 0.10.2: complete teacher participant roster (2026-11-13)

- Branch `feat/practicum-participant-results`, based on local timer fix 4273cf9;
  the update includes 0.10.1 fixes. Module 2026111301 / 0.10.2. No schema change,
  merge, push or user-server deployment.
- Teacher results now include enrolled learners with no attempts, displaying
  Not started / Не приступал with em dashes for grade and attempt link. Existing
  attempts display In progress / Выполняется or Completed / Завершено, preserving
  real zero grades, modes, all attempt links and repeat attempts. Name-sorted
  current learners come from Moodle enrolment/capability APIs, with custom roles
  and suspended enrolments included. Staff with report access are not shown as
  non-starters; staff/unenrolled users' actual attempts remain visible as before.
- New local participant_results service requires viewreports, derives activity
  id from its module context, and merges roster/history without creating attempts
  or gradebook entries. Teacher list still finalizes overdue work using the
  existing lifecycle. Learner view remains restricted to the current user's
  attempts. D026 documents the roster contract.
- Native Moodle 5.3.1 Build 20261112 / PHP 8.4.27 / PostgreSQL 17.12 / PHPUnit
  11.5.56: local module upgrade and separate PHPUnit database upgrade succeeded.
  `docker exec practicum-policy-php php -d max_input_vars=5000 -d memory_limit=512M vendor/bin/phpunit --testsuite mod_practicum_testsuite,qtype_ipv4_testsuite,qtype_linuxcmd_testsuite`
  passed **115 tests / 94,643 assertions**. Six new cases cover both modes,
  non-starters, staff/other-course exclusion, no phantom attempts, true zero
  grades, custom roles, suspended enrolments, multiple historical attempts after
  unenrolment, expiry and student denial of the roster service.
- Chromium / native Boost UI:
  `python -u work/check-participant-browser.py` passed teacher training/exam lists
  with idle/working/finished students, a third activity with nobody started,
  status/grade/link checks and absence of the teacher as a non-starter. Student
  training/exam pages showed only that student's attempts, no classmates.
  Teacher screenshots in ignored work/participant-results-{0,1,2}.png; inspected
  the exam table. Seed and browser scripts/logs are ignored work artifacts.
- PHP 8.4.27 syntax: 83 source PHP files passed; git diff --check passed.
  Existing packaging tests and release archive membership are checked for this
  release. No production data, other themes/browsers, very large course load or
  pagination tested. The list keeps the existing unpaginated activity layout.

## Practicum 0.10.1: timed assessment saving (2026-11-13)

- Branch `fix/practicum-timed-attempts`, based on main 5542ce0. Module version
  2026111300 / 0.10.1. No schema/qtype changes, user-server deployment or merge.
- Confirmed loss mechanism: attempt.php reloaded at the deadline without posting
  the form; attempts::process rejected all POST answers at/after that deadline.
  Only manually persisted answers could be graded. There was no live countdown.
- Added visible countdown and save status, native question-engine autosaves after
  input (700 ms debounce), periodic retries, serialized manual/final submission
  and freeze/capture of answers at zero. Native autosaves keep sequence checks
  stable if their acknowledgement is lost. Requests time out after 8 seconds;
  final delivery retries the captured payload every 5 seconds. Moodle's
  js_init_call prepends YUI, so init explicitly accepts `(Y, seconds)`.
- D025 defines the 30-second server transport allowance. GET/cron cannot finish
  ahead of the final form during that window; a POST during the window finishes
  immediately. Later requests grade saved work only. Expired readonly forms
  submit finishsaved without blank question data. Existing deadlines/plans/usages
  remain untouched. Ownership, capability and sesskey checks precede JSON saves.
- Activity listing finalizes overdue attempts, offers Continue for active work,
  and hides Start when the exam limit is exhausted, retaining Review. Existing
  server limit enforcement was already present. The reported question changes
  were NOT reproduced: real-engine tests and browser reload/review kept original
  questions, usage, plan and one attempt. No production records were available
  to diagnose the specific complaints. No retrospective regrading/data recovery;
  answers never sent to the server cannot be recovered from the Moodle DB.
- Actual native verification: Moodle 5.3.1 Build 20261112, PHP 8.4.27, PostgreSQL
  17.12, PHPUnit 11.5.56, Linux / Boost. Installed a separate ignored work/moodle
  checkout, isolated normal/test table prefixes and dataroots, Docker containers
  practicum-policy-php and practicum-policy-db. Full command:
  `docker exec practicum-policy-php php -d max_input_vars=5000 -d memory_limit=512M vendor/bin/phpunit --testsuite mod_practicum_testsuite,qtype_ipv4_testsuite,qtype_linuxcmd_testsuite`
  passed **109 tests / 94,615 assertions**, no warnings. Covers native autosave
  retry, final POST grace, partial saved work after cron expiry, gradebook,
  repeated starts, immutable questions/settings, existing families and backup.
  Fixed a pre-existing PHPUnit loader issue by declaring global CFG before the
  form include in mod_form_test.php.
- Actual Chromium browser run: `python -u work/check-timed-moodle-browser.py`
  against that native install passed both learner scenarios: countdown/automatic
  finish 100/100 and partial saved work/expired-page finish 33.33/100. Verified
  autosave, saved responses after reload, exact question snapshots, gradebook,
  exhausted Start button, one stored attempt and Review. No JavaScript errors.
  Fixture scripts, logs and screenshots remain ignored in work/.
- `python -u tools/check-practicum-timer.py`: seven Chromium scenarios passed,
  including slow saves, freeze at zero, manual finish during autosave, failed
  final POST retry, lost autosave acknowledgement and readonly expired page.
  This deterministic HTTP fixture supplements the native browser run.
- PHP 8.4.27 syntax: all 81 source PHP files passed. Standalone commands
  `LD_LIBRARY_PATH="$PWD/work/php-runtime/usr/lib/x86_64-linux-gnu" work/php-runtime/usr/bin/php8.4 -n tools/check-practicum-lifecycle.php`
  and the same runtime with `tools/check-linuxcmd.php` passed 47 policy and
  75,531 family/configuration assertions respectively (stand-ins, not native).
  Two packaging tests plus mocked deployment checks, JS syntax and diff checks
  passed. Test dependency archives used official codeload GitHub at locked refs;
  only ignored test-install composer.lock URLs changed, no plugin dependency
  versions changed. Other themes/browsers, multi-tab conflicts, disconnected
  clients beyond the transport allowance and user production data are unverified.
## Practicum 0.11.0: timed training (2026-11-12)

- Published PR #20: https://github.com/thua48xsen3iemicy-boop/moodle-qtype_ipv4/pull/20
  Open for review against main; no merge or user-server deployment.
- Branch `feat/training-time-limit`, based on main `5542ce0` (PR #19 merged).
  Module version 2026111200 / 0.11.0. No DB migration or qtype changes.
- The existing optional duration is visible in training and exam settings, with
  RU/EN help. The activity page announces the configured duration before start;
  an active attempt displays its frozen deadline through the existing renderer.
- New training attempts snapshot a positive limit just like exams. Zero remains
  untimed. Existing deadlines/plans/usages are untouched, including previously
  untimed training when the activity is later given a limit. Positive limits
  already stored on training activities now apply to their next new attempt.
- Reuses existing browser refresh and locked lifecycle/cron expiry. Saved answers
  are graded, unanswered slots finish, late POST data is rejected, and teachers
  can review the stored result. Training retains immediate feedback, unlimited
  attempts and no gradebook writes. Explicit Save/Check is still necessary;
  no autosave. Moodle cron must run regularly for closed browser pages.
- Added real-Moodle lifecycle regressions for timed/untimed training, frozen
  deadlines and new limits on subsequent attempts, partial saved/checked answers,
  late data rejection, idempotent finish, cron expiry with an untimed control,
  gradebook separation, and timed training/exam backup/restore.
- Actual browser run: Moodle 5.3+ Build 20261030 (core `236a9d7`), PHP 8.4.27,
  PostgreSQL 17.11, Boost, Chromium 156.0.8078.79, desktop 1440px.
  `python -u work/check-training-timer.py`: all checks passed. A real 15-second
  training saved 1/4 answers and automatically became readonly at 25/100.
  Untimed manual finish, teacher result/review, duration visibility in both modes,
  edit/save/reopen with changed duration and disabling the limit all passed.
  No learner JS errors. Fixtures, screenshots and logs remain ignored in work/.
- PHP 8.4.27 syntax: all 80 source PHP files passed. Isolated
  `docker exec --user 1000:1000 practicum-timer-test php tools/check-linuxcmd.php`:
  75,531 assertions passed (stand-ins, separate from native integration).
  `python -m unittest discover -s tools/tests -v`: two packaging tests and mock
  deployment success/failure/unknown-type checks passed. `git diff --check` passed.
- Native Moodle 5.3 integration passed on the runtime above, PHPUnit 11.5.56:
  `docker exec --user 1000:1000 -w /app/work/moodle practicum-timer-test php vendor/bin/phpunit --testsuite qtype_ipv4_testsuite,qtype_linuxcmd_testsuite,mod_practicum_testsuite`
  — 112 tests, 94,631 assertions, no failures/warnings. Includes the new lifecycle
  and cron tests plus both modes of real course backup/restore. Added the missing
  global CFG import to the form test so PHPUnit's scoped loader can load it.
- Unrun: PHP 8.3, other DBs/themes, Mobile, Russian core UI, full Moodle core suite,
  production scheduled runner timing and load/concurrency. The cron task itself
  is exercised directly in lifecycle tests. No user-server deployment or merge.

## Practicum 0.10.0: first learning topic (2026-11-11)

- Published PR #19: https://github.com/thua48xsen3iemicy-boop/moodle-qtype_ipv4/pull/19
  Awaiting user review/merge; no user-server deployment.

- Branch `feat/ipv4-order-learning-topic`, initially based on d1f6a03; integrated main 1fbbc7a before PR.
  Module version 2026111100 / 0.10.0 retains current qtype dependencies.
- Teacher adds a separate native Practicum course activity, selects “IPv4 basics:
  previous and next address” under Activity content, and chooses 1–50 questions
  per type (default 4, eight total). Existing training/exam modes remain available.
  This is a ready-made topic within mod_practicum, not a new Moodle component.
- Bundled RU/EN theory covers 32 bits/four octets, 2^8 values and 0–255,
  increment/decrement with multi-octet carry/borrow, non-wrapping global bounds,
  numeric adjacency versus usable hosts, examples and expandable self-check.
  Theory precedes practice on the activity page. Training has an expandable
  inline reference; unfinished exams do not. Finished attempts show it again.
- Uses existing ipv4_nextip/ipv4_previousip adapters, generated conditions,
  text answers, explanations and grading. Prefix /24 is an internal adapter
  requirement only, not shown/used in address-order questions. Existing random
  generation includes octet-boundary cases but does not guarantee each case.
- Versioned topic marker lives in the existing blocks JSON and ordinary concrete
  slot settings. Backup already copies both. No DB migration, template-key change,
  qtype change or attempt regeneration. Attempt reference reads its own plan,
  so later activity edits cannot remove/change that saved topic identity.
- Actual checks: PHP 8.4.27 syntax, all 78 repository PHP files passed;
  `LD_LIBRARY_PATH="$PWD/work/php-runtime/usr/lib/x86_64-linux-gnu" work/php-runtime/usr/bin/php8.4 -n tools/check-linuxcmd.php`
  passed 9,516 isolated assertions (Moodle stand-ins, NOT integration). New checks
  cover type/count scope, hidden stale controls, limits, unknown topics, saved
  markers/template reuse and form edit/custom-switch roundtrip.
- Chromium 156.0.8078.79 via `python work/check-topic-browser.py`: RU/EN theory
  rendered by the real helper with minimal html_writer stand-in, 1440/390 widths,
  disclosure toggles and no horizontal overflow passed. All arithmetic examples
  independently checked with Python ipaddress. Screenshots/fixtures in ignored
  work/. This does NOT validate native Moodle form/Boost/attempt rendering.
- Two packaging tests and mocked deployment success/upgrade-failure/cache-failure/
  unknown-type checks passed; git diff --check passed. Release module archive
  built and checked for topic inclusion/version and exclusion of scratch files.
  Added real-Moodle lifecycle
  coverage for both modes, correct answers/100 grade and saved topic plan after
  activity edits. NOT RUN: Moodle 5.3 core/DB/PHPUnit absent. Required next: real
  module upgrade, form create/edit/switch, learner training/check/resume, exam,
  gradebook and course backup/restore on Moodle 5.3. No deployment/push/merge.
- Pre-PR integration (2026-11-11): merged origin/main 1fbbc7a, preserved CIDR
  requirements, multiselect blocks and Linux permissions, resolved documentation/
  language append conflicts, assigned D022 and bumped module to 0.10.0/2026111100.
  Revalidation: PHP 8.4.27 syntax on 80 files; isolated harness 75,531 assertions;
  two packaging tests and mocked deployment scenarios passed. Native Moodle 5.3
  integration remains unrun for the same missing-core/DB blocker above.
- Scope: one mode per activity as before; no combined training/final-exam flow,
  automatic mastery completion/unlocking, editable theory or adaptive repeats.

## Practicum 0.9.0 / Linuxcmd 0.3.0: Linux permissions (2026-11-10)

- Published PR #18: https://github.com/thua48xsen3iemicy-boop/moodle-qtype_ipv4/pull/18
  Awaiting user merge; no user-server deployment.

- Branch `feat/linux-permissions`, based on main 83f64f4 after PR #17 merged.
  No user-server deployment or merge performed.
- Separate permissions category with 17 stable linux_* types: owner, owner+group,
  recursive owner, group, recursive group, numeric/symbolic chmod, set/remove SUID,
  directory SGID, set/remove sticky, octal↔letters, file/directory r/w/x meaning,
  special-bit meaning (including executable SGID). Files category stays 10 types.
  Category selection/once/mixed blocks reuse the existing planner.
- New pure-data permissions evaluator, version-2 frozen question snapshots and
  v2 template keys for new types only. Literal command parsing/path traversal,
  exact recursion/ownership and resulting mode checks; explicit notation for
  numeric/symbolic tasks. Symbolic directory assignment preserves SUID/SGID as
  GNU chmod does. No learner input is executed. ACLs and chattr/lsattr excluded.
- RU/EN tasks/labels/help, short-response labels without irrelevant trees,
  explanatory feedback, conversion including uppercase S/T. Existing version-1
  file scenarios and template keys keep their grading. No schema migration.
  qtype_linuxcmd 2026111003 / 0.3.0; mod_practicum 2026111008 / 0.9.0 requires it.
- Actual validation: PHP 8.4.24 extracted from official Debian trixie packages.
  `LD_LIBRARY_PATH="$PWD/work/php-runtime/root/usr/lib/x86_64-linux-gnu" work/php-runtime/root/usr/bin/php8.4 -n tools/check-linuxcmd.php`:
  75,491 assertions passed (Moodle stand-ins, NOT native PHPUnit/core/DB).
  Includes all 27 Linux types × 5 path settings, roundtrip generation, frozen
  state/regrading, notation/effect/path/flag rejection and permissions once plans.
- `python work/verify-permissions.py`: all 4,096 octal masks match Python
  stat.filemode, 288 symbolic transformations match GNU chmod on scratch regular
  files/directories. This uses generated controlled fixtures, never learner input.
  `work/check-permission-languages.php`: all task strings/placeholders pass RU/EN.
- All 79 source PHP files pass syntax. Packaging tests: 2 passed; mock deployment
  success/failure/unknown-type checks passed. git diff --check passed.
- Native Moodle tests extended for all-type XML settings, mixed training/exam,
  unchanged reload after settings changes, backup/restore of all Linux types.
  NOT RUN: Moodle 5.3/core/DB/PHPUnit, native UI/upgrade; this environment has no
  installed Moodle or DB/runtime container. These remain required before rollout.

## Practicum 0.8.0: multiple types per advanced block (2026-11-10)

- Published PR #17: https://github.com/thua48xsen3iemicy-boop/moodle-qtype_ipv4/pull/17
  Awaiting user merge; no user-server deployment.

- Branch `feat/multiselect-question-blocks`, based on main 8ca9ced after PR #16.
- Advanced blocktype is now a native multiple autocomplete. Count label and RU/EN
  instructions clarify N per selected type (3 × 5 = 15), additional blocks for
  other counts/prefixes. Prefixes remain visible with help explaining IPv4-only
  use; the old hide rule was unsafe for mixed IPv4/file multiselects.
- Legacy scalar form values remain accepted. Single-type rows retain old JSON;
  multi-type rows use existing each planning with layout=block UI metadata.
  Preprocessing restores grouped rows/counts/prefixes, and old single types become
  one-item multiselects. Deduplicate within each row; independent rows can repeat
  a type with different settings. Expanded total limit remains 100.
- mod_practicum 2026111007 / 0.8.0. No DB/qtype/template changes; saved attempt
  plans and conditions remain unchanged.
- Isolated PHP 8.4.27 harness:
  `LD_LIBRARY_PATH="$PWD/work/php-runtime/root/usr/lib/x86_64-linux-gnu" work/php-runtime/root/usr/bin/php8.4 -n tools/check-linuxcmd.php`:
  66,083 assertions passed with Moodle stand-ins. Covers per-type counts across
  multiple rows, mixed families, prefix isolation, row deduplication, zero-count
  deletion, limits, empty/unknown types, edit grouping and legacy form roundtrip,
  plus existing IPv4/CIDR/path/state regressions.
- PHP 8.4.27 syntax: all 77 source PHP files passed. Two packaging tests, mock
  deployment success/failure/unknown-type checks and git diff --check passed.
- Added a native lifecycle test for grouped mixed blocks, actual counts, grading
  and unchanged resume after configuration edits. NOT RUN: Moodle 5.3/core/DB/
  PHPUnit and native repeated autocomplete UI; this checkout lacks Moodle/DB.
  Verified Moodle 5.3 source supports cloned autocomplete/select attributes in
  repeat_elements; actual core/Boost rendering still needs integration checking.
- No user-server deployment or PR merge performed.


## IPv4 0.5.0 / Practicum 0.7.1: network CIDR answers (2026-11-10)

- Published PR #16: https://github.com/thua48xsen3iemicy-boop/moodle-qtype_ipv4/pull/16
  Merged by user into main (8ca9ced); no user-server deployment.

- Branch `feat/require-network-prefix`, based on main d1f6a03 after PR #15 merged.
- New network-address attempts require address/prefix. Both parts are checked;
  missing prefix is invalid format, wrong prefix/address grades 0. Dotted-mask
  suffixes remain unsupported. Native quiz radio choices include the prefix too.
  Other exercise formats and the 12-type subnet category remain unchanged.
- New snapshots store networkformat=cidr inside _ipv4. Old snapshots without it
  retain old answers/choices/conditions and grading after restore/regrade. New
  task/validation RU/EN strings explain the format. Low-level IPv4 parsing and
  calculation APIs remain unchanged; response normalizer takes an optional flag.
- Network Practicum templates now use v4 keys with networkformat=cidr, preserving
  old questions and other template keys. qtype_ipv4 2026111001 / 0.5.0;
  mod_practicum 2026111006 / 0.7.1 requires it. No DB schema changes.
- PHP 8.4.27 syntax: all 77 source PHP files passed. Expanded standalone harness
  `LD_LIBRARY_PATH="$PWD/work/php-runtime/root/usr/lib/x86_64-linux-gnu" work/php-runtime/root/usr/bin/php8.4 -n tools/check-linuxcmd.php`:
  66,045 assertions passed. Added DB-independent root IPv4 exercise/question
  suites to the existing stand-in harness; actual core/DB suites are not run.
  Coverage includes all /1–/30, text/choice, correct/wrong/missing prefixes,
  malformed notation, generated choices, legacy snapshots and template isolation.
- Chromium 156 / Python Playwright:
  `python -u work/network-cidr/check-browser.py` passed at 1440/390 px. Fixture
  loads real question.php and renderer.php with Moodle stand-ins. RU task/error
  text, correct address/prefix, wrong prefix/address, missing prefix, dotted-mask
  rejection and legacy bare-address acceptance verified. Screenshots in ignored
  work/network-cidr/. This is NOT a real Moodle browser integration test.
- Two packaging tests and mock deployment success/failure/unknown-type checks
  passed; git diff --check passed. Native Moodle 5.3/DB/PHPUnit, actual upgrade,
  full regrade/backup and Boost UI remain unrun: no Moodle install/database here.
  No user-server deployment or merge performed.


## Practicum 0.7.0: one question per selected type (2026-11-10)

- Published PR #15: https://github.com/thua48xsen3iemicy-boop/moodle-qtype_ipv4/pull/15
  Merged by user into main (d1f6a03); no user-server deployment.

- Branch `feat/one-question-per-type`, based on main d3f5837 after PR #14 merged.
- Added explicit once selection mode (“One question per type, no repeats”).
  Selected categories and extra individual types expand to a unique union;
  each type contributes exactly one question, then order is shuffled. Count
  entry is hidden and stale sharedcount ignored; saved once count must equal 1.
- Category checkboxes display the current number of registered types, including
  Subnets: 12. Once has its own instructions distinguishing type coverage from
  random addresses/masks. Allowed prefixes remain configurable and independently
  random. Each/random/advanced retain previous behaviour; new activities still
  default to each. Once errors attach to the visible mode field, not hidden count.
- Snapshot/UI metadata and version-1 slot shapes are reused. Existing attempts
  and saved configurations are unchanged. No DB migration or qtype changes.
  mod_practicum 2026111005 / 0.7.0.
- PHP 8.4.27 syntax: all 44 plugin/harness PHP files passed. Command
  `LD_LIBRARY_PATH="$PWD/work/php-runtime/root/usr/lib/x86_64-linux-gnu" work/php-runtime/root/usr/bin/php8.4 -n tools/check-linuxcmd.php`:
  9,476 assertions passed using stand-ins, NOT Moodle integration/PHPUnit.
  Covers 12 subnet types exactly once over repeated plans, stale/omitted counts,
  category+individual deduplication, select-all, invalid stored count, total limit,
  count labels, mode/category edit roundtrip and prior generation/state regressions.
- Two packaging tests and mock deployment success/failure/unknown-type checks
  passed. git diff --check passed. No real deployment performed.
- Real Moodle lifecycle test now selects all IPv4 categories in once mode and
  checks complete type coverage, both training/exam, grading and unchanged resume
  after settings edits. NOT RUN: Moodle 5.3/core/DB/PHPUnit and native browser
  form checks; this checkout lacks the Moodle installation and database.


## Practicum 0.6.1: stay at the checked training question (2026-11-10)

- Published PR #14: https://github.com/thua48xsen3iemicy-boop/moodle-qtype_ipv4/pull/14
  Merged by user into main (d3f5837); no user-server deployment.

- Branch `fix/training-question-position`, based on main b2eab11 after PR #13
  was merged. Root cause: attempt.php redirected every POST to the bare attempt
  URL, losing the checked question position.
- `attempt_navigation::after_submit` uses the saved usage's native behaviour
  submit field and outer question id to anchor the redirect. Only active training
  checks receive a question anchor. Saving/exam/finished/expired attempts receive
  an explicit empty fragment, preventing browsers inheriting the prior question
  fragment. CSS scroll-margin-top keeps questions below the fixed navigation bar.
  Native question-engine POST/redirect/GET, capabilities, sesskey, grading and
  stored attempts remain unchanged. No client JS/AJAX or database changes.
- mod_practicum 2026111004 / 0.6.1; qtypes unchanged.
- PHP 8.4.27: all 44 plugin/harness files passed syntax; existing isolated suite
  tools/check-linuxcmd.php passed 8,173 assertions; navigation fixture passed
  11 assertions, including unknown usage/slot, save, exam and finished cases.
- Packaging tests: 2 passed; mock deployment success/failure/unknown-type checks
  and git diff --check passed. No real deployment was performed.
- Chromium 156 with Python Playwright:
  `python -u work/training-navigation/check-browser.py` passed desktop 1440 px
  and mobile 390 px, each with JavaScript enabled/disabled. On a 50-question PHP
  fixture using the real navigation helper and stylesheet, Check on 37 and 45
  redirects to that question with feedback visible below a fixed header; Finish
  clears the question fragment and returns to top. Initial browser run found
  fragment inheritance on Finish; explicit empty fragment fixed it. Screenshots
  and fixture scripts remain in ignored work/training-navigation/.
- Browser fixture uses Moodle stand-ins, NOT a real Moodle site. Actual Moodle
  5.3 API source verified for native field/id and URL fragment methods. Added a
  real question-engine lifecycle test for redirect/finish behaviour; NOT RUN:
  Moodle core/DB/PHPUnit are absent. Native Moodle/Boost UI, other themes and
  browsers still need integration checks. No user-server deployment or merge.


## Practicum 0.6.0: category selection and file-operation naming (2026-11-10)

- Published both changes together in PR #13:
  https://github.com/thua48xsen3iemicy-boop/moodle-qtype_ipv4/pull/13
  `feat/question-category-selection` includes `feat/random-linux-paths` as an
  ancestor; no separate random-path PR is needed. Remote main 1cd5b25 is also an
  ancestor; GitHub reported mergeable with no conflicts. Awaiting merge by user.

- Branch `feat/question-category-selection`, based on automatic-path commit
  78de9ea. Native category checkboxes in each/random form modes: subnet, address,
  routing, virtual, diagnostic, files. Reuses IPv4 catalog grouping and the file
  exercise list; no duplicate list of task members. Individual selection remains.
- Form save unions checked categories and individual types, removes duplicates,
  checks the expanded total limit and saves concrete type snapshots. Optional
  groups/individualtypes metadata restores category checkboxes and extra types
  on edit. Old configurations still reopen; all-types overrides category choices.
  Newly registered category members enter on resave; existing attempts stay fixed.
- Renamed “Linux” family to “File and path operations” in RU/EN activity type
  labels and native qtype name/editor headings/path label. Component names,
  linux_* ids and immutable template identities remain unchanged.
- mod_practicum 2026111003 / 0.6.0; qtype_linuxcmd 2026111002 / 0.2.1.
  No DB schema changes; native Moodle upgrade refreshes versions/language caches.
- Actual validation: PHP 8.4.27 syntax on all 43 plugin/harness files passed;
  `LD_LIBRARY_PATH="$PWD/work/php-runtime/root/usr/lib/x86_64-linux-gnu" work/php-runtime/root/usr/bin/php8.4 -n tools/check-linuxcmd.php`:
  8,173 assertions passed using stand-ins, not Moodle core/DB/PHPUnit. Tests cover
  category coverage, category-only each/random plans, multiple groups plus extras,
  deduplication, total limits, invalid categories, real preprocessing roundtrip,
  old configuration editing, existing state/grading/path-generation regressions.
- Packaging unit tests: 2 passed. Mock deployment success/failure/unknown-type
  checks and `git diff --check` passed; no real deployment performed.
- Real Moodle tests updated for category-selected pool resume after edits and
  backup/restore with category metadata. NOT RUN: native Moodle 5.3 installation,
  DB/PHPUnit/browser form and upgrade checks; this checkout has no Moodle core,
  database, configured web app or PHPUnit. No server deployment or PR merge.


## Practicum 0.5.1: automatic Linux path styles (2026-11-10)

- Branch `feat/random-linux-paths`. Removed shared and repeated per-block path
  selectors from activity creation/editing, including preprocessing/help/hide rules.
  Form saves use `random`; submitted obsolete path fields cannot override it.
- Each operand independently chooses relative with probability 2/3, absolute 1/3.
  Added `reverse` for absolute source/relative destination; all four combinations
  can appear. Conditions specify the requirement; structure renderer unchanged.
  Native quiz editor retains manual modes and now also exposes reverse/random.
- Existing saved blocks keep their mode until resaved. Started attempts, old v1
  grading and template keys stay unchanged. Random templates have distinct keys;
  generated concrete mode/styles are frozen in `_linuxcmd`. No table migration.
- mod_practicum 2026111002 / 0.5.1 requires qtype_linuxcmd 2026111001 / 0.2.0.
- PHP 8.4.27 extracted from official Debian packages into ignored work/php-runtime.
  `LD_LIBRARY_PATH="$PWD/work/php-runtime/root/usr/lib/x86_64-linux-gnu" work/php-runtime/root/usr/bin/php8.4 -n tools/check-linuxcmd.php`:
  7,915 assertions passed (isolated stand-ins, not Moodle/PHPUnit). All 42 plugin
  and harness PHP files passed syntax. Coverage: all 10 operations × 5 modes,
  both mixed directions, random weighting/combinations, frozen state/regrading,
  form modes, obsolete fields, old template identities and IPv4 regressions.
- Packaging unit tests: 2 passed; mocked deployment success/failure/unknown-type
  checks passed. `git diff --check` passed. These are not a real deployment.
- Updated real Moodle lifecycle tests for random backup/restore and all saved
  modes. NOT RUN: Moodle 5.3/DB/PHPUnit/browser checks, because this checkout has
  no Moodle installation/database/web app. No server deployment or merge.


## Practicum 0.4.0: Linux files and paths

- PR #11: https://github.com/thua48xsen3iemicy-boop/moodle-qtype_ipv4/pull/11
- Branch `feat/practicum-linux-paths` updated with main (48f444d) to preserve all
  41 IPv4/ip-command tasks and searchable labels alongside 10 file/path tasks.
  Module release is 0.4.0; dependency qtype_ipv4 >= 2026110903. No PR merge or
  deployment performed.
- New `qtype_linuxcmd` 0.1.0 at `plugins/linuxcmd` installs in
  `public/question/type/linuxcmd`. Required by mod_practicum 0.4.0; the manifest
  builder/deployer discovers the third component without special cases.
- Ten `linux_*` operations: mkdir/mkdirparents/touch/copyfile/copytree/rename/
  move/rmdir/removefile/removetree. Each block selects absolute, relative or mixed
  paths. Mixed means relative source/absolute destination, unary uses relative.
- Literal command parser supports quoting/escaping, permitted flags and --.
  Checks operations, operand count/style, path traversal and destination semantics.
  No shell/process execution; no real filesystem or terminal backend is involved.
- Generated v1 scenarios are saved in `_linuxcmd`; template identity includes
  type/pathmode. Reload restores original conditions. IPv4 keys/usages unchanged.
- RU/EN UI, native qtype editor, extra-field persistence, XML import/export,
  backup/restore handler and null Privacy provider included. Existing activity
  lifecycle/permissions/deadlines/grades are reused.
- Local PHP 8.4.27 (Debian CLI extracted under ignored work/php-runtime) used for
  syntax and isolated checks. `tools/check-linuxcmd.php`: 3,499 assertions passed,
  including existing IPv4 configuration/key regressions. This uses stand-ins,
  NOT Moodle core/DB or PHPUnit. Packaging and mock deployment checks passed.
  All 42 PHP files passed syntax checks; three ZIPs and manifest built/verified
  under ignored dist/. `git diff --check` passed.
- Real Moodle tests added for all 30 Linux type/mode combinations mixed with
  IPv4 in training/exam, reload/settings edits, gradebook, XML and backup/restore.
  NOT RUN: no installed Moodle, DB, vendor/PHPUnit or shared web server in this
  checkout. Required next: install all three packages on a Moodle 5.3 test site,
  run qtype_linuxcmd/mod_practicum suites and browser-check repeated block fields,
  training feedback, assessment/review and upgrade with an existing IPv4 attempt.
- See docs/VALIDATION.md for exact commands and GNU coreutils reference cases.

## Read first
AGENTS.md → docs/PROJECT.md → docs/DECISIONS.md → docs/ARCHITECTURE.md.

## Implemented
- `mod/practicum`: native course activity with training and assessment modes.
- Mixed block planning contract, explicit type registry, versioned attempt plans.
- IPv4 adapter using existing qtype; manual input only in Practicum; independent random prefixes.
- Reused immutable templates per configuration; independent Moodle question usages.
- Frozen attempt mode/deadline, resume, manual save, finalisation, best exam grade.
- Server-enforced expiry and scheduled task; student ownership and standard capabilities.
- Course backup/restore including question usages; Privacy export/delete provider.
- RU/EN strings, Moodle module form, generator and lifecycle tests.
- `tools/package.py`: separate ZIPs for both install locations, excludes work and secrets.

## Validation environment
Moodle 5.3+ (Build 20261030), PHP 8.4.24, PostgreSQL 17.11, PHPUnit 11.5.56.
Local environment in ignored work/. PHP launcher work/php. Moodle copy in work/moodle.
IMPORTANT: installed plugins are copies, not symlinks. Sync changed files before tests.
Command: LOCPATH=/workspace/moodle-qtype_ipv4/work/locales work/php
work/moodle/vendor/bin/phpunit -c work/moodle/phpunit.xml --testsuite mod_practicum_testsuite
(single command on one line).
7 tests / 28 assertions passed, including backup restoration of generated questions
and privacy deletion of owned usage. UI training save/reload/finish gives 100/100;
native activity creation and student assessment save/reload/finish (100/100) tested. See docs/VALIDATION.md for final results.

## Known limits / next work
- First alpha. No lab backend implementation or URL/API for the existing GNS3 project.
- No autosave: deadline grades saved data only. Do not silently accept late browser data.
- Training immediatefeedback fixes an answered question; restart the activity for another attempt.
- Grade policy is best completed exam; review reveals solutions after finishing.
- Further families need settings UI and qtype adapters; registry is not a Moodle subplugin loader.
- Future template schema changes must change versioned key; do not mutate old templates.
- Browser Russian core language pack and PHP 8.3 full integration remain unavailable.
- Privacy export, time expiry cron on a live scheduled runner, accessibility audit,
  concurrency stress, completion criteria, other themes/databases and Mobile need further coverage.
- No migration of old quiz attempts. Old qtype and bulk generator are kept compatible.

## Agent cooperation
No active delegated tasks. Keep file ownership notes here if parallel work is assigned.
Do not overwrite other agents' notes; append decision/result with context and tests.

## qtype IPv4 exercise expansion (PR #4)

- Branch `feat/ipv4-exercise-types`: qtype 0.3.0 adds 12 selectable exercise variants,
  prefixes /1–/30, text/choice grading and a backwards-compatible DB upgrade.
- Selection is implemented in the qtype editor and quiz bulk generator. Practicum's
  registry/settings remain the network-only foundation; exposing these variants
  in Practicum requires a separate adapter/UI change. Existing network adapter
  calls retain the default network type.
- Merged current `main` (Practicum foundation) into this branch without conflicts.
- Validation in this checkout: PHP 8.4.24 syntax, isolated calculation/state/grading
  harness (64,303 assertions) and 7,200 independent Python ipaddress comparisons.
  This is not a Moodle PHPUnit run. See TESTING.md.
- Historical `work/php` and `work/moodle` described above are absent in this
  checkout. Actual Moodle DB upgrade, full plugin suites and browser checks
  remain required before release; no server deployment or PR merge performed.

## Automated server deployment helper

- `tools/deploy-practicum.sh` syncs the dedicated checkout of repository main,
  builds both ZIPs, installs both plugins in Moodle 5.3's public/ layout and runs
  maintenance → upgrade → purge caches → disable maintenance on success.
- Defaults: /var/www/moodle, www-data, /opt/moodle-practicum-src.
- File backups and commit/artifact records are kept outside the web root. DB
  backups are a separate operator step. Failed updates leave maintenance enabled.
- Verified `bash -n` and mocked success/upgrade-failure/cache-failure paths.
  No real Moodle upgrade or server deployment was performed. See tools/DEPLOY.md.

## Universal deploy entry point

- Root `deploy.sh` replaces the hardcoded plugin pair with manifest-driven
  discovery. `tools/package.py` builds tracked component ZIPs and manifest.json.
- Install locations are read from Moodle core_component::get_plugin_types();
  unknown types and unsafe targets fail before maintenance. New registered plugin
  types/components need no shell-script edits. Moodle 5.3 remains the target.
- Old source/backup directory defaults are kept; old tool path wraps root deploy.sh.
- Packaging unit tests and sandboxed deployment success/upgrade-error/cache-error
  tests passed, including an additional local_example plugin. Bash syntax passed.
- No real Moodle/server upgrade was run. DB backups remain external; failed
  deployment keeps maintenance enabled. See tools/DEPLOY.md.

## Practicum 0.2.0: all IPv4 exercise variants

- Fixes the missing activity selector wiring: registry now exposes every one of
  qtype_ipv4's 12 variants. Shared ipv4_task adapter explicitly saves tasktype and
  forces text answers; ipv4_network class/id remains compatible with old plans.
- Per-block prefix multiselect supports /1–/30; old saved block JSON and legacy
  comma-separated submissions are accepted. Added type-specific RU/EN explanations.
- Template identity v2 includes task type and prefixes; old templates/usages are
  untouched. Module version 0.2.0 requires qtype_ipv4 >= 2026110902. No table changes.
- Validation: PHP 8.4.24 syntax on all Practicum files; 5 new configuration tests
  run in an isolated harness (62 assertions); existing isolated qtype checks still
  pass (64,365 total). Isolated adapter save/cache contract verifies 12 distinct
  manual-answer templates. These use Moodle stand-ins, not a real DB.
- Added real-Moodle mixed-activity lifecycle test for all types in training/exam,
  reload, settings changes and full grading; NOT RUN: this checkout has no Moodle
  installation/DB. Browser form, upgrade and actual PHPUnit integration remain
  required. Deploy after merge with root deploy.sh and open activity block settings.

## Practicum 0.2.1: Yes/No controls

- Explicit user change: samesubnet and assignable use choice mode (native labelled
  Yes/No radio controls), other activities still use text. Updated AGENTS product
  rule and recorded D011 to supersede the former text-only contract.
- Boolean templates use new v3 keys including answermode; text v2 keys remain
  identical. Existing templates, question usages and saved modes are untouched.
- Module version 0.2.1; no table migration. Deploy after merge and start a new
  attempt to see choices; pre-upgrade active attempts retain their saved text mode.
- Validation: PHP syntax, isolated configuration/qtype harness (64,389 assertions),
  adapter save/reuse contract for all 12 types with correct modes. Added 24 mode/
  identity assertions; extended real-Moodle mixed lifecycle test for both modes
  to check two boolean options and grading. Real Moodle/DB/browser tests were
  NOT run: core and DB are not installed in this checkout.

## Published ip-command integration on current main

- Integrates the previously local ip modules with main eec6304, retaining all 12
  published IPv4 exercises, /1-/30, boolean choice templates and Practicum state.
  Adds 29 command/diagnostic ids: 41 total, grouped quiz creation, topic categories,
  search in single-question and activity block selectors, and ordered textareas.
- qtype_ipv4 0.4.0 / DB 2026110903; mod_practicum 0.3.0 / DB 2026110903 requires it.
  Canonical DB selector stays tasktype. Migration also reads the earlier local
  exercise-column schema. Existing question exercise arrays and attempt ids stay
  unchanged. TYPES stays the legacy 12-id API; ALL_TYPES exposes all 41 to the new
  module so the old deployed Practicum remains usable during a staged update.
- Validation actually run in Docker ipv4-runtime: Moodle 5.3+ Build 20261030,
  PHP 8.4.27, PostgreSQL 17.11, PHPUnit 11.5.56. Upgrade of both copied plugins
  succeeded. Separate PHPUnit database reinitialised. Command:
  docker exec -w /app/work/moodle ipv4-runtime php vendor/bin/phpunit
  --testsuite qtype_ipv4_testsuite,mod_practicum_testsuite
  (one command): 44 tests, 74,368 assertions, all passed. Covers every type in
  training/exam, resume and settings changes, grading, backup/restore, XML,
  permissions, expiry/privacy, semantic command equivalents and negative inputs.
- The earlier browser run (30-type local implementation) reached 8/8 with
  abbreviations and multi-line answers; it is not a fresh UI run of this merged
  41-type version. PHP 8.3, other databases/themes, full core tests and real
  kernel command execution were not verified in this integration. Commands are
  never executed by either production plugin. Supported grammar only.
- Publish to feat/bulk-ipv4, the branch used by the user's old update.sh; keep
  latest main changes intact and do not force-push. No user-server deployment
  is performed here. That old script installs qtype only; full new activity
  functionality needs both packages from this same branch via deploy.sh.

## Wide command answers (qtype 0.4.1)

- Fix/wide-command-answer removes size=18 only for command controls and adds
  Bootstrap w-100 to single-command inputs and ordered-command textareas. The
  common qtype renderer covers ordinary quizzes and Practicum, editable answers
  and readonly review. IPv4 fields and boolean choices retain their controls.
- qtype version 2026111000 / 0.4.1 refreshes the installed plugin; no schema change
  or Practicum dependency change. No user-server deployment or PR merge performed.
- Actual checks: PHP syntax; Moodle 5.3+ Build 20261030 / PHP 8.4.27 / PostgreSQL
  17.11 / Boost; Chromium via `python -u work/check-wide-answer.py`. Geometry at
  viewport widths 1440 and 390 matches the available answer area for both inputs
  and textareas: quiz 536/340 px, Practicum 851/340 px at measurement time. Long
  command fits on desktop; narrow single-line fields retain native text scrolling.
  No extra page overflow versus original sizing (stock quiz navigation itself
  has a four-pixel mobile gutter overflow). Multiline fields wrap visually.
- Browser save/return/reload preserves all responses; quiz 3/3 and Practicum
  100/100, boolean input included. Readonly review remains wide. No JS errors.
  Screenshots and logs in ignored work/wide-*. Custom themes and browsers other
  than Chromium were not checked. Existing 44-test run is the previous release's
  result; this small display change was verified directly in the browser.

## Practicum 0.5.0: multi-topic configuration (2026-11-10)

- Branch `feat/practicum-multiselect`. Three form modes: existing per-topic blocks,
  same N per selected topic, or N total from independently random selected types.
  Searchable native Moodle multiple autocomplete and select-all checkbox; shared
  prefix/path settings; Russian/English instructions. Existing activities reopen
  with their original mode/counts, simple selections round-trip in blocks JSON.
- Random types sample with replacement; topics may repeat or be absent. Equal
  counts enforce the 100-slot total, not just the per-topic count. Selected ids
  are allowlisted. No table changes; mod version 2026111001 / release 0.5.0.
- Pools expand only at new attempt creation into ordinary concrete slots.
  Existing attempts, template keys, grading and prefix randomness remain intact.
- Actual validation: Debian PHP 8.4.27 extracted locally under ignored work/;
  `LD_LIBRARY_PATH="$PWD/work/php-runtime/usr/lib/x86_64-linux-gnu" work/php-runtime/usr/bin/php8.4 -n tools/check-linuxcmd.php`
  passed 3744 assertions (isolated stand-ins, NOT Moodle core/DB). All seven
  changed PHP files passed `php8.4 -n -l`; `git diff --check` passed.
- Added real-Moodle lifecycle regression for pool creation, saved plan resume
  after settings edits, full grading and next-attempt settings. NOT RUN: this
  checkout has no Moodle installation/vendor/DB; Docker socket access denied.
  Required on Moodle 5.3: mod_practicum PHPUnit suite and browser checks for new
  activity, all three mode switches, searchable multi-selection/select-all,
  validation errors, edit/save/reopen and existing activity/attempt preservation.
- No user-server deployment, push, merge or PR creation performed.
