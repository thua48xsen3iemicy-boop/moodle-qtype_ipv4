# Architecture

`mod_practicum` owns activity settings, attempt policy, deadlines and grades.
`blueprint` validates blocks and builds a shuffled attempt plan. Every task slot
contains a stable type id, schema version and settings snapshot. No quota balancing.
`registry` is an explicit allowlist of task implementations. `task_type` is the
adapter contract. `ipv4_task` bridges every supported qtype IPv4 exercise to the activity; the
`ipv4_network` wrapper preserves the original class and stable plan id. Template
keys v2 include exercise type and prefixes. Boolean choice templates use v3
identities including answer mode; network-address CIDR templates use v4 identities
including networkformat=cidr; other tasks keep v2. Existing templates are
never mutated. Boolean tasks use Yes/No radio buttons; other tasks use text.

Moodle question usages store generated condition, answer order, responses and
behaviour steps. Activity attempts store owner, plan, mode, timestamps and deadline.
Training uses immediatefeedback; exam uses deferredfeedback. Only finished exam
attempts update gradebook (best score, 0–100). Deadline and mode are snapshotted.
Expired attempts reject new responses and finish saved answers on access or cron.
The optional activity time limit applies to both training and exam attempts;
zero means untimed. Each new attempt snapshots its own deadline, so changing
settings never retimes a running attempt (including legacy untimed training).
Training expiry stores a reviewable result without writing a gradebook grade.

Adding a family: add adapter, register stable id, add configuration validation and
form UI, implement its question type, add generation/grading/state tests. A plan
must remain readable after code upgrades; introduce versioned migration only when
needed. Do not mutate the condition in apply_attempt_state.

## Linux command scenarios

`qtype_linuxcmd` (source `plugins/linuxcmd`) owns a literal-word parser, generated
file/directory scenarios and automatic grading. `linux_task` registers 10 stable
`linux_*` ids. Each block stores `pathmode` instead of IPv4 prefixes. New form saves use
`random`: independent relative (2/3) or absolute (1/3) operands. `reverse` adds
absolute source/relative destination. Legacy mixed mode still requires a relative
source and absolute destination; unary mixed tasks use a relative operand. Linux and IPv4 blocks share the existing attempt/grade lifecycle.

The generated condition, working directory, initial objects, required command,
options, paths, operand styles and example answer are snapshotted in `_linuxcmd`.
Reload never regenerates. Immutable v1 template keys include operation/pathmode.
Evaluation resolves literal paths, verifies traversed directories and checks GNU
cp/mv destination semantics. Quotes/escapes are supported; shell expansion,
operators, pipelines and redirects are rejected. There is no process execution.
This is a bounded command grammar, not a full Bash or filesystem emulator.
Grading changes must retain v1 semantics for saved scenarios and introduce a new
scenario/template version if that contract changes.

## Future isolated execution boundary

A separate lab backend will own provision/resume/status/submit-evidence/destroy.
Use opaque per-attempt handles, authenticated service calls, ownership checks,
idempotency keys, resource limits, expiry and scheduled cleanup. Browser terminal
sessions must be authorised against the current learner's attempt. Evidence must
be obtained from the backend; terminal output supplied by the browser is untrusted.
Credentials belong in Moodle configuration/secret storage, never in plans or JS.
No GNS3 client, VM deployment or command execution is implemented in this foundation.

## Persistence ownership

Never reuse another student's question usage. Grade and state changes run under
per-attempt lock and DB transaction. HTTP endpoints enforce ownership/capabilities
and sesskey. Cron may finish expired attempts through the same lifecycle service.
Course deletion removes usages via module deletion. Full course backup and privacy
coverage must be explicitly tested before treating this alpha as production-ready.

## ip command and diagnostic adapters

