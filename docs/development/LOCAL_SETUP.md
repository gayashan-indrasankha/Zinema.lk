# Local development — ZL-13

The extracted workspace and its uncommitted changes are authoritative. This setup
builds a snapshot of reviewed current files; it never restores an archive or
mounts the repository into a container. **ZL-13 is PARTIAL:** a verified application
schema is still required under ZL-14. A healthy process is not a working catalogue,
login flow or player. See [operations and executed checks](ENVIRONMENT_OPERATIONS.md).

## Requirements and runtime choice

- Docker Desktop running **Linux containers**, a local daemon and Docker Compose.
  Windows PowerShell and VS Code are supported by the Node helper.
- Host Node.js 24 LTS; the image and CI runner use 24.22.1. No global npm packages.
- Free loopback port 8080. Staging uses 8081; CI uses 8082.
- Internet access for initial pinned-image pulls and Debian build dependencies.
  MySQL uses an internal network; web also has a scoped ingress network with egress.
  No provider account is required.
- Optional host PHP for parser tests: set `PHP_BINARY` to its executable. Container
  tests include PHP, Node and the required reviewed files.

PHP 8.2 was selected to retain the existing 8.2 parser baseline. The image pins
8.2.35/Apache Bookworm by digest, adds MySQLi, PDO MySQL, OPcache and cURL, and
retains Apache rewrite rules. PHP 8.2 reaches the end of security support on
2026-12-31; plan an upgrade with compatibility tests before production.
[PHP support policy](https://www.php.net/supported-versions.php),
[8.2.35 release](https://www.php.net/releases/8_2_35.php),
[Node release policy](https://nodejs.org/en/about/previous-releases).

MySQL 8.4.13 LTS is pinned by digest. Its server startup and both PHP drivers must
be tested independently from legacy SQL compatibility. The PHP base image and
Node base are immutable; Debian package resolution during the extension build
still depends on repository state. This is not a bit-for-bit hermetic build.

## Start from the repository root

```powershell
node scripts/environment.cjs doctor
node scripts/environment.cjs init development
node scripts/environment.cjs config development
node scripts/environment.cjs secrets
node scripts/environment.cjs up development
node scripts/environment.cjs health development
node scripts/environment.cjs audit development
node scripts/environment.cjs test development
```

`init` creates only `infra/environments/development/.env`, with independent random
local application/root passwords. Repeating it preserves the file. Existing root
or legacy `.env` files are never loaded. Templates contain fictional values.
Generated local values stay ignored and outside the image context; never publish
them. Windows ACLs remain the owner's responsibility; POSIX mode 0600 is requested
where supported. Docker administrators can inspect runtime environment variables.

Use `http://127.0.0.1:8080/__health/live` initially. Dependency health is at
`/__health/dependencies`; `ready` deliberately reports 503/schema_unverified.
Do not run legacy migration/admin endpoints or load ordinary DB-backed pages as
an initialization procedure. Some legacy bootstrap paths attempt DDL. The local
application account has DML privileges only, and no application tables are seeded.

## Daily work

Images contain a source snapshot. After editing a reviewed file, run `up` again to
rebuild. New files require explicit review into `infra/environment-files.json`
and regeneration of `.dockerignore` using `dockerIgnore()` from the helper.
Do not broaden exceptions to whole directories. PHP helper modules from ZL-12
remain outside the document root and retain their existing shims.

```powershell
node scripts/environment.cjs status development
node scripts/environment.cjs logs development
node scripts/environment.cjs down development
```

`logs` reports bounded counts rather than raw content. Normal shutdown retains
the database volume. Sessions use tmpfs and disappear on container recreation.
No uploads/media are mounted; the document root is read-only. Persistent uploads,
scheduled jobs, bot/extractor startup and real media tests are outside this setup.

Host-only syntax and synthetic tests, if PHP is installed:

```powershell
$env:PHP_BINARY = 'C:/xampp/php/php.exe'
node scripts/verify.cjs
```

## Troubleshooting

- `RUN_INIT_FIRST`: initialize the selected profile.
- `UNMANAGED_ENV_FILE_PRESERVED`: review the file yourself; the helper will not
  replace an existing unknown configuration.
- `LOCAL_PORT_IN_USE`: stop your own conflicting listener or choose another
  approved profile. The helper never terminates another application.
- `DOCKER_COMMAND_FAILED`: check Docker Desktop/Linux mode, network availability
  and local CLI access. Diagnostic values are withheld. Do not paste raw Compose
  config, `docker inspect` environments or logs into public reports.
- `DOCKERIGNORE_MANIFEST_DRIFT`: review the explicit list and regenerate its
  exact exclusions. Do not bypass the context safety check.
- Dependency 503: use `status`, bounded `logs` and `audit`; inspect only this
  project's local services. Readiness 503 with dependency 200 is the expected
  ZL-14 schema hold, not a reason to import a historical SQL dump.

See [environment matrix](ENVIRONMENT_MATRIX.md), [staging](STAGING_SETUP.md),
[CI](CI_PIPELINE.md), and [operations](ENVIRONMENT_OPERATIONS.md).


## ZL-14 partial-schema extension

Only four source-backed tables now have migrations. Run
`node scripts/database-test.cjs ci` after building the CI image to validate them
on a new disposable server. This never initializes this development volume and
does not make DB-backed application pages ready. The complete schema, runtime
DDL coupling and PDO route wiring remain unresolved. See
[database migration guide](../database/MIGRATION_GUIDE.md) and
[actual results](../database/SCHEMA_TEST_RESULTS.md). No private dump is needed.
