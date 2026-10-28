# Recovery status

Updated: 2026-10-26.

## Current phase

Bot lifecycle/ingestion/recovery hardening: **COMPLETE within executed local fake
scope; overall launch NO GO**. Started clean at extractor checkpoint `2bd6305`.

- Reproduced lost checkpoints, corrupt-state fallback, duplicate schedules, active
  queue deduplication loss and unsafe media paths/overwrites (initial 1/9 passed).
  Another fake recipient test observed six send attempts after a lost acknowledgement.
  Repairs preserve/resume work, hold uncertainty without tier retries, serialize
  jobs/reconnects, validate no-overwrite media and preserve legacy queue evidence.
- Added explicit Asia/Colombo scheduling, private single-worker lease, bounded
  graceful shutdown, late-heartbeat/stale-open protection, raw-log suppression,
  safe pairing-required stop and authenticated read-only API readiness. Shared
  maintenance paths require send + metadata/log acknowledgement; no blind retries.
- Expanded Node 102/102, real Node/SQL/fake-send 10/10, protocol 6/6, retained Docker
  integration 88/88 + OAuth 7/7 and environment 21/21 passed. Fresh native HTTP 94/94
  and schema/claim bridge 22/22 passed. Exact cases, failure history and limits:
  BOT_LIFECYCLE.md, BOT_LIFECYCLE_SQL_RESULTS.json and evidence/bot-lifecycle-*.tap.
- Actual Baileys/device pairing, real recipients/large media and operational host
  behavior are not externally verified. Stale worker leases and ambiguous
  maintenance remain held for reviewed intervention; no automatic data/session
  destruction or exactly-once provider claim. Pairing needs its own reviewed flow.
- Real integrations, production access and publishing remain disabled. PHP 196/JS 51/CJS 15/Python 15 checks and candidate JSON parsing passed.
  Gitleaks staged/full 483-file candidate/history scans returned zero findings;
  12 ignore checks passed. Default-rule/private-data limits remain. Owned services
  stopped; private volumes and evidence remain preserved.

Next authorized local phase: **Prepare an isolated staging artifact and rehearse
synthetic-data backup/restore, TLS/security boundaries, monitoring and bounded load.**
Hosting remains undecided; no purchase, public host, real-provider test or publishing
is authorized. Real staging/live acceptance remains a release gate.

## Previous private extractor connection

Private authenticated administrator-to-extractor connection: **COMPLETE within
local fake scope**; overall launch remains **NO GO**. Started clean at `70af8a5`.
Source checkpoint `2bd63050e18ea7e749b60aa98f23c39688dbe3ab` left a clean tree.

- Added same-origin administrator POST/CSRF proxy, independent server-only key,
  bounded shared PHP client and private POST-only Node service contract. Preview,
  health and optional shot caching use the same client. Browser has no service
  credential/destination, no upstream HTML or diagnostics. Live/production paths
  remain blocked; the default development stack still disables extraction.
- Added explicit internal-only fake override, pinned Node/cURL dependencies and
  transport fault fixtures. Current-admin/member/guest boundaries, CSRF, validation,
  no CORS, request/body/time/redirect/media limits and service admission exercised.
- Private contract 33/33, client validation 14/14, real cURL faults 4/4, Node service
  6/6 and retained Node 63/63 passed. Docker integration 88/88 + OAuth 7/7,
  product 112/112 and disabled environment 21/21 passed. Fresh native schema 22/22,
  HTTP 94/94 and authenticated disabled/local services 4/4 passed; scoped audits zero.
- Manual administrator health/preview, stopped-service and restored-disabled UI
  verified with fictional input only. Screenshot and exact evidence/earlier failed
  harness/build attempts: EXTRACTOR_CONNECTION.md and EXTRACTOR_CONNECTION_*.
- PHP 195/JS 48 syntax and additional CJS/Python/JSON parse passed; target guards
  9/9 and extractor isolation 12/12 passed. Gitleaks staged/full 468-file candidate/history scans returned zero findings;
  10 private ignore checks passed (default-rule/private-data limits remain). All owned
  services stopped; private configuration/volumes preserved. No real provider,
  recipient, production connection, deployment, remote push or publication.

Next authorized local work: **Bot ingestion/scheduler/disconnect/restart/queue and
log-redaction regressions, then isolated staging recovery/load/monitoring rehearsal.**
Real hosting/TLS and limited live tests still need concrete targets and approval;
no live or publishing authorization follows from the repeated release plan.

