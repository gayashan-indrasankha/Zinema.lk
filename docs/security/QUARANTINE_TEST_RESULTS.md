# ZL-18 executed verification

2026-10-24, Asia/Colombo. **PARTIAL**: reviewed live payment, extraction,
unofficial delivery and legacy external-media paths are quarantined; private/
production entry points and full legacy integration remain unverified.
LOCAL TESTS: PASS in the scopes below. HOSTED CI: NOT VERIFIED.

## Preconditions and environment

Started from the current extracted working tree, not a ZIP/HEAD replacement:
409 eligible inputs, 101 existing dirty paths; feat/zl-13-14-environments-migrations
at 958dd9eaf97c72ea6e76648eae6814d775f03641, empty index. Read recovery/project,
architecture/integration/environment and ZL-17 evidence before changes. No
tracked Gitlinks or nested .git at inspected eligible/component roots; private
ignored stores were not recursively inventoried. Prior tasks remain partial.

Host PHP 8.2.12 / Node 24.13.1; reviewed image PHP 8.2.35 / Node 24.22.1; Chrome
155.0.8059.85. No new provider packages installed or real environment/auth/session/
private SQL/media contents read. Existing original ZL-17 generated HLS was reused.

## Executed commands/results

| Command / scope | Actual result |
| --- | --- |
| node --test tests/quarantine/quarantine.test.cjs | PASS: 71 feature/module/callback/helper/simulator contracts |
| node --test tests/quarantine/http.test.cjs | PASS: 63 checks; 31 routes x GET/POST + safe status |
| PHP_BINARY=C:/xampp/php/php.exe; node scripts/verify.cjs (PowerShell env assignment) | PASS: 524 tests, 0 fail/skip; 161 PHP /63 JS context syntax |
| node scripts/environment.cjs secrets | PASS: reviewed context before final documentation; 275 text inputs then, 276 in final context |
| node scripts/environment.cjs up ci / test ci | PASS: 524 tests, 0 fail/skip, 161 PHP /63 JS image parsers |
| docker run --network none --read-only --cap-drop ALL --user 33:33, no host mounts, tmpfs /tmp, node --test tests/quarantine/quarantine.test.cjs tests/quarantine/http.test.cjs | PASS: 134 tests, 0 fail/skip; no private sessions or provider credentials mounted |
| node tests/quarantine/browser.cjs | PASS: 8 actual browser assertions, zero provider/API/external requests from disabled UI |
| node tests/playback/browser.cjs | PASS: unchanged ZL-17 journey, 49 actual native/MSE browser/protocol assertions, zero external requests |
| Full eligible php -n -l / node --check parser loop | PASS: 222 PHP /64 JS, zero syntax failures; private inputs excluded |
| node scripts/environment.cjs audit ci | DB available, least privilege verified, zero application tables, schemaReady false |
| node scripts/environment.cjs health ci | Live/dependencies 200, readiness 503 schema_unverified; existing asset/containment smoke passes |

Final expanded scanning, workflow/link checks, exact image inventory, source/Git
proof and local shutdown are appended below. No current hosted workflow ran; the
existing workflow discovers the new tests and runs quarantine UI after HLS checks.

## Executed denial and isolation coverage

71 contracts cover all four policy modes with missing/false/true/live/local,
malformed/case/whitespace/array/object values, rejected production activation,
unknown features and PHP/Node parity. Ten Node modules are directly imported in
missing and forced-production environments. Tripwires record zero network calls,
private-store reads, writes, timers and provider dependency imports. Actual bot/
extractor CLI startup emits only fixed disabled JSON with exit 1. Generic protected
callbacks recheck denial when invoked after flags change; independent token/group
helpers deny before random token/DB work with a fake connection count of zero.

The separate in-memory simulator tests cached-forward and synthetic-fallback,
deduplication, one-use tokens, cross-recipient denial, capacity, no transport and
mode recheck before dequeue. It cannot use a real number, URL or message/session
store, and never persists work for later live dispatch. No historical queue is
loaded, cleared or replayed.

