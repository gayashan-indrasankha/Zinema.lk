# Module inventory and disposition — Jira ZL-11

Date: 2026-10-23 (Asia/Colombo). **COMPLETE for the bounded static blueprint;
migration/runtime readiness is not established.** Authoritative input is the
existing extracted workspace, including its working tree and uncommitted changes.
No ZIP is required or used.

## Scope and evidence

Started on `feat/zl-10-safe-public-export` at HEAD `a2e5aebf55320e891f5580e7b8825bb32e72cdd8`, tree
`437f5a903c064aab5c2ec7462027af18caf2c848`. The index was empty. Existing modifications to
`docs/BASELINE.md` and `docs/CONTRIBUTIONS.md` predate this task and are preserved.
ZL-9 and ZL-10 artifacts were checked on disk and reconciled with recovery records;
their presence alone is not treated as evidence of runtime success.

The inventory covers **265 tracked paths in 51 responsibility groups**:
101 first-party PHP files, 36 first-party JavaScript/CJS files, six SQL references,
four example templates, 70 grouped vendor files, three binaries, plus styles,
manifests, policy and documentation. All 137 PHP/JS/CJS files received static
dependency/reference extraction and parser checks. Selected principal control
flows were inspected with source redaction. This is not a line-by-line semantic,
security, installed-dependency or runtime audit.

Private environments, authentication, dumps, logs, media and generated dependencies
were excluded from content inspection. Six known ignored alternative bot programs
were inventoried by metadata only. Existing example contracts were taken from
security/configuration records; environment values were not reopened.
No active nested Git marker was found among 24 nonprivate tracked-parent
directories; preserved private history was not searched or relocated.

KEEP means retain responsibility, **not proven correct**. REPAIR means necessary
work with defects or missing contracts. ISOLATE retains optional/risky behavior
behind explicit interfaces/default-off gates. ARCHIVE CANDIDATE requires
replacement, dependency/regression verification and owner approval.
UNRESOLVED means insufficient evidence. None authorizes deletion.

## Classification statistics

Counts are responsibility groups, not files. The two excluded-boundary groups
P01/P02 below are reported separately and are not added to these totals.

| Classification | Groups |
| --- | ---: |
| KEEP | 10 |
| REPAIR | 22 |
| ISOLATE | 12 |
| ARCHIVE CANDIDATE | 2 |
| UNRESOLVED | 5 |
| Total | 51 |

## Classification matrix

Every tracked path has exactly one owning group. Vendor files are grouped, not
individually classified as first-party modules. The full nonvendor path ledger
below resolves group membership.

| ID | Responsibility | Disposition | Confidence | Change risk | Tracked paths |
| --- | --- | --- | --- | --- | ---: |
| W01 | Apache routes and discovery | REPAIR | High | High | 4 |
| W02 | Configuration, DB and session bootstrap | REPAIR | High | High | 3 |
| W03 | CSRF and rate-limit primitives | KEEP | High | Medium | 2 |
| W04 | Account routes and auth variants | REPAIR | High | High | 12 |
| W05 | OAuth and mail adapters | ISOLATE | High | High | 2 |
| W06 | Catalogue listings and search pages | REPAIR | High | High | 4 |
| W07 | Details and native playback | REPAIR | High | High | 3 |
| W08 | Shots feed and social endpoints | REPAIR | High | High | 6 |
| W09 | Profile and saved Shots | REPAIR | High | High | 1 |
| W10 | Catalogue/search JSON APIs | REPAIR | High | High | 5 |
| W11 | Movie comments/PDO subsystem | REPAIR | High | High | 5 |
| W12 | Trailer feed and manager | REPAIR | High | High | 5 |
| W13 | Shared navigation and pagination | KEEP | High | Medium | 3 |
| W14 | Admin shell, login and assets | REPAIR | High | High | 7 |
| W15 | Admin catalogue CRUD | REPAIR | High | High | 5 |
| W16 | Admin Shots and extractor client | REPAIR | High | High | 1 |
| W17 | Admin metrics/settings/bot monitor | REPAIR | High | High | 3 |
| W18 | Database-backed settings | REPAIR | High | High | 1 |
| W19 | Token issuance/delivery/download UI | ISOLATE | High | High | 3 |
| W20 | Website-to-bot bridge | REPAIR | High | High | 21 |
| W21 | Quarantined commercial adapters | ISOLATE | High | High | 3 |
| W22 | Analytics collection | REPAIR | High | Medium | 2 |
| W23 | Quarantined maintenance jobs | ISOLATE | High | High | 3 |
| W24 | Disabled ads compatibility helpers | KEEP | High | Low | 1 |
| W25 | Prototype pages and assets | ARCHIVE CANDIDATE | Medium | High | 8 |
| W26 | Standalone/legacy browser modules | UNRESOLVED | Medium | Medium | 12 |
| W27 | Stylesheet contracts | KEEP | Medium | Medium | 12 |
| W28 | Small URL/escaping helpers | UNRESOLVED | Low | Medium | 1 |
| W29 | Server diagnostics endpoint | ISOLATE | High | Medium | 1 |
| B01 | Guarded Baileys entry/socket adapter | ISOLATE | High | High | 1 |
| B02 | Bot configuration boundary | KEEP | High | High | 1 |
| B03 | HTTP bridge client | REPAIR | High | High | 1 |
| B04 | Multi-tier forwarding coordinator | REPAIR | High | High | 1 |
| B05 | Priority upload queue | REPAIR | High | Medium | 1 |
| B06 | Local file/media adapter | ISOLATE | High | High | 1 |
| B07 | Background cache warmer | ISOLATE | High | High | 1 |
| B08 | Automatic refresh worker | ISOLATE | High | High | 1 |
| B09 | Health/heartbeat monitoring | ISOLATE | High | Medium | 1 |
| B10 | Alternate direct DB client | UNRESOLVED | Medium | High | 1 |
| B11 | Scheduler diagnostic sample | ARCHIVE CANDIDATE | High | Low | 1 |
| B12 | Bot package and lock contract | KEEP | High | Medium | 2 |
| X01 | Guarded extractor service | ISOLATE | High | High | 3 |
| D01 | Historical bot SQL generations | REPAIR | High | High | 3 |
| D02 | Website parts/Shot SQL references | REPAIR | High | High | 3 |
| G01 | Repository safeguards and example contracts | KEEP | High | Medium | 10 |
| G02 | Existing docs and audit evidence | KEEP | High | Low | 17 |
| G03 | Syntax-only checker | KEEP | High | Low | 1 |
| G04 | Public-export utility and synthetic suite | KEEP | High | Medium | 2 |
| G05 | Orphan website lockfile | UNRESOLVED | High | Medium | 1 |
| G06 | Bundled PHPMailer vendor group | ISOLATE | High | Medium | 70 |
| G07 | Binary identity assets | UNRESOLVED | Medium | Medium | 3 |

## Module dossiers

Evidence shorthand in W/X dossiers is relative to `Zinema.lk Website/`;
B dossiers use `whatsapp-bot-deploy/`. D dossiers name the matching SQL paths
listed in the ledger. Root-qualified docs/scripts paths remain root relative.
Line references describe the current preserved source. Proposed paths are
**PLANNED**, not existing destinations. Related Jira references now use the owner-supplied
planning list in PRODUCTION_GAP_ANALYSIS.md; dependency ordering is PROPOSED DEPENDENCY,
not independently verified Jira links or issue completion.

### W01 — Apache routes and discovery

- Responsibility / reason: Preserve slug/PHP URLs, sitemap and browser metadata; error-page targets are absent.
- Current status: Statically inspected; runtime NOT VERIFIED. Disposition **REPAIR**; confidence High; modification risk High.
- Evidence: .htaccess:1-9,61-89; sitemap.php:2-5.
- Calls, configuration, database, libraries and service boundary: Apache 2.4/AllowOverride and rewrite modules; BASE_URL; MySQLi movies/series/collections via admin/config.
- Consumers: Browsers/crawlers and all public routes; actual host configuration NOT VERIFIED.
- Proposed location / relocation consequence: `apps/web/public/ compatibility entry points`. Changing this boundary can break the listed callers, imports, URLs, state roots or response contracts; retain old-path compatibility until verified.
- Required verification: Isolated Apache route/status/query/host/subdirectory/deny-rule tests before moving document root.
- Related Jira: ZL-12, ZL-48, ZL-31; ZL-12 only for covered relocation. Issue mapping does not assert completion. See [migration gates](MIGRATION_PLAN.md) and [debt register](TECHNICAL_DEBT.md).

### W02 — Configuration, DB and session bootstrap

- Responsibility / reason: Separate pure config from session/database side effects while preserving both interfaces.
- Current status: Statically inspected; runtime NOT VERIFIED. Disposition **REPAIR**; confidence High; modification risk High.
- Evidence: includes/environment.php:7-61; admin/config.php:2-55; includes/database.php:2-24.
- Calls, configuration, database, libraries and service boundary: Process APP_ENV/BASE_URL/DB_*/SESSION_SAVE_PATH and adapter modes; mysqli $conn vs PDO $pdo; users/remember cookies; PHP extensions.
- Consumers: Most pages/admin/APIs; alternate PDO callers DEPENDENCY NOT FULLY VERIFIED.
- Proposed location / relocation consequence: `apps/web/src/Config/, Database/, Auth/ with old-path bootstrap`. Changing this boundary can break the listed callers, imports, URLs, state roots or response contracts; retain old-path compatibility until verified.
- Required verification: Fixture DB and disabled-mode/cookie/error tests; audit every consumer; no production bootstrap execution.
- Related Jira: ZL-14, ZL-19, ZL-13; ZL-12 only for covered relocation. Issue mapping does not assert completion. See [migration gates](MIGRATION_PLAN.md) and [debt register](TECHNICAL_DEBT.md).

### W03 — CSRF and rate-limit primitives

- Responsibility / reason: Retain reusable primitives; inclusion is not proof that callers enforce protection.
- Current status: Statically inspected; runtime NOT VERIFIED. Disposition **KEEP**; confidence High; modification risk Medium.
- Evidence: includes/csrf_helper.php:11-73; includes/whatsapp_token.php:38-53.
- Calls, configuration, database, libraries and service boundary: PHP sessions/random_bytes/hash_equals; injected MySQLi rate_limits. Issuance limiter call is commented out.
- Consumers: Forms/AJAX auth/token helper; endpoint enforcement varies.
- Proposed location / relocation consequence: `apps/web/src/Auth/ procedural helpers`. Changing this boundary can break the listed callers, imports, URLs, state roots or response contracts; retain old-path compatibility until verified.
- Required verification: Fictional valid/expired/invalid CSRF and scoped rate-limit tests; enumerate mutation endpoints.
- Related Jira: ZL-19, ZL-30, ZL-51; ZL-12 only for covered relocation. Issue mapping does not assert completion. See [migration gates](MIGRATION_PLAN.md) and [debt register](TECHNICAL_DEBT.md).

### W04 — Account routes and auth variants

- Responsibility / reason: Preserve direct form, AJAX and JSON variants pending one tested contract.
- Current status: Statically inspected; runtime NOT VERIFIED. Disposition **REPAIR**; confidence High; modification risk High.
- Evidence: ajax_login.php:9-20,82-100; api/auth-handler.php:7-28.
- Calls, configuration, database, libraries and service boundary: MySQLi users/password_resets, session/CSRF, password hashing, optional mailer; BASE_URL and mail/OAuth modes.
- Consumers: Profile and duplicated inline page overlays; external direct callers NOT FULLY VERIFIED.
- Proposed location / relocation consequence: `apps/web/public/ wrappers -> src/Auth/`. Changing this boundary can break the listed callers, imports, URLs, state roots or response contracts; retain old-path compatibility until verified.
- Required verification: Sample signup/login/logout/reset/verification/remember matrix; response fields, redirects, encoding, cookies and authorization.
- Related Jira: ZL-19, ZL-53; ZL-12 only for covered relocation. Issue mapping does not assert completion. See [migration gates](MIGRATION_PLAN.md) and [debt register](TECHNICAL_DEBT.md).

