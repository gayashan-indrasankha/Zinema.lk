# Zinema.lk baseline - Jira ZL-9

Audit date: 2026-10-23 (Asia/Colombo). Authoritative source: the existing extracted
`Zinema.lk/` workspace, including its current Git working tree and uncommitted
content. Checksums describe eligible working-tree bytes. Reference HEAD commit:
`7e8d325c4fc206c3a2664652de99e560bae44795`.

## 1. Executive Summary

Zinema is an existing PHP/MySQL-compatible entertainment discovery and delivery
project. Observable source implements movie/series/collection browsing, a Shots
feed, accounts and interactions, content administration, browser playback, and a
separate token-based WhatsApp delivery bot. A Node/Express extraction service
contains legacy Facebook media extraction logic behind recovery restrictions.
Conversational AI, recommendation intelligence and voice features are planned;
they are not established current functionality.

**ZL-9 status: COMPLETE within the clarified current-repository audit scope.**
This is an integrity and provenance record, not a runtime, security, release or
production-readiness certification. The active development source is the extracted
workspace, not an archive or a checkout reconstructed from HEAD. An original ZIP
is historical only and is neither required for acceptance nor presumed to contain
the latest source. Original feature creation dates remain unverified.
Source-publication quality was previously recorded as PARTIAL and is not promoted
to complete by this audit.

The owner reports independently developing the project with substantial AI coding
assistance and previously running the local journey from Shots to WhatsApp
delivery. Those are owner-reported historical facts, not independently reproduced
behavior. The owner reports that the service was never publicly launched; source
repository publication is a separate event from operating a public service.

## 2. Audit Scope and Limitations

Authorized operations: bounded filesystem metadata inventory; read-only local Git
metadata; selected first-party source and dependency-manifest inspection;
structure-only inspection of six versioned SQL reference files; SHA-256 hashing
of eligible nonsensitive files; and three documentation outputs.

**Source authority for ZL-9 and all future Jira tasks:** continue from the existing
workspace and preserve its tracked edits, staged changes and eligible untracked
files. A clean HEAD reference does not supersede working-tree content. Establish
the current Git status and eligible-file checksums before changes; use existing
recovery records as supporting historical evidence, reconciled with present files.
Do not replace current files from a historical ZIP or HEAD, require an archive for
completion, reset Git, or discard uncommitted work to reproduce this baseline.

Only these outputs are authorized:

- [BASELINE.md](BASELINE.md)
- [CONTRIBUTIONS.md](CONTRIBUTIONS.md)
- [baseline-source-sha256.txt](baseline-source-sha256.txt)

All three were absent at the initial check. Existing recovery records are inputs
and remain untouched. The explicit ZL-9 restriction against editing them takes
precedence over the general workflow instruction to update recovery records.

No application code, configuration, dependency, migration, Git reference, index
or commit is intentionally changed. No packages are installed. No PHP server,
Node service, database, bot, scheduler, browser or network integration is started.
No external API, remote repository fetch, WhatsApp, payment or email service is
contacted. Local audit programs perform metadata, parsing and hashing only; they
do not import application modules. No runtime tests or new syntax/lint suite is
executed in ZL-9.

Environment files, actual credentials/keys, authentication stores, user database
dump, sensitive logs, private media and preserved private history are not opened
or inspected for contents. Their existence is established by safe metadata only.
Selected legacy SQL references are examined for DDL identifiers and relationships;
data-row values are not reproduced or used. The user database dump is excluded
entirely. Existing recovery claims are compared with source where inspection is
permitted, and otherwise labeled unverified historical concerns.

Evidence labels used throughout:

| Label | Meaning |
| --- | --- |
| Source-confirmed | Relevant implementation/guard is present; correctness is not proven. |
| Owner-reported | Stated by the owner, not independently reproduced. |
| Executed audit check | A metadata, hash or document validation was actually performed. |
| Historical record | Earlier local Git/documentation evidence; not re-executed here. |
| Runtime verified | Independently executed feature behavior; none assigned in this audit. |
| Planned | Future intent; no completion claim. |

## 3. Current Workspace Structure

The inspected workspace root is the current Git repository, shown as `.` below.
No modernized directory layout is assumed or introduced.

```text
.
|-- .git/                         root version-control metadata; no changes authorized
|-- .github/                      pull-request template
|-- .recovery-private/            ignored preserved evidence/tools; contents excluded
|-- AGENTS.md                     existing recovery workflow
|-- README.md / CONTRIBUTING.md   existing project and contributor guidance
|-- docs/
|   |-- recovery/                 existing status, decisions, description, publishing record
|   |-- BASELINE.md               this audit
|   |-- CONTRIBUTIONS.md          provenance and contribution boundaries
|   `-- baseline-source-sha256.txt
|-- scripts/check-source.cjs      existing syntax-only checking utility
|-- Zinema.lk Website/
|   |-- *.php                     public page and account entry points
|   |-- api/                      public/social endpoints and whatsapp/ bridge
|   |-- admin/                    console, content management, guarded maintenance
|   |-- includes/                 bootstraps, helpers, bundled PHPMailer/
|   |-- assets/, css/, js/        browser presentation and scripts
|   |-- pages/                    parallel starter presentation paths
|   |-- migrations/               historical schema reference files
|   |-- fb-video-api/             separately configured Express extractor
|   |-- Database.sql              excluded user-data dump; metadata only
|   `-- uploads/                  excluded upload/private-media area
`-- whatsapp-bot-deploy/
    |-- index.js                  guarded Baileys entry point
    |-- config/                   API, forwarding, queue, file and monitoring modules
    |-- database/                 historical bot schema/migration references
    |-- package.json / package-lock.json
    |-- node_modules/             excluded generated dependencies
    |-- whatsapp-session/         excluded authentication area
    |-- .wwebjs_auth/             excluded legacy authentication area
    `-- bot-media/                excluded local media
```

