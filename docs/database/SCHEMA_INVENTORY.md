# Schema inventory - ZL-14

Status: **PARTIAL**. Current working tree, including uncommitted ZL-12/ZL-13 work,
is authoritative. The excluded private Database.sql was not accessed, copied or
used. No historical SQL file is executed by the new runner.

## Scope and method

Reviewed six tracked SQL references and the current first-party query/connection
sources. The bounded quoted-string inventory inspected 134 legacy PHP/JS paths
and found 278 SQL-like candidates; UI strings were filtered from table findings.
This is static evidence, not a complete SQL compiler or proof of installed schema.
SELECT * and dynamic identifiers cannot establish a full column/type contract.
The machine-readable [evidence index](SCHEMA_EVIDENCE.json) gives consumer paths,
line references, observed column names and definition sources for all 39 named
tables/views (34 directly referenced in extracted queries).

## Historical sources

| Source | Evidence and decision |
| --- | --- |
| `Zinema.lk Website/admin/migrate_shots.sql` | ALTER shots video field only; base table missing. Not an installer. |
| `Zinema.lk Website/migrations/episode_parts.sql` | Complete episode_parts CREATE; represented in the partial migration. |
| `Zinema.lk Website/migrations/movie_parts.sql` | Complete movie_parts CREATE plus token ALTERs. Only the CREATE is represented in the partial migration. |
| `whatsapp-bot-deploy/database/migration_ultimate_system.sql` | ALTERs require absent base tables; bot/cache structures, routines and view are not a standalone installer. |
| `whatsapp-bot-deploy/database/migration_ultimate_system_FIXED.sql` | Status-only tokens plus parts/cache/bot structures; incompatible token fields and version-sensitive ALTER syntax. |
| `whatsapp-bot-deploy/database/schema.sql` | Older video_tokens / forward_logs / media_refresh_log family; not current bridge naming. |

Two additional complete definitions are embedded in includes/settings.php:104
and includes/rate_limiter.php:25. Those four independent CREATE definitions are
the entire new application-schema migration scope. Historical row/default seed
values, accounts, sessions and real media are not imported.

The FIXED filename suggests a revision, not a verified latest installed schema.
No prior application migration ledger or execution provenance establishes which
generation was deployed. Neither ultimate script is a standalone current installer;
their ordering/base-table assumptions conflict. No history is silently selected.

## Compatibility matrix

G1 for every missing definition: obtain independently approved, sanitized complete
CREATE DDL with column types/lengths/nullability/defaults, PK/unique/FK/check
constraints and delete actions, index expressions/prefixes/order, engine/charset/
collation, relevant ALTER history and query/API semantics. Provide no row data,
credentials, sessions or dump contents. Resolve relationships and historical data
assumptions using synthetic fixtures before proposing conversion or constraints.

