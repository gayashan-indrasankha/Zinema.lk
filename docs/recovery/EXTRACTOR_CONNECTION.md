# Private extractor connection phase

Started 2026-10-25 from clean local commit 70af8a5. Authorized: private administrator
connection, local fake contracts and regressions; follow with bot and isolated local
staging rehearsals. Production/live integrations, recipients and publishing remain
disabled. The owner's repeated release sequence does not approve a live account,
recipient list, staging host purchase or publication now.

Before-change evidence (statically inspected): manage_shots.php embeds the extractor
base URL in browser JavaScript and calls its health/extract routes cross-origin.
Its server-side optional cache request has no service credential. fb-video-api
allows wildcard CORS and returns local results to unauthenticated callers. The
health UI marks any JSON response online, and interpolates upstream text as HTML.

Intended: browser -> same-origin administrator POST endpoint -> fixed configured
private extractor. Require a current administrator and CSRF; do not accept a
destination, credential or raw upstream response from/to the browser. Protect all
extractor routes with an independent server-only key. Validate bounded exact-host
Facebook source URLs, methods, JSON, deadlines and media-result URL policy; disable
redirects and live access. Return stable sanitized errors. Use the same client for
optional cache extraction when saving a shot, preserving disabled-mode storage.

The ordinary development stack remains disabled. A separate explicit local-only
Compose override enables the fake extractor in the internal namespace, shares a
generated private key and publishes no extractor port. Positive/negative HTTP
tests use actual PHP and Node, with no Facebook fetch or Chromium launch. Normal
development/quarantine checks must still refuse unexpected enabled adapters.

## Executed verification

Private HTTP contract 33/33; projected-client validation 14/14; actual cURL fault
transport 4/4; service concurrency/authentication fixtures 6/6 passed. The expanded
retained Node suite passed 63/63, retained Docker integration 88/88 plus OAuth helper
7/7, product 112/112 and disabled environment 21/21. Native results are recorded in
EXTRACTOR_CONNECTION_RETAINED_HTTP/SCHEMA_RESULTS/SERVICE_RESULTS.json.

Manual browser: fictional administrator signed in; Check Status displayed Local
fake online; Test URL Extraction displayed Local fake connection verified. No key
or private address appeared in the page. Stopping only the fake service displayed
Offline/extractor_unavailable. Restoring the baseline displayed extractor_disabled.
Sanitized screenshot: evidence/extractor-connection-manual.png. No real URL was
fetched; the fictional Facebook-shaped source is validation input only.

The first service fixture run was 5/6 because its GET helper sent an invalid body;
fixing that test transport produced 6/6. The first image build correctly refused
extractor sources excluded by the prior Docker context allowlist; only six named
source/lock files are now added. The first CLI transport probe lacked exported
container-exec configuration (not inherited from PID 1); explicit private-file
bootstrap corrected it, then 4/4 passed. No failed run was called a pass.

## Implementation and limits

`admin/extractor.php` rejects oversized requests before shared CSRF parsing, then
requires current administrator/POST/CSRF and a small fixed action schema. It accepts
no destination/key/headers. `includes/extractor_client.php` is shared by preview,
health and optional shot caching; fixed local destination, independent 64-hex key,
TLS verification, no proxy/redirect, 1s connect/4s total deadline, 8 KiB response
limit, JSON/status/media allowlists and stable errors. All live/production modes
fail closed. The ordinary development profile still requires disabled extraction.

The Node service binds loopback, requires the independent key on all routes,
accepts POST JSON only, returns no CORS permission, bounds body/concurrency/rate and
omits request/provider diagnostics. Its local adapter returns only original demo
media. The unreachable historical provider/browser startup code was removed from
the entry point; Git preserves it and pure transport/parser evidence remains.
This change does not enable or certify real Facebook extraction or Chrome.

The opt-in Compose override has no published extractor port, non-root/read-only
Node, memory/PID bounds and an internal namespace. It is a test fixture, not a
production deployment. Generated keys, seed tokens and runtime state stay ignored.
A pinned official Node image and cURL development package are recorded in
`dev/runtime-lock.json`. Registry availability and base-image/platform limits remain.

Use `python dev/local.py up`, `python dev/local.py extractor-up`, then
`python dev/local.py test-extractor`. Finish with `python dev/local.py down` and
`python dev/local.py up` to restore the ordinary disabled stack. Never run operator
cleanup/reconciliation while the opt-in fixture is active: its guards still reject
unexpected adapter modes. Do not reuse development images or aliases in staging.

This closes the **local fake connection** requirement. Private HTTPS deployment,
real source extraction, media expiry, independent host/network policy and browser
fallback remain staging/live gates. The platform is still NO GO for publication.


Final native verification: schema 22/22, HTTP 94/94, authenticated services 4/4
and retained Node subset 23/23; two scoped npm audits zero. Target guards 9/9 and
actual extractor isolation checks 12/12 passed. All owned services stopped.
Source syntax/parse details are in EXTRACTOR_CONNECTION_STATIC_RESULTS.json.