## Previous production audit

Fresh-launch production-readiness audit: **PARTIAL — NO GO for publishing**.
Owner target is a real free-access relaunch with fresh data, full movies/episodes
via WhatsApp only, and website catalogue/shorts/trailers. Hosting is undecided;
small beta plus separate staging is an assumption. CV work/old-data reconstruction
are outside this task. Started clean at `28e0ddf` on `zinema/local-dev-safety`.

- Created a 92-entry source inventory, functional evidence/gap matrix, precise
  manual browser record, defect log, staging requirements and capped controlled
  live-test plan. See PRODUCTION_AUDIT.md and PRODUCTION_FEATURES.md.
- Repaired search/JSON-LD, input/target/visibility/security boundaries, collection
  shot schema v4 (38 tables/2 views), URL/TLS validation, serialized password/token
  admission/proxy trust, administrator session checks, settings initialization,
  WhatsApp-only CTAs, mobile administrator navigation/assets and trailer player/
  pagination/share/reaction recovery. Added bot lifecycle/identity/queue/health
  repairs and fixture-tested bounded extractor transport with upgraded full lock.
- Executed product 112/112, security 17/17, process admission races 2/2, route
  boundaries 92/92, Node/SQL fake delivery 7/7, Node 62/62 and protocol 6/6.
  Retained integration 88/88 + OAuth 7/7, operations 57/57, quarantine 51/51,
  environment 21/21 and local-target guards 7/7 passed. Fresh native schema 22/22,
  HTTP 94/94, extractor disabled/local 4/4 and retained Node subset 22/22 passed.
  No new PHP diagnostics. Full extractor imports 3/3; three scoped npm audits
  zero advisories. Earlier failures and fixture/tool limits remain documented.
- Manual evidence: fake OAuth/member saved shot, catalogue/search, actual admin
  movie create/edit/logout, mobile navigation and original-motion trailer playback/
  save. Every action was not manually tested. No integration is externally verified.
  PHP 190/JS 47/CJS 11/Python 14 syntax passed; sanitized JSON parsed. Secret scan,
  local commit and stopped-service evidence are recorded at completion below.
- Launch blockers: authenticated private extractor path/browser isolation; complete
  bot scheduler/restart/ingest and log minimization; real staging TLS/proxy/secrets/
  artifact; coordinated restore/load/alerts/rollback; approved live delivery/mail/
  OAuth/extraction and owner content/privacy/support/moderation operations. These
  gaps prevent a production-readiness claim despite local passing suites.
- Production adapters/payments/publishing remain disabled. No real recipients,
  application-provider transactions, production access, deployment, purchase,
  remote push or credential rotation. Public package/docs research only.
  Owned Docker/native services stopped; private volumes/state preserved.

Exact next recommended task: **Implement/test the private authenticated
administrator-to-extractor proxy with local fakes, then complete bot scheduler,
restart/ingest and log-redaction regressions. Keep live integrations, production
access and publishing disabled.** Follow with staging restore/load/monitoring
rehearsals, then the controlled live approval plan; do not publish yet.

### Audit verification and local checkpoint

Gitleaks 8.30.1 archive/binary matched the saved upstream checksum/archive.
Staged, full 444-file candidate and all-local-history scans returned zero findings;
nine private-path ignore checks passed, and no forbidden paths were indexed.
Default rules and registry audits have limits; private ignored data was not scanned.
PRODUCTION_SECURITY_RESULTS.json records the exact scope. PRODUCTION_SHUTDOWN_RESULTS.json
records zero owned running containers and closed 8088/9081/3307/8089/3334 listeners.
No volumes were deleted. Source checkpoint `013bd480889c5adad1e0d2d9b290b842018e8910` left a clean tree.
This documentation-only handoff follows in a separate local commit; no remote Git
or publication is involved. Final JSON parse covered 67 candidate files.

## Previous recoverable upload phase

Owner-requested recoverable quarantine and explicitly reviewed cleanup for owned
development uploads: **COMPLETE within the authorized local scope**. Source commit
`637327e55d772dcb6acce4383b226bbaee606bd5` on `zinema/local-dev-safety`; starting
tree was clean at `a31d47e`. Production integrations and publishing remain disabled.

