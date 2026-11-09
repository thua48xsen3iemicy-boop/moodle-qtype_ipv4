# Decisions

- D001: Keep qtype_ipv4 backwards compatible; add mod_practicum as separate package.
- D002: Input in Practicum is text; do not remove legacy radio mode from old quizzes.
- D003: Prefix selection remains independent random; no balancing policy.
- D004: Use Moodle question engine, not an embedded hidden mod_quiz.
- D005: Attempt plan, mode and deadline are frozen at start. Grade scale 0–100,
  best finished assessment attempt; training does not write grades.
- D006: Future GNS3/VM tasks use an isolated backend contract, not local shell calls.
- D007: Explicit registry initially; Moodle subplugin ecosystem can be introduced
  when there is a real independently installable task family.

- D008: Deploy both plugin packages from a dedicated clean main checkout. Keep
  maintenance enabled on failed install/upgrade/cache purge; automatic file-only
  rollback is insufficient after DB migrations. Database backups remain external.

- D009: Discover plugins from tracked literal component declarations and emit a
  package manifest. Resolve install directories using the target Moodle's plugin
  type registry; reject unknown types before maintenance. A removed source plugin
  is not automatically uninstalled from Moodle.
