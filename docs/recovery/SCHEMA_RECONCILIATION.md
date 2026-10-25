# Canonical local schema reconciliation

Date: 2026-10-25. Verified against MariaDB 10.4.32 using fictional fixtures.

`database/schema.sql` is the data-free authority for **new isolated development/test databases**: 37 tables and two views. It was initialized and rebuilt from the final file in a second empty database. No legacy rows were imported or exported. It contains no database selection, credentials, live identifiers or environment-specific SQL definer. All text uses utf8mb4_unicode_ci; views run with invoker privileges.

The historical SQL files remain evidence. `Database.sql` contains 25 table definitions: the earlier assessment fixture extracted 21 plain CREATEs and skipped four CREATE IF NOT EXISTS definitions. The bot's original and fixed migrations are conflicting generations, not a sequence to import blindly.

| Conflict/evidence | Canonical decision | Executed verification |
| --- | --- | --- |
| mysqli and PDO had different bootstraps and no common port | Shared explicit DB_PORT; PDO consumers initialize PDO | Populated trailer/comment HTTP queries on port 3307 |
| `user_views`, `movie_comments`, `login_attempts` lacked complete identity definitions | Primary auto-increment identities; unique `(user_id, shot_id)` for views | Repeated view does not insert another row; comment write/reply lifecycle |
| API queried nonexistent `comments` while dump contained `movie_comments` | Keep one movie-comment store; add parent relation and alias comment_text as content for the API | Read/write/reply, ownership denial, owner deletion |
| Trailer code expected nonexistent poster/duration/table fields | cover_image is exposed as poster_url; catalogue description/duration fields and five trailer tables defined; video_url exposed as trailer_url | Populated list, like, page rendering and browser playback |
| Root token flags conflict with fixed migration's status enum | Keep is_active/is_used plus expiry; add assigned_bot_id and optional part_number | Valid, repeated and expired consumption tests; populated dashboard render |
| `movie_message_ids` was not a coherent defined store | Full files use whatsapp_message_ids; parts use movie_parts/episode_parts | Full/movie-part/episode-part add, read and cache update |
| Mixed file size units and operational columns | Preserve explicit bytes and MB columns; add required cache/counter timestamps | Multipart writes and statistics adapter checks |
| Full and multipart popularity/aging queries used incompatible joins | v_delivery_files normalizes full part=0 and unions both part tables; v_popular_content joins statistics on that key | Populated popularity and aging requests |
| Prototype query used `tv_series` | Use the established series table | Recent-content query executes |
| Bot selection depended on an unprovisioned stored function | Query recently online bots by queue size and bot_id in PHP SQL | SQL statically inspected; actual live assignment remains gated |
| Remember credential stored raw with no server expiry | Store SHA-256 digest plus remember_expires_at | Digest comparison, restoration and revoked-cookie replay tests |

Operational health/statistics/log tables from the fixed migration were reconciled with the existing table fields instead of replacing the token table. Older `video_tokens`/`forward_logs` structures were retained for provenance; the old direct-DB bot adapter remains disabled. No table was removed merely because it appeared obsolete.

The creation file was flattened from successfully executed structure, with counters removed, then rebuilt and verified again. This avoids requiring operators to discover an ALTER ordering across historical files. The final file's SHA-256 and successful rebuild results are in `REPAIR_SCHEMA_RESULTS.json`.

There is deliberately **no in-place upgrade script**. An existing database may contain duplicate views, inconsistent identities, data requiring mapping or different media/token conventions. Any migration of such data needs a separate authorized inventory, preserved evidence and validation. Never import the preserved dump or apply this creation file against an unknown database.

## Authorized local development follow-up (2026-10-25)

Canonical schema v2 adds hashed claim receipts, recipient binding, claim state and
claim timestamp to `whatsapp_tokens`. Expiry is explicit: updating a claim no
longer changes `expires_at`. The additive `002-token-claims.sql` migration exists
solely to upgrade a verified, owned development volume from canonical v1; the
development launcher checks its exact database identity before applying it.
It does not reconcile preserved legacy data or authorize any production upgrade.
Fresh schema rebuild and 22 bridge checks passed; the concurrent claim and expiry
checks are in `LOCAL_DEV_RESULTS.json`. Full-file fallback now joins part zero
explicitly. Token completion requires the claim protocol; legacy mark-used is
denied. Historical repair results above remain evidence of the earlier phase.