- Added guarded CLI review/history/quarantine/restore/recover, revision/case review,
  private verified rescue copies, separate quarantine quota, journal/audit and
  reviewed interruption handling. No last-copy purge is exposed. Restore retains
  its archive and never overwrites an existing path.
- Coordinated cooperating media-reference writers with DB gate -> volume lock;
  pending/quarantined references fail closed. Unknown/legacy/shared/young/changed
  and unsafe files stay held. Direct trusted writers remain outside the protocol.
- Final quarantine 51/51, retained operations 57/57, integration 88/88, OAuth helper
  7/7, local boundary 7/7 and environment 21/21 passed. Fresh native schema/bridge
  22/22 (38 tables/2 views), HTTP 94/94, Node 22/22 + 6/6 and extractor 4/4 passed.
  Two scoped npm audits reported zero advisories. Earlier failed restore recovery
  and restricted socket execution are documented, corrected and preserved privately.
- Static checks passed for 185 PHP/41 JavaScript and 11 Python files; sanitized
  JSON parsed. Gitleaks staged/full-candidate/all-local-history scans returned zero
  findings across all 384 candidate files; eight ignore checks passed. Scanner
  limits remain explicit. The source commit left a clean tree; this documentation-only
  handoff follows in a separate local commit.
- Owned containers/native services stopped; 8088/9081/3307/8089/3334 closed. Private
  credentials, database/upload/rescue volumes and audit remain preserved/ignored.
  No remote Git, production connection, deployment or publishing was performed.

Runbook: `dev/QUARANTINE.md`. Evidence/limits: `QUARANTINE_PHASE.md`,
`QUARANTINE_*_RESULTS.json` and `DECISIONS.md`. The product remains in local recovery.
Audit/journals are bounded and trusted-admin mutable; recovery is not a distributed
transaction or a power-loss guarantee. Total disk space is not freed. Production
readiness, arbitrary reference detection and malware/codec safety remain unverified.

Exact next recommended task: **Verify coordinated backup and restore of the owned
development database, upload volume and private lifecycle manifest using fictional
interruption fixtures; keep production integrations and publishing disabled.**

### Previous operator reconciliation and inventory phase

Owner-requested local operator reconciliation and upload quota/cleanup inventory:
**COMPLETE within the authorized local scope**. Source commit
`cae7ab7f05680a8a4894c732aa3529c518d95e33` on `zinema/local-dev-safety`; starting tree
was clean at `b9dead6`. Production integrations, publishing, deployment and remote
Git actions remained disabled. The overall product remains in local recovery.

- Added CLI held-claim list/review/resolve with version checks, row locking, explicit
  stopped-worker/evidence attestation, replay handling and an append-only-through-tool
  audit. Old claims close permanently; no delivery, reuse or replacement is triggered.
- Added owned-volume quotas (64 MiB/500 physical files), private provenance, shared
  admission/initialization locking and fail-closed missing/corrupt-state handling.
  Read-only cleanup inventory retains referenced/shared/young/changed/unknown/unsafe
  assets. There is no cleanup mutation command. Legacy/media data stays preserved.
- Canonical v3 has 38 tables/2 views and an additive development-only migration.
  Claim revisions/reconciliation guards and expiry predicates preserve held evidence;
  the legacy cron remains disabled. Local Docker context and owned-gateway checks
  prevent remote-daemon/unknown-listener test use; explicit Compose env-file avoids
  preserved configuration loading. See `dev/OPERATIONS.md` and `OPERATIONS_PHASE.md`.
- Executed final operations 57/57, direct CLI 7/7, local-target boundary 7/7, retained
  integration 88/88, OAuth helper 7/7 and environment 20/20. Fresh native canonical
  schema/bridge 22/22, HTTP 94/94, Node configuration/bot 22/22, token protocol 6/6
  and extractor 4/4 passed. PHP 182/182, JavaScript 41/41, Python AST 10 and JSON
  parsing 37 files passed. Both scoped npm audits returned zero advisories.
- Documented and corrected fixture setup failures and one agent orchestration port
  collision; no failures were hidden. Final suites passed after corrections.
- Gitleaks 8.30.1 checksum rechecked against saved upstream record. Staged changes,
  all 363 candidate files and all local history returned exit 0 / zero findings.
  Eight private-path ignore checks passed; new staged whitespace checks passed.
  Scanner/audit limits and historical publication whitespace remain explicit.
