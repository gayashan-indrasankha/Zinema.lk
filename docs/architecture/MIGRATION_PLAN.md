# Reversible migration plan — Jira ZL-11

Date: 2026-10-23 (Asia/Colombo). **PLANNED only. No move, scaffold, code change,
database operation, commit or ZL-12 work is performed.**
Use the current extracted workspace, existing uncommitted documentation and
[current architecture](CURRENT_ARCHITECTURE.md). Do not restore a historical ZIP,
reset Git or discard owner edits to manufacture a clean start.

## Preconditions and decision records

Initial HEAD: `a2e5aebf55320e891f5580e7b8825bb32e72cdd8`.
Initial index: empty; existing BASELINE/CONTRIBUTIONS modifications preserved.
The original 176-input baseline is retained. Its three known workflow deltas are
documented, not hidden by rewriting its hashes. ZL-11 protects current eligible
text with 188 pre-edit hashes; contributions are append-only.

The 51-group [inventory](MODULE_INVENTORY.md) is a static responsibility map,
not a deployment manifest. Missing runtime evidence is a move gate, not proof
that a component is dead. Recovery guards, private state and the four export
holds from ZL-10 remain. Current export approval does not extend to new paths
automatically; no real export is authorized.

Principles: one boundary per change, explicit compatibility, measurable before/
after results, no dependency upgrade/feature/security rewrite bundled with a move,
and an immediately available retained implementation. PHP functions may remain
procedural; do not prescribe PSR-4 or convert all controllers to classes.

## Regression availability and test profiles

Availability describes the present workspace. A future test requirement is not a
passed test. All service/DB/browser tests below are unexecuted in ZL-11.

| Profile | Current state | Before a future move | After the move |
| --- | --- | --- | --- |
| P0 source/metadata | AVAILABLE NOW; executed direct parsers, hashes, include/path checks | Capture working-tree/index/HEAD, eligible hashes, exact include/asset references; parse changed PHP/JS | Same parsers plus intentional-delta review, no unexpected deletion/secret exposure |
| P1 leaf/procedural unit | MISSING dedicated app unit suite | Add fixture assertions for function signature/return/no side effects; no DB/network bootstrap | Same tests through old shim and new helper, exact behavior parity |
| P2 routes/Apache | BLOCKED until isolated HTTP/Apache setup is approved and reproducible | Capture direct PHP, slug, extensionless, admin/API, query/method/body/redirect/headers and denied paths | Exercise identical URLs/host/subdirectory behavior; no new public helper/state access |
| P3 auth/session | MISSING tests; BLOCKED on fictional DB/session/mail setup | Characterize forms/AJAX/JSON/admin roles, CSRF, verification/reset/cookies/logout | Preserve identity, expiry, cookie/header/body semantics; separate repairs from movement |
| P4 DB/query contract | BLOCKED: no verified canonical sanitized schema/database | Reconcile caller vocabulary and fixtures; snapshot catalogue/parts/interaction queries and required views/routines | Same data/constraints/results and connection types; no automatic migration on reads |
| P5 browser/assets/player | MISSING; BLOCKED on local legal media + HTTP fixtures | DOM/visual/accessibility/network paths, caches, native player/resume/episodes and root/slug URLs | Same DOM handlers/assets/storage keys and legal-media behavior across viewports |
| P6 bot core/bridge | MISSING fake socket/store/clock/HTTP contract suites | Test invalid/expired/inactive/used tokens, idempotent claim/ack, queue/coalescing/failure/restart | Same contracts through relocated modules; no live Baileys/auth import or real account |
| P7 extractor | REQUIRES FUTURE IMPLEMENTATION of isolated caller/service fixtures | Test disabled/local fixture, timeout/fallback and response shapes without Facebook/Chromium | Existing callers retain contract; live logic remains unreachable |
| P8 workers/observability | MISSING fake-scheduler/state/metrics tests | Capture cron/timezone/manual/stop/retry/redaction and state path semantics | No duplicate timers, changed schedule, lost state or raw identifiers |
| P9 packaging/policy | AVAILABLE NOW as existing tooling; historical 32 tests, NOT rerun in ZL-11 | Review exact allowlist/ignore/licenses/rights before approving new paths; no real export | Approved synthetic tests/manifest/path checks; source bytes preserved, private assets still excluded |
| P10 private runtime state | BLOCKED on separate owner-approved retention/recovery plan | Verify target and private backup/ownership without public copying; use fake state for tests | Keep same explicit roots or prove separately approved restore; never silently migrate live auth/media |

