# ZL-16 executed verification

2026-10-24, Asia/Colombo. Status: **PARTIAL**. LOCAL TESTS: results below;
HOSTED CI: **NOT VERIFIED**. No commit or push, so historical hosted passes do not
verify these changes. Current extracted working tree is authoritative.

## Preconditions and environment

Read project/recovery records and actual ZL-14/ZL-15 implementation before design.
Starting branch feat/zl-13-14-environments-migrations, HEAD 958dd9eaf97c72ea6e76648eae6814d775f03641,
empty index; 352 eligible files including 40 existing dirty ZL-15 paths. Existing
ZL-15 work was preserved. ZL-14 remains a four-table verified subset; ZL-15 adds
only two verified synthetic workflow/audit tables. No full canonical/account DDL
or production identity/licensing adapter was established. Protected Database.sql
was not read, copied, scanned or reconstructed.

Host: Windows, PHP 8.2.12 (C:/xampp/php/php.exe), Node 24.13.1. Reviewed local
Linux image: PHP 8.2.35, Node 24.22.1, pinned MySQL 8.0 image from the existing
ZL-13 environment. No production database/media/provider or real users.

## Executed commands and results

| Command / scope | Actual result |
| --- | --- |
| PHP_BINARY=C:/xampp/php/php.exe; node scripts/verify.cjs (PowerShell env assignment) | PASS: 332 tests, 0 failures, 0 skips; 141 PHP / 48 JS parsers |
| node --test tests/catalogue/catalogue.test.cjs tests/catalogue/http.test.cjs | PASS: 86 tests (83 contracts + 3 HTTP) |
| node scripts/environment.cjs test ci | PASS: same 332 tests / 141 PHP / 48 JS, image runtime |
| Full eligible source parser loop: php -n -l / node --check | PASS: 202 PHP / 49 JS, no failures; excluded private inputs not read |
| node scripts/database-test.cjs ci content | PASS: 13 migration + 19 service assertions, actual concurrent editors one winner/one stale denial, excessive privilege gate |
| node scripts/database-test.cjs ci | PASS: 42 original DB assertions; PDO/MySQLi and repeated placeholders preserved; synthetic AES-256-GCM backup/restore and outage recovery pass |
| node scripts/database-test.cjs ci catalogue | PASS: 13 migration prerequisites + 24 catalogue assertions; excess reader privileges refused; DB outage fails closed and recovers |
| Local Apache GET /api/catalogue/v1.php?resource=movies | 503 CATALOGUE_NOT_CONFIGURED, no data, private/no-store |
| Local Apache POST new endpoint | 405 METHOD_NOT_ALLOWED |
| Local Apache forged is_premium / malformed id | 400 INVALID_REQUEST, no data |
| Existing environment up/health smoke | live 200, dependencies 200, ready 503 schema_unverified; CSS 200, sensitive paths/delivery/admin migration blocked 403 |
| Expanded Gitleaks 8.30.1 stdin --redact=100 | PASS at interim: 278 reviewed text inputs, no findings; final post-documentation scan below |
| actionlint 1.7.7 -shellcheck= -pyflakes= .github/workflows/ci.yml | PASS; shellcheck / pyflakes NOT RUN |

The parser/unit runner loads test-only data and a loopback PHP test server; it
does not start legacy application providers. Docker tests use newly created,
uniquely labelled internal-network/tmpfs databases, exact synthetic target
UUID/marker checks and generated local-only identities. Their finally cleanup
retains existing managed volumes. No migration runs against those existing volumes.

## Coverage and failures investigated

Executed contracts cover literal/Unicode/empty/malformed/injection-shaped search;
combined filters and runtime; allowlisted sorts; stable ties/first/middle/last
pages; cursor signature/context/filter/expiry; nonpublic states, missing/future/
expired/revoked/territory-limited rights; public/authenticated/grant/age access;
missing/expired/cross-user/territory-bound grants; unavailable context; parent
eligibility; preview versus playback; identical hidden/missing error shape;
field allowlists, JSON delimiter escaping, no-store, fresh correlation IDs,
unknown exception redaction, bounded quota capacity and invalid-request quota,
response/query/candidate bounds and malformed persisted aggregate denial.

