# Production streaming architecture — Jira ZL-11

Date: 2026-10-23. **PROPOSED TARGET / NOT IMPLEMENTED / NOT RUNTIME VERIFIED.**
Read [current evidence](CURRENT_ARCHITECTURE.md), [production gaps](PRODUCTION_GAP_ANALYSIS.md),
[deployment](PRODUCTION_DEPLOYMENT.md) and [migration gates](MIGRATION_PLAN.md).
The extracted working tree is authoritative; no archive, media or service was used.

## Source basis and design decision

W07 currently renders native video from a database MP4 URL:
[ movie-details.php ](../../Zinema.lk%20Website/movie-details.php) lines 582–619
and browser-local progress keys. W15 stores cover uploads and catalogue metadata;
[admin/trailers.php](../../Zinema.lk%20Website/admin/trailers.php) lines 10–45
uploads a trailer, conditionally shells out to ffmpeg for one thumbnail, and inserts
a trailer record. This is **not an adaptive transcoding pipeline**. Lines 17–29
derive an extension from the upload name and interpolate unquoted paths into a
shell command: a source-confirmed unsafe construction, exploitability not tested.
Trailer status can be updated at lines 51–58; that does not establish rights-aware
publication. W16 requires a Facebook share URL for a Shot. No HLS player integration
or rights-field matches were found in the targeted first-party code search.

Retain PHP for title, identity, rights and playback decisions. Isolate media probing
and encoding in a worker with restricted privileges and resource limits. Deliver
large immutable renditions through a CDN backed by a private object origin, rather
than PHP, an extractor, local bot files or WhatsApp. Published catalogue IDs remain
stable; object keys are opaque versioned identifiers, never user-controlled paths.

## Minimum production and growth options

| Concern | Minimum production proposal | Growth only after measurements |
| --- | --- | --- |
| Control plane | Existing PHP app, canonical MySQL, one background job consumer with durable queue | More consumers with leases/backpressure; read/cache tuning when measured |
| Storage | One private object store, separate quarantine/master/rendition namespaces and least-privilege identities | Lifecycle tiers, selective replication based on recovery objectives and rights |
| Encoding | Managed job transcoder or isolated patched encoder worker; choose after pilot | Parallel jobs, per-title ladders, additional codecs if device/cost evidence warrants |
| Distribution | One CDN, private-origin authentication, scoped signed access | Multi-region or multi-CDN only with availability and cost justification |
| Player | HTML5 video; native HLS where supported, reviewed fallback library for other tested browsers | Advanced analytics/DRM only where licensed-content agreements require it |
| Operations | One staging lane, licensed fixture, queue failures, budget/egress alerts and restore drill | Automated capacity policies after load and failure testing |

AWS S3 / MediaConvert / CloudFront are candidate product families for object storage,
job encoding and edge delivery. Their appeal is separating media bytes and CPU-heavy
jobs from the PHP host under a coherent access model; operational complexity, regional
availability, support burden and total transfer charges must be compared with other
providers. Every current product capability, supported codec/region/limit, origin
policy, signing behavior and price is **REQUIRES CURRENT VENDOR VERIFICATION.**
No vendor documentation, price sheet, account or cloud credential was accessed.
A service name here is an option, not a verified selection or compliance assurance.
For a solo developer, initially choose one region, a small licensed catalogue, a
limited ladder and one CDN. Do not promise a free tier or unlimited streaming.

## Authorized ingestion and asset lifecycle

~~~mermaid
flowchart LR
  A[Publisher session and rights draft] --> B[Upload intent]
  B --> Q[Private quarantine]
  Q --> V[Validation and bounded probe]
  V --> J[Durable encode job]
  J --> R[Versioned private HLS renditions]
  R --> C[Review and rights publication gate]
  C --> D[CDN private origin]
  U[Viewer] --> P[PHP playback authorization]
  P --> S[Scoped short lived media access]
  S --> D
~~~

1. Publisher permission, MFA and CSRF protection precede an upload intent. Validate
   title/episode ownership, expected MIME/size/duration and rights evidence. Return
   a bounded, expiring upload capability for a single quarantine object; no public ACL.
2. Store the original checksum, expected and observed length, ingest ID, uploader
   internal ID, asset version and audit event. Verify completion server-side against
   storage metadata. Repeated completion requests are idempotent.
