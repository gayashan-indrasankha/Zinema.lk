# Refactor execution — Jira ZL-12

Date: 2026-10-23 (Asia/Colombo). **PARTIAL overall: two selected low-risk batches
implemented and functionally verified; full HTTP/browser/database route acceptance
remains blocked.** The implementation audit below predates the owner's later
publishing request. At that audit boundary no ZL-13 work, Git staging, branch,
commit, push or deployment had been performed. Subsequent publication does not
complete the deferred route acceptance gates.

## 1. Scope and authoritative source

Continue the [ZL-11 migration plan](MIGRATION_PLAN.md) using the existing extracted
working tree. No archive or historical checkout replaced current files. This is
a small implementation step toward maintainability and the hackathon demonstration,
not a production-ready streaming system. Existing safeguards and disabled adapters
remain. No framework, dependency, database/schema, worker or new product feature.

## 2. Initial Git and preservation state

- Branch: docs/zl-11-production-architecture.
- HEAD: 804ec5ab07503c3fb0c311b4349443b87184e28f.
- Working tree clean; index empty; 275 tracked paths. No pre-existing modified or
  nonignored untracked files. No prior REFACTOR_EXECUTION or dedicated leaf suite.
- Captured SHA-256/size metadata for all 275 tracked paths, HEAD/tree/all refs/index
  and the full contribution prefix before changes. Preserved the two current
  helper implementations in ignored same-session recovery evidence.
- Checked 25 nonprivate tracked parent directories for nested .git markers: none.
  Root Git retained; private preserved history, generated dependencies, sessions,
  logs, dumps and media were not searched for contents or relocated.
- ZL-9 BASELINE/checksum manifest, ZL-10 ignore/example protections and prior audit/
  recovery records were reviewed. Existing four export holds remain; no old hash
  entries were rewritten to conceal the intended ZL-12 changes.

## 3. ZL-11 decisions and bounded adaptation

Selected M01/W24, the explicitly recommended disabled ads leaf. Selected only the
side-effect-free B02 configuration parser from M16; **full M16 entry/package/state/
scheduler movement remains HOLD**. The parser depends solely on node:path, reads
only a supplied environment object when called, and imports no socket/worker/DB
module. Its consumers keep their old import paths.

M02 unknown helpers, M03 session/rate-limit helpers, M04 bootstrap, M07–M12 pages/
auth/admin/DB/token paths, M13–M15 workers/state and M18 extraction are deferred:
their existing HTTP/schema/security/scheduler fixtures are not available.
Do not interpret a pure-helper test as clearance for those larger moves.

## 4. Batch inventory and outcomes

| Batch | Source → canonical target | Dependencies / expected change | Risk and gate | Outcome |
| --- | --- | --- | --- | --- |
| B1 — M01/W24 | Zinema.lk Website/includes/ads.php → apps/web/src/Views/Ads.php | Eight PHP include consumers retain the old file; shim uses __DIR__. Eight functions/constants/empty output unchanged; no bootstrap/session/DB/HTTP dependency | Low; characterize before, test after, preserve exact implementation, update precise export dependency | Implemented; 35 baseline leaf +32 old export tests passed after move; then six new export tests passed (73 total) |
| B2 — narrow B02/M16 slice | whatsapp-bot-deploy/config/environment.js → whatsapp-bot-deploy/src/config/environment.js | index.js, config/api-client.js and config/multi-tier-forwarder.js retain their literal imports. CommonJS shim re-exports same object | Low for parser only; explicit fictional env, no consumer execution, unchanged live guard, exact implementation and precise export dependency | Implemented; 78 tests passed with both export pairs; final boundary/rollback suite brings total to 85 |

Both canonical implementations match the original pre-refactor bytes exactly.
Original file paths remain as shims; no original pathname was deleted. No browser
asset, public handler, rewrite rule, SQL, manifest/lock or runtime state was moved.

## 5. Before and after layout

~~~text
BEFORE
Zinema.lk Website/includes/ads.php            disabled functions/constants
whatsapp-bot-deploy/config/environment.js    pure parser and live-start guard
scripts/prepare-public-export.cjs           original exact path policy

AFTER (only these boundaries implemented)
Zinema.lk Website/includes/ads.php            retained PHP shim
apps/web/src/Views/Ads.php                   exact original implementation
whatsapp-bot-deploy/config/environment.js    retained CommonJS shim
whatsapp-bot-deploy/src/config/environment.js exact original implementation
tests/fixtures/ads-contract.php              CLI-only contract probe
tests/refactor/leaf-contracts.test.cjs        35 behavioral contracts
tests/refactor/export-dependencies.test.cjs  11 fail-closed packaging regressions
tests/refactor/module-boundaries.test.cjs    7 boundary/rollback cases
~~~