### W05 — OAuth and mail adapters

- Responsibility / reason: Keep external identity/mail disabled or local behind explicit adapters.
- Current status: Statically inspected; runtime NOT VERIFIED. Disposition **ISOLATE**; confidence High; modification risk High.
- Evidence: google-callback.php:7-40; includes/mailer.php:12-14,25-52.
- Calls, configuration, database, libraries and service boundary: OAUTH_*/MAIL_* config, curl, PHPMailer and users; live transports would contact configured services.
- Consumers: Signup/reset/verification and page OAuth links.
- Proposed location / relocation consequence: `apps/web/src/Auth/Adapters/`. Changing this boundary can break the listed callers, imports, URLs, state roots or response contracts; retain old-path compatibility until verified.
- Required verification: Fake transport/state/callback/identity-link tests; preserve disabled errors; separate approval before provider/account access.
- Related Jira: ZL-19, ZL-18, ZL-48; ZL-12 only for covered relocation. Issue mapping does not assert completion. See [migration gates](MIGRATION_PLAN.md) and [debt register](TECHNICAL_DEBT.md).

### W06 — Catalogue listings and search pages

- Responsibility / reason: Retain filter/pagination/partial HTML; SQL, templates and inline auth are coupled.
- Current status: Statically inspected; runtime NOT VERIFIED. Disposition **REPAIR**; confidence High; modification risk High.
- Evidence: movies.php:3-5,91-117,959; tv-series.php:3-5; collections.php:3-5.
- Calls, configuration, database, libraries and service boundary: MySQLi movies/series/collections; CSRF/no-op ads/nav/pagination; BASE_URL, query strings and relative CSS/fetch.
- Consumers: Public navigation/search/direct and extensionless URLs.
- Proposed location / relocation consequence: `apps/web/public/ wrappers -> src/Movies/, Series/, Collections/`. Changing this boundary can break the listed callers, imports, URLs, state roots or response contracts; retain old-path compatibility until verified.
- Required verification: Fictional filter/pagination/partial-response/DOM snapshots and URL compatibility before template extraction.
- Related Jira: ZL-16, ZL-27; ZL-12 only for covered relocation. Issue mapping does not assert completion. See [migration gates](MIGRATION_PLAN.md) and [debt register](TECHNICAL_DEBT.md).

### W07 — Details and native playback

- Responsibility / reason: Preserve detail/episode links and native player/resume behavior; not verified streaming readiness.
- Current status: Statically inspected; runtime NOT VERIFIED. Disposition **REPAIR**; confidence High; modification risk High.
- Evidence: movie-details.php:3-4,582-619; series-details.php:3-4,973; collection-details.php:3-4.
- Calls, configuration, database, libraries and service boundary: MySQLi movies/series/episodes/collections; BASE_URL assets, DB media URLs, inline JS/localStorage; media rights unknown.
- Consumers: Slug/direct URLs, catalogue and linked Shots.
- Proposed location / relocation consequence: `apps/web/public/ wrappers -> domain query/templates`. Changing this boundary can break the listed callers, imports, URLs, state roots or response contracts; retain old-path compatibility until verified.
- Required verification: Legal local clips: controls, resume keys, episodes/posters, slug/direct routes; separate escaping/security repair from movement.
- Related Jira: ZL-17, ZL-45, ZL-44; ZL-12 only for covered relocation. Issue mapping does not assert completion. See [migration gates](MIGRATION_PLAN.md) and [debt register](TECHNICAL_DEBT.md).

### W08 — Shots feed and social endpoints

- Responsibility / reason: Keep feed/social core; conditional fresh-CDN loader couples public playback to extraction.
- Current status: Statically inspected; runtime NOT VERIFIED. Disposition **REPAIR**; confidence High; modification risk High.
- Evidence: index.php:33-105,451-550,982-1163; api/like_shot.php:18-78.
- Calls, configuration, database, libraries and service boundary: MySQLi shots/shot_likes/shot_comments/user_favorites/user_views/users; sessions/auth overlays; FB_VIDEO_API_URL and browser caches.
- Consumers: Home/profile-saved feed and linked-content navigation.
- Proposed location / relocation consequence: `apps/web/public/ wrappers -> src/Shots/, Api/, assets/`. Changing this boundary can break the listed callers, imports, URLs, state roots or response contracts; retain old-path compatibility until verified.
- Required verification: Sample anonymous/authenticated/exhausted-history feed, social mutations, cache/fallback and links; no live media extraction.
- Related Jira: ZL-17, ZL-25, ZL-55; ZL-12 only for covered relocation. Issue mapping does not assert completion. See [migration gates](MIGRATION_PLAN.md) and [debt register](TECHNICAL_DEBT.md).

### W09 — Profile and saved Shots

- Responsibility / reason: Retain saved feed and account overlays; large controller mixes UI/query/auth.
- Current status: Statically inspected; runtime NOT VERIFIED. Disposition **REPAIR**; confidence High; modification risk High.
- Evidence: profile.php:3-4,32-79,543-864,1106.
- Calls, configuration, database, libraries and service boundary: MySQLi users/user_favorites/shots; session/CSRF/AJAX verification/favorite APIs and browser state.
- Consumers: Navigation and account redirects.
- Proposed location / relocation consequence: `apps/web/public/profile.php -> src/Users/`. Changing this boundary can break the listed callers, imports, URLs, state roots or response contracts; retain old-path compatibility until verified.
- Required verification: Fictional account/verification/saved pagination/overlay response tests.
- Related Jira: ZL-19, ZL-23, ZL-53; ZL-12 only for covered relocation. Issue mapping does not assert completion. See [migration gates](MIGRATION_PLAN.md) and [debt register](TECHNICAL_DEBT.md).

### W10 — Catalogue/search JSON APIs

- Responsibility / reason: Retain existing JSON contracts and linked-content search selectors.
- Current status: Statically inspected; runtime NOT VERIFIED. Disposition **REPAIR**; confidence High; modification risk High.
- Evidence: api/movies.php:7-21; api/series.php:8-32; admin/assets/admin.js:77.
- Calls, configuration, database, libraries and service boundary: MySQLi movies/series/episodes/collections, session bootstrap, CORS/query fields/relative URL assumptions.
- Consumers: Browser modules, live search and admin selectors.
- Proposed location / relocation consequence: `apps/web/public/api/ wrappers -> src/Api/ and domain queries`. Changing this boundary can break the listed callers, imports, URLs, state roots or response contracts; retain old-path compatibility until verified.
- Required verification: Snapshot fields/null/errors/pagination/search types/HTTP/CORS with sanitized fixtures.
- Related Jira: ZL-16, ZL-55; ZL-12 only for covered relocation. Issue mapping does not assert completion. See [migration gates](MIGRATION_PLAN.md) and [debt register](TECHNICAL_DEBT.md).

### W11 — Movie comments/PDO subsystem

- Responsibility / reason: Keep separately routed movie comments; bootstrap and dynamic subpaths need contract review.
- Current status: Statically inspected; runtime NOT VERIFIED. Disposition **REPAIR**; confidence High; modification risk High.
- Evidence: api/comments.php:2-27; api/includes/comment_helpers.php:1-77; js/comments.js:5,207.
- Calls, configuration, database, libraries and service boundary: $pdo passed after MySQLi-only admin config; comments/users/movies; session and JSON methods; constructed /like path.
- Consumers: movie-comments.php widget; external/PATH_INFO consumers DEPENDENCY NOT FULLY VERIFIED.
- Proposed location / relocation consequence: `apps/web/public/ wrappers -> src/Comments/, Api/, assets/`. Changing this boundary can break the listed callers, imports, URLs, state roots or response contracts; retain old-path compatibility until verified.
- Required verification: GET/POST/DELETE/like/reply/schema/auth/DB adapter and PATH_INFO tests; preserve URLs until verified.
- Related Jira: ZL-14, ZL-17, ZL-55; ZL-12 only for covered relocation. Issue mapping does not assert completion. See [migration gates](MIGRATION_PLAN.md) and [debt register](TECHNICAL_DEBT.md).

### W12 — Trailer feed and manager

- Responsibility / reason: Keep trailers separate from Shots; PDO and generated endpoint assumptions are unresolved.
- Current status: Statically inspected; runtime NOT VERIFIED. Disposition **REPAIR**; confidence High; modification risk High.
- Evidence: trailers.php:2-17; api/trailers.php:2-18; includes/TrailerManager.php:1-315.
- Calls, configuration, database, libraries and service boundary: PDO manager after mysqli bootstrap; trailers/movies/trailer_views/trailer_likes/trailer_favorites/trailer_shares; constructed /view,/share routes.
- Consumers: Direct feed/API/admin-managed trailers; not proven as Shots replacement.
- Proposed location / relocation consequence: `apps/web/public/ wrappers -> src/Trailers/`. Changing this boundary can break the listed callers, imports, URLs, state roots or response contracts; retain old-path compatibility until verified.
- Required verification: Fixture manager/likes/favorites/views/shares/bootstrap/response tests; session-start ordering review.
- Related Jira: ZL-14, ZL-42, ZL-45; ZL-12 only for covered relocation. Issue mapping does not assert completion. See [migration gates](MIGRATION_PLAN.md) and [debt register](TECHNICAL_DEBT.md).

### W13 — Shared navigation and pagination

- Responsibility / reason: Retain shared PHP and inline browser contracts.
- Current status: Statically inspected; runtime NOT VERIFIED. Disposition **KEEP**; confidence High; modification risk Medium.
- Evidence: _top_nav.php:120,193,214; _bottom_nav.php:235-287.
- Calls, configuration, database, libraries and service boundary: BASE_URL/current-page variables, relative navigation/fetch and js/tracker.js; no own DB bootstrap.
- Consumers: Main pages/details/feed/profile.
- Proposed location / relocation consequence: `apps/web/src/Views/ via old-path includes first`. Changing this boundary can break the listed callers, imports, URLs, state roots or response contracts; retain old-path compatibility until verified.
- Required verification: Link/DOM/active-state/pagination tests across root, slug and subdirectory URLs.
- Related Jira: ZL-12, ZL-27; ZL-12 only for covered relocation. Issue mapping does not assert completion. See [migration gates](MIGRATION_PLAN.md) and [debt register](TECHNICAL_DEBT.md).

### W14 — Admin shell, login and assets

- Responsibility / reason: Retain admin authorization; helper/script usage needs full consumer confirmation.
- Current status: Statically inspected; runtime NOT VERIFIED. Disposition **REPAIR**; confidence High; modification risk High.
- Evidence: admin/login.php:6-31; admin/config.php:52-55; admin/assets/admin.js:77.
- Calls, configuration, database, libraries and service boundary: mysqli admins/users, admin_id vs user_id sessions; admin-relative CSS/API; admin.js include not confirmed.
- Consumers: Admin routes/bookmarks; helper/script DEPENDENCY NOT FULLY VERIFIED.
- Proposed location / relocation consequence: `apps/web/public/admin/ wrappers -> src/Admin/, assets/`. Changing this boundary can break the listed callers, imports, URLs, state roots or response contracts; retain old-path compatibility until verified.
- Required verification: Sample login/logout/role separation/layout/search widget and direct URL tests; preserve uncertain helper/script.
- Related Jira: ZL-19, ZL-55; ZL-12 only for covered relocation. Issue mapping does not assert completion. See [migration gates](MIGRATION_PLAN.md) and [debt register](TECHNICAL_DEBT.md).

