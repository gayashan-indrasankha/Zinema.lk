# Recovery decisions

## 2026-10-25 — Publish the existing source as an ordered, attributed import

Context: The owner requested staging, conventional commits, branches, GitHub
pushes and reviewable PRs to the existing repository. The starting root is now a
Git repository at `f89da9b`, with only the README tracked. Another local repair
finished while publication inspection was underway; refreshed snapshots must
include its final source and records.

Decision: Treat this request as explicit publication authorization. Preserve
`main`, use current dates and the configured owner identity, and group existing
files into coherent import commits with five branch checkpoints and ordered PRs.
Do not manufacture 100+ activity entries or claim upstream code as original work.
All PRs target `main`; later diffs include their predecessors until earlier PRs
merge. Owner review and merge remain manual.

Rationale: A traceable source import and honest verification record provide
reviewable provenance. Commit counts do not prove ownership or recreate absent
history. Existing production restrictions still apply.

Consequences: Intermediate imports require later checkpoints for the full
application. Private SQL/data, sessions, diagnostic exports, runtime fixtures,
installed dependencies and credentials remain local. Hardcoded contacts become
optional empty-by-default configuration. The embedded extractor repository is
preserved locally while its reviewed source becomes ordinary parent-repository
files, avoiding an incomplete gitlink. PHPMailer attribution and licenses remain.

Verification: Publication-phase syntax, fake-client regression, secret/data,
staged-content and remote checks are recorded in `PUBLISHING.md` and its results.
Historical repair HTTP/browser evidence is not represented as newly executed.

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

## 2026-10-25 — Authorize local assessment from the new owner request

Context: The owner explicitly asked to read the product and test it manually and automatically. Earlier restrictions required an owner request before runtime investigation.

Decision: Treat the new request as authorization for local source inspection, automated/manual tests and isolated fictional fixtures. Preserve application behavior and existing files; add regression tests and records. Do not infer authorization for application repair, production integrations, credential rotation or publishing.

Rationale: Runtime evidence is necessary to answer whether the current product works. Repairs would be a distinct phase.

Consequences: Assessment is executed but the phase remains PARTIAL because unresolved failures are retained. No numbered historical recovery phase is declared complete.

Verification: 75 HTTP assertions, 17 Node guard/fake-client tests, four extractor HTTP checks, PHP/JS syntax checks and manual browser journeys executed. Details and failures are in WORKING_STATE_ASSESSMENT.md and result files.

## 2026-10-25 — Test supplied structure in a separate native runtime

Context: XAMPP binaries are installed even though PHP is absent from PATH. The execution sandbox blocks local socket access. Existing local services are not proof of a sanitized database. SQL generations are unreconciled.

Decision: Create a separate MariaDB data directory on loopback port 3307 and verify its data directory/port before SQL operations. Extract only CREATE TABLE and ALTER TABLE structure from Database.sql, omit historical counters, import zero legacy rows and seed fictional records. Run a separate Apache instance on port 8088 against a sanitized source mirror and block external browser resources. Use approved execution outside the socket-restricted sandbox for these local checks. Protect generated runtime artifacts and stop services after testing.

Rationale: Test compatibility without contacting an unknown database, using real accounts or pretending that reconstruction is complete. Native binaries avoid an unnecessary Docker dependency.

Consequences: Results apply to the supplied structure and explicit local test configuration. They do not establish the deployed schema. PDO's separate bootstrap and external integrations still need dedicated work. Initial harness setup failures were investigated, corrected and separated from product defects.

Verification: 21 tables and 45 ALTER statements applied; zero preserved rows imported. Sample video played in the browser. Owned Apache/MySQL processes stopped with exit 0 and their ports closed. No original application source edits were made.

## 2026-10-25 — Separate module evidence, advisory matches and live-service claims

Context: WhatsApp startup and live extraction are intentionally quarantined. Extractor dependencies/lockfile are absent; the bot supplies a lockfile. A health endpoint and syntax checks alone cannot establish delivery reliability.

Decision: Keep guards active. Exercise bot modules with fake clients only; test extractor disabled/local modes using temporary HTTP dependencies with installation scripts disabled. Retain failing regression expectations for coalescing and false upload success. Audit a copy of the bot lockfile and identify the temporary extractor audit as a partial dependency subset.

Rationale: Obtain useful runtime evidence without granting live access or overstating audit coverage.

Consequences: Live WhatsApp, Facebook extraction, mail, OAuth and payment behavior remain unverified/unavailable. Reported dependency counts are advisory matches; application exploitability is not established. No dependency upgrades or automatic fixes applied.