The metadata walk counted 279 files outside pruned private/generated/runtime
directories before creating the outputs; this is not a count of all files on
disk. The root contains 259 tracked files. The website has 101 first-party PHP
files, including 18 under administration and 38 API endpoint files under the
stated counting rule (PHP files under `api/`, excluding bridge configuration and
`api/includes/`). There are 34 tracked JavaScript/CJS files.

No active nested `.git` repository was found in inspected nonprivate project
paths. Preserved extractor Git metadata exists within the excluded private
evidence area; its contents/history are not inspected. No nested bot repository
is currently observed. Six ignored historical/temporary bot variants are present
by metadata; their contents and safety are not assumed. A bot export file, two
maintenance log files and generated dependency/media directories are also present
and excluded. No standalone archive was found in the inspected workspace or at
the two expected archive basenames in its parent; no broad personal-disk search
was performed.

## 4. Source Authority and Integrity

**Owner clarification:** the existing extracted workspace and its current working
tree are authoritative, including uncommitted changes. The original ZIP, if
retained, is historical evidence only. Archive basename/checksum: **NOT REQUIRED
AND NOT AN ACCEPTANCE GATE**. No archive is used as current source, searched again,
extracted or compared as a prerequisite for this clarification. Nothing is copied
into Git, relocated, protected with filesystem flags or archived by this task.

HEAD identifies committed history, not every file currently in the workspace.
The manifest is calculated from eligible files on disk, not from `git show HEAD`.
At the original source audit there were no tracked edits or nonignored untracked
files. At this documentation clarification there are still no tracked edits, and
the three ZL-9 outputs are untracked, uncommitted working-tree content. They are
authoritative audit artifacts even though deliberately excluded from their own
input manifest. They are not represented by the recorded HEAD tree hash.

The [SHA-256 manifest](baseline-source-sha256.txt) covers **176 existing tracked
files**. It hashes raw file bytes, not normalized text, Git patches or runtime
output. Paths are repository-relative and sorted deterministically. Existing
nonsensitive documentation, first-party PHP/JavaScript/CSS, package/lock JSON,
`.gitignore`, `.gitattributes` and website rewrite configuration are included.
The two new Markdown files and the manifest itself are deliberately excluded to
avoid circular hashes and to keep the baseline focused on pre-audit inputs.

| Tracked exclusion category | Files | Policy |
| --- | --- | --- |
| All environment files, including example templates | 4 | Never read or publish their contents in this audit. |
| Bundled PHPMailer/vendor material | 70 | Third-party code is outside the first-party source manifest. |
| SQL references | 6 | Conservatively exclude all SQL from public hashes, including migrations; the dump is separately ignored. |
| Binary logo/icon/media assets | 3 | No media or rights claim is implied by inclusion in Git. |
| Total excluded tracked files | 83 | 259 tracked inputs minus 176 eligible inputs. |

Ignored environment instances, authentication stores, logs, user dump, private
history, dependencies, uploads/media, exports and historical variants are outside
the tracked allowlist used for this captured input set. This policy does not make
uncommitted work non-authoritative. Future tasks must identify and safely classify
new eligible untracked files and modified working-tree bytes before deciding how
to extend integrity evidence; absence from HEAD is not a reason to discard or
replace them. Sensitive filenames would also be excluded; none was identified in
the eligible manifest paths. Exclusion counts above concern tracked inputs only;
contents of pruned private areas are not enumerated or counted.

The commit identifier and manifest provide content-addressed and tamper-evident
references. They do **not** physically freeze the workspace, create write-once
storage, establish original authorship or prove the snapshot equals pre-recovery
source. Git branch names are mutable. Archive-to-working-tree equivalence cannot
be claimed. Line-ending/encoding conversion on another checkout may change raw
hashes even when text appears equivalent; do not normalize this baseline.

Run this read-only verification from the repository root in PowerShell:

```powershell
$verifiedCount = 0
foreach ($entry in (Get-Content -LiteralPath 'docs/baseline-source-sha256.txt')) {
    if ($entry.StartsWith('#') -or [string]::IsNullOrWhiteSpace($entry)) { continue }
    if ($entry -notmatch '^([0-9a-f]{64})  (.+)$') { throw 'Malformed manifest entry' }
    $expectedHash = $Matches[1]
    $relativePath = $Matches[2]
    if ([IO.Path]::IsPathRooted($relativePath) -or $relativePath.Split('/') -contains '..') {
        throw 'Unexpected manifest path'
    }
    $actualHash = (Get-FileHash -LiteralPath $relativePath -Algorithm SHA256).Hash.ToLowerInvariant()
    if ($actualHash -ne $expectedHash) { throw "Hash mismatch: $relativePath" }
    $verifiedCount++
}
if ($verifiedCount -ne 176) { throw 'Unexpected baseline file count' }
"Verified $verifiedCount baseline files."
```

