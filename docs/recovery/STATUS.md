# Recovery status

Updated: 2026-10-24.

## Current phase - Jira ZL-19 (PARTIAL)

Owner authorized ZL-19 local authentication/admin hardening. Current extracted
working tree remains authoritative, including all prior dirty work. ZL-19 is
PARTIAL: independent typed Identity services and synthetic fixtures, three narrow
legacy fixes (CSRF malformed/expiry handling, admin error suppression, preserve
expired unverified signup accounts), exact CI/test discovery and ten security
guides plus inventory/report. No new public privileged route or real requirement.

Final host/image 566 tests pass, zero failure/skip; context 174 PHP/68 JS parsers;
full eligible syntax 235 PHP/69 JS. Identity 32 PHP contracts plus 10 HTTP/controller
Node tests/subtests; actual identity browser 18, unchanged original HLS 49 and
quarantine UI 8 pass. Read-only network-none 176 tests pass without host/session
mounts; image exactly 297 reviewed matching paths, no symlinks. HLS last startup
11,555/1,049 ms is a sample, not a production performance claim.

Complete users/admins/password_resets schema remains unavailable. No auth SQL or
migration is invented/applied; SQL/manifests, Content/Catalogue/Playback and ZL-18
gates remain byte-preserved. Production factory is unconfigured/503. MFA is NOT
implemented: library, encrypted key lifecycle, verified binding/HTTPS and owner
recovery are missing. All new privileged permissions deny without verified step-up;
legacy flags/admin routes are not thereby secured. Real-account compatibility,
shared stores/limits, mail outbox/delivery, full CSRF/RBAC/upload integration and
recovery/rollout approval remain release blockers. No real account/session/private
SQL/provider/production operation, Git write, commit/push or ZL-20 occurred.

See [execution report](../security/ZL19_EXECUTION_REPORT.md),
[test evidence](../security/AUTH_TEST_RESULTS.md) and
[rollback](../security/AUTH_ROLLBACK.md). Recommended next requested planning task
is ZL-20; production authentication first needs approved sanitized ZL-14 identity
DDL and a rehearsed owner recovery/rollout plan. No automatic continuation.

## Previous phase - Jira ZL-18 (PARTIAL)

Owner authorized local quarantine of unapproved payment, extraction, unofficial
delivery and legacy external media. Current extracted workspace remains source;
409 starting eligible inputs /101 existing dirty paths on
feat/zl-13-14-environments-migrations at 958dd9eaf97c72ea6e76648eae6814d775f03641,
empty index. No production/private store access, Git writes, live provider or ZL-19.

Implemented one shared policy with PHP/Node gates; flags cannot enable the four
live capabilities. Ten Node entry/module guards plus 114 method/action checks,
31 PHP route controls, nine token-helper guards, safe status endpoint, bounded
separate offline simulator and accurate disabled UI. Historical implementations
retained; no session/queue/token/payment record/media deletion. Browser legacy
extraction/cache-origin/embed/episode fallbacks are intentionally unavailable;
ZL-17 verified HLS/progress and Content/Catalogue modules remain unchanged. Bot
npm dev no longer launches a watcher. Shared CI/context and seven guides/static
operation inventory/contribution/recovery/architecture records updated.

Host/image 524 tests pass, zero failure/skip; context syntax 161 PHP/63 JS,
full eligible 222 PHP/64 JS. Dedicated read-only network-none run 134 tests passes,
no mounts/private sessions; direct-entry tripwires observe zero provider import,
transport, private read, write or timer. Browser unavailable UI 8 and unchanged
HLS 49 checks pass, no external request. 31 host routes 403; withheld admin source
absent/404 in image and remaining 30 actual handlers denied. Actual Apache status
200, payment/token/cron/download 403; playback/catalogue remain 503, pure view 200.
Managed CI has zero app tables and readiness 503. No SQL/database harness change;
prior fresh database suites not rerun. Image 279 exact reviewed paths; final hash/
shutdown proof appended below. Gitleaks expanded 338 text inputs pass; actionlint
passes; shellcheck/pyflakes NOT RUN, hosted CI NOT VERIFIED. Scan excludes private
stores/history/generated media/binaries and is not exhaustive security approval.

Remaining: protected historical variants and arbitrary/deployed startup/schedules,
provider approvals and full legacy canonical/auth/admin/runtime coverage unresolved.
Mail/OAuth retain prior controls and separate approval uncertainty. Web egress is
not globally firewalled; network-none evidence is for tested contracts only. No
production state or callback receipt is certified. Next task ZL-19 authentication/
administration hardening, only on a separate request. ZL-28/29/30/44/45/54 are
proposed dependencies, not verified Jira links or completion claims. See
../security/INTEGRATION_QUARANTINE.md and QUARANTINE_TEST_RESULTS.md.

## Previous phase - Jira ZL-17 (PARTIAL)

Owner explicitly authorized local Shots/movie/HLS/session/progress implementation
and original synthetic fixture/browser verification. No Git staging/commit/push,
production/private SQL/real media/provider, cloud or automatic ZL-18. Continued
from 374 eligible inputs and 62 existing dirty ZL-15/ZL-16 paths on
feat/zl-13-14-environments-migrations at 958dd9eaf97c72ea6e76648eae6814d775f03641;
index empty. No audit restart or historical ZIP/source replacement.

Implemented apps/web/src/Playback/, four additive PHP APIs, pure catalogue view,
validated Shot resolver, native/MSE player and opt-in contextual tab-local progress.
Only index movie/series Shot links and movie Watch/player block changed, plus
additive movie/series view gates. Default public factories remain unavailable;
no real licence/identity/provider binding. Reused unchanged Content/Catalogue
policies and interfaces. Original generated HLS/captions stays private, outside
web root and image; every fixture resource/range reauthorizes. No SQL/schema change.
Seven streaming guides, contribution/architecture/README/security/CI records updated.

Verification: host/image 388 passed, zero failures/skips, 156 PHP/56 JS context
syntax; full eligible 217 PHP/57 JS. New 56 unit/contract tests plus 49 actual
Chrome 155.0.8059.85 browser/protocol checks. Desktop native HLS and forced-MSE
390px viewport decoded video/captions, progress/resume/retry/denials/expiry/teardown
pass. Physical mobile/Safari/fullscreen/keyboard/native control/a11y audit NOT
VERIFIED. Last startup samples native 4,647 ms /MSE 5,903 ms, repeated range
1.34-6.081 seconds, no speedup/SLA/production capacity claim. Public Apache APIs
503/no-store, new views 200 and private file paths 404. CI audit remains least
privilege/zero app tables/readiness 503; no old volume migrated. Historical fresh
DB suites not rerun because SQL/database harness is unchanged this phase.

Image exactly 265 host-matching reviewed files, context scan 262 text and expanded
scan 314 text pass. npm audit zero known package advisories; actionlint pass;
shellcheck/pyflakes NOT RUN; hosted CI NOT VERIFIED. Secret scans exclude protected
private stores/history/generated media/binaries; not exhaustive certification.
Final preservation/link/cleanup proof appended below.

Remaining: complete canonical/account/media contracts, approved real licence/
identity/territory/age/grants, shared limits, real protected CDN/private-origin
controls and device/legacy integration/load/release verification. One playback
cookie per browser supports one active asset; multi-title tabs need future design.
Next recommended task is separately authorized ZL-18 quarantine of unapproved
payment/extraction/unofficial delivery. Do not start it automatically. Production
HLS remains gated by ZL-19 and ZL-41-45; proposed dependencies are not verified
Jira edges. See ../streaming/PLAYBACK_TEST_RESULTS.md and STREAMING_OPERATIONS.md.

