# Practicum foundation verification

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
