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