Verification: Guards 10/10; fake bot modules 5/7; extractor checks 4/4. npm advisory commands executed with findings and limitations recorded in SERVICE_RESULTS.json. Dedicated secret scanners unavailable; no passed secret scan claimed.

## 2026-10-25 — Apply the explicitly authorized local repairs

Context: The owner requested authentication/CSRF and bot delivery fixes, schema reconciliation and passing the retained regressions while keeping production/publishing disabled.

Decision: Centralize verified password authentication, safe session setup and registration. Require session-bound CSRF for browser writes, provide tokens in forms and same-origin fetch headers, and replace GET deletion/logout with POST. Preserve successful behavior by supplying valid tokens in positive tests while retaining missing/invalid/cross-session rejection expectations. Store new remember credentials as digests with expiry and revoke them on logout/password reset.

Rationale: Multiple parallel handlers had conflicting verification and mutation policies. One bootstrap and shared helpers reduce bypass opportunities without replacing the product architecture.

Consequences: Unverified sessions and old plaintext remember credentials cannot authenticate through the repaired policy. Disabled mail blocks new registration; failed enabled delivery preserves an honestly reported pending account. Actual OAuth/mail journeys are not certified. No existing credentials were rotated.

Verification: 94 final HTTP/security assertions pass; zero new PHP diagnostics; browser valid/invalid login and protected mutations checked. Details and caveats in REPAIR_PHASE.md.

## 2026-10-25 — Establish one data-free local creation schema

Context: The dump includes four IF NOT EXISTS definitions omitted by the first fixture; migrations disagree on token state, message mapping and table names. Trailer/comment paths used the wrong connection/schema. Mixed collations surfaced when delivery views were exercised.

Decision: Keep token boolean state and the established catalogue/movie-comment names, normalize full/part read models, add missing identities/uniqueness/operational fields, initialize PDO explicitly and share DB_PORT. Produce a flattened, invoker-view creation file with consistent collation, no data or environment-specific definer. Preserve legacy files and do not provide an unverified in-place migration.

Rationale: A reproducible empty database can be verified without guessing how to merge sensitive legacy records.

Consequences: New local fixtures use database/schema.sql. Existing database migration and distributed token claims need a later explicit phase. A comment-only difference in the legacy bot SQL observed during this work was preserved without claiming authorship.

Verification: 37 tables and two views rebuild in another empty isolated database; all 19 populated schema/SQL adapter checks pass. Original data was not imported. Populated dashboard errors were corrected and diagnostics are checked in the final suite.

## 2026-10-25 — Require acknowledgements for each bot recipient

Context: The forwarder referenced an undefined variable when broadcasting, reported unacknowledged uploads as successful, could leave failed-operation waiters unresolved and retained upload timeout handles.

Decision: Coalesce duplicate requests onto pending results, require valid client message keys for success, settle every waiter independently, and release upload timeouts. Cache warming is not recipient delivery. Keep the real startup guard and use fake clients only.

Rationale: Delivery outcomes must be backed by a client acknowledgement before the handler consumes a token or reports success.

Consequences: Missing acknowledgements and rejected recipient sends fail honestly. This does not establish distributed exactly-once delivery or real WhatsApp compatibility. Incomplete unreferenced snippets were preserved privately and replaced with fail-closed quarantine stubs.

Verification: All 12 fake-client bot regressions pass, including duplicate requests, primary/waiter failures and a real-timer successful upload test. Startup/configuration guards pass 10/10.

## 2026-10-25 — Repair dependency advisories within the retained major line

Context: Baseline npm checks reported bot critical/high findings. Compatible lockfile updates cleared most; an older transitive protobuf copy required an explicit override. Temporary extractor tests had pinned old HTTP dependencies despite compatible ranges in the actual manifest.

Decision: Keep Baileys 6.x (locked 6.7.24), require Node >=20 and override protobufjs to ^7.6.6. Validate a clean isolated install and offline factory/serializer contracts. Lock the extractor HTTP test subset separately and do not imply full Chromium/extractor certification.

Rationale: Clear the exercised advisory checks without enabling live services or performing an untested major Baileys migration.

Consequences: The previous installed bot node_modules tree is preserved and must not be confused with the repaired lockfile. Live compatibility, browser-extraction dependencies and comprehensive security remain outside the result.

Verification: Both scoped npm audits exit 0 with zero findings; clean npm ci --ignore-scripts and offline dependency smoke pass. No socket or auth store started.

## 2026-10-25 — Retain and disclose import whitespace findings