PHP/JS parser success alone cannot clear P2-P8. Existing export tests verify
packaging, not application auth/player/delivery. The scheduler diagnostic prints
success without assertions and does not satisfy P8.

Exact parser commands available now:

```powershell
# Parser-only forms; enumerate approved first-party source, excluding vendor/private paths.
php -n -l <approved-php-file>
node --check <approved-js-or-cjs-file>
```

The locally available PHP executable was used directly, with -n disabling
configuration loading. No project checking/test/start script was executed in
ZL-11. Automated route/DB/browser/fake-adapter commands are **NOT AVAILABLE YET**;
do not invent a phpunit/npm/e2e command for tests that do not exist.

## Staged sequence — candidate issue ordering

“Stage” is a migration stage, not Git staging performed by this task.
Existing issue ownership now follows the supplied Jira list below. Every inferred
ordering is PROPOSED DEPENDENCY; issue completion and Jira links are not verified.

| Stage | Planned action | Exit gate / hold | Suggested Jira |
| --- | --- | --- | --- |
| 0 | Protect current working tree and baseline; enumerate intended changes/private boundaries | P0; preserve owner edits and explicit records; no reset/ZIP restoration | ZL-12 preflight |
| 1 | Confirm route/include/consumer map and produce an isolated regression baseline | P2-P5 readiness; unresolved dynamic/deployment consumers recorded; no source move if affected behavior untestable | ZL-12 preflight / environment-and-tests prerequisite |
| 2 | Create only justified destination structure and compatibility plan | No document-root switch, code execution or fake completeness from empty folders | ZL-12 incremental preparation |
| 3 | Move/encapsulate one low-side-effect leaf, e.g. disabled ads helper, retaining old include shim | P0/P1 and impacted page contract; do not start with DB/session bootstrap | ZL-12 first reviewed structural slice |
| 4 | Prove URL/include/assets parity for that slice | P2/P5 and owner-reviewed before/after evidence; rollback rehearsed locally | ZL-12 validation |
| 5 | Repeat one group at a time, preserving wrappers and dependency order | Each mapping row below; stop on unexplained behavior drift | ZL-12 follow-up structural slices |
| 6 | Reconcile DB architecture/schema in a separate issue | Sanitized canonical contract, fixture migrations/backup/rollback; preserve raw SQL references | ZL-14; ZL-47/50 gates |
| 7 | Recover core journey with explicit fake adapters and security contracts | P3-P7, token validity/claim/ack, legal media and observable fictional delivery | ZL-17/19/29/30/45 |
| 8 | Implement optional narrow AI boundary only after grounded catalogue contracts | PLANNED AI tests/privacy/budget/fallback; feature work separate from movement | ZL-20 through ZL-23; ZL-25 |
| 9 | Review optional live media/delivery adapters separately | Existing guards remain throughout stages 0-8; external/account authorization required | ZL-18/28/29/30/51/54 |
| 10 | Decide archive candidates individually after replacement and consumer evidence | Owner approval, retained evidence, tested redirects/restoration, rights/privacy checks | ZL-12/31 consumer and preservation gates |

Stage 6 is a prerequisite earlier than its nominal sequence for any component
whose movement depends on untestable DB behavior. Do not push a failing database
boundary through stages 3-5 just to follow numbering. Isolating risky adapters
begins with preserving current guards at stage 0, not waiting until stage 9.

## Current-to-target migration map

Each row inherits the exact current-path membership from its inventory group.
Profile Pn specifies both required **before** and **after** tests above.
“Retained shim” means a future explicitly reviewed compatibility change, not a
file created now. Targets are candidates, not mandatory mass moves.