## Previous phase - Jira ZL-16 (PARTIAL)

Owner authorized local catalogue/API/search and entitlement implementation, with
all current working-tree ZL-15 changes preserved. No Git staging/commit/push or
production/private SQL/provider access, and no automatic ZL-17.
Started at 958dd9eaf97c72ea6e76648eae6814d775f03641 on
feat/zl-13-14-environments-migrations, empty index, 352 eligible inputs including
40 existing ZL-15 dirty paths. ZL-14/ZL-15 remain PARTIAL, not production contracts.

Implemented additive apps/web/src/Catalogue/, api/catalogue/v1.php and synthetic
contract/HTTP/SQL tests. Reused unchanged Content policy; public composition stays
unconfigured (503). No canonical/account schema guesses, new migrations/indexes,
real licensing/auth/geo/grant integration or playable locator. Existing frontend,
website, bot/extractor, Content module and SQL source remain byte-preserved.
Seven API guides and shared context/CI/verification documentation are updated.

Host/image full suite: 332 passes, zero failure/skip; parsers 141 PHP/48 JS in
context, full eligible 202 PHP/49 JS. Catalogue DB: 13 prerequisites +24 assertions,
524 synthetic rows, read-only and excess privilege gates, outage/recovery pass.
Existing Content 13+19 plus actual race/privilege tests, original DB 42 assertions,
legacy access and encrypted synthetic backup/outage regressions pass. Actual Apache
new-route 503, POST 405, forged query 400, no-store; existing CSS/guard smoke passes.
Live/dependency/ready remains 200/200/503, schema unverified. Full legacy UI is NOT
runtime verified. Performance 20 reads: p50 70.69/p95 82.60/max 102.12 ms, 8,886 bytes
maximum; search still ALL/filesort, not production-scale proof.

Image inventory 237 exact reviewed hashes; context secret scan 234 text inputs
passes. Actionlint passes; shellcheck/pyflakes NOT RUN, current hosted CI NOT
VERIFIED. Expanded post-documentation scan, final Git/checksum and teardown proof
is appended below. Private SQL/environment/session/data/media/history/binaries
are outside scan scope, not certified clean. No production database was accessed.

Remaining: complete permitted canonical/account DDL and approved bindings, actual
licence operations, ZL-19 identity/admin, ZL-55 access rules/grants, trusted territory/
age evidence, shared rate protection, protected preview/HLS/CDN, legacy browser
compatibility and release validation. Proposed next task ZL-17 only after schema,
rights, identity and protected-delivery prerequisites are established; do not start
it automatically. See ../api/CATALOGUE_TEST_RESULTS.md and INTEGRATION_GUIDE.md.

## Previous phase - Jira ZL-15 (PARTIAL)

2026-10-24 (Asia/Colombo). Owner explicitly authorized local licensed catalogue /
rights implementation and synthetic verification, preserving current source and
Git. No staging/commits/push, production, live providers or automatic ZL-16. Earlier
no-ZL-15 statements below describe prior tasks, not the newly authorized scope.

Started clean on feat/zl-13-14-environments-migrations, HEAD
958dd9eaf97c72ea6e76648eae6814d775f03641, 328 tracked inputs and empty index.
Read project/recovery/source evidence. Missing full movies/series/episodes/shots/
users/admins DDL and current session-flag administration do not establish a safe
live integration. The protected private Database.sql was never accessed/copied.

Implemented outside the website document root: PHP metadata validation, protected
workflow, default-deny authority/canonical lookup ports, holder/asset/grant review,
versioned state machine, rights/time/territory/channel/revocation policy, safe
bounded discovery DTOs and synthetic-only PDO persistence with atomic audit/CAS.
No public/admin/UI/upload/fetch/media/AI adapter. Actual streaming always denied.

Two additive workflow/audit migrations and explicit ContentMigrations profile
reuse the existing safety/ledger engine without altering the four original SQL
files/manifest. One narrow regression-tested Runner parser fix preserves VARCHAR
length while normalizing integer display widths. Interim bounded aggregate storage
is not a normalized production catalogue/rights schema; no guessed FK or movie table.

Updated reviewed context/ignore, verification/database harness, environment test
and local CI workflow; created tests/content/ and six content guides. README,
architecture, security/migration/CI guides and contribution/recovery records updated.
Every legacy website/bot/extractor source byte, historical SQL, dependency/baseline
file and prior contribution prefix remains preserved. Final checksum/Git proof
and local teardown are appended below.

Verification: host/image 246 tests pass; 128 PHP/46 JS context parsers. Full eligible
source parsers 189 PHP/47 JS, no failures. Rights MySQL fixtures: 13 migration and
19 service assertions pass, actual concurrent editor race has one commit and one
stale rejection, excessive service privileges refused. Original 42 DB assertions,
legacy PDO/MySQLi probes, synthetic encrypted backup/restore and outage/recovery
pass. Existing DDL-coupled helpers still fail under DML-only privileges as recorded.
Managed CI volume has zero app tables; health remains 200/200/503. Final image:
222 exact reviewed paths, no missing/extra/hash mismatch. Gitleaks context scan:
219 text inputs passed after correcting a synthetic-fixture false positive without
rule suppression. Actionlint passes; shellcheck/pyflakes NOT RUN. Hosted execution
for these uncommitted changes NOT RUN. Expanded scan/link/preservation/teardown
results are recorded in the final handoff. Secret scans are not exhaustive privacy
or security certification; private stores/history/binaries are outside their scope.

Initial failure evidence and corrections are in
[content test results](../content/CONTENT_TEST_RESULTS.md). All failed ephemeral
runs cleaned up; existing volumes were not migrated, reset or deleted.

**Release blockers:** complete permitted canonical/account/admin DDL and verified
lookup/write/parent semantics; real RBAC/CSRF/identity integration; full legacy
route/browser tests; real legal verification/private evidence operations;
normalized production relationships/retention/backups; user entitlements and
protected origin/CDN/player enforcement. Legacy direct media URLs remain outside
the disconnected rights policy. No genuine licence approval or public media
publication is claimed. Full application readiness remains 503. ZL-15 is PARTIAL.

**Exact next recommended task:** supply independently approved sanitized complete
catalogue/account/admin DDL and a scoped authorization/compatibility plan; resume
ZL-14/ZL-19 prerequisites, then separately authorize ZL-16 catalogue search and
entitlement integration using these ports. The private dump is not a fallback.
Do not begin ZL-16 automatically. Review
[architecture](../content/CATALOGUE_ARCHITECTURE.md),
[workflow](../content/INGESTION_WORKFLOW.md),
[policy](../content/PUBLISHING_POLICY.md) and
[operations](../content/CONTENT_OPERATIONS.md).

## Previous publication phase (preserved record)

The owner subsequently authorized staging the existing ZL-12/ZL-13/ZL-14 work,
incremental Conventional Commits, feature-branch publication and review-ready PRs.
This supersedes the earlier implementation-only Git restrictions below; those
statements remain historical evidence, not a prohibition on this publishing task.
No merge, deployment, production access or ZL-15 is authorized by this task.

Continued from 804ec5a with 328 eligible existing inputs, an empty index and 64
changed/new paths. Refreshed origin/main at 94f7708; its tree matches 804ec5a and
GitHub confirms ZL-11 PR #13 merged. New work starts from that main tip. No reset,
stash, history rewrite, application edit or private dump access was needed.