### W15 — Admin catalogue CRUD

- Responsibility / reason: Keep CRUD; GET deletion and upload boundaries need later security repair.
- Current status: Statically inspected; runtime NOT VERIFIED. Disposition **REPAIR**; confidence High; modification risk High.
- Evidence: admin/movies.php:2-27,49-59; admin/trailers.php:297.
- Calls, configuration, database, libraries and service boundary: mysqli movies/series/episodes/collections/trailers, admin session; ../uploads filesystem writes/deletes; API/layout.
- Consumers: Authenticated admin forms and direct endpoints.
- Proposed location / relocation consequence: `apps/web/public/admin/ wrappers -> src/Admin/`. Changing this boundary can break the listed callers, imports, URLs, state roots or response contracts; retain old-path compatibility until verified.
- Required verification: Fixture-only CRUD/CSRF/upload/authorization, backup/rollback tests; never test deletion on unknown DB/media.
- Related Jira: ZL-19, ZL-42, ZL-52, ZL-55; ZL-12 only for covered relocation. Issue mapping does not assert completion. See [migration gates](MIGRATION_PLAN.md) and [debt register](TECHNICAL_DEBT.md).

### W16 — Admin Shots and extractor client

- Responsibility / reason: Retain Shot management and separate extraction from CRUD later.
- Current status: Statically inspected; runtime NOT VERIFIED. Disposition **REPAIR**; confidence High; modification risk High.
- Evidence: admin/manage_shots.php:12,48,523,583,647.
- Calls, configuration, database, libraries and service boundary: mysqli shots/admins/movies/series/collections; FB_VIDEO_API_URL PHP/browser clients, health checks, uploads and sessions.
- Consumers: Admin ingestion and public feed consuming saved metadata.
- Proposed location / relocation consequence: `apps/web/public/admin/ wrapper -> src/Admin/Shots/`. Changing this boundary can break the listed callers, imports, URLs, state roots or response contracts; retain old-path compatibility until verified.
- Required verification: Fictional Shot CRUD, linked IDs, required-share-URL and optional cached-extraction contracts; any new local-fixture ingestion path requires separate implementation.
- Related Jira: ZL-18, ZL-42, ZL-52; ZL-12 only for covered relocation. Issue mapping does not assert completion. See [migration gates](MIGRATION_PLAN.md) and [debt register](TECHNICAL_DEBT.md).

### W17 — Admin metrics/settings/bot monitor

- Responsibility / reason: Retain operations UI; separate local paths and complex SQL from public controllers.
- Current status: Statically inspected; runtime NOT VERIFIED. Disposition **REPAIR**; confidence High; modification risk High.
- Evidence: admin/analytics.php:2-22; admin/settings.php:7-8; admin/whatsapp-bot-tracking.php:3.
- Calls, configuration, database, libraries and service boundary: mysqli analytics/site_settings/bot_health_status/bot_statistics/system_alerts/whatsapp_tokens/v_popular_content; settings helper and personal-path patterns withheld.
- Consumers: Admin dashboard; installed views/data NOT VERIFIED.
- Proposed location / relocation consequence: `apps/web/public/admin/ wrappers -> src/Admin/ private operations adapters`. Changing this boundary can break the listed callers, imports, URLs, state roots or response contracts; retain old-path compatibility until verified.
- Required verification: Fake metrics/settings/view/schema/auth/redaction checks; review path-bearing page privately before export.
- Related Jira: ZL-19, ZL-49, ZL-55; ZL-12 only for covered relocation. Issue mapping does not assert completion. See [migration gates](MIGRATION_PLAN.md) and [debt register](TECHNICAL_DEBT.md).

### W18 — Database-backed settings

- Responsibility / reason: Retain settings lookup but separate schema mutation in later repair.
- Current status: Statically inspected; runtime NOT VERIFIED. Disposition **REPAIR**; confidence High; modification risk High.
- Evidence: includes/settings.php:15-27,99-137.
- Calls, configuration, database, libraries and service boundary: Injected mysqli/site_settings, request cache and runtime CREATE TABLE/default INSERT.
- Consumers: Admin settings and token helper.
- Proposed location / relocation consequence: `apps/web/src/Config/Settings.php (procedural initially)`. Changing this boundary can break the listed callers, imports, URLs, state roots or response contracts; retain old-path compatibility until verified.
- Required verification: Characterize defaults/cache/errors; approve canonical migration before removing runtime DDL.
- Related Jira: ZL-14, ZL-47; ZL-12 only for covered relocation. Issue mapping does not assert completion. See [migration gates](MIGRATION_PLAN.md) and [debt register](TECHNICAL_DEBT.md).

### W19 — Token issuance/delivery/download UI

- Responsibility / reason: Retain optional access adapter and all guards; do not restore historic delivery flow now.
- Current status: Statically inspected; runtime NOT VERIFIED. Disposition **ISOLATE**; confidence High; modification risk High.
- Evidence: whatsapp-watch.php:3-8,78; download.php:2-4; includes/whatsapp_token.php:38-53,166-175.
- Calls, configuration, database, libraries and service boundary: mysqli whatsapp_tokens/whatsapp_message_ids/movie_parts/episode_parts/bot_health_status/site_settings/settings/distribution_groups; fn_get_available_bot; mode/config, rate-limit stub and WhatsApp deep links.
- Consumers: Details/content links and bot token validation.
- Proposed location / relocation consequence: `apps/web/public/ wrappers -> src/ContentAccess/; services/delivery/`. Changing this boundary can break the listed callers, imports, URLs, state roots or response contracts; retain old-path compatibility until verified.
- Required verification: Disabled behavior first; later fictional simulator plus schema/validity/claim/ack tests before enabling.
- Related Jira: ZL-28, ZL-30, ZL-54; ZL-12 only for covered relocation. Issue mapping does not assert completion. See [migration gates](MIGRATION_PLAN.md) and [debt register](TECHNICAL_DEBT.md).

### W20 — Website-to-bot bridge

- Responsibility / reason: Retain twenty endpoint files and shared config; unify guarded contract only after characterization.
- Current status: Statically inspected; runtime NOT VERIFIED. Disposition **REPAIR**; confidence High; modification risk High.
- Evidence: api/whatsapp/config.php:8-45; validate-token.php:73-99; update-cache-message-id.php:25-48; update-forward-stats.php:23-50.
- Calls, configuration, database, libraries and service boundary: mysqli bridge tables, shared-key header/WHATSAPP mode. get-aging has separate header path. Sixteen literal endpoint names in client; four other endpoint consumers NOT FULLY VERIFIED.
- Consumers: Bot entry/workers/admin; external callers DEPENDENCY NOT FULLY VERIFIED.
- Proposed location / relocation consequence: `apps/web/public/api/whatsapp/ wrappers -> src/Api/Delivery/`. Changing this boundary can break the listed callers, imports, URLs, state roots or response contracts; retain old-path compatibility until verified.
- Required verification: Fake HTTP auth/disabled/request/response/error/DB idempotency contract; HTTP 200 is not token validity.
- Related Jira: ZL-14, ZL-29, ZL-30; ZL-12 only for covered relocation. Issue mapping does not assert completion. See [migration gates](MIGRATION_PLAN.md) and [debt register](TECHNICAL_DEBT.md).

### W21 — Quarantined commercial adapters

- Responsibility / reason: Retain disabled payment/subscription paths.
- Current status: Statically inspected; runtime NOT VERIFIED. Disposition **ISOLATE**; confidence High; modification risk High.
- Evidence: api/subscribe.php:2-4; api/payment-notify.php:2-4,18; js/subscribe.js:29-51.
- Calls, configuration, database, libraries and service boundary: users/session/PAYMENT_*; unreachable legacy provider requests/raw notification logging.
- Consumers: Legacy subscribe UI/callback registrations DEPENDENCY NOT FULLY VERIFIED.
- Proposed location / relocation consequence: `Disabled compatibility routes -> src/Payments/`. Changing this boundary can break the listed callers, imports, URLs, state roots or response contracts; retain old-path compatibility until verified.
- Required verification: Disabled status/body then fake notifications under separate security/account approval.
- Related Jira: ZL-18; ZL-12 only for covered relocation. Issue mapping does not assert completion. See [migration gates](MIGRATION_PLAN.md) and [debt register](TECHNICAL_DEBT.md).

### W22 — Analytics collection

- Responsibility / reason: Keep page/watch telemetry with path/privacy contracts.
- Current status: Statically inspected; runtime NOT VERIFIED. Disposition **REPAIR**; confidence High; modification risk Medium.
- Evidence: _bottom_nav.php:287; js/tracker.js:5,27; api/track.php:5-35.
- Calls, configuration, database, libraries and service boundary: Relative sendBeacon, mysqli analytics/session and event/value query fields; player DOM assumptions.
- Consumers: Shared bottom-nav pages and admin analytics.
- Proposed location / relocation consequence: `apps/web/public/api/track.php -> src/Analytics/, assets/`. Changing this boundary can break the listed callers, imports, URLs, state roots or response contracts; retain old-path compatibility until verified.
- Required verification: Root/slug/subdirectory URL and fake watch/session/event/privacy tests.
- Related Jira: ZL-49, ZL-53; ZL-12 only for covered relocation. Issue mapping does not assert completion. See [migration gates](MIGRATION_PLAN.md) and [debt register](TECHNICAL_DEBT.md).

### W23 — Quarantined maintenance jobs

- Responsibility / reason: Retain disabled schema/cleanup jobs and unknown deployment references.
- Current status: Statically inspected; runtime NOT VERIFIED. Disposition **ISOLATE**; confidence High; modification risk High.
- Evidence: admin/run_migration.php:2-15; admin/cron/cleanup-tokens.php:13-18; reset-daily-stats.php:13-18.
- Calls, configuration, database, libraries and service boundary: Computed dirname bootstrap; mysqli shots/whatsapp_tokens/rate_limits/bot_health_status; old cPanel comments are not deployment evidence.
- Consumers: Possible CLI/cPanel invocation DEPENDENCY NOT FULLY VERIFIED.
- Proposed location / relocation consequence: `scripts/maintenance/ only after tests; old disabled shims retained`. Changing this boundary can break the listed callers, imports, URLs, state roots or response contracts; retain old-path compatibility until verified.
- Required verification: Discover scheduler references privately; disabled-exit fixtures and separate DB backup/rollback approval before writes.
- Related Jira: ZL-18, ZL-14, ZL-35; ZL-12 only for covered relocation. Issue mapping does not assert completion. See [migration gates](MIGRATION_PLAN.md) and [debt register](TECHNICAL_DEBT.md).

### W24 — Disabled ads compatibility helpers

- Responsibility / reason: Keep no-op helper interface; no ad network is enabled.
- Current status: Statically inspected; runtime NOT VERIFIED. Disposition **KEEP**; confidence High; modification risk Low.
- Evidence: includes/ads.php:7-17.
- Calls, configuration, database, libraries and service boundary: Disabled flag/empty functions; no current external service.
- Consumers: Catalogue/detail/download templates.
- Proposed location / relocation consequence: `apps/web/src/Views/ compatibility helper`. Changing this boundary can break the listed callers, imports, URLs, state roots or response contracts; retain old-path compatibility until verified.
- Required verification: Call/signature/render checks must preserve empty behavior.
- Related Jira: ZL-12, ZL-18; ZL-12 only for covered relocation. Issue mapping does not assert completion. See [migration gates](MIGRATION_PLAN.md) and [debt register](TECHNICAL_DEBT.md).

### W25 — Prototype pages and assets

