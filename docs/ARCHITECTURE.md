# Architecture

`mod_practicum` owns activity settings, attempt policy, deadlines and grades.
`blueprint` validates blocks and builds a shuffled attempt plan. Every task slot
contains a stable type id, schema version and settings snapshot. No quota balancing.
`registry` is an explicit allowlist of task implementations. `task_type` is the
adapter contract. `ipv4_task` bridges every supported qtype IPv4 exercise to the activity; the
`ipv4_network` wrapper preserves the original class and stable plan id. Template
keys v2 include exercise type and prefixes. Boolean choice templates use v3
identities including answer mode; other tasks keep v2. Existing templates are
never mutated. Boolean tasks use Yes/No radio buttons; other tasks use text.

Moodle question usages store generated condition, answer order, responses and
behaviour steps. Activity attempts store owner, plan, mode, timestamps and deadline.
Training uses immediatefeedback; exam uses deferredfeedback. Only finished exam
attempts update gradebook (best score, 0–100). Deadline and mode are snapshotted.
Expired attempts reject new responses and finish saved answers on access or cron.

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
checkboxes for whole categories in each/random modes alongside individual types.
`blueprint::from_form` expands the union once on save and stores optional `groups`
and `individualtypes` UI metadata with the existing concrete `types` snapshot.
Planning/backup keep that snapshot; preprocessing restores categories and extras
separately. New catalog members enter a selected category on resave, never during
an existing attempt. No Moodle question-bank category or schema changes.
