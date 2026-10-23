# Technical debt and migration blockers — Jira ZL-11

Date: 2026-10-23 (Asia/Colombo). Bounded static findings only.
No issue below is repaired by this task. No CRITICAL exploit is independently
confirmed; severity describes potential impact if affected behavior is enabled
or moved. Every runtime/exploit claim is **NOT VERIFIED** unless explicitly
identified as a parser/filesystem/URL-model check.

Evidence without a root prefix is relative to `Zinema.lk Website/`.
Bot-relative shorthand following a bot path stays under `whatsapp-bot-deploy/`.
Use [module ownership](MODULE_INVENTORY.md) and [current architecture](CURRENT_ARCHITECTURE.md)
for complete file membership and callers. Existing baseline findings were
reconciled with current source; this is not a dependency vulnerability or full
privacy/security audit.

## Prioritized register

| ID | Priority | Finding | Confidence | Affected groups |
| --- | --- | --- | --- | --- |
| TD01 | High | PDO/MySQLi bootstrap mismatch | High | W02,W11,W12 |
| TD02 | High | Incompatible schema generations | High | D01,D02,W19,W20,B10 |
| TD03 | High | Bridge table and ID vocabulary drift | High | W20,B03,B04 |
| TD04 | High | Invalid token data accepted by bot | High | W19,W20,B01,B03 |
| TD05 | High | Delivery success, claim and acknowledgement conflated | High | W20,B03,B04,B05,B07 |
| TD06 | High | Long-lived and parallel session/auth policies | High | W02,W04,W05,W14 |
| TD07 | High | Admin destructive GET and upload boundary | High | W15,W16 |
| TD08 | High | Current safety guards interrupt historic core journey | High | W19,W21,W23,W29,B01 |
| TD09 | High | Sensitive diagnostic logging paths | High | B01,B03,B09,W21 |
| TD10 | High | Settings reads perform schema/data writes | High | W18,W19 |
| TD11 | High | Browser URL namespace and physical paths coupled | High | W01,W06,W07,W13,W22,W26 |
| TD12 | Medium | Conditional extractor dependency and unsafe retained logic | High | W08,W16,X01 |
| TD13 | Medium | Large mixed controllers and duplicated overlays | High | W04,W06,W08,W09,W16 |
| TD14 | Medium | Parallel prototype and standalone browser generations | High source; Medium disposition | W25,W26 |
| TD15 | Medium | Generated API subpaths lack verified server contract | High | W11,W12 |
| TD16 | High | Runtime state tied to code path and incomplete ignore protection | High | B04,B06,B07,G01,G04 |
| TD17 | Medium | Scheduler/comment/diagnostic drift | High | B07,B08,B11 |
| TD18 | Medium | Untested queue/coalescing/cancellation semantics | High source; behavior NOT VERIFIED | B04,B05,B07 |
| TD19 | Medium | Dependency reproducibility gaps | High | B12,X01,G05,G06 |
| TD20 | High | Missing runtime and regression baseline | High | All behavior-bearing groups |
| TD21 | Medium | Unknown external/dynamic consumers | High uncertainty | B10,W26,W28,P01 |
| TD22 | High | Private data/account/media rights remain outside audit | High presence; contents unknown | P02,G07,G06 |
| TD23 | Medium | Offline CSP versus CDN asset assumptions | High source; runtime NOT VERIFIED | W01,W25,W27 |
| TD24 | Low | Absent error/prototype/legacy route targets | High | W01,W25,W26 |
| TD25 | Low | Misleading scheduler success output | High | B11 |
| TD26 | Medium | Token expiry/rate-limit configuration drift | High | W03,W18,W19 |
| TD27 | Medium | Export approval does not follow new paths automatically | High | G01,G04 and every moved group |
| TD28 | High | Live bot can mutate private authentication state | High | B01,B06,P02 |
| TD29 | Critical for public upload exposure | Unsafe upload-derived thumbnail shell command | High construction; exploitability NOT VERIFIED | W15 |

