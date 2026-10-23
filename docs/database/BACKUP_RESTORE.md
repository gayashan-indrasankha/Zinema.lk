# Backup and restore - ZL-14

**Verified scope: a disposable, synthetic four-table database and its migration
ledger. No real database backup or production restore has been performed.**

Before migrating real data in a future authorized phase, inventory all required
tables, views, routines, triggers, schema versions and object/media references.
Define recovery-point/recovery-time objectives, encrypted off-host retention,
access auditing and restore ownership. Partial-schema backups cannot protect a
full Zinema deployment. Infrastructure scheduling/DR belong to later issues.

## Executed synthetic rehearsal

`node scripts/database-test.cjs ci` creates a brand-new tmpfs-only MySQL instance,
verifies its labels/mounts/empty state and installs hashed synthetic-target markers.
Its separate restore database is verified empty except for its own marker.
No current development/staging/CI volume is imported, replaced or reset.

1. Apply the four reviewed migrations; insert deterministic fictional metadata.
2. Produce a logical mysqldump of only site_settings, rate_limits, movie_parts,
   episode_parts and schema_migrations. Use single-transaction, no table locks,
   no tablespaces/triggers/GTID rewrite and skip DROP/CREATE DATABASE statements.
3. Reject a dump containing destructive/global/definer statements. The artifact
   stays in memory; it is never logged or written as plaintext.
4. Encrypt/decrypt with a fresh AES-256-GCM key and nonce in memory. The test key
   is not stored or treated as an operational backup key.
5. Restore into the separate empty marked synthetic database, preserving its
   independent safety marker. Recompute structure plus sorted-row hashes.
6. Require identical source/restored hashes and verify all four ledger versions
   are recognized/skipped by the runner.
7. Remove only the newly created labelled containers/network; existing volumes
   remain. No permanent usable backup artifact is produced by this rehearsal.

The harness uses root only within that new disposable server for bootstrap/dump/
restore, with its password supplied through environment rather than arguments.
New migration/runtime containers do not receive that root password. A future
operational backup identity should have only the privileges its chosen options
require; validate them against the complete approved schema and server settings.

## Future operational procedure (not executed)

- Verify explicit target identity/authorization, schema version, write activity,
  backup completeness and available storage. Never infer a safe target from its
  name. Record sanitized fingerprints and migration checksums, not customer rows.
- Keep backup keys separately controlled, recoverable and access-audited. Use a
  managed key store or approved key custody; test restoration with authorized key
  retrieval. Define rotation/retention without deleting the only usable backup.
- Restrict directory/object ACLs and exclude backups/keys from Git, Docker context,
  CI logs and public export. Protect transfer and storage encryption and integrity.
- Freeze/coordinate schema changes for consistency; InnoDB single-transaction
  backup does not make concurrent DDL safe or cover external media automatically.
- Restore into a **new, explicitly verified empty target**, never over an existing
  volume. Reconcile migration ledger, table/constraint/index metadata, row counts,
  representative queries and application readiness before a separately approved
  cutover. Preserve the old environment until recovery has been validated.

## Migration failure recovery

Do not assume failed MySQL DDL was rolled back. Inspect fixed ledger states and
schema-only metadata privately. On the disposable targets, exact matching steps
can be reconciled; drift/unknown history stops execution. Preserve checksums and
failure records. For real data, use a reviewed forward repair or restore to a new
target from a previously verified backup; neither is authorized automatically by
this task. No reset script, volume deletion, production scheduling, media backup
or cloud disaster recovery is implemented.
