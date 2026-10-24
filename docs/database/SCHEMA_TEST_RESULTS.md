# ZL-14 execution results

Date: 2026-10-23, Asia/Colombo. **PARTIAL**: the four-table subset is verified;
the complete canonical application database and full compatibility are blocked.
Tests use only fresh synthetic targets and fictional metadata. No private dump,
production database, real user/session/media or live integration is used.

## Environment and actual commands

Windows host: Node 24.13.1 and PHP 8.2.12. Image: PHP 8.2.35, Node 24.22.1 and
the digest-pinned MySQL 8.4.13 image from ZL-13. Docker Desktop Linux/Compose.
The temporary server has an internal network, no published port and tmpfs data.
Existing development/staging/CI volumes are outside the migration harness.

```powershell
$env:PHP_BINARY = 'C:/xampp/php/php.exe'
node scripts/verify.cjs
node scripts/environment.cjs up ci
node scripts/environment.cjs test ci
node scripts/database-test.cjs ci
node scripts/environment.cjs audit ci
node scripts/environment.cjs secrets
node scripts/environment.cjs down ci
```

Workflow validation: checksum-verified actionlint 1.7.7 with `-shellcheck=
-pyflakes= .github/workflows/ci.yml`; PASS. External shellcheck/pyflakes were not
run. GitHub-hosted Actions remains **NOT VERIFIED / NOT RUN**: no commit/push.

## Results

| Check | Expected | Actual result and evidence classification |
| --- | --- | --- |
| Starting Git/source preflight | Preserve existing work and identify nested repositories | Branch docs/zl-11-production-architecture, HEAD 804ec5ab07503c3fb0c311b4349443b87184e28f; 275 tracked + 32 pre-existing untracked inputs; 48 nonprivate source parents inspected, no nested .git |
| Unit/static suite | Existing tests plus new runner/offline Node checks pass | **PASS: 145 passed, zero failed/skipped** on host and container; 115 PHP / 44 JS parsers pass in reviewed context; test-covered and executed |
| Ordering/checksum/missing/unsafe fixtures | Reject before connecting | PASS: reversed order, changed bytes, missing file and destructive fixture SQL refused; no destructive statement executed |
| Invalid settings | Fail closed before connecting | PASS: production mode, arbitrary host/database, root role, missing password/local flag/token/server identity refused |
| New target validation | New owned server, empty baseline, explicit synthetic identity | PASS: local daemon/labels/mounts/ports checked, initial non-system table count zero; server UUID and hashed marker verified before migration writes |
| Fresh initialization/version ledger | Four ordered tables and four applied records | PASS: applied=4, fullApplicationReady=false; actual metadata matched all four definitions |
| Repeatability | No reapplication or attempt inflation | PASS: skipped=4, applied=0; completed attempts remain one |
| Historical subset upgrade | Adopt the two permitted original part CREATE shapes, retain rows | PASS: two source-derived part tables adopted; two missing subset tables created; fictional part row unchanged |
| Post-DDL SQL failure | Ledger failed, already-committed table preserved | PASS: real SQL error injected after CREATE; table remains, fixed error code recorded, retry reconciles it and increments only its attempt |
| Interrupted running state | Reconcile only exact checked DDL | PASS: running step matched and recovered; no guessed rollback |
| Schema/ledger/history drift | Refuse unknown or altered state | PASS: added schema column, added ledger column and changed applied checksum rejected |
| Advisory locking | Second connection fails without migrating | PASS: MIGRATION_LOCKED with zero-wait lock; migration succeeds after release |
| Wrong target marker/UUID | Refuse mutation | PASS: both independently rejected |
| Invalid synthetic credentials | Generic failure, no secret diagnostics | PASS: MIGRATION_CONNECTION_FAILED |
| Database unavailable/recovery | Bounded failure while paused; success after unpause | PASS in final run: unavailableDatabaseRejected=true and unpausedDatabaseRecovered=true |
| Uniqueness/nullability | Preserve actual source constraints | PASS: duplicate settings/movie-part/episode-part rejected; message_id NULL rejected; optional part metadata accepts NULL |
| New foreign-key validation | Require complete parent DDL first | **BLOCKED / NOT RUN** for catalogue/account relationships; no speculative parent/FK/cascade created |
| Parameter binding and DML rollback | Input remains data; InnoDB DML rollback works | PASS: injection-shaped key returns no match; inserted transaction fixture rolls back |
| Runtime/migration privilege separation | Runtime cannot CREATE; migrator has no DROP/global CREATE | PASS: runtime DDL denied, grants reviewed as booleans, arbitrary CREATE DATABASE denied |
| Representative PHP queries | Settings and part lookup contracts execute | PASS via PDO/MySQLi on synthetic rows; database integration suite totals **42 passed, zero failed** |
| Original PHP bootstraps/repeated PDO parameter | Preserve original connection behavior | PASS when both bootstraps explicitly included; emulation/repeated parameter query preserved; no full business-route acceptance |
| Legacy settings/rate-limiter helper calls | Observe current behavior without granting schema privilege | **Known compatibility failures observed:** both attempt runtime CREATE and are denied under the DML account; preserved source, unresolved blocker |
| Offline Node reader | Exercise current query/return shapes without packages/network | PASS: missing/inactive/used/expired cases, uppercase input and bound parameter shape; **not mysql2 database integration** |
| Index plans | Verify source-backed keys without speed claims | PASS: unique_movie_part, setting_key and idx_ip_action selected in representative EXPLAIN; tiny fixture only |
| Encrypted synthetic backup/restore | Exact schema and row match on a separate empty target | PASS: AES-256-GCM in-memory roundtrip, structure/data hashes match; restored ledger skips all four versions; no plaintext/key file written |
| Complete historical/production upgrade | Require approved full DDL/data assumptions | **BLOCKED / NOT RUN**; only the documented two-part-table fixture path is supported |
| Website process/containment smoke | Keep ZL-13 response contracts | PASS: live/dependencies 200, readiness 503; CSS 200 and seven sensitive/disabled paths 403 |
| Full application/auth/social/player/live delivery | Verified complete schema and authorized fixtures | **BLOCKED / NOT RUN**; no integration activated |