| Move / source groups and current paths | Proposed target | Reason / affected callers | Prerequisites and URL/path compatibility | Before/after tests | Rollback and risk | Jira order |
| --- | --- | --- | --- | --- | --- | --- |
| M01 W24: includes/ads.php | apps/web/src/Views/Ads.php, old include shim | Small disabled no-op interface used by catalogue/detail/download | Preserve function names/empty results; no advertising enablement | P0/P1 + impacted P2/P5 | Point retained old include to retained original helper; Low | Stage 3, ZL-12 first slice if gates pass |
| M02 W28: includes/functions.php | src/Support/ only if retained | URL/escaping helpers, consumers unknown | DEPENDENCY NOT FULLY VERIFIED; no archive or move until callers/host trust confirmed | P0/P1/P2 | Keep original helper/path; Medium, HOLD | Stage 5 after consumer review |
| M03 W03: csrf_helper.php/rate_limiter.php | src/Auth/ helpers | Public/AJAX forms and token issuance | Preserve session lifecycle/signatures; do not enable disabled rate limit as a move | P0/P1/P3/P4 | Retained helper/shim and prior behavior; Medium | Stage 5, protection-contract slice |
| M04 W02: environment.php/admin/config.php/database.php | src/Config/, Database/, Auth/ | Almost all PHP routes; $conn/$pdo and remember sessions | Separate pure config from side effects only after contracts; keep all old include paths until proven | P0/P2/P3/P4 | Repoint shims to retained original bootstrap; no DB down-migration; High, HOLD | Stage 5 only after schema/session prerequisite |
| M05 W13: _top_nav/_bottom_nav/_pagination | src/Views/ | Lists/details/feed/profile and tracker | Keep template variables/DOM/query/BASE_URL and relative-fetch policy | P0/P2/P5 | Restore retained partial includes and old asset map; Medium/High | Stage 5 after one leaf |
| M06 W26/W27: css/, js/, assets/css/ | public css/js/assets compatibility plus later source assets | All browser consumers including uncertain legacy scripts | Preserve URL bytes/root-slug/subdirectory resolution, storage keys, order; no bundling/minification or CSS merge | P0/P2/P5 | Keep old asset tree and revert reference map; Medium, unresolved JS HOLD | Stage 5, one verified asset group at a time |
| M07 W06/W07/W10: lists/details/catalogue APIs | public PHP/API wrappers -> domain queries/views | Nav/search/player/episode/collection consumers | P4 catalogue fixtures and response/partial/redirect contracts; never change slugs or JSON while moving | P0/P2/P4/P5 | Old wrappers delegate back to retained handlers; High | Stage 5 after DB/route gates |
| M08 W08/W09: index/profile/social APIs | public wrappers -> src/Shots/, Users/, Api/ | Feed/saves/history/auth overlays and media refresh | Legal local media, fictional users, cache/fallback/interaction contract; extractor optional only for prepared fixtures | P0/P2-P5/P7 | Retained handlers, cache/storage keys and asset references; High | Stage 5 after fixture baseline |
| M09 W04/W05: auth/AJAX/JSON/OAuth/mailer | public exact routes -> src/Auth/ and adapters | Repeated page overlays, verification/reset/session users | Preserve body formats, cookies/redirects/CSRF/role separation; fake SMTP/OAuth only | P0/P2/P3/P4/P5 | Restore retained routes/helper imports and configured fake transports; no live account rollback; High | Separate auth compatibility slice |
| M10 W14-W18: admin pages/assets/settings | public/admin/ wrappers -> src/Admin/, Config/ | CRUD/analytics/Shot ingest/bot monitor | Authorization/upload/GET-delete issues characterized separately; runtime settings DDL repair not bundled | P0/P2-P5/P7/P8 as affected | Old admin handlers/layout/settings adapter retained; High | After auth/schema fixtures |
| M11 W11/W12: comments/trailers/controllers/JS | public routes -> src/Comments/, Trailers/ | Widgets/managers and generated /like,/view,/share paths | Resolve PDO bootstrap and intended method/PATH_INFO contract before migration; do not fabricate routes | P0/P2-P5 | Old manager/route/client version retained together; High, HOLD | Repair prerequisite issue, then structural slice |
| M12 W19/W20: token helper/access/bridge | public exact access/whatsapp APIs -> ContentAccess/Api/Delivery | Bot API client, detail links and admin | Validity/status, ID/table vocabulary, atomic claim/ack and disabled UI/fake adapter must be specified/tested first | P0/P2/P4/P6 | Retain old guarded endpoints; no real token/account state reversal; High, HOLD | Contract/schema repair issue before recovered journey |
| M13 B04/B05: forwarder/queue | services/delivery/core/ | Bot token handler, warming/coalescing/upload handlers | Extract side-effect-free core only with fake socket/store/clock contracts; no live entry import | P0/P1/P6 | Old module imports/injected handlers retained; High | Independent bot-core slice after contracts |
| M14 B06/B07: file adapter and state references | adapters/local-media/, workers/ plus external configured state | Forwarder/warmer/media naming and restart | Code path and private state root are separate; warming-state currently follows __dirname; no real media/auth copying | P0/P6/P8/P10 | Preserve original configured roots and retained worker; no automated state deletion; High | After private-state plan, not simple folder rename |
| M15 B03/B07/B08/B09: API/workers/health | services/delivery/clients/, workers/, observability/ | Entry/commands, bridge and admin metrics | Preserve API base, schedule/timezone/stop/retry/redaction; no duplicate timers | P0/P6/P8 | Stop only approved test workers and return to retained modules/config; High | Worker-specific reviewed slices |
| M16 B01/B02/B12: entry/environment/package+lock | services/delivery/ service boundary | Node start/dev/deployment imports | Guard must run before live imports; retain old launcher until initialization contract and CWD/state paths are tested; move manifest+lock together without upgrade | P0/P6/P8/P9/P10 | Retained old guarded entry/start path and package pair; High | Late delivery structural slice |
| M17 B10: config/database.js | legacy-db adapter only if retained | Unknown direct DB users | DEPENDENCY NOT FULLY VERIFIED; no pool import against real DB | P0/P4/P6 | Keep original file and consumers unchanged; High, HOLD | ZL-14/29 consumer review; ZL-12 hold |
| M18 X01: fb-video-api/ | services/media-extractor/ | Public Shots and admin PHP/browser calls | Preserve loopback/mode guard/HTTP response/base URL; top-level listen cannot be imported in tests; lock addition is separate dependency work | P0/P2/P5/P7/P9 | Repoint approved local test endpoint to retained guarded service; never enable legacy extraction; High | Separate adapter compatibility slice |
| M19 D01/D02: six SQL references | database/schema/legacy/, migrations/legacy/ | Operator/tool references and future query contract | Byte-preserving source move only; no SQL execution/reformat/schema repair or blind FIXED ordering | P0/P4 plus path-reference metadata | Retain original references and migration ledger; data rollback separately approved; High | After schema proposal and external-reference review |
| M20 G01-G07: policies/docs/scripts/locks/vendor/binaries | Mostly root/current locations; reviewed component boundaries later | Developers, exporter, mailer, browser identity | Keep baseline hashes/history, scripts, ignore/examples and licenses; no automatic approval of new export paths; orphan lock/rights unresolved | P0/P2/P5/P9 | Retain policy/import/asset maps and original records; Medium/High | Explicit packaging/licensing slice only if needed |
| M21 W25/B11 and P01: prototype/diagnostic/ignored alternatives | Preserved archive only after approval | Prototype links/direct URLs/developer/deployed invocations | Prove replacement and consumer ownership; P01 contents still uninspected, so HOLD; archive candidate is not safe-delete | P0/P2/P5/P8/P9 as relevant | Restore retained aliases/implementation without overwriting new owner work; High for prototype/P01 | Stage 10 only, archive-review issue |

