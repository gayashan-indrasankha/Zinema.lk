# Catalogue search architecture — ZL-16

Status: PARTIAL, 2026-10-24. Source-backed synthetic integration; no production
catalogue adapter. [API contract](CATALOGUE_API.md) and
[executed evidence](CATALOGUE_TEST_RESULTS.md) define the limits.

## Executed path

`api/catalogue/v1.php` → Controller → Query → SearchService → SearchRepository /
Access → explicit DTO → JSON. ContextProvider, AccessRules and Grants are trusted
ports. Access reuses the unchanged ZL-15 Policy plus an independent canonical
Catalogue lookup. The physical public factory deliberately has no configured
context or repository; it returns 503 without connecting to a database. Test
composition is separate and never enabled by a public configuration flag.

PdoSearchRepository reads only the independently verified ZL-15
content_rights_records projection. Its current data_class is synthetic. This is
not the unresolved movies/series/accounts canonical schema. No source field,
season, popularity score, alternative title or mood taxonomy is invented.

## Validation and SQL

Query accepts scalar UTF-8 strings, rejects unknown keys, ASCII control bytes,
invalid numeric forms and conflicting resource/type selections. Title query is
trimmed, whitespace-normalized and capped at 200 UTF-8 bytes. Empty q browses.
The raw query and decoded parameter totals are bounded at 4,096 bytes. Repeated
HTTP keys follow PHP parsing semantics; bracket-array inputs are rejected.

All SQL values, limits and cursor positions use native PDO placeholders. Sort
expressions, JSON paths, comparisons and tables are fixed application allowlists.
Literal LIKE escaping uses `!`: `!`, `%` and `_` become `!!`, `!%` and `!_`.
Quotes remain bound data. utf8mb4_unicode_ci supplies MySQL case/accent-insensitive
title comparison. Sinhala/CJK matching and accent behavior have SQL fixtures;
this is not a linguistic relevance ranking or semantic search engine.

Filters operate on verified metadata JSON: genre membership, language, year,
runtime upper bound and canonical parent pair; content_type and published state
use verified projection columns. JSON_VALID/CASE prevents malformed JSON from
becoming an unsafe expression; decoded aggregates are validated before policy use.
A malformed candidate fails the request closed rather than returning partial data.

## Pagination and cost

Default 20 / maximum 50 records; title ascending/descending and release-year
ascending/descending, followed by content_type and canonical ID ascending.
IDs use the existing varchar contract, so tie order is lexicographic, not numeric.
No offset, hidden total or estimated count. A signed 300-second cursor contains
only the last returned public tuple and a hash bound to filters, resource, limit
and trusted identity/territory/age context. The cursor is signed, not encrypted.
An integration must provide a stable secret key; the disconnected factory's
ephemeral key cannot support public multi-request pagination.

Rights/entitlements are reevaluated on each request. No snapshot consistency is
claimed when content changes between pages. Stable-dataset ties are tested;
deletion, revocation and expiry can remove records between requests.

At most 200 candidates are evaluated, with one overflow sentinel. If enough
eligible results cannot be established within that budget, return generic 503
SEARCH_BUDGET_EXCEEDED without partial items or a hidden-position cursor. Linked
episodes/Shots require an eligible parent; parent lookups add bounded queries.
The PDO connection is injected/reused. There is no cross-request access cache.

Each candidate SELECT carries MySQL MAX_EXECUTION_TIME(1000). LIMIT bounds returned
candidates, not rows examined: JSON extraction, leading-wildcard LIKE and sorting
can scan the table. Existing unique_content_reference supports detail lookup;
search may use ALL/filesort. No index or migration was added. Production scale
needs a reviewed canonical projection, representative plans, selective indexes
and load tests; 524 synthetic rows do not establish that capacity. The isolated
reader verifies target UUID/marker and exact read-only grants, and deliberately
refuses production targets rather than pretending to be a production repository.