## Evidence and actions

### TD01 — PDO/MySQLi bootstrap mismatch

- Priority: **High**. Confidence: High. Runtime/exploit behavior NOT VERIFIED.
- Evidence: api/comments.php:2-27; api/trailers.php:2-18; trailers.php:2-17; includes/database.php:2-24.
- Impact: Controllers pass $pdo after including a file that creates only $conn; preserving filenames alone cannot recover these paths.
- Recommended action: Define explicit connection contract and fixture-backed comment/trailer behavior before moving bootstrap or managers.
- Dependencies/gates: Sanitized schema, P3/P4 and method/response contracts.
- Jira: ZL-14. ZL-12 remains an affected-move hold; fixes are separately reviewed. Do not bundle the fix with an unrelated path migration.

### TD02 — Incompatible schema generations

- Priority: **High**. Confidence: High. Runtime/exploit behavior NOT VERIFIED.
- Evidence: whatsapp-bot-deploy/database/schema.sql:6,22; migration_ultimate_system_FIXED.sql:43-58; includes/whatsapp_token.php:166-175; api/whatsapp/validate-token.php:32-54.
- Impact: Old video_tokens/forward_logs and status-only whatsapp_tokens definitions conflict with current expected names/columns; installed DB unknown.
- Recommended action: Produce canonical structural proposal and caller/schema compatibility matrix; do not execute FIXED/legacy scripts blindly.
- Dependencies/gates: Separate schema issue, sanitized fixtures and approved migration/rollback.
- Jira: ZL-14, ZL-47. ZL-12 remains an affected-move hold; fixes are separately reviewed. Do not bundle the fix with an unrelated path migration.

### TD03 — Bridge table and ID vocabulary drift

- Priority: **High**. Confidence: High. Runtime/exploit behavior NOT VERIFIED.
- Evidence: api/whatsapp/update-cache-message-id.php:25-48; update-forward-stats.php:23-50; get-popular-content.php:37-40; validate-token.php:50-54.
- Impact: Update/stat paths use movie_message_ids and token ID lookup assumptions while validation/add/get use whatsapp_*; tv_series differs from normal series queries.
- Recommended action: Specify content/token/message/part ID and table contracts; fixture-test every bridge endpoint before repair.
- Dependencies/gates: TD02 and fake HTTP/query suite P4/P6.
- Jira: ZL-14, ZL-30. ZL-12 remains an affected-move hold; fixes are separately reviewed. Do not bundle the fix with an unrelated path migration.

### TD04 — Invalid token data accepted by bot

- Priority: **High**. Confidence: High. Runtime/exploit behavior NOT VERIFIED.
- Evidence: api/whatsapp/validate-token.php:73-99; whatsapp-bot-deploy/index.js:927-939.
- Impact: Inactive/expired responses include data; handler requires data and explicitly rejects already_used but does not positively enforce all invalid states.
- Recommended action: Specify explicit allowlist of processable validation outcomes, including deliberate no_message_id behavior; add fake invalid/replay regressions before any enabling.
- Dependencies/gates: TD02, fake bridge/socket and owner-approved behavior change.
- Jira: ZL-30, ZL-51. ZL-12 remains an affected-move hold; fixes are separately reviewed. Do not bundle the fix with an unrelated path migration.

### TD05 — Delivery success, claim and acknowledgement conflated

- Priority: **High**. Confidence: High. Runtime/exploit behavior NOT VERIFIED.
- Evidence: whatsapp-bot-deploy/index.js:945-954; config/api-client.js:95-101; config/multi-tier-forwarder.js:478-490; api/whatsapp/mark-used.php:32-47.
- Impact: Token-use marking follows reported send and can fail as false; warmer completion returns success without an explicit recipient send in that branch. Concurrency/idempotency unproven.
- Recommended action: Design claim/deliver/ack state, ownership, retries and recipient receipts; regress warm/user races and mark failure separately from movement.
- Dependencies/gates: TD02-TD04, fake clock/store/socket/HTTP P6.
- Jira: ZL-30, ZL-29. ZL-12 remains an affected-move hold; fixes are separately reviewed. Do not bundle the fix with an unrelated path migration.