W21/W23/W29 payment/maintenance/diagnostic paths remain disabled in place until
their own adapter/retention decision; they are not prerequisites for the discovery
demo and are not silently moved into executable jobs. G05 orphan lock/G07 binary
rights and every UNRESOLVED module remain on hold unless their dossier is cleared.

## Concrete compatibility manifest required in ZL-12

For each approved slice, record: old/new filesystem paths, entry URLs, include
callers, script/style/media URLs, form actions, AJAX/JSON bodies, response fields,
status/redirect/headers, cookie/session keys, localStorage/cache keys, process
start command/CWD, config variable names, state/media roots and export policy.
Use real currently observed values for nonsecret contracts and fictional
identities/fixtures. Never write real secrets or private operational paths.

A browser test must cover both direct PHP and clean slug URLs. For example,
relative `api/live_search.php` on a nested detail URL must be resolved explicitly;
static file existence does not prove correct browser requests. Preserve query
strings, content associations, episode navigation, native video/resume and
repeated inline overlays. Test denied helper/private/vendor/extractor paths too.

## Rollback mechanics and evidence

1. Before each slice, preserve the current uncommitted owner work and record
   hashes/path/route contracts. Any Git commit/branch requires the authorization
   applicable to that future task; none is created here.
2. Retain old handlers/helpers/assets behind compatibility shims until parity
   tests pass. Rollback restores dispatch/import/reference maps to those retained
   implementations through a new reviewed corrective change.