- Review [PR #14](https://github.com/gayashan-indrasankha/Zinema.lk/pull/14) first:
  four commits for ads, pure bot configuration, export closure and refactor evidence.
- The dependent feat/zl-13-14-environments-migrations branch groups the tightly
  coupled environment and database infrastructure while separating containers,
  probes, migrations, disposable tests, context tooling, CI and documentation into
  focused commits. This avoids publishing an environment tip whose reviewed
  context refers to absent migration files.
- Exact staged snapshots receive Gitleaks 8.30.1 scanning, explicit path and diff
  review, whitespace checks and relevant parser/contract checks before commits.
  The refactor tip passes 85 tests; the integrated infrastructure snapshot passes
  145 tests and 115 PHP/44 JavaScript parsers using PHP 8.2.12 and Node 24.13.1.
  Earlier executed Docker and 42 database assertions remain in the implementation
  evidence below; they are not described as newly executed publishing checks.
- Original working-tree source bytes are preserved. The refactor execution record
  receives a historical-boundary clarification; recovery records document this
  new authorization. Private credentials, ignored data and historical private SQL
  are excluded. A secret scan is not an exhaustive security/privacy certification.

Publication handoff: [PR #14](https://github.com/gayashan-indrasankha/Zinema.lk/pull/14)
and [PR #15](https://github.com/gayashan-indrasankha/Zinema.lk/pull/15) are open,
ready for review and both target main. Merge #14 first, then #15, using Create a
merge commit to retain history. No retargeting is needed: shared ancestors drop
from #15's diff when #14 merges. This final packaging replaces the earlier plan
to target the second PR at the first branch.

Four refactor commits plus ten environment/migration/documentation commits,
including this handoff, preserve all 64 original changed/new paths. Before this
handoff, branch tip 4c183fb and PR #14 tip 6f3baf7 were pushed successfully and the
working tree/index were clean. All 328 initial eligible inputs remain; only four
documentation files were intentionally updated during publication. Every original
application, infrastructure, test, SQL, baseline and other input byte is preserved.
The previous contribution record remains an exact prefix. All 98 reviewed local
links/fences and actionlint pass; shellcheck/pyflakes are not installed.

GitHub Actions is enabled. Actual PR and push runs for 4c183fb are IN PROGRESS
(run 39993830225 / 39993776079), not a passed hosted check. The final handoff push
will trigger checks for its new tip; their latest status and final clean/remote
verification are reported at handoff. No containers or database services were
started during this publication phase. Scan limitations and earlier runtime
failures remain documented. ZL-12, ZL-13 and ZL-14 remain PARTIAL; publishing their
implementation does not satisfy unresolved acceptance gates. Next recommended
task: review/merge #14 then #15, then separately authorize the scoped ZL-14 schema
and compatibility follow-up already described below. Do not start ZL-15.

## Latest implementation phase (preserved record)

**Jira ZL-14 - Reconcile canonical SQL schema and reliable database access: PARTIAL.**
2026-10-23 (Asia/Colombo). The owner confirmed no sanitized full schema is available,
limited migrations to four independently verified definitions, prohibited private
dump access and speculative application changes, and requested all safe partial
work. No Git staging/commits/push, production access or automatic ZL-15.

- Starting state: branch docs/zl-11-production-architecture, HEAD
  804ec5ab07503c3fb0c311b4349443b87184e28f, 275 tracked + 32 pre-existing untracked
  inputs. Existing ZL-12/ZL-13 work preserved. Inspected 48 nonprivate source parent
  directories; no nested .git. Private Database.sql was not accessed/copied.
- Inventoried six SQL references, two embedded CREATE definitions and major
  PHP/Node consumers. Evidence index covers 39 named tables/views, 34 directly
  queried; 24 have no complete permitted definition. Historical token/message/
  bot/routine/view generations conflict and are not silently merged.
- Four versioned LF/checksummed CREATE migrations: site_settings (actual source
  name), rate_limits, movie_parts and episode_parts. This is a verified partial
  subset, NOT the complete canonical database. No other feature table invented.
- New PHP runner/CLI: file/order/checksum preflight, strict local synthetic target,
  server UUID and hashed marker checks, separate scoped migration role, advisory
  locking, running/applied/failed ledger, exact metadata/drift validation and
  tested post-implicit-commit forward recovery. No DROP/reset/conversion command.
- New temporary harness reuses ZL-13 images but creates a separate uniquely
  labelled internal network/tmpfs-only MySQL server, verifies zero initial
  non-system tables and no persistent mounts/ports, then removes its own resources.
  Existing dev/staging/CI volumes are not migrated or reset.
- Host/image regression suite: 145 passed, zero failures/skips; reviewed-context
  parsers 115 PHP / 44 JS. Final database assertions: 42 passed, zero failed.
  Fresh/repeated install, historical two-part fixture adoption and row preservation,
  locking, file/history/schema/ledger drift, post-DDL SQL failure recovery, running
  state recovery, marker/UUID/credential rejection, bound values, nullability,
  uniqueness and DML rollback verified on synthetic targets.
- Original PDO/MySQLi bootstraps verified when explicitly included; repeated PDO
  named parameters preserved. Two legacy runtime CREATE calls are observed
  compatibility failures under DML-only privileges. Source is unchanged; missing
  PDO route wiring/full account/catalogue/social schema remain blockers.
- Offline Node query-shape tests use a mock pool only. Actual mysql2 SQL/pool/
  delivery integration remains NOT VERIFIED; existing disabled adapter unchanged.
- Synthetic logical backup encrypted/decrypted in memory and restored to a
  different verified empty target matches structure/data hashes; restored ledger
  skips all four versions. No plaintext/key file or operational backup artifact.
- Paused-server rejection/recovery verified after bounding mysqlnd read timeout
  during the new migration connection's construction and restoring the prior
  process setting. Initial timeout and inherited Compose-label failures were
  investigated/corrected without weakening ownership guards or changing legacy PHP.
- Tiny-fixture EXPLAIN selects unique_movie_part, setting_key and idx_ip_action.
  No measured speed improvement or speculative index/FK/cascade is claimed.
- Current Docker context: 204 exact reviewed inputs; only four new SQL paths,
  CREATE-only shape and checksum verified. Public export policy unchanged.
  Gitleaks 8.30.1: 201 context text inputs passed. Updated actionlint passed;
  hosted GitHub Actions NOT RUN (no commit/push), shellcheck/pyflakes NOT RUN.
- Managed CI startup/HTTP checks retain live/dependencies 200 and ready 503.
  Its audit still shows DML-only account and zero application tables; normal
  shutdown preserves the volume. No real data/media/session or live provider used.
- Six database guides plus SCHEMA_EVIDENCE.json created; contribution, setup,
  architecture, security and decisions updated. All application/historical SQL/
  package/baseline/ZL-12 bytes preserved; exploratory PHP edits withdrawn and
  checked against starting hashes. Final preservation/expanded scan/link/teardown
  evidence is recorded in the contribution handoff.

Final checks: all 307 initial inputs remain; 289 byte-identical, 18 intended
infrastructure/documentation changes and 21 new files. Every initial application,
tracked historical SQL, baseline checksum and prior contribution-prefix byte is
preserved. Full parsers: 176 PHP / 45 JS, zero failures. All 84 inspected local
links, Markdown fences and whitespace checks pass. Expanded Gitleaks scan: 219
reviewed text inputs, exit 0. Final image: 204 files, no extra/missing paths or
SHA mismatches. Zero ZL-14 containers/networks and zero managed containers remain;
all three original environment volumes are retained. Scan scope excludes private
stores, Git history and binaries; no blanket secret/PII assurance is claimed.

Git metadata observation: HEAD/tree/local branches/tags and staged state remain
unchanged. During this session origin/main moved from 45b9309f4a25 to
94f7708be599, and origin/HEAD resolves to that new value. No fetch, remote update,
commit or ref-write command was issued by this task. The all-refs equality check
therefore does not pass; this external remote-tracking change is recorded and
left intact. It does not change the authoritative working-tree source baseline.

**Unresolved:** 24 missing complete schema definitions, conflicting current
token/message/view/routine contracts, runtime DDL coupling, missing PDO route
includes and full application/Node compatibility. No verified full historical/
production upgrade, canonical referential integrity or production backup/migration.
Full application readiness remains 503. ZL-14 is not complete; ZL-15 is unstarted.

Exact next recommended task: resume ZL-14 with independently approved sanitized
DDL, indexes/defaults/constraints/collation and migration history for each missing
contract, plus an owner-scoped PHP/runtime-DDL compatibility plan. The private
dump is not a fallback. ZL-15 remains a downstream task, not automatic continuation.
See [inventory](../database/SCHEMA_INVENTORY.md),
[migration guide](../database/MIGRATION_GUIDE.md) and
[execution results](../database/SCHEMA_TEST_RESULTS.md).

## Earlier recovery records

## Previous phase: ZL-13 (preserved record)

**Jira ZL-13 - Build reproducible local staging and CI environments: PARTIAL.**
2026-10-23 (Asia/Colombo). Owner-authorized local implementation only; no Git
staging/commits/push, production provisioning or automatic ZL-14 work.

- Continued from branch docs/zl-11-production-architecture at HEAD
  804ec5ab07503c3fb0c311b4349443b87184e28f with existing uncommitted ZL-12 changes:
  275 tracked paths plus seven existing untracked files, 282 protected inputs.
- Added two-service Docker Compose infrastructure, strict reviewed build context,
  digest-pinned runtimes, separate dev/staging/CI templates and generated ignored
  local credentials, safe helper commands, three minimal health probes, CLI DB
  audit, synthetic configuration/security tests and least-privilege pinned CI.
- Sole application edit: existing pure PHP configuration accepts DB_HOST=db only
  with RECOVERY_CONTAINER_MODE=local. Default loopback, recovery DB/user prefix
  and local URL/live-integration guards remain. No business behavior rewrite.
- Both container builds and all three local profile startups verified. Runtime
  ports are loopback-only; databases have distinct owned volumes and no published
  ports. MySQL is internal; web also has scoped ingress and CAN egress.
- Local host and each image profile: 109 PHP / 42 JS parsers pass; 125 synthetic
  tests pass with zero failures/skips. Images run PHP 8.2.35 and Node 24.22.1;
  host baseline PHP 8.2.12 / Node 24.13.1. MySQL image 8.4.13 selected/pinned.
  Broader preserved/new-source sweep: 170 PHP / 43 JS, zero syntax failures.
- Each profile returns live 200 / dependencies 200 / ready 503 schema_unverified.
  CSS 200 and seven sensitive/disabled paths 403. DB audits verify both drivers,
  DML-only app access and zero application tables. Wrong fictional credentials,
  DB stop/recovery and web restart verified without data deletion.
- Actual Docker matcher passes fictional .env/SQL/Git exclusion fixture; final
  CI image has exactly 190 reviewed files, no extra/missing inputs. Gitleaks
  8.30.1 scoped scan: 187 text files, exit 0, redacted/no findings. actionlint
  1.7.7 passes; official checksum verified; shellcheck/pyflakes NOT RUN.
  Final expanded scan adds changed documentation: 198 text inputs, exit 0.
  All 58 inspected local links, Markdown fences and diff whitespace pass.
- Final teardown stopped all three owned projects, retaining their DB volumes and
  ignored local configurations. No original data, SQL, sessions or media imported.
  Post-teardown inspection confirms zero owned containers and all three DB volumes.
- Added five docs/development guides; appended contribution/architecture/security
  and decision records; README links and Linux config line-ending rules updated.
  Existing ZL-12 canonical implementations/shims/tests/export policy and baseline
  checksum documents are preserved. Final preservation: 274/282 starting inputs
  byte-identical, eight intended changed paths, zero missing/unexpected changes.
  HEAD/tree/branch/all refs/index unchanged; prior contribution prefix retained.

**Blockers and limits:** No verified canonical application schema, fixture/reset
contract, full bootstrap/auth/session/catalogue/browser/player regression or
production readiness. ZL-14 remains unstarted. GitHub Actions is statically
validated and reproduced locally, but remote execution is PENDING (no push).
Provider npm installation/vulnerability audit, live integration tests, persistent
uploads, remote staging/TLS and production operations are NOT RUN. PHP 8.2 needs
an upgrade before security support ends 2026-12-31; apt resolution is not hermetic.
Web egress is possible. An initial broad-context build was aborted and corrected;
its local BuildKit cache was not inspected/pruned. Scanner scope excludes private
stores, history/binaries and cannot prove absence of every secret/PII pattern.

Observed failures and their corrections, commands, ownership/rollback rules and
evidence classifications are in
[ENVIRONMENT_OPERATIONS](../development/ENVIRONMENT_OPERATIONS.md).
Infrastructure/driver/HTTP observations are runtime verified only within the
tested local boundaries; pure regressions are test-covered and executed. No
claim of full application or external verification.

Exact next recommended owner-authorized task: **ZL-14 - Reconcile canonical SQL
schema and reliable database access**, preserving this working tree and reviewing
migrations/synthetic fixtures before extending readiness or stateful behavior.
Do not start it automatically.

## Earlier recovery records

## Previous phase: ZL-12 (preserved record)

**Jira ZL-12 — Refactor modules incrementally without route regressions: PARTIAL.**
2026-10-23 (Asia/Colombo). The owner authorized safe implementation and tests, but
explicitly prohibited commits/push/deployment and automatic ZL-13 work. This
supersedes earlier publication authorization for this new phase.

- Initial branch docs/zl-11-production-architecture; HEAD
  804ec5ab07503c3fb0c311b4349443b87184e28f; clean worktree, empty index, 275 tracked
  paths. Preserved current source rather than restoring ZIP/old checkout content.
- B1/M01/W24: disabled ads implementation now in apps/web/src/Views/Ads.php;
  includes/ads.php remains an anchored shim. Eight PHP consumers retain paths.
- B2/narrow B02/M16 slice: pure parser now in bot src/config/environment.js;
  config/environment.js remains a CommonJS shim. Entry/client/forwarder imports
  and runtime/private state are unchanged; full M16 remains High/HOLD.
- Both canonical implementations are byte-identical to their pre-refactor sources.
  Added 35 characterization, 11 export-dependency and seven boundary/rollback tests.
  With the existing 32 synthetic export cases: **85 passed, zero failures/skips**.
- Before edits: 35 leaf and 32 existing export tests passed. B1 reached 73 passes;
  B2 reached 78; final direct-module/rollback additions reached 85.
- Direct parsers include tracked/new code: **166 PHP / 40 JS/CJS**, zero failures.
  These are syntax results, not page/browser/database verification.
- Narrow exporter update reviews two exact canonical paths and enforces paired
  dependencies in planning, pre-write and manifest validation; version
  zl12-leaves-v1. No real export. Actual-source dry run: 190 eligible, four unchanged
  holds, no output created. Five synthetic ignore probes pass; ignores/templates
  are unchanged.
- All 275 original tracked paths remain; only two helper wrappers, the reviewed
  exporter and explicitly listed developer/architecture/security/recovery docs
  change. BASELINE/checksum, public handlers/assets, SQL, locks/manifests, guards
  and prior contribution prefix remain preserved. HEAD/tree/branch/all refs/index
  unchanged. Final bounded redacted scan and preservation totals are recorded in
  REFACTOR_EXECUTION and the appended contribution record.
- Rehearsed PHP and Node rollback/reapply in isolated fictional fixtures. No
  workspace rollback, source deletion, session/media migration or external contact.
- Checked 25 nonprivate tracked parent directories: no nested .git. Private
  preserved history and sensitive stores were not opened or moved.

Complete HTTP/Apache rewrite/access-denial, canonical DB/auth/session, browser/
player and service/worker compatibility remain BLOCKED/NOT TESTED. No known
introduced critical regression, but those important ZL-11 acceptance gates cannot
be claimed passed. No broad refactor, DB/schema repair, new feature/framework,
dependency installation, live bot/extractor/payment, Git staging/commit/branch/
push, deployment or paid operation occurred.

Current facts, before/after route matrix, test commands, rollback and deferred
work are in docs/architecture/REFACTOR_EXECUTION.md. CURRENT_ARCHITECTURE reflects
only the two implemented boundaries; TARGET remains planned.
Exact next recommended task: **ZL-13 — Build reproducible local staging and CI
environments**, to enable further route-sensitive verification. ZL-14 owns canonical
DB reconciliation and ZL-31 the broader regression gates. Dependencies are proposed;
none of those issues is executed automatically.

Final checks: 266 original tracked paths byte-identical; nine intended changes,
both original helper implementations retained exactly; all 48 local doc links
and whitespace/privacy checks pass. Gitleaks 8.30.1 scanned 16 changed/new artifacts
with full redaction: exit 0, no findings/reports. No historical/private scan claimed.

## Prior ZL-9/ZL-11 review publication handoff (historical)

Owner-authorized ZL-9 clarification / ZL-11 review publication, 2026-10-23.
Architecture planning is COMPLETE within static documentation scope; production
readiness remains NOT VERIFIED. The owner's latest request explicitly authorizes
staging, logical commits, new review branches and PR publication for the existing
documentation. Earlier no-commit statements describe the audit phases.

- Verified GitHub PRs #10 and #11 are merged. Refreshed origin/main is
  45b9309f4a25a4d6a8646509a8c15f9221b28043, whose tree matches the audit base.
  The local main branch is preserved; these changes are on new review branches.
- [PR #12](https://github.com/gayashan-indrasankha/Zinema.lk/pull/12):
  docs/zl-9-authority-clarification -> main, two documentation commits ending at
  087b9d3b3f009ce5d820f316b80d0d868802d7c0.
- [PR #13](https://github.com/gayashan-indrasankha/Zinema.lk/pull/13):
  docs/zl-11-production-architecture -> main, ten focused architecture commits
  plus this contribution/recovery handoff: eleven ZL-11 commits.
- Thirteen unique new Conventional Commits across both branches. PR #13 shares
  #12's two ancestor commits until #12 merges. Merge #12 first and then #13 using
  **Create a merge commit** to retain all thirteen; both already target main.
- PR #12 was opened ready. PR #13 was initially draft while this final handoff
  was prepared; its final push/readiness and remote SHA agreement are verified
  after this commit and reported to the owner. No PR is merged here.
- Explicit paths were staged per document/responsibility. The ZL-9 contribution
  prefix was staged directly through the index without altering the complete
  working file. No blanket staging, amended history or fabricated authorship.
- The ten architecture documents cover 265 original tracked paths in 51 groups:
  KEEP 10, REPAIR 22, ISOLATE 12, ARCHIVE CANDIDATE 2, UNRESOLVED 5; 24 production
  gap categories, eight journeys, 29 source debt items and the owner-supplied
  twelve-epic/43-story Jira map. Inferred sequencing is PROPOSED DEPENDENCY.
- Before every commit: status and working/staged changes reviewed; document/link/
  count/privacy checks and staged whitespace passed; Gitleaks 8.30.1 scanned the
  exact index text blobs with full redaction. Three existing binaries were
  excluded from text scans and checked unchanged. Five synthetic sensitive-path
  ignore probes passed. No secret/data/session/media files were staged.
- PHP syntax: 164 files; JavaScript syntax: 36 files; zero failures. Executed once
  for this documentation publication; unchanged source hashes justify reuse for
  later documentation-only commits. No application functional/runtime test.
- Complete outgoing-history scans passed before the initial branch pushes.
  The final handoff is scanned before commit, and the entire resulting outgoing
  history is checked again before the final push.
- All 275 original tracked/current-architecture snapshot paths are accounted for.
  The 272 paths outside the contribution and two recovery records remain
  byte-identical, including source, baseline clarification, checksum manifest,
  dependency files, examples/binaries and architecture documents. The complete
  previous contribution prefix is retained; recovery history is preserved below.
  Final clean status and remote agreement are reported after commit/push.

Security limitations: bounded staged/outgoing scans are not a complete historical
secret, privacy, vulnerability or rights audit. Existing export holds remain.
Canonical schema, authentication/admin/upload safety, licensed rights and private
HLS/CDN, reproducible staging/tests, operations/restore and release gates remain
future work. WhatsApp delivery is CONDITIONAL; AI/voice are proposals. Vendor
facts/prices require current verification. No live service, database, account,
deployment, paid operation, real export, Git reset/rewrite or ZL-12 work occurred.

Current architectural decisions are in docs/architecture and the appended
contribution log. Exact next recommended Codex task:
**ZL-12 — Refactor modules incrementally without route regressions**, starting with
current-state reconciliation and one covered leaf. Missing environment/regression
prerequisites map to ZL-13/31; do not move affected behavior before those gates.

## Prior ZL-9/ZL-10 publication handoff (historical)

Owner-authorized ZL-9/ZL-10 review publication: local preparation COMPLETE;
branches pushed and both review PRs opened. The owner explicitly requested
incremental staging/commits and GitHub PRs after the ZL-10 preparation handoff.
That later request authorizes publishing these reviewed changes to the existing
repository; it does not authorize a real project export, PR merge or deployment.

- [PR #10](https://github.com/gayashan-indrasankha/Zinema.lk/pull/10):
  `docs/zl-9-baseline-provenance` -> `main`; two baseline/provenance commits,
  ending at `76ec6df6ef75443f76db963d7ec47a7ab7b63248`.
- [PR #11](https://github.com/gayashan-indrasankha/Zinema.lk/pull/11):
  `feat/zl-10-safe-public-export` -> `main`; six ZL-10 commits, including this
  final contribution/recovery handoff, follow the two ZL-9 ancestor commits.
  Eight new Conventional Commits in total, without fabricated development dates.
- Merge #10 first, then #11, using **Create a merge commit** for both to retain
  the incremental history. Both target main; no retargeting is needed. Until #10
  merges, #11 also displays the shared ZL-9 commits. No PR is merged here.
- Exact paths/content were staged incrementally. The original ZL-9 contribution
  prefix was staged through the index without changing the complete working
  file; subsequent ZL-10 content is included in this final commit. Baseline and
  checksum files remain byte-identical; original contribution history retained.
- Before each commit: inspected status and working/staged diffs; checked staged
  whitespace; Gitleaks 8.30.1 scanned exact staged text blobs with full redaction;
  local syntax checks passed. The three existing logo/icon binaries were excluded
  from text scanning and unchanged. No secret/data/session/media paths added.
- Executed syntax checks with the available local PHP executable: 164 PHP and,
  after staging both new tools, 36 JavaScript/CJS files; zero failures. All 32
  synthetic export tests passed with zero failures/skips/cancellations. All 40
  targeted ignore assertions and 265 required-path checks passed; four example
  gates passed. Actual-source default dry run: 188 eligible files, four holds,
  no output created. No functional application test or real export was executed.
- Gitleaks outgoing-history scans passed for both branch ranges before initial
  push. The final handoff commit is checked before its commit and the complete
  outgoing history is checked again before the final push. This is a bounded
  outgoing-change scan, not a full historical/privacy/vulnerability assessment.
- Local and remote main started at
  `7e8d325c4fc206c3a2664652de99e560bae44795` and are not advanced by this work.
  Existing branches/history and private assets are retained. No reset, stash,
  amend, history rewrite, force-push, production contact or account operation.
- PR #11 was initially opened as a draft while this last record was prepared.
  Final push, ready-for-review transition, remote head agreement, clean worktree
  and last commit SHA are verified and reported at handoff after this commit.

Evidence: executed local checks above and externally verified repository/main,
branch publication and PR creation. Application logic, SQL, dependencies and
assets remain unchanged. Existing risks and four export holds remain; no blanket
security or runtime-readiness claim. ZL-11 has not started.

Exact next recommended Codex task: **Jira ZL-11 - Map modules and classify keep,
repair, isolate, archive**, after owner review of these PRs. Further real export,
credential/account changes, destructive cleanup, deployment and production access
need their own explicit authorization.

## Prior ZL-10 preparation handoff (historical)

Jira ZL-10 - Sanitize sensitive files and define safe public export: COMPLETE
within local preparation and synthetic-validation scope. This record supersedes
the next-task recommendations in the historical sections below. ZL-11 has not
started. A real project export and publication still require explicit approval.

- Authoritative input: current extracted working tree, including uncommitted
  content. No ZIP was used. Starting main HEAD was
  `7e8d325c4fc206c3a2664652de99e560bae44795`, tree
  `7e01e85cc90bbd838144d5b9bddd01122036eea9`, with 259 tracked files and 32
  reachable commits. The index was empty. Three untracked ZL-9 documents already
  existed; they are prior work, not new ZL-10 implementation.
- All 176 eligible ZL-9 source checksums matched before changes. The baseline
  document and checksum manifest remain unchanged. The contribution log is
  extended without changing its previous content. ZL-10 intentionally changes
  three original manifest inputs: root `.gitignore`, this status record and
  `DECISIONS.md`. These workflow/documentation deltas do not replace the baseline.
- Extended root ignore rules; preserved nested rules and all component examples.
  Appended clearly FUTURE / NOT IMPLEMENTED root example settings. Added
  `scripts/prepare-public-export.cjs`, `scripts/test-public-export.cjs` and
  `docs/SECURITY_EXPORT.md`; appended `docs/CONTRIBUTIONS.md` and recovery records.
- The standard-library Node tool uses an explicit path allowlist and content
  gates, defaults to a no-write dry run, rejects unsafe/existing destinations,
  and separately validates hashes/manifests. No source normalization or repair.
- Executed new-script syntax checks and 32 synthetic tests: all passed, zero
  failed/skipped/cancelled. Fixtures exercised actual Windows junctions,
  hardlinks, traversal, sensitive-file non-reading, tampering and SQL policy.
  Ignore checks passed 21 sensitive-path and 19 required-file assertions. All
  four example templates passed their content gate. Actual-source dry run
  created no output directory; final counts are in `../SECURITY_EXPORT.md`.
- **Statically inspected:** safe templates, ignore rules and export policy.
  **Test-covered and executed:** export/validation behavior in synthetic local
  fixtures. **Runtime verified:** only the new export utility, not application
  features. **Externally verified:** none in ZL-10. No app, bot, payment,
  extractor, database or production service was started/contacted.
- Security inventory used metadata only for real environments, sessions, logs,
  dumps and private recovery assets. Redacted local first-party checks held
  three tracked files for local-path patterns and one SQL migration outside the
  conservative schema-only grammar. See SECURITY_EXPORT for sanitized paths.
  Originals, 70 bundled-library files and binary assets remain untouched.
- New-artifact secret-scan results and preservation checks are recorded in
  SECURITY_EXPORT and the contribution entry. No historical secret scan or full
  vulnerability/privacy audit is claimed. Private-content preservation is
  metadata/no-write evidence, not a claim that its contents were inspected.
- Git history/HEAD/index preserved; no staging, commits, resets, stash, push,
  real project export, account changes or publishing. Changes remain reviewable
  and uncommitted. Existing application PHP/JS/SQL, dependencies and assets are
  unchanged; application regression/runtime tests were not rerun for this scope.

Remaining risks: bounded content heuristics can miss secrets; held candidates
need review, dependency/media rights need review, and historical exposure remains
unassessed. Obtain separate authorization for a real export, publication,
credential/session changes or destructive cleanup. No runtime readiness claim.

Exact next recommended Codex task: **Jira ZL-11 - Map modules and classify keep,
repair, isolate, archive.** Continue from current working-tree evidence and
existing recovery records; do not infer deletion authority from an export hold.

## Prior integration handoff (historical)

Owner-authorized integration into main: COMPLETE for Git integration. On
2026-10-23 the owner explicitly requested completing the final merge. PR #8
merged the complete reviewed project from `feat/whatsapp-delivery` into main
using a merge commit, preserving all 26 original incremental commits.

- Integration PR: https://github.com/gayashan-indrasankha/Zinema.lk/pull/8
- Main integration commit: `626e35110f2acb89391c56831daf2712ee9be08b`.
- GitHub reported the PR mergeable with a clean merge state; expected head SHA
  was supplied to the merge operation. No branch protection was bypassed.
- Local main was fast-forwarded to that merge. It contained 30 commits and its
  tree `499372d1e7a41125962eb2d1d8412a2719e88ac7` exactly matched the reviewed
  project tree. Every one of the original 26 commit SHAs passed an ancestry check.
- Re-executed PHP 8.2.12 lint on 164 files and Node 24.13.1 syntax checks on 34
  JavaScript/CJS files: zero failures. Gitleaks 8.30.1 scanned the 23 remaining
  non-merge source commits before integration: no findings. The original three
  foundation commits were already on main and had passed the publication checks.
- This follow-up changes only AGENTS.md, STATUS.md, DECISIONS.md and PUBLISHING.md
  to record authorization, integration and verification. Its explicit staged
  paths, diff and secret scan are checked before commit; no application code is
  changed. The record is integrated through a separate documentation-only PR.
- Evidence: executed syntax/security/ancestry checks and externally verified
  GitHub merge. No application behavior or production service was runtime tested.
- Security limits and the 1,798 inherited whitespace findings below remain.
  The earlier source-publication quality status remains PARTIAL; completing a Git
  merge does not establish runtime, schema or security readiness.

Final documentation commit/merge hashes, clean local status and remote agreement
are reported at handoff and recorded in ignored local verification evidence.
Existing feature branches and private preserved evidence are retained. No
credentials were rotated, production service contacted or deployment performed.

Exact next recommended Codex task:

"Perform a read-only reconciliation of Zinema's existing recovery changes and SQL
generations. Produce a canonical schema proposal, route/bootstrap map and
prioritized defect list with source evidence. Include a behavior-safe proposal
for the retained whitespace findings. Do not import data, start live integrations
or change application behavior."

## Prior publication handoff (historical)

Owner-requested GitHub source-publication phase: PARTIAL. The requested seven
branches and seven open, non-draft PRs are published for owner review. The final
handoff brings the new Conventional Commit count to 26. Syntax and secret scans
passed; the inherited whitespace-check findings are retained and unresolved.
Application recovery, schema compatibility and runtime readiness are unverified.
No historical numbered phase is assumed complete. No PR was merged or deployed.

### Completed publication work and files

- Added exclusions, line-ending rules, contribution/PR guidance and a source
  syntax script; expanded the root README and recovery records.
- Imported 259 tracked files across shared website foundations, accounts/Shots,
  catalogue/playback, administration, delivery and extraction. Preserve the
  existing source layout; detailed commits/PRs are indexed in `PUBLISHING.md`.
- Removed embedded contact numbers and a roadmap contact address; disabled empty
  support destinations. Replaced sample token/message identifiers, corrected
  example local paths and labeled historical READMEs. Originals are preserved
  outside Git. No credentials were rotated or live functionality enabled.
- Preserved the modified extractor repository's metadata locally before importing
  its reviewed current files. Its previous history is not part of this publication.
- Corrected contributor wording to describe optional strict PHP checking without
  implying a CI workflow exists. CI and dependency auditing remain future work.

### Executed verification and evidence

- **Statically inspected:** explicit staging manifests/diff summaries and filtered
  configuration/contact findings before every commit. Not an exhaustive security
  or line-by-line review of every legacy feature.
- **Executed syntax checks:** Node 24.13.1 `--check`, 34 JavaScript/CJS files,
  zero failures; PHP 8.2.12 `-n -l`, 164 PHP files, zero failures. Checked each
  cumulative tracked snapshot before committing. No application modules executed.
- **Executed data-format checks:** all six tracked JSON files parsed successfully.
- **Executed security checks:** Gitleaks 8.30.1 on every exact staged snapshot and
  the complete 24-commit outgoing source/workflow history before initial push;
  zero remaining findings. The handoff snapshot is checked before its commit;
  outgoing history is scanned again before its push. Scanner archive SHA-256
  matched the official release metadata. Default rules used; no finding allowlist.
- Supplemental staged checks rejected private paths, Git links, numeric contact/
  group identifiers and invitation links. Checked credential-bearing URL patterns:
  no findings. Verified ignore rules for environments, dump, sessions, exports and
  private preserved metadata. Only product logo/icon binaries were imported;
  no movie, episode, user-upload or demonstration media was added.
- **Whitespace check: NON-CLEAN.** `git diff --cached --check` reported 1,798
  inherited trailing-whitespace/EOF warnings across the 24 import commits. No
  conflict-marker finding. Source whitespace was preserved to avoid changing
  legacy string/template content without behavior tests. This unresolved check
  is why the phase remains PARTIAL; it is not reported as a pass.
- **Test-covered:** feature regression coverage not established; no functional
  test suite executed. **Runtime verified:** no application behavior.
  **Externally verified:** GitHub repository metadata, successful branch push and
  creation of seven open PRs. No database, WhatsApp, email, payment, production
  infrastructure or media source was contacted.
- Limitations: scanners can miss secrets; no dependency vulnerability, full
  security, browser, performance, database compatibility or media-rights audit
  completed. Source syntax does not establish a secure or runnable application.

### Git and handoff

Starting root commit: `e2c239d7e0fc608d96e2623227a727ba3286b64d` on main.
Source-import boundary: `375a989aa413a02cf4d909b5fab8ec6576c09950`.
All seven source branches were pushed successfully with an atomic push. The
handoff commit remains on `feat/video-extractor`, updating PR #7. Its exact hash,
final clean-status check and remote-state verification are reported at handoff;
the private local execution ledger also records each commit and check.

Review [PR #1](https://github.com/gayashan-indrasankha/Zinema.lk/pull/1) through [PR #7](https://github.com/gayashan-indrasankha/Zinema.lk/pull/7) in the order in
`PUBLISHING.md`. Use merge commits to retain history and verify/retarget each
successor after merging its base. No merges were performed. Ignored legacy data,
originals and scanner reports remain only on the owner's machine.

### Unresolved risks and next recommended task

Inherited whitespace findings remain unresolved. Existing runtime/schema/security
risks from the discovery below remain. Do not enable live integrations or treat
historical SQL as setup instructions. Publishing source is not production approval.

Exact next recommended Codex task after owner PR review:

"Perform a read-only reconciliation of Zinema's existing recovery changes and SQL
generations. Produce a canonical schema proposal, route/bootstrap map and
prioritized defect list with source evidence. Include a behavior-safe proposal
for the retained whitespace findings. Do not import data, start live integrations
or change application behavior."

## Previous completed phase

Owner-requested project-understanding and description phase: COMPLETE within
static-discovery scope. Application recovery, remediation and runtime readiness
are NOT complete. The following historical checks are not current publication
verification; the final publication record will state fresh results.

## Starting state

- Workspace root is not a Git repository; failed root status was not treated as clean.
- `docs/recovery/STATUS.md` and `DECISIONS.md` did not exist when checked.
- Nested bot repository status initially failed Git's ownership check. A command-scoped `safe.directory` exception allowed read-only status; no global Git configuration was changed.
- Existing bot modifications: `.env.example`, `.gitignore`, `config/api-client.js`, `config/database.js`, `config/file-manager.js`, `config/multi-tier-forwarder.js`, `index.js`; untracked `config/environment.js`. Preserved without staging or changes.
- Existing source already includes recovery guards. Their origin and historical phase completion have not been established.

## Completed work and changed files

- Traced the principal website, account, Shots, catalogue, playback, administration, bot and extractor paths.
- Inspected dependency manifests and SQL structure without importing SQL or displaying data rows.
- Added `PROJECT_DESCRIPTION.md`, this status record and `DECISIONS.md`. No application source changed.

## Checks and evidence

- Git status: root not a repository; nested bot changes recorded above. No commit created; commit hash not applicable.
- Static source inspection: completed for the main subsystems and selected implementation paths; not an exhaustive line-by-line audit.
- JavaScript syntax: `node --check`, 31 selected standalone files, 31 passed, zero failures. Excluded dependencies, minified files, historical bot variants, scheduler sample and `refresh-method-new.js`.
- PHP lint/runtime: not performed; PHP unavailable on PATH.
- Test-covered: feature test coverage not established. No application regression suite executed.
- Runtime verified / externally verified: no application features. No services started, database operations or external connections performed.
- Security: used filtered reads, omitted sensitive contexts, avoided session/environment/log dumps and SQL data. gitleaks/trufflehog unavailable on PATH; no dedicated secret scan passed or claimed. Not a comprehensive vulnerability or privacy audit.

## Unresolved risks and blockers

- Inconsistent PDO/MySQLi bootstrap paths and code/schema mismatches need reconciliation.
- Existing quarantines interrupt download/payment flows; WhatsApp and extractor recovery gates alter legacy behavior.
- Sensitive legacy configuration, session directories and logs exist; do not publish without a later authorized sanitization and review.
- Existing application changes lack corresponding recovery records. Root version history is absent.
- No isolated runtime/database fixture has been established or tested in this phase.

## Next recommended phase

Read-only recovery-state and canonical-schema reconciliation. Exact next owner task is recorded in `PROJECT_DESCRIPTION.md`. Do not proceed automatically into remediation, database reconstruction or publishing.

## Final ZL-15 verification handoff

Final ZL-15 verification: all 328 initial eligible files remain (312 byte-identical,
16 reviewed tooling/documentation changes), plus 24 new files; no unexpected change
or deletion. All website/bot/extractor source, historical SQL, four original SQL
migrations/manifest, dependency assets and baseline records are byte-preserved.
The entire previous contribution record is an exact prefix. HEAD remains
958dd9eaf97c72ea6e76648eae6814d775f03641; branch/local heads/tags are unchanged and
index is empty. Changes remain unstaged/uncommitted; no Git publishing occurred.

Final host and image suites pass 246 tests and 128 PHP/46 JS parsers; full eligible
source parsing passes 189 PHP/47 JS. Rights DB results: 13 migration +19 service
assertions, one winner/one rejected stale editor in actual concurrent processes,
and excessive-privilege refusal. Original 42 DB assertions and synthetic encrypted
backup/outage checks pass. Image audit: exactly 222 paths, no extra/missing/hash
mismatch. Expanded Gitleaks 8.30.1 scan: 233 reviewed text inputs, exit 0, no findings;
private legacy stores/history/binaries excluded. 90 inspected local links, Markdown
fences/new-text checks and git diff --check pass. Actionlint passes; shellcheck/
pyflakes and hosted execution of these changes are NOT RUN. Unconfigured content
migration CLI returns MIGRATION_TARGET_REFUSED before a connection attempt.

Local HTTP probes for new Content/bootstrap.php, content-migrations.json and content
test driver paths all return 404 under the website document root. Managed CI
connectivity/audit still shows zero app tables and readiness 503. Normal shutdown
completes: zero ZL-15 containers/networks, zero managed containers, all three original
managed database volumes retained. No production, real users/media/agreements,
WhatsApp, extractor, paid AI, private dump or ZL-16 work. ZL-15 remains PARTIAL.

## Final ZL-16 handoff verification

All 352 starting eligible files remain: 340 byte-identical and 12 reviewed shared
CI/tooling/documentation updates; 22 new files (374 total). No unexpected source
change or deletion. All pre-existing website/bot/extractor/Content module bytes,
SQL files/manifests, dependency/baseline evidence and previous contribution prefix
are preserved. Git HEAD/tree/branch/local heads/tags unchanged, empty index;
62 current dirty paths include the 40 pre-existing ZL-15 paths. No Git writes.

Final Gitleaks 8.30.1 expanded scan: 279 reviewed text inputs, exit 0, no findings;
private stores/history/binaries excluded. Context scan separately passed 234 text
inputs. Actionlint passes (shellcheck/pyflakes NOT RUN). 84 local links checked,
none broken, Markdown fences balanced and git diff --check passes (existing
LF-to-CRLF advisory only). No current hosted CI execution was performed.

Final host/image suites: 332 passes, no failure/skip; context syntax 141 PHP/48 JS.
Full eligible syntax: 202 PHP/49 JS. Catalogue SQL prerequisites 13 plus 24 checks,
reader/excess-privilege/outage gates pass. Prior content/database regression suites
pass as listed above. Final image exactly 237 matching host paths/hashes.

Managed CI audit: least privilege verified, zero application tables, schemaReady
false; HTTP live/dependency/ready 200/200/503. Owned CI services stopped normally,
all three existing managed database volumes retained. No ZL-14/ZL-15/ZL-16
fixture containers/networks remain; no managed ZL-13 containers remain. No private
SQL, production, real users/media/licences, messaging/extractor/paid AI/cloud,
commit/push or ZL-17 work occurred. ZL-16 remains PARTIAL.

## Final ZL-17 handoff verification

All 374 starting eligible files remain: 358 byte-identical, 16 authorized reviewed
application/tooling/documentation updates, plus 35 new files (409 total). No
unexpected change/deletion. Earlier Content/Catalogue implementations, all SQL/
manifests, bots/extractor, baseline/dependency evidence and prior contribution
prefix are byte-preserved. Only index Shot actions, movie Watch/player removal
and additive movie/series view gates change existing application source. Git
HEAD/tree/branch/local heads/tags remain unchanged, index empty; 101 current dirty
paths include all 62 pre-existing paths. No staging, commit, push or history writes.

Host/image full suite passes 388 tests, zero failure/skip; 156 PHP/56 JS context
parsers. Full eligible syntax passes 217 PHP/57 JS. Actual browser/protocol suite
passes 49 assertions with generated original HLS; native desktop Chrome plus
forced-MSE mobile viewport, not Safari/physical-device certification. Last startup
samples 4,647/5,903 ms, no production SLA claim. Image exactly 265 host-matching
paths/hashes. Context Gitleaks 262 and expanded 314 text inputs pass; private stores,
history/generated media/binaries excluded. npm audit zero reported advisories;
actionlint passes, shellcheck/pyflakes NOT RUN, hosted CI NOT VERIFIED. 102 local
links resolve (including URL-encoded spaces), fences balanced, git diff --check
passes with only the existing line-ending advisory.

Managed CI audit retains zero application tables and ready 503; normal shutdown
completed, zero managed containers and all three original database volumes
retained. Temporary generator containers used --rm; loopback browser servers were
stopped by ownership-scoped cleanup. No ZL-17 fixture container/network remains.
No production/private SQL/real media/users/licences, live WhatsApp/extractor,
payment/AI/cloud, publishing or ZL-18 work. ZL-17 remains PARTIAL.

## Final ZL-18 handoff verification

All 409 initial eligible inputs remain: 342 byte-identical, 67 reviewed authorized
source/tooling/documentation updates, plus 22 new files (431 total), no deletion.
Content/Catalogue/Playback, HLS/progress source, SQL/migration manifests and baseline
evidence are unchanged. Ten historical Node implementation bodies are identical
to initial bytes after removing only guard additions. Previous contribution history
is an exact prefix. Git HEAD/tree/branch/local heads/tags unchanged; index empty;
176 current dirty paths include all prior 101. No staging/commit/push/history write.

Final host/image full suite 524 passes, no failure/skip; context syntax 161 PHP/63 JS,
full eligible syntax 222 PHP/64 JS. Read-only network-none quarantine run 134 passes,
no host/session mounts. Actual unavailable-UI 8 and unchanged HLS 49 browser checks
pass with zero external operations. Final image exactly 279 reviewed host-matching
paths/hashes, no extras/missing/symlinks. Expanded Gitleaks passes 338 reviewed
text/JSON inputs; excluded private stores/history/generated media/binaries are not
certified. Actionlint passes; shellcheck/pyflakes NOT RUN; hosted CI NOT VERIFIED.
113 local links resolve, Markdown fences balanced and git diff --check passes
(existing line-ending advisory only). The flagged trailing space was corrected.

Managed CI audit retains zero app tables and readiness 503; owned services stopped
normally, zero managed containers, all three existing database volumes retained.
Network-none verification containers used --rm; temporary HTTP/browser servers
closed through owned cleanup. No ZL-18 fixture container/network remains. No real
session, payment, WhatsApp message, extraction, private SQL, production operation,
real media/recipient, credential rotation, deploy or ZL-19 work occurred.
ZL-18 remains PARTIAL for the explicit private/deployed and full-integration gaps.

## Final ZL-19 handoff verification

All 431 starting eligible files remain: 417 byte-identical, 14 authorized changes,
30 new files (461 total), no unexpected change/deletion. Content/Catalogue/Playback,
all SQL/manifests and baseline source evidence are unchanged. Previous contribution
history remains an exact prefix. HEAD/tree/branch/local heads/tags unchanged;
empty index, 209 dirty paths including earlier work. No Git writes.

Host/image full suite 566 passes, zero failure/skip; full eligible syntax 235 PHP/
69 JS. Network-none read-only 176, identity browser 18, original HLS 49, quarantine
UI 8 pass; no provider/external request. Image 297 exact reviewed path hashes.
Expanded redacted Gitleaks scan passes 364 reviewed text inputs; private stores/
history/binaries/generated media excluded. Actionlint passes; shellcheck/pyflakes,
fresh package advisory audit and hosted CI NOT RUN. 136 local links resolve,
fences balanced, git diff --check passes with existing line-ending advisory.

Managed CI read-only audit: least privilege, zero app tables, schemaReady false.
Final resource inventory: zero managed/fixture containers; all three existing
managed database volumes retained after normal shutdown. Audit/down must follow network-none fixture exit because the image
inherits Compose project labels without runtime service ownership labels; the
initial guard refusals were not bypassed. No account migration, private SQL, real
account/session/password/MFA/mail/provider operation, commit/push or ZL-20.
ZL-19 remains PARTIAL; production schema, shared stores, MFA/owner recovery, TLS/
mail, comprehensive legacy guards/uploads and rollout approval remain blockers.