### TD06 — Long-lived and parallel session/auth policies

- Priority: **High**. Confidence: High. Runtime/exploit behavior NOT VERIFIED.
- Evidence: admin/config.php:6-9,30-47; ajax_login.php:82-100; api/auth-handler.php:7-28; trailers.php:6.
- Impact: Century-scale session settings/remember-token lookup coexist with multiple form/AJAX/JSON/admin lifecycles; expiry/revocation and role parity unverified.
- Recommended action: Characterize current cookie/session/auth state first; plan security repair separately, including rotation/expiry/CSRF/role tests.
- Dependencies/gates: Fictional users/admins, isolated session/mail/OAuth P3.
- Jira: ZL-19. ZL-12 remains an affected-move hold; fixes are separately reviewed. Do not bundle the fix with an unrelated path migration.

### TD07 — Admin destructive GET and upload boundary

- Priority: **High**. Confidence: High. Runtime/exploit behavior NOT VERIFIED.
- Evidence: admin/movies.php:10-27,49-59; admin/series.php; admin/episodes.php.
- Impact: Authenticated GET can delete records/files; inspected upload block uses supplied basename without a complete visible type/size policy. Apache upload denial is untested defense-in-depth.
- Recommended action: Review mutation/CSRF/authorization and file containment/type/size policy, add fixture regressions, keep behavioral repair out of source moves.
- Dependencies/gates: P2-P5, synthetic uploads and approved disposable DB.
- Jira: ZL-19, ZL-42, ZL-51. ZL-12 remains an affected-move hold; fixes are separately reviewed. Do not bundle the fix with an unrelated path migration.

### TD08 — Current safety guards interrupt historic core journey

- Priority: **High**. Confidence: High. Runtime/exploit behavior NOT VERIFIED.
- Evidence: download.php:2-4; whatsapp-watch.php:3-4; api/subscribe.php:2-4; api/server-check.php:2-4; whatsapp-bot-deploy/index.js:1-3.
- Impact: Source is not evidence the owner-reported Shots-to-delivery journey currently runs. Local/full delivery simulator is not implemented.
- Recommended action: Keep guards; define explicit fictional delivery and legal-media demo contract before recovery. Never bypass live flags to simulate.
- Dependencies/gates: P3-P7 and separate simulator/repair issue.
- Jira: ZL-18, ZL-17, ZL-28. ZL-12 remains an affected-move hold; fixes are separately reviewed. Do not bundle the fix with an unrelated path migration.

### TD09 — Sensitive diagnostic logging paths

- Priority: **High**. Confidence: High. Runtime/exploit behavior NOT VERIFIED.
- Evidence: whatsapp-bot-deploy/config/api-client.js:81-82; index.js:924,942,954,991; api/payment-notify.php:18.
- Impact: Code can log full token responses/identifiers or legacy notifications. Existing logs were not read and exposure is not confirmed.
- Recommended action: Specify structured redacted event contract and retention; test with synthetic values without printing real findings.
- Dependencies/gates: Separate logging/security scope and fake payloads.
- Jira: ZL-49, ZL-51, ZL-53. ZL-12 remains an affected-move hold; fixes are separately reviewed. Do not bundle the fix with an unrelated path migration.

### TD10 — Settings reads perform schema/data writes