HTTP tests force production/live approval flags and forged callback status,
subscriber, token, API/admin claims. All 31 existing host routes deny GET/POST with
403 safe JSON, no cookie or data leak and no temporary log/queue/session artifact.
Inside the managed image the withheld admin tracking source is absent: its two
requests verify 404 absence; the other 30 routes verify actual 403 handler denial.
That absence is not misreported as executed deployment of the withheld source.
Public capability GET exposes only four safe disabled statuses; POST is 405.

UI browser tests load actual integration-status.js, subscribe.js and the historical
iframe wrapper: delivery links have no href, payment/form action is disabled,
forged browser flags do not enable it and no iframe is created. Backend tests
independently prove bypass denial. Full legacy SQL-backed rendering remains
unverified; the UI test uses a small synthetic page.

HLS regression reuses actual ZL-17 controllers/view/player and original media,
covering Shot navigation, decoded native desktop and forced-MSE mobile viewport,
captions, progress, seek/restart/completion, origin/resource/range denial,
revocation/expiry, missing/corrupt media, retry and teardown. This proves no new
WhatsApp dependency in that local path, not Safari/physical-device or production
CDN compatibility. Latest startup samples 6,210 ms native /2,975 ms MSE are only
local regression observations, not a speedup or SLA.

## Actual local Apache results

Capability status 200/private-no-store. Subscribe/payment callback, token redemption,
cron and download handlers 403 INTEGRATION_DISABLED/private-no-store/no cookie.
New integration JS 200. Withheld admin tracking 404. ZL-17 catalogue-backed detail
view remains 200; playback session remains 503 PLAYBACK_NOT_CONFIGURED; catalogue
v1 remains 503 CATALOGUE_NOT_CONFIGURED. No private locator/token or usable external
credential is issued. Core liveness is independent of blocked providers.

## Failures investigated and evidence limits

Earlier regression tests expected a production flag to enable the bot and an old
DB guard message. Updated them to enforce the new permanent denial/error contract;
new tripwire tests prove no provider import. Initial image execution exposed the
same outdated message expectation; final image suite passes after correction.
An initial report parser failed on a console-symbol regex after network-none
execution; corrected the parser and reran, recording actual 134 passes. No failed
or skipped check is counted as a pass, and no guard was relaxed.

Static post-change [external call evidence](EXTERNAL_CALL_AUDIT.json) lists retained
patterns in 18 eligible targeted sources. Ten Node implementation bodies match the
exact recorded starting bytes after removing only new guards (including forwarding,
queues, refresh and extraction). This is not exhaustive analysis of ignored private
variants, package internals, deployed processes or third-party platform behavior.

No schema/database harness/rights/playback module change was made. Previous fresh
MySQL suites were not rerun in this phase; their prior results remain historical.
Full homepage/movie/series/auth/admin DB-backed flows cannot be verified without
missing permitted canonical/account schemas and trusted authority. Intentional
legacy preview/direct-media unavailability is quarantine, not a regression hidden
as a working feature. Mail/OAuth retain their previous guards and are separately
UNRESOLVED for production; no provider approval is inferred. Private historical
variants, unused main.min.js, real schedules, live callbacks and arbitrary owner-run
scripts remain NOT VERIFIED. Managed web egress is not globally firewalled; the
network-none proof applies to the tested container, alongside transport tripwires.

No production payment/message/extraction/media operation, real user contact,
credential rotation, cloud deployment, commit/push or ZL-19 occurred. See
[inventory](INTEGRATION_QUARANTINE.md), [controls](FEATURE_FLAGS.md),
[rollback](QUARANTINE_ROLLBACK.md), [WhatsApp](../integrations/WHATSAPP_STATUS.md),
[extraction](../integrations/LEGACY_EXTRACTION.md) and
[payments](../integrations/PAYMENT_STATUS.md).

## Final ZL-18 handoff verification

