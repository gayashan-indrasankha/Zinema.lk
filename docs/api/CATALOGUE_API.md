# Catalogue API - ZL-16

2026-10-24 (Asia/Colombo). Initial audit/design recorded before implementation.
Current workspace: 352 eligible files, 40 existing changed/new ZL-15 paths, empty
index, branch feat/zl-13-14-environments-migrations at 958dd9e. Preserve all existing
uncommitted work. Local implementation/testing only; no commit/push or ZL-17.

## Source-confirmed legacy inventory

Paths below are relative to Zinema.lk Website/. GET describes the read handler's
observed use; existing handlers do not consistently restrict other HTTP methods.
All database/bootstrap/auth behavior below remains unverified end to end.

| Route | Parameters / DB / auth | Response and consumers | Risk / decision |
| --- | --- | --- | --- |
| api/movies.php | Read, no params; movies; MySQLi admin/config bootstrap, no per-title rights gate | Array with id/title/release_date/genre/cover_image/video_url/collection_id; js/main.js:93 | Unbounded; media URL/signing semantics conflict; retain shape unchanged |
| api/series.php | Read, optional id; series/episodes; same bootstrap | List or series object with seasons/driveId; js/tv-series.js:43, js/series.js:95 | No verified full schemas/rights; retain |
| api/collections.php | Read, optional id; collections/movies; schema-column probe | List/detail including driveId/video_url; js/collections.js:50, js/collection.js:113 | Dynamic date-field compatibility and origin references; retain |
| api/live_search.php | Read q/type; movies/series title LIKE, bound values, limit 10 | Bare array id/title/release_date/cover_image; _top_nav.php:214, movies.php:1234, tv-series.php:1112 | No rights filter, wildcard input and full schema unverified; retain caller contract |
| api/search_content.php | Read q/type movie/series/collection; session/admin search, bound LIKE, limit 20 | status/results envelope; admin/assets/admin.js:77, admin/manage_shots.php:647 | Internal admin purpose; must not repurpose as public rights API |
| movies.php, tv-series.php | search/genre/filter/sort/page; movies/series/views/created_at; LIMIT/OFFSET | HTML, direct catalogue browsing | Counts/metrics and DDL not verified; no page rewrite |
| index.php / social APIs | Shots plus users/likes/favorites/views; POST mutations and GET comments | Feed and social envelopes | Separate access/CSRF/schema work; no new writes |
| api/auth-handler.php, api/check-session.php | User session/account/subscription-expiry queries | Authentication/session JSON | users DDL and subscription semantics unverified; not an entitlement source |
| api/whatsapp/validate-token.php | POST token; guarded bridge, conflicting token/message/part contracts | Internal delivery response | Not a licence/user-entitlement authority; do not activate |
| admin/movies.php, series.php, episodes.php, manage_shots.php | Session flag check; CRUD/uploads and linked content | Existing admin HTML | No verified fine-grained RBAC; leave untouched |

movie-details.php:582-619 inserts a direct MP4 source; API/movie/series/collection
paths can construct legacy delivery references behind current guards. New metadata
APIs cannot secure old URLs. No protected origin/CDN claim is made.

## Dependency decision and implemented versioned boundary

ZL-14 still lacks complete permitted catalogue/account/admin DDL. ZL-15 supplies
tested synthetic rights-workflow tables, policy and ports, not a real licensed
catalogue or identity/territory provider. Do not invent movie/subscription tables,
permissions, seasons or popularity. Keep all legacy routes/callers unchanged.

Implemented one additive physical PHP entry, GET api/catalogue/v1.php, with validated
resource selection. Its public factory stays unconfigured and fails closed: there
is no environment switch exposing synthetic data. The actual controller/search/
entitlement implementation is assembled with fictional trusted adapters in tests.
No production identity, territory, access-rule or catalogue adapter is invented.

Search SQL uses only the verified ZL-15 rights-workflow projection, with an
independent canonical lookup port. This is explicit synthetic integration, not a
substitute canonical catalogue. No schema or index migration is planned. Reuse
ZL-15 validation/policy and preserve its files byte-for-byte.

## Implemented contract

- Resources: movies, series, episodes, shots, search, availability and playback.
  Lists/detail use validated canonical type/ID strings. Episodes/linked Shots
  require a separately eligible parent. No unverified season fields are emitted.
- Query: q, content_type, genre, language, release_year, max_runtime, sort,
  availability, parent_type, parent_id, limit, cursor; id where applicable.
  Resource selection is fixed separately. No client country/user/premium/role.
- Sort: title_asc/title_desc/release_year_asc/release_year_desc, then type/ID ties.
  No invented popularity or canonical added-date semantics.
