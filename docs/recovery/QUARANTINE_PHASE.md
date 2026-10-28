# Recoverable development-upload cleanup

Date: 2026-10-25. State: COMPLETE within the authorized local scope. Starting tree clean on
`zinema/local-dev-safety`, head `a31d47e`. The owner authorized recoverable
quarantine, explicitly reviewed cleanup, audit, restore and concurrency tests.
Production integrations, remote Git operations and publishing remain disabled.

## Intended behavior before implementation

- Cleanup means withdrawing an eligible, registered development upload into a
  private quarantine. No permanent purge of the last recoverable copy is included.
  Unknown, legacy, referenced, shared, young, changed, missing or linked media stays
  held. Do not act on existing sample records merely because they appear obsolete.
- A CLI review exposes a numeric asset revision and current eligibility. Mutations
  require that revision, a fictional actor/evidence/case reference and explicit
  acknowledgement of review. Recheck file identity, age, quota and references at
  execution. Identical cases replay; conflicting cases and stale reviews fail.
- Coordinate reference-writing requests and filesystem mutations in a consistent
  lock order. Reject new references to pending/quarantined assets. Trusted direct
  DB/filesystem writers remain outside this cooperative protocol.
- Quarantine is outside the web root, separately bounded and counted. Copy and
  verify before removing the public source. Persist operation intent/checkpoints
  and append-only-through-tool audit in the private atomic manifest. Filesystem
  and database operations are not a distributed transaction.
- Restore must never overwrite a path or exceed active quota. Retain the verified
  quarantine archive after restore. Interrupted operations pause new admission and
  require an explicitly reviewed finish or rollback; preserve all ambiguous data.
- Use separate fictional fixture volumes for mutation/failure simulations. Exercise
  references added after review and during cleanup, concurrent operators, idempotent
  retries, interruption recovery, tampering/collisions, quota and target guards.
  Retain prior regressions and sanitized evidence, and stop all owned services.

Implementation, executed evidence, limitations and handoff follow below.

## Implementation and initial evidence

The CLI adds storage-review, storage-history, storage-quarantine, storage-restore
and storage-recover. Shared lifecycle metadata/reference gating is in
includes/upload_lifecycle.php; dev/upload_cleanup.php implements the journal and
verified copy/publication protocol. Existing schema v3 (38 tables/2 views) is
unchanged. See dev/QUARANTINE.md for concrete reviewed commands and limits.

The first executed quarantine suite returned 46/48: restore recovery after the
public part link had already been removed could no longer recognize its own
published file; the remaining suite then stopped because admission correctly
remained paused. Both copies were intact. Persisted publication identity fixes
recognition without permitting collision overwrite. The failed fictional fixture
alone was reconciled after verifying its audit publication event and both copies;
an explicit fixture verification event and reviewed recovery preserved its archive.
A complete rerun passed 49/49 before adding direct CLI coverage, recovery replay and
unpublished-restore rollback. Private failed results remain preserved.

The restricted Node token protocol run could not connect to its loopback fixture socket (EACCES).
It was rerun with local socket permission and passed 6/6. This was an execution
boundary, not a passing test or an automatic-approval rejection. Production access
was never requested. Final executed results follow after retained checks.

## Final executed verification

| Check | Result | Evidence classification |
| --- | --- | --- |
| Reviewed quarantine/restore/audit/races/recovery, real CLI and fictional failure hooks | 51/51 passed | runtime verified |
| Retained held-claim/quota/inventory suite | 57/57 passed | runtime verified |
| Retained Docker HTTP mail/OAuth/upload/token suite | 88/88 passed | runtime verified |
| OAuth state/PKCE helper | 7/7 passed | test execution passed |
| Docker target/gateway boundary unit cases | 7/7 passed | test execution passed |
| Owned Docker isolation/disabled adapters/private storage and quarantine limits | 21/21 passed | runtime verified |
| Fresh native canonical schema / SQL bridge | 22/22 passed; 38 tables/2 views, zero legacy rows imported | runtime verified |
| Retained native HTTP/security suite | 94/94 passed; no new PHP diagnostics | runtime verified |
| Node config/bot and fake token client protocol | 22/22 and 6/6 passed | test execution passed |
| Extractor disabled/local fixture | 4/4 passed | runtime verified |
| Scoped bot-lockfile and HTTP-only extractor npm audits | Both exit 0, zero advisories | executed dependency checks |
| Owned shutdown | No running containers; 8088/9081/3307/8089/3334 closed | runtime verified |