All 409 initial eligible inputs remain: 342 byte-identical, 67 reviewed authorized
source/tooling/documentation updates, plus 22 new files (431 total), no deletion.
Content/Catalogue/Playback, HLS/progress source, SQL/migration manifests and baseline
evidence are unchanged. Ten historical Node implementation bodies are identical
to initial bytes after removing only guard additions. Previous contribution history
is an exact prefix. Git HEAD/tree/branch/local heads/tags unchanged; index empty;
176 current dirty paths include all prior 101. No staging/commit/push/history write.

Final host/image full suite 524 passes, no failure/skip; context syntax 161 PHP/63 JS,
full eligible syntax 222 PHP/64 JS. Read-only network-none quarantine run 134 passes,
no host/session mounts. Actual unavailable-UI 8 and unchanged HLS 49 browser checks
pass with zero external operations. Final image exactly 279 reviewed host-matching
paths/hashes, no extras/missing/symlinks. Expanded Gitleaks passes 338 reviewed
text/JSON inputs; excluded private stores/history/generated media/binaries are not
certified. Actionlint passes; shellcheck/pyflakes NOT RUN; hosted CI NOT VERIFIED.
113 local links resolve, Markdown fences balanced and git diff --check passes
(existing line-ending advisory only). The flagged trailing space was corrected.

Managed CI audit retains zero app tables and readiness 503; owned services stopped
normally, zero managed containers, all three existing database volumes retained.
Network-none verification containers used --rm; temporary HTTP/browser servers
closed through owned cleanup. No ZL-18 fixture container/network remains. No real
session, payment, WhatsApp message, extraction, private SQL, production operation,
real media/recipient, credential rotation, deploy or ZL-19 work occurred.
ZL-18 remains PARTIAL for the explicit private/deployed and full-integration gaps.

## Exact ZL-18 file delta

Changed existing inputs:

- .dockerignore
- .github/workflows/ci.yml
- README.md
- Zinema.lk Website/_top_nav.php
- Zinema.lk Website/admin/cron/cleanup-tokens.php
- Zinema.lk Website/admin/cron/reset-daily-stats.php
- Zinema.lk Website/admin/manage_shots.php
- Zinema.lk Website/admin/whatsapp-bot-tracking.php
- Zinema.lk Website/api/payment-notify.php
- Zinema.lk Website/api/server-check.php
- Zinema.lk Website/api/subscribe.php
- Zinema.lk Website/api/whatsapp/add-message-id.php
- Zinema.lk Website/api/whatsapp/bot-error.php
- Zinema.lk Website/api/whatsapp/bot-heartbeat.php
- Zinema.lk Website/api/whatsapp/config.php
- Zinema.lk Website/api/whatsapp/get-aging-messages.php
- Zinema.lk Website/api/whatsapp/get-all-message-ids.php
- Zinema.lk Website/api/whatsapp/get-message-id.php
- Zinema.lk Website/api/whatsapp/get-message-ids.php
- Zinema.lk Website/api/whatsapp/get-movie-parts.php
- Zinema.lk Website/api/whatsapp/get-popular-content.php
- Zinema.lk Website/api/whatsapp/get-popular-files.php
- Zinema.lk Website/api/whatsapp/log-cache-warming.php
- Zinema.lk Website/api/whatsapp/log-forward.php
- Zinema.lk Website/api/whatsapp/log-refresh.php
- Zinema.lk Website/api/whatsapp/mark-used.php
- Zinema.lk Website/api/whatsapp/needs-refresh.php
- Zinema.lk Website/api/whatsapp/refresh-stats.php
- Zinema.lk Website/api/whatsapp/update-cache-message-id.php
- Zinema.lk Website/api/whatsapp/update-daily-stats.php
- Zinema.lk Website/api/whatsapp/update-forward-stats.php
- Zinema.lk Website/api/whatsapp/validate-token.php
- Zinema.lk Website/download.php
- Zinema.lk Website/fb-video-api/index.js
- Zinema.lk Website/find_movies.php
- Zinema.lk Website/includes/environment.php
- Zinema.lk Website/includes/whatsapp_token.php
- Zinema.lk Website/index.php
- Zinema.lk Website/js/main.js
- Zinema.lk Website/js/subscribe.js
- Zinema.lk Website/js/video-player.js
- Zinema.lk Website/pages/download.php
- Zinema.lk Website/series-details.php
- Zinema.lk Website/whatsapp-watch.php
- docs/CONTRIBUTIONS.md
- docs/SECURITY_EXPORT.md
- docs/architecture/CURRENT_ARCHITECTURE.md
- docs/architecture/WHATSAPP_DELIVERY_ARCHITECTURE.md
- docs/development/CI_PIPELINE.md
- docs/recovery/DECISIONS.md
- docs/recovery/STATUS.md
- infra/environment-files.json
- scripts/verify.cjs
- tests/database/migrations.test.cjs
- tests/environment/environment.test.cjs
- tests/refactor/leaf-contracts.test.cjs
- whatsapp-bot-deploy/config/api-client.js
- whatsapp-bot-deploy/config/auto-refresher.js
- whatsapp-bot-deploy/config/cache-warmer.js
- whatsapp-bot-deploy/config/database.js
- whatsapp-bot-deploy/config/file-manager.js
- whatsapp-bot-deploy/config/health-monitor.js
- whatsapp-bot-deploy/config/multi-tier-forwarder.js
- whatsapp-bot-deploy/config/upload-queue.js
- whatsapp-bot-deploy/index.js
- whatsapp-bot-deploy/package.json
- whatsapp-bot-deploy/src/config/environment.js

