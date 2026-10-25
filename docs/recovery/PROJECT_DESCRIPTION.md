# Zinema project description

Date: 2026-10-23. Evidence: local static source inspection, with limited JavaScript syntax validation. This document describes code and intended flows, not a verified running service.

## Purpose and audience

Zinema is a web entertainment discovery and media-delivery project. It combines a movie and TV-series catalogue, a vertical short-video discovery feed called Shots, user accounts and social interactions, a content administration area, and a separate WhatsApp video-delivery bot. Sinhala dubbed/subtitled filters and Sinhala bot messages indicate an intended Sri Lankan/Sinhala-speaking audience.

The main product journey is: discover a short clip, open its linked movie, series, or collection, then watch a configured video in the browser or request delivery through WhatsApp. These are source-described journeys; current recovery restrictions interrupt some paths.

## Main components

| Component | Location | Responsibility |
| --- | --- | --- |
| PHP website | `Zinema.lk Website/` | Pages, catalogue queries, account flows, social endpoints and administration |
| MySQL schema and migrations | Website `Database.sql`, website `migrations/`, bot `database/` | Content, users, interactions, delivery mappings, tokens and operational records |
| WhatsApp delivery bot | `whatsapp-bot-deploy/` | Receive commands, validate requests through the website API, forward or upload media, report health |
| Video URL extraction service | Website `fb-video-api/` | Express API containing Facebook media URL extraction logic; currently guarded during recovery |

The website is predominantly procedural, server-rendered PHP with HTML, CSS and browser JavaScript. SQL, page rendering and request handling often share the same file. It is not organized around a single application framework or a uniform controller/service structure.

The main PHP bootstrap is `admin/config.php`, even for public pages. It establishes configuration, sessions, a MySQLi connection named `$conn`, remember-me handling and an admin-login helper. A separate `includes/database.php` creates a PDO connection named `$pdo`, used by another set of helpers and pages. This split is relevant to incomplete integration paths.

## Discovery and catalogue

### Shots

The root `index.php` is a vertical short-video feed rather than a conventional catalogue homepage. Its queries retrieve Shots and social counts. Logged-in users first receive unseen Shots; when none remain, the code falls back to all Shots in random order. Anonymous visitors receive a random feed. This is a simple history-aware selection rule, not evidence of a machine-learning recommendation system.

Shots support likes, comments, saved favourites, sharing and view recording. A Shot can link to a movie, series or collection. The browser code handles active-video playback, audio state, prefetching and cached/fresh media URLs. Profile pages retrieve saved Shots from `user_favorites`.

Key evidence: `index.php`, `api/like_shot.php`, `api/add_comment.php`, `api/get_comments.php`, `api/favorite_shot.php`, `api/record_view.php`, `profile.php`.

### Movies, series and collections

Movie browsing includes title search, genre filtering, Sinhala dubbed/subtitled filtering, newest/trending ordering, a hero area based on view counts, pagination and a mobile-oriented load-more path. Movie details include metadata, cover artwork, a view increment, watch controls and a download link.

Series have episodes ordered by season and episode number. The detail page groups episodes into seasons and updates playback and download actions when an episode is selected. Collections group movies, using a movie's `collection_id`.

Key evidence: `movies.php`, `movie-details.php`, `tv-series.php`, `series-details.php`, `collections.php`, `collection-details.php`, catalogue/search endpoints under `api/`.

## Playback and media handling

The principal movie and episode detail pages insert native HTML5 `<video>` players using database `video_url` values. JavaScript saves playback position in browser localStorage by movie/episode ID and attempts to resume it. This is browser-local progress, not an inspected cross-device progress service.

The repository also contains older or parallel player/trailer code, including `js/video-player.js`, `js/trailer-feed.js`, `includes/TrailerManager.php`, `pages/` starter pages and JW Player documentation. Those artifacts do not establish that the main pages currently use those players. The inspected main detail pages use native video elements.

There is no demonstrated transcoding pipeline, adaptive bitrate packaging, DRM or independently verified permanent media-hosting service in this investigation. Catalogue metadata and media delivery are separate concerns in the code.

## Accounts and social features

The website contains signup/login, logout, email verification and resend verification, forgotten-password/reset flows, optional Google OAuth, session checks and remember-me cookies. PHPMailer source is bundled for mail delivery. Some account handlers use CSRF helpers and password hashing/verification functions; these mechanisms have not been audited end to end.

Multiple account implementations coexist: full PHP pages, `ajax_*` handlers and `api/auth-handler.php`. Their presence creates possible behavioral differences that require later tests rather than an assumption of consistency.

Social data includes Shot likes, comments, favourites and viewed-history records. There is also separate movie-comment/trailer infrastructure, which is not consistently aligned with the supplied database schema and bootstrap.

## WhatsApp delivery flow

The intended delivery path is:

1. A visitor chooses a movie or episode.
2. `whatsapp-watch.php` asks `includes/whatsapp_token.php` to create a content request token. The page explicitly requests a ten-minute lifetime, although the helper's default is different.
3. Token creation checks for recently online bots and attempts to assign an available bot using database health information and `fn_get_available_bot()`.
4. A WhatsApp link prepares a `!get` command containing the request token.
5. The Node bot receives the command, queues work and asks the website API for token/content information.
6. The forwarder tries a cached message, then a cache-group path, then a local-file upload. Shared in-progress operations can collect additional waiting recipients.
7. After reported success, the handler attempts to mark the token used and records a forwarding log.

The bot uses Baileys, not the WhatsApp Web.js implementation described by parts of its README. Dependencies also include mysql2, node-schedule, pino and qrcode-terminal. The current main request handler uses API-client functions; direct-database helpers remain in the repository.

