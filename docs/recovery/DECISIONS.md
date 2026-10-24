# Recovery decisions

## 2026-10-23 — ZL-12: refactor only tested dependency-free leaves

Context: The owner requested actual safe implementation of the ZL-11 plan while
prohibiting Git commits/push and later tasks. Starting HEAD
804ec5ab07503c3fb0c311b4349443b87184e28f on docs/zl-11-production-architecture had
a clean worktree/empty index. Canonical database and complete HTTP/browser fixtures
are still unavailable; broad bootstrap/page/worker movement would be unverified.

Decision: Implement M01/W24 disabled ads behind its existing PHP include and only
the side-effect-free B02 parser portion of M16 behind its existing CommonJS import.
Retain exact original implementation bytes in canonical files, explicit relative
shims and all current callers. Full M16 entry/package/scheduler/state movement and
all high-risk route/DB/security changes stay on HOLD. No class/framework conversion.

Rationale: Both modules can be characterized and executed without application
bootstrap, database, real environment files, live accounts or worker timers.
Thirty-five baseline contracts passed before source edits; 32 original export
tests also passed. Preserve false/empty ad behavior and pure config validation,
not merely parser acceptance.

Packaging consequence: A relocated helper omitted by the exporter would break a
candidate. Approve exactly the two reviewed canonical text paths, retain every
independent sensitive exclusion and bump to zl12-leaves-v1. Enforce exact shim/
implementation dependencies in plan/export/manifest validation; do not approve
whole app/test/document trees. Four existing holds remain; no real export.

Verification: B1 baseline plus new paired-export checks reached 73 passing tests;
B2 reached 78; direct modules, three consumer import contexts and isolated rollback/
reapply checks brought final total to 85, zero failed/skipped. Direct syntax:
166 PHP / 40 JS/CJS, zero failures. Current-source no-write plan: 190 eligible,
four unchanged holds, no output. SHA-256 proves both canonical files retain initial
bytes; unchanged public handler/asset/guard/schema/config-root contracts are static
evidence, not runtime page or production assertions.

Status: PARTIAL for ZL-12 overall, with both selected low-risk batches functionally
verified. Full HTTP/Apache/browser/schema acceptance is not available; next proposal
is ZL-13 environment work, then relevant ZL-14/31 gates. No next issue executed.
Rollback is a guarded current-session copyback/import correction, rehearsed only
in isolated synthetic fixtures; preserve later owner edits and never reset Git,
restore an archive, delete originals or mutate private runtime state.
HEAD/refs/index remain untouched; contribution history is append-only.
Security/export limitations and exact files/commands are in REFACTOR_EXECUTION.md.

## 2026-10-23 — Publish ZL-9 clarification and ZL-11 architecture for review

Context: The owner requested incremental staging/Conventional Commits, review
branches and ready PRs for the existing unstaged documentation. This supersedes
the earlier audit-only no-commit restriction for this publication follow-up.
GitHub confirms PRs #10/#11 merged; current origin/main is 45b9309f4a25a4d6a8646509a8c15f9221b28043
with the same tree as the audit base. Existing source and uncommitted document
bytes must be preserved.

Decision: Create docs/zl-9-authority-clarification from current origin/main, with
two commits for BASELINE and the matching contribution prefix. Create
docs/zl-11-production-architecture on those commits, with ten substantial document
commits and one final contribution/recovery handoff. Stage exact paths; stage the
ZL-9 prefix through the index while retaining the entire working file. Use docs
commit types because this work does not implement application features.

Rationale: The authority clarification and architecture blueprint are distinct
reviews. One commit per substantial architecture document gives useful review
boundaries without manufacturing tiny edits or claiming historical development.
Both PRs target main; #12 merges before #13 so the latter's shared two ancestor
commits disappear from its diff automatically. Merge commits preserve all thirteen
unique new commits without retargeting or squash reconciliation.

