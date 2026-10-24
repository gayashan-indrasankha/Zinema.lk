# Legacy extraction - ZL-18

LEGACY / UNAPPROVED. Zinema.lk Website/fb-video-api/index.js retains HTTP extraction
and Chromium fallback source, Express routes, old URL validation and response
logic. New entry guard runs before Express/cors/got/Chromium imports or listener
startup, independent of mode/production flags. Both extraction functions also
check the central gate. The old local/disabled route implementation is retained
historically, not exposed as an alternate live bypass.

Known callers: index.php cached-CDN loader, admin/manage_shots.php optional cURL,
admin URL test/health JavaScript. The PHP mode is forced disabled; admin cURL also
checks central capability immediately before its action, and browser test/probe
functions return unavailable without a fetch. Legacy Shot markup withholds cached
origin/share locators and its loader/apply-source functions cannot use memory,
localStorage, DB or extractor fallback. Metadata, social interactions and verified
linked-content navigation remain; legacy previews show unavailable.

Existing extraction source is preserved; no scraping, browser extraction, remote
video or copyright material was executed/downloaded. The separate offline fixture
function accepts only original-pattern and returns a synthetic identifier, no
remote URL or media access. ZL-17 generated HLS/player/provider files are unchanged
and were browser-tested again. Local test media does not prove commercial rights.

Unresolved source concerns include historical permissive URL matching, provider
coupling and old browser-origin logic; quarantine is not SSRF remediation or rights
approval for reactivation. Do not enable those implementations by removing a gate
or changing flags. Future authorized ingest/provider replacement needs a separate
reviewed task and rights/provenance/URL-egress/security design. Private historical
variants and deployed schedules outside this checkout remain NOT VERIFIED.
See [controls](../security/FEATURE_FLAGS.md) and [tests](../security/QUARANTINE_TEST_RESULTS.md).
