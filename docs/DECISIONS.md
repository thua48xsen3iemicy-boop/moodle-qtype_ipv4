# Decisions

- D023: Timed Practicum exams use a visible countdown, debounced input saves
  (700 ms), a 10-second retry interval and final POST at zero instead of reloading
  the page. Use native process_all_autosaves for AJAX saves (stable real-step
  sequence checks on retries); use process_all_actions for final/manual POSTs.
  Serialize requests and refresh native question-engine sequence checks.
  At zero, capture the final form once and disable inputs. A fixed 30-second
  transport allowance accepts the payload and immediately finishes the attempt;
  GET/cron defer automatic completion until deadline + 30 seconds. This is a
  deliberate change from rejecting all POSTs at the exact deadline: the server
  can enforce receipt time but cannot prove when a browser captured its answers.
  No client timestamp is trusted. After the allowance, only persisted answers
  count. Existing deadlines, conditions, usages and plans are never regenerated.
  Expired readonly forms finish persisted answers without submitting empty
  question controls. Keep ownership/capabilities/sesskey, native engine actions,
  per-attempt locks, transactions and best finished exam grade policy. Hide start
  when the exam limit is exhausted and retain review access. No DB migration.

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

- D016: Name the current Linux question family “File and path operations”; keep
  qtype_linuxcmd component, linux_* ids, database fields and template keys stable.
  In Practicum each/random selection modes offer native category checkboxes plus
  individual types. Category membership derives from the existing IPv4 catalog
  and Linux exercise list, so new registered members are included on the next
  form save. Union selections without duplicates and enforce the expanded slot
  limit. Persist concrete types plus optional groups/individualtypes UI metadata
  in blocks JSON; restore the same checkboxes and extra types on edit. Plans use
  concrete types only, preserving existing attempts and older configurations.

- D017: Training Check submits keep the native question-engine POST/redirect/GET
  flow and return to the submitted question's native fragment id. Derive control
  names and anchors from the loaded usage, never from an arbitrary return URL or
  slot supplied by the client. Finished/expired attempts and exam/saving requests
  clear the fragment explicitly to prevent browser fragment inheritance. Add CSS
  scroll margin for fixed navigation. No JS, AJAX or grading/state changes.

- D018: Add explicit `once` selection mode: expand selected categories/individual
  types to a unique union and generate exactly one slot per type. No count entry;
  form submissions always store count=1, ignoring stale sharedcount. Persisted
  once blocks must have count=1. Shuffle order only; no random sampling of types.
  Generated parameters/prefixes remain independently random. Category labels
  show current member counts. Keep each/random/advanced semantics and existing
  attempt snapshots; no quotas for prefixes and no global parameter uniqueness.

- D019: New network-address exercises require address/prefix notation. Grade both
  the exact network address and its prefix; reject bare addresses and dotted-mask
  suffixes. Mark new network scenarios with networkformat=cidr inside _ipv4;
  old snapshots without the marker keep their original bare-address grading,
  choices and task text. New network radio choices also include the prefix.
  Use distinct Practicum v4 network template keys with networkformat=cidr;
  other template identities stay unchanged. No DB migration or new task type.

- D020: Advanced per-topic blocks accept multiple task types with the same count
  per selected type. Deduplicate types within a row; keep separate rows independent
  for different counts/prefixes. Single-type rows retain their legacy JSON shape.
  Multi-type rows reuse selection=each with layout=block UI metadata so editing
  restores the original rows instead of switching to the global each mode. Plan
  and total-limit calculation reuse existing each semantics. Mixed IPv4/file rows
  are supported; prefixes remain visible and apply only to IPv4. No schema change.

- D021: Reuse qtype_linuxcmd for a separate permissions category (17 stable
  linux_* ids); retain the ten-member files category. permissions owns version-2
  snapshots and semantic mode evaluation, with Practicum v2 template keys only
  for new ids. Existing v1 generation/grading/keys remain unchanged. Short text
  responses support commands, octal/letters conversion and permission meanings.
  Commands are never executed. Explicit symbolic classes only; directory special
  bit tasks require symbolic notation, with GNU directory SUID/SGID preservation
  on =. ACL and extended filesystem attributes remain outside this module.

- D022: Ready-made learning topics are separately addable instances of the existing
  Practicum module. The first, `ipv4_order_v1`, pairs bundled versioned RU/EN
  theory with the existing nextip/previousip adapters. Store its marker in blocks
  JSON and concrete attempt-slot settings, keeping schema, bank identities,
  backup fields and old attempts unchanged. The reference within an attempt
  reads its saved plan, not mutable activity configuration. Topic revisions must
  use a new id and keep v1 content available. Custom question sets and the existing
  single training/exam mode remain supported; combined learning stages and
  mastery completion are deferred until this first topic is evaluated.