3. If future migration commits are explicitly authorized, a reviewed new
   `git revert <migration-commit>` may be appropriate; do not reset, amend,
   rebase, force-push or overwrite later user edits. This is future guidance,
   not a command executed in ZL-11.
4. Re-run the same before/after test profiles against the rollback. Preserve new
   work as evidence rather than deleting destination folders during failure.
5. Source movement does not include data migration. A DB rollback needs a
   separately approved target, sanitized test proof, backups and a reviewed
   forward-correction/down plan; never blindly run destructive SQL.
6. Private session/media/state roots remain separate. No rollback extracts old
   ZIP content, replaces current workspace files, clears authentication stores
   or contacts real accounts.

## Stop conditions

Stop that slice when a route/consumer/state owner is unresolved; a pre-existing
defect cannot be safely characterized; private roots/backups are unapproved;
a schema/interface/security gate fails; new code needs live credentials; files
would become public; or rollback cannot be demonstrated in isolation.
Record NOT VERIFIED/BLOCKED for the affected migration, preserve evidence and
continue only independent safe preparation. Do not label a failed flow as parity.

## Exact next recommended task — not started

**Jira ZL-12 — Refactor modules incrementally without route regressions.**

Start with read-only current-state reconciliation and a small approved fixture/
test plan from P0-P5. Preserve all current owner edits. Do not bulk-move the
website or switch document roots first. Select one low-risk leaf only after its
baseline/compatibility/rollback tests exist. Keep schema repair, security fixes,
dependency upgrades, simulator/AI features and archive decisions as separate
reviewed work. Do not start servers or any migration during ZL-11.

## Production execution overlay — supplied Jira plan

This overlay extends the source-to-target M01–M21 rows above. It does not authorize
execution. Stage numbering is planning order, not commit staging. **Every inferred
edge below is PROPOSED DEPENDENCY**, not a verified Jira dependency link.
The owner's [43-story reference](PRODUCTION_GAP_ANALYSIS.md) supersedes generic
issue-TBD labels; no issue is marked implemented or complete from source presence.

All rows inherit: preserve current owner edits and disabled guards; exact touched
paths reviewed first; retain old handlers/wrappers until parity; stop if baseline
tests are unavailable. Structural moves, behavioral repairs, schema migrations and
new features are separate reviewable changes. Never restore a ZIP or reset Git.

