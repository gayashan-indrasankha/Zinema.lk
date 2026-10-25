# Zinema working-state assessment

This is the historical pre-repair baseline. Current local repair results are in [REPAIR_PHASE.md](REPAIR_PHASE.md); the original failing observations below are retained as evidence.

Date: 2026-10-25. Phase state: **PARTIAL — executed assessment, unresolved failures**.

## Verdict

Zinema is **partially functional and still needs development/recovery work**. It is not ready for production or a claim that the complete product works. Its core browsing and local playback paths can run, but security checks, several subsystems and bot delivery logic fail. Other delivery/commercial features are intentionally quarantined.

This conclusion comes from executed local tests and browser interactions, not just reading the code. It is not a production-site assessment, an exhaustive line-by-line review, or a percentage-of-completion estimate.

## Environment and isolation

- Existing PHP 8.2.12, Apache 2.4.58, MariaDB 10.4.32 and Node 24.13.1 were used. Docker could not be queried from the sandbox; native XAMPP binaries were sufficient.
- Dedicated Apache bound to `127.0.0.1:8088`, serving a code/style-only mirror. The actual project `.htaccess` ran in the mirror. Ancestor overrides were disabled in the dedicated Apache config.
- A new MariaDB data directory bound to `127.0.0.1:3307`; `@@datadir` and `@@port` were verified before every fixture SQL operation. PHP's test runtime set `mysqli.default_port=3307`. The separate PDO bootstrap has no explicit port setting and was not runtime validated.
- Extracted 21 CREATE TABLE and 45 ALTER TABLE statements from `Database.sql`. **Zero legacy data rows were imported.** Historical auto-increment counters were removed from the fixture. This is a schema compatibility experiment, not an approved canonical schema or database reconstruction.
- Seeded fictional `example.invalid` accounts, two movies, one collection, one series, two episodes and one Shot. Generated a four-second colour-pattern video with installed ffmpeg and an original SVG placeholder. No preserved sessions or private media were used.
- All website adapters were disabled. A restrictive test Content Security Policy blocked external browser assets, frames and requests. No real mail, OAuth, payment, WhatsApp, live Facebook extraction or production database was contacted. npm registry/advisory queries were the external development-service activity.
- Temporary extractor HTTP dependencies were installed under the ignored audit directory with install scripts disabled. Production application dependencies and source were not changed.
- Temporary Apache and MariaDB were stopped successfully; ports 8088 and 3307 were confirmed closed. Both extractor processes were also stopped. Browser viewport overrides were reset and the test tab closed.

## Executed automated results

| Check | Passed | Failed | Meaning |
| --- | ---: | ---: | --- |
| PHP syntax | 164 | 0 | Includes bundled PHP code; syntax only, no feature claim |
| JavaScript syntax | 37 | 2 | 39 non-dependency `.js` files, including retained auxiliary variants |
| HTTP/application assertions | 63 | 12 | Catalogue, accounts, interactions, admin, gates and security expectations |
| Node configuration/startup guards | 10 | 0 | Local restrictions and bot fail-closed startup |
| Bot modules with fake clients | 5 | 2 | Cache forwarding, file lookup, queue and failure/concurrency scenarios |
| Extractor local/disabled HTTP checks | 4 | 0 | Health, disabled extraction and local fixture response |

The 96 behavioral/guard assertions comprise 82 passes and 14 failures. These counts are **not a product completion percentage**: some passes verify that unavailable features are blocked. Node and HTTP regression commands return nonzero when the known defects remain. PHP syntax passing does not establish SQL compatibility, security or browser behavior.

Machine-readable evidence: [HTTP and syntax results](AUTOMATED_RESULTS.json), [Node/service and advisory results](SERVICE_RESULTS.json), [source-pattern inventory](STATIC_CHECKS.json). Node TAP records are under `evidence/`.

## Manual browser findings