Consequences: PR #12 ends at 087b9d3b3f009ce5d820f316b80d0d868802d7c0.
PR #13 initially contains the ten architecture commits through
fc0aca1e8ddfcac1be31905dc52a9e9332ed73c2, then this final handoff. The final SHA is
reported after commit/push. It was opened draft until final checks; readiness is
verified before handoff. PRs remain unmerged. Local/remote main are not advanced.
No ZL-12 code, new framework, schema repair, live integration or deployment follows
from permission to publish documentation.

Verification: Reviewed .gitignore and exact staged/working paths/content before
each commit; redacted Gitleaks scans of exact index text, document checks, staged
whitespace and five sensitive ignore probes passed. PHP 164 / JS 36 syntax checks
passed once; source remains identical throughout later documentation-only commits.
Scanned complete outgoing history before initial pushes and repeat for the final
handoff. Snapshot checks retain all source/examples/binaries, checksum/BASELINE and
ten architecture document bytes; contribution history is a preserved prefix.
The recovery records retain their prior content as historical entries.
Final remote SHA/readiness, source preservation and clean worktree are checked
after the final push. These are publication/static checks, not runtime/security/
legal/production certification. Existing export holds and architecture risks remain.

## 2026-10-23 - Publish ZL-9/ZL-10 as incremental review PRs to main

Context: After the local ZL-10 handoff, the owner explicitly requested staging
existing changes, logical Conventional Commits, new branches and open review PRs.
This supplies publishing authorization for the reviewed changes to the existing
repository. Earlier no-commit/no-push statements describe the previous phases;
they do not require asking again for this specifically requested follow-up.

Decision: Preserve original application/data/history and baseline bytes. Create
two milestone branches and eight coherent commits: two ZL-9 audit/provenance
commits, then six ZL-10 ignore/example/tool/test/security/handoff commits. Stage
the original contribution prefix independently through the index, preserving the
complete working file. Keep original development dates/authorship unverified.

Rationale: These two Jira scopes are independently reviewable. Additional
arbitrary branches or tiny edits would not improve review quality. Both PRs
target main so owner merges integrate the commits into main. The ZL-10 branch
shares the ZL-9 ancestors; merge the baseline PR first with a merge commit, then
the export PR with a merge commit, retaining all eight Conventional Commits.

Consequences: PR #10 is the baseline/provenance review; PR #11 is the export
review. The second PR initially includes the shared baseline diff until #10
merges. No PR is merged automatically and main is not advanced. No source repair,
real project export, credential change, production access or deployment follows
from permission to publish these reviewed changes. ZL-11 remains unstarted.

Verification: Exact staged-tree secret scans and whitespace checks passed before
commits; executed syntax checks covered 164 PHP and up to 36 JavaScript files.
The 32 synthetic export tests, 40 targeted ignore checks, 265 required-path checks
and four template gates passed. Default real-source dry run created no output.
Outgoing history is scanned before each push; final upstream/readiness/clean
status and final SHA are reported at handoff. Original baseline artifacts and
contribution prefix are preserved; recovery/security guidance adds this follow-up.

## 2026-10-23 - ZL-10: prepare an isolated export without changing legacy source

Context: The owner authorized sensitive-category inventory, ignore/example
protection, a conservative local exporter, synthetic tests and documentation.
The current extracted working tree is authoritative, including uncommitted ZL-9
records. Real secret/data stores must not be read and a real project export,
publishing, source repairs and destructive cleanup are outside this task.

Decision: Preserve application/data/history bytes. Extend root ignore rules,
retain all existing examples and append explicitly unimplemented future root
settings. Use a static reviewed path allowlist plus independent sensitive-path,
UTF-8/JSON/example/SQL/content gates. Copy only approved current working-tree
bytes after an explicit export action, to an absent external local directory;
default to no-write dry run. Validate manifest membership, counts and hashes
against source. Withhold uncertain files; never silently sanitize copied source.

Rationale: Git tracking and extensions alone do not establish publication safety.
Ignore rules cannot fix tracked/history exposure. Python was unavailable; Node
24.13.1 and its standard library support the required implementation without new
dependencies or executing application scripts. A narrow SQL grammar preserves
reviewed schema references while withholding data/unknown statements.

