# Watch progress - ZL-17

The old movie path wrote movie_<id>_progress on every timeupdate in persistent
localStorage, without account separation. That direct player/writer is removed.
The separate legacy episode writer remains unchanged; no unverified account/history
schema is invented and no old keys are silently migrated or deleted.

New player progress is explicit opt-in and uses sessionStorage in the current tab.
It stores only finite position and saved timestamp; the key contains an opaque
server-HMAC context scope, type, canonical ID and record version. It never stores
session credentials, origin, title or raw user ID. Different trusted users/scopes
or versions do not share keys. No progress is read until opted in; opting out,
Restart and completion remove that key. No database/network request is made.

Periodic writes are at most once per five seconds, with a final pause/pagehide
save. Entries expire after 24 hours, invalid/future/out-of-range data is ignored,
position is bounded by media duration and completed media is not resumed. At most
32 versioned keys are retained in the tab. Storage denial/quota exceptions do not
break playback. Reauthorization happens before resume; saved position never grants
access. New tabs without copied sessionStorage start independently; browser tab
cloning can copy device state, so do not claim a secure shared-device history vault.

Server account/history integration, consent/legal policy, logout-wide erasure,
concurrency/merge and cross-device synchronization remain unresolved. The opaque
scope depends on a future stable trusted server key/identity, not a client user ID.
Current production factory has none and remains closed. Synthetic unit/browser
tests verify throttle, scope/version isolation, expiry, resume, restart, completion
and unavailable storage. See [results](PLAYBACK_TEST_RESULTS.md).
