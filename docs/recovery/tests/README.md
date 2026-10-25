# Local repair verification

The original assessment JSON/TAP files preserve the pre-repair failures. Current checks write `REPAIR_RESULTS.json`, `REPAIR_SERVICE_RESULTS.json`, `REPAIR_SCHEMA_RESULTS.json` and repair-prefixed TAP files. Negative security assertions remain; positive writes use valid CSRF tokens and POST deletion/logout.

The recorded environment is Windows XAMPP (PHP 8.2.12, Apache 2.4.58, MariaDB 10.4.32), Python 3.11 and Node 24.13.1. Bot dependencies now require Node >=20. Run from `C:\xampp\htdocs\Zinema.lk`.

## Start and check

In one terminal, start the isolated fixture and keep the launcher alive:

```powershell
python -X utf8 docs/recovery/tests/audit.py prepare --hold
```

Use the **exact runtime path printed by prepare** in a second terminal. The placeholder below must be replaced:

```powershell
$testRuntime = 'C:\xampp\htdocs\Zinema.lk\.runtime\audit-YYYYMMDD-HHMMSS'
python -X utf8 docs/recovery/tests/schema_bridge.py $testRuntime
python -X utf8 docs/recovery/tests/audit.py check $testRuntime
node --test --test-isolation=none docs/recovery/tests/config.test.cjs docs/recovery/tests/bot.test.cjs
```

`schema_bridge.py` rebuilds the final canonical file in another empty database and uses a local PHP SQL adapter with a fake key on port 8089. It does not start a WhatsApp library or contact a recipient. It can be repeated on its fixture; it resets only explicitly named fictional token records.

Install the locked HTTP-only test dependencies into the ignored runtime, then run service checks:

```powershell
$httpDependencies = Join-Path $testRuntime 'extractor-deps'
New-Item -ItemType Directory -Path $httpDependencies -Force
Copy-Item -LiteralPath docs/recovery/tests/extractor-http/package.json -Destination $httpDependencies
Copy-Item -LiteralPath docs/recovery/tests/extractor-http/package-lock.json -Destination $httpDependencies
npm.cmd ci --prefix $httpDependencies --ignore-scripts --no-audit --no-fund
python -X utf8 docs/recovery/tests/services.py $testRuntime
```

For a clean bot dependency smoke check, use a new ignored directory. The previous installed bot dependency tree is not automatically replaced:

```powershell
$botDependencies = Join-Path $testRuntime 'bot-dependencies'
New-Item -ItemType Directory -Path $botDependencies
Copy-Item -LiteralPath whatsapp-bot-deploy/package.json -Destination $botDependencies
Copy-Item -LiteralPath whatsapp-bot-deploy/package-lock.json -Destination $botDependencies
npm.cmd ci --prefix $botDependencies --ignore-scripts --no-audit --no-fund
node docs/recovery/tests/dependency-smoke.cjs $botDependencies
```

Stop the owned services from the second terminal; the holding launcher then exits:

```powershell
python -X utf8 docs/recovery/tests/audit.py stop $testRuntime
```

## Boundaries and interpretation

- Ports 8088, 3307, 3334 and 8089 must be free. Existing or unknown servers are never reused. Sandbox execution may require approved local socket access.
- `prepare` creates a fresh MariaDB data directory, verifies its identity, applies only `database/schema.sql`, seeds fictional accounts and optionally uses installed ffmpeg to generate a test video. It never imports `Database.sql` or loads preserved environment files, sessions or media.
- Apache serves a sanitized code/style mirror, applies the project .htaccess and blocks external browser resources. The main fixture's adapters remain disabled. Only the separate SQL adapter test uses WHATSAPP_MODE=local with a fictional key; all production flags stay false.
- `refresh <runtime>` updates only the owned code mirror after source edits, without changing schema. Rebuild a fixture when schema changes; do not mistake a refresh for a database migration.
- Runtime credentials/logs/sessions remain private under ignored `.runtime/`. An Apache deny file is defense in depth; never expose the workspace root. `stop` checks the exact owned Apache config/process and MariaDB data directory. It is safe if the launcher has already been reaped.
- Likes/favorites toggle and fictional rows accumulate across HTTP runs. Use a fresh fixture for a clean run. Diagnostic assertions inspect new PHP log entries generated during the request suite, not historical resolved failures.
- Commands exit nonzero on failed expectations or advisory errors. Do not weaken negative assertions to obtain green results. Scoped npm audit results do not certify the full extractor, installed old node_modules tree, live integration safety or exploitability.
- No live WhatsApp, real mail/OAuth/payment, production infrastructure, publication or deployment is authorized by these scripts. Manual browser procedures/results and evidence classifications are in `REPAIR_PHASE.md`.

## Current local development phase

The portable Docker/fake mail/OAuth/upload/concurrency suite is documented in
`../../../dev/README.md`. Its source is `dev/integration.py`; it does not start a
real bot. Run `node --test docs/recovery/tests/token-delivery.test.cjs` separately
for the claim/completion protocol and local fake HTTP API-client checks.

For retained Windows checks, set `$env:ZINEMA_TEST_PHASE='LOCAL_DEV'` before the
commands above to write new `LOCAL_DEV_*` records and preserve prior repair
results. The schema bridge now uses claim → begin → complete for positive token
consumption and explicitly asserts the old mark-used endpoint cannot bypass
ownership. Negative expiry, duplicate-use, API-key and browser CSRF assertions
remain. Docker and XAMPP fixtures must run sequentially because both own 8088.