Consequences: The export is a review candidate, potentially incomplete at
runtime because vendor/media and held files are excluded. Known tracked-file
holds are documented with categories only. Manual review, rights/history
assessment and explicit owner authorization remain required before a real
export or publishing. No live credentials, sessions or production were used.
Recovery-record updates are justified phase bookkeeping under AGENTS.md. The
original ZL-9 manifest is retained rather than regenerated to hide these deltas.

Verification: Both new tools passed Node syntax checks; 32 synthetic tests passed
with no failures/skips/cancellations. Windows junction and hardlink tests executed.
All 40 ignore assertions and four template checks passed. Actual-source default
dry run created no output. Final preservation/security results are recorded in
`../SECURITY_EXPORT.md` and the append-only contribution entry. These are executed
local checks, not application runtime verification or an exhaustive security audit.

## 2026-10-23 - Integrate the complete reviewed source into main

Context: The owner merged the seven original PRs. Only PR #1 targeted main;
PRs #2-#7 targeted the preceding feature branches, so main retained only the
repository foundations. The owner then explicitly requested completing the merge.

Decision: Create PR #8 from `feat/whatsapp-delivery` into main and merge it with
GitHub's merge method, supplying its expected head SHA. That branch contained all
26 original project commits and exactly the reviewed source tree. Preserve branch
history and feature branches; do not squash, rebase or rewrite existing commits.

Rationale: One integration PR brings the complete reviewed project and original
incremental history into main without reapplying application changes. Retained
merge-only commits on other feature branches do not represent missing source.

Consequences: Main now contains the full reviewed project. Record the completed
merge through a documentation-only PR. Runtime recovery and unresolved legacy
whitespace remain separate work; deployment and production access are not granted.

Verification: PR #8 merged at `626e35110f2acb89391c56831daf2712ee9be08b`.
All 26 original commit SHAs passed ancestry checks. Main's integration tree
matched the reviewed tree. Re-executed 164 PHP and 34 JavaScript syntax checks
passed; Gitleaks found no secrets in the remaining non-merge source history.

## 2026-10-23 - Retain inherited whitespace and report partial verification

Context: All staged source snapshots passed syntax and secret checks, but the
imported legacy text produced 1,798 whitespace/EOF warnings from Git diff checks.

Decision: Preserve those source lines during publication, record the non-clean
check explicitly and mark the phase PARTIAL. Do not apply bulk whitespace edits
to string/template bodies without behavior evidence merely to obtain a green check.

Consequences: The requested branch/commit/PR workflow is prepared for review,
while formatting validation remains unresolved. No functional readiness claim is
made. A later authorized recovery phase should determine safe formatting changes.

Verification: Warning counts and command outputs are preserved in private local
execution records. PHP/JavaScript syntax and publication secret checks passed;
the final counts and limits are recorded in STATUS.md.

## 2026-10-23 - Publish the reviewed legacy source as a dependent PR stack

Context: The owner initialized a public GitHub repository and explicitly asked
for feature branches, staged incremental Conventional Commits and open PRs.

Decision: Publish reviewed, sanitized source in seven dependent branches, from
repository foundations through the website, administration, delivery and
extractor. Keep real creation dates and identify commits as legacy imports.
Preserve source paths, existing changes and private originals. Preserve nested
extractor Git metadata locally and exclude its unscreened history. Keep main
unchanged until owner review. The precise scopes are in `PUBLISHING.md`.

Rationale: Small coherent changes and narrow PR diffs make the existing project
reviewable. Artificial micro-commits or fabricated feature history would not
improve engineering quality. The explicit request supplies publication approval;
no extra publishing confirmation is inferred from the earlier setup boundary.

Consequences: Intermediate branches are incomplete import snapshots. Review and
merge in sequence, retaining ancestry. Runtime defects, schema reconciliation,
deployment and production access remain outside this phase. Embedded contacts
are removed for publication, so support links and fallback contacts are disabled.
Historical setup documents are labeled to avoid treating them as verified steps.