- Priority: **High**. Confidence: High. Runtime/exploit behavior NOT VERIFIED.
- Evidence: includes/settings.php:15-27,99-137.
- Impact: getSetting calls ensureSettingsTable, which creates a table and inserts defaults; moving/importing it is not a pure configuration change.
- Recommended action: Characterize default/cache/error semantics; separate approved migrations from request reads in a dedicated repair.
- Dependencies/gates: Canonical schema and isolated DB tests P4.
- Jira: ZL-14, ZL-47. ZL-12 remains an affected-move hold; fixes are separately reviewed. Do not bundle the fix with an unrelated path migration.

### TD11 — Browser URL namespace and physical paths coupled

- Priority: **High**. Confidence: High. Runtime/exploit behavior NOT VERIFIED.
- Evidence: _top_nav.php:120,214; js/tracker.js:5,27; .htaccess:73-85; detail pages' BASE_URL asset links.
- Impact: Relative actions/fetch/beacons under rewritten slug URLs resolve below nested paths. Pure URL modeling shows risk; no live request failure asserted.
- Recommended action: Build direct/slug/subdirectory route+asset contract and preserve wrappers/explicit URL roots before moving public entry files.
- Dependencies/gates: Isolated Apache/browser P2/P5 and route ownership.
- Jira: ZL-12, ZL-16, ZL-31. ZL-12 remains an affected-move hold; fixes are separately reviewed. Do not bundle the fix with an unrelated path migration.

### TD12 — Conditional extractor dependency and unsafe retained logic

- Priority: **Medium**. Confidence: High. Runtime/exploit behavior NOT VERIFIED.
- Evidence: index.php:451-550; admin/manage_shots.php:35-56; fb-video-api/index.js:239-254.
- Impact: Public refresh and admin share-URL ingestion depend conditionally on extraction; unreachable URL validation uses substring matching, admin curl disables TLS verification in retained call. Guards currently contain live extraction.
- Recommended action: Keep extractor isolated; legal local/pre-resolved fixtures and explicit timeout/fallback interface; separate SSRF/TLS threat-model repair.
- Dependencies/gates: P7, rights-reviewed media and explicit authorization for any live adapter.
- Jira: ZL-18, ZL-51. ZL-12 remains an affected-move hold; fixes are separately reviewed. Do not bundle the fix with an unrelated path migration.

### TD13 — Large mixed controllers and duplicated overlays

- Priority: **Medium**. Confidence: High. Runtime/exploit behavior NOT VERIFIED.
- Evidence: index.php; movies.php; tv-series.php; profile.php; admin/manage_shots.php.
- Impact: SQL/rendering/styles/browser state/auth are combined; similar overlays and handlers drift across pages.
- Recommended action: Extract tested query/DTO/partial boundaries one leaf at a time; do not mechanically split by line count.
- Dependencies/gates: P1-P5 and behavior snapshots.
- Jira: ZL-12. ZL-12 remains an affected-move hold; fixes are separately reviewed. Do not bundle the fix with an unrelated path migration.

### TD14 — Parallel prototype and standalone browser generations

- Priority: **Medium**. Confidence: High source; Medium disposition. Runtime/exploit behavior NOT VERIFIED.
- Evidence: pages/movie-detail.php:7-16; pages/trailers.php:4-17; includes/header.php:43-49; js/navbar.js:28-32.
- Impact: TODO/sample pages coexist with real controllers; legacy .html targets are absent, but some prototype/direct links remain. No safe-unused conclusion.
- Recommended action: Maintain explicit consumer/URL ledger; only consider archive after replacement/redirect/tests and owner approval.
- Dependencies/gates: P2/P5, external/deployment consumer review.
- Jira: ZL-12, ZL-27, ZL-31. ZL-12 remains an affected-move hold; fixes are separately reviewed. Do not bundle the fix with an unrelated path migration.

### TD15 — Generated API subpaths lack verified server contract

