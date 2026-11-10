# Handoff: Practicum foundation

## Practicum 0.3.0: Linux files and paths

- Branch `feat/practicum-linux-paths`; no deployment or merge performed.
- New `qtype_linuxcmd` 0.1.0 at `plugins/linuxcmd` installs in
  `public/question/type/linuxcmd`. Required by mod_practicum 0.3.0; the manifest
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
  syntax and isolated checks. `tools/check-linuxcmd.php`: 3,325 assertions passed,
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