- Source commit left a clean tree. This documentation-only completion record follows
  in a separate local commit. All owned containers/native test processes stopped;
  ports 8088, 9081, 3307, 8089 and 3334 verified closed. Private credentials, metadata,
  datasets and named volumes remain preserved/ignored. Nothing was pushed.

Owner attention: worker quiescence and delivery evidence remain trusted operator
attestations, not external verification. Exactly-once delivery, live integrations,
production access control, arbitrary reference detection, malware/codec validation
and distributed quota enforcement are not established. Fictional test records and
held examples accumulate intentionally. Inventory candidates remain advisory.

Exact next recommended task: **Add recoverable quarantine and explicitly reviewed
cleanup for owned development uploads, with audit, restore and concurrency tests;
keep production integrations and publishing disabled.**

### Previous local development phase

Owner-requested reproducible local development, fake mail/OAuth tests, uploads
and distributed claims, and sanitized local Git baseline: **COMPLETE within the
authorized local scope**. Source baseline commit: `9c48b7ca8a2b6535b6da0b408d49bbdfe7280f1a`
on local-only `zinema/local-dev-safety`; starting checkout was clean `bf370a4`.
Production integrations, deployment, remote Git operations and publishing stayed
disabled. The product remains in recovery and is not production-ready.

- Added pinned Docker development, fictional SMTP/OAuth, private generated config,
  upload validation/preservation, session-bound OAuth state/PKCE, database claims
  and the additive owned-development migration. Files and limits are recorded in
  `LOCAL_DEV_PHASE.md`, `DECISIONS.md` and `dev/README.md`.
- Executed 88 HTTP + 7 OAuth helper + 6 new bot protocol/client + 17 environment
  checks; retained 94 HTTP + 22 schema/bridge + 22 Node + 4 extractor checks passed.
  A fresh sanitized source export with new database volumes also passed 88/7/17.
  Manual browser invalid login, fake OAuth member login and sign-out passed.
- PHP 177/177 and JavaScript 41/41 syntax, Python AST 8 files and JSON parsing
  26 files passed. Two scoped npm audits reported zero advisories. These are
  executed static/local test results; real integrations are not yet verified.
- Gitleaks 8.30.1 archive checksum rechecked; final staged changes, all 337
  candidate files and all local history scans returned exit 0 / zero findings.
  Eight ignore checks passed; no forbidden private/data paths were indexed.
  New staged whitespace checks passed. Historical publication whitespace remains
  unresolved; scanner/audit and runtime limits are explicit in the phase report.
- Source commit left a clean worktree. This documentation-only completion record
  follows it in a separate local handoff commit. No existing history was rewritten
  and nothing was pushed. Both Docker stacks and all owned native test processes
  stopped; ports 8088, 9081, 3307, 8089 and 3334 closed. Private config and volumes
  remain preserved and ignored.

Owner attention: held/uncertain deliveries need reviewed operator reconciliation;
media quotas/cleanup, full video/malware validation, complete extractor/container
security checks and real provider verification remain outside this completed local
scope. The shared bot API key remains a worker trust boundary. See the full report
for the absence of an exactly-once delivery guarantee.

Exact next recommended task: **Implement a local-only operator reconciliation
workflow for held token deliveries and an upload quota/cleanup inventory, using
fictional fixtures; keep production integrations and publishing disabled.**

### Earlier completed and partial work

Owner-requested branch naming update: **COMPLETE within scope**. The five
review branches use `zinema/import-*` locally and remotely. Replacement PRs
#6–#10 are open against `main`; superseded PRs #1–#5 are closed and linked to
their replacements. Existing source commits and author information are intact.
`main` is unchanged. Relevant documentation/metadata checks are recorded below.

Owner-authorized initial Git publication: **PARTIAL — five review PRs prepared;
legacy whitespace check unresolved**. The ordered import contains 37 conventional
commits including publication and branch-name handoffs. Branches are pushed and all five PRs were verified
open, non-draft and mergeable into `main`. Nothing was merged or deployed.
See `PUBLISHING.md` and `PUBLISHING_RESULTS.json` for scope, inventory and limits.

The prior local authentication/CSRF, bot delivery and schema repair is **COMPLETE
within its requested local scope**. See `REPAIR_PHASE.md` and the repair-prefixed
results. Overall product recovery and production readiness remain incomplete.
Earlier assessment failures remain historical evidence.


