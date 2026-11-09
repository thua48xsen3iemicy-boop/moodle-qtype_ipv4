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