The 42 database assertions do not include the additional backup/restore, outage
and bootstrap summaries, and do not convert known legacy failures into successful
business acceptance. Static source findings are distinguished from executed
database observations throughout the inventory/access guides.

## Failure investigation and limits

The first quoted-string inventory scanner was too slow and was stopped; a bounded
linear scanner replaced it. SQL-like UI labels were manually filtered from the
public table inventory. These static extraction limits are recorded rather than
presented as exhaustive SQL parsing.

The final all-refs comparison detected origin/main changing from 45b9309f4a25 to
94f7708be599; origin/HEAD's resolution changed with it. No remote Git operation or
ref mutation was issued by this task. HEAD/tree, local branches/tags and the empty
index remain preserved. The remote-tracking change is recorded without resetting
it; all starting application/source checksums remain valid.

An initial paused-server probe did not fail within its intended deadline: PDO's
connect timeout alone did not bound the stalled mysqlnd handshake. The synthetic
server was safely unpaused, and its owned test resources were removed. The new
migration connection now scopes mysqlnd.net_read_timeout=2 during construction,
restores the prior process setting afterwards, suppresses raw constructor warnings
and reports a fixed code. Legacy application PHP configuration/source is unchanged.
The corrected final outage/recovery test passed.

Inherited Compose image labels initially made transient test containers appear to
belong to the preserved CI project, correctly triggering its ownership refusal.
The harness now assigns its own unique project/run labels. No ownership guard was
weakened. Final cleanup removes only the new synthetic containers/network and
never requests volume deletion. Full source preservation, final image/secret
scope and teardown evidence are recorded in the recovery/contribution handoff.

## Remaining acceptance blockers

Twenty-four inventoried tables lack a complete permitted definition. Historical
token/message/bot/view/routine generations also conflict. The owner confirmed no
sanitized full schema is available and prohibited inventing it or opening the
private dump. [SCHEMA_INVENTORY](SCHEMA_INVENTORY.md) lists each missing definition,
affected modules and exact evidence needed. Runtime DDL coupling, missing PDO
route includes and full Node database contracts remain unresolved. A passing
four-table test suite is not a complete schema or production-readiness claim.

ZL-14 remains PARTIAL. Resume it only when the required approved schema evidence
and a scoped compatibility plan are supplied. ZL-15 has not begun.

## Final preservation, image and security checks

- 307 starting inputs present: 289 byte-identical and 18 intended shared
  infrastructure/documentation changes; 21 new files. Every application source,
  tracked historical SQL and baseline checksum remains unchanged. Prior contribution
  content is an exact byte prefix. No Git staging, commit, branch change or push.
- Broader source-only syntax sweep: 176 PHP and 45 JS parsed, zero failures.
- 84 inspected local documentation links resolve; fences/new-text/working-diff
  whitespace checks pass.
- Final CI image contains exactly 204 reviewed paths with matching SHA-256 values;
  zero unexpected/missing files. The private dump/environment/session stores are
  not build inputs. Only the four new reviewed SQL migration files enter context.
- Gitleaks 8.30.1 final expanded scan: 219 reviewed text inputs, exit 0, redacted
  and no findings. Scope excludes private stores, history and binaries; this does
  not prove absence of every possible secret/PII pattern.
- Post-teardown inspection: zero ZL-14 containers/networks, zero managed profile
  containers, all three preserved ZL-13 database volumes still present. Managed CI
  DB audit before shutdown still reports zero application tables and least privilege.
- The all-refs equality check detected the separately documented origin/main /
  origin/HEAD remote-tracking change. HEAD/local branches/tags/staging are preserved;
  that reference observation is not hidden or reset.