| Table/view | Existing definition / decision | Affected current consumers (examples) |
| --- | --- | --- |
| `admins` | **MISSING complete permitted DDL - G1 required** | `web/admin/login.php`, `web/admin/manage_shots.php` |
| `analytics` | **MISSING complete permitted DDL - G1 required** | `web/admin/analytics.php`, `web/api/track.php` |
| `backup_log` | Historical definition only; resolve generation/required fields; not migrated | No direct literal query found; inspect routines/historical consumers |
| `bot_health_status` | Historical definition only; resolve generation/required fields; not migrated | `web/admin/cron/reset-daily-stats.php`, `web/api/whatsapp/bot-error.php`, `web/api/whatsapp/bot-heartbeat.php` |
| `bot_statistics` | Historical definition only; resolve generation/required fields; not migrated | No direct literal query found; inspect routines/historical consumers |
| `cache_warming_log` | Historical definition only; resolve generation/required fields; not migrated | `web/api/whatsapp/log-cache-warming.php` |
| `collections` | **MISSING complete permitted DDL - G1 required** | `web/admin/collections.php`, `web/admin/index.php`, `web/admin/manage_shots.php` |
| `comments` | **MISSING complete permitted DDL - G1 required** | `web/api/comments.php`, `web/api/includes/comment_helpers.php` |
| `distribution_groups` | **MISSING complete permitted DDL - G1 required** | `web/includes/whatsapp_token.php` |
| `episode_parts` | Complete source CREATE; partial migration verified | `web/api/whatsapp/get-aging-messages.php`, `web/api/whatsapp/get-all-message-ids.php`, `web/api/whatsapp/get-message-ids.php` |
| `episodes` | **MISSING complete permitted DDL - G1 required** | `web/admin/episodes.php`, `web/api/series.php`, `web/api/whatsapp/get-aging-messages.php` |
| `file_cleanup_queue` | Historical definition only; resolve generation/required fields; not migrated | No direct literal query found; inspect routines/historical consumers |
| `forward_logs` | Historical definition only; resolve generation/required fields; not migrated | No direct literal query found; inspect routines/historical consumers |
| `media_refresh_log` | Historical definition only; resolve generation/required fields; not migrated | `web/api/whatsapp/log-refresh.php`, `web/api/whatsapp/needs-refresh.php`, `web/api/whatsapp/refresh-stats.php` |
| `movie_message_ids` | **MISSING complete permitted DDL - G1 required** | `web/api/whatsapp/update-cache-message-id.php`, `web/api/whatsapp/update-forward-stats.php` |
| `movie_parts` | Complete source CREATE; partial migration verified | `web/api/whatsapp/get-aging-messages.php`, `web/api/whatsapp/get-all-message-ids.php`, `web/api/whatsapp/get-message-ids.php` |
| `movies` | **MISSING complete permitted DDL - G1 required** | `web/admin/collections.php`, `web/admin/index.php`, `web/admin/manage_shots.php` |
| `password_resets` | **MISSING complete permitted DDL - G1 required** | `web/ajax_forgot_password.php`, `web/ajax_reset_password.php`, `web/reset-password.php` |
| `rate_limits` | Complete source CREATE; partial migration verified | `web/admin/cron/reset-daily-stats.php`, `web/includes/rate_limiter.php` |
| `series` | **MISSING complete permitted DDL - G1 required** | `web/admin/episodes.php`, `web/admin/index.php`, `web/admin/manage_shots.php` |
| `shot_comments` | **MISSING complete permitted DDL - G1 required** | `web/api/add_comment.php`, `web/api/get_comments.php`, `web/index.php` |
| `shot_likes` | **MISSING complete permitted DDL - G1 required** | `web/api/like_shot.php`, `web/index.php` |
| `shots` | **MISSING complete permitted DDL - G1 required** | `web/admin/manage_shots.php`, `web/index.php`, `web/profile.php` |
| `site_settings` | Complete source CREATE; partial migration verified | `web/includes/settings.php`, `web/includes/whatsapp_token.php` |
| `system_alerts` | Historical definition only; resolve generation/required fields; not migrated | `web/api/whatsapp/bot-error.php` |
| `trailer_favorites` | **MISSING complete permitted DDL - G1 required** | `web/includes/TrailerManager.php` |
| `trailer_likes` | **MISSING complete permitted DDL - G1 required** | `web/includes/TrailerManager.php` |
| `trailer_shares` | **MISSING complete permitted DDL - G1 required** | `web/includes/TrailerManager.php` |
| `trailer_views` | **MISSING complete permitted DDL - G1 required** | `web/includes/TrailerManager.php` |
| `trailers` | **MISSING complete permitted DDL - G1 required** | `web/admin/trailers.php`, `web/includes/TrailerManager.php`, `web/includes/database.php` |
| `tv_series` | **MISSING complete permitted DDL - G1 required** | `web/api/whatsapp/get-popular-content.php` |
| `user_favorites` | **MISSING complete permitted DDL - G1 required** | `web/api/favorite_shot.php`, `web/index.php`, `web/profile.php` |
| `user_views` | **MISSING complete permitted DDL - G1 required** | `web/api/record_view.php`, `web/index.php` |
| `users` | **MISSING complete permitted DDL - G1 required** | `web/admin/config.php`, `web/ajax_forgot_password.php`, `web/ajax_login.php` |
| `v_popular_content` | Historical definition only; resolve generation/required fields; not migrated | `web/api/whatsapp/get-popular-files.php` |
| `video_tokens` | Historical definition only; resolve generation/required fields; not migrated | No direct literal query found; inspect routines/historical consumers |
| `whatsapp_forward_logs` | **MISSING complete permitted DDL - G1 required** | `web/api/whatsapp/log-forward.php`, `bot/config/database.js` |
| `whatsapp_message_ids` | **MISSING complete permitted DDL - G1 required** | `web/api/whatsapp/add-message-id.php`, `web/api/whatsapp/get-all-message-ids.php`, `web/api/whatsapp/get-message-id.php` |
| `whatsapp_tokens` | Historical definition only; resolve generation/required fields; not migrated | `web/admin/cron/cleanup-tokens.php`, `web/api/whatsapp/get-popular-content.php`, `web/api/whatsapp/mark-used.php` |

## Confirmed conflicts and unresolved contracts

- `series` is used by active catalogue paths; guarded server-check code mentions
  `tv_series`. No alias or replacement table is invented.
- The FIXED token CREATE uses status and lacks message_id, is_used and is_active,
  while current PHP INSERT/validation and disabled Node queries require those
  fields plus assignment/part/expiry semantics. Token migration is blocked.
- `video_tokens`/`forward_logs` differ from current whatsapp_tokens/
  whatsapp_forward_logs/whatsapp_message_ids. No blind rename, row conversion
  or FK/cascade substitution is attempted.
- movie_message_ids receives ALTERs without a complete base CREATE. The FIXED
  history instead expands part tables. Current base part lookups need only the
  verified CREATE columns; optional cache expansion is not applied.
- Original migration fragments use ADD COLUMN/INDEX IF NOT EXISTS forms that
  are not assumed portable to this pinned MySQL. None is executed wholesale.
- Both generations define v_popular_content, sp_bot_heartbeat and
  fn_get_available_bot; only the original defines sp_record_forward. Their
  required base schemas and complete behavior remain unverified; no routine,
  event or view is installed.
- PHP comments/trailer paths reference $pdo after a MySQLi-only bootstrap. The
  separate PDO helper works when explicitly included, but those route includes
  are unchanged. TrailerManager repeats named parameters; emulation is preserved.
- Settings and rate-limiter helpers perform runtime CREATE. Their behavior is
  preserved; DML-only accounts do not acquire schema privileges to hide this risk.

The 24 names marked MISSING plus the conflicting current token/message/routine
contracts block a complete canonical database. No new catalogue, account, social,
licensing, entitlement, AI, video-asset or delivery tables are invented. ZL-15 is
not started. [Access](DATABASE_ACCESS.md), [canonical subset](CANONICAL_SCHEMA.md),
[tests](SCHEMA_TEST_RESULTS.md).