- Responsibility / reason: Parallel TODO/sample pages remain linked/routable; future archive candidate only.
- Current status: Statically inspected; runtime NOT VERIFIED. Disposition **ARCHIVE CANDIDATE**; confidence Medium; modification risk High.
- Evidence: pages/movie-detail.php:2-16; pages/trailers.php:2-17; includes/header.php:43-49; footer.php:24-26.
- Calls, configuration, database, libraries and service boundary: Generic header/footer, Bootstrap/Font Awesome CDNs, sample media references, /pages and /assets URLs.
- Consumers: Prototype nav links; bookmarks/external use DEPENDENCY NOT FULLY VERIFIED.
- Proposed location / relocation consequence: `Preserved archive/legacy-web/ only after approved redirects`. Changing this boundary can break the listed callers, imports, URLs, state roots or response contracts; retain old-path compatibility until verified.
- Required verification: Inventory all URLs, prove real replacement/aliases, tests/rights/owner approval; no automatic deletion.
- Related Jira: ZL-12, ZL-27, ZL-31; ZL-12 only for covered relocation. Issue mapping does not assert completion. See [migration gates](MIGRATION_PLAN.md) and [debt register](TECHNICAL_DEBT.md).

### W26 — Standalone/legacy browser modules

- Responsibility / reason: Retain separate nav/player/catalogue/countdown modules pending consumer/DOM audit.
- Current status: Statically inspected; runtime NOT VERIFIED. Disposition **UNRESOLVED**; confidence Medium; modification risk Medium.
- Evidence: js/collections.js:27; js/tv-series.js:23; js/navbar.js:28-32; trailers.php:45-46.
- Calls, configuration, database, libraries and service boundary: Some .html links target absent files; nav.js is confirmed loaded. JSON APIs/DOM IDs/browser storage; separate main.js/main.min.js.
- Consumers: Known trailer nav and possible dynamic loaders; DEPENDENCY NOT FULLY VERIFIED for others.
- Proposed location / relocation consequence: `apps/web/assets/ only after consumer confirmation`. Changing this boundary can break the listed callers, imports, URLs, state roots or response contracts; retain old-path compatibility until verified.
- Required verification: Template/DOM/event/dynamic-loader mapping, minified parity and duplicate-handler/path tests; no unused claim.
- Related Jira: ZL-12, ZL-31, ZL-27; ZL-12 only for covered relocation. Issue mapping does not assert completion. See [migration gates](MIGRATION_PLAN.md) and [debt register](TECHNICAL_DEBT.md).

### W27 — Stylesheet contracts

- Responsibility / reason: Preserve visual assets; duplicate basenames are not proof of redundant content.
- Current status: Statically inspected; runtime NOT VERIFIED. Disposition **KEEP**; confidence Medium; modification risk Medium.
- Evidence: index.php:125-128; movie-details.php:155-159; includes/header.php:27-31.
- Calls, configuration, database, libraries and service boundary: Relative vs BASE_URL links, inline styles/media queries, css vs assets/css; no build pipeline.
- Consumers: Current/prototype templates; individual selectors DEPENDENCY NOT FULLY VERIFIED.
- Proposed location / relocation consequence: `Keep public URL assets first; organize source assets later`. Changing this boundary can break the listed callers, imports, URLs, state roots or response contracts; retain old-path compatibility until verified.
- Required verification: Visual/viewport/accessibility/asset-network tests; no CSS consolidation in path-only migration.
- Related Jira: ZL-12, ZL-27; ZL-12 only for covered relocation. Issue mapping does not assert completion. See [migration gates](MIGRATION_PLAN.md) and [debt register](TECHNICAL_DEBT.md).

### W28 — Small URL/escaping helpers

- Responsibility / reason: No literal consumer confirmed; lack of imports does not prove disuse.
- Current status: Statically inspected; runtime NOT VERIFIED. Disposition **UNRESOLVED**; confidence Low; modification risk Medium.
- Evidence: includes/functions.php:3-9.
- Calls, configuration, database, libraries and service boundary: HTTP_HOST/HTTPS and htmlspecialchars; no DB/library.
- Consumers: DEPENDENCY NOT FULLY VERIFIED: deployment/dynamic consumers not established.
- Proposed location / relocation consequence: `apps/web/src/Support/ only after review`. Changing this boundary can break the listed callers, imports, URLs, state roots or response contracts; retain old-path compatibility until verified.
- Required verification: Discover callers and host/escaping behavior; any archive requires separate approval.
- Related Jira: ZL-12, ZL-31; ZL-12 only for covered relocation. Issue mapping does not assert completion. See [migration gates](MIGRATION_PLAN.md) and [debt register](TECHNICAL_DEBT.md).

### W29 — Server diagnostics endpoint

- Responsibility / reason: Retain the unconditional recovery guard before a legacy diagnostic that would reveal paths/directories if enabled.
- Current status: Unconditional recovery 403 before legacy diagnostics; runtime NOT VERIFIED. Disposition **ISOLATE**; confidence High; modification risk Medium.
- Evidence: api/server-check.php:2-4,11-41.
- Calls, configuration, database, libraries and service boundary: PHP server/runtime metadata and directory listing in unreachable legacy body; no DB import.
- Consumers: Direct diagnostics callers DEPENDENCY NOT FULLY VERIFIED.
- Proposed location / relocation consequence: `Private diagnostics adapter or disabled compatibility endpoint after review`. Changing this boundary can break the listed callers, imports, URLs, state roots or response contracts; retain old-path compatibility until verified.
- Required verification: Test redacted information/status surface locally; identify consumers before move or restriction.
- Related Jira: ZL-18, ZL-48, ZL-51; ZL-12 only for covered relocation. Issue mapping does not assert completion. See [migration gates](MIGRATION_PLAN.md) and [debt register](TECHNICAL_DEBT.md).

### B01 — Guarded Baileys entry/socket adapter

- Responsibility / reason: Keep live adapter/command orchestration guarded before imports; not a demo simulator.
- Current status: Statically inspected; runtime NOT VERIFIED. Disposition **ISOLATE**; confidence High; modification risk High.
- Evidence: index.js:1-3,16-51,339-376,922-954.
- Calls, configuration, database, libraries and service boundary: Baileys/pino/qrcode-terminal/cli-progress; process config; socket/auth/store/workers/API, timers and sender-session deletion if enabled.
- Consumers: package start/dev and message/admin-command events.
- Proposed location / relocation consequence: `services/delivery/adapters/whatsapp/ plus disabled entry shim`. Changing this boundary can break the listed callers, imports, URLs, state roots or response contracts; retain old-path compatibility until verified.
- Required verification: Fake socket/store/events/clocks/commands; validity/ack repairs and explicit account approval before live startup.
- Related Jira: ZL-18, ZL-28, ZL-54; ZL-12 only for covered relocation. Issue mapping does not assert completion. See [migration gates](MIGRATION_PLAN.md) and [debt register](TECHNICAL_DEBT.md).

### B02 — Bot configuration boundary

- Responsibility / reason: Keep pure explicit process config/live guard; dotenv is not auto-loaded.
- Current status: Statically inspected; runtime NOT VERIFIED. Disposition **KEEP**; confidence High; modification risk High.
- Evidence: config/environment.js:5-32.
- Calls, configuration, database, libraries and service boundary: APP_ENV/API_URL/API_KEY/SESSION_PATH/LOCAL_MEDIA_PATH/BOT_INSTANCE_ID and explicit live flags; node:path.
- Consumers: Entry, API client and forwarder persistence paths.
- Proposed location / relocation consequence: `services/delivery/config/`. Changing this boundary can break the listed callers, imports, URLs, state roots or response contracts; retain old-path compatibility until verified.
- Required verification: Fictional config/error/local-path/disabled-startup tests before any side-effecting imports.
- Related Jira: ZL-13, ZL-48; ZL-12 only for covered relocation. Issue mapping does not assert completion. See [migration gates](MIGRATION_PLAN.md) and [debt register](TECHNICAL_DEBT.md).

### B03 — HTTP bridge client

- Responsibility / reason: Retain HTTP interface; formalize validity, redaction, false/error and completion semantics.
- Current status: Statically inspected; runtime NOT VERIFIED. Disposition **REPAIR**; confidence High; modification risk High.
- Evidence: config/api-client.js:18-86,95-101,113-125.
- Calls, configuration, database, libraries and service boundary: node:http/https and API_* config; 30-second timeout; 16 literal endpoints; token response logged and mark-used errors converted to false.
- Consumers: Entry/forwarder/cache/refresh/health workers.
- Proposed location / relocation consequence: `services/delivery/clients/website-api/`. Changing this boundary can break the listed callers, imports, URLs, state roots or response contracts; retain old-path compatibility until verified.
- Required verification: Fake HTTP JSON/status/timeout/invalid/expired/replay/ack-failure contracts; no live requests.
- Related Jira: ZL-29, ZL-30; ZL-12 only for covered relocation. Issue mapping does not assert completion. See [migration gates](MIGRATION_PLAN.md) and [debt register](TECHNICAL_DEBT.md).

### B04 — Multi-tier forwarding coordinator

- Responsibility / reason: Retain cache/group/local-file selection; delivery-success/coalescing contracts need tests.
- Current status: Statically inspected; runtime NOT VERIFIED. Disposition **REPAIR**; confidence High; modification risk High.
- Evidence: config/multi-tier-forwarder.js:12-50,229-253,259-310,478-490.
- Calls, configuration, database, libraries and service boundary: Injected socket/store/FileManager/API/UploadQueue; session-root JSON persistence, global.activeUploads and reconnect waits.
- Consumers: Bot token handler; cache warmer shares global upload state.
- Proposed location / relocation consequence: `services/delivery/core/ plus storage/cache adapters`. Changing this boundary can break the listed callers, imports, URLs, state roots or response contracts; retain old-path compatibility until verified.
- Required verification: Fake recipient acknowledgement/cache miss/stale/reconnect/restart/part/concurrency; warming success is not recipient delivery.
- Related Jira: ZL-29, ZL-30; ZL-12 only for covered relocation. Issue mapping does not assert completion. See [migration gates](MIGRATION_PLAN.md) and [debt register](TECHNICAL_DEBT.md).

### B05 — Priority upload queue

- Responsibility / reason: Retain priority/FIFO/concurrency policy but characterize promise and dedupe semantics.
- Current status: Statically inspected; runtime NOT VERIFIED. Disposition **REPAIR**; confidence High; modification risk Medium.
- Evidence: config/upload-queue.js:33-73,79-135,163-170.
- Calls, configuration, database, libraries and service boundary: EventEmitter, injected uploadHandler, queue-size/concurrency/delay limits; no own DB/network.
- Consumers: Forwarder injects uploader.
- Proposed location / relocation consequence: `services/delivery/core/queue/`. Changing this boundary can break the listed callers, imports, URLs, state roots or response contracts; retain old-path compatibility until verified.
- Required verification: Fake timers/tasks: order, limits, duplicates, failure/cancel/clear-active cases and settlement.
- Related Jira: ZL-29, ZL-30, ZL-31; ZL-12 only for covered relocation. Issue mapping does not assert completion. See [migration gates](MIGRATION_PLAN.md) and [debt register](TECHNICAL_DEBT.md).

### B06 — Local file/media adapter

