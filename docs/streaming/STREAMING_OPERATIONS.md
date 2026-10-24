# Streaming operations - ZL-17

Use the current extracted working tree. Keep private historical SQL, environment,
sessions and media excluded. No production CDN/provider is configured. The public
Playback factory returns 503 and no configuration flag enables synthetic content.

## Safe local verification

Install exact test dependencies without scripts: npm ci --ignore-scripts --no-audit
--no-fund --prefix tests/playback/tools. Set PHP_BINARY to an available PHP 8.2+
CLI. Tests/playback/browser.cjs uses installed Chrome on Windows, CHROMIUM_BINARY
when provided, or the Playwright-installed Chromium elsewhere. Browser downloads
are test dependencies only; never publish their profile/cache or node_modules.

Generate original media with an available FFmpeg executable:
node scripts/playback-fixture.cjs .recovery-private/zl17-fixture.
FFMPEG_BINARY can select an explicit executable. The target must be empty and
inside the ignored private recovery directory. Inputs are fixed lavfi video/tone;
no external file/network/media URL is accepted. FFmpeg output is H.264/AAC HLS,
12 seconds, 10 files, with recorded SHA-256 package members. Ffmpeg version/platform
may affect encoded bytes; regenerate and independently bind fixture rights to the
actual generated digest. There is no production encoder/ingestion service.

Set ZL17_MEDIA to that generated directory, then run node tests/playback/browser.cjs.
The harness creates a new private synthetic state file and empty document root,
starts a loopback PHP server with a whitelist router, blocks external browser
requests, and finally stops its own process and removes only its owned temporary
state/root. The generated media remains ignored for review. No real account,
licence agreement, DB connection or live provider is used. Do not use this test
router/server as a deployment. CI has an explicit generation/browser step; current
hosted execution is NOT VERIFIED.

Windows execution here generated media using Debian-provided FFmpeg from the existing Debian
image in a temporary container; only the new fixture output folder and generator
script were mounted. Initial cap-dropped installation failed before generation;
a normal temporary installation container succeeded. The application image has
no FFmpeg install or media bind mount. See exact evidence in test results.

## Troubleshooting and operations

503 PLAYBACK_NOT_CONFIGURED is expected until reviewed composition exists. Do not
point it at private SQL or disable guards. 404 hides missing/unauthorized content;
401 requires new authorization; 403 indicates origin rejection. Error/retry UI is
bounded and never changes provider/source to circumvent a denial. Check safe state
codes and correlation IDs, not full cookies, descriptors, URLs or rights documents.
The player keeps at most 30 in-memory event/time entries; no analytics pipeline,
retention service or production dashboards are installed.

Local fixture hash mismatch, malformed manifest or missing file fails closed.
Recreate fixtures in a new empty private directory after verifying generator and
rights fixtures. Do not repair by serving files statically or dropping authorization.
The test provider permits only private .recovery-private data under CLI/CLI-server;
it deliberately refuses production Apache. All resources must go through policy.

## Rollback and future gates

No SQL/schema migration exists for ZL-17. Preserve the complete working tree first.
Revert only this task's reviewed delta: movie Watch replacement, Shot link helper,
additive catalogue view gates, new Playback/API/player/tests/context/CI/docs. Never
reset/clean Git or discard earlier ZL-15/ZL-16 work. Do not restore the old unsafe
MP4 player as an automatic runtime fallback. Keep new endpoints unconfigured or
disable them in a separately reviewed deployment. Stop owned local CI with
node scripts/environment.cjs down ci; retain all existing database volumes.

Before production: verify complete permitted canonical/media/account contracts,
legal publication, trusted identity/territory/age/grants, HTTPS/CSRF/CORS/cookie
scope, shared limits, approved ingest/processing and ready renditions, real CDN
key management/edge access and origin denial for every object, logs/cache privacy,
revocation/renewal, multiple tabs, load/reliability, real Safari/iOS/Android/tablet,
keyboard/fullscreen/a11y, release rollback and operational ownership. ZL-18/19 and
ZL-41-45 relationships are PROPOSED DEPENDENCIES, not verified Jira edges.

Manual devices: use original permitted fixture only; verify no initial audio,
Watch/seek/pause/mute/volume, keyboard focus and native controls, captions, rotation,
fullscreen, background/resume, network loss, expiry and denied renewal. Record actual
browser/OS/device and result. Safari and physical device results are NOT VERIFIED.
Do not start ZL-18 automatically; it is the recommended next separately authorized task.
