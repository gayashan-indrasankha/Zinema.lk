# Quarantine rollback and recovery - ZL-18

No production/config/DB/data migration occurred. No credentials were rotated,
sessions deleted, queue records cleared, historical source removed or Git history
changed. All existing uncommitted work is authoritative. Never reset/clean/stash
or replace the workspace from a ZIP/HEAD to undo this task.

Review the exact delta in [the test report](QUARANTINE_TEST_RESULTS.md). Changes
are shared policy/gates/simulator, early PHP/Node controls, affected legacy UI,
npm dev startup, regression tests/CI/context and documentation. Historical bot/
forwarder/worker/extractor function bodies remain after guard additions; source
hash proof is recorded. Earlier ZL-15/16/17 module/media/database code is preserved.

Rollback workflow: first preserve the complete current working tree privately;
review a diff limited to ZL-18; selectively restore only a confirmed regression's
UI/integration wiring while keeping default-deny backend/worker guards; rerun
quarantine and HLS tests. Never restore unapproved runtime behavior as an automatic
fallback. A deleted gate or true/live flag is not an approved-provider migration.
No SQL rollback is needed and historical queues must not be replayed/cleared.

Use node scripts/environment.cjs down ci to stop owned local CI while retaining
all existing volumes. Test helpers remove only their owned temporary HTTP roots;
no production process/scheduler was stopped or inspected. Existing private variants,
real deployments, credentials, auth stores and external callback configuration
remain outside this verification. Do not run them as a rollback shortcut.

Future activation requires a new explicit task with approved provider/business
scope, actual rights/identity/entitlement evidence, isolated sandbox/egress/session
security tests, queue migration/replay decisions, sanitized telemetry and a concrete
release/rollback plan. ZL-19, ZL-28-30, ZL-44-45 and ZL-54 are proposed follow-ups;
none has been started or marked complete here. Hosted CI for this working tree is
NOT VERIFIED; no commit/push/deploy was performed.
