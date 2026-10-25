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
