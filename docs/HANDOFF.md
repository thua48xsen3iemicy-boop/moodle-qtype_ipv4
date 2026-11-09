# Handoff: Practicum foundation

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
