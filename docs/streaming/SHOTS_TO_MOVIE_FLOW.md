# Shots to movie flow - ZL-17

The legacy index selects Shot id, linked_content_type and linked_content_id. Those
are source-confirmed reads, not complete DDL or proven associations. Previously
movie/series actions concatenated the target ID into a clean URL. Now the pure
Navigation helper accepts positive decimal IDs up to 18 digits and movie/series
only, and emits shot-content.php?id=<shot-id>. Existing collection links and
social/feed handlers are unchanged. Invalid movie/series references have no action.

shot-content.php requests /api/playback/link.php?shot_id=...; no browser-supplied
target is trusted. The service requires discoverable Shot metadata, authorized
preview assets, independent canonical association, an existing published eligible
parent and its metadata entitlement. Missing/deleted/draft/restricted/invalid or
preview-ineligible references fail safely with no target title or location leak.
No failed API request falls back to the legacy URL. The public factory currently
returns 503: this is an honest integration hold, not a working production feed.

A successful verified resolver returns movie-details.php?catalogue=1&id=<id> or
series-details.php?catalogue=1&id=<id>. The browser accepts only these fixed local
patterns, then loads the additive detail view. Existing default PHP routes and
Apache rewrites remain present. Movie details' old direct MP4 Watch function is
removed; the default page offers Check playback availability instead. The new
view has no enabled Watch action until its trusted details response says ready.

Local browser tests traverse the real Navigation helper, Shot resolver, detail
view, session controller and HLS player using generated fictional records. They
verify movie 501, no autoplay, decoded playback, seek, captions, progress and
recovery. Synthetic tests separately verify series mapping and episode sessions.
The local Shots page is a deliberately small fixture, not the legacy DB-backed
homepage. The full legacy feed, actual movies/users tables, previews, series
season navigation and production data remain NOT VERIFIED. No association is
invented in SQL and no production records are created. See
[test evidence](PLAYBACK_TEST_RESULTS.md) and [authorization](PLAYBACK_AUTHORIZATION.md).