Context: The complete source import passes syntax, JSON, fake-client regressions
and dedicated secret scans, while Git's whitespace check exits 2 with 1,709
findings across 111 legacy files. Broad formatting changes would enlarge the
publication diff without addressing a confirmed functional defect.

Decision: Preserve the imported formatting and explicitly retain the phase's
PARTIAL state for the unresolved check. Complete the authorized branch/PR workflow
and leave merging to the owner. Do not describe all checks as passed.

Consequences: Five ready-for-review PRs are available in a documented order;
whitespace cleanup remains a separate reviewable follow-up. No production or
full-product readiness claim follows from local test/scan results.

Verification: Staged scans 35/35 and complete root-history scan report zero
findings; 10 configuration and 12 fake-client tests pass; Git whitespace exit 2
retained. GitHub confirms all five PRs open/non-draft/mergeable with base `main`.
Final documentation is checked again before its handoff commit and push.

## 2026-10-25 — Use the owner's project branch prefix

Context: The owner explicitly requested all five review branch names use
`zinema/import-*`. GitHub PR source branches cannot be edited through the PR
update API; changing those branch names requires replacement PRs.

Decision: Preserve all original source commit hashes and author information,
create verified project-prefixed references and replacement PRs, close and link
the superseded PRs, then remove the old references and update local upstreams.
Update current recovery branch labels and PR links without rewriting history.

Consequences: PRs #6–#10 replace #1–#5 and retain the same merge order into `main`.
One documentation commit records the rename. Existing source authorship,
third-party attribution, application behavior and prior verification limits remain.

Verification: New refs matched original heads and replacement PRs were verified
open, non-draft and mergeable before cleanup. Local branches use the requested
prefix and upstreams. Final documentation checks and remote/PR verification are
recorded in the execution handoff; no runtime retest is required for branch names.

## 2026-10-25 — Isolated portable development and fictional identity services

Context: The native Windows fixture was reproducible only on the installed XAMPP
paths. Disabled mail/OAuth prevented positive verification/reset/provider tests.

Decision: Pin official PHP/Apache, MariaDB and Python image digests and image
library package versions. Build an allowlisted code image, initialize only the
canonical schema in a named development volume, and generate private configuration
under ignored `.runtime/local-dev`. Share a loopback namespace on an internal
network so the existing local-target guards remain effective; a credential-free
fixed TCP gateway exposes only two host-loopback ports. Implement stdlib SMTP and
OAuth fakes with fictional identities, no relay, protected test controls and no
request/token logging. Keep production-capable adapters disabled and all remote
Git actions outside this phase.

Consequences: A clean checkout needs Docker/Python instead of native XAMPP. Builds
require registry and Debian package availability; architecture and bit-for-bit
reproducibility are limited as recorded in `dev/runtime-lock.json`. Development
aliases/accounts must never become a production deployment. Generated secrets,
logs, database files and user media are excluded from images and Git.

Verification: Pinned builds and repeated starts passed. Seventeen owned-container
checks passed; HTTP tests confirm no default route in the application namespace.
Fake mail covers signup/verify/resend/reset, expiry/replay and SMTP failure. OAuth
uses single-use session state, S256 PKCE, verified-email checks, explicit owner
linking and session rotation; local HTTP, seven helper checks and a manual browser
sign-in/sign-out passed. Real providers and delivery remain not yet verified.

## 2026-10-25 — Validate new uploads and preserve existing media

Context: Admin upload handlers accepted raw extensions/names, removed old media
before replacement succeeded, trusted a posted collection path, and invoked a
shell for trailer thumbnails. Episodes stored a legacy remote thumbnail URL.

Decision: Centralize size/type/filename validation, randomize stored names, bound
image pixels and decode/re-encode accepted PNG/JPEG/WebP images. Store MP4/WebM as
inert files after MIME/signature/size checks; remove shell-based thumbnail work.
Use local upload paths. Retain old/shared media on replacement and record deletion;
filesystem cleanup needs a separate inventory and ownership decision.

Consequences: Invalid replacement cannot destroy the previous record or file.
Local Apache serves only generated media names with script execution disabled.
Video checks establish container/type containment, not comprehensive decoding or
malware safety. There is no antivirus, storage quota, orphan cleanup or production
server certification in this phase. PHP's full_path field is checked because it
normalizes the ordinary name field before the application sees traversal input.

Verification: All image handlers and the generated original MP4 succeed over HTTP;
PHP disguise, SVG, mismatched/malformed images, traversal and oversize are rejected.
Images lose appended payloads and failed replacements preserve old files. Posted
existing collection paths cannot replace database-owned values. Tests passed in
`LOCAL_DEV_RESULTS.json`; no legacy media was imported or deleted.

