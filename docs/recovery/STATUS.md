# Recovery status

Updated: 2026-10-25.

## Current phase

Owner-requested local operator reconciliation and upload quota/cleanup inventory:
**IN PROGRESS**. Starting state clean at `b9dead6` on `zinema/local-dev-safety`.
Scope and intended behavior are recorded before implementation in
`OPERATIONS_PHASE.md`. No production integration, publication or remote Git action
is authorized. Existing development volumes/media and prior evidence are preserved.

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