The directory tree in TARGET_ARCHITECTURE remains a proposal. No new public root,
framework, services/delivery tree or empty destination scaffold was created.

## 6. Dependencies, includes and compatibility shims

PHP shim: require_once __DIR__ . '/../../apps/web/src/Views/Ads.php'.
No CWD-dependent new include, no closing-tag output and no bootstrap. The original
constants remain ADS_ENABLED=false and SMART_LINK_URL=''; seven render functions
return null/output nothing and getSmartLinkUrl returns an empty string. Repeated
require_once remains silent. Existing known callers already use require_once.

Node shim: module.exports = require('../src/config/environment').
readConfig and assertLiveWhatsAppEnabled retain their original signatures,
normalization/errors, immutable DTO, path rules and live flags. All three consumer
resolution contexts resolve the old path and receive the same canonical object.
Tests construct require contexts without executing index, API client or forwarder.

Only dependency-free implementations moved. No circular imports, environment
loader, duplicate configuration policy, state-root adjustment or new package.
Debug stack filenames may now identify canonical files; public import paths remain.

## 7. Before/after public route and asset matrix

“STATICALLY VERIFIED” here means the handler/asset/rewrite bytes and include
relationships are unchanged, not a completed HTTP request or correct legacy
behavior. “FUNCTIONALLY VERIFIED” is limited to executed helper/parser contracts.

| Journey / current path or URL | Before → after | Verification / unresolved gate |
| --- | --- | --- |
| Homepage / Shots: /index.php and / | Same handler, assets, queries and mode guards | STATICALLY VERIFIED; full HTTP/DB/browser NOT TESTED |
| Movies: /movies.php | Same handler and includes/ads.php reference | Helper FUNCTIONALLY VERIFIED; page HTTP/schema BLOCKED |
| Movie detail: /movie-details.php and /movie/{id}/{slug} | Same handler, rewrite and native player/JS | STATICALLY VERIFIED; actual HTTP/player/media NOT TESTED |
| Series: /tv-series.php, /series-details.php and /series/{id}/{slug} | Same handlers, rewrite and helper imports | Helper FUNCTIONALLY VERIFIED; full page/player BLOCKED |
| Collections: /collections.php, /collection-details.php and /collection/{id}/{slug} | Same handlers and helper imports | Helper FUNCTIONALLY VERIFIED; full page BLOCKED |
| Guarded delivery views: /download.php, /whatsapp-watch.php | Same handlers, guards and helper import locations | STATICALLY VERIFIED; not enabled/executed |
| Search: /find_movies.php and /api/live_search.php | Same entry points, request construction and response code | STATICALLY VERIFIED; DB responses NOT TESTED |
| Login/signup/reset/verification/profile | All existing public files and AJAX/API variants retained | STATICALLY VERIFIED; session/schema/mail flows BLOCKED |
| Admin and internal APIs | All existing route and authorization code retained | STATICALLY VERIFIED; existing security gaps not repaired |
| CSS/JS/images/navigation/forms/redirects | All existing assets and public handler bytes retained | STATICALLY VERIFIED; rendering/accessibility NOT TESTED |
| Bot environment imports at three consumers | Same literal requests → shim → same exports | FUNCTIONALLY VERIFIED without importing consumers |
| /includes/ads.php | Same old filesystem path; no output from helper inclusion | CLI include FUNCTIONALLY VERIFIED; direct HTTP NOT TESTED |
| New apps/ and bot src/ modules | Outside unchanged intended website document root | Static placement verified; deployed Apache/document-root/access-denial NOT VERIFIED |

All 81 handler-route candidates from CURRENT_ARCHITECTURE remain at their original
paths and bytes. No query/body/cookie/status/JSON/redirect/media URL was deliberately
changed. This does not repair existing relative-URL, schema or authorization defects.
No new universal router, symlink, rewrite, public upload or asset relocation.

## 8. Database and bot boundaries

No database connection, query, migration, table or data operation occurred.
MySQLi $conn and PDO $pdo interfaces remain unchanged; bootstrap mismatch, token
schema generations, settings runtime DDL and table/ID vocabulary remain ZL-14 work.
Do not import a “FIXED” migration or replace adapters during the next structural move.

