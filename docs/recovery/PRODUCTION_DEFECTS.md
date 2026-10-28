# Confirmed defects and intended behavior

Audit baseline: 74 product checks executed, 67 passed and 7 failed. Raw results
remain private in .runtime/local-dev/results/product-baseline-failures.json.
One failure was a harness expectation: find_movies.php is a quarantined diagnostic
and must stay 403, not become a public feature. Correct the assertion explicitly.

| ID | Evidence before change | Intended behavior |
| --- | --- | --- |
| P01 | Negative trailer pagination produces invalid SQL/500; huge limit accepted by executed HTTP tests | Integer page >=1 and limit 1..100; bounded offset, return 400 for invalid values |
| P02 | Executed record_view accepts a nonexistent shot; user_views has no FK | Return 404 before recording a nonexistent shot |
| P03 | Executed shot form accepts facebook.com.example.invalid | Parse HTTPS URL; exact Facebook host allowlist; reject userinfo, non-default ports, malformed and deceptive hosts |
| P04 | Executed collection shot creation returns 500; form/index support collection while shots enum supports only movie/series | Align structure and additive owned-dev migration with advertised movie/series/collection behavior; validate linked record |
| P05 | Browser search failed; source popup fetch/result links/submit are relative on rewritten detail URLs, and always query movies; query/title interpolated into innerHTML | Base all URLs on configured base, select current catalogue type, render untrusted text through DOM textContent and suppress stale results |
| P06 | Static inspection: no password throttle; login_attempts table unused; token limiter commented out and proxy headers trusted from arbitrary peers | Reproduce with bounded fictional attempts, then enforce shared DB-backed limits using socket peer identity; production proxy trust requires explicit configuration |
| P07 | Static inspection: shots extractor request disables TLS verification | Require TLS peer/host verification for future approved adapters; preserve disabled integration gate |

Do not enable live Facebook/WhatsApp/payment to turn a disabled-feature assertion
into a success. Provider access, production operational gaps and UI features without
executed evidence remain explicit launch blockers. See PRODUCTION_AUDIT.md.

P08: Executed fake Baileys health collection reports a null bot phone although the
socket has user.id. Intended: derive the base phone from the Baileys identity;
retain legacy client shape compatibility and never report QR/session material.

P09: Static inspection shows reconnect attempt counters reset inside each new
connection, periodic store writers/health monitors accumulate, and unreachable
recovery code targets an unconfigured session directory for automatic deletion.
Intended: bounded persistent backoff, one pending retry/store writer/monitor,
stop maintenance while disconnected, and preserve all session evidence. Verify the
reconnect controller with fake timers and no Baileys/network initialization.

P10: Static inspection: bot administrator authorization uses substring matching,
and !get command parts (including bearer tokens) are logged. Intended: exact
normalized administrator identity matching and no command/token value logging.
Other recipient/operational logging still requires a production retention review.

P11: Executed 92-entry-point GET boundary sweep: 85 passed, 7 internal partials/
helpers served directly as 200. This is an unnecessary public surface, not proof
of a credential leak. Intended: direct requests to internal PHP includes and
partials return 403 while server-side includes continue to work.

Dependency finding: resolving the complete fb-video-api declaration (including
browser dependencies) reported 9 high advisory-bearing packages. The earlier
zero-advisory extractor check covered only HTTP test dependencies. A separate
pinned browser-compatible upgrade candidate still has 3 high packages; it is not
a passing fix. The final full-lock candidate pins Puppeteer 25.16.0/Node 24+,
removes the incompatible bundled Chromium declaration and reports zero advisories;
three isolated imports passed. Keep extraction gated until runtime/network checks
pass. Private detailed reports/candidate failures remain preserved.

P12: Manual browser review measured administrator content width 1,271 px inside a
375 px mobile content viewport; the fixed sidebar obscures most form controls.
Intended: usable stacked navigation/forms at mobile width, constrained main content
and horizontal scrolling confined to wide data tables. Verify desktop and 390 px
browser viewports after the CSS repair; this is not a full accessibility certification.

P13: Static inspection: incoming bot command queue has no size bound and health
reads global.messageQueue although the queue is never published there. Intended:
bounded admission, observable queue size, sequential execution and failure isolation;
exercise with fake tasks/timers without Baileys.

P14: Static inspection: warming-state.json is written beside bot source and is not
explicitly ignored. Intended: keep future warming state inside configured private
session storage and ignore any legacy copy. Do not delete/import existing state.