## 2026-10-25 publication handoff

- Published the supplied source as 35 logical import commits plus two documentation handoffs,
  across `zinema/import-foundation`, `zinema/import-website`, `zinema/import-admin`,
  `zinema/import-adapters` and `zinema/import-evidence`. PRs #6–#10 target `main`;
  merge in that order with normal merge commits to retain the series.
- Changed publication exclusions, optional empty-by-default support contacts,
  sanitized examples, README, third-party attribution and recovery records.
  Preserved all originals privately; no production data, sessions, user uploads,
  installed dependencies or credentials were published. The extractor is tracked
  as ordinary source and its local embedded repository remains intact.
- Executed PHP lint 167/167, JavaScript/CJS syntax 38/38, JSON parsing, configuration
  regressions 10/10 and fake-client bot regressions 12/12. Staged checks repeated
  before every source import commit. These are static and test-execution claims;
  prior HTTP/browser/schema results are retained, not newly rerun here.
- Gitleaks 8.30.1: upstream archive checksum matched; 35 staged scans and full
  history scan exit 0, zero findings. One initial illustrative-token false
  positive was removed from its comment without an allowlist. Private exclusion
  checks 12/12 and public environment-template checks 3/3 passed. Scanner limits
  and supplemental contact/SQL/media review are documented in `PUBLISHING.md`.
- Unresolved whitespace check: exit 2, 1,709 findings in 111 files. Retained legacy
  formatting makes this phase PARTIAL; no passing whitespace scan or comprehensive
  security, licensing, live-service or production certification is claimed.
- Git: original `main`/remote head `f89da9b61fe92f4023e773b7965a02d4bdd72721`
  remains unchanged. Imported head before this handoff is
  `944116cc5a7734d0afc16bc0ec91019423c22da1`; all 35 hashes are in the results
  inventory. This handoff's final hash and post-push clean status are reported in
  the execution handoff. No commits were amended or rewritten.
- Owner attention: review and merge the five linked PRs in `PUBLISHING.md`.
  No external recipients or live integrations were contacted. Next recommended
  Codex task: prepare a reproducible isolated development environment and fake
  mail/OAuth end-to-end tests using the canonical schema; review uploads and
  distributed token reservation; preserve history and keep live/deployment off.

## Historical starting state (2026-10-23 discovery)

- Workspace root is not a Git repository; failed root status was not treated as clean.
- `docs/recovery/STATUS.md` and `DECISIONS.md` did not exist when checked.
- Nested bot repository status initially failed Git's ownership check. A command-scoped `safe.directory` exception allowed read-only status; no global Git configuration was changed.
- Existing bot modifications: `.env.example`, `.gitignore`, `config/api-client.js`, `config/database.js`, `config/file-manager.js`, `config/multi-tier-forwarder.js`, `index.js`; untracked `config/environment.js`. Preserved without staging or changes.
- Existing source already includes recovery guards. Their origin and historical phase completion have not been established.

## Historical discovery work (2026-10-23)

- Traced the principal website, account, Shots, catalogue, playback, administration, bot and extractor paths.
- Inspected dependency manifests and SQL structure without importing SQL or displaying data rows.
- Added `PROJECT_DESCRIPTION.md`, this status record and `DECISIONS.md`. No application source changed.

## Historical discovery checks (2026-10-23)

- Git status: root not a repository; nested bot changes recorded above. No commit created; commit hash not applicable.
- Static source inspection: completed for the main subsystems and selected implementation paths; not an exhaustive line-by-line audit.
- JavaScript syntax: `node --check`, 31 selected standalone files, 31 passed, zero failures. Excluded dependencies, minified files, historical bot variants, scheduler sample and `refresh-method-new.js`.
- PHP lint/runtime: not performed; PHP unavailable on PATH.
- Test-covered: feature test coverage not established. No application regression suite executed.
- Runtime verified / externally verified: no application features. No services started, database operations or external connections performed.
- Security: used filtered reads, omitted sensitive contexts, avoided session/environment/log dumps and SQL data. gitleaks/trufflehog unavailable on PATH; no dedicated secret scan passed or claimed. Not a comprehensive vulnerability or privacy audit.

## Historical discovery risks (2026-10-23)

