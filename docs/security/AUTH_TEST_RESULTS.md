# Authentication verification - ZL-19

Status: PARTIAL. Results are local synthetic evidence, not production readiness.
Current date: 2026-10-24. No real identities, sessions, SQL dump or providers used.

## Executed verification

| Check | Result | Scope / limit |
| --- | --- | --- |
| Host node scripts/verify.cjs | PASS: 566 tests, zero failure/skip | Final executed run, PHP 8.2.12 / Node 24.13.1 |
| Pinned image node scripts/environment.cjs test ci | PASS: 566 tests, zero failure/skip | Final executed run, PHP 8.2.35 / Node 24.22.1 |
| Identity PHP contracts | 32 cases in final suite | Hash/validation/compatibility/rehash, registration, status, account/network throttle, recovery digest/purpose/expiry/replay, rollback/audit failure, change/revocation, session expiry, ownership/RBAC, MFA denial, CSRF, upload and HTTPS cookie parameters |
| Identity HTTP tests | PASS: 9 Node assertions/subtests including group | Real PHP loopback cookies, fixation/rotation, logout, account isolation, MFA denial after reset, request bounds and method/CSRF/Origin checks |
| Legacy signup controller | PASS: 1 | Actual source with synthetic expired account, unexpected SQL/mail tripwires; no real bootstrap/database |
| node tests/identity/browser.cjs | PASS: 18 assertions | Chromium 155.0.8059.85; genuine browser HttpOnly/cookie rotation/deletion, registration/verification/replay/isolation/CSRF/admin denial; zero unexpected requests |
| node tests/playback/browser.cjs | PASS: 49 checks | Actual original HLS decode/captions/progress, native desktop plus forced-MSE 390px; Safari/physical devices/production CDN NOT VERIFIED |
| node tests/quarantine/browser.cjs | PASS: 8 checks | No provider/API or external request |
| Read-only network-none image contracts | PASS: 176 tests | Final run passes; no host/session mounts, non-root, capability drop |
| Full eligible syntax | PASS: 235 PHP / 69 JS | Parser-only, including third-party eligible PHP; no application/provider execution |
| Managed CI read-only DB audit | PASS on repeat | Least privilege, zero application tables, schemaReady false; live/dependency/ready 200/200/503 |
| Audit/shutdown concurrent with offline fixture | REFUSED | UNOWNED_CONTAINER_REFUSED; offline container inherits Compose project image label but lacks managed service labels. Image-label inspection confirms this; sequential audit/shutdown required, ownership guard unchanged |
| Image content | PASS | Final 297 exact reviewed paths/hashes, no missing/extra/symlink |
| Gitleaks 8.30.1 expanded reviewed-text stdin | PASS: 364 inputs | Exit 0, no findings; redacted, private stores/history/binaries/generated media excluded |
| Actionlint 1.7.7 | PASS | shellcheck and pyflakes NOT RUN |
| Hosted GitHub Actions / fresh dependency advisory audit | NOT RUN | No staging, commit, push or hosted execution; no new authentication dependencies |

Host positive MIME/image inspection explicitly loads the existing PHP fileinfo
extension; the pinned image provides it. Other host cases use -n; unavailable
inspection fails closed. Header inspection is not malware scanning/full decoding.

The HTTP fixture has an exclusive state-file lock. The in-memory Store models
rollback and conditional epochs. Neither proves production SQL transaction locks,
unique constraints, multi-instance rate limiting or actual legacy DB compatibility.
No authentication migration is invented or applied. Existing ZL-14/15/16 disposable
SQL suites were not rerun for this no-SQL-change phase; their prior results remain
historical. Read-only managed DB state is checked separately.

Initial fixture failures were corrected before final passing execution: empty JSON
objects were misclassified as lists; unknown-route JSON and Windows signal cleanup
were incomplete; PHP cookie deletion uses an expired deleted value rather than an
empty literal. These were test-router/client assumptions, not passed security
results. The first fixture's owned synthetic temporary files were cleaned safely.
The disabled-issued-reset case was subsequently added and triggered final reruns.

## Acceptance coverage still blocked / not verified

Complete user/admin/password_resets DDL; real hash/username/collation compatibility;
production login/registration and session integration; distributed token/session/
rate-limit/audit storage; actual SQL recovery races; complete admin/API CSRF and
RBAC; canonical upload decoding/storage/ownership and scanner; mail delivery/outbox;
MFA enrollment, encrypted secrets, replay, one-use recovery, alternate-route bypass
and owner recovery; real HTTPS/proxy/canary/rollback; full legacy homepage/profile/
watch history/admin/database browser journeys. These are NOT passed by fixture
coverage. No mandatory requirement has been activated on real accounts.

See [architecture](AUTHENTICATION_ARCHITECTURE.md), [MFA blockers](ADMIN_MFA.md),
[rollback](AUTH_ROLLBACK.md) and [full execution report](ZL19_EXECUTION_REPORT.md).

Final preservation: 431 initial files all present, 417 byte-identical, 14 authorized
updates and 30 additions (461 total); no unexpected change/deletion. Prior contribution
history is an exact prefix. HEAD/tree/branch/local refs unchanged, empty index,
209 current dirty paths. All earlier Content/Catalogue/Playback, SQL and baseline
source evidence stays byte-identical. 136 inspected local links resolve, fences
are balanced and git diff --check passes (existing LF/CRLF advisory only).

Final cleanup inventory: zero managed/fixture containers and all three existing
managed database volumes retained. Owned synthetic HTTP/browser fixture servers
are stopped; no phase-created identity fixture directory remains.