| Step / existing stories | Current paths / proposed targets and affected dependencies | Reason, risk and prerequisites | Before / after gates, compatibility and rollback |
| --- | --- | --- | --- |
| R0 ZL-9/10/11 | Existing Git, docs/baseline-source-sha256.txt, docs/recovery and docs/architecture remain in place | Confirm existing holds and current uncommitted work; do not rebaseline away unexplained changes | Before: status/hash/index comparison. After: reviewed scope. Rollback: documentation correction preserving prior records; no source restore |
| R1 ZL-13/31 | scripts/check-source.cjs and export tools retained; propose tests/fixtures plus reproducible environment definitions | Parser checks alone cannot protect routes or data; no source moves before affected fixtures/contracts exist | Before: review runtime/install scripts and legal fixtures. After: isolated parse/unit/schema/routes/browser tests and secret gates. Rollback: disable new CI job/environment config, retain evidence; no production contact |
| R2 ZL-12 | M01–M21 exact current group paths → compatibility wrappers and proposed apps/web/src or services boundaries | Start one low-side-effect leaf; DB/session/HTTP/browser globals make broad moves high risk | Before/after: P0–P5 as relevant; stable methods/URLs/query/cookies/assets/exports. Rollback: retained shim points to retained old implementation; no destructive checkout |
| R3 ZL-14 | admin/config.php, includes/database.php/settings.php, PHP query consumers and six SQL references → explicit Database repositories plus reviewed database/migrations | Canonical schema/IDs and adapter contract precede stateful repairs; incompatible token generations and runtime DDL are blockers | Before: fictional canonical DB, PDO/MySQLi tests, constraints/index/query plan and backup. After: approved additive migration/version and all consumer tests. Rollback: expand/contract compatibility or reviewed forward repair/isolated restore; never all legacy SQL |
| R4 ZL-19/18 | W03–W05/W14–W17 and guarded endpoints → Auth/Admin and isolated adapters | Admin upload/publication needs secure roles/MFA and mutation methods early; do not defer until after ingest | Before: fake mail/identity and ownership/CSRF baseline. After: role/session/reset/upload negative tests; integrations remain off. Rollback: prior safe boundary only; never re-enable insecure access as compatibility |
| R5 ZL-15/16/17/27 | W06–W13 catalogue/Shot/profile routes → Catalogue/Movies/Series/Shots/Users and retained URLs | Restore catalogue/social journey on canonical data with safe legal previews; history/privacy contracts explicit | Before: eligible IDs, fixtures, URL model and browser tests. After: search/link/favorites/comments/history ownership and accessible states. Rollback: retained wrappers plus compatible DTO version; no false claim direct MP4 is finished HLS |
| R6 ZL-52/55/33/53 | Existing title/Shot/admin metadata → proposed Rights and publication workflow; canonical tables added only in later authorized task | Rights cover full media plus clips/posters/audio/subtitles, territory/expiry and privacy; licensing not established by source | Before: owner-verified grants and policy requirements. After: publish denial, expiry/revocation/takedown and audit tests. Rollback: keep content unpublished or denied; never reinstate revoked rights |
| R7 ZL-41/46/48 | No established infrastructure implementation → proposed infra/environments and private storage/runtime configuration | Select minimal region/host/storage/CDN, budget and recovery goals; vendor facts/prices unverified; no paid provisioning here | Before: current official vendor review, cost estimate, network/identity plan and owner authorization. After: isolated staging, HTTPS and private origin/DB denial. Rollback: reviewed IaC/cutover changes; preserve data, DNS old target and audit |
| R8 ZL-42 | admin/trailers.php/media upload UI → proposed Media ingest intents and services/media-worker validation | Unsafe unquoted ffmpeg path must not be reused; authenticated quarantine/rights/provenance required | Before: safe upload fixtures/limits/sandbox. After: spoof/corrupt/oversize/path/shell cases, idempotent outbox/job and provenance tests. Rollback: disable new ingest; retain quarantined assets/evidence, no automatic deletion |
| R9 ZL-43 | Thumbnail responsibility only → proposed worker jobs, private versioned masters/renditions and metadata | Durable bounded HLS encoding must recover without publishing incomplete outputs; requires R3/R6–R8 | Before: legal sample encode/probe/ladder and cost bounds. After: retry/lease/dead-letter/lost callback/output/audio/subtitle tests. Rollback: stop new jobs and retain last authorized validated version pointer |
| R10 ZL-44/45/17 | movie-details.php/series-details.php and Shot linked IDs → ContentAccess/playback DTO and HLS adapter while URLs remain | Authorize manifest and all segments/tracks; maintain resume semantics; no public-origin fallback | Before: signed access, rights/identity and browser matrix. After: ABR/seek/captions/audio/expiry/renewal/origin denial/takedown/cache tests. Rollback: last authorized healthy asset/player version or controlled unavailable state; do not fall back to exposed permanent URLs |
| R11 ZL-20–23/25 | Existing catalogue and Shot ID links → Companion interface, optional services/ai | New feature separate from refactor; no arbitrary SQL/model authority. Core viewing works with provider off | Before: ground-truth evaluation, eligibility API, consent and budgets. After: validated IDs, deterministic fallback, injection/timeout/privacy/quota tests. Rollback: AI feature off and catalogue search remains |
| R12 ZL-24/26/27 | Existing browser layout only → optional launcher/STT/TTS UI interfaces | Text-first and explicit mic/audio controls; voice does not gate core viewing | Before: safe text companion and privacy policy. After: denial/stop/mute/transcripts/keyboard/reduced-motion/mobile tests. Rollback: voice off; no audio retained by default |
| R13 ZL-28/54 → ZL-29/30 | W19/W20/B01–B12 → optional provider-neutral contracts, simulator and approved adapter | Feasibility/approval and messaging rights are hard live gates; token/ack correctness required even in fake mode | Before: canonical token schema, strict status and fake concurrent redemption. After: recipient acknowledgement/cache/local fallback/queue/restart/unknown outcome tests. Rollback: messaging off; independent CDN remains; no session manipulation |
| R14 ZL-49/50/35/47 | Health/analytics intent and proposed infra/CI → redacted metrics, alert/runbooks, encrypted backups and artifact promotion | Begins alongside R3/R7, finishes before external alpha; not a late afterthought. Restore and operational staffing needed | Before: approved SLO/RPO/RTO, permissions/keys/retention. After: measured restore, alert delivery, job/app/player metrics and staging rollback rehearsal. Rollback: pin known-good artifact, separate schema recovery and retained telemetry; do not undo rights revocations |
| R15 ZL-31/32/33/34/51/36 | Tests and release evidence → controlled staging/alpha/canary and approved public artifact | Security/load/legal/support gates and cost budgets block public launch; AI/voice/messaging may remain off | Before: canonical data, rights, HLS/CDN, auth, DNS, backup, monitoring and policy evidence. After: scoped alpha, agreed canary thresholds, owner approval, measured production verification. Rollback: stop promotion, restrict traffic, pin compatible safe artifact, preserve incident evidence |
| R16 ZL-12/31 | W25/B11 archive candidates and W26/W28/B10/G05 unresolved → retained evidence or future archive only | No static-reference search authorizes deletion; caller ownership and replacements first | Before: consumer/route/fixture and owner disposition. After: tested redirects/restoration and privacy/export review. Rollback: retained approved legacy path without reviving unsafe service |

