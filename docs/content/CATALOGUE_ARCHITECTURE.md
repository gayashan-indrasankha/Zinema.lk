# Catalogue architecture - ZL-15

2026-10-24 (Asia/Colombo). **Implementation plan before source changes.** The
current extracted working tree is authoritative. Initial state: clean index/tree,
328 tracked inputs, branch feat/zl-13-14-environments-migrations at 958dd9e.
The ZL-15 request authorizes local implementation/testing, not commits or publishing.

## Source-confirmed current-to-target map

| Current evidence | Existing contract / risk | ZL-15 boundary |
| --- | --- | --- |
| `Zinema.lk Website/admin/movies.php:2-3,37-73,77-125` | MySQLi CRUD on movies; title/release_date/genre/rating/cover_image/video_url/language_type/collection_id; cover uploads under public uploads | Preserve source; proposed canonical adapter awaits full movies DDL |
| `admin/series.php:36-77,80-125` under the same website | series title/release/genre/description/cover/language; similar duplicated upload/delete behavior | Preserve; no replacement series table |
| `admin/episodes.php:36-73` | series_id, season_number, episode_number, title, description, video_url, thumb_image, air_date, duration | Episode reference must be individually verified; no inherited series rights |
| `admin/manage_shots.php:26-114,135-148` | Shots refer to movie/series/collection; share URL required; extractor conditional | New Shot rights remain independent; extractor stays disabled |
| `admin/trailers.php:10-58` | Public upload/thumbnail shell invocation; client-submitted status; separate trailers table | Do not execute or extend upload/status endpoints; independently approved preview rights |
| `admin/functions.php:3-10`; `admin/config.php:2-20` | Session flag gate, MySQLi bootstrap, no verified fine-grained RBAC; long session lifetime | Default-deny authorization port; no HTTP admin adapter until ZL-19 |
| `api/movies.php:10-32`; `api/series.php:18-54` | Existing catalogue response shapes and guarded signing; video_url has conflicting direct-URL / numeric interpretations | No response/field reinterpretation or route changes |
| `api/live_search.php:19-38`; `series-details.php:16-35` | Bound search / series-episode queries; no verified rights filtering | Add isolated eligible-metadata contract, not live search integration |
| `movie-details.php:582-619` | Direct MP4 source inserted in player; existing browser progress | Not secured by new metadata. Protected delivery remains ZL-44/45 |

Above paths without a full prefix are relative to `Zinema.lk Website/`.
These are static findings, not executed catalogue/admin/browser behavior.

## ZL-14 gate

The current migration manifest independently defines only site_settings,
rate_limits, movie_parts and episode_parts. Full movies, series, episodes, shots,
trailers, users and admins DDL remains missing; series versus tv_series and media
identifier semantics remain unresolved. PDO/MySQLi bootstraps differ and runtime
CREATE behavior is not repaired here. The excluded private Database.sql is never
opened. See [inventory](../database/SCHEMA_INVENTORY.md) and
[access evidence](../database/DATABASE_ACCESS.md).

The existing runner restricts its original loader to those four tables. Preserve
its safety rules, manifest and applied migrations. A reproduced parser defect
removed `(20)` from VARCHAR as well as integer display widths; its narrowly
scoped fix now normalizes INT/BIGINT only, with a regression test. An extension loader will
validate exactly two new rights-workflow definitions and combine them with the
original four only on a newly marked disposable synthetic server. Reuse the same
checksum/target/locking/ledger/drift/recovery engine; do not weaken original loading.

## Planned isolated design and persistence decision

- Pure PHP validation, policy and workflow outside the website document root.
- Inject trusted authorization, clock and canonical-catalogue lookup ports.
  Default adapters deny access; request/session data is not an administrator proof.
- A bounded versioned rights aggregate stores metadata drafts and separate logical
  holder, asset and grant collections linked by opaque IDs. This is a workflow
  projection for one optional canonical reference, not a new movie/series database.
- Two additive tables: content_rights_records for aggregate/version/canonical
  reference and content_rights_events for sanitized append-only actor/action audit.
  Optimistic compare-and-swap and audit insert commit in one transaction. Unique
  non-null canonical references prevent duplicate workflows; unassociated drafts
  cannot be published. No FK to any unverified catalogue/account table.
- Aggregate JSON in bounded TEXT is a deliberate interim persistence decision,
  not a claim of a fully normalized relational rights schema. Internal logical
  entities have distinct IDs/references. Shared holder normalization, database
  FK relationships and cross-title search await approved canonical contracts.
- Dedicated disposable service identity: SELECT/INSERT/UPDATE on records;
  SELECT/INSERT on events; SELECT on the synthetic target marker. No DDL, DELETE
  or audit UPDATE. Do not use an administrative migration identity for ingestion.
- Legal verification is a distinct human-review action with an opaque private
  evidence reference. Metadata entry never verifies ownership automatically.
- Public playback always remains denied until entitlement and delivery adapters
  exist. No HTTP endpoint, UI, file upload, remote fetch, media URL or AI provider.
  Synthetic workflow publication does not publish a real asset.

## Compatibility and release boundaries

Preserve every existing website/bot/extractor source byte and the original four
SQL migrations. Extend only reviewed local tooling/build-context inputs as needed
for tests. Do not migrate existing profile volumes, historical data or production.
Catalogue lookup and RBAC are test adapters until independently verified; no user
interface or live catalogue integration is claimed. Full acceptance must remain
PARTIAL if those integration gates are unresolved.

Dependencies on ZL-16 (catalogue/entitlement), ZL-19 (RBAC/CSRF), ZL-42 (authorized
ingest), ZL-44/45 (protected playback), ZL-52 (human licensing operations) and ZL-55
(territory/moderation administration) are **PROPOSED DEPENDENCIES**, not verified
Jira links or completion claims. See the final test/operations records for results.

## Execution follow-up

The planned isolated foundation is now implemented in Contracts.php,
Validation.php, Policy.php, CatalogueService.php, PdoRepository.php and bootstrap.php
under apps/web/src/Content/. Tests and explicit ContentMigrations/CLI bindings are
present; no legacy route calls them. Two new CREATE migrations have runtime-verified
metadata on a fresh synthetic server. Full catalogue/RBAC/admin integration remains
blocked, so ZL-15 is PARTIAL. Aggregate persistence is deliberately interim; no
fully normalized production schema or genuine licensed catalogue is claimed.

Final host/image suites pass 246 tests (145 existing +101 new) and 128 PHP/46 JS
context parsers. Rights DB fixtures pass 13 migration and 19 service assertions,
an actual two-editor race and excessive-privilege refusal. Original 42 database
assertions, synthetic backup/outage checks and the honest schema hold pass.
Details, failures, limitations and commands are in
[CONTENT_TEST_RESULTS](CONTENT_TEST_RESULTS.md). The site/bot/extractor and all
four original migration SQL/checksum records remain unchanged; the only existing
database-engine edit is the tested integer-width parser correction.
