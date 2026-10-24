# Authorized HLS player - ZL-17

js/hls-player.js is a small standalone component; it does not replace the separate
legacy iframe wrapper. Native video controls supply play/pause, seek, volume, mute
and fullscreen where the browser supports them. The view adds accessible status,
Watch, Retry, Restart and explicit progress consent. Responsive aspect ratio,
44px action targets, focus indicators, text wrapping, playsinline and reduced
motion rules are provided. No audio plays merely by opening details.

After a user Watch action, the server must return the fixed approved descriptor.
The client rejects arbitrary URLs, nonlocal modes, MP4, expired descriptors and
missing scope/version fields. Native canPlayType(application/vnd.apple.mpegurl)
is checked first; otherwise Hls.isSupported selects MSE. Unsupported browsers get
a clear state without another media source. The self-hosted dependency is
[hls.js 1.7.3](https://github.com/video-dev/hls.js/releases/tag/v1.7.3), with Apache
licence, SHA-256 notice and exact npm integrity lock. See the project's
[capability API](https://hlsjs.video-dev.org/api-docs/hls.js.hls.issupported).
No runtime third-party script/CDN request is needed. Workers are disabled for this
small fixture/CSP boundary, not a measured production optimization.

The original 12-second test pattern has H.264 baseline/AAC, two aligned renditions
(320x180 and 160x90), 4-second MPEG-TS segments, a master and WebVTT captions. The
package is generated locally, never copied from old URLs. MSE has two adaptive
levels; native quality selection is browser-owned. No manual quality/audio UI,
multiple audio tracks, DRM or captions for real titles is promised.

Manifest/playlist/segment policies allow one internal retry per load policy;
startup/buffering watchdogs are 10 seconds. Session fetch is bounded at 8 seconds.
Fatal errors stop/destroy the source; two manual retries reauthorize, then stop.
Expiry pauses and removes the source. There is no public MP4/embed/origin fallback.
Teardown aborts pending authorization, clears timers/tracks/src, destroys Hls and
removes listeners. Events are a bounded 30-entry in-memory list of state/time only;
no URLs, tokens, user IDs, titles or analytics requests are recorded.

Browser execution verified desktop Chrome native HLS and a 390px viewport using
MSE (native detection deliberately disabled in that test), with decoded video and
captions. Safari/iOS, physical Android/tablet, hardware fullscreen/keyboard native
control interactions and production throughput remain NOT VERIFIED. Test-covered
native/MSE selection alone is not Safari certification. See
[actual results](PLAYBACK_TEST_RESULTS.md) and [manual checks](STREAMING_OPERATIONS.md).
