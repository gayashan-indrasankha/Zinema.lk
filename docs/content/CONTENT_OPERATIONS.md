# Content operations and recovery - ZL-15

**Test-only operation.** No production UI, real licence ingestion, evidence store,
public publication, job scheduler or distribution adapter is configured. Use only
fictional records and the supplied isolated fixture harness.

## Local checks

From the current workspace, with the documented local Docker Desktop daemon:

```powershell
$env:PHP_BINARY='C:/xampp/php/php.exe' # Or an installed PHP 8.2+ executable.
node scripts/verify.cjs
node scripts/environment.cjs secrets
node scripts/environment.cjs up ci
node scripts/environment.cjs test ci
node scripts/database-test.cjs ci content
node scripts/database-test.cjs ci
node scripts/environment.cjs health ci
node scripts/environment.cjs audit ci
node scripts/environment.cjs down ci
```

`content` mode creates a uniquely labelled ZL-15 network and tmpfs-only MySQL
server with no public port or persistent/bind mount. It verifies zero initial
non-system tables, creates a hashed synthetic marker and checks server UUID and
database identity before migration. All credentials are generated per run and
withheld. The new narrowly privileged service role is separate from the migrator.
No existing profile volume is migrated or reset. Ordinary shutdown retains those
volumes. The fixture harness removes only its own ephemeral resources in finally.

The original ZL-14 mode remains separate and creates its own server. It continues
to test the original four-table subset and backup/restore. A six-version rights
fixture is not a new complete canonical schema, and it is not used as the normal
application database. Readiness must remain 503/schema_unverified.

## Migration profile and forward recovery

`database/content-migrations.json` binds exact LF bytes of versions 0005/0006.
`database/ContentMigrations.php` explicitly combines them with original versions
0001-0004, reusing ZL-14 checksum/target/lock/ledger/drift/recovery behavior. New
tables hold a rights-workflow aggregate and audit, with no FK to missing schemas.
The original manifest and four SQL files remain unchanged. The original loader
still accepts only four tables, and its CLI will refuse unknown version 0005/0006
history if mistakenly pointed at an extended fixture. Use the explicit profile.

`scripts/migrate-content.php` is CLI-only and accepts only the existing strict
marked disposable target contract. Do not paste real environment values or use it
against normal development/production databases. There is no down/reset/DROP CLI.

MySQL DDL is not treated as transactional rollback. A post-CREATE failure retains
the table and records failed; rerun may adopt only an exact matching schema and
checksum. Drift or changed applied history stops execution. Preserve sanitized
diagnostics; do not edit applied checksums, delete ledger rows or grant broader
privileges. Design a forward repair or restore to a new verified disposable target.

## Workflow operations

The service interfaces describe future approved operators, not existing production
accounts. An editor registers draft metadata and references; a distinct permitted
reviewer verifies holder/grant evidence and publication; a publisher selects a
scope. Never put private contract text, contacts or URLs into metadata/audit.
Use an access-controlled evidence system later; its opaque references are not
download links. Existing evidence handling and retention are unresolved ZL-52/55
release gates. Current fixture actors are declared only under tests/content/.

After editing, reload the new version and repeat review. A VERSION_CONFLICT means
another action won; do not retry sensitive approvals automatically. Revocation is
an audited new version and clears publication. Expiry is enforced at access time;
it does not depend on a cron job. Do not claim existing CDN URLs were revoked.

## Rollback and interrupted execution

The new service has no HTTP/admin route integration, so stopping its test harness
leaves the legacy application unchanged. Do not reset Git or overwrite files to
undo a test. Preserve the uncommitted diff for review. Never modify the four old
SQL files or remove original data/volumes as rollback.

If a process is force-killed, first inspect only metadata for exact io.zinema.phase
zl15 and its random io.zinema.run identity. Verify the intended tmpfs mounts, no
ports, matching run/network labels and zero persistent/bind mounts before removing
that run's ephemeral resources. Do not prune Docker broadly or delete named
volumes. Normal finally cleanup is preferred and tested.

Persistent rights backup/restore, encryption-key custody, retention, production
deployment rollback and disaster recovery are NOT IMPLEMENTED by these ephemeral
tests. [ZL-14 backup guidance](../database/BACKUP_RESTORE.md) remains a prerequisite,
not proof of a production rights-data recovery capability.