The shipped `exercise::TYPES` constant remains the 12 IPv4 ids so an older
Practicum installation is not exposed to adapters it cannot render. `ALL_TYPES`
and `exercise\catalog` expose all 41 ids to the updated module. `tasktype` stays
the persisted selector; the `exercise` question property remains the saved array.
Command modules live under classes/exercise; the similarly named existing
qtype_ipv4\exercise class retains its public calculation and state API.
Command templates force text entry. The semantic grammar never launches input.
Multi-command tasks use ordered newline-separated commands and a textarea.

## Category selection

`registry::groups()` maps stable category ids to concrete allowlisted type ids,
using the existing IPv4 catalog and file-operation exercise list. The form offers
checkboxes for whole categories in once/each/random modes alongside individual types.
`blueprint::from_form` expands the union once on save and stores optional `groups`
and `individualtypes` UI metadata with the existing concrete `types` snapshot.
Planning/backup keep that snapshot; preprocessing restores categories and extras
separately. New catalog members enter a selected category on resave, never during
an existing attempt. No Moodle question-bank category or schema changes.

`once` persists a normal selection snapshot with count=1 and plans one slot per
unique selected type; validation rejects any other persisted count for this mode.
The form hides count entry and ignores stale sharedcount when saving once.
Category labels show live catalog counts, while saved type snapshots remain fixed.
The existing each/random modes and version-1 slot shape are unchanged.

## Network answer format

New `network` generation stores `networkformat: cidr` in `_ipv4`. Canonical answers
and all radio choices include /prefix; normalization uses the saved format marker
and grading compares the full address/prefix. Bare addresses fail format validation;
a well-formed wrong prefix is incorrect. Task/error strings explain the format.
Legacy snapshots without the marker retain bare-address answers/choices and their
old task text on restore/regrade. Low-level network calculations remain bare IPv4;
other exercise formats are unchanged. Practicum network templates use v4 identities
so the new feedback is saved separately from immutable older templates.

## Multiselect blocks

Advanced blocktype controls submit arrays per repeated row; legacy scalar posts
are still accepted. One-type rows save the original concrete type/count/settings
shape. Multi-type rows save selection=each, layout=block, types/count/prefixes/
pathmode, reusing existing validation and planning. layout only controls form
preprocessing, keeping the row groups/counts on edit. The union within each row is
unique; different rows may intentionally repeat a type with other counts/prefixes.
Prefix controls stay visible for mixed selections, and file operations ignore them.
Existing version-1 attempt plans, templates and saved attempts are unchanged.

## Linux permission questions

qtype_linuxcmd exercise::TYPES is the union of FILE_TYPES and permissions::TYPES.
Only version-2 snapshots dispatch to permissions; version-1 code is preserved.
The existing persisted tasktype/pathmode fields, question-engine state, import,
backup and adapters are reused. No schema change. permissions snapshots include
initial/goal modes, owner/group targets, response kind and canonical answer.
Evaluation parses literal command words and checks paths, options, recursion and
resulting bits. chmodnumeric and chmodsymbolic enforce the requested notation;
conversion requires exact nine-character letters or 3–4 octal digits. Theory uses
specified short tokens. The renderer uses a generic answer label and omits the
filesystem tree for non-command tasks. All answers remain native text responses.
The permissions category and labels use the same registered type list. Existing
saved pools are concrete snapshots and do not grow during attempts.

## Ready-made learning topics

`learning_topic` owns versioned bundled content and maps a topic to existing
concrete blocks. `ipv4_order_v1` uses nextip/previousip with configurable equal
counts and the adapters' existing /24 template settings (prefix does not affect
numeric adjacency). `blueprint::from_form` resolves the selected topic before
reading custom hidden controls. Topic metadata is retained in block/slot settings
and ignored by the existing question template identity. Form preprocessing
restores topic/count; choosing custom again returns to ordinary configuration.
The activity page reads its blocks; inline training/review theory reads the saved
attempt plan. Backup already preserves both JSON fields. No new table or question
family. Existing training/exam lifecycle, grade policy and completion support do
not change. Keep v1 content when adding a later topic revision.