Owner clarification: first launch is free access, with full content delivered via
WhatsApp only. Payment/subscription handlers stay disabled and are outside launch
acceptance. Shorts/trailers and catalogue browsing remain website features.

P15: Static inspection: settings reads perform CREATE TABLE and default INSERTs on
ordinary requests, conflicting with least-privilege production DB credentials;
three editable token/rate settings are not applied by the token flow. Intended:
schema/default setup belongs to verified bootstrap, reads remain read-only, known
numeric settings are validated and applied with safe bounded defaults.

P16: Static inspection finds trim(array) failures in public search/shot comment
inputs, unbounded shot comments and raw SQL exception details in catalogue JSON.
Intended: reject malformed/oversized inputs, return 404 for missing shot targets,
and keep client error text generic. Add negative HTTP cases before final rerun.

P17: Executed temporary fictional trailer status change: public detail API still
returned 200 for an inactive trailer. Original active state was restored after the
probe. Intended: inactive trailers are absent/404 for public readers and cannot
receive member interactions; authenticated administrator preview remains available.

P18: Static inspection: member sessions recheck account existence/verification,
but administrator sessions only check the presence of an ID. Intended: clear a
session whose administrator row no longer exists; exercise a newly created local
CLI session with a nonexistent fictional ID and preserve every real account.

P19: Static inspection: token availability uses a two-minute heartbeat window,
while bot assignment permits five-minute-old heartbeats. Intended: assign only
within the same fresh window, and test actual PHP token issuance through the Node
claim/delivery pipeline using a transaction-scoped fictional online bot. Commit
that fixture bot offline so no running bot is falsely advertised afterwards.

P20: Executed browser reload at 390 px still used the old fixed sidebar, although
an independent loopback HTTP hash comparison proved the new responsive CSS was
served. Styles had a month-long cache lifetime and unchanged asset URLs. Intended:
content-hash versions for administrator CSS and the repaired search script so a
relaunch/update reliably loads the new assets. Retest in the same browser session.

P21: Executed inert script-boundary fixtures failed on movie, series and collection
JSON-LD: all three allowed a title to close the structured-data script element.
The baseline returned 105/108 with exactly those three failures. Intended: serialize
complete JSON safely with HTML-hex flags, preserve decoded metadata and encode the
administrator linked-title prefill as JSON. Original fixture records were deleted
only through the exact-created-record HTTP lifecycle; no executable payload was used.

Mobile follow-up: movies/series/episodes/collections fit 375 px after the shared CSS
fix, but the shots table used a different wrapper and still overflowed to 864 px.
Add a scroll-only wrapper for that table, preserving the form/dropdown layout.

P22: Manual administrator navigation/source inspection found no link to trailer
management even though the feature exists and is automatically tested. Intended:
make Manage Trailers reachable from the common administrator sidebar; preserve
existing authorization and verify the link in the product regression.

P23-P25: Manual trailer feed stayed on a spinner with locally loaded original MP4s
(readyState 4, paused, no codec error). Five executable UI regressions failed:
missing muted/native playback controls, overlapping-page duplication, repeated end
pagination, no autoplay-denial recovery, and share links to nonexistent trailer.php.
Intended: quiet autoplay with explicit controls/fallback, append only new IDs,
stop at the final page, and deep links to the real feed. No live share was submitted.

P26: Static inspection of optimistic reaction failure handling shows the prior
like count is never restored, and guest buttons submit unauthorized mutations
without sign-in guidance. Intended: preserve exact prior state/count/text on
failure and send guests to the local sign-in profile without a POST. Saved labels
must reflect persisted status. Ten final trailer fixture regressions pass;
manual selected-motion playback and member Save passed. Decoration/loading layers
do not intercept the native controls; changed CSS/JS have content-hash URLs.

Final relevant results: product 112/112, route boundaries 92/92, security 17/17,
admission races 2/2, Node 62/62, Node/SQL fake delivery 7/7, native HTTP 94/94 and
schema 22/22. See PRODUCTION_AUDIT.md for retained suites and unresolved launch
requirements. A passing disabled/local assertion never substitutes for live proof.

P27: A screenshot caught the spinner returning during a fully buffered clip loop.
Intended: waiting does not cover a completely buffered frame; loadeddata/progress
clear the loader. A tenth trailer regression exercises both buffered-loop and true
empty-buffer waiting. This is a bounded player repair, not a codec certification.
