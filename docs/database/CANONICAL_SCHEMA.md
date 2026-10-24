# Verified partial schema - ZL-14

**PARTIAL, not the complete canonical Zinema database.** Only site_settings,
rate_limits, movie_parts and episode_parts have complete, independently permitted
definitions for this task. The owner explicitly limited migrations to these four.
No catalogue, account, social, token or future-feature definition is invented.

The authoritative definition of this subset is the four ordered files listed in
[migrations.json](../../database/migrations.json). Its SHA-256 values bind exact
LF file bytes. Runner metadata expectations are derived from those definitions,
not an independently maintained schema copy. All historical sources remain intact.

| Migration | Source and columns | Integrity and indexes |
| --- | --- | --- |
| 0001 site_settings | Embedded settings helper: id, setting_key, setting_value, description, created_at, updated_at | PK id; unique setting_key; original idx_key retained |
| 0002 rate_limits | Embedded rate limiter: id, ip_address, action_type, request_count, first_request_at, last_request_at, blocked_until | PK id; idx_ip_action(ip_address, action_type); idx_blocked |
| 0003 movie_parts | Historical CREATE: id, movie_id, part_number, message_id, file_name, file_size, created_at, updated_at | PK id; unique(movie_id, part_number); original movie/message indexes |
| 0004 episode_parts | Historical CREATE: id, episode_id, part_number, message_id, file_name, file_size, created_at, updated_at | PK id; unique(episode_id, part_number); original episode/message indexes |

All four use InnoDB and explicit utf8mb4/utf8mb4_unicode_ci, consistent with ZL-13's
local database policy. INT identifiers retain signed legacy semantics; no guessed
unsigned conversion is performed. Message identifiers are required VARCHAR(255).
Names/sizes remain nullable. Settings values remain TEXT, not a speculative JSON
or secret-management model. Original timestamp/default semantics are retained.

The embedded settings/rate-limit CREATE statements left collation implicit. This
new subset makes ZL-13's local unicode collation explicit; it does not infer the
collation of an unknown historical database. Adoption rejects a different actual
collation instead of automatically converting data or unique-key semantics.

The source's redundant nonunique idx_key and content-id prefix indexes are
preserved for compatibility, not claimed as optimizations. EXPLAIN on tiny
synthetic fixtures verifies available/selected keys only; no latency or throughput
improvement is claimed. Search/filter indexes for unresolved catalogue tables
cannot be selected without DDL and representative workload evidence.

No new FK is added from parts to movies/episodes: their complete permitted DDL,
key types and deletion semantics are missing. Part rows in a disposable fixture
therefore do not prove a valid parent catalogue item. No cascade deletion, new
one-time token model, speculative CHECK, authentication rule or data conversion
is introduced. Uniqueness/nullability are runtime tested for this subset; full
referential integrity remains blocked.

schema_migrations is infrastructure metadata, not an application feature table.
It tracks version, checksum, running/applied/failed status, attempts, timestamps
and a generic error code. recovery_test_target is a disposable-test safety marker
created by the harness, not a deployable application migration. Neither makes
the other missing application tables ready.

Current core and delivery gaps are listed individually in
[SCHEMA_INVENTORY.md](SCHEMA_INVENTORY.md) and [SCHEMA_EVIDENCE.json](SCHEMA_EVIDENCE.json).
The latter lists every identified consumer, observed column names and the exact
schema evidence still needed. SELECT * does not establish a complete definition.
Historical bot tables/views/routines are documented, not installed or silently
converted into the current model.

Future catalogue rights, entitlements, AI metadata, licensed media assets and
provider-neutral delivery require their own issue-scoped schema decisions. No
unused tables for those features are created here. Application readiness remains
503/schema_unverified; a four-table subset is insufficient to change that gate.
