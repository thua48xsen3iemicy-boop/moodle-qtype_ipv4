# Decisions

- D001: Keep qtype_ipv4 backwards compatible; add mod_practicum as separate package.
- D002: Input in Practicum is text, except Yes/No tasks per D011; retain quiz radio mode.
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

- D010: Practicum exposes all supported qtype IPv4 exercise ids through one
  text-only adapter. Preserve ipv4_network and old plans/usages; version new
  template keys as v2 and include task type so different exercises cannot collide.
  Prefix settings support /1–/30; legacy comma-separated form data stays readable.

- D011: User explicitly requested clickable Yes/No responses for boolean tasks.
  Practicum samesubnet/assignable now use native choice mode; other tasks keep
  text entry. Boolean templates use new v3 identities including answer mode,
  leaving existing v2 text templates and saved attempt state untouched.