- Inconsistent PDO/MySQLi bootstrap paths and code/schema mismatches need reconciliation.
- Existing quarantines interrupt download/payment flows; WhatsApp and extractor recovery gates alter legacy behavior.
- Sensitive legacy configuration, session directories and logs exist; do not publish without a later authorized sanitization and review.
- Existing application changes lack corresponding recovery records. Root version history is absent.
- No isolated runtime/database fixture has been established or tested in this phase.

## Next recommended phase after the earlier local repair (historical)

Reproducible local development setup, fake mail/OAuth flows, upload/distributed-token review and a sanitized Git baseline after a dedicated secret/data review. Exact next task is in `REPAIR_PHASE.md`. Production integrations, publishing and deployment remain disabled; do not expand scope automatically.

## 2026-10-25 local repair handoff

- Owner explicitly authorized the repairs, schema reconciliation and passing retained regression tests. Implemented the shared verified-login/session policy, CSRF transport and enforcement, POST admin deletion/logout, digest/expiry/revocation for remember cookies, honest registration outcomes, correct PDO consumers, canonical schema/mapping queries and acknowledged/coalesced bot delivery.
- Added `includes/session.php`, `includes/auth.php`, `includes/registration.php`, `assets/js/security.js`, `database/schema.sql`, schema/dependency test helpers, locked HTTP test dependencies, `REPAIR_PHASE.md`, `SCHEMA_RECONCILIATION.md` and repair-prefixed evidence/results. Updated affected PHP templates, APIs, bot module/lockfile and recovery records. Detailed groups/limits are in the repair report; baseline differences are inventoried separately and may include concurrent work.
- Final verification: **94/94** application/HTTP/security/static assertions; **10/10** guards; **12/12** fake-client bot tests; **19/19** schema/SQL-only adapter checks; **4/4** extractor local/disabled checks. All corresponding commands exit 0. PHP **167/167**, JavaScript **40/40** syntax checks pass. Final request logs have zero new PHP warnings/notices/fatal errors/deprecations. Test coverage and execution are both recorded; no live delivery claim follows.
- Browser verification: unverified password login denied, verified AJAX login and protected Save persisted, generated trailer played, admin token-bearing POST forms and POST logout worked. Screenshots retained. Email templates were excluded from browser CSRF markup.
- Canonical schema: **37 tables, 2 invoker-security views**, consistent collation, data-free, tested from final file in a second empty database. No original data imported. No destructive existing-database migration provided.
- Security: repaired bot lockfile and locked extractor HTTP test subset each return zero npm audit findings. Clean isolated install and offline factory/serializer smoke test passed. Dedicated secret scanners unavailable; no comprehensive secret/privacy/security certification claimed. Chromium/Puppeteer and previous installed bot dependency tree are outside the audit claim.
- During the phase, Git changed from absent to an existing root repository (`main`, existing commit `f89da9b`, many untracked files). Nested bot resolves to root. No initialization/staging/commit/push/deployment by this repair. Current unrelated README and comment-only legacy bot SQL changes were preserved; provenance not assumed.
- Cleanup: all owned Apache/MySQL, extractor and fake SQL bridge processes stopped and test ports closed. No live integrations or real recipients used. Private generated credentials/sessions/logs and pre-repair evidence remain under excluded directories.
- Remaining limits: full legacy-data migration, live adapters, distributed cross-bot token reservation, large-upload/reconnection behavior, fake email/OAuth end-to-end coverage, upload hardening, broad performance/accessibility and first-publication secret/data review remain future phases.

## Historical 2026-10-25 assessment handoff (before repairs)

### Scope and current checkout

- The owner requested reading the product and testing it manually and automatically; this authorizes local investigation, testing and test-only fixtures. It does not authorize production connections, publication or application repair.
- Root and nested bot `git status` both fail because neither is a Git repository in this checkout. Earlier nested-repository changes are historical observations and cannot be treated as current tracked changes. No commit/hash applies.
- The project description exists at `docs/recovery/PROJECT_DESCRIPTION.md`; an initial lookup at the workspace root was corrected.

### Completed work and files changed

- Added `WORKING_STATE_ASSESSMENT.md`, `AUTOMATED_RESULTS.json`, `SERVICE_RESULTS.json`, `STATIC_CHECKS.json`, the `tests/` harness/readme/Node regressions and `evidence/` screenshots/TAP records. Updated this file and `DECISIONS.md`.
- Established a temporary Apache/PHP/MySQL fixture using code/style copies, structure-only SQL, fictional accounts and generated media. Original application source hashes for mirrored files remained unchanged. No application behavior changed.
- Ignored `.runtime/` holds generated private test databases, credentials, logs, sessions and dependencies. Added a runtime Apache deny file. These are disposable assessment fixtures, not a recovered database or public artifacts.

