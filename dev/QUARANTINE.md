# Reviewed development-upload quarantine

Only the owned Docker development target is supported. Production integrations,
remote services and publishing stay disabled. The CLI lives outside the document
root; `/dev` remains denied. Use fictional `demo_` actors and `fixture:` references.

```powershell
python dev/local.py up
python dev/local.py operator inventory
python dev/local.py operator storage-review --path uploads/REGISTERED_FILENAME.png
```

Replace the placeholder with a registered path from inventory. Review ownership,
reference coverage, age, hashes, active/private usage and the numeric `revision`.
Only unchanged, unlinked, unreferenced owned files older than 24 hours qualify.
Unknown, legacy, shared, changed, missing and young media stays held. A generated
filename alone is never ownership evidence. Inventory is advisory and read-only.

After reviewing the facts, supply the exact revision and a unique case:

```powershell
python dev/local.py operator storage-quarantine --path uploads/REGISTERED_FILENAME.png --revision 1 --actor demo_operator --evidence-ref fixture:review-example --case-ref fixture:quarantine-example --reviewed
python dev/local.py operator storage-history --path uploads/REGISTERED_FILENAME.png
python dev/local.py operator storage-review --path uploads/REGISTERED_FILENAME.png
```

The example revision is illustrative. Cleanup copies and verifies the registered
bytes into private `/var/lib/zinema/storage/quarantine`, journals the copy, rechecks
references and file identity, then removes the public source. It frees **public
quota**, not total disk space. Private archives have their own 64 MiB/500-file
limits. There is **no permanent purge command**; the last rescue copy is preserved.

Restore uses a fresh review, fresh case and current revision:

```powershell
python dev/local.py operator storage-restore --path uploads/REGISTERED_FILENAME.png --revision 4 --actor demo_operator --evidence-ref fixture:restore-review --case-ref fixture:restore-example --reviewed
```

Restore checks the private hash and public quota, publishes exclusively at the
original path, gives the file a fresh grace period and keeps the private archive.
A collision or changed archive is held. Existing content is never overwritten.
Repeating the identical case returns its outcome; conflicting reuse fails. Stale
revisions require review again. Cases are scoped to the private volume manifest.

## Interrupted operations

`cleanup_pending_review` means intent/checkpoints are persisted and new upload
admission is paused. Review `storage-history` and `storage-review`; retain all
files. Do not clear the manifest or remove parts manually. Record a fresh reviewed
recovery case, using the pending operation ID, current revision and either
`finish` or `rollback`:

```powershell
python dev/local.py operator storage-recover --path uploads/REGISTERED_FILENAME.png --revision 3 --operation-id OPERATION_ID --decision finish --actor demo_operator --evidence-ref fixture:interruption-review --case-ref fixture:recovery-example --reviewed
```

An identical recovery case may be retried. A chosen recovery decision is retained;
conflicting recovery cases fail. Finish rechecks references. Rollback of quarantine
keeps or restores its public copy; rollback of restore is permitted only before
publication. If a restore already published, finish it. Unsafe/colliding/changed
files remain held for investigation. Do not delete ambiguous copies to unblock it.

The private atomic manifest contains operation checkpoints, case signatures and
append-only-through-tool audit with actor, evidence, case, revision and time.
It is bounded (1,000 operations, 2,000 cases, 6,000 events), not cryptographically
immutable against filesystem administrators. Exhaustion fails closed and needs
a separately reviewed maintenance design; do not erase audit to reset capacity.

## Concurrency and limits

Operator mutations and participating browser/WhatsApp metadata writers take the
development DB advisory reference gate, then the upload volume lock. They recheck
references and reject pending/quarantined paths. Upload admission and restore
share physical quota accounting. All production-capable adapters stay disabled.
Known reference columns and one encoding layer are covered, not arbitrary remote
or embedded references. Direct trusted SQL/filesystem writers bypass this protocol.
Database and two volumes are not one distributed transaction; copy verification
and journaling preserve recovery options but do not establish power-loss durability,
malware safety, distributed quotas or production authorization.

```powershell
python dev/local.py test-quarantine
python dev/local.py test-operations
python dev/local.py test
python dev/verify_environment.py
python dev/local.py down
```

The quarantine suite creates fictional files in separate `quarantine_uploads` and
`quarantine_storage` volumes, uses a non-published fixture listener on loopback
9083, and stops its own fixture container. Failure hooks are fixture-role/CLI only.
Tests cover explicit review, audit, quota, tampering, collisions, reference races,
operator races and interrupted finish/rollback. Volumes and private evidence remain
preserved. `down` stops services without removing volumes; never use `down -v` on
unknown data. Results are private under `.runtime/local-dev/results/`; sanitized
phase reports use `docs/recovery/QUARANTINE_*`.