- Responsibility / reason: Preserve content/part file naming without reading real media.
- Current status: Statically inspected; runtime NOT VERIFIED. Disposition **ISOLATE**; confidence High; modification risk High.
- Evidence: config/file-manager.js:15-64,145-176,231-290.
- Calls, configuration, database, libraries and service boundary: node:fs/path; LOCAL_MEDIA_PATH; movies/episodes/content/part paths; read/write/delete/directory side effects.
- Consumers: Forwarder/cache warmer/bot operations.
- Proposed location / relocation consequence: `services/delivery/adapters/local-media/; runtime data outside source`. Changing this boundary can break the listed callers, imports, URLs, state roots or response contracts; retain old-path compatibility until verified.
- Required verification: Synthetic filesystem/temp fixtures for containment/naming/missing/large parts; no actual media.
- Related Jira: ZL-42, ZL-29, ZL-52; ZL-12 only for covered relocation. Issue mapping does not assert completion. See [migration gates](MIGRATION_PLAN.md) and [debt register](TECHNICAL_DEBT.md).

### B07 — Background cache warmer

- Responsibility / reason: Keep optional warming separate from request acknowledgement.
- Current status: Statically inspected; runtime NOT VERIFIED. Disposition **ISOLATE**; confidence High; modification risk High.
- Evidence: config/cache-warmer.js:17-24,308-323,413,728-798.
- Calls, configuration, database, libraries and service boundary: node-schedule/socket/FileManager/API/global.activeUploads; __dirname-relative warming-state.json, time windows/retries.
- Consumers: Entry starts after connection; forwarder watches global state.
- Proposed location / relocation consequence: `services/delivery/workers/ with explicit private state root`. Changing this boundary can break the listed callers, imports, URLs, state roots or response contracts; retain old-path compatibility until verified.
- Required verification: Fake clock/scheduler/socket/state/recovery/coalescing tests; preserve state-root path before relocating code.
- Related Jira: ZL-29, ZL-30, ZL-49; ZL-12 only for covered relocation. Issue mapping does not assert completion. See [migration gates](MIGRATION_PLAN.md) and [debt register](TECHNICAL_DEBT.md).

### B08 — Automatic refresh worker

- Responsibility / reason: Retain optional refresh; reconcile interval property versus fixed cron separately.
- Current status: Statically inspected; runtime NOT VERIFIED. Disposition **ISOLATE**; confidence High; modification risk High.
- Evidence: config/auto-refresher.js:21,172-224,235,344,511.
- Calls, configuration, database, libraries and service boundary: node-schedule/fs/path; socket/API/cache/storage groups; 01:00 cron and refreshIntervalHours property, retries/local paths.
- Consumers: Entry/commands and media-message APIs.
- Proposed location / relocation consequence: `services/delivery/workers/`. Changing this boundary can break the listed callers, imports, URLs, state roots or response contracts; retain old-path compatibility until verified.
- Required verification: Fake clock/timezone/reconnect/retry/manual-range tests; no actual jobs started.
- Related Jira: ZL-29, ZL-30, ZL-49; ZL-12 only for covered relocation. Issue mapping does not assert completion. See [migration gates](MIGRATION_PLAN.md) and [debt register](TECHNICAL_DEBT.md).

### B09 — Health/heartbeat monitoring

- Responsibility / reason: Keep operational metrics behind redacted interface.
- Current status: Statically inspected; runtime NOT VERIFIED. Disposition **ISOLATE**; confidence High; modification risk Medium.
- Evidence: config/health-monitor.js:9-27,46-66,98-113.
- Calls, configuration, database, libraries and service boundary: node:os/timer/injected API/client; heartbeat/error/daily-stats endpoints and bot health tables.
- Consumers: Bot entry/admin monitoring.
- Proposed location / relocation consequence: `services/delivery/observability/`. Changing this boundary can break the listed callers, imports, URLs, state roots or response contracts; retain old-path compatibility until verified.
- Required verification: Fake clock/API/socket/sample metrics; validate startup/stop and no account/session data exposure.
- Related Jira: ZL-49, ZL-30; ZL-12 only for covered relocation. Issue mapping does not assert completion. See [migration gates](MIGRATION_PLAN.md) and [debt register](TECHNICAL_DEBT.md).

### B10 — Alternate direct DB client

- Responsibility / reason: Keep alternate mysql2 layer; no literal active consumer found is not proof of disuse.
- Current status: Statically inspected; runtime NOT VERIFIED. Disposition **UNRESOLVED**; confidence Medium; modification risk High.
- Evidence: config/database.js:2-10,40,111,248,315; index.js:34-51.
- Calls, configuration, database, libraries and service boundary: mysql2/promise/process DB_* and pool creation; whatsapp_tokens/message_ids/forward_logs/media_refresh_log older vocabulary.
- Consumers: DEPENDENCY NOT FULLY VERIFIED: tracked entry uses HTTP client; external scripts unknown.
- Proposed location / relocation consequence: `services/delivery/legacy-db/ only if retained; no automatic archive`. Changing this boundary can break the listed callers, imports, URLs, state roots or response contracts; retain old-path compatibility until verified.
- Required verification: Discover deployment/external consumers, schema/privacy/fake-repository tests; never connect pool to production.
- Related Jira: ZL-14, ZL-29; ZL-12 only for covered relocation. Issue mapping does not assert completion. See [migration gates](MIGRATION_PLAN.md) and [debt register](TECHNICAL_DEBT.md).

### B11 — Scheduler diagnostic sample

- Responsibility / reason: Prints correctness without assertions; sample is not regression coverage.
- Current status: Statically inspected; runtime NOT VERIFIED. Disposition **ARCHIVE CANDIDATE**; confidence High; modification risk Low.
- Evidence: test-scheduler.js:9-27; config/auto-refresher.js:224.
- Calls, configuration, database, libraries and service boundary: Date/console, 00:30 sample target vs worker 01:00; no package test script.
- Consumers: Direct developer use possible; no claimed import consumer.
- Proposed location / relocation consequence: `archive/legacy-diagnostics/ only after approved replacement`. Changing this boundary can break the listed callers, imports, URLs, state roots or response contracts; retain old-path compatibility until verified.
- Required verification: Real fake-clock regression tests, consumer/owner review and preserved rollback before archive; not executed here.
- Related Jira: ZL-31, ZL-35; ZL-12 only for covered relocation. Issue mapping does not assert completion. See [migration gates](MIGRATION_PLAN.md) and [debt register](TECHNICAL_DEBT.md).

### B12 — Bot package and lock contract

- Responsibility / reason: Preserve dependency metadata without install/upgrade.
- Current status: Statically inspected; runtime NOT VERIFIED. Disposition **KEEP**; confidence High; modification risk Medium.
- Evidence: package.json:1-30; index.js:16-51.
- Calls, configuration, database, libraries and service boundary: Baileys/pino/qrcode-terminal/cli-progress/node-schedule/mysql2, nodemon dev; dotenv declared not loaded.
- Consumers: Node tooling/deployment; installed-version compatibility NOT VERIFIED.
- Proposed location / relocation consequence: `services/delivery/ manifest+lock moved together`. Changing this boundary can break the listed callers, imports, URLs, state roots or response contracts; retain old-path compatibility until verified.
- Required verification: Future authorized locked install/dependency audit; import/CWD/start-script checks separate from structural change.
- Related Jira: ZL-13, ZL-31; ZL-12 only for covered relocation. Issue mapping does not assert completion. See [migration gates](MIGRATION_PLAN.md) and [debt register](TECHNICAL_DEBT.md).

### X01 — Guarded extractor service

- Responsibility / reason: Keep fixture/disabled boundary separate from quarantined network/browser code; conditionally used by current public feed.
- Current status: Statically inspected; runtime NOT VERIFIED. Disposition **ISOLATE**; confidence High; modification risk High.
- Evidence: fb-video-api/index.js:1-16,239-254,312; environment.js:3-15.
- Calls, configuration, database, libraries and service boundary: Express/cors/got/puppeteer-core/@sparticuz/chromium; APP_ENV/EXTRACTOR_MODE/PORT/FAKE_MEDIA_URL; loopback; no lockfile.
- Consumers: index.php conditional fresh-CDN loader; admin/manage_shots.php PHP/browser clients.
- Proposed location / relocation consequence: `services/media-extractor/ quarantined adapter`. Changing this boundary can break the listed callers, imports, URLs, state roots or response contracts; retain old-path compatibility until verified.
- Required verification: Pure config/fake HTTP fixture/disabled/timeout/fallback tests; no live extraction/Chromium; optional demo only with approved pre-resolved fixtures.
- Related Jira: ZL-18, ZL-28, ZL-51; ZL-12 only for covered relocation. Issue mapping does not assert completion. See [migration gates](MIGRATION_PLAN.md) and [debt register](TECHNICAL_DEBT.md).

### D01 — Historical bot SQL generations

- Responsibility / reason: Preserve competing schema evidence; FIXED filename is not install-order proof.
- Current status: Statically inspected; runtime NOT VERIFIED. Disposition **REPAIR**; confidence High; modification risk High.
- Evidence: database/schema.sql:6,22,41; migration_ultimate_system.sql:13,28,236; migration_ultimate_system_FIXED.sql:43-58.
- Calls, configuration, database, libraries and service boundary: Old video_tokens/forward_logs vs whatsapp_*; status-only schema lacks columns needed by current callers; routines/views/seed blocks not executed or reproduced.
- Consumers: Bridge/token/admin/alternate DB client; installed schema DEPENDENCY NOT FULLY VERIFIED.
- Proposed location / relocation consequence: `database/schema/legacy/; approved canonical migrations later`. Changing this boundary can break the listed callers, imports, URLs, state roots or response contracts; retain old-path compatibility until verified.
- Required verification: Structural diff and source-query contract; separate sanitized fixture migration/rollback issue, never import dump/seeds blindly.
- Related Jira: ZL-14, ZL-30; ZL-12 only for covered relocation. Issue mapping does not assert completion. See [migration gates](MIGRATION_PLAN.md) and [debt register](TECHNICAL_DEBT.md).

### D02 — Website parts/Shot SQL references

- Responsibility / reason: Preserve migration references separately from private dump.
- Current status: Statically inspected; runtime NOT VERIFIED. Disposition **REPAIR**; confidence High; modification risk High.
- Evidence: migrations/movie_parts.sql:5,23,27; migrations/episode_parts.sql:5; admin/migrate_shots.sql:6.
- Calls, configuration, database, libraries and service boundary: movie_parts/episode_parts/token part columns/Shot metadata alterations; complete catalogue/account/social schema not established.
- Consumers: Issuance/validation/message lookup/admin/feed.
- Proposed location / relocation consequence: `database/migrations/legacy/ before reviewed canonical version`. Changing this boundary can break the listed callers, imports, URLs, state roots or response contracts; retain old-path compatibility until verified.
- Required verification: Columns/indexes/constraints/version order on fictional fixture DB in separately authorized work; preserve external path references.
- Related Jira: ZL-14, ZL-15; ZL-12 only for covered relocation. Issue mapping does not assert completion. See [migration gates](MIGRATION_PLAN.md) and [debt register](TECHNICAL_DEBT.md).

### G01 — Repository safeguards and example contracts

- Responsibility / reason: Retain ignore, line-ending/review policy and four example contracts; no real env contents read.
- Current status: Safeguards inspected; template contracts from prior records, values not reopened. Disposition **KEEP**; confidence High; modification risk Medium.
- Evidence: .gitignore; .gitattributes; docs/SECURITY_EXPORT.md (section 5).
- Calls, configuration, database, libraries and service boundary: Git normalization/exclusions and process-variable contracts from existing security documentation; no GitHub workflow directory observed.
- Consumers: Developers/export/config readers; no automatic dotenv loader.
- Proposed location / relocation consequence: `Root safeguards unchanged; component examples move only with service review`. Changing this boundary can break the listed callers, imports, URLs, state roots or response contracts; retain old-path compatibility until verified.
- Required verification: Ignore/export policy and approved placeholder review before path changes; never copy real credentials.
- Related Jira: ZL-9, ZL-10, ZL-48; ZL-12 only for covered relocation. Issue mapping does not assert completion. See [migration gates](MIGRATION_PLAN.md) and [debt register](TECHNICAL_DEBT.md).