- Priority: **Medium**. Confidence: High. Runtime/exploit behavior NOT VERIFIED.
- Evidence: js/comments.js:207; js/trailer-feed.js:360,372; api/comments.php:17-32; api/trailers.php.
- Impact: Clients append /like,/view,/share while PHP handlers use method/query dispatch. Standalone matching files/router behavior not established.
- Recommended action: Specify and test real method/query/PATH_INFO response contract; do not invent matching endpoints during migration.
- Dependencies/gates: P2-P5 and TD01.
- Jira: ZL-16, ZL-31. ZL-12 remains an affected-move hold; fixes are separately reviewed. Do not bundle the fix with an unrelated path migration.

### TD16 — Runtime state tied to code path and incomplete ignore protection

- Priority: **High**. Confidence: High. Runtime/exploit behavior NOT VERIFIED.
- Evidence: whatsapp-bot-deploy/config/cache-warmer.js:24,257-294; config/multi-tier-forwarder.js:25-28; .gitignore and bot .gitignore.
- Impact: warming-state.json follows __dirname; current git check-ignore does not match that generated name. Real state was not opened; exporter still denies unapproved paths independently.
- Recommended action: Document/configure separate private state root in later scope; add reviewed ignore/preservation regression under security authorization; no real state move now.
- Dependencies/gates: P8/P9/P10, owner retention/recovery plan.
- Jira: ZL-13, ZL-30, ZL-48. ZL-12 remains an affected-move hold; fixes are separately reviewed. Do not bundle the fix with an unrelated path migration.

### TD17 — Scheduler/comment/diagnostic drift

- Priority: **Medium**. Confidence: High. Runtime/exploit behavior NOT VERIFIED.
- Evidence: whatsapp-bot-deploy/config/auto-refresher.js:21,224; config/cache-warmer.js:308-323; test-scheduler.js:9-27.
- Impact: Interval property and fixed cron differ; diagnostic targets 00:30, actual refresher 01:00. Process timezone/deployed scheduling unverified.
- Recommended action: Define schedule/timezone/manual/stop semantics and fake-clock assertions; do not execute diagnostic as correctness proof.
- Dependencies/gates: P8, explicit owner requirements.
- Jira: ZL-30, ZL-35. ZL-12 remains an affected-move hold; fixes are separately reviewed. Do not bundle the fix with an unrelated path migration.

### TD18 — Untested queue/coalescing/cancellation semantics

- Priority: **Medium**. Confidence: High source; behavior NOT VERIFIED. Runtime/exploit behavior NOT VERIFIED.
- Evidence: whatsapp-bot-deploy/config/upload-queue.js:33-73,111-135,163-170; multi-tier-forwarder.js:31-35.
- Impact: Dedupe excludes recipient and uses in-memory sets; clear resets keys while tasks may remain active. Promise settlement, restart and shared warming races untested.
- Recommended action: Add fake tasks/clocks/store tests, distinguish intentional coalescing from dropped recipient delivery; repair separately after contract review.
- Dependencies/gates: P6 and TD05.
- Jira: ZL-29, ZL-30, ZL-31. ZL-12 remains an affected-move hold; fixes are separately reviewed. Do not bundle the fix with an unrelated path migration.

### TD19 — Dependency reproducibility gaps

- Priority: **Medium**. Confidence: High. Runtime/exploit behavior NOT VERIFIED.
- Evidence: whatsapp-bot-deploy/package.json/package-lock.json; Zinema.lk Website/fb-video-api/package.json; Zinema.lk Website/package-lock.json.
- Impact: Extractor has no adjacent lock; orphan website lock has zero packages/no manifest. Declared dotenv/mysql2 are not evidence current entry imports them; bundled PHPMailer restoration is not centralized.
- Recommended action: Establish per-service package ownership, locked install/license/audit plan separately; no upgrades or installs during movement.
- Dependencies/gates: Owner/source provenance and offline/isolated dependency policy.
- Jira: ZL-13, ZL-31. ZL-12 remains an affected-move hold; fixes are separately reviewed. Do not bundle the fix with an unrelated path migration.

