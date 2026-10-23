# Environment operations and verification — ZL-13

Date: 2026-10-23, Asia/Colombo. Status: **PARTIAL**. The current working tree is
the source; HEAD is not a snapshot of these uncommitted ZL-12/ZL-13 changes.

## Routine commands

Run from the repository root. Replace `development` with `staging` or `ci` only
when operating that profile. The helper validates identities, resource ownership,
storage, local ports, provider flags and the exact build input list before startup.

```powershell
node scripts/environment.cjs doctor
node scripts/environment.cjs init development
node scripts/environment.cjs config development
node scripts/environment.cjs up development
node scripts/environment.cjs status development
node scripts/environment.cjs health development
node scripts/environment.cjs audit development
node scripts/environment.cjs test development
node scripts/environment.cjs logs development
node scripts/environment.cjs restart development
node scripts/environment.cjs failures development
node scripts/environment.cjs down development
```

`failures` is an explicit local diagnostic: it tries a fictional wrong password,
temporarily stops only the owned DB container, verifies dependency failure while
web remains alive, then restarts DB in a finally block and checks recovery. Do
not run it while another developer needs the same local profile. It preserves
the volume. All other projects and production infrastructure are outside scope.

Normal shutdown uses Compose down without deleting volumes. No helper action
prunes storage or resets a database. Configuration and logs are never dumped.
The helper recognizes only owned ZL-13 resources and refuses name collisions.
Changing local credentials after initialization requires a deliberate migration;
the official MySQL init hook does not rerun for an existing volume.

## Health contracts and service boundaries

| Route | Expected state with an initialized empty DB | Meaning |
| --- | --- | --- |
| /__health/live | 200, alive | Apache/PHP responds; no DB/session/legacy bootstrap |
| /__health/dependencies | 200, dependencies_available, schema unverified | MySQLi and PDO authenticate and SELECT 1 |
| /__health/ready | 503, schema_unverified, next_issue ZL-14 | Full application readiness has not been established |
| Dependencies while DB unavailable | 503, dependency_unavailable | Bounded failure, no connection details |

These small infrastructure endpoints are outside the website source tree and
aliased by the local Apache configuration. They reveal no versions, credentials,
table lists or customer data. DB audit is CLI-only and reports booleans/counts.
Health never invokes admin/config.php or a migration. Compose health uses process
liveness; it intentionally does not pretend schema readiness has passed.

Apache retains the website document root and .htaccess. ZL-12 helpers remain
outside it. Includes are denied over HTTP but still usable by PHP. MySQL is on an
internal project network without published ports. Web also joins a scoped bridge
network to support loopback port publishing on the observed Docker engine.
**Web egress is possible**; disabled adapter settings, local URL/DB guards and
absence of provider processes are the application boundary. No external provider
is contacted during these checks. A future stronger egress policy requires its
own verified design. Do not weaken daemon-wide firewall settings.