This verifies only the declared allowlist; it does not approve excluded assets for
publication. Keep this manifest alongside the pinned commit identity and compare
the manifest itself with the owner-retained audit artifact if tampering is suspected.

## 5. Version Control Status

| Observation at audit start | Result |
| --- | --- |
| Repository | Root repository at `.` |
| Branch | `main` |
| HEAD | `7e8d325c4fc206c3a2664652de99e560bae44795` |
| HEAD tree | `7e01e85cc90bbd838144d5b9bddd01122036eea9` |
| Reachable commits | 32 |
| Added / modified / deleted / renamed tracked paths | 0 / 0 / 0 / 0 |
| Untracked, nonignored files | 0 |
| Cached `origin/main` | Same commit as local HEAD; not freshly fetched or externally verified in ZL-9. |
| Active nested repository in inspected paths | None found; excluded private preserved history is not audited. |

The table captures the initial audit state, before the three outputs were created.
The current clarification state is: zero tracked additions/modifications/deletions,
zero staged paths, and exactly three untracked ZL-9 documents. HEAD and its tree
remain unchanged. Thus the working tree contains current audit content beyond
HEAD, despite the unchanged committed application source. No Git initialization,
staging, commit, reset, stash, archive extraction or source replacement is needed.

All reachable commit dates observed in local Git metadata are 2026-10-23. The
history records an initial README, incremental source import/workflow commits and
merge/documentation commits. It provides current repository provenance; it does
not establish when the legacy features were originally written. Import subjects
such as `feat(auth): import ...` must not be relabeled as original implementation
dates. The prior integration record and current local commit graph agree that
the complete reviewed source reached main; no GitHub API was called in this audit.

Earlier notes about uncommitted bot changes and an active nested bot repository
are historical and do not describe the current clean root checkout. No commits,
tags, staging, stashes, branch resets or repository initialization are performed
in ZL-9. The expected final change set is exactly three untracked audit outputs.

## 6. Technology Inventory

| Area | Source-based evidence | Interpretation and limit |
| --- | --- | --- |
| Website | `*.php`, `admin/config.php`, HTML/CSS/vanilla browser JS | Predominantly procedural server-rendered PHP; no single application framework established. |
| Database | `new mysqli` in `admin/config.php`; `new PDO` with MySQL DSN in `includes/database.php`; SQL references | MySQL-compatible relational database intended; actual server/version not inspected. |
| Mail | `includes/mailer.php::sendEmail`; bundled PHPMailer composer manifest | PHPMailer wrapper; mode may disable mail. No delivery executed. |
| Bot | `whatsapp-bot-deploy/index.js:16-17`; `package.json` | CommonJS Node entry point uses `@whiskeysockets/baileys`, declared `^6.7.21`. |
| Bot support | Bot package manifest | mysql2, node-schedule, pino, qrcode-terminal and cli-progress declared; nodemon is development-only. Dotenv is declared but current entry point does not automatically load preserved environment files. |
| Extractor | `fb-video-api/index.js`; its `package.json` | Express, cors, got, puppeteer-core and Chromium package declared. Legacy network/browser extraction is guarded. |
| Browser playback | `movie-details.php::playMovie`; `series-details.php::playEpisode` | Native video insertion and browser-local playback position; not established cross-device synchronization. |
| Server routing | Website `.htaccess` | Apache rewrite/security rules intended; actual server configuration not executed/verified. |
| Test/tooling | `scripts/check-source.cjs`; `test-scheduler.js` | Syntax checker and a scheduling demonstration, not an established functional regression suite. |

The bot manifest declares Node `>=14.0.0`; the extractor declares Node `>=18`.
These are source declarations, not validated compatible/supported production
targets. Bot package-lock exists. The extractor has no observed package-lock.
There is no root package.json or root Composer application manifest; the website
package-lock and bundled library manifest do not establish a reproducible complete
application installation. Dependencies are not installed, resolved or audited.

## 7. Application Architecture

```text
Browser
  |-- PHP pages / browser scripts -- MySQLi bootstrap -- catalogue/account/social data
  |-- parallel trailer/comment APIs -- PDO expectations (bootstrap inconsistency)
  |-- video elements -- configured media URLs (media handling remains guarded)
  `-- content request page -- token helper -- WhatsApp link (live-only guard)
                                             |
                              guarded Baileys bot entry point
                                             |
                         API client -- PHP WhatsApp bridge -- database
                                             |
                     hot message -> cache/storage group -> local-file upload queue

Shots/admin browser logic -- extractor API -- local fixture or disabled response
                                           (legacy HTTP/Chromium logic not reached)