R4 security, R6 rights and R14 operational groundwork run early where their
dependencies permit. R5 is not a production HLS claim before R8–R10. A stage may
remain blocked while independent documentation/tests proceed; do not force the
linear order through failing prerequisites.

### Production rollback and release invariants

- Compatibility manifests retain old URI, handler, caller, DB/response/cookie and
  browser-storage contracts. Each new path needs export policy review (ZL-10/31).
- Version application artifacts, schema compatibility and media independently.
  An old app may run only against a tested compatible schema; rollback is not a
  destructive down-migration or Git reset.
- A failed ingest/encode leaves content unpublishable. Revert an asset pointer only
  to a healthy currently licensed version. Short-lived CDN grants and takedown
  policies remain in force across application releases.
- AI/voice/messaging have separate feature switches and typed unavailable states.
  Turning them off preserves authorized catalogue/CDN use.
- Canary thresholds and error budgets require owner approval from measured staging
  baselines; no arbitrary green threshold is asserted passed. A backup is not
  accepted until an isolated restore meets the approved objectives.
- Live provider, paid cloud, production DB/DNS and rollout actions need explicit
  phase authorization. ZL-11 performs none.

**Exact next recommended task: ZL-12 — Refactor modules incrementally without route
regressions.** Begin with current-state reconciliation and the smallest covered
leaf; if required route/environment tests are unavailable, record the ZL-13/31
prerequisite and do not move that behavior. No ZL-12 execution has begun here.
