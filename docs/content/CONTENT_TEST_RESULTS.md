# ZL-15 execution and acceptance evidence

2026-10-24 (Asia/Colombo). **PARTIAL.** An implemented and tested isolated rights
workflow does not establish a functioning, authorized live catalogue/admin system.
Missing schema/RBAC/delivery contracts are release blockers, not bypassed gates.

## Implementation and evidence classification

- **Statically inspected:** current catalogue CRUD, uploads, auth/session gate,
  MySQLi/PDO/API/detail/search contracts and ZL-14 limitations. Findings and the
  current-to-target map preceded application-module changes in
  [CATALOGUE_ARCHITECTURE](CATALOGUE_ARCHITECTURE.md).
- **Test-covered and executed:** input validation, scoped default-deny authority,
  independent holder/grant verification and publication review, state/version
  transitions, asset-specific policy, strict time/territory/channel rules,
  revocation, metadata discovery and immutable canonical associations.
- **Runtime verified on synthetic MySQL only:** two new workflow migrations,
  original-four extension/adoption, tracked failure recovery, drift/lock checks,
  bound values, durable records/audit, atomic rollback, least privilege and actual
  competing-editor behavior. No real licence or media was used.
- **Not yet verified:** production RBAC/CSRF, legacy catalogue browser/HTTP and
  account behavior, real canonical lookup/writes, actual media byte validation,
  real legal permissions, entitlements, protected CDN/origin and private evidence
  storage/retention. No hosted execution of these uncommitted changes is claimed.

## Commands, environment, expected and actual outcomes

| Command / check | Environment and expected outcome | Actual result |
| --- | --- | --- |
| `node scripts/verify.cjs` before changes, PHP_BINARY set | Host PHP 8.2.12, Node 24.13.1; retain original baseline | PASS: 145 tests; 115 PHP/44 JS parsers |
| `node --test tests/content/content.test.cjs` initial implementation | Same host; isolated PHP subprocesses, synthetic metadata only | PASS: initial 88 tests. Later hardening/preflight/parser cases are included in the final combined suite |
| `node scripts/verify.cjs` final | Same host; original 145 plus 101 new tests | PASS: 246 tests, zero failures/skips; 128 PHP/46 JS context parsers |
| `node scripts/environment.cjs secrets` | Digest-pinned Gitleaks 8.30.1; reviewed context only | PASS: 219 text inputs, no findings after the synthetic-fixture correction |
| `node scripts/environment.cjs up ci` | Local Linux Docker / Compose 5.1.4; reviewed build, loopback-only HTTP | PASS: builds/starts; live/dependencies 200, readiness 503 |
| `node scripts/environment.cjs test ci` | Image PHP 8.2.35 / Node 24.22.1 | PASS: 246 tests, zero failures/skips; 128 PHP/46 JS parsers |
| `node scripts/database-test.cjs ci content` | New labelled tmpfs-only MySQL 8.4.13, synthetic marker/UUID, no ports/persistent mounts | PASS: 13 migration +19 service assertions, actual two-editor race and overprivileged-role refusal; finally cleanup completes |
| `node scripts/database-test.cjs ci` | Different new synthetic server; original ZL-14 mode | PASS: 42 assertions, legacy PDO/MySQLi access probe, encrypted in-memory backup/restore, outage/recovery and cleanup |
| `node scripts/environment.cjs health ci` | Managed local CI profile; no schema migration | PASS: 200/200/503 plus CSS 200 and seven guarded paths 403 |
| `node scripts/environment.cjs audit ci` | Existing managed CI volume; metadata/connectivity only | PASS: least privilege, zero application tables, schema not ready |
| `actionlint -shellcheck= -pyflakes= .github/workflows/ci.yml` | Existing checksum-verified actionlint 1.7.7 executable | PASS; shellcheck/pyflakes NOT RUN |

No database check above is inferred from a mock. Policy/authorization fixtures are
separately labelled as fixtures; their success is not verified real RBAC or a legal
review. Final source/image/teardown checks are recorded at the end of this document.

## Representative synthetic scenarios

| Scenario | Expected and actual result |
| --- | --- |
| Fictional metadata draft, no real film or agreement | Stored only as synthetic draft; never grants public playback |
| Movie/series/episode/Shot with an approved fixture catalogue relationship | Valid metadata accepted; unknown ID/parent/type rejected |
| Missing licence, unverified holder/grant, pending approval | Publication or eligibility denied |
| Independently reviewed fictional movie, LK/web, active window | Rights eligibility passes; actual streaming still denied |
| Same grant requested in US or messaging | Denied; no implicit worldwide/multi-channel permission |
| Future/expired grant, inclusive start and exclusive expiry instant | Future/expired denied immediately without cron; start instant accepted |
| Revoked holder or grant | New authorization denied; workflow publication cleared |
| Trailer/poster/Shot/subtitle/metadata grant | Does not grant a different film/episode asset's rights |
| Two episodes under one series | One episode's grant cannot authorize the other episode's asset |
| Metadata-only eligible discovery | Safe bounded DTO, no private fields or locator, playable false |
| Stale reviewer/editor or duplicate canonical submission | Version conflict / duplicate reference; no overwrite or duplicate workflow |
| Competing real processes using the same expected database version | Exactly one commit and one stale rejection |
| Audit insertion conflicts after record UPDATE | Whole DML transaction rolls back; record version and audit count preserved |
| Service tries DELETE, audit UPDATE or CREATE; excessive grant added | SQL operations refused; overly privileged connection rejected |
| URL, traversal, arbitrary evidence path, invalid type/date/territory/HTML | Rejected before persistence/fetch; no upload/fetch exists |
| Malformed persisted date/JSON/state/role or missing review provenance | Denied or fixed-code corrupt-record error; no permissive fallback |