### G02 — Existing docs and audit evidence

- Responsibility / reason: Preserve baseline/recovery/provenance and owner edits; historical docs are not current runtime proof.
- Current status: Statically inspected; runtime NOT VERIFIED. Disposition **KEEP**; confidence High; modification risk Low.
- Evidence: docs/BASELINE.md:8,568; docs/recovery/STATUS.md:5; README.md.
- Calls, configuration, database, libraries and service boundary: Relative links/pinned hashes/source-authority rules; component README claims may contradict current code.
- Consumers: Owner/future tasks/reviewers.
- Proposed location / relocation consequence: `Existing docs retained; five new architecture docs link historical evidence`. Changing this boundary can break the listed callers, imports, URLs, state roots or response contracts; retain old-path compatibility until verified.
- Required verification: Preserve all existing bytes except append contribution entry; link/privacy checks; import dates do not establish feature authorship.
- Related Jira: ZL-9, ZL-10, ZL-11, ZL-35; ZL-12 only for covered relocation. Issue mapping does not assert completion. See [migration gates](MIGRATION_PLAN.md) and [debt register](TECHNICAL_DEBT.md).

### G03 — Syntax-only checker

- Responsibility / reason: Retain developer parser checks without claiming functional coverage.
- Current status: Statically inspected; runtime NOT VERIFIED. Disposition **KEEP**; confidence High; modification risk Low.
- Evidence: scripts/check-source.cjs:1-35.
- Calls, configuration, database, libraries and service boundary: Git tracked paths, Node parser and optional PHP_BINARY/REQUIRE_PHP; parsers do not start application modules.
- Consumers: README/CONTRIBUTING and developer invocation.
- Proposed location / relocation consequence: `scripts/ unchanged`. Changing this boundary can break the listed callers, imports, URLs, state roots or response contracts; retain old-path compatibility until verified.
- Required verification: ZL-11 invokes parsers directly rather than project scripts; future moves must keep syntax scope explicit.
- Related Jira: ZL-13, ZL-31; ZL-12 only for covered relocation. Issue mapping does not assert completion. See [migration gates](MIGRATION_PLAN.md) and [debt register](TECHNICAL_DEBT.md).

### G04 — Public-export utility and synthetic suite

- Responsibility / reason: Retain deny-by-default packaging; new architecture paths are not automatically approved.
- Current status: Statically inspected; runtime NOT VERIFIED. Disposition **KEEP**; confidence High; modification risk Medium.
- Evidence: scripts/prepare-public-export.cjs:8-12; docs/SECURITY_EXPORT.md (sections 6-10).
- Calls, configuration, database, libraries and service boundary: Node stdlib, exact allowlist/content/SQL/destination/hash gates; synthetic external-temp fixtures. No utility/test execution here.
- Consumers: Future owner-approved export workflow.
- Proposed location / relocation consequence: `scripts/ unchanged; explicit policy update after future moves`. Changing this boundary can break the listed callers, imports, URLs, state roots or response contracts; retain old-path compatibility until verified.
- Required verification: Approved synthetic policy tests after allowlist changes, no real export. Prior 32-test result is historical, not rerun in ZL-11.
- Related Jira: ZL-10, ZL-31; ZL-12 only for covered relocation. Issue mapping does not assert completion. See [migration gates](MIGRATION_PLAN.md) and [debt register](TECHNICAL_DEBT.md).

### G05 — Orphan website lockfile

- Responsibility / reason: Retain empty historical lock with no matching website manifest.
- Current status: Statically inspected; runtime NOT VERIFIED. Disposition **UNRESOLVED**; confidence High; modification risk Medium.
- Evidence: package-lock.json:1; package.json absence metadata.
- Calls, configuration, database, libraries and service boundary: lockfileVersion 3, zero packages, historical name; does not lock extractor dependencies.
- Consumers: Build/deployment consumers DEPENDENCY NOT FULLY VERIFIED.
- Proposed location / relocation consequence: `Keep path pending history/owner review`. Changing this boundary can break the listed callers, imports, URLs, state roots or response contracts; retain old-path compatibility until verified.
- Required verification: Establish provenance/consumers and approve actual service lock separately; no deletion or reproducibility claim.
- Related Jira: ZL-13, ZL-31; ZL-12 only for covered relocation. Issue mapping does not assert completion. See [migration gates](MIGRATION_PLAN.md) and [debt register](TECHNICAL_DEBT.md).

### G06 — Bundled PHPMailer vendor group

- Responsibility / reason: Group 70 third-party files; no individual first-party classifications.
- Current status: Metadata/import boundary inspected; vendor internals not audited. Disposition **ISOLATE**; confidence High; modification risk Medium.
- Evidence: includes/mailer.php:12-14; tracked vendor metadata.
- Calls, configuration, database, libraries and service boundary: PHPMailer source/license/composer metadata, wrapper's three exact imports; no root Composer restoration established.
- Consumers: Mailer; other external legacy consumers NOT FULLY VERIFIED.
- Proposed location / relocation consequence: `Keep path initially; separate vendor restoration/ownership policy later`. Changing this boundary can break the listed callers, imports, URLs, state roots or response contracts; retain old-path compatibility until verified.
- Required verification: License/version/import-path/restoration review; no installation or vendor rewrite in structural move.
- Related Jira: ZL-19, ZL-31, ZL-51; ZL-12 only for covered relocation. Issue mapping does not assert completion. See [migration gates](MIGRATION_PLAN.md) and [debt register](TECHNICAL_DEBT.md).

### G07 — Binary identity assets

- Responsibility / reason: Retain three logos/icons; rights not inferred and no private media used.
- Current status: Metadata/reference only; content and rights NOT VERIFIED. Disposition **UNRESOLVED**; confidence Medium; modification risk Medium.
- Evidence: includes/header.php:8-10; three tracked binary paths.
- Calls, configuration, database, libraries and service boundary: Image/favicon/manifest references; binary contents not inspected.
- Consumers: Public pages and browser identity.
- Proposed location / relocation consequence: `Same public URL paths under apps/web/public/ after review`. Changing this boundary can break the listed callers, imports, URLs, state roots or response contracts; retain old-path compatibility until verified.
- Required verification: Metadata/reference/rights approval; use legally usable fixtures only.
- Related Jira: ZL-33, ZL-52; ZL-12 only for covered relocation. Issue mapping does not assert completion. See [migration gates](MIGRATION_PLAN.md) and [debt register](TECHNICAL_DEBT.md).


## Excluded and uncertain local boundaries

These are not additional tracked-source groups and their private content was not
opened. Classification describes preservation/isolation policy, not a security
certification or approved archive operation.

### P01 — Ignored alternate bot programs

- Disposition: **UNRESOLVED**; confidence Low; risk High.
- Paths: `whatsapp-bot-deploy/baileys-connection-temp.js`; `whatsapp-bot-deploy/index-before-fix.js`; `whatsapp-bot-deploy/index-temp.js`; `whatsapp-bot-deploy/index-whatsappwebjs-OLD.js`; `whatsapp-bot-deploy/index-whatsappwebjs-original.js`; `whatsapp-bot-deploy/refresh-method-new.js`.
- Responsibility/reason: Preserve six alternative/fragment programs as evidence; names do not prove safe obsolescence.
- Evidence/status: Six known paths exist by metadata; root ignore policy and prior baseline corroborate legacy/temporary status. Metadata only; no content or runtime inspection.
- Dependencies/configuration/consumers: DEPENDENCY NOT FULLY VERIFIED: contents deliberately not opened; may contain identifiers/configuration. Unknown external/developer/deployment invocation.
- Proposed disposition: Remain ignored in place. Any future preserved archive is a separately approved decision.
- Verification/Jira: Private redacted dependency/history review and tested replacement before archive approval; never delete by filename. ZL-12 hold; ZL-18/28/54 preserve and review optional legacy delivery.

### P02 — Private/generated/runtime assets

- Disposition: **ISOLATE**; confidence High; risk High.
- Paths: `.recovery-private/`; `whatsapp-bot-deploy/node_modules/`; `whatsapp-bot-deploy/whatsapp-session/`; `whatsapp-bot-deploy/.wwebjs_auth/`; `whatsapp-bot-deploy/bot-media/`; `Zinema.lk Website/uploads/`; `Zinema.lk Website/assets/uploads/`.
- Responsibility/reason: Keep credentials/auth/logs/dumps/private media and generated dependencies outside public/export scope.
- Evidence/status: Existing security inventory and ignored-boundary metadata; private contents not enumerated. Excluded/private contents NOT VERIFIED.
- Dependencies/configuration/consumers: Runtime env/session/media/state roots and external dependency restoration; actual contents unverified. Legacy runtime/account services; full graph intentionally not inspected.
- Proposed disposition: Private external runtime roots and generated dependencies, never apps/web/public/; no moves now.
- Verification/Jira: Owner-approved private backup/retention/rights/recovery plan before any state-root change; never copy real sessions into fixtures. Separate security/data-retention approval; not a ZL-12 source move.


## Complete tracked-path ledger

These 195 rows plus the 70-file vendor aggregate account for all 265 tracked
paths at task start. This ledger is a membership inventory, not an export
allowlist. New ZL-11 documents are outputs, not retroactively inserted into the
original source baseline.