Initial HTTP fixture tests failed because they invoked the intentionally CLI-only
ZL-15 workflow bootstrap in a web SAPI. The test harness now generates fictional
records through CLI and injects them into the pure HTTP controller. No production
bootstrap guard or ZL-15 source file was weakened. All three HTTP tests pass.

Initial SQL runs reached 22 assertions but failed before the first performance
sample: the expiry fixture advanced its clock to 2027 then rewound the same
limiter. CONTEXT_UNAVAILABLE was the correct clock-rollback denial. Performance
now uses a fresh request composition. A new regression verifies rollback denial;
no policy was weakened. Final full SQL, privilege and outage tests pass.

## Acceptance gaps and evidence classification

**Statically inspected:** legacy route/consumer map and unchanged source checks.
**Test-covered and executed:** policy/query/controller, real loopback HTTP and
verified synthetic workflow SQL (final catalogue result below).
**Runtime verified:** local Apache safe failures, isolated service connectivity,
readiness hold and guarded synthetic database suites.
**Not verified:** complete canonical schema, legal licences, live auth/session/
territory/age/grant provenance, real frontend end-to-end flows, production scale,
protected previews/HLS/CDN, production DAST and current hosted CI.

Legacy movie/search/Shot/admin pages are byte-preserved, not certified working.
Existing direct origin/MP4 exposure remains outside the new boundary. The public
factory is deliberately disconnected: isolated fixtures are not a live catalogue.
No popularity/season schema, subscription table, real grant persistence or new
index is invented. Process-local quotas are not distributed abuse protection.

Secret scans cover reviewed first-party text, not private SQL/environment/session/
user/log/media stores, binary content or Git history. They are not an exhaustive
security/privacy certification. No actual public export/publishing was performed.

See [integration and rollback](INTEGRATION_GUIDE.md), [SQL cost](SEARCH_ARCHITECTURE.md),
[security](API_SECURITY.md) and [availability](AVAILABILITY_CONTRACT.md). ZL-16 stays
PARTIAL until canonical/rights/auth/access integration and release prerequisites
are verified. Do not start ZL-17 automatically.

## Measured SQL performance and final image

Fresh disposable dataset: 524 fictional rows, 20 measured end-to-end service reads,
page size 20, alternating title ascending / release year descending. p50 70.69 ms,
p95 82.60 ms, maximum 102.12 ms; largest JSON response 8,886 bytes. No before/after
speedup or network/production SLA is claimed. Thresholds under 2,000 ms and at most
262,144 bytes passed. EXPLAIN search: ALL, no selected key, Using where / Using
filesort. Detail uses existing unique_content_reference. No new index/migration.
The finite candidate budget and SQL execution hint are safeguards, not proof of
large-catalogue efficiency; see [cost limits](SEARCH_ARCHITECTURE.md).

Final read-only, network-none, mount-free image inventory: exactly 237 reviewed
paths; no extra, missing, symlink or host SHA-256 mismatch. Runtime/context code
is unchanged after this audit. Security, final Git preservation and managed
teardown evidence is appended at handoff below.

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

## Jira-ready progress comment

ZL-16 PARTIAL. Implemented additive versioned catalogue controller, prepared SQL
search/filter/sort/keyset pagination, metadata allowlists and separate asset-rights/
requester-entitlement checks. The public factory stays safely unconfigured (503);
no real playable URL or guessed canonical/account/subscription schema. All existing
ZL-15/application source and Git history/index are preserved. Host/image: 332 test
passes, 0 fail/skip; catalogue MySQL: 13 prerequisites +24 assertions on 524
synthetic rows, privilege/outage checks pass; previous content/database regressions
pass. Measured page-20 p50/p95 70.69/82.60 ms, max 102.12 ms, max 8,886 bytes; search
still scans/filesorts. Security scan 279 reviewed text inputs passes with private
stores/history/binaries excluded. Hosted CI NOT VERIFIED. Canonical schema, real
rights, trusted identity/territory/age/grants, shared limits, protected delivery and
legacy browser verification remain blocking. Seven API guides and contribution/
recovery records updated; local services stopped with three volumes retained.
Recommend ZL-17 only after prerequisites; no later Jira task started.
