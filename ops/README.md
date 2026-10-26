# Local promotion controls

These tools have no real SDK, remote backup, mail, webhook or deployment adapter.
`promotion-policy.json` is a reviewed configuration candidate, not a release or
permission. It requires every external/production/publishing switch to remain off.
All executable operator paths are restricted to this checkout's ignored `.runtime/`.
No production credentials, historical sessions or real identities are accepted.

Run from the workspace root:

```powershell
node ops/policy.cjs
node --test --test-isolation=none docs/recovery/tests/promotion-controls.test.cjs
python -X utf8 dev/test_staging.py
python -X utf8 dev/rehearse_promotion.py
```

The final command exports the **reviewed Git index tree**. Inspect/stage only
sanitized intended files first; it records both the base commit and exact tree
fingerprint. It uses fresh labelled Docker targets, no published ports/default
route, original demonstration media and different source/restore DB credentials.
It removes only owned containers afterward and preserves all volumes/evidence.

For a manual fictional operator rehearsal, create a private request JSON inside
`.runtime/` and invoke `node ops/local-operator.cjs <absolute-request-file>`. Never
put secret values, QR data or session content in CLI arguments or chat output.
The wrapper emits projected results/stable errors; it never prints input or raw
exceptions. Request files are bounded to 16 KiB. Use an owner-only directory and
separate private 64-hex key files. Real-host filesystem/ACL custody needs review.

## Owner pairing

Use a new empty owned session directory and a reviewer key **outside** it. The
`pairing-prepare` request has `session`, `keyFile` and `review` containing a
`fictional_account_1`-style identity, `demo_` account reference, `dedicatedAccount:
true` and optional 30–300 second TTL. It writes a fictional challenge privately;
no browser, terminal QR renderer, SDK or network connection starts.

`pairing-review` returns only identity/challenge fingerprints, revision and expiry.
For `pairing-approve`, explicitly review the private challenge and supply the
matching identity/revision/challengeRef, `reviewed: true`, `dedicatedAccount: true`,
`demo_` actor and `fixture:` evidence reference. The signed local receipt records
the review. Expiry, changed challenge/identity, stale review, occupied session,
different actor replay or worker lease fails closed. An interrupted identical
review can finish; existing credentials are never overwritten or deleted.

The resulting credentials/receipt are explicitly **local-fake** and cannot start
the delivery worker. A normal worker rejects fresh/fake credentials before opening
a socket. Registered credentials additionally require a separate signed review
receipt, `PAIRING_REVIEW_KEY_FILE` outside the session, and a matching
`BOT_PAIRING_ACCOUNT_REF=test_...`. The receipt binds the normalized SDK identity,
dedicated-test-account scope and at most a one-hour window. Expiry stops jobs and
the worker, including across reconnects. The existing production/live startup
guards still run first; receipt possession does not bypass them.

The fake CLI does not issue `baileys-reviewed` provider receipts. The real SDK
pairing adapter/receipt issuer must be reviewed against the exact approved account
and test window before any real connection. No live key/session was created or
rotated in this phase. Renewing test-window receipts is an explicit owner operation,
not an automatic continuation or production authorization.

## Held maintenance

`maintenance-review` takes an owned `session` and returns checkpoint revision,
opaque item reference and remaining count. It acquires the same lease as the worker;
stale/occupied leases remain untouched. An unidentified legacy group checkpoint
stays held rather than guessing which work completed.

`maintenance-resolve` additionally requires `review` with the exact revision,
fresh 32-hex operationId, `reviewed: true`, `workersStopped: true`, `demo_` actor,
`fixture:` evidence reference and outcome `verified_completed` or `withdraw`.
Completion requires exact item/revision metadata evidence; the local CLI accepts
only a private `metadataEvidenceFile` marked `fixture: true`. This is fictional,
owner-reviewed evidence, not independent verification of a live acknowledgement.

Withdrawal permanently excludes that maintenance identity from subsequent force
warming/group scans. It does not requeue, retransmit, delete the original or resolve
a recipient claim. Completion advances one identified item; metadata evidence and
the complete original checkpoint remain in the private immutable audit record.

A durable intent precedes exclusion/checkpoint updates. While it exists, maintenance
stays blocked. After interruption, `maintenance-recover` requires matching
operationId and `reviewed: true` to finish the exact transaction. Unknown/corrupt/
changed evidence stays held. No rollback, unwithdraw, stale-lock removal, queue
purge or arbitrary sending action is exposed. These are cooperative process/file
guarantees; power-loss/hostile-root behavior is not certified.

## Extractor and storage

The promotion fixture uses `/run/zinema/extractor.sock`, mode 0660 in an owned 0770
directory, with UID 33 and an independent server key. It has no network route or
published port. PHP uses cURL's Unix socket option, no proxy/redirect, and the
existing bounded body/deadline/response rules. Administrator POST/CSRF/role checks
remain mandatory. `private-fixture` requires production configuration, exact local
origin, explicit fixture flag and live integrations false. It returns only original
managed fixture media; provider and browser adapters remain disabled.

`UPLOAD_QUOTA_MODE=staging` enables the existing quota/reference/ownership workflow
only for explicit promotion fixtures: production PHP, exact loopback origin and
fictional DB/port, owner marker, separate state volume and live integrations false.
The rehearsal verifies byte/file rejection without changing manifest bytes. Normal
development keeps its original `local` mode; unknown quota modes fail closed.
Production candidate budgets (32 GiB/10,000 files, 20 MiB website upload limit and
4 GiB quarantine) are examples for owner review on selected hardware. Bot media
masters remain separate; this does not provide distributed-host storage locking.

## Streaming recovery and monitoring

`backup-encrypt` requires explicit `writersQuiesced: true`; `backup-decrypt` and
`backup-mirror` use private request paths and a key stored separately from input/
output directories. AES-256-GCM streams with fresh nonces, authenticates the header,
refuses overwrite/link/path escapes and detects changed source metadata. Restore
publishes only after authentication; failed private partial plaintext is removed.
The default 10 GiB per-file limit is configurable in the module, bounded to 1 TiB.
No object store/SSH/upload or backup deletion/retention purge is implemented.

The rehearsal streams the owned SQL dump and media tar to private files, writes
ZIP64 incrementally, encrypts/mirrors it and restores SQL/tar via streaming stdin.
It validates archive allowlists/types and compares all rows/files on a new target
before new writes. The separate local directory proves mirror integrity, **not
off-host disaster recovery**. Real off-host/key custody, full disk, lost-machine,
actual SDK session and power-loss drills remain acceptance gates.

`monitor` accepts only bounded booleans/counters, never URLs, tokens, phones, free
text or diagnostics. Fixed alert/recovery codes cover website/DB, registration,
heartbeat, queue age, held maintenance, disk, backup age and errors. State/outbox
publication is atomic; unchanged conditions do not repeat alerts across restarts.
An outbox at capacity fails explicitly without dropping old alerts. There is no
external alert sender or acknowledgement/deletion action. Scheduling/routing,
actual worker/disk metric collection and on-call response await the chosen host.

See `../docs/recovery/PROMOTION_CONTROLS.md` for executed results and remaining gates.