No actual movie, poster, trailer, subtitle, uploaded file or rights agreement was
used. Fictional licence records are explicitly not commercial agreements.

## Failure investigation, corrected rather than hidden

1. Initial context scan failed on a fictional setting-key literal. Local and
   container Gitleaks confirmed the same finding. Simplified that synthetic key
   and value; reran unchanged rules successfully. No allowlist or suppression.
2. First real migration run failed its new schema check. The existing parser
   removed `(20)` from VARCHAR as well as integer display widths. Added and ran a
   failing regression, then restricted normalization to INT/BIGINT. Four old SQL
   files/checksums/manifest remain unchanged; original 42 DB assertions pass.
3. First service fixture stopped before its first assertion. The native-prepare
   check accepted only boolean false, while the tested driver also reports zero.
   Accept exactly false/zero, retaining native prepares, and improve fixed-code
   fixture diagnostics. Migration identity and excessive privileges remain denied;
   corrected service runs pass. No guard was loosened to accept production.

Every failed temporary database run executed owned-resource cleanup. No unrelated
table, profile volume, private file or application behavior was repaired to conceal
a failure. The final policy also explicitly denies malformed persisted roles,
dates and missing independent-review provenance.

## Security and compatibility limits

Prepared values, fixed SQL identifiers, bounded metadata/paging, strict references,
default-deny authority, scoped IDOR checks, optimistic versions and transactional
audit are tested. Rights documents are not stored/served, and evidence references
are omitted from discovery/audit DTOs. SQL role tests establish append-only audit
access for the disposable service identity, not protection against database-owner
compromise. Aggregate relationships and status rules are service-enforced; new
tables have unique/not-null constraints but no FK or SQL status CHECK.

HTML/control input validation passes. No browser/render-time XSS, HTTP CSRF or
production RBAC test is claimed: no new HTTP endpoint/UI is exposed. MIME/hash
fields are declarations; byte/MIME sniffing, upload inspection, SSRF fetch controls
and private evidence storage remain future integration work. No remote fetch is
implemented. Existing public upload/MP4/image/trailer URLs are unchanged and can
bypass the disconnected policy. They are not secured by this task.

All legacy website/bot/extractor source, assets, dependency files and original SQL
remain unchanged. Static/hash and existing helper/export regressions establish
that preservation; full homepage/movies/series/Shot/admin/login/API browser/database
behavior remains BLOCKED by absent complete DDL and verified auth contracts.

## Acceptance and dependencies

Implemented/tested: isolated metadata registration, holder/asset/grant association,
territory/time/revocation rules, independent review/publication state machine,
server-side deny policy, scoped test authorization, audit/concurrency, versioned
test migrations and synthetic database persistence. Current-to-target design,
privacy limits, operations and contribution history are documented.

BLOCKED for complete acceptance: real authorized administrative ingestion/UI,
canonical catalogue/account schema and adapters, end-to-end existing route
compatibility, real legal review/evidence protection, complete entitlement and
media access enforcement, normalized production rights schema/relationships,
production backups/retention and streaming availability. Isolated fixtures and
documentation are not treated as COMPLETE. ZL-15 remains PARTIAL.

PROPOSED DEPENDENCIES: resume ZL-14 with independently approved sanitized DDL;
ZL-19 authentication/RBAC/CSRF; ZL-16 canonical search/entitlement integration;
ZL-42 authorized ingest/byte validation; ZL-44/45 protected delivery/player;
ZL-52 human licensing/evidence operations; ZL-55 territory/moderation administration.
No Jira relationship, external approval or issue completion is inferred. ZL-16 is
the requested next product issue after those prerequisites are resolved; it is not
started automatically. The excluded private Database.sql is never a fallback.

## Jira-ready progress comment

ZL-15 PARTIAL: implemented isolated PHP metadata/rights validation, default-deny
authorization and canonical-lookup ports, independent holder/grant and publication
review, asset/territory/channel/time/revocation policy, bounded eligible-metadata
DTOs, CAS updates and atomic append-only audit. Two additive workflow-table
migrations extend the verified four-table subset only on marked disposable test
servers; no catalogue/account definitions or FKs were invented. Real playback
remains denied and no admin/upload/public endpoint was added. Host tests pass 246;
synthetic MySQL passes 13 migration and 19 service assertions plus a real two-editor
race and excessive-privilege refusal. Original 42 database assertions and
backup/outage checks pass. Full schema, RBAC/CSRF, legacy route compatibility,
real licensing/evidence operations, entitlements and protected delivery remain
release blockers. No protected private files, real media, production, WhatsApp,
extractor, Git staging/commits/push or ZL-16 work.

## Final preservation, security and teardown handoff

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