```

This is a static responsibility map, not a running deployment diagram. Website
pages combine SQL, request handling, markup and inline scripts. Most public paths
reuse `admin/config.php` for sessions and `$conn`. A separate PDO bootstrap exposes
`$pdo`. The bot's main path uses the PHP API bridge; the retained direct-database
adapter throws immediately. WhatsApp is a delivery adapter in this baseline, not
an independently verified permanent media CDN.

## 8. Main Application Components

| Component | Entry points / symbols | Source-confirmed responsibility |
| --- | --- | --- |
| Shots | `index.php`; `toggleLike`, `openComments`, `toggleFavorite`, `recordView` | History-aware/random feed queries, content links and social actions. |
| Movies/search | `movies.php`, `movie-details.php`, `find_movies.php`; `api/movies.php`, `api/live_search.php`, `api/search_content.php` | Catalogue metadata, filters/search, detail retrieval and view changes. |
| Series | `tv-series.php`, `series-details.php::playEpisode`; `api/series.php` | Series listing, episodes, season selection and player update. |
| Collections | `collections.php`, `collection-details.php`; `api/collections.php` | Grouped movie browsing using collection linkage. |
| Accounts | `login.php`, `signup.php`, `ajax_*`, `verify-email.php`, `reset-password.php`, `google-callback.php`, `api/auth-handler.php` | Parallel account flows; password hashing/verifying, email verification, reset and optional OAuth code. |
| Social/profile | `profile.php`; `api/like_shot.php`, `add_comment.php`, `get_comments.php`, `favorite_shot.php`, `record_view.php` | Saved content and Shot interactions. |
| Administration | `admin/index.php`, content/settings pages, `analytics.php`, `whatsapp-bot-tracking.php` | Console authentication, content forms, reporting and bot monitoring. |
| Parallel presentation | `pages/`, `includes/TrailerManager.php`, `api/trailers.php`, `api/comments.php`, player scripts | Retained starter/trailer/comment paths; integration consistency is not established. |
| Delivery | `includes/whatsapp_token.php::createWhatsAppToken`; `whatsapp-watch.php`; bridge API | Token generation, content lookup, bot assignment, validation and usage recording. |
| Bot workers | `index.js::handleTokenRequest`, `MultiTierForwarder`, `UploadQueue`, `FileManager`, `CacheWarmer`, `AutoRefresher`, `HealthMonitor` | Request handling, forwarding/upload fallbacks, prioritization, file access and operational reporting. |
| Extractor | `fb-video-api/index.js` routes; `environment.js::readConfig` | Intended URL extraction; current fixture/disabled route returns before legacy extraction. |

Website paths in this table are relative to `Zinema.lk Website/`; bot symbols
refer to `whatsapp-bot-deploy/`. These are implementations found in source, not
assertions that every route currently works or is secure.

## 9. User Journey Mapping

### Discovery and playback

1. `index.php` queries Shots and interaction/history data; authenticated and
   anonymous selection paths differ. A Shot can link to catalogue content.
2. `movie-details.php` retrieves a movie; `series-details.php` retrieves episodes
   and updates a selected episode. Browser functions insert native video and
   persist progress locally.
3. Movie/episode download actions reference `download.php` (movie detail line 529;
   series detail line 1007). That target returns HTTP 403 before legacy logic.
   The current inspected download path therefore does not prove a traversable
   end-to-end delivery journey.

### Intended WhatsApp delivery

1. `whatsapp-watch.php:3-4` first requires production/live WhatsApp mode. It loads
   catalogue content and calls `createWhatsAppToken(..., 10)` at line 78.
2. The helper checks recent bot heartbeats, resolves part/content mappings,
   selects an available bot and inserts a token. Its parameter default is 180
   minutes despite a nearby ten-minute comment; the inspected page overrides it.
3. `getWhatsAppLink` prepares the request command; no real link/token is included
   here. The bot's `MessageQueue` serializes tasks and its handler asks the API
   client for validation/content data.
4. `forwardVideo` attempts a hot message, then a cache/storage-group path, then
   a local-file upload through `UploadQueue`; it also coalesces active operations.
5. On reported success, the handler calls `markTokenAsUsed` and forwarding logs.
   Validation/claim/completion concerns are recorded below, not repaired.

The owner reports that Shots -> movie details -> WhatsApp content request -> bot
delivery worked previously in a local environment. The current guards and defects
must not be erased to make the present source agree with that historical report.

## 10. Feature Verification Matrix

Categories can overlap: an implementation may be source-confirmed and guarded.
**C = currently runtime verified has no qualifying features in this audit.**

| Feature | Classification | Evidence | Runtime status |
| --- | --- | --- | --- |
| Movie/series/collection catalogue | A: source-confirmed | Catalogue pages, queries and APIs | Not verified |
| Shots feed and linked discovery | A | `index.php`, Shot/social endpoints | Not verified |
| Signup/login/verification/reset | A; D: parallel implementations | Account pages, `ajax_*`, `api/auth-handler.php` | Not verified |
| Optional Google OAuth | A; D: mode-gated | `google-callback.php`, environment adapter checks | Not verified |
| Likes/comments/favorites/views | A | Shot APIs and profile queries | Not verified |
| Content administration and reporting | A | `admin/` handlers and dashboards | Not verified |
| Browser movie/episode playback and local resume | A | `playMovie`, `playEpisode`, localStorage | Not verified |
| Download route | A; D: disabled | `download.php:3-4` | Not executed; guard inspected |
| WhatsApp token generation/validation | A; D: schema/validation concerns | Token helper and bridge | Not verified |
| Cached/cache-group/local-file delivery | A; D: live startup guard | `MultiTierForwarder`, queue, guarded entry point | Not verified |
| Monitoring/refresh/cache warming | A; D: live-dependent | Worker modules and bridge endpoints | Not verified |
| Core local Shots-to-delivery journey in the past | B: owner-reported previously working | Owner statement supplied for ZL-9 | Not independently reproduced |
| Live media extraction | A; D: quarantined | Extract route early return; environment mode restriction | Not executed |
| Extractor local fixture | A; D: configurable stub | Extract route returns configured fixture | Not executed; no simulation claim |
| Subscription/payment and maintenance | A; D: disabled | Early HTTP 403 in handlers/jobs | Not executed |
| Advertising | D: disabled/no-op | `includes/ads.php`, `ADS_ENABLED` false | Not executed |
| Conversational/mood/catalogue-grounded AI | E: planned | Owner's future feature brief | Not implemented/verified here |
| Voice/animated companion/AI-to-Shots | E | Owner's future feature brief | Not implemented/verified here |
| Controlled delivery simulation and accessibility/reliability improvements | E | Future scope; existing extractor fixture is not a full delivery simulation | Not completed here |

## 11. Database and Integration Overview

### Bootstraps and schema generations

- `admin/config.php:20` constructs MySQLi `$conn`; public/account/admin paths use
  it for SQL and session bootstrap.
- `includes/database.php:5` constructs PDO `$pdo`; `getMovie`, `getMovieTrailers`
  and `TrailerManager` expect PDO behavior.
- `api/trailers.php:2,18` and `api/comments.php:2,19` load the MySQLi bootstrap but
  use `$pdo`; their inspected includes do not initialize it.
- `Database.sql` exists by metadata but is treated as a user-data dump and not
  read. Its full table/column contents and compatibility are NOT VERIFIED.
- Website migrations define `movie_parts`/`episode_parts`; the former adds
  `whatsapp_tokens.part_number`. The Shot migration changes a Shot field.
- Bot `database/schema.sql` describes older `video_tokens`, `forward_logs` and
  `media_refresh_log`. This differs from the current bridge's table vocabulary.
- `migration_ultimate_system.sql` alters `movie_message_ids` and adds bot
  assignment/operations structures; the `FIXED` variant instead extends
  movie/episode part tables and declares another token shape.

The `FIXED` variant's `whatsapp_tokens` definition has a `status` enum and lacks
`message_id`, `is_used` and `is_active`. The current token INSERT and validation
SELECT require those three fields. This is a present, source-confirmed structural
conflict between approved source references; it does not require inspecting the
excluded user dump. Which schema was actually installed is unknown. No canonical
migration sequence, schema version or safely provisioned fixture is established.

### Relationships

| Relationship | Evidence type |
| --- | --- |
| Series -> episodes through `series_id` | Source JOIN/query in `series-details.php`; installed FK not verified. |
| Collection -> movies through `collection_id` | Collection detail query; installed FK not verified. |
| User -> Shot likes/comments/favorites/history | Feed/API/profile queries; database rows not inspected. |
| Movie/episode -> parts -> message mappings | Website migration DDL and bridge joins. |
| Token -> polymorphic content and assigned bot | Token helper columns and validation query; not a universal content FK. |
| Legacy forward log -> legacy token | Explicit FOREIGN KEY in bot `database/schema.sql`. |
| Bot health/statistics/cache/alerts/cleanup/backup | DDL in bot migration variants; actual operations not executed. |

External boundaries include WhatsApp/Baileys, mail, OAuth, configured media URLs,
legacy payment integration and extraction. Environment modules require explicit
process configuration and gate modes. Bot `config/database.js:1` throws before
creating a direct connection pool. PHP maintenance entry points are quarantined.
No credentials, environment values, operational group IDs or private URLs are
included in this document.

## 12. Security-Sensitive Assets (Sanitized)

| Asset category | Observed safe metadata | Handling and limit |
| --- | --- | --- |
| Environment configuration | Six ignored bot environment instances and four tracked example templates exist | Contents not opened; all environment files excluded from hashes. |
| WhatsApp authentication | `whatsapp-session/` and `.wwebjs_auth/` exist under bot root | No directory contents/session records inspected; no account access. |
| User database | Website `Database.sql` exists | Size/existence only; no schema/data extraction from this file. |
| Logs/exports | Maintenance log files and bot export file exist | Metadata only; no private diagnostic content read. |
| Private media/uploads | Bot media and website upload areas exist | Pruned; media rights and contents not assessed. |
| Preserved evidence/history | Ignored private evidence area and preserved extractor metadata exist | Contents excluded; no copying or restoration. |
| Potential keys/certificates elsewhere | No comprehensive private-area search performed | Absence cannot be claimed. |

The existing ignore/rewrite policy is source evidence, not proof that every
sensitive artifact is excluded from every historic commit or future export.
Current configured Apache denial rules are not runtime tested. Previously
reported secret scans do not authorize reading sensitive stores or establish a
complete present-day secret/vulnerability audit. ZL-10 must define the export
boundary before any additional publication or demonstration data preparation.

## 13. Technical Debt and Risk Register

Severity is potential impact if the relevant path is enabled or exported;
confidence describes the observation, not an exploit probability. No CRITICAL
exploit is independently confirmed within this restricted audit. All runtime
cells mean **NOT VERIFIED; no exploit or integration execution performed**.

| ID / severity | Issue and subsystem | Safe source evidence | Concern / confidence | Runtime | Recommended next task |
| --- | --- | --- | --- | --- | --- |
| R01 HIGH | Sensitive local assets vs export boundary | Environment/session/dump/log/private-area metadata in section 12 | Authentication or personal material could enter an unsafe export; exposure is not established. High confidence in presence, unknown contents/exposure. | Not verified | ZL-10: classify and define an explicit safe export without publishing private material. |
| R02 HIGH | Mixed database bootstrap contracts | `Zinema.lk Website/api/trailers.php:2,18`; `api/comments.php:2,19`; `admin/config.php:20`; `includes/database.php:5` | `$pdo` consumed after a MySQLi-only bootstrap; likely route failure. High source confidence. | Not verified | After ZL-10, propose one bootstrap contract and isolated route tests; do not repair in ZL-9. |
| R03 HIGH | Incompatible token/schema generations | `whatsapp-bot-deploy/database/migration_ultimate_system_FIXED.sql:43`; website `includes/whatsapp_token.php:166,172`; `api/whatsapp/validate-token.php:37-41` | Status-only token DDL disagrees with required message/boolean columns; installed schema unknown. High. | Not verified | Produce canonical schema/compatibility proposal from sanitized structure in a later authorized task. |
| R04 HIGH | Validation result bypass in guarded delivery | Bot `index.js::handleTokenRequest:929-945`; `config/api-client.js::getTokenData`; website `api/whatsapp/validate-token.php:73-98` | Handler accepts response data and explicitly rejects already-used only; inactive/expired responses also contain data. Source control-flow concern, not a live exploit claim. High. | Guarded; not verified | Specify reject/allow response contract and local invalid-token regressions before enabling delivery. |
| R05 HIGH | Non-atomic token consumption | Bot `index.js:945-954`; website `api/whatsapp/mark-used.php:32`; token helper rate-limit block | Usage is marked after reported delivery; mark failure/false return does not prevent prior send. Cross-request/bot concurrency is unproven. High ordering confidence. | Guarded; not verified | Design claim/deliver/acknowledge states and idempotent isolated tests in later scope. |
| R06 HIGH | Token issuance rate limit disabled | Website `includes/whatsapp_token.php:38-53` | RateLimiter call is commented out; including its module does not enforce it. High. | Guarded path; not verified | Specify and test local issuance limits before a controlled delivery demonstration. |
| R07 HIGH | Completion may precede actual recipient delivery | Bot `config/multi-tier-forwarder.js:478-490`; `index.js:947-954` | Cache-warmer completion returns success without a send to the requesting chat in that branch, potentially leading to token-use marking. High source observation; medium impact inference. | Guarded; not verified | Trace recipient acknowledgement and test overlapping warm/user uploads locally after authorization. |
| R08 HIGH | Long-lived session/remember behavior | Website `admin/config.php:6-9,30-47` | Century-scale session settings and remember-token lookup require expiry/revocation review. Cookie/server behavior not measured. High. | Not verified | Define session lifetime, rotation and revocation requirements before security remediation. |
| R09 HIGH | State change through GET / weak upload boundary | Website `admin/movies.php:10-27,49-60` | Authenticated GET deletes content; inspected upload block uses original basename and move without visible type/size validation. Apache upload execution denial is a separate, untested layer. High. | Not verified | Review authorization/CSRF and upload policy, then add isolated regressions in later scope. |
| R10 MEDIUM | Current journey interrupted by guards | Website `download.php:3-4`, `whatsapp-watch.php:3-4`, `api/subscribe.php:3-4`, `api/payment-notify.php:3-4`; bot `index.js:1-3` | Current source is not proof of the historical local journey; enabling guards is not a documentation fix. High. | Not executed | Define safe fixture/delivery-adapter contracts; retain every guard until separate authorization. |
| R11 MEDIUM | Quarantined extractor still retains unsafe legacy URL logic | Extractor `index.js:239-254`; `environment.js:5-15` | Early returns currently block legacy extraction; substring domain checking would not establish safe outbound targets if revived. High guard confidence; medium security inference. | Quarantined; not verified | Threat-model URL/redirect/address validation and choose a local substitute before any future reactivation. |
| R12 MEDIUM | Parallel account/UI/player paths and tight coupling | Website `login.php`, `ajax_login.php`, `api/auth-handler.php`, `pages/`, `includes/TrailerManager.php`, large inline page scripts | Multiple contracts and duplicated rendering/queries increase drift. No module is declared unused or deletable. High presence confidence. | Not verified | Produce a route/include dependency map and behavior inventory before any restructuring. |
| R13 MEDIUM | Schema mutation inside settings helper | Website `includes/settings.php::ensureSettingsTable:99-137` | Helper contains CREATE TABLE/default INSERT logic, coupling configuration lookup and schema management. Caller execution not tested. High source confidence. | Not verified | Trace callers and propose controlled migrations under later authorization. |
| R14 MEDIUM | Functional test/reproducibility gap | `scripts/check-source.cjs`; bot `test-scheduler.js:9-32`; both Node manifests; missing extractor lock/root application manifests | Syntax utility is not a feature suite; scheduling sample logs a success message without assertions. Full safe environment is not established. High. | No tests executed | Define isolated fixtures, assertions and a reproducible developer setup after export scoping. |
| R15 MEDIUM | Historical/temporary modules and contradictory docs | Bot root legacy-variant metadata; bot README:1-5; `index.js:10-13`; forwarder `forwardVideo:229-253` | README retains obsolete library instructions; four-tier/night-only prose differs from immediate local upload logic. Original purpose/references of excluded variants are not proven. High. | Not verified | Inventory references and reconcile documentation; preserve files until purpose/provenance is established. |
| R16 MEDIUM | Sensitive data can be logged by source | Bot `index.js:924,942,954`; `config/api-client.js:82`; bridge logging/operational modules | Source logs token/request/recipient-related fields; actual log contents were not read. High source confidence. | Not executed | ZL-10: define redaction and safe diagnostic/export policy without exposing current logs. |
| R17 MEDIUM | Original creation/provenance dates missing | Local Git import subjects dated 2026-10-23; original feature implementation dates unverified | Import chronology cannot prove legacy implementation dates or competition eligibility. High. | Not applicable | Maintain CONTRIBUTIONS evidence and request owner-retained historical artifacts if needed; no archive is required for current-workspace task acceptance. |
| R18 LOW | Configured error-page targets absent | Website `.htaccess:88-89`; metadata checks for referenced HTML files | ErrorDocument points at missing files; server fallback behavior unknown. High. | Not verified | Record intended error behavior and test in an isolated server during a later phase. |
| R19 LOW | Token-expiry naming/comment drift | Website `includes/whatsapp_token.php:34-38`; `whatsapp-watch.php:78` | Comment says ten minutes, helper default is 180, inspected caller passes ten. Different callers may disagree. High. | Not verified | Enumerate callers and decide a documented expiry contract before changes. |

Earlier claims that particular dump tables/columns are missing are **historical
concerns requiring verification**, because the user dump is not read. Earlier
1,798 whitespace findings are also a **historical documented result**, not a newly
executed ZL-9 whitespace scan. They are not silently presented as fresh failures
or fresh passes. No files are labeled safe to delete based on appearances.

## 14. Recovery State

Current source contains recovery gates, explicit environment readers, local-mode
constraints, disabled commercial/download/maintenance entry points, a disabled
direct bot database adapter, and a fixture/disabled extractor route. These are
source-confirmed present conditions. Their comments do not establish historical
phase completion, authorship or test execution.

The existing [status](recovery/STATUS.md), [decisions](recovery/DECISIONS.md) and
[project description](recovery/PROJECT_DESCRIPTION.md) remain unchanged. Their
integration narrative agrees with the current root Git history; their older
non-repository/nested-bot/uncommitted-bot statements are explicitly historical and
not current facts. Their prior syntax/secret-scan claims are not re-executed by
this documentation task. Known recovery defects and runtime uncertainty persist.

## 15. Proposed Future Work Boundaries

See [contribution boundaries](CONTRIBUTIONS.md) for existing vs owner-reported vs
competition-period vs planned work. Future ideas include conversational/mood-based
catalogue recommendations, optional voice and animated UI, AI-to-Shots links,
controlled delivery simulation, and reliability/accessibility improvements.
No such feature is implemented or credited as completed by ZL-9.

Future work must receive its own authorization. Do not restructure, reformat,
remove legacy modules, reconcile/import a database, enable integrations, rotate
credentials, publish an export, commit these outputs or start ZL-10 automatically.
Any demonstration must use legally usable media and fictional/sample accounts;
existing private media and historical operational accounts are not fixtures.

## 16. Verification Performed

| Check | Result / actual scope |
| --- | --- |
| Read request, root Git state and existing recovery records | Performed; clean main at pinned commit; request narrowed writes to three outputs. |
| Workspace and archive metadata inventory | Performed with private/generated areas pruned; archive clarified as current repository by owner. |
| Git branch/HEAD/tree/history/status counts | Performed locally; no remote fetch/API; initial tracked changes and nonignored untracked counts all zero. |
| Selected source and dependency/DDL inspection | Performed; findings traceable to paths/symbols above; no dump or sensitive-store contents read. |
| Generate deterministic eligible-file SHA-256 manifest | Performed for 176 files; 83 tracked exclusions reconciled to 259 inputs. |
| Recompute manifest hashes and verify policy/unique relative paths | PASS: all 176 hashes matched; no malformed, duplicate, unsorted or excluded entry. |
| Markdown structure, local links and generated-document privacy checks | PASS within scope: 19/6 sections, 14 tables, ten links, heuristics and generated-output Gitleaks scan. |
| Verify protected source/recovery inputs unchanged | PASS: all 176 initial eligible hashes unchanged, HEAD/tree/index unchanged and no tracked modifications. |
| New application syntax/runtime/regression tests | NOT PERFORMED in ZL-9; prohibited runtime execution and unnecessary application execution avoided. |
| External service, dependency vulnerability or complete secret audit | NOT VERIFIED; no external calls/installations and no inspection of excluded secret material. |

Post-write audit results (executed):

- Recomputed all 176 manifest hashes, exceeding the requested representative
  sample: 176 matched; zero malformed, duplicate, unsorted, outside-root or
  policy-excluded entries. Every eligible hash/byte length matched the independently
  captured initial inventory, including existing recovery documents.
- Verified all three output files exist. Checked the 19 numbered baseline sections,
  six contribution sections, balanced code fences, 14 Markdown tables and ten
  relative document links: no structural/link findings.
- Checked generated documents/manifest paths for personal absolute paths, email
  addresses, operational contacts/session identifiers, recognizable credential
  tokens, private-key material and credential-bearing URLs: zero findings. These
  are bounded heuristic checks, not proof of absence of all sensitive information.
- Executed the already available Gitleaks scanner against only the three generated
  outputs through standard input, with full redaction and no report-file writes:
  exit code 0, no findings. No source/private directories were supplied to this
  scan; no packages or updates were installed.
- Confirmed unchanged HEAD, branch, Git tree and empty staging area. Git status
  reports exactly the three authorized untracked outputs and no tracked changes.
- Compared metadata sizes for the 14 explicitly inventoried sensitive files and
  six excluded bot variants: unchanged. Their contents were deliberately not
  rehashed, inspected or cryptographically verified.
- No application/source/configuration/migration/dependency edit, runtime test,
  service startup, database operation, external call, Git staging or commit was
  performed. Source remains unchanged within the executed hash/status checks;
  excluded sensitive areas are outside byte-level verification.

The current manifest's SHA-256 is
`aec9cc4cb1183c79ac2ab9d11ccaedac04db710ca204ccb26e3d11b5be58bb54`.
This pins the delivered manifest independently of its own entries. The three audit
outputs are excluded from the input manifest; revising documentation does not
change its pre-audit source hashes. The final post-edit document scan and the
PowerShell verification recipe are rechecked before handoff.

Clarification follow-up: the existing audit was continued without a new source
investigation. Only these three documents were revised to make current-workspace
authority, historical ZIP status and HEAD/working-tree separation explicit. All
176 path/checksum entries are retained; only manifest comment headers change.
The manifest-file digest therefore changes while the recorded source checksums
remain the same. The existing recovery records are not edited.

## 17. Unverified Claims and Open Questions

- Original feature implementation dates/authorship beyond the owner's statement;
  an October import is not independent proof of October feature development.
- The exact earlier locally working revision, environment and database schema.
- All current feature behavior, performance, concurrency and delivery reliability.
- Installed database structure/data and compatibility with all schema generations.
- Whether every parallel page/helper is still reachable or used; no deletion
  recommendation is made without a complete dependency map.
- Contents/validity/exposure of environment instances, sessions, logs, user dump,
  preserved history and media; these were deliberately not inspected.
- Specific AI products, model versions, prompts and per-file authorship splits.
- Actual hosting/server configuration and third-party access; source publication
  is not proof of a public service launch.
- Organizer eligibility/rules and competition dates independently checked online;
  the October 16, 2026 start is supplied owner context only.

## 18. Recommended Next Jira Task

**Jira ZL-10 - Sanitize sensitive files and define safe public export.**

Proposed scope for separate owner authorization: inventory/classify sensitive
assets by metadata; define explicit include/exclude and sanitized fixture/media
policies; review safe-export candidates with redacted evidence; and document what
may be published. Preserve originals and current guards. Do not infer permission
for credential rotation, destructive cleanup, production access, commits or an
export upload. Schema/route reconciliation and isolated regression work follow
only under their own approved scopes. ZL-10 is not started here.

## 19. Baseline Acceptance Checklist

- [x] Extracted workspace and uncommitted content are authoritative; historical ZIP is not required for acceptance.
- [x] Reference HEAD/tree are distinguished from current uncommitted audit documents and eligible working-tree bytes.
- [x] Root Git commit/tree, branch and initial clean state recorded without changes.
- [x] Components, manifests, schema generations and excluded sensitive categories identified.
- [x] Source evidence separated from owner history, runtime verification and planned features.
- [x] Risk register includes subsystem, relative evidence, concern, severity, confidence and next tasks.
- [x] Provenance/AI assistance and competition-period uncertainty documented separately.
- [x] SHA-256 manifest generated with explicit eligible/excluded categories and relative paths.
- [x] Post-write hash, Markdown/link, privacy and preservation checks passed within the declared scope.
- [x] Existing recovery records, source, guards, migrations, dependencies and Git history left unchanged.
- [x] No service/network/integration/database execution or package installation performed.
- [x] No ZL-10 implementation or new AI feature work performed.

Acceptance is scoped to this current-source documentation audit. It is not
acceptance of original-archive provenance, operational correctness, a safe public
export, historical feature credit or production readiness.