3. An isolated worker inspects magic bytes, container/codecs, dimensions, duration
   and tracks; enforces limits and rejects executable/polyglot/unexpected formats.
   Probe subprocesses use argument arrays, no shell interpolation, bounded memory/
   CPU/time and no arbitrary network access. A malware check is a separate gate;
   it does not replace parser sandboxing. Reject unsupported/encrypted input safely.
4. Commit asset state and an outbox event in one application transaction; a publisher
   forwards outbox events to a durable queue. Consumers use asset-version/job keys,
   leases, retry limits and a dead-letter queue. Verify provider callbacks with
   request authentication, replay bounds and job reconciliation.
5. Encode private HLS variants, audio and thumbnail/poster derivatives. Probe outputs,
   validate manifest/segment references and sample decodes; record checksums and
   track languages. Mark READY only when the complete version passes.
6. Human review plus licensed-asset/territory/window checks moves DRAFT/READY to
   PUBLISHED. An uploaded or encoded object alone is never publishable.
7. Publish an immutable manifest version by changing a small application pointer,
   not by overwriting a live manifest while segments are incomplete.

Proposed states: QUARANTINED → VALIDATING → QUEUED → PROCESSING → READY →
PUBLISHED; FAILED, REJECTED, UNPUBLISHED and RETIRED are explicit branches.
A job being accepted is not media readiness, and readiness is not publication.
Table names are conceptual, not migrations: asset/version, processing job,
track/rendition, publication, rights grant and audit records. Reconcile them with
canonical movies/series/episodes before proposing versioned schema changes.

## HLS, tracks and player migration

A pilot ladder could test 360p/480p/720p, adding 1080p only where the source, devices
and budget justify it; never upscale automatically. Bitrates, segment duration,
keyframe alignment, codecs and audio profiles are **design parameters requiring
fixture/device/bandwidth tests**, not validated production presets. Keep renditions
aligned for switching. Package master and variant manifests plus audio language
groups and accessible caption/subtitle tracks with explicit labels and defaults.
Validate subtitle file types, text escaping, timing, language and rights; an existing
“subtitled” catalogue label is not evidence of a playable subtitle track.

Adapt movie-details.php and series-details.php behind the future ContentAccess
boundary, preserving detail routes, title/episode IDs and existing resume keys
until a separately reviewed migration. On an explicit Play action, obtain an
authorized playback descriptor; attach native HLS or a pinned, reviewed fallback
player. Test autoplay restrictions, seeking, ABR changes, subtitle/audio switching,
mobile fullscreen, keyboard controls, captions and accessible errors using legal
fixtures. Use a safe DOM assignment; do not interpolate untrusted URLs into HTML.
Browser-local progress is optional device state; authenticated server history needs
a separately defined consent, merge, delete and multi-device policy.

## Proposed playback API — contract only

**POST /api/v1/playback-sessions** accepts a canonical content type/ID, episode ID
where relevant, asset version preference and client playback capabilities. Session
authentication and CSRF apply for cookie-authenticated requests; anonymous licensed
content may use a bounded anonymous identity and rate policy. No caller supplies a
storage key, arbitrary URL, user ID override or rights grant.

The backend verifies publication, rights window, territory policy, age restrictions
if applicable, identity/entitlement, asset readiness and abuse limits at request time.
Territory signals require an explicit lawful policy; never trust a client country
parameter. Return a typed result containing playback-session ID, allowed manifest
location, asset version, expiry, renewal hint, track metadata and authorized poster.
Prefer a scoped secure cookie or tightly scoped short-lived URL based on client/CDN
fit. Do not expose origin credentials. Deny with stable codes such as UNAVAILABLE,
NOT_AUTHORIZED, RIGHTS_EXPIRED or PROCESSING and a non-sensitive user message.

Renewal rechecks rights and entitlement; cap lifetime at the nearest rights expiry.
Avoid a per-segment PHP proxy. Protect manifest **and every segment, caption,
thumbnail and audio object**; a signed master pointing at public segments is not
secure delivery. Cache policy must preserve authorization while permitting shared
immutable bytes only behind verified edge authorization. Test cookie scope, CORS,
HTTPS, seeking/range behavior and cache-key isolation. Never log signed query
strings or session identifiers.

