# Recovery status

Updated: 2026-10-23.

## Current publication phase

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