| Journey | Observed result | Evidence classification |
| --- | --- | --- |
| Shots feed | Generated clip loaded, `readyState=4`, playback time advanced while unpaused | Runtime verified in browser |
| Shot → movie → Watch Now | Movie page opened and the four-second clip played through to its end | Runtime verified in browser |
| Movie delivery button | Displayed “Disabled during recovery” | Runtime verified; unavailable feature |
| Catalogue Dubbed tab | Removed the subtitled fixture from the result grid | Runtime verified in browser |
| Desktop search popup | Returned the matching fictional movie and its link | Runtime verified in browser |
| Series → episode two | Updated the delivery link to episode 2 and played the sample clip | Runtime verified in browser |
| Standalone login, unverified fixture | Reached authenticated `Audit_unverified` profile | Runtime verified security failure |
| Profile sign-out | Confirmation completed and returned to anonymous feed | Runtime verified in browser |
| Anonymous Save | Opened the login modal | Runtime verified in browser |
| Verified AJAX modal login and Save | Authenticated and saved the Shot; profile showed 1 of 1 saved records | Runtime verified in browser |
| 390×844 Shots and 1440×900 catalogue | Rendered and had no measured horizontal overflow | Limited responsive smoke check |

Screenshots: [movie playback](evidence/movie-playback.png), [episode playback](evidence/episode-playback.png), [unverified account accepted](evidence/unverified-account-logged-in.png), [desktop search](evidence/catalogue-desktop-search.png), [saved collection](evidence/saved-shot-profile.png), [mobile feed](evidence/shots-mobile.png), [disabled delivery](evidence/download-disabled.png).

The mirror intentionally omitted existing image/media files, including logos. Some original CDN assets are blocked by local policy. Missing logos/icons in these screenshots are fixture limitations, not independently established production defects. These checks do not certify all breakpoints, accessibility or all browsers.

## Prioritized confirmed findings

### P1 — Account verification differs by login route

`ajax_login.php:42-70` selects and enforces `is_verified`. `login.php:27` and `api/auth-handler.php:19` do not enforce it. With the same unverified fictional account and correct sample password, AJAX login denied access while the other two paths authenticated. The standalone-form bypass was also reproduced manually. Regression assertions fail for both alternate routes.

### P1 — Missing CSRF enforcement on alternate authentication and mutations

The JSON login handler authenticated without a CSRF token. An authenticated Shot comment POST without a token created a record. Authenticated admin movie creation without a token succeeded. `admin/movies.php:10` also deletes via GET: the test created a disposable movie, edited it and deleted only that generated record through `?delete=...`. The security expectations fail even though basic CRUD succeeds. Cross-origin exploitability and browser cookie behavior were not comprehensively tested; server-side token enforcement is demonstrably absent on these paths.

### P1 — Bot cannot broadcast a coalesced request

`whatsapp-bot-deploy/config/multi-tier-forwarder.js:618` references `chatId` outside its scope in `broadcastToWaiters`. The fake-client regression test throws `ReferenceError: chatId is not defined` when a waiting recipient exists. No WhatsApp connection was needed to reproduce the defect.

### P1 — Bot reports success without a valid upload acknowledgement

`whatsapp-bot-deploy/config/multi-tier-forwarder.js:534-536` returns `success: true` when `sendMessage` produces no valid message key. A fake upload returning `undefined` reproduces this false-success result. Delivery has not been established in that case. The regression expectation that it reject the unacknowledged operation fails.

### P1 review — Dependency advisories remain unresolved

An executed `npm audit` against a copy of the supplied bot lockfile reported **15 affected dependency entries: 2 critical, 10 high and 3 moderate**. Critical entries include Baileys and protobufjs. This is advisory matching against the lockfile, not proof of installed-tree equivalence or application exploitability. Exact advisory links and package names are retained in `SERVICE_RESULTS.json`.

The temporary extractor HTTP subset, pinned for this test to Express 4.18.2, CORS 2.8.5 and Got 11.8.6, reported 7 entries: 3 high, 1 moderate, 3 low. There is no supplied extractor lockfile or installed tree, so this is **not** a full vulnerability count for a deployed extractor. Chromium/Puppeteer were not installed or exercised. No automatic dependency fixes were applied.