Bot index/live guard, package/lock, API client, token validation, queue/forwarder,
refresh/warmer/health schedules and configured private state/media roots are
unchanged. No Baileys/client/worker entry was imported or started. Synthetic tests
call the pure guard with fictional objects; approving such an object does not
start a bot or authorize a real account.

## 9. Regression baseline and actual execution

Before application changes:

- New legacy-path characterization suite: **35 passed, zero failures/skips**.
  Includes PHP signatures/constants/output at three CWDs and Node validation,
  placeholder/missing-key/URL/path/production/live gates, frozen DTO, explicit
  child environment and no directory creation.
- Existing synthetic export suite: **32 passed, zero failures/skips**.
- Actual-source no-write plan: 188 eligible files, four manual-review holds,
  no output directory created.

After B1: 67 baseline tests passed; adding six PHP dependency-export cases produced
73 passes. After B2 and its second export pair: 78 passes. Final suite:
**85 passed, zero failures, zero cancelled, zero skipped** (35+32+11+7).

Exact executed command forms from repository root:

~~~powershell
$env:PHP_BINARY='C:/xampp/php/php.exe'
node --test tests/refactor/leaf-contracts.test.cjs
node scripts/test-public-export.cjs
node --test tests/refactor/leaf-contracts.test.cjs tests/refactor/export-dependencies.test.cjs scripts/test-public-export.cjs
node --test tests/refactor/*.test.cjs scripts/test-public-export.cjs
~~~

Direct parser loop over tracked plus new approved code:
PHP 8.2.12 -n -l on **166 PHP files**, Node 24.13.1 --check on **40 JS/CJS files**:
zero failures. This count includes bundled PHP vendor files and the new fixtures/
modules/tests, unlike the narrower first-party ZL-11 count. Parser output was
captured; no source-quoting diagnostics exposed. Do not use the tracked-only
check-source script as sole coverage of newly untracked files.

Direct file/include checks found no missing literal PHP include candidates.
Computed shim resolution is additionally exercised at runtime; static checks
cannot prove all dynamic or externally deployed consumers.

## 10. Failed, blocked and not-tested checks

No executed assertion, parser or security check remains failed. No test was skipped.
One patch operation was rejected before application because it attempted a delete/
add for the same path; it made no edits. It was replaced by an in-place update.

BLOCKED: complete application HTTP/Apache rewrites/access denial, canonical fixture
DB, auth/session flows, page/browser/media behavior and service/worker contracts.
No reproducible isolated app environment/canonical schema exists; none was invented.
Those are future ZL-13/14/31 prerequisites, not passes from the 85 leaf/export tests.

No live messaging/extraction/payment/cloud/model/service or legally uncertain
media test. No external integrations or production systems contacted.

## 11. Security and safe-export integration

Reviewed exactly two canonical paths for the existing deny-by-default exporter:
apps/web/src/Views/Ads.php and whatsapp-bot-deploy/src/config/environment.js.
Only these paths were added; apps traversal is gated by exact membership. Unknown
app code remains excluded without opening it. Other new tests/architecture files
do not acquire blanket export approval.

Policy version is now zl12-leaves-v1. Exact shim→implementation dependencies are
enforced during planning, export-before-write and manifest validation. A missing,
content-rejected or manually removed implementation raises MISSING_MODULE_DEPENDENCY.
Synthetic tests cover these cases, valid paired exports and edited plans/manifests.
Old policy-version manifests must be reviewed under their matching policy; the new
validator does not silently accept them.

Actual-source no-write plan now selects **190** files (188+2), retains the same
four holds, and creates no output. Only fictional fixture exports were written.
No real public export/publication was authorized. Existing ignore rules, environment
templates, sensitive path/content gates and media/session exclusions remain.

## 12. Source-preservation and Git verification

Final executed checks: all 275 initial tracked paths remain present; 266 are
byte-identical and nine have only the documented source/tool/documentation changes.
Both relocated implementations match their original SHA-256. The prior contribution
prefix and all Git refs/HEAD/tree/branch/index remain intact. The 81 existing route
handler candidates, rewrite rules, assets, SQL, dependencies and guards are unchanged.
All 48 local links across seven changed/new Markdown documents resolve; fences,
bounded privacy checks and git diff --check pass. Gitleaks 8.30.1 scanned the exact
16 changed/new artifacts through fully redacted stdin: exit 0, no findings or report
files. Scope is current changed artifacts, not all history/private stores or a full
security/vulnerability audit. Five sensitive-ignore probes passed. Final regression
rerun after generated-file formatting: 85 passed, zero failed/cancelled/skipped.

The immutable 176-entry historical manifest itself is unchanged. At phase start,
173 entries matched and the three known workflow inputs differed. After ZL-12,
170 match and six differ: the same workflow inputs plus README.md and the two old
helper paths now containing shims. Those three new deltas are intentional; both
original implementations remain byte-identical at their canonical paths. No old
manifest hash was regenerated or “fixed” to conceal the refactor.

HEAD, committed tree, branch, all Git refs and empty index remain unchanged.
All 275 initial tracked paths remain present. Only the two helper shims, narrow
export policy and explicitly listed developer/recovery documentation may differ.
The two original helper implementations are retained byte-for-byte in canonical
locations; original BASELINE/checksum, SQL, dependencies, guards, route handlers
and assets remain unchanged. Previous contribution content is an intact prefix.
Final exact counts and bounded redacted scan results are recorded at handoff.

No Git add/mv/reset/stash/clean/commit/branch/fetch/push, deletion of original source,
archive restoration or private-state operation. Test cleanup only removes verified
owned temporary synthetic directories, never the repository or real data.

## 13. Rollback procedures

Both rollback/reapply rehearsals passed in isolated fixtures: copy the retained
implementation into the old slot, execute the same contracts, then restore the
thin wrapper and execute again. No workspace/source was replaced for the rehearsal.

For an owner-approved working-tree reversal:

1. Inspect status and compare the current wrapper/canonical hashes with this batch.
   Stop if subsequent user edits are present; do not overwrite them.
2. For B1, copy the unchanged canonical Ads.php implementation back into the old
   includes/ads.php slot. For B2, copy the unchanged canonical environment.js back
   into config/environment.js. These are retained current-session bytes, not ZIP/
   historical checkout recovery. Keep destinations as evidence; no deletion needed.
3. Review the exact policy additions/dependency assertions/version change together;
   do not leave a shim selected while its target is unapproved. Restore the previous
   policy only if no later approved export changes would be lost.
4. Run the 35 original leaf contracts and 32 original export tests. The extra direct
   module/paired-export suite assumes canonical destinations remain present.
5. Record the corrective action; never reset history, restore all files, mutate
   real sessions/media or undo unrelated owner work.

Temporary compatibility paths remain until a separately authorized route/consumer/
deployment review proves removal safe. This issue does not schedule their deletion.

## 14. Deferred refactoring and known risks

Keep all schema/session/auth/admin/public-page/worker/extractor/private-state
movement gated. Existing TD01–TD29 defects are not repaired here. Full M16 remains
High/HOLD despite the isolated parser relocation. The intended website root stays
Zinema.lk Website; deploying only that folder now omits the external PHP module,
so use the reviewed complete repository layout/package. Do not run from arbitrary
old component deployment instructions or expose the workspace root as HTTP root.

Runtime stack filenames and include-file counts change as expected; helper results,
errors, flags and import contracts do not. No assumed dynamic caller was deleted.
The default config signature still permits process.env; tests use an isolated
explicit environment and never load preserved dotenv files.

## 15. Developer documentation and provenance

[README](../../README.md) documents the implemented boundaries, full-layout packaging
requirement and offline test command. [Current architecture](CURRENT_ARCHITECTURE.md)
adds an implemented-state note; TARGET remains a proposal. [Security export](../SECURITY_EXPORT.md)
records the exact policy delta. [Contributions](../CONTRIBUTIONS.md) is append-only;
[status](../recovery/STATUS.md) and [decisions](../recovery/DECISIONS.md) retain history.
AI-assisted analysis, tests, implementation and documentation are disclosed without
inventing original authorship, feature dates or runtime claims.

## 16. Acceptance status

**ZL-12 PARTIAL.** B1 and B2 are implemented and functionally verified, export
dependency closure is tested, and no introduced regression is known. Broader
HTTP/browser/database compatibility acceptance cannot be established in this
workspace. Do not equate 85 passing isolated tests with a working deployed site,
a finished architecture migration or production readiness.

## 17. Exact next task dependencies

**ZL-13 — Build reproducible local staging and CI environments.** Establish isolated
HTTP/Apache/PHP/Node regression fixtures before further route-sensitive movement;
canonical schema reconciliation belongs to ZL-14 and production regression gates
to ZL-31. Those are PROPOSED DEPENDENCY relationships, not verified Jira links.
Do not execute them automatically. No new Jira issue, Git PR or deployment was made.
