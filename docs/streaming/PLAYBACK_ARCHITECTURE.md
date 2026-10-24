# Playback architecture - ZL-17

2026-10-24. Initial source audit and intended behavior before application changes.
Status: PARTIAL; production schema/rights/identity/media delivery remain unresolved.

SOURCE CONFIRMED: index.php selects linked_content_type/linked_content_id and emits
movie/<id>/watch or series/<id>/watch links. Movie details uses movies SELECT *,
updates views, interpolates video_url into a direct MP4 source, logs that URL and
writes movie_<id>_progress on each timeupdate. Series details separately embeds
video_url and episode_<id>_progress. js/video-player.js is a separate Doodstream
iframe wrapper, not an HLS player. No permitted HLS media asset or verified CDN
adapter exists. Historical owner reports do not establish runtime operation.

ZL-14 complete canonical/account DDL remains missing; ZL-15/16 are synthetic,
tested foundations with disconnected public factories. No private SQL or existing
media URL may fill those gaps. Existing local services are stopped, three volumes
retained. Starting 374 eligible files / 62 dirty paths; preserve all existing work.

Intended change: additive Playback services, validated linked-content resolution,
server-issued same-origin session, current per-resource rights/entitlement checks,
verified local fixture provider and unavailable default provider. Protect master,
variant, segment and captions, not just the first manifest. No CDN signing adapter
or origin claim without actual approved configuration. Explicit native-HLS/MSE
player, bounded recovery, opt-in session-local progress and safe metadata UI.
Integrate narrowly at movie Watch action and Shots links; preserve unrelated
legacy behavior/series handlers. Local test page uses the actual new controllers
and player with generated fictional records and HLS; no production fixture flag.

No Git writes, real media/production, unofficial delivery, extractor, payment,
cloud or ZL-18 work. Tests and final evidence will be appended after execution.

## Implemented path

Shots movie/series actions now resolve a validated Shot ID through shot-content.php
and GET api/playback/link.php. The server rechecks Shot preview eligibility and
canonical parent metadata before returning a fixed detail-route reference.
movie-details.php / series-details.php?catalogue=1 use an additive pure view,
without legacy database/admin bootstrap. Existing clean URL rewrites are unchanged.
The old movie Watch action now checks availability; its direct MP4 interpolation,
URL logging and unthrottled unscoped progress writer were removed. Series defaults
and the unrelated iframe wrapper are retained, not certified secure by this work.

The new view retrieves allowlisted details, then an explicit Watch action POSTs
type/id to api/playback/session.php. Playback Service reuses ZL-15 asset policy
and ZL-16 canonical/entitlement interfaces, independently checks the selected
film/episode asset and a ready provider digest, and issues a short-lived cookie.
Every master, variant, segment, caption and range request repeats authorization.
The public Factory is permanently unconfigured in this phase: no environment
switch exposes fixtures or guesses production identity/schema/provider settings.

LocalFixtureProvider is restricted to CLI/CLI-server and marked generated files
inside .recovery-private. It verifies exact hashes, names, size/count and manifest
references; the approved asset digest binds the complete package. This is a
synthetic byte-serving test adapter, not a production PHP media proxy or CDN.
SourceProvider / UnavailableProvider and the authorization/session boundary leave
production provider composition explicit; a future CDN adapter needs its own
verified descriptor and origin/edge tests, not removal of local safeguards.

Player state: idle -> authorizing -> loading -> ready/playing/paused/buffering ->
completed or error/expired/unsupported. Bounded load policies, watchdogs, two manual
retries, expiry stop and explicit teardown prevent endless recovery or origin
fallback. Native HLS is preferred; MSE uses pinned local hls.js. Details and error
text use textContent, not template interpolation of media or metadata.

Evidence: [executed tests](PLAYBACK_TEST_RESULTS.md), [HLS details](HLS_PLAYER.md),
[authorization](PLAYBACK_AUTHORIZATION.md), [progress](WATCH_PROGRESS.md) and
[operations](STREAMING_OPERATIONS.md). ZL-17 remains PARTIAL: no full canonical
schema, real licence/identity/grant integration, protected CDN or live legacy QA.