### P2 — Trailer and movie-comment subsystems crash

`/trailers.php`, `/api/trailers.php` and `/api/comments.php?movie_id=1` returned HTTP 500. Generated local logs establish undefined `$pdo` and calls to `prepare()` on null. Their MySQLi bootstrap does not initialize PDO. `/admin/trailers.php` returned HTTP 500 because the supplied schema lacks `trailers`. Merely fixing the connection variable will not establish schema compatibility: additional trailer/comment tables and columns need reconciliation.

Shot comments are a different subsystem: their add/get test passed.

### P2 — View history does not enforce the intended deduplication

`api/record_view.php` uses `INSERT IGNORE` and says duplicates should be suppressed. The supplied `user_views` table lacks the necessary unique user/Shot constraint, and its ID also lacks the primary-key/auto-increment ALTERs present for other tables. Repeating the same view produced additional rows; six matching rows remained after three assessment runs. This finding is specific to the supplied schema fixture, not evidence about an unknown deployed database.

### P2 — Signup claims success after verification delivery fails

With mail explicitly disabled, AJAX signup created a new unverified fictional account and returned `success: true`. `ajax_signup.php:130-135` intentionally follows that path when mail fails. A user cannot complete the advertised email journey in this configuration. Successful real/local-mail verification and password recovery remain unverified; no real mail was sent.

### P3 — Auxiliary JavaScript syntax failures

`whatsapp-bot-deploy/baileys-connection-temp.js` fails with unexpected end of input. `whatsapp-bot-deploy/refresh-method-new.js` fails with unexpected identifier `refresh`. Both are retained auxiliary artifacts; neither failure alone proves the current bot entry point cannot parse. They should be classified/preserved before any later removal.

## What is unavailable or still unverified

- Downloads and payment endpoints return 403. WhatsApp watch/bridge and disabled OAuth return 503. These are verified recovery restrictions, not operational delivery/payment features.
- Extractor health works with temporary dependencies; disabled extraction returns 503 and local mode returns the configured sample URL. Actual Facebook extraction is quarantined and was not tested.
- Main bot startup correctly rejects the test environment before loading Baileys or saved authentication. Module tests do not verify real delivery, token redemption end to end, account compatibility, large uploads, multi-bot operation or reconnect behavior.
- Several admin pages render and a fictional movie CRUD lifecycle succeeds. This does not verify every admin form, file upload validation or bot-health function.
- `pages/` starter pages render but still contain sample/TODO wiring; successful rendering does not establish real starter download/trailer functionality.
- Dedicated gitleaks and trufflehog scanners were unavailable. The 203-file first-party PHP/JS pattern inventory is not a secret scan or vulnerability certification. It found no session ID regeneration call, which needs review in the authentication repair phase.
- Legacy session/auth directories and a legacy log still exist. Their values were not read or published. The original SQL data and media were not audited for privacy, rights or deployability.
- No load, soak, penetration, production-infrastructure, actual-mail/OAuth/payment, broad accessibility or cross-browser certification was performed.

## Changes, history and next task

No application behavior was changed. Added recovery tests, sanitized result files, screenshots and this report; updated recovery status and decisions. Generated services, credentials, sessions and data live under ignored `.runtime/`, with an extra Apache deny file. Original mirrored application hashes remained unchanged. Root and bot Git status both report **not a Git repository** in this checkout; the earlier record of a nested bot repository is historical and is not true of the current directory. No Git initialization, commit, push or deployment occurred.

Exact next recommended owner task:

> Start a local recovery phase to reconcile the canonical schema and repair the confirmed authentication/CSRF and bot delivery-result/concurrency defects in this assessment. Preserve existing work, establish traceable local version control after a secret/data review, use isolated sample services, and make the retained failing regression tests pass. Do not connect production integrations, publish, push or deploy.