## 2026-10-25 — Database claim before bot delivery

Context: Validate-then-send-then-mark-used allowed two independent processes to
send the same active token. A failed mark-used response was ignored. Token expiry
also changed automatically on UPDATE, making a claim unsafe without schema repair.

Decision: Atomically reserve an active, unused, unexpired token for its assigned
bot and recipient hash. Store a hash of a random claim receipt. Require the proof
for claimed → sending → completed/uncertain transitions, consume only acknowledged
sends, and deny the old mark-used bypass. Remove automatic expiry updates. Never
release sending/uncertain claims on a timer or retry after an ambiguous completion.
The migration is additive and applied only after the launcher verifies its owned
local database identity. Fresh creation remains data-free.

Consequences: Honest adapter workers get one cross-process claim; a shared API key
still makes those workers a trust boundary. The claim receipt is bearer material
and must not be logged. This is not distributed exactly-once delivery: external
acknowledgement can be lost and delivery-tier transport ambiguity is still possible.
Held claims need an operator reconciliation workflow, deliberately not implemented
as automatic reset. Real WhatsApp and production schema upgrades remain disabled.

Verification: Thirty-two independent HTTP workers produce one winner. Wrong owner,
receipt, assigned bot, pre-send completion, replay, inactive/expired tokens and late
begin are denied. Stale sending and failed claims remain held; completion after a
valid begin can consume even if expiry passes during delivery. Six fake bot/client
checks and 22 schema/bridge checks pass. No real recipient was contacted.

## 2026-10-25 — Preserve history and establish a local sanitized baseline

Context: This task began from a clean existing imported repository. Earlier
publication and remote review history were already present; the current owner
explicitly kept publishing and production disabled.

Decision: Preserve all existing history and user information. Create local-only
`zinema/local-dev-safety`, stage only reviewed source/tests/sanitized evidence,
run dedicated staged/candidate/history scans and commit locally without contacting
remotes. Keep generated configuration, sample runtime media, sessions, databases,
dependencies, debug logs and preserved legacy evidence out of Git.

Verification: Eight ignore sentinels and forbidden-index-path review passed;
Gitleaks 8.30.1 found zero findings in staged changes, the full 337-file candidate
and all local history. Runtime regressions, fresh-source reproduction and new
staged whitespace checks passed. Baseline commit `9c48b7ca8a2b6535b6da0b408d49bbdfe7280f1a`
was created and left a clean tree. A documentation-only completion commit records
this result; final staged/history checks and Git status are checked at handoff.
Historical publication whitespace and comprehensive production/security readiness
remain unresolved and were not relabeled as passing.

## 2026-10-25 — Local operator closure with versioned claim evidence

Context: Sending/uncertain claims intentionally remain held after failures, but
there was no operator workflow. The expiry cleanup utility and disabled cron SQL
could erase held evidence if invoked. The owner authorized a local fictional
reconciliation phase and explicitly kept production/publishing disabled.

Decision: Keep operator actions in a guarded CLI outside the public document root.
Review a numeric ID and integer revision; require a fictional operator/evidence
reference, explicit stopped-worker acknowledgement and a ten-minute minimum age.
Under a row lock, compare revision, insert one audit and close the old token.
Delivered consumes it; confirmed non-delivery deactivates it. Neither releases,
reissues nor sends. Identical repeats return the existing audit; conflicting or
stale reviews fail. Claim transitions increment revisions and reject reconciled
rows. Preserve all claimed/reconciled records in expiry cleanup; keep cron disabled.

Consequences: The operator's attestation is trusted, not externally proved. Age
alone proves nothing about delivery or quiescence. Local Docker/DB privileges and
the shared worker key remain trust boundaries. Audit rows are append-only through
this tool, not cryptographically immutable against database administrators. There
is no exactly-once or in-flight cancellation guarantee; live adapters stay disabled.

Verification: Executed independent-process operator conflicts/replays, stale worker
transitions, late begin/complete, age and target guards, secret-free listing and
manual CLI list/review/acknowledge/resolve/audit/replay passed. Cleanup preservation
runs against a session-local temporary table; no existing token was deleted.
Fresh canonical schema and retained bridge checks passed 22/22 with 38 tables/2 views.

## 2026-10-25 — Serialize physical upload quotas and keep cleanup advisory

Context: Valid uploads were type/size checked, but retained/orphan files could grow
without aggregate accounting. Legacy filename appearance does not prove ownership.

