# Practicum 0.3.0 Linux verification

Environment: repository checkout, Debian 13, PHP 8.4.27 CLI extracted locally
under ignored `work/php-runtime`. No installed Moodle core/database or PHPUnit.

Executed:

- PHP `-n -l`: all 42 PHP files in the Linux qtype, Practicum and standalone
  harness passed syntax checks.
- `LD_LIBRARY_PATH="$PWD/work/php-runtime/root/usr/lib/x86_64-linux-gnu" work/php-runtime/root/usr/bin/php8.4 -n tools/check-linuxcmd.php`:
  **3,325 assertions passed**. Covers all 10 operations × 3 modes × 20 random
  scenarios, literal quoting/escaping, flags, path styles, known relative-path
  examples, missing intermediates, regular-file traversal, copy destinations,
  trailing slashes, special dot operands, snapshot/restore/grading and existing
  IPv4 configuration/immutable template identities. This uses stand-ins; it is
  not PHPUnit and does not establish Moodle integration.
- `python -m unittest discover -s tools/tests -v`: 2 packaging tests passed;
  included deployment harness passed success/upgrade-error/cache-error/unknown
  plugin-type cases. These use temporary fixtures, not a Moodle deployment.
- GNU coreutils `cp` with trusted test fixtures in a fresh temporary directory:
  recursive copy to a new `copy/` succeeds; new `copy-dot/.` and missing
  `copy-parent/../copy-again` fail. Grading tests reflect these distinctions.
- `python tools/package.py`: built mod_practicum (31 files), qtype_linuxcmd
  (18 files), qtype_ipv4 (30 files). Verified ZIP integrity, manifest components,
  install roots, no work/.git contents, and no nested plugins in qtype_ipv4.
- `git diff --check`: passed.

Added but **not run**: native question settings/XML tests and mixed-family Moodle
lifecycle tests for training/exam, persisted state after settings edits, gradebook
and course backup/restore with a finished Linux attempt. Actual Moodle 5.3 upgrade,
repeated block form/browser UI and existing-attempt migration remain unverified:
this checkout has no Moodle installation, DB or vendor/PHPUnit. No server deployed.

# Practicum foundation verification (historical)

Environment: Moodle 5.3+ (Build 20261030), PHP 8.4.24, PostgreSQL 17.11,
PHPUnit 11.5.56, standard Boost, Chromium through Playwright.

Passed:
- Native plugin installation and course activity settings form.
- 7 PHPUnit tests / 28 assertions: exam grading and attempt limit, mode snapshot,
  save/reload, manual input, independent blocks, training without gradebook or deadline,
  deadline rejection of late data, unknown task rejection, unauthorised start rejection,
  Privacy API discovery/deletion, backup/restore with finished attempt and saved IPs.
- Browser training: manual responses, save, reload, finish, result 100/100;
  native Check button gives immediate correctness feedback.
- Browser activity creation through course/modedit.php with exam mode.
- Browser assessment: student start, manual answers, save/reload, finish, result 100/100.
- PHP syntax checks on all new PHP files.

Not yet verified: full Privacy export, live cron scheduling (expiry lifecycle is tested),
concurrent stress, full Moodle core/Behat, Mobile, alternate themes and databases,
Russian core language pack, PHP 8.3 integration. No claim of GNS3/terminal integration.

Earlier qtype checks remain documented in root TESTING.md. Work logs, environment
and screenshots are kept in ignored work/ and are never shipped in plugin archives.

## Practicum 0.2.0 IPv4 selector expansion

PHP 8.4.24 syntax checked. Five configuration test methods were executed in an
isolated assertion harness: 62 assertions, all passed. Together with existing
isolated qtype calculation/state/grading checks: 64,365 assertions. A mocked
adapter save/reuse run created 12 separate text-only templates with correct
tasktype/prefix settings. These checks do not establish Moodle integration.

The new mixed-activity lifecycle PHPUnit test covers all variants in both modes,
resume after activity settings changes, preserved conditions and 100% grading.
It was added but not run: this checkout lacks Moodle core/DB and the historical
work/moodle environment. Browser multiselect, actual upgrade and full PHPUnit
remain unverified for this version.

## Practicum 0.2.1 Yes/No controls

PHP 8.4.24 syntax checks pass. Isolated configuration tests include 24 new
assertions for choice/text modes and new boolean identities with preserved text
identities (64,389 assertions including qtype checks). The isolated adapter
save/reuse contract checks correct modes on all 12 generated templates. The
existing isolated qtype checks cover both boolean options and saved text/choice
attempt restoration. These use Moodle stand-ins and are not DB integration.

The mixed-activity real-Moodle test now expects two radio choices for boolean
tasks, tests both yes/no grades and empty response validation. This test and
browser verification remain unrun: no Moodle core/DB in this checkout.