| Relative path | Group | Disposition |
| --- | --- | --- |
| `.env.example` | G01 | KEEP |
| `.gitattributes` | G01 | KEEP |
| `.github/pull_request_template.md` | G01 | KEEP |
| `.gitignore` | G01 | KEEP |
| `AGENTS.md` | G02 | KEEP |
| `CONTRIBUTING.md` | G02 | KEEP |
| `README.md` | G02 | KEEP |
| `Zinema.lk Website/.env.example` | G01 | KEEP |
| `Zinema.lk Website/.gitignore` | G01 | KEEP |
| `Zinema.lk Website/.htaccess` | W01 | REPAIR |
| `Zinema.lk Website/JW_PLAYER_SETUP.md` | G02 | KEEP |
| `Zinema.lk Website/README.md` | G02 | KEEP |
| `Zinema.lk Website/_bottom_nav.php` | W13 | KEEP |
| `Zinema.lk Website/_pagination.php` | W13 | KEEP |
| `Zinema.lk Website/_top_nav.php` | W13 | KEEP |
| `Zinema.lk Website/admin/_layout.php` | W14 | REPAIR |
| `Zinema.lk Website/admin/analytics.php` | W17 | REPAIR |
| `Zinema.lk Website/admin/assets/admin.css` | W14 | REPAIR |
| `Zinema.lk Website/admin/assets/admin.js` | W14 | REPAIR |
| `Zinema.lk Website/admin/collections.php` | W15 | REPAIR |
| `Zinema.lk Website/admin/config.php` | W02 | REPAIR |
| `Zinema.lk Website/admin/cron/cleanup-tokens.php` | W23 | ISOLATE |
| `Zinema.lk Website/admin/cron/reset-daily-stats.php` | W23 | ISOLATE |
| `Zinema.lk Website/admin/episodes.php` | W15 | REPAIR |
| `Zinema.lk Website/admin/functions.php` | W14 | REPAIR |
| `Zinema.lk Website/admin/index.php` | W14 | REPAIR |
| `Zinema.lk Website/admin/login.php` | W14 | REPAIR |
| `Zinema.lk Website/admin/logout.php` | W14 | REPAIR |
| `Zinema.lk Website/admin/manage_shots.php` | W16 | REPAIR |
| `Zinema.lk Website/admin/migrate_shots.sql` | D02 | REPAIR |
| `Zinema.lk Website/admin/movies.php` | W15 | REPAIR |
| `Zinema.lk Website/admin/run_migration.php` | W23 | ISOLATE |
| `Zinema.lk Website/admin/series.php` | W15 | REPAIR |
| `Zinema.lk Website/admin/settings.php` | W17 | REPAIR |
| `Zinema.lk Website/admin/trailers.php` | W15 | REPAIR |
| `Zinema.lk Website/admin/whatsapp-bot-tracking.php` | W17 | REPAIR |
| `Zinema.lk Website/ajax_forgot_password.php` | W04 | REPAIR |
| `Zinema.lk Website/ajax_login.php` | W04 | REPAIR |
| `Zinema.lk Website/ajax_resend_verification.php` | W04 | REPAIR |
| `Zinema.lk Website/ajax_reset_password.php` | W04 | REPAIR |
| `Zinema.lk Website/ajax_signup.php` | W04 | REPAIR |
| `Zinema.lk Website/api/add_comment.php` | W08 | REPAIR |
| `Zinema.lk Website/api/auth-handler.php` | W04 | REPAIR |
| `Zinema.lk Website/api/check-session.php` | W04 | REPAIR |
| `Zinema.lk Website/api/collections.php` | W10 | REPAIR |
| `Zinema.lk Website/api/comments.php` | W11 | REPAIR |
| `Zinema.lk Website/api/favorite_shot.php` | W08 | REPAIR |
| `Zinema.lk Website/api/get_comments.php` | W08 | REPAIR |
| `Zinema.lk Website/api/includes/comment_helpers.php` | W11 | REPAIR |
| `Zinema.lk Website/api/like_shot.php` | W08 | REPAIR |
| `Zinema.lk Website/api/live_search.php` | W10 | REPAIR |
| `Zinema.lk Website/api/movies.php` | W10 | REPAIR |
| `Zinema.lk Website/api/payment-notify.php` | W21 | ISOLATE |
| `Zinema.lk Website/api/record_view.php` | W08 | REPAIR |
| `Zinema.lk Website/api/search_content.php` | W10 | REPAIR |
| `Zinema.lk Website/api/series.php` | W10 | REPAIR |
| `Zinema.lk Website/api/server-check.php` | W29 | ISOLATE |
| `Zinema.lk Website/api/subscribe.php` | W21 | ISOLATE |
| `Zinema.lk Website/api/track.php` | W22 | REPAIR |
| `Zinema.lk Website/api/trailers.php` | W12 | REPAIR |
| `Zinema.lk Website/api/whatsapp/add-message-id.php` | W20 | REPAIR |
| `Zinema.lk Website/api/whatsapp/bot-error.php` | W20 | REPAIR |
| `Zinema.lk Website/api/whatsapp/bot-heartbeat.php` | W20 | REPAIR |
| `Zinema.lk Website/api/whatsapp/config.php` | W20 | REPAIR |
| `Zinema.lk Website/api/whatsapp/get-aging-messages.php` | W20 | REPAIR |
| `Zinema.lk Website/api/whatsapp/get-all-message-ids.php` | W20 | REPAIR |
| `Zinema.lk Website/api/whatsapp/get-message-id.php` | W20 | REPAIR |
| `Zinema.lk Website/api/whatsapp/get-message-ids.php` | W20 | REPAIR |
| `Zinema.lk Website/api/whatsapp/get-movie-parts.php` | W20 | REPAIR |
| `Zinema.lk Website/api/whatsapp/get-popular-content.php` | W20 | REPAIR |
| `Zinema.lk Website/api/whatsapp/get-popular-files.php` | W20 | REPAIR |
| `Zinema.lk Website/api/whatsapp/log-cache-warming.php` | W20 | REPAIR |
| `Zinema.lk Website/api/whatsapp/log-forward.php` | W20 | REPAIR |
| `Zinema.lk Website/api/whatsapp/log-refresh.php` | W20 | REPAIR |
| `Zinema.lk Website/api/whatsapp/mark-used.php` | W20 | REPAIR |
| `Zinema.lk Website/api/whatsapp/needs-refresh.php` | W20 | REPAIR |
| `Zinema.lk Website/api/whatsapp/refresh-stats.php` | W20 | REPAIR |
| `Zinema.lk Website/api/whatsapp/update-cache-message-id.php` | W20 | REPAIR |
| `Zinema.lk Website/api/whatsapp/update-daily-stats.php` | W20 | REPAIR |
| `Zinema.lk Website/api/whatsapp/update-forward-stats.php` | W20 | REPAIR |
| `Zinema.lk Website/api/whatsapp/validate-token.php` | W20 | REPAIR |
| `Zinema.lk Website/assets/css/style.css` | W27 | KEEP |
| `Zinema.lk Website/assets/images/README.txt` | G02 | KEEP |
| `Zinema.lk Website/assets/images/logo.png` | G07 | UNRESOLVED |
| `Zinema.lk Website/assets/images/tab-logo.png` | G07 | UNRESOLVED |
| `Zinema.lk Website/assets/js/download.js` | W25 | ARCHIVE CANDIDATE |
| `Zinema.lk Website/assets/js/main.js` | W25 | ARCHIVE CANDIDATE |
| `Zinema.lk Website/assets/js/trailers.js` | W25 | ARCHIVE CANDIDATE |
| `Zinema.lk Website/collection-details.php` | W07 | REPAIR |
| `Zinema.lk Website/collections.php` | W06 | REPAIR |
| `Zinema.lk Website/css/comments.css` | W11 | REPAIR |
| `Zinema.lk Website/css/desktop-scroll-fix.css` | W27 | KEEP |
| `Zinema.lk Website/css/desktop-tablet.css` | W27 | KEEP |
| `Zinema.lk Website/css/nav_spacing.css` | W27 | KEEP |
| `Zinema.lk Website/css/pages.css` | W27 | KEEP |
| `Zinema.lk Website/css/pagination_styles.css` | W27 | KEEP |
| `Zinema.lk Website/css/profile-style.css` | W27 | KEEP |
| `Zinema.lk Website/css/responsive.css` | W27 | KEEP |
| `Zinema.lk Website/css/search_popup_styles.css` | W27 | KEEP |
| `Zinema.lk Website/css/shots-style.css` | W27 | KEEP |
| `Zinema.lk Website/css/style.css` | W27 | KEEP |
| `Zinema.lk Website/css/trailer-feed.css` | W12 | REPAIR |
| `Zinema.lk Website/css/video-player.css` | W27 | KEEP |
| `Zinema.lk Website/download.php` | W19 | ISOLATE |
| `Zinema.lk Website/favicon.ico` | G07 | UNRESOLVED |
| `Zinema.lk Website/fb-video-api/.env.example` | G01 | KEEP |
| `Zinema.lk Website/fb-video-api/.gitignore` | G01 | KEEP |
| `Zinema.lk Website/fb-video-api/README.md` | G02 | KEEP |
| `Zinema.lk Website/fb-video-api/environment.js` | X01 | ISOLATE |
| `Zinema.lk Website/fb-video-api/index.js` | X01 | ISOLATE |
| `Zinema.lk Website/fb-video-api/package.json` | X01 | ISOLATE |
| `Zinema.lk Website/find_movies.php` | W06 | REPAIR |
| `Zinema.lk Website/google-callback.php` | W05 | ISOLATE |
| `Zinema.lk Website/includes/TrailerManager.php` | W12 | REPAIR |
| `Zinema.lk Website/includes/ads.php` | W24 | KEEP |
| `Zinema.lk Website/includes/csrf_helper.php` | W03 | KEEP |
| `Zinema.lk Website/includes/database.php` | W02 | REPAIR |
| `Zinema.lk Website/includes/environment.php` | W02 | REPAIR |
| `Zinema.lk Website/includes/footer.php` | W25 | ARCHIVE CANDIDATE |
| `Zinema.lk Website/includes/functions.php` | W28 | UNRESOLVED |
| `Zinema.lk Website/includes/header.php` | W25 | ARCHIVE CANDIDATE |
| `Zinema.lk Website/includes/mailer.php` | W05 | ISOLATE |
| `Zinema.lk Website/includes/rate_limiter.php` | W03 | KEEP |
| `Zinema.lk Website/includes/settings.php` | W18 | REPAIR |
| `Zinema.lk Website/includes/whatsapp_token.php` | W19 | ISOLATE |
| `Zinema.lk Website/index.php` | W08 | REPAIR |
| `Zinema.lk Website/js/activeNav.js` | W26 | UNRESOLVED |
| `Zinema.lk Website/js/collection.js` | W26 | UNRESOLVED |
| `Zinema.lk Website/js/collections.js` | W26 | UNRESOLVED |
| `Zinema.lk Website/js/comments.js` | W11 | REPAIR |
| `Zinema.lk Website/js/countdown-timer.js` | W26 | UNRESOLVED |
| `Zinema.lk Website/js/download-handler.js` | W26 | UNRESOLVED |
| `Zinema.lk Website/js/main.js` | W26 | UNRESOLVED |
| `Zinema.lk Website/js/main.min.js` | W26 | UNRESOLVED |
| `Zinema.lk Website/js/nav.js` | W26 | UNRESOLVED |
| `Zinema.lk Website/js/navbar.js` | W26 | UNRESOLVED |
| `Zinema.lk Website/js/series.js` | W26 | UNRESOLVED |
| `Zinema.lk Website/js/subscribe.js` | W21 | ISOLATE |
| `Zinema.lk Website/js/tracker.js` | W22 | REPAIR |
| `Zinema.lk Website/js/trailer-feed.js` | W12 | REPAIR |
| `Zinema.lk Website/js/tv-series.js` | W26 | UNRESOLVED |
| `Zinema.lk Website/js/video-player.js` | W26 | UNRESOLVED |
| `Zinema.lk Website/linkedin_project_roadmap.md` | G02 | KEEP |
| `Zinema.lk Website/login.php` | W04 | REPAIR |
| `Zinema.lk Website/logout.php` | W04 | REPAIR |
| `Zinema.lk Website/manifest.json` | W01 | REPAIR |
| `Zinema.lk Website/migrations/episode_parts.sql` | D02 | REPAIR |
| `Zinema.lk Website/migrations/movie_parts.sql` | D02 | REPAIR |
| `Zinema.lk Website/movie-comments.php` | W11 | REPAIR |
| `Zinema.lk Website/movie-details.php` | W07 | REPAIR |
| `Zinema.lk Website/movies.php` | W06 | REPAIR |
| `Zinema.lk Website/package-lock.json` | G05 | UNRESOLVED |
| `Zinema.lk Website/pages/download.php` | W25 | ARCHIVE CANDIDATE |
| `Zinema.lk Website/pages/movie-detail.php` | W25 | ARCHIVE CANDIDATE |
| `Zinema.lk Website/pages/trailers.php` | W25 | ARCHIVE CANDIDATE |
| `Zinema.lk Website/profile.php` | W09 | REPAIR |
| `Zinema.lk Website/reset-password.php` | W04 | REPAIR |
| `Zinema.lk Website/robots.txt` | W01 | REPAIR |
| `Zinema.lk Website/series-details.php` | W07 | REPAIR |
| `Zinema.lk Website/signup.php` | W04 | REPAIR |
| `Zinema.lk Website/sitemap.php` | W01 | REPAIR |
| `Zinema.lk Website/trailers.php` | W12 | REPAIR |
| `Zinema.lk Website/tv-series.php` | W06 | REPAIR |
| `Zinema.lk Website/verify-email.php` | W04 | REPAIR |
| `Zinema.lk Website/whatsapp-watch.php` | W19 | ISOLATE |
| `docs/BASELINE.md` | G02 | KEEP |
| `docs/CONTRIBUTIONS.md` | G02 | KEEP |
| `docs/SECURITY_EXPORT.md` | G02 | KEEP |
| `docs/baseline-source-sha256.txt` | G02 | KEEP |
| `docs/recovery/DECISIONS.md` | G02 | KEEP |
| `docs/recovery/PROJECT_DESCRIPTION.md` | G02 | KEEP |
| `docs/recovery/PUBLISHING.md` | G02 | KEEP |
| `docs/recovery/STATUS.md` | G02 | KEEP |
| `scripts/check-source.cjs` | G03 | KEEP |
| `scripts/prepare-public-export.cjs` | G04 | KEEP |
| `scripts/test-public-export.cjs` | G04 | KEEP |
| `whatsapp-bot-deploy/.env.example` | G01 | KEEP |
| `whatsapp-bot-deploy/.gitignore` | G01 | KEEP |
| `whatsapp-bot-deploy/README.md` | G02 | KEEP |
| `whatsapp-bot-deploy/config/api-client.js` | B03 | REPAIR |
| `whatsapp-bot-deploy/config/auto-refresher.js` | B08 | ISOLATE |
| `whatsapp-bot-deploy/config/cache-warmer.js` | B07 | ISOLATE |
| `whatsapp-bot-deploy/config/database.js` | B10 | UNRESOLVED |
| `whatsapp-bot-deploy/config/environment.js` | B02 | KEEP |
| `whatsapp-bot-deploy/config/file-manager.js` | B06 | ISOLATE |
| `whatsapp-bot-deploy/config/health-monitor.js` | B09 | ISOLATE |
| `whatsapp-bot-deploy/config/multi-tier-forwarder.js` | B04 | REPAIR |
| `whatsapp-bot-deploy/config/upload-queue.js` | B05 | REPAIR |
| `whatsapp-bot-deploy/database/migration_ultimate_system.sql` | D01 | REPAIR |
| `whatsapp-bot-deploy/database/migration_ultimate_system_FIXED.sql` | D01 | REPAIR |
| `whatsapp-bot-deploy/database/schema.sql` | D01 | REPAIR |
| `whatsapp-bot-deploy/index.js` | B01 | ISOLATE |
| `whatsapp-bot-deploy/package-lock.json` | B12 | KEEP |
| `whatsapp-bot-deploy/package.json` | B12 | KEEP |
| `whatsapp-bot-deploy/test-scheduler.js` | B11 | ARCHIVE CANDIDATE |
| `Zinema.lk Website/includes/PHPMailer/` (70 tracked vendor files) | G06 | ISOLATE |