Docker's port behavior reference:
[port publishing](https://docs.docker.com/engine/network/port-publishing/).
No public staging, external DB, WhatsApp, extractor, payment, SMTP, OAuth, AI,
media upload, scheduler or bot process is started.

## Fixtures, storage and reset policy

Existing export/refactor fixtures plus the new PHP health driver use fictional
configuration and simulated dependency outcomes. No real account, number,
session, watch history or media is loaded. No movie asset is downloaded or seeded;
no new media provenance claim is made. Existing identity artwork is preserved.

Historical SQL sources identified by the earlier architecture audit conflict.
None has a verified canonical contract. ZL-13 creates an empty local database,
not application tables. The initialization hook narrows the application account
to SELECT/INSERT/UPDATE/DELETE; it cannot create, alter or drop tables. This can
surface legacy bootstrap DDL failures and is intentional. ZL-14 must provide
reviewed migrations, a separate migration identity, deterministic fictional
fixtures, idempotence tests and fixture-reset contracts.

No database seed/reset is advertised as working. There is no destructive reset
command. For future disposable fixtures, first verify exact local daemon,
project/volume labels, DB identity and synthetic-only contents; obtain explicit
approval for any data deletion. Never import original dumps to make a check pass.
Sessions are ephemeral; MySQL volumes survive stop/restart/down. Uploads and
private media are not mounted, and their workflows are not verified.

## Executed verification

The table distinguishes executed checks from proposed work. Results are local
unless explicitly stated otherwise. Commands omit generated credential values.

| Check / command | Environment | Expected | Actual / classification |
| --- | --- | --- | --- |
| `node scripts/verify.cjs` with PHP_BINARY | Windows Node 24.13.1 / PHP 8.2.12 | Syntax and synthetic regressions pass | PASS: 109 PHP + 42 JS parsed; 125 tests passed, 0 failed/skipped; test-covered and executed |
| Full preserved-input plus new-source parser sweep | Windows PHP 8.2.12 / Node 24.13.1 | No syntax regression outside the image subset | PASS: 170 PHP / 43 JS parsed, zero failures; syntax only |
| `config development`, `config staging`, `config ci` | Local Docker / Compose | Distinct local plans, no mounts/live providers | PASS; Compose-parsed and safety-gate validated |
| Docker context fixture build | Newly generated fictional files only | Include one approved template, exclude .env/SQL/Git fixtures | PASS using actual Docker matcher |
| Exact final CI-image file inventory | Isolated no-network disposable container | Only reviewed paths | PASS: 190 expected/actual files, zero extra/missing; runtime image inspection |
| `secrets` | Gitleaks 8.30.1 pinned container, stdin/no mounts/no network | No scanner findings in reviewed text | PASS: 187 text inputs; values redacted |
| Final expanded redacted scan | Same scanner, reviewed context plus all ZL-13 deltas/docs | No scanner findings | PASS: 198 text inputs, exit 0; private stores/history/binaries excluded |
| `up development` / HTTP smoke | Local containers, loopback 8080 | live/dependencies 200, ready 503; static/denied paths correct | PASS: live 200, dependencies 200, ready 503; CSS 200 and seven denied paths 403; runtime verified for these paths only |
| `test development` | PHP 8.2.35 / Node 24.22.1 in image | Same synthetic suite | PASS: 125 passed, 0 failed/skipped; 109 PHP / 42 JS parsers |
| `audit development` | Empty owned local DB | Both drivers available, least privilege, no application tables | PASS: available=true, leastPrivilegeVerified=true, applicationTableCount=0, schemaReady=false |
| `failures development`, `restart development` | Owned local services | Generic wrong-password failure, isolated outage, recovery | PASS: wrong synthetic credential rejected; DB stopped: live 200/dependency 503; DB recovered; restart restored 200/200/503; no volume deletion |
| Local staging / CI reproduction | Separate local projects | Startup/isolation and same health contract | PASS: both built/started, same eight HTTP paths and 200/200/503; each suite 125 passed, 109 PHP/42 JS parsers; both DB audits passed with zero app tables |
| Runtime storage/port separation and generated config | All three local projects | Distinct credentials and DB volumes; loopback web; unpublished DB | PASS: six independent generated credentials, three distinct DB volumes/projects; repeat init preserved each configuration; generated files ignored |
| Normal teardown `down ci`, `down staging`, `down development` | Owned local projects | Stop services, retain volume | PASS: all stopped, volumes retained; no other projects operated |
| Post-teardown owned-resource inspection | Local Docker metadata only | No owned containers; DB volumes retained | PASS: zero owned containers, all three owned DB volumes still present |
| actionlint 1.7.7, explicit workflow path | Windows, checksum-verified official release | YAML/workflow/expression validation | PASS; shellcheck/pyflakes NOT RUN; not a GitHub execution |
| GitHub-hosted Actions | Remote runner | Workflow passes after authorized publication | PENDING / NOT RUN: no commit or push |
| Full application/schema/auth/player regression | No approved schema or media | Verified business journeys | BLOCKED by ZL-14 and later scoped tasks |
| npm install / vulnerability audit / live providers | Not started | Separate reviewed dependency/runtime validation | NOT RUN; root bot lock consistency only is tested |

Failure history is retained: the first new PHP fixture tests failed because their
fictional username prefix did not match the existing guard; corrected fixtures
and templates passed without weakening that guard. Sanitizing the CLI environment
initially removed Windows system paths needed to discover Compose; restored only
system path keys. The first image build was deliberately stopped after detecting
overly broad parent-directory exceptions in Docker ignore rules. Corrected rules
were tested using fictional inputs before rebuilding. An initial container test
failed because the ignored .dockerignore was absent from the image; it is now an
explicit reviewed input. Internal-only web networking gave healthy containers
but refused host HTTP; the separate scoped ingress network resolved it.

The first DB-stop probe exceeded its HTTP request deadline; a subsequent run
passed. Resolver attempts/timeouts are now bounded through RES_OPTIONS, with a
five-second HTTP deadline and classified timeout/connection failures. The final
outage run passed after that change. This does not prove every network failure
mode. A host Python YAML probe was unavailable; actionlint supplied the actual
workflow validation instead.

Preservation verification compared all 282 starting tracked/untracked inputs:
274 byte-identical, eight intended existing-file changes, zero missing or
unexpected changes. The eight are .gitattributes, PHP includes/environment.php,
README, CONTRIBUTIONS, SECURITY_EXPORT, CURRENT_ARCHITECTURE, STATUS and DECISIONS.
All existing ZL-12 source/test/export changes and historical checksum files remain.
HEAD/tree/branch/all refs and the empty index match the starting snapshot. The
prior contribution log remains an exact byte prefix. No Git staging, commit,
branch mutation, history rewrite, push or PR creation occurred.
All 58 inspected local documentation links resolve; Markdown fences and Git
working-diff whitespace checks pass. This is document validation, not application
behavior verification.

The aborted build's local BuildKit cache is not inspected or pruned. No image is
pushed. The final exact image inventory proves the final application image's
file boundary, not historical cache removal. Secret scanning is bounded to
reviewed text and cannot prove absence of all secret/PII patterns; private stores,
Git history, binary assets and Docker's own local metadata are outside its scan.

## Rollback and handoff

Stop each initialized profile with `down <profile>`; keep its volume and local
configuration. Stop using these new scripts/workflow if necessary. For a source
rollback, review only the ZL-13 diff: the sole application edit is the explicit
local `db` hostname exception in includes/environment.php. Restore that original
loopback condition manually if required; preserve every ZL-12 change and all
prior recovery records. Do not run Git reset/clean/stash or delete source/data.

Next owner-authorized task: **ZL-14 — Reconcile canonical SQL schema and reliable
database access.** Begin from this current tree and preserved records; verify
historical schema contracts, design reviewed local migrations/fixtures and then
replace the readiness hold with real application assertions. ZL-14 has not begun.


## Subsequent ZL-14 extension (2026-10-23)

The ZL-13 results above remain historical. Current reviewed input count is 204;
the only SQL build inputs are four new SHA-checked CREATE migrations for
site_settings, rate_limits, movie_parts and episode_parts. No historical/private
SQL dump is included. The current regression suite has 145 tests.

After building CI, `node scripts/database-test.cjs ci` creates a verified new
tmpfs-only synthetic server, runs 42 migration/integrity/target/locking checks,
preserved PHP/offline Node compatibility probes, encrypted restore and a bounded
outage/recovery test, then removes its owned temporary resources. It does not
migrate/reset any managed environment volume or create a complete schema.

The final runner's connection/read timeout is scoped to its own CLI construction
and restored afterwards; legacy application configuration is unchanged. Runtime
settings/rate-limiter DDL and missing PDO route bootstrap remain blockers. See
[ZL-14 execution results](../database/SCHEMA_TEST_RESULTS.md) before interpreting
any passing subset check as application readiness. GitHub CI is still unverified.