### TD20 — Missing runtime and regression baseline

- Priority: **High**. Confidence: High. Runtime/exploit behavior NOT VERIFIED.
- Evidence: scripts/check-source.cjs:1-35; scripts/test-public-export.cjs; current nonprivate tooling inventory.
- Impact: Parser checks and export tests do not verify application routes/schema/auth/player/delivery. No first-party unit/integration/e2e or CI setup identified.
- Recommended action: Implement isolated fictional DB/media/HTTP/clock/socket fixtures and route baseline before high-risk movement; record known failures honestly.
- Dependencies/gates: Environment/test prerequisite to ZL-12 affected slices.
- Jira: ZL-13, ZL-31, ZL-51. ZL-12 remains an affected-move hold; fixes are separately reviewed. Do not bundle the fix with an unrelated path migration.

### TD21 — Unknown external/dynamic consumers

- Priority: **Medium**. Confidence: High uncertainty. Runtime/exploit behavior NOT VERIFIED.
- Evidence: whatsapp-bot-deploy/config/database.js; Zinema.lk Website/includes/functions.php; standalone browser modules; metadata-only ignored alternatives.
- Impact: No literal import is insufficient evidence of disuse; DB-stored URLs, direct entry/CLI/cPanel/bookmarks and dynamic loading may exist.
- Recommended action: Keep UNRESOLVED holds; privately inventory deployment/callback/job references with owner when authorized; no automatic deletion.
- Dependencies/gates: Consumer ownership and regression replacement.
- Jira: ZL-12, ZL-31. ZL-12 remains an affected-move hold; fixes are separately reviewed. Do not bundle the fix with an unrelated path migration.

### TD22 — Private data/account/media rights remain outside audit

- Priority: **High**. Confidence: High presence; contents unknown. Runtime/exploit behavior NOT VERIFIED.
- Evidence: docs/SECURITY_EXPORT.md (inventory, session and rights sections); excluded private boundaries.
- Impact: Actual env/session/log/dump/private media and preserved histories are not fixtures; exposure/rights and retention remain unverified.
- Recommended action: Keep excluded/in place; future data/account/rights review requires explicit owner scope; use fictional/legal samples only.
- Dependencies/gates: Separate security/retention/rights approval.
- Jira: ZL-33, ZL-52, ZL-53, ZL-54. ZL-12 remains an affected-move hold; fixes are separately reviewed. Do not bundle the fix with an unrelated path migration.

### TD23 — Offline CSP versus CDN asset assumptions

- Priority: **Medium**. Confidence: High source; runtime NOT VERIFIED. Runtime/exploit behavior NOT VERIFIED.
- Evidence: includes/environment.php:58-61; includes/header.php:27-31; includes/footer.php:24-26; catalogue/detail templates.
- Impact: Nonproduction CSP restricts external resources while some templates name CDNs; icons/styles/bootstrap-dependent UI may differ offline. Browser effects untested.
- Recommended action: Characterize headers/resource failures and approve legal self-hosted fixtures/assets; do not weaken containment merely to make a demo render.
- Dependencies/gates: P2/P5, license review.
- Jira: ZL-27, ZL-48. ZL-12 remains an affected-move hold; fixes are separately reviewed. Do not bundle the fix with an unrelated path migration.

### TD24 — Absent error/prototype/legacy route targets

- Priority: **Low**. Confidence: High. Runtime/exploit behavior NOT VERIFIED.
- Evidence: .htaccess:88-89; includes/header.php:46; js/collections.js:27; js/tv-series.js:23.
- Impact: 404.html,500.html,pages/movies.php and five referenced .html files are absent by metadata. Loading/callback reachability varies.
- Recommended action: Capture expected status/redirect and add reviewed compatibility/error behavior later; retain callers until mapped.
- Dependencies/gates: P2/P5 and owner URL contract.
- Jira: ZL-12, ZL-27, ZL-31. ZL-12 remains an affected-move hold; fixes are separately reviewed. Do not bundle the fix with an unrelated path migration.