## Dependency uncertainties and migration holds

- Every UNRESOLVED group and P01 remains **DEPENDENCY NOT FULLY VERIFIED**.
- Direct URL, deployed scheduler/CLI invocation, dynamic PHP include, browser
  loader, DB-stored asset/URL and external callback consumers are not disproved
  by an absent literal import.
- Literal include targets exist, but include_path/CWD and Apache/PHP execution
  remain unverified. Computed include targets were traced statically only.
- The 81 route candidates in [current architecture](CURRENT_ARCHITECTURE.md)
  exclude helper/partial files for presentation; that exclusion is not evidence
  those helper URLs are inaccessible.
- No module is removed, moved, enabled or reclassified automatically by a future
  directory scaffold. Resolve each dossier's tests and ownership first.

## Related records

- [Current architecture and route ledger](CURRENT_ARCHITECTURE.md)
- [Target architecture — all new boundaries PLANNED](TARGET_ARCHITECTURE.md)
- [Reversible migration plan](MIGRATION_PLAN.md)
- [Prioritized technical debt](TECHNICAL_DEBT.md)
- [Existing baseline](../BASELINE.md), [contributions](../CONTRIBUTIONS.md),
  [security/export policy](../SECURITY_EXPORT.md),
  [recovery status](../recovery/STATUS.md) and
  [decisions](../recovery/DECISIONS.md)

The existing recovery records are preserved as inputs because this task limits
writes to new architecture documentation and the appended contribution log.
ZL-11 completion/decisions are recorded here and in that log. ZL-12 is not started.

## Production relevance and supplied Jira ownership

The exact-path dossiers and ledger above remain authoritative for group membership,
consumers, dependencies, confidence, change risk, target and relocation tests.
All 51 classifications are retained; P01/P02 are separate excluded boundaries.
KEEP is not production approval; no archive or relocation is authorized.
Mappings use the owner-supplied planning list in [the gap analysis](PRODUCTION_GAP_ANALYSIS.md).

| Group | Production relevance / safe disposition | Existing stories |
| --- | --- | --- |
| B01 | Keep live adapter/command orchestration guarded before imports; not a demo simulator. | ZL-18, ZL-28, ZL-54 |
| B02 | Keep pure explicit process config/live guard; dotenv is not auto-loaded. | ZL-13, ZL-48 |
| B03 | Retain HTTP interface; formalize validity, redaction, false/error and completion semantics. | ZL-29, ZL-30 |
| B04 | Retain cache/group/local-file selection; delivery-success/coalescing contracts need tests. | ZL-29, ZL-30 |
| B05 | Retain priority/FIFO/concurrency policy but characterize promise and dedupe semantics. | ZL-29, ZL-30, ZL-31 |
| B06 | Preserve content/part file naming without reading real media. | ZL-42, ZL-29, ZL-52 |
| B07 | Keep optional warming separate from request acknowledgement. | ZL-29, ZL-30, ZL-49 |
| B08 | Retain optional refresh; reconcile interval property versus fixed cron separately. | ZL-29, ZL-30, ZL-49 |
| B09 | Keep operational metrics behind redacted interface. | ZL-49, ZL-30 |
| B10 | Keep alternate mysql2 layer; no literal active consumer found is not proof of disuse. | ZL-14, ZL-29 |
| B11 | Prints correctness without assertions; sample is not regression coverage. | ZL-31, ZL-35 |
| B12 | Preserve dependency metadata without install/upgrade. | ZL-13, ZL-31 |
| D01 | Preserve competing schema evidence; FIXED filename is not install-order proof. | ZL-14, ZL-30 |
| D02 | Preserve migration references separately from private dump. | ZL-14, ZL-15 |
| G01 | Retain ignore, line-ending/review policy and four example contracts; no real env contents read. | ZL-9, ZL-10, ZL-48 |
| G02 | Preserve baseline/recovery/provenance and owner edits; historical docs are not current runtime proof. | ZL-9, ZL-10, ZL-11, ZL-35 |
| G03 | Retain developer parser checks without claiming functional coverage. | ZL-13, ZL-31 |
| G04 | Retain deny-by-default packaging; new architecture paths are not automatically approved. | ZL-10, ZL-31 |
| G05 | Retain empty historical lock with no matching website manifest. | ZL-13, ZL-31 |
| G06 | Group 70 third-party files; no individual first-party classifications. | ZL-19, ZL-31, ZL-51 |
| G07 | Retain three logos/icons; rights not inferred and no private media used. | ZL-33, ZL-52 |
| W01 | Preserve slug/PHP URLs, sitemap and browser metadata; error-page targets are absent. | ZL-12, ZL-48, ZL-31 |
| W02 | Separate pure config from session/database side effects while preserving both interfaces. | ZL-14, ZL-19, ZL-13 |
| W03 | Retain reusable primitives; inclusion is not proof that callers enforce protection. | ZL-19, ZL-30, ZL-51 |
| W04 | Preserve direct form, AJAX and JSON variants pending one tested contract. | ZL-19, ZL-53 |
| W05 | Keep external identity/mail disabled or local behind explicit adapters. | ZL-19, ZL-18, ZL-48 |
| W06 | Retain filter/pagination/partial HTML; SQL, templates and inline auth are coupled. | ZL-16, ZL-27 |
| W07 | Preserve detail/episode links and native player/resume behavior; not verified streaming readiness. | ZL-17, ZL-45, ZL-44 |
| W08 | Keep feed/social core; conditional fresh-CDN loader couples public playback to extraction. | ZL-17, ZL-25, ZL-55 |
| W09 | Retain saved feed and account overlays; large controller mixes UI/query/auth. | ZL-19, ZL-23, ZL-53 |
| W10 | Retain existing JSON contracts and linked-content search selectors. | ZL-16, ZL-55 |
| W11 | Keep separately routed movie comments; bootstrap and dynamic subpaths need contract review. | ZL-14, ZL-17, ZL-55 |
| W12 | Keep trailers separate from Shots; PDO and generated endpoint assumptions are unresolved. | ZL-14, ZL-42, ZL-45 |
| W13 | Retain shared PHP and inline browser contracts. | ZL-12, ZL-27 |
| W14 | Retain admin authorization; helper/script usage needs full consumer confirmation. | ZL-19, ZL-55 |
| W15 | Keep CRUD; GET deletion and upload boundaries need later security repair. | ZL-19, ZL-42, ZL-52, ZL-55 |
| W16 | Retain Shot management and separate extraction from CRUD later. | ZL-18, ZL-42, ZL-52 |
| W17 | Retain operations UI; separate local paths and complex SQL from public controllers. | ZL-19, ZL-49, ZL-55 |
| W18 | Retain settings lookup but separate schema mutation in later repair. | ZL-14, ZL-47 |
| W19 | Retain optional access adapter and all guards; do not restore historic delivery flow now. | ZL-28, ZL-30, ZL-54 |
| W20 | Retain twenty endpoint files and shared config; unify guarded contract only after characterization. | ZL-14, ZL-29, ZL-30 |
| W21 | Retain disabled payment/subscription paths. | ZL-18 |
| W22 | Keep page/watch telemetry with path/privacy contracts. | ZL-49, ZL-53 |
| W23 | Retain disabled schema/cleanup jobs and unknown deployment references. | ZL-18, ZL-14, ZL-35 |
| W24 | Keep no-op helper interface; no ad network is enabled. | ZL-12, ZL-18 |
| W25 | Parallel TODO/sample pages remain linked/routable; future archive candidate only. | ZL-12, ZL-27, ZL-31 |
| W26 | Retain separate nav/player/catalogue/countdown modules pending consumer/DOM audit. | ZL-12, ZL-31, ZL-27 |
| W27 | Preserve visual assets; duplicate basenames are not proof of redundant content. | ZL-12, ZL-27 |
| W28 | No literal consumer confirmed; lack of imports does not prove disuse. | ZL-12, ZL-31 |
| W29 | Retain the unconditional recovery guard before a legacy diagnostic that would reveal paths/directories if enabled. | ZL-18, ZL-48, ZL-51 |
| X01 | Keep fixture/disabled boundary separate from quarantined network/browser code; conditionally used by current public feed. | ZL-18, ZL-28, ZL-51 |
| P01 | Preserve six alternative/fragment programs as evidence; names do not prove safe obsolescence. | ZL-18, ZL-28, ZL-54 |
| P02 | Keep credentials/auth/logs/dumps/private media and generated dependencies outside public/export scope. | ZL-10, ZL-50, ZL-53 |

New production capabilities are proposals, not additional existing source groups:
[streaming and private CDN](STREAMING_ARCHITECTURE.md), [AI/voice](AI_ARCHITECTURE.md),
[conditional messaging](WHATSAPP_DELIVERY_ARCHITECTURE.md),
[operations/security](PRODUCTION_DEPLOYMENT.md) and [24 gaps](PRODUCTION_GAP_ANALYSIS.md).
A targeted additional inspection identifies unquoted upload-derived ffmpeg command
construction in admin/trailers.php:17–29 (ZL-42/ZL-51); runtime exploitability is
NOT VERIFIED. The original five-document completion record describes the earlier
static blueprint; this production extension supplies ten architecture documents.
