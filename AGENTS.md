# Agent entry point

Read `docs/PROJECT.md`, `docs/ARCHITECTURE.md`, `docs/HANDOFF.md` and the nearest
AGENTS.md before editing. Read `docs/DECISIONS.md` before changing behaviour.

## Repository layout
- Root: existing `qtype_ipv4` plugin, installed at `public/question/type/ipv4`.
- `mod/practicum`: separate activity plugin, installed at `public/mod/practicum`.
- `work/`: ignored environment, credentials, generated artifacts and scratch files.
Never commit work/, database contents, sessions, tokens or Moodle core.

## Product rules
- Practicum uses text input except boolean tasks (samesubnet/assignable), which use Yes/No choices per user request. Keep existing qtype choice mode for backwards compatibility.
- Prefixes are independently random. Do not introduce balanced quotas.
- Existing attempts must never be regenerated or affected by changed settings.
- Use native Moodle question engine, capability checks, sesskey, grade API and transactions.
- Never run learner commands on the Moodle host. Future labs require an isolated backend adapter.
- Never claim an integration is tested without an actual run. Record version, command and result.

## Workflow
1. Inspect git status; preserve user changes. Work in a feature branch.
2. Write/adjust meaningful tests for attempt lifecycle, grading and authorisation changes.
3. Verify on Moodle 5.3; include unrun checks and concrete blockers in handoff.
4. Update docs/HANDOFF.md with exact completed work, limitations and next steps.
5. Record architectural decisions in docs/DECISIONS.md; avoid silent contract changes.
6. Do not merge PRs or deploy to a user server without authorisation.

## Cooperation
Use docs/HANDOFF.md as persistent shared state. If multiple agents are explicitly
assigned, record ownership of files there before edits; avoid concurrent edits to
DB schemas and public interfaces. Chat history is not a substitute for repository docs.
