# Migration execution and recovery - ZL-14

This lightweight runner validates and installs a **four-table partial schema on
explicitly disposable synthetic targets only**. It is not a production migration
tool. It does not modify the preserved ZL-13 development/staging/CI volumes.

## Files and ordering

database/migrations.json lists 0001 through 0004 in deterministic order with exact
file checksums and provenance. Each SQL file contains one reviewed CREATE TABLE
statement. There are no implicit seeds, ALTER conversions, DROP/down migrations,
arbitrary SQL imports or automatic resets. Git attributes enforce LF for these
files so Windows checkout normalization cannot silently change their checksums.

Runner.php loads and validates all files/order/checksums before connecting. It
accepts only the four reviewed tables and a narrow CREATE grammar. Already-applied
checksums cannot change unnoticed. Never edit an applied migration to conceal a
mistake; add a separately reviewed forward migration in a future authorized task.
This owner's four-table scope is intentionally enforced by the current runner.

## Supported execution

First build the reviewed ZL-13 CI image from the current tree:

```powershell
node scripts/environment.cjs init ci
node scripts/environment.cjs config ci
node scripts/environment.cjs up ci
node scripts/environment.cjs test ci
node scripts/database-test.cjs ci
node scripts/environment.cjs down ci
```

The harness creates a new internal network and tmpfs-only MySQL container with
unique labels/credentials. It checks the local daemon, container identity,
unpublished ports, absence of bind/persistent mounts and zero non-system tables
before creating synthetic databases or granting scoped privileges. It does not
reuse an existing volume. Temporary containers/data are removed only after
ownership verification; existing volumes are never removed.

Handled failures run finally cleanup. Forced process termination or a host crash
can leave a labelled temporary run: inspect its exact io.zinema.run ownership,
network and absence of persistent/bind mounts before removing only that run's
resources. Do not use broad cleanup/prune commands or touch another project's
volumes. This is an operational limitation, not a production recovery service.

scripts/migrate.php is CLI-only. The harness invokes it with freshly generated
values kept out of command arguments, images, files and reports. Direct host
invocation without its verified test contract fails closed. Do not copy private
or production configuration into this interface or point it at a normal database.

## Target and privilege boundaries

- APP_ENV=test, DB_HOST=db, explicit local-container opt-in, allowlisted test DB
  names and a dedicated migration username are mandatory.
- Generated local credential syntax, expected server UUID and a run token are
  required. The server/database identity and hashed synthetic-data marker must
  match before any migration/ledger write. Names alone never prove disposability.
- The runtime account retains SELECT/INSERT/UPDATE/DELETE only. A separate
  migration account has SELECT/INSERT/UPDATE/CREATE/ALTER/INDEX scoped to newly
  created synthetic databases. It has no global, DROP or grant privileges.
- Root is used only inside the new isolated test server for controlled bootstrap
  and synthetic backup/restore. Root credentials do not enter the web test image
  or PHP migration container. No external service is contacted.

## Ledger, concurrency and repeatability

An advisory lock keyed by database identity is acquired with zero wait before
ledger mutation and released in finally. A competing migrator exits
MIGRATION_LOCKED. Each step records running before DDL and applied only after
information_schema verification. The runner checks column types, defaults,
nullability, timestamps, collation, indexes and unexpected FK/CHECK constraints.
It refuses out-of-band schema or ledger drift and unknown/changed history.

Reruns skip applied, matching structures without incrementing attempts. A matching
pre-ledger table can be adopted only on the marked synthetic target. The verified
historical upgrade fixture contains the two original part CREATE statements,
independently extracted without token ALTERs, row data or private dump content.
Their synthetic rows survive adoption. Unknown full historical/production states
are unsupported, as are differing collations/types/constraints or optional bot
expansions. Refusal does not authorize automatic conversion.

## Failure and forward recovery

MySQL DDL can implicitly commit: a failed migration is not assumed rolled back.
[MySQL implicit commits](https://dev.mysql.com/doc/refman/8.4/en/implicit-commit.html).
The executed failure fixture raises a real SQL error after CREATE and before its
success record. The table remains and the ledger records failed with a generic
code. On retry, an exact structure/checksum match can be reconciled, then remaining
steps proceed. A running record left by interruption is treated similarly.

If structure differs, stop; do not mark it applied, edit its checksum or erase the
ledger. Preserve the failure evidence, compare schema-only metadata to the
reviewed definition, and design an explicit forward repair or restore into a new
verified disposable target. This runner never guesses how to repair unknown data.
Connection/config errors are generic and omit SQL, hosts, passwords and records.

Production or real-development migration requires approved full DDL, exact schema
fingerprints, impact review, a verified encrypted backup, a separate migration
identity, restore rehearsal and explicit target authorization. None is supplied
or authorized by this partial installer. See [backup/restore](BACKUP_RESTORE.md)
and [actual results](SCHEMA_TEST_RESULTS.md).