- Page default 20, maximum 50. Signed short-lived keyset cursor binds query and
  trusted user/territory context. It contains only the last visible public sort
  tuple, no hidden row identity/count. No total count. Finite candidate budget.
- Rights and user entitlements are separate. Metadata, preview and playback
  eligibility differ; actual preview/playback delivery remains unavailable.
- Whitelisted JSON with string IDs, typed metadata/availability, correlation ID,
  private/no-store caching and safe errors. No private origin/evidence/grant data.
- Missing trusted adapters fail closed. A bounded process-local test limiter is
  not claimed to protect a distributed production deployment.

Execution details and measured results will be recorded in the companion API
guides; production acceptance remains PARTIAL if dependency gates stay unresolved.

## Request reference

All routes below are GET /api/catalogue/v1.php query variants, with no rewrite
framework. Default resource=search. The **actual public endpoint returns 503
CATALOGUE_NOT_CONFIGURED** for valid requests; success examples require the
explicit injected synthetic test composition, never a production enable flag.

| resource | Contract |
| --- | --- |
| movies, series, episodes, shots | List; add id for contextual detail |
| search | Cross-type list; content_type optional; detail requires type and id |
| availability | Requires content_type and id; safe availability only |
| playback | Requires content_type and id; 404 denied or 503 PLAYBACK_NOT_CONFIGURED |

Unknown keys, arrays, invalid UTF-8/control bytes, unsupported enums and conflicting
fixed resource/type return 400. No client identity, territory or approval input.
An id is a positive decimal string with at most 18 digits. Detail requests reject
list/search/pagination fields. No page-number API is implied.

| Parameter | Bounds / semantics |
| --- | --- |
| q | Optional title substring, trimmed/normalized, max 200 UTF-8 bytes; empty browse |
| content_type | movie, series, episode, shot; must match a fixed resource |
| genre | ZL-15 Validation::GENRES allowlist, exact genre membership |
| language | si, ta, en, hi, ko, ja, fr, es |
| release_year | Integer 1888-2200 |
| max_runtime | Upper runtime_seconds bound, integer 0-86400 |
| sort | title_asc default; title_desc, release_year_asc, release_year_desc |
| availability | discoverable default; preview_eligible, playback_eligible |
| parent_type / parent_id | Paired movie/series + canonical ID; episode parent must be series |
| limit | Integer 1-50; default 20 |
| cursor | Opaque signed navigation value, max 2048 bytes, 300 seconds; same context/query |

List envelope: data.items (array), data.nextCursor (string or null), requestId.
Detail envelope: data (object), requestId. Explicit DTO fields are id (string),
contentType, title, synopsis, genre (array), language, releaseYear (integer),
runtimeSeconds (integer), tags (array), availability (object), optional parent
{type,id}. Missing parent is omitted; no unverified nullable season/media fields
are invented. No total/hidden count, storage/origin URL, rights evidence or user ID.
See [availability field meanings](AVAILABILITY_CONTRACT.md).

Examples (fictional test composition only):

- ?resource=movies&q=Alpha&genre=drama&language=si&limit=20
- ?resource=movies&id=501
- ?resource=episodes&parent_type=series&parent_id=601
- ?resource=shots&parent_type=movie&parent_id=501
- ?resource=availability&content_type=movie&id=502

Next page: repeat the same filter/sort/limit and supply returned cursor. Cursors
encode the last **visible** tuple; they never authorize access. Data changes can
remove items between pages. No pagination snapshot guarantee is made.

Errors: {error:{code,message},requestId}. Codes/statuses: INVALID_REQUEST or
INVALID_CURSOR 400; AUTHENTICATION_REQUIRED 401 for inactive trusted account;
CONTENT_UNAVAILABLE 404 for hidden/missing/denied detail; METHOD_NOT_ALLOWED 405
(Allow GET); RATE_LIMITED 429 (Retry-After); CATALOGUE_NOT_CONFIGURED,
CONTEXT_UNAVAILABLE, CATALOGUE_UNAVAILABLE, SEARCH_BUDGET_EXCEEDED,
RESPONSE_LIMIT_EXCEEDED or PLAYBACK_NOT_CONFIGURED 503; unexpected error 500
INTERNAL_ERROR. Valid denied metadata is not revealed via a special 403.
All replies: JSON UTF-8, private/no-store, Vary Authorization/Cookie, nosniff and
fresh server-generated X-Request-ID/body requestId. Error text never uses raw
exception messages.

Legacy routes and all their callers remain untouched. This version does not
secure old media references or prove end-to-end frontend compatibility. See
[integration/rollback](INTEGRATION_GUIDE.md), [search](SEARCH_ARCHITECTURE.md),
[entitlements](ENTITLEMENT_POLICY.md), [security](API_SECURITY.md) and
[executed tests](CATALOGUE_TEST_RESULTS.md).
