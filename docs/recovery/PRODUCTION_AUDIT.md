# Fresh-launch production-readiness audit

Date: 2026-10-25 (Asia/Colombo). State: **PARTIAL — NO GO for relaunch**.
Starting tree clean at 28e0ddf on zinema/local-dev-safety.

Owner intent: relaunch for real users with new data. CV/portfolio preparation and
legacy-data reconstruction are not objectives. Continue existing recovery work.

Authorized: inventory every discovered first-party route, administrator feature,
WhatsApp component and fb-video-api path; inspect source, manually exercise local
UI, execute/extend automated tests, repair confirmed defects, rerun regressions,
and prepare launch blockers, staging requirements and controlled live test plans.
No publishing/deployment, production connections, real recipients or live provider
tests are authorized. Prepare concrete live tests for approval. Public documentation
research/package checks do not authorize provider transactions or app integrations.

Evidence categories: statically inspected; test-covered (execution separate);
runtime verified locally; externally verified; not yet verified. Every inventory
row must identify evidence and gaps. Disabled features are not passing live features.
No finite suite proves all inputs or production readiness; report incomplete work.

Sequence: inventory and intended behavior -> baseline checks and browser journeys ->
confirmed defect reproductions -> bounded fixes/regressions -> final verification ->
security/staging/live-test handoff. Use only fictional accounts and original test
media in the owned isolated runtime. Preserve previous evidence and private state.
Fresh application data does not authorize deleting unknown local files/volumes.

Before behavior changes, record the failure, intended behavior and verification.
Treat a production relaunch as blocked while critical paths or security requirements
remain unverified. Backup/restore of future production data is an operational
requirement, not a request to reconstruct historical user data.

## Result and scope

The website and local recovery workflows have substantial executed coverage and
confirmed defects have been repaired. The platform is still in engineering/staging
work. **Do not republish yet.** Real delivery/extraction and operational acceptance
have not been established. Every feature has been inventoried by functional group;
every discovered feature has not been manually and externally tested. See the
[feature matrix](PRODUCTION_FEATURES.md), [92-entry source inventory](PRODUCTION_ROUTE_INVENTORY.json)
and [exact manual actions](PRODUCTION_MANUAL.md) for the difference.

Owner decisions: fresh data, free access, full movies/episodes via WhatsApp only.
Website catalogue, shots and trailers remain. Payments and full-content website
streaming are excluded from the first launch. Hosting is undecided; a modest beta
plus separate staging is an assumption. No capacity or hosting purchase is implied.

## Confirmed repairs

The [defect record](PRODUCTION_DEFECTS.md) preserves evidence and intended behavior.
Repairs include malformed-input/error boundaries, bounded trailer pagination,
nonexistent-target and inactive-trailer handling; collection-shot schema alignment
and linked-record validation; exact Facebook URL validation and TLS verification;
root-correct safe popup search and safe JSON-LD; shared serialized password/token
admission with explicit proxy trust; revoked administrator session handling;
read-only settings initialization with bounded effective token/rate settings;
WhatsApp-only content CTAs; internal PHP access denials; mobile administrator layout
and trailer navigation; content-hashed changed assets; and trailer playback,
pagination, deep links and failed-reaction rollback.

Bot component repairs cover exact administrator identity, bounded command queues,
health identity/timing, reconnect budgets/timer ownership and session preservation.
Future cache state is private. Actual PHP issuance, SQL claims and the Node client
were exercised with a fake sender. This does not establish live Baileys behavior.

The extractor now has pure fixture-tested parsing and bounded, DNS-pinned HTTPS
transport with redirect/address revalidation. The complete dependency declaration
is locked on Puppeteer 25.16.0/Node 24+ and audits clean. Live extraction and browser
fallback remain disabled; no Chrome binary was launched or provider page fetched.
The authenticated application-to-extractor staging path remains unfinished.

## Executed final verification

These suites overlap; counts must not be summed into a claim of unique features.