Verification: Initial Git state and remote metadata inspected. Gitleaks 8.30.1
installed from its official release with SHA-256 verification. Final executed
checks and commit references are recorded in STATUS.md at handoff.

## 2026-10-23 — Scope the owner request as source-based project discovery

Context: The owner requested deep project understanding and a whole-project description after providing rules that require explicit authorization for investigation. This new request authorizes investigation/documentation, but does not request application repair or deployment.

Decision: Inspect local code and schema structure, run non-executing syntax checks, and document findings. Preserve application code and all existing user changes. Do not start services, import databases, contact integrations or initialize/commit the workspace.

Rationale: Explain the project using implementation evidence while respecting the recovery boundaries.

Consequences: Feature descriptions express intended/source-described behavior. Runtime functionality, database compatibility and production readiness remain unverified.

Verification: Git/status and filtered static inspections executed; 31 selected JavaScript syntax checks passed. PHP unavailable on PATH. Details in STATUS.md.

## 2026-10-23 — Do not infer recovery history from code comments

Context: Recovery records were missing, but existing files contain recovery guards and comments referring to earlier numbered work. The nested bot repository also has user changes.

Decision: Document the observed checkout and preserve it. Do not assert completion of any numbered recovery phase or overwrite existing recovery implementation.

Rationale: Code comments establish current text, not historical authorization, test execution or completion.

Consequences: Reconciliation of prior changes and schema generations is the next recommended task.

Verification: Presence of guards and Git modifications statically inspected; historical provenance not yet verified.


## 2026-10-23 - ZL-13 reproducible local environments with an explicit schema hold

Context: The owner authorized implementing development, restricted local staging
and CI from the existing extracted working tree, preserving uncommitted ZL-12
changes. No commits, publishing, production resources or ZL-14 schema work.

Decision: Use digest-pinned PHP 8.2.35/Apache, Node 24 (observed 24.22.1) and MySQL
8.4.13. Retain the website-only document root and .htaccess. Node runs synthetic
tests rather than a live integration. Two services are sufficient. MySQL is on an
internal network; web also needs a scoped ingress bridge because this engine did
not publish the internal-only service port. Bind only to 127.0.0.1. Web egress is
possible and is not described as firewall-denied. All providers remain disabled.

Decision: Three profiles have independent generated local credentials and named
volumes. Preserve actual PHP variable names and APP_ENV values; staging/CI use
test. Permit hostname db only with an explicit local container opt-in while
retaining all existing non-production target guards. No application tables,
schema merge, dump import, media or account seeds. The app identity has DML only.

Decision: Distinguish liveness, driver connectivity and schema readiness. Ready
returns 503 until ZL-14 supplies verified schema assertions. No health endpoint
runs legacy bootstrap DDL. Keep immutable action refs, least-privilege Actions
permissions, bounded diagnostics, and no artifacts/deployment. GitHub execution
is pending; local workflow reproduction is not a remote CI pass.

Consequences: Source changes need a rebuild; new build paths need explicit review.
Sessions are ephemeral; database volumes survive normal teardown. Uploads are
unavailable on read-only source. PHP 8.2 needs a supported-version migration before
its 2026-12-31 security support end. Debian build-package resolution is not fully
hermetic. Bot/extractor dependencies and business workflows remain unverified.
An aborted build may retain local cache; do not run broad pruning as cleanup.

Verification: Three local profiles started, loopback/DB-volume isolation checked;
125 synthetic tests and 109 PHP/42 JS parsers passed in the image. Dependency
checks/audits show both drivers available, least privilege and zero application
tables; readiness remains 503. Wrong synthetic credentials, DB outage/recovery,
web restart, actual Docker context exclusion fixture and final exact image
inventory were verified. Gitleaks scoped scan and actionlint passed. See
docs/development/ENVIRONMENT_OPERATIONS.md for commands, failures and limitations.


## 2026-10-23 - ZL-14 four-table subset, strict synthetic migration boundary

