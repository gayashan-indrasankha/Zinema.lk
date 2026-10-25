# Zinema.lk

A PHP/MySQL catalogue and media interface with movies, series, collections,
trailers, a vertical Shots feed, account features and an administration area.
A Node.js delivery adapter and extractor are included behind recovery guards.

This is an import of an existing project and its local recovery work. Selected
authentication, CSRF, schema and fake-client delivery regressions have been
repaired and tested; the complete product is not production-ready. Live
WhatsApp, mail, OAuth, payments, downloads and extraction remain restricted.

## Start here

- [Private extractor connection and local tests](docs/recovery/EXTRACTOR_CONNECTION.md)
- [Fresh-launch audit and blockers](docs/recovery/PRODUCTION_AUDIT.md), [feature checklist](docs/recovery/PRODUCTION_FEATURES.md) and [manual evidence](docs/recovery/PRODUCTION_MANUAL.md)
- [Staging requirements](docs/recovery/PRODUCTION_STAGING.md) and [controlled live-test plan](docs/recovery/PRODUCTION_LIVE_TEST_PLAN.md)
- [Project description](docs/recovery/PROJECT_DESCRIPTION.md)
- [Current recovery status](docs/recovery/STATUS.md)
- [Repair scope and executed evidence](docs/recovery/REPAIR_PHASE.md)
- [Canonical schema](database/schema.sql) and [schema reconciliation](docs/recovery/SCHEMA_RECONCILIATION.md)
- [Portable local development](dev/README.md) and [executed local phase](docs/recovery/LOCAL_DEV_PHASE.md)
- [Local operator runbook](dev/OPERATIONS.md) and [operations phase](docs/recovery/OPERATIONS_PHASE.md)
- [Retained test instructions](docs/recovery/tests/README.md)
- [Publication and PR review order](docs/recovery/PUBLISHING.md)
- [Third-party attribution](THIRD_PARTY.md)

## Local checks

With Node.js 24, run these fake-client and configuration regressions from the
project root:

```powershell
node --test --test-isolation=none --test-reporter=tap docs/recovery/tests/config.test.cjs
node --test --test-isolation=none --test-reporter=tap docs/recovery/tests/bot.test.cjs
```

The HTTP/database harness additionally requires the documented Windows XAMPP
binaries. It creates isolated loopback fixtures with fictional accounts and
generated media, and refuses occupied service ports. Follow its README and stop
its owned services after testing. The Docker workflow in [dev/README.md](dev/README.md) provides a separate
portable environment with fake SMTP/OAuth and an internal database.

## Configuration and data

Review .env.example, whatsapp-bot-deploy/.env.example and
b-video-api/.env.example. PHP reads process environment variables; copying a
file to .env does not automatically configure the website. Keep the example
service modes disabled and use only local substitutes in tests. The optional
SUPPORT_WHATSAPP_NUMBER is empty for demonstrations.

Use database/schema.sql only for a new isolated database. Historical migration
files are retained as evidence and are not an upgrade procedure for a populated
database. Original database rows, credentials, uploads, sessions, media libraries
and installed dependencies are excluded from Git. Never serve the workspace's
private recovery directories as a public document root.

## Ownership and history

The initial publication organizes the owner's supplied source into current,
logical import commits; it does not recreate past development activity.
PHPMailer and npm dependencies retain their upstream authorship and licenses.
See THIRD_PARTY.md for scope and remaining license-review limits.
