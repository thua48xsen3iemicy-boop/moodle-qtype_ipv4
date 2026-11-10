# Handoff: Practicum foundation

## Practicum 0.6.0: category selection and file-operation naming (2026-11-10)

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