### TD25 — Misleading scheduler success output

- Priority: **Low**. Confidence: High. Runtime/exploit behavior NOT VERIFIED.
- Evidence: whatsapp-bot-deploy/test-scheduler.js:22-27.
- Impact: Printed correctness claim has no assertions and proves no scheduled job behavior.
- Recommended action: Replace diagnostic role with real assertions in future tests; then consider preserved archive after approval.
- Dependencies/gates: P8 and B11 archive gate.
- Jira: ZL-31, ZL-35. ZL-12 remains an affected-move hold; fixes are separately reviewed. Do not bundle the fix with an unrelated path migration.

### TD26 — Token expiry/rate-limit configuration drift

- Priority: **Medium**. Confidence: High. Runtime/exploit behavior NOT VERIFIED.
- Evidence: includes/whatsapp_token.php:34-53; whatsapp-watch.php:78; includes/settings.php:124-126.
- Impact: Comment says ten minutes, helper default is 180, current caller passes ten; settings suggest limits but issuance call is commented out.
- Recommended action: Enumerate callers and specify authoritative expiry/limit behavior; fixture tests then separately approved repair.
- Dependencies/gates: TD02/P4/P6 and owner product requirement.
- Jira: ZL-30. ZL-12 remains an affected-move hold; fixes are separately reviewed. Do not bundle the fix with an unrelated path migration.

### TD27 — Export approval does not follow new paths automatically

- Priority: **Medium**. Confidence: High. Runtime/exploit behavior NOT VERIFIED.
- Evidence: scripts/prepare-public-export.cjs:8-12; docs/SECURITY_EXPORT.md (inclusion policy).
- Impact: Exact allowlist intentionally excludes new/unreviewed paths, including new architecture outputs; moving source without policy review can omit required package files.
- Recommended action: Review policy per approved structural slice, re-run synthetic manifest/hash tests, preserve deny-by-default and separate real-export approval.
- Dependencies/gates: P9, explicit path/license/security review.
- Jira: ZL-10, ZL-31. ZL-12 remains an affected-move hold; fixes are separately reviewed. Do not bundle the fix with an unrelated path migration.

### TD28 — Live bot can mutate private authentication state

- Priority: **High**. Confidence: High. Runtime/exploit behavior NOT VERIFIED.
- Evidence: whatsapp-bot-deploy/index.js:79-103,339-376; config/file-manager.js:290.
- Impact: If enabled, session auto-recovery unlinks account-state files and media adapter can delete files. This is source behavior, not an operation performed or approved here.
- Recommended action: Keep import/start guard and file/account operations behind explicit adapters; fake filesystem tests and private recovery plan before live use.
- Dependencies/gates: P6/P10 plus owner account authorization.
- Jira: ZL-18, ZL-28, ZL-54. ZL-12 remains an affected-move hold; fixes are separately reviewed. Do not bundle the fix with an unrelated path migration.


## Sequencing and ownership

1. Preserve guards/private boundaries and owner edits now.
2. Establish isolated test/runtime/schema contracts before promising move parity.
3. Clear TD01-TD07/TD10 and relevant route/media contracts for any affected
   high-risk component. Independent no-op leaf preparation need not await an
   unrelated live adapter.
4. Keep source-path compatibility distinct from fixing old behavior; document
   expected failures and stop the affected slice if no honest baseline is possible.
5. Implement fictional delivery before any live-provider recovery; planned AI
   requires trusted catalogue DTOs, auth/privacy and budget/fallback contracts.
6. Decide archives/unknown consumers, dependency restoration and rights only
   through explicit reviewed gates; no file is “safe to delete” in this register.

## Executed checks and limitations

- Direct parser checks: PHP 8.2.12, 101 first-party files; Node 24.13.1, 36
  first-party JS/CJS files; zero failures. Vendor code/inline JS/runtime excluded.