### Executed verification and evidence classifications

- PHP 8.2.12 lint: 164/164 passed; JavaScript syntax: 37/39 passed, two auxiliary bot files failed. **Statically inspected / syntax validated**, not feature verification.
- HTTP assertions: 63/75 passed, 12 failed; final command exit 1. **Runtime verified locally** for exercised requests; failing expectations retained.
- Configuration/startup regression tests: 10/10 passed. Fake-client bot modules: 5/7 passed, two confirmed failures; exit 1. **Test-covered and executed**; no real WhatsApp verification.
- Extractor health and disabled/local fixture behavior: 4/4 passed using temporary HTTP dependencies. Live extraction remains quarantined and unverified.
- Manual browser: Shots/movie/episode sample playback, navigation, filter, desktop search, AJAX login, favorites/profile and logout exercised. Unverified standalone login bypass reproduced. Mobile 390×844 and desktop 1440×900 smoke checks had no measured horizontal overflow. Screenshots retained; omitted logos/CDN restrictions limit visual fidelity.
- **Externally verified:** npm advisory matching only, not production functionality. Bot supplied lockfile: 15 dependency entries (2 critical, 10 high, 3 moderate). Temporary extractor HTTP subset: 7 entries (3 high, 1 moderate, 3 low), not a deployed/full extractor inventory.
- Dedicated secret scanners gitleaks/trufflehog unavailable; no dedicated secret scan passed. Source pattern inventory covered 203 PHP/JS files and is not a security certification. Sensitive preserved files/data were not dumped. No production-user contacts or live integrations used.
- Sandbox socket restrictions and initial harness configuration/parser/reporting failures were investigated and corrected before final results. Node TAP child-runner serialization was avoided with `--test-isolation=none`; final evidence contains all intended tests. Initial harness failures are not counted as application defects.
- Cleanup: audit Apache and MariaDB shutdown commands both exit 0; loopback ports 8088 and 3307 confirmed closed. Extractor processes terminated and port 3334 closed. Browser tab closed and viewport reset.

### Unresolved risks and blockers

- Authentication verification bypasses; missing CSRF enforcement on tested mutation/authentication paths; admin GET deletion.
- Bot coalescing references undefined `chatId`; missing upload acknowledgement can still produce success.
- Trailer/movie-comment PDO bootstrap failures and missing schema tables; non-deduplicating `user_views` structure.
- Signup reports success despite disabled/failed verification mail; real verification/password recovery remain untested.
- Dependency advisories and auxiliary JS syntax failures remain unresolved. Existing sessions/logs/private media and absent version history still prevent safe publishing claims.
- Downloads, payments, WhatsApp and live extraction remain deliberately unavailable. No complete end-to-end product, load, comprehensive security or production-readiness certification is established.

## 2026-10-25 branch naming handoff

- Renamed all five review branches to `zinema/import-*`, verified replacement
  refs at the original heads, set local upstreams, and removed superseded remote
  names. Current branch: `zinema/import-evidence`.
- Created PRs #6–#10 against `main`, carried over descriptions/comparison links,
  attached replacements to the chat and closed PRs #1–#5 with replacement links.
- Updated this status, `DECISIONS.md`, `PUBLISHING.md` and publication JSON branch
  labels/PR links. This adds a documentation commit; existing commits are intact.
- Verification: five local/new-remote heads matched before cleanup; new PRs
  verified open/non-draft/mergeable. Documentation JSON parsing, staged whitespace
  and Gitleaks checks run before commit. No application/runtime test was rerun for
  this metadata/documentation-only change. Prior publication whitespace findings
  remain unresolved; they do not represent a new rename failure.
- Git baseline before this update: `9ff34b61ffb868555cf4a45d17a77f3c83b49b59`;
  final documentation hash and clean/remote status appear in the execution
  handoff. No merge, deployment, history rewrite or author change was performed.
- Owner attention: review/merge PRs #6, #7, #8, #9, #10 in that order. Next
  recommended Codex task remains the reproducible isolated development environment
  and fake mail/OAuth tests, with uploads/distributed token review; preserve
  history and keep live integrations/deployment disabled.
