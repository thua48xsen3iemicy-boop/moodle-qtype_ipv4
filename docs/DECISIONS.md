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

- D012: Integrate command tasks with the published tasktype schema rather than
  overwrite the independently released 2026110902 schema. Use qtype 2026110903
  (0.4.0) and Practicum 2026110903 (0.3.0). Preserve the 12-id TYPES API for older
  activity code, expose ALL_TYPES for the new module, and retain immutable saved
  state and boolean template identities. Support the experimental exercise-field
  migration as well. Searchable grouped labels and quiz topic checkboxes avoid
  an unstructured dropdown; saved teacher presets remain future work. All command
  grading is limited, in-process grammar comparison with no shell execution.

- D013: Implement Linux files/paths as a separate native `qtype_linuxcmd`, bridged
  by `linux_task`, with 10 selectable operations and per-block absolute/relative/
  mixed path rules. Accept equivalent literal commands by semantic path checking;
  never execute learner input. Single-operand mixed tasks use relative paths.
  Generated v1 conditions and required checks are saved in question-engine steps;
  use immutable versioned template identities. Existing IPv4 ids/keys remain intact.

- D014: Practicum adds searchable multi-topic selection alongside legacy per-topic
  blocks. `each` means N generated questions per selected stable type; `random`
  means N total slots, each independently choosing a selected type uniformly with
  replacement (no quotas or guarantee that every type appears). A select-all
  checkbox resolves the current registry on save, storing explicit ids. Shared
  IPv4 prefixes and Linux path mode are stored in the existing blocks JSON with
  selection/types/count; no database migration. Plan creation resolves pools to
  ordinary version-1 concrete slots, keeping existing attempts/templates intact.
  Existing activities reopen in advanced mode, new ones default to equal counts.

- D015: Remove shared/per-block Linux path selectors from the activity form.
  Form saves always use a new `random` mode, choosing each operand independently:
  relative 2/3, absolute 1/3. Both mixed directions are possible; `reverse` means
  absolute source/relative destination. Conditions state the chosen requirement;
  initial structure remains. No quotas. Native quiz editor retains manual modes.
  Old blocks remain readable until resaved; old attempts and v1 grading semantics
  are unchanged. New mode values yield distinct immutable v1 template identities;
  concrete operand styles/mode are snapshotted as before. No schema migration.
