# Local operator runbook

The launcher refuses remote Docker contexts and explicit DOCKER_HOST overrides.
Select a local Unix-socket/named-pipe context; it is pinned for the process. Compose
uses the checked-in empty `compose.env`, so preserved `.env` files are not loaded.
Local Docker/client configuration remains a trusted boundary.

These tools operate only inside owned development containers, with the exact local
database, matching storage owner and production adapters disabled. They never send
messages or contact real providers. Operator names (`demo_*`) and evidence references
(`fixture:*`) are deliberately fictional. This is a local demonstration workflow.

```powershell
python dev/local.py up
python dev/local.py operator held
python dev/local.py operator inventory
```

## Held delivery decisions

1. Stop every worker that could still send this delivery. The development stack
   starts no real bot. A timer or failed API response does not establish non-delivery.
2. List held claims. `held --after-id ID` continues after the last returned ID,
   with 100 rows per page. Review one using `operator review --token-id ID`.
   Use numeric IDs; never paste a token string or claim receipt.
3. Independently review the fictional evidence. Uncertain facts stay held. An
   `eligible` claim is unresolved and at least ten minutes old; eligibility does
   not establish delivery, non-delivery or worker quiescence.
4. Use the exact reviewed `claim_revision`, choose the outcome, and explicitly
   attest the stopped-worker/evidence review:

```text
python dev/local.py operator resolve --token-id ID --revision REVISION --outcome delivered --actor demo_operator --evidence-ref fixture:reviewed-ack --workers-stopped
```

`ID` and `REVISION` are placeholders from the review, not sample values to run
blindly. Choose `--outcome not_delivered` only after reviewing evidence of
non-delivery. Invalid argument values are suppressed to avoid echoing an
accidentally pasted bearer token. See `operator --help` for syntax.

Both outcomes permanently close the old token. Delivered marks it used/completed;
not-delivered marks it inactive while retaining claim evidence. Neither outcome
releases the token, reissues one, queues anything or retries a send. Late worker
begin/complete calls are rejected. Any fresh delivery request needs a separate
reviewed action; this tool does not create one.

A transaction locks the token, compares its revision, writes one audit row and
closes it together. Worker transitions invalidate old reviews. Concurrent conflicting
decisions cannot both win. An identical repeat returns the original audit without
another mutation; a different decision is rejected. Review the ID again to inspect
the resulting state and audit. The tool cannot edit/delete audits, and the schema
restricts deletion of their token. Trusted database administrators can still edit
the database directly; this is not a cryptographically immutable audit system.

The shared bot API key, local Docker/filesystem access and operator attestation
remain trust boundaries. There is no distributed exactly-once guarantee or
cancellation of an already-running external request. Real integrations remain
disabled. Expiry cleanup retains claimed/reconciled evidence; the cron stays disabled.

## Upload quota and inventory

The portable development service allows **64 MiB and 500 physical files** by default,
set by `UPLOAD_MAX_BYTES` / `UPLOAD_MAX_FILES` in Compose. Retained and unknown files
count too. Stored output bytes are measured after image rewriting. Existing image/
video validation and individual 5/20 MiB limits remain in force.

One lock serializes capacity checks, exclusive file creation and manifest updates
on the local volume. Quota denial is HTTP 507 and preserves the previous catalogue
path/file. Links or corrupt/mismatched provenance make admission fail closed.
The image entrypoint requires local quota mode. These are per-volume local quotas;
temporary processing space and distributed storage are outside their scope.

The marker, lock and manifest are on the private `/var/lib/zinema/storage` named
volume, outside public uploads. Existing files are counted, but generated-looking
names do not establish ownership. Catalogue SQL and filesystem writes are separate;
a crash or failed catalogue write may leave an orphan/unknown file. It still
consumes quota and needs review.

`operator inventory` reads registered-file hashes and persisted media-reference
columns ending in `url`, `path`, `image`, `file_name` or `shot_video_file`. It skips
derived views and reports covered columns. One URL/HTML encoding layer is decoded;
generated basenames are matched conservatively. Credentials, tokens and contact
columns are excluded. Limits are 10,000 rows per column, 2,048 bytes per value and
bounded traversal. Incomplete/unsafe inspection prevents cleanup candidacy.

| Status | Meaning |
| --- | --- |
| `referenced_hold` / `shared_hold` | One or multiple persisted references; retain. |
| `young_hold` | Unreferenced but under 24 hours old by manifest or file time. |
| `changed_hold` | Registered size/hash differs; preserve and investigate. |
| `missing_or_unsafe` / `linked_hold` | Missing or unsafe registered entry. |
| `unregistered_hold` | Unknown provenance; opaque entry reference, no automatic adoption. |
| `inventory_incomplete_hold` | Reference/filesystem inspection is incomplete or unsafe. |
| `review_candidate` | Registered, unchanged, old and unreferenced within the reported scope. |

This is an advisory snapshot. It does not establish absence of future, arbitrarily
embedded or remote references, certify malware safety or authorize deletion.
Do not turn candidate rows into shell deletion commands. Legacy/shared media and
evidence remain preserved. The separately authorized [quarantine workflow](QUARANTINE.md)
adds explicit revision/case review, recoverable public withdrawal and restore.
There is no permanent purge command. Inventory itself remains read-only and now
also reports `quarantined` / `recovery_required` lifecycle states.

## Tests and shutdown

```powershell
python dev/local.py test
python dev/local.py test-operations
python dev/test_local_guard.py
python dev/verify_environment.py
python dev/local.py down
```

Operations tests use a separate `operator-fixtures` service, upload/state volumes
and owner marker. Its loopback fixture server has no published port. Simulations
are gated by the fixture role. Quota tests never fill/clean the app media volume;
temporary SQL tables isolate cleanup and bounded-reference cases from existing
rows. Only disposable fixture links, an explicitly generated missing-file fixture
and private staging files are removed. The suite stops its fixture container;
`down` stops the remaining services without deleting volumes or configuration.

Private results remain under `.runtime/local-dev/results/`; sanitized phase evidence
uses `docs/recovery/OPERATIONS_*`. Earlier results remain historical. Stop Docker
before running the retained XAMPP suite because both fixtures own port 8088.