| Check | Executed result | Evidence |
| --- | --- | --- |
| Fictional product/API/admin CRUD and negative paths | 112/112 passed | PRODUCTION_PRODUCT_RESULTS.json |
| Password/proxy/configuration admission | 17/17 passed | PRODUCTION_AUTH_ADMISSION_RESULTS.json |
| Independent-process admission races | 2/2 passed | PRODUCTION_ADMISSION_CONCURRENCY_RESULTS.json |
| Real Node HTTP client + SQL claims + fake sender | 7/7 passed | PRODUCTION_BOT_INTEGRATION_RESULTS.json |
| Route access/error boundary sweep | 92/92 passed | PRODUCTION_ROUTES_RESULTS.json; not 92 functional success claims |
| Retained fake mail/OAuth/CSRF/upload/claim integration | 88/88 and OAuth helper 7/7 passed | PRODUCTION_INTEGRATION_RESULTS.json, PRODUCTION_OAUTH_UNIT_RESULTS.json |
| Operator/quota and quarantine/restore/fault/race suites | 57/57 and 51/51 passed | PRODUCTION_OPERATIONS_RESULTS.json, PRODUCTION_QUARANTINE_RESULTS.json |
| Environment and launcher target guards | 21/21 and 7/7 passed | PRODUCTION_ENVIRONMENT_RESULTS.json; dev/test_local_guard.py |
| Expanded pure/fake Node suite | 62/62 passed | evidence/production-node.tap |
| Delivery protocol/socket tests | 6/6 passed | evidence/production-token-delivery.tap |
| Fresh canonical v4 schema/claim bridge | 22/22 passed; 38 tables/2 views, zero legacy rows imported | PRODUCTION_SCHEMA_RESULTS.json |
| Retained native HTTP/security suite | 94/94 passed; no new PHP warnings/notices/fatals/deprecations | PRODUCTION_RETAINED_HTTP.json |
| Disabled/local extractor HTTP; retained Node subset | 4/4; 22/22 passed | PRODUCTION_SERVICE_RESULTS.json (Node subset overlaps 62) |
| Full extractor isolated dependency imports | 3/3 passed; browser not launched | PRODUCTION_EXTRACTOR_DEPENDENCIES.json |
| npm audit | Zero advisories in bot lock, HTTP-only extractor test subset and full upgraded extractor lock | PRODUCTION_SERVICE_RESULTS.json, PRODUCTION_EXTRACTOR_DEPENDENCIES.json |
| Source syntax/parse | 190 PHP, 47 JS, 11 CJS, 14 Python passed; all candidate JSON parsed | PRODUCTION_RETAINED_HTTP.json, PRODUCTION_STATIC_RESULTS.json |

Manual browser evidence includes fake OAuth/member saved-shot persistence,
catalogue/search, actual administrator movie create/edit, logout and responsive
navigation, and actual original-motion trailer playback/save. Screenshots and
limitations are in PRODUCTION_MANUAL.md. External shares/wa.me links were not sent.

Earlier executed failures are retained privately and explained in the defect
record: 67/74 initial product checks (one harness login expectation corrected),
85/92 initial route boundaries, 0/5 initial trailer UI regressions, a malformed
temporary security-test table, an initial transport address-list bug, and package
audit candidates with 9 then 3 high advisory-bearing packages. Adding the motion
fixture initially left the byte assertion pointing to the old red clip (111/112);
the expected original bytes were corrected and 112/112 reran successfully. This
was a fixture assertion mismatch, not a hidden transport pass. Restricted socket
attempts were rerun with local socket permission. None of these is erased or
counted as a successful initial execution.

## Remaining launch blockers, in order

1. Finish the private authenticated administrator extractor proxy/fake contract.
   Browser fallback needs a separately isolated, compatible runtime if required;
   keep it blocked until sandbox/egress/resource/concurrency tests pass.
2. Complete the bot scheduler, restart, disconnect, ingest and graceful-shutdown
   rehearsal with fakes. Verify durable original movie/episode/part storage and
   mappings, single-worker ownership and comprehensive log minimization. Component
   tests do not prove the entire entry-point event lifecycle.
3. Select a staging target and build an allowlisted application artifact with
   fresh schema/default/admin provisioning, TLS/proxy trust, least privilege,
   private storage and separate secrets. Development images/aliases/fakes/accounts
   must never be exposed as production. No staging target is provisioned.
4. Rehearse coordinated database/media/private-state backup and isolated restore,
   rollback, actual alert delivery and measured load/capacity on staging.
5. Obtain approval for the exact dedicated test accounts, original media and
   recipient allowlist, then run real mail/OAuth/Facebook/WhatsApp acceptance.
   Correct delivery/acknowledgement, replay, unavailable media, reconnect and held
   delivery handling remain externally unverified.
6. Complete content-rights, privacy/support/moderation/takedown and abuse operations,
   deployment security review and independent security assessment as appropriate.
   This audit is not a legal compliance determination or penetration test.

The concrete [staging requirements](PRODUCTION_STAGING.md) and
[controlled live-test plan](PRODUCTION_LIVE_TEST_PLAN.md) define acceptance evidence,
request/send caps and stop conditions. No live test is approved or executed by
this report. No deployment, remote push, publishing, hosting purchase, credential
rotation, real recipient or production connection occurred.

## Security and local handoff

Secret scanning and final Git/shutdown evidence are recorded separately in
PRODUCTION_SECURITY_RESULTS.json and STATUS.md. Scanning covers the staged and
complete candidate plus all local Git history, not ignored private legacy state.
Default scanner rules cannot establish absence of every secret. npm results are
time-bound; no container vulnerability scan/full pentest is claimed. Private
credentials, runtime logs, media and lifecycle journals remain ignored/preserved.
All owned test services were stopped; retained volumes were not erased.

Exact next recommended Codex task: **Implement and test the private authenticated
administrator-to-extractor proxy using only local fakes, then complete bot
scheduler/restart/ingest and log-redaction regressions. Keep all live integrations,
production access and publishing disabled.** Follow with staging recovery/load
rehearsals, then the prepared live approval gate. Do not combine publication with
those verification tasks.