Sanitized evidence is in QUARANTINE_*_RESULTS.json and evidence/quarantine-*.tap.
Private raw setup output, first failure, generated configuration, sessions and media
remain ignored. Tests use only fictional accounts/media and local adapters; no
external delivery, OAuth provider, recipient or production system was verified.
Static syntax/parsing and final local security/commit results are recorded below.

## Remaining limits and handoff

The protocol covers cooperating writers and the enumerated bounded reference
columns/one decoding layer. It does not constrain direct privileged SQL/filesystem
edits or prove absence of arbitrary references. Private archives are retained even
after restore; both public and private storage quotas and bounded journal capacity
can fill. Cleanup releases public quota without claiming total disk reclamation.
A selected recovery case/decision is retained; ambiguous/colliding data stays held.
Audit is append-only through the tool, not immutable against administrators. No
last-copy purge, malware/codec scan, container vulnerability scan, power-loss
transaction guarantee or distributed quota is established. Schema v3 is unchanged.
Legacy files and existing evidence were preserved; no production/publishing work,
credential rotation or remote Git action was performed. All owned services stopped;
private credentials, database/upload/rescue volumes and audit remain preserved.

Exact next recommended task: **Verify coordinated backup and restore of the owned
development database, upload volume and private lifecycle manifest using fictional
interruption fixtures; keep production integrations and publishing disabled.**


## Static and local security review

Executed static checks passed for 185/185 PHP and 41/41 JavaScript files, plus
Python AST parsing for 11 files. JSON parsing passed for all sanitized source/result
files; the detailed count is in QUARANTINE_STATIC_RESULTS.json. The working/staged
source and result diffs were reviewed and new whitespace checks passed.

Gitleaks 8.30.1 is available. Its saved upstream archive checksum matches, and the
scanner binary matches that verified archive. With default rules, empty suppressions,
inline allows ignored and fully redacted output, staged changes, the full indexed
candidate and all local history returned exit 0 / zero findings. Final updated
candidate counts/results are in QUARANTINE_SECURITY_RESULTS.json. Eight private-path
ignore checks passed and no forbidden credential/data/session/media path is indexed.
.gitignore was inspected unchanged. This does not certify ignored private/legacy
evidence or prove absence of every secret. The npm audit scopes exclude full browser
extractor dependencies, old installed trees, containers and exploitability.

Only local Conventional Commits are prepared. No prior commit was amended/reset and
no unrelated user change was staged (the phase began clean). The resulting source
commit and final clean-tree/shutdown status follow in the local completion record.


## Completed local handoff

Source commit: `637327e55d772dcb6acce4383b226bbaee606bd5` (`feat(storage): add reviewed
recoverable upload quarantine`). The source commit left a clean worktree. Final
staged, all 384 candidate files and all local history scans returned zero findings;
Git ignore/private-path and whitespace checks passed. Relevant executed checks and
limits are recorded above. This documentation-only completion record follows in
a separate local commit; no previous commit was amended or rewritten.

All owned containers and native test processes stopped; loopback ports
8088/9081/3307/8089/3334 were verified closed. Database/upload/rescue/audit volumes,
private credentials and failed-test evidence remain preserved. Nothing was pushed,
published or deployed, and no production service or real recipient was contacted.
Start subsequent work by reading STATUS.md/DECISIONS.md and dev/QUARANTINE.md.