New inputs:

- Zinema.lk Website/api/integrations/status.php
- Zinema.lk Website/includes/integrations.php
- Zinema.lk Website/js/integration-status.js
- apps/web/src/Integrations/FeatureGate.php
- config/integration-policy.json
- docs/integrations/LEGACY_EXTRACTION.md
- docs/integrations/PAYMENT_STATUS.md
- docs/integrations/WHATSAPP_STATUS.md
- docs/security/EXTERNAL_CALL_AUDIT.json
- docs/security/FEATURE_FLAGS.md
- docs/security/INTEGRATION_QUARANTINE.md
- docs/security/QUARANTINE_ROLLBACK.md
- docs/security/QUARANTINE_TEST_RESULTS.md
- shared/integration-gate.cjs
- shared/offline-simulator.cjs
- tests/quarantine/browser.cjs
- tests/quarantine/http.test.cjs
- tests/quarantine/node-probe.cjs
- tests/quarantine/php-probe.php
- tests/quarantine/quarantine.test.cjs
- tests/quarantine/router.php
- tests/quarantine/routes.json

## Jira-ready progress comment

ZL-18 PARTIAL. Central PHP/Node policy denies live payment, extraction, unofficial
WhatsApp delivery and legacy external media regardless of flags. Added early
route/module/action/cron/token guards and safe UI/status, preserving historical
cached-forward/queue/refresh/extractor implementations and private data. Separate
bounded simulator has no transport or persistent work. Host/image 524 tests pass;
read-only network-none quarantine run 134 passes; actual browser unavailable UI 8
and unchanged authorized local HLS 49 checks pass. Tripwires observe zero external
operations/private reads/writes/timers/provider imports through tested entries.
31 host routes deny GET/POST; 30 deployed image handlers deny and withheld admin
route is absent/404. Expanded 338-text scan passes with private stores/history/
generated media/binaries excluded. Image exact 279 hashes; SQL/core playback/Git
state preserved, no commit/push/deploy. Seven guides plus static evidence and
contribution/recovery records updated. Local services stopped, three volumes
retained. Private historical/deployed entries, full canonical/auth/admin flows,
separate mail/OAuth approval and broader release verification remain unresolved.
Hosted CI NOT VERIFIED. Recommend separately authorized ZL-19; not started.
