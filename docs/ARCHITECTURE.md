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