Supporting modules implement a priority upload queue, local file management, cache warming, automatic refresh, health heartbeats, error reporting and operational statistics. Website administration includes bot tracking. Movie/episode part tables indicate support for multipart content. Distribution-group helpers and UI also exist.

Some retained comments and status messages describe a fourth/night-queue tier. The inspected `forwardVideo()` currently attempts local upload immediately after cache failures. Documentation should not present night-only delivery as established current behavior.

Delivery reliability, token atomicity, rate limiting, concurrency, multi-bot behavior and WhatsApp access have not been runtime verified. In particular, token-generation rate-limit enforcement is commented out in the inspected helper. Existing session directories are sensitive legacy material, not permission to connect.

## Facebook extraction service

`fb-video-api/` is a separate Node/Express service with health and extraction routes. Legacy extraction logic first looks for video URLs in fetched HTML, with Puppeteer/Chromium fallback logic. Browser feed code requests fresh URLs and caches results.

The current extraction route returns a configured local fixture in local mode and otherwise returns a recovery-disabled response before the legacy extraction block. Therefore, this checkout cannot be described as a currently verified live Facebook extractor. README performance figures were not tested.

## Administration and commercial features

Admin pages cover movies, series, episodes, collections, Shots, trailers, site settings, analytics and bot monitoring. They include content forms and artwork uploads. Separate admin identities are represented in the schema.

Subscription code targets IdeaMart and stores subscriber/expiry information. Both the inspected subscription endpoint and payment notification endpoint exit with HTTP 403 before their legacy logic. Advertising helpers are currently no-ops with ads disabled. These are legacy commercial integration artifacts, not verified active billing or advertising features. Subscription fields do not establish effective access control over all media routes.

The site includes clean Apache rewrite routes, sitemap generation, canonical URLs, Open Graph/Twitter metadata and structured data. Some structured ratings are literal defaults in source, so they should not be represented as independently established ratings. CSS and navigation code show responsive desktop/mobile intent; no rendered UI review was performed.

## Data model

Only schema structure was inspected; SQL data rows were not printed or imported.

| Domain | Representative entities |
| --- | --- |
| Catalogue | movies, series, episodes, collections |
| Identity | users, admins, password_resets, login_attempts |
| Short-video interactions | shots, shot_likes, shot_comments, user_favorites, user_views |
| Delivery | whatsapp_tokens, whatsapp_message_ids, movie_parts, episode_parts |
| Operations | bot_health_status, whatsapp_forward_logs, media_refresh_log, distribution_groups |

Primary relationships include series to episodes, collection to movies, user to Shot interactions, and content to delivery message/part mappings. Some content links use a content-type/content-ID pair rather than a single database foreign key. Multiple SQL generations exist and should not be imported indiscriminately.

## Current engineering state and concrete inconsistencies

- The workspace root is not a Git repository. The bot has a nested repository with pre-existing modifications and an untracked environment module. These were preserved. Website change history is not established here.
- Recovery records were absent at the start. Source already contains recovery-related guards and quarantine comments. This establishes their presence, not the completion of any previous recovery phase.
- `api/trailers.php` and `api/comments.php` bootstrap `admin/config.php` but reference `$pdo`; that bootstrap defines `$conn`. The inspected include chains do not establish a PDO connection for those paths.
- Trailer/comment code refers to tables such as `trailers` and `comments` that are absent from the inspected website SQL table declarations; that SQL instead includes `movie_comments` and Shot tables. Other migrations may represent different intended schema versions.
- Token creation inserts `assigned_bot_id` and optionally `part_number`; these columns are absent from the supplied website SQL's `whatsapp_tokens` declaration and its inspected ALTER statements. A later bot migration contains related evolution. A canonical schema remains to be established.
- Movie/episode download buttons link to `download.php`, which currently exits with HTTP 403 before rendering. The separate WhatsApp watch page also has a live-mode gate.
- The bootstrap configures a very long session lifetime. Admin movie deletion uses a GET parameter, and the inspected artwork upload path does not show comprehensive file validation. These are security-review findings from source, not an exploit demonstration or complete security audit.
- The READMEs describe only parts of the application and, for the bot, an older library architecture. Source and documents need reconciliation.

## Verification and limits

Executed: workspace and nested-repository status checks, filtered source/schema inspection, dependency-manifest inspection and `node --check` on 31 selected standalone JavaScript files. All 31 passed syntax validation. Minified files, historical bot variants, the scheduler sample and `refresh-method-new.js` were excluded. Syntax validation executes no application code and proves no feature behavior.

PHP, gitleaks and trufflehog were not found on PATH. PHP linting/runtime tests and a dedicated secret scan were therefore not performed. No website, bot or extractor was started; no database was imported or contacted; no live infrastructure or external integration was accessed. No end-to-end, browser, performance, vulnerability or media-rights verification was performed. Sensitive environment/session/log contents were not dumped.

## Reusable project description

Zinema is a PHP/MySQL entertainment discovery and media-delivery application with movie and TV catalogues, a vertical short-video feed, user interactions and an administration dashboard. A separate Node.js bot provides token-based WhatsApp delivery using cached-message forwarding and local-file fallback, supported by queues, refresh jobs and health reporting. The project also contains a video URL extraction service and legacy subscription integrations. The present checkout is a legacy recovery project whose schema consistency, security and end-to-end behavior still require isolated validation.

## Recommended next task

“Perform a read-only reconciliation of Zinema's existing recovery changes and SQL generations. Produce a canonical schema proposal, route/bootstrap map and prioritized defect list with source evidence. Do not import data, start live integrations or change application behavior.”