Signed access reduces unauthorized fetching; it does not prevent recording or
constitute DRM. If a license requires DRM, that requirement blocks publication
until an appropriate separately assessed system exists. Immediate revocation has
a defined maximum delay: short validity plus edge denial/invalidation where supported.
Urgent takedown disables new and renewed sessions first, blocks affected object
versions and verifies edge behavior; vendor invalidation semantics remain
**REQUIRES CURRENT VENDOR VERIFICATION.**

## Failure recovery, retention and reliability

Retry transient validation/job/status failures with bounded exponential backoff and
jitter. Keep durable job facts so a worker restart cannot duplicate publication.
Reject corrupt input; quarantine poison jobs and alert on oldest age/dead-letter
growth. Reconcile a lost callback using provider job state rather than blindly
submitting another chargeable job. A version pointer stays on the last authorized
healthy asset until a replacement is fully validated; never roll back to expired
rights or an unsafe asset.

Set distinct retention policies for failed uploads, raw masters, renditions and
audit evidence. Candidate policies must be approved against contracts, privacy and
restore needs; no automatic deletion is authorized here. Reference counting,
retention holds and a dry-run cleanup report precede deletion. Backup records and
masters needed for rebuilding; CDN caches are not backups. Encrypt backups and
test restoring metadata plus a legal sample asset.

Candidate first-launch objectives, **not achieved SLAs**: 99.5% monthly playback
authorization availability; p95 start under 5 seconds on a defined test network;
recover metadata within 4 hours with at most 24 hours of loss. Owner must approve
or revise these with hosting budget and rights constraints before choosing services.
Track start failure, startup time, rebuffer ratio, errors by rendition/device,
encode duration/failure, queue age and authorization denials. Use aggregate,
privacy-preserving telemetry rather than full viewing logs.

## Costs, capacity and anti-abuse

Monthly total = app/DB/worker baseline + stored GB-month + encoding minutes by
output profile + CDN GB delivered + requests + logs/backups + taxes/support.
For capacity arithmetic only: 100 viewers × 10 viewing-hours/month × 2 Mbps average
is approximately **900 GB/month** of payload before overhead (decimal units).
This is a synthetic workload, not measured demand or a currency quote. A two-hour
ladder totaling 5 Mbps stores roughly 4.5 GB per version, excluding masters, audio
and overhead. Multiply rates only after current official quotes and region are
verified: **REQUIRES CURRENT VENDOR VERIFICATION.**

Set approved per-day ingest minutes, maximum upload bytes, worker concurrency,
per-account playback/session rates and egress alerts before enabling public traffic.
Budget alerts are not guaranteed hard caps; define a tested degradation switch
(disable new uploads/encodes first, preserve existing safe playback as affordable)
and owner paging. Do not start Kubernetes, multi-region active-active, speculative
pre-encoding or additional codecs for an unmeasured launch.

## Future acceptance gates and Jira

ZL-37 owns pipeline/player delivery; ZL-40 owns rights/publication; ZL-3 owns canonical
relations; ZL-4 viewing UX; ZL-38 hosting; ZL-39 security/metrics/restore; ZL-8 release
tests. Existing story mapping: ZL-41 reference design, ZL-42 ingest, ZL-43 encoding,
ZL-44 CDN, ZL-45 player; see [the supplied Jira map](PRODUCTION_GAP_ANALYSIS.md).
Before implementation release: legal fixture ingest and repeat retry, corrupt and
oversize rejection, no shell injection, least-privilege origin denial, no public
segment access, expired/wrong-territory denial, concurrent renewal, callback forgery,
partial encoding, rollback, takedown, low-bandwidth/mobile/a11y playback, cost and
restore drills. All are **FUTURE ACCEPTANCE GATES / NOT EXECUTED** in ZL-11.

## 2026-10-24 - Implemented ZL-17 local boundary

The ZL-11 proposals above are historical design, not a claim that their production
gates have passed. ZL-17 now implements an additive authorized local HLS/session/
player path, without cloud infrastructure, real media or a production signing
adapter. Default public composition stays unavailable. Native desktop Chrome and
MSE mobile-viewport fixture playback were exercised; Safari/physical devices and
CDN/private-origin restrictions remain NOT VERIFIED. See
[current playback architecture](../streaming/PLAYBACK_ARCHITECTURE.md) and
[actual test evidence](../streaming/PLAYBACK_TEST_RESULTS.md).