- 150 literal PHP include candidates resolve; three computed expressions trace
  to an existing admin/config.php. Actual include_path/CWD behavior is not tested.
- 16 unique literal bot API endpoints have matching bridge files.
- Eight named referenced HTML/prototype/error targets are absent by metadata.
  An absent target is not proof every client executes that link.
- Pure URL modeling shows relative fetch/action paths can resolve below slug
  routes. No HTTP requests or actual browser execution were used.
- Six SQL references inspected for structural identifiers only; no user dump,
  environment, session, log, key or private media content read.
- Root/component ignore rules were inspected; a direct git check-ignore check
  for the warmer state name is recorded in TD16. No rules are edited here.
- No network, install, project script/test execution, live service, scheduler,
  DB import or app/runtime test. Existing 32 export-test pass is historical
  ZL-10 evidence only, not app coverage and not rerun.
- Private/external/deployment/dynamic dependencies and every UNRESOLVED dossier
  remain **DEPENDENCY NOT FULLY VERIFIED**.

This register plus [target design](TARGET_ARCHITECTURE.md) and
[migration gates](MIGRATION_PLAN.md) form a static blueprint. ZL-12 is recommended,
not started. No publishing, Git mutation or automatic remediation is performed.

## Production extension: additional source finding and release gaps

### TD29 — Unsafe upload-derived thumbnail shell command

- Priority: **Critical for public upload exposure**; confidence High in static
  construction, runtime reachability/exploitability **NOT VERIFIED**.
- Evidence: [admin/trailers.php](../../Zinema.lk%20Website/admin/trailers.php):10–29.
  The upload extension comes from the supplied filename (:17); a path containing
  that extension is interpolated unquoted into an exec command (:29) after upload.
  The visible handler does not establish a strict extension/MIME/size allowlist.
- Impact: untrusted media/paths enter a shell and unbounded decoder in a web
  request; input parsing, command interpretation and resource exposure need review.
  No exploit payload, uploaded file or ffmpeg execution was used in this audit.
- Recommended future repair: isolate thumbnail/probe jobs, validate file magic/
  type/size/duration and identifiers, invoke an argument-array subprocess without
  a shell, bound CPU/time/memory and restrict egress/permissions.
- Dependencies: ZL-19 authenticated publisher controls, ZL-42 quarantine/validation,
  ZL-51 security regression; preserve legal fixtures and original source evidence.
- Jira: **ZL-42, ZL-51**; not a ZL-11 implementation or a path-only ZL-12 move.

The register now contains **29 source-backed debt items** (the original 28 plus
TD29). The 24 [production gap categories](PRODUCTION_GAP_ANALYSIS.md) also include
missing capabilities such as licensed rights control, HLS/CDN, operations/restore,
AI and voice. Missing proposed features are not mislabeled implemented defects.
Severity in that release-gap analysis can be higher than original refactor priority.

### Production scope versus current verification

Source facts support planning and threat boundaries, not launch approval.
Highest release blockers: canonical schema/repositories, secure accounts/admin/
uploads, verified content grants and publication, private authorized HLS/CDN,
reproducible staging/regression tests, monitoring/restore, privacy/security/load
and owner-approved release. AI/voice are planned; official messaging remains
CONDITIONAL with ZL-28/ZL-54, never assumed to be an approved CDN.

The owner-supplied story mapping replaces generic TBD placeholders above without
creating duplicate issues or assuming completion. Related epics/titles and all
**PROPOSED DEPENDENCY** relationships are in the [Jira map](PRODUCTION_GAP_ANALYSIS.md).
Official vendor capabilities/prices remain **REQUIRES CURRENT VENDOR VERIFICATION**.
Original parser checks remain historical evidence from this continuing ZL-11
audit; the production extension runs documentation/preservation checks, not live
application, database, cloud, model, encoding or messaging tests.