Decision: In the portable development runtime, count every physical regular file
and byte under one volume lock before exclusive creation, and record new-file
provenance in a private manifest. Default to 64 MiB/500 files. Unknown/retained media
counts; links, mismatched ownership and corrupt state fail closed. Keep the manifest
outside public uploads. Inventory registered hashes and persisted media/path
reference columns in a read-only DB snapshot, skip derived views, bound traversal/
reference reads, and hold any incomplete scan. Only old, unchanged, registered,
unreferenced media becomes a review candidate. Provide no deletion/apply command.

Consequences: This is a local-volume quota, not a distributed quota or temporary
processing limit. SQL and filesystem writes are separate; crash orphans still count
and need review. Hashes detect drift, not a malicious trusted filesystem editor.
Reference coverage is explicit and incomplete for arbitrary embedded/remote syntax.
A snapshot/candidate does not prove deletion is safe. Legacy/shared/unknown media is
preserved; recoverable quarantine and restore require a separately scoped phase.

Verification: HTTP 507 preserves the old catalogue path, denied writes leave usage
unchanged, concurrent file/byte limits do not oversubscribe, and shared/encoded/base
references are retained. Tampered/missing/young/unknown/linked files and truncated
references are held. Inventory changes no data. Separate fixture volumes isolate
quota/tamper simulations; generated missing-file/link fixtures alone are disposable.

## 2026-10-25 — Pin local Docker and exclude implicit environment loading

Context: A tool labelled local must not follow a globally selected remote Docker
context, and Compose's implicit environment discovery must not read preserved config.

Decision: Inspect client context metadata before daemon access, accept only local
Unix-socket/named-pipe endpoints, refuse DOCKER_HOST overrides, pin the inspected
context in the launcher's private environment, and supply an explicit empty Compose
environment file. Keep generated configuration and volume data private and preserved.

Verification: Seven executed boundary unit cases reject simulated remote/override/
broken contexts and unrelated/stopped/mismapped gateways before Compose, pin local transports and verify the explicit safe
env-file. Real guarded startup and the operations suite are checked separately.
No remote Docker daemon, Git remote, production provider or publishing was used.

Local handoff: source commit `cae7ab7f05680a8a4894c732aa3529c518d95e33` was created after
57 operations checks, 7 direct CLI steps, 7 local-boundary cases and the retained
suites passed. Final staged/candidate/history scans reported zero findings; all
owned test services stopped and data remained preserved. A documentation-only
completion commit records the result without publishing or rewriting history.

## 2026-10-25 — Reviewed recoverable development-upload cleanup

Context: The owner explicitly authorized the next phase after the read-only
inventory. A candidate snapshot is insufficient authority to delete media, and
SQL references and two storage volumes cannot share a database transaction.

Decision: Add CLI review/history/quarantine/restore/recover only for registered,
owned development assets. Require current revision, explicit review, fictional
actor/evidence and unique case. Take the DB advisory reference gate before the
volume lock; recheck known reference columns, source identity/hash/age and quota.
Participating browser POST and WhatsApp metadata writers take the same gate and
reject pending/quarantined paths. Unknown/legacy/shared/young/changed/unsafe files
stay held. Production adapters, remote Git operations and publishing stay disabled.

Copy and verify into private quarantine before withdrawing the public source.
Journal intent and checkpoints in the atomic private manifest with bounded cases
and append-only-through-tool audit. Identical cases replay; conflicting/stale
cases fail. Interrupted operations pause admissions and require reviewed finish or
rollback. Restore publishes exclusively without overwriting, enforces public
quota, records publication identity and retains the private rescue archive.
Quarantine has separate physical byte/file limits. There is no permanent purge.

Consequences: Public quota can be reclaimed, but total disk space is not freed.
Restored archives and journals accumulate; capacity exhaustion fails closed and
requires a separately reviewed maintenance design. Direct trusted DB/filesystem
writers bypass the cooperative protocol. Reference coverage remains bounded and
syntax-specific. Audit is not immutable against administrators, and journaling
is not a distributed transaction or a power-loss durability guarantee. Unsafe or
ambiguous data is retained. No production readiness claim follows from local tests.

Verification: Separate fictional volumes exercise review, hash drift, collisions,
quota, audit/replay, reference/operator races, interruptions and reviewed recovery.
The initial executed suite exposed loss of publication recognition after the
restore link was removed but before completion. A persistent publication identity
fix passed all 49 checks on rerun. Final expanded CLI/regression evidence and
handoff are recorded in QUARANTINE_PHASE.md and STATUS.md.