Context: The owner requested database reconciliation, then confirmed no sanitized
full DDL is available. Explicitly limit migrations to four independently defined
tables, keep existing PHP/MySQLi/PDO behavior, do not invent missing schemas or
open/copy the private dump, and report PARTIAL. No commits/push/production/ZL-15.

Decision: Retain site_settings (the actual settings table name), rate_limits,
movie_parts and episode_parts only. Their versioned CREATE statements and LF
checksums are the partial definition authority. Preserve historical SQL files,
all app source and repeated PDO named-parameter behavior. Exploratory app edits
were withdrawn and their exact initial hashes verified. No runtime DDL repair,
native-prepare conversion, table alias, missing token field or FK is guessed.

Decision: Use a small PHP runner rather than an ORM. Validate files/order/checksum
before connecting, and require local test role/name/UUID/hashed synthetic marker
before writes. Advisory lock precedes ledger creation; running/applied/failed
records track attempts with fixed errors. Verify schema/ledger drift and reconcile
post-commit failures only when the reviewed definition exactly matches. No DROP,
automatic conversion or production-capable runner is introduced.

Decision: Use existing ZL-13 images with a new independently labelled internal
network/tmpfs-only MySQL server. Verify zero non-system tables and no persistent/
bind mounts before bootstrap. Fresh credentials and a separate scoped migration
role; no existing profile volumes changed. CI gets the same check; hosted execution
pending. Exactly four reviewed new SQL files are permitted in build context, not
public export. No SQL dump/private row data is read.

Verification: Host/image suites 145 passes; final DB assertions 42 passes, including
historical two-part adoption, checksum/schema/ledger drift, locking, post-DDL SQL
failure recovery, marker/UUID rejection and prepared-value integrity. Original
bootstraps/repeated PDO parameters verified on the subset; runtime CREATE calls
in two legacy helpers are observed compatibility failures, left unchanged. Backup
encrypted in memory/restored to an empty separate target with identical schema/
rows and a valid ledger. Paused-server failure/recovery passed after bounding
mysqlnd greeting/read timeout only during migration connection construction.

Consequences: PARTIAL, with 24 missing complete DDL definitions plus unresolved
token/message/routine generations and full route/mysql2 compatibility. No timing
improvement claim from tiny-fixture EXPLAIN. No FK/cascade to unknown parent tables.
No operational persistent backup/key artifact, cloud DR or production migration.
Resume ZL-14 with approved missing schema/compatibility evidence; do not start
ZL-15 automatically. Detailed commands, corrected test failures and evidence limits
are in docs/database/SCHEMA_TEST_RESULTS.md.

## 2026-10-23 - Publish existing work as two dependent review units

Context: The owner explicitly requested staging, incremental commits, feature
branches and open PRs after the implementation-only ZL-12/ZL-13/ZL-14 tasks.

Decision: Start from refreshed origin/main 94f7708, whose tree equals the reviewed
ZL-11 HEAD. Use refactor/zl-12-leaf-modules for the two compatible leaf moves and
export closure. Use feat/zl-13-14-environments-migrations for coupled local
infrastructure and the four-table subset. Preserve distinct Conventional Commits
for substantial source, infrastructure, tests, CI and documentation groups.
Final packaging: both PRs target main, avoiding an owner retargeting step. Merge
PR #14 first and PR #15 second; the four shared refactor ancestors disappear from
#15's diff after #14 merges. This supersedes the initial stacked-base proposal.
Recommend merge commits to retain history; no PR is automatically merged.

Verification: Explicit paths, staged-tree Gitleaks, staged/working diff inspection,
whitespace and relevant parser/contract checks before each commit. Final staged
refactor passes 85 tests; integrated infrastructure passes 145 tests and 115 PHP /
44 JavaScript parsers. Full staged suite at integration supplements per-group
parsing; no claim that every intermediate commit implements the final environment.

Consequences: Existing partial acceptance states remain. Publication does not
repair unresolved schemas, runtime DDL or route wiring, enable live providers,
authorize production, or start ZL-15. Historical no-commit statements apply to
the earlier audit boundaries. Preserve current source and ignored recovery data.
