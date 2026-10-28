# Isolated local staging rehearsal — 2026-10-26

Started clean at bot checkpoint 0fb8566. Authorized: prepare an allowlisted
artifact and test synthetic-data recovery, local TLS/security, monitoring and
bounded load. Hosting is undecided. No real host, DNS, public listener, provider,
recipient, production data, deployment, payment or publishing is authorized.

Plan before execution:

1. Export committed application files into a dedicated artifact. Exclude dev/test
   aliases, diagnostics, fixtures, dumps, secrets, private media and Git material.
   Keep schema/backend sources outside the public document root. Never deploy the
   existing development image as production.
2. Create fresh labelled local source/restore databases and volumes with fictional
   catalogue records and original media. Use private file credentials and no
   default route or published ports. Do not import the existing development DB.
3. Serve the artifact through a loopback-only TLS proxy using an explicit temporary
   test CA; never alter OS/browser trust stores or bypass browser warnings. Exercise
   production configuration with every provider disabled against only the named
   local fixture DB. Verify CRUD-only runtime privileges and negative paths.
4. Measure a bounded 5/25-reader rehearsal on synthetic records with explicit
   resource limits; record errors/percentiles without claiming production capacity.
   Detect a controlled local website outage and retain a local alert result.
5. Stop writers, snapshot database/media/private state, authenticate/encrypt a local
   backup with a separately stored fixture key, reject tampering, and restore into
   another fresh isolated target. Compare row/file fingerprints before resumed
   writes. Retain all fixture volumes/evidence; stop only labelled owned containers.

Actual results, failure history and operational limits follow after execution.
Real staging TLS/DNS, off-host key escrow/backups/alerts, large media, real SDK/
pairing, held-maintenance review and approved provider tests remain release gates.

## Reproduce without a host or provider

Run from the workspace root with a local Docker Desktop context, Python, Node and
the existing XAMPP OpenSSL installation:

```powershell
python -X utf8 dev/test_staging.py
node --test docs/recovery/tests/backup-crypto.test.cjs
python -X utf8 dev/rehearse_staging.py
```

The launcher refuses remote Docker contexts and creates a new random labelled
source/restore case under ignored `.runtime/staging-rehearsals/`. It never starts
or imports the existing development database. Containers have no published ports;
the database namespace has no default route, shared only by its application, TLS
proxy and explicit test clients. Resource limits are database 384 MiB/1 CPU,
application 256 MiB/1 CPU and proxy 64 MiB/0.5 CPU. TLS runs as UID 101, read-only,
with capabilities dropped and writable temporary directories only in tmpfs.

The public image receives only the committed allowlist, not the working directory
or development image. Database, backend source and attribution remain outside its
document root. The artifact manifest identifies the source commit and file hashes;
the rehearsal configuration is separately hashed. This is a local rehearsal
artifact, not an approved release. The entrypoint rejects other origins/databases
and requires every provider disabled even though APP_ENV=production is exercised.

Private secret files, the two-day CA/leaf keys, SQL, archive, row/file fingerprints,
container diagnostics and encrypted backup remain ignored. No CA is added to any
trust store. Test clients explicitly trust the local CA; default trust rejects it.
Do not print/copy the private payload or `expected.json`: it includes session file
names and token-table fingerprints. Keep the backup key separately from the archive.
All fixture volumes and failed-run evidence are retained. Only containers with the
exact generated ownership labels are stopped/removed. No volume purge is provided.

## Executed evidence and limits

STAGING_REHEARSAL_RESULTS.json records the executed source/restore HTTPS tests,
23 orchestration checks, load measurements, outage alert, backup authentication
and full row/file comparisons. The expanded dataset has 251 movies, 41 series,
201 episodes, 21 collections and 11 trailers with realistic description sizes;
it uses shared original tiny media, not representative movie-sized payloads.
Source and restore each passed their administrator login/CSRF/guest, secure-cookie,
Host/proxy, private-path, disabled-provider and least-privilege checks. A forged
forwarding-header sign-in sequence hit the shared 429 admission limit after restore.
No new PHP warnings/notices/fatal errors/deprecations were found in captured logs.

The source application is stopped before the database/media/state snapshot, leaving
the local TLS proxy up to verify an unavailable alert. The new target starts from
the canonical schema and a distinct DB credential. All 38 tables, 2 views, 538 row
fingerprints and every one of the 408 files matched before resumed writes in the
first successful rehearsal. Most files are fictional anonymous PHP sessions from
load requests; this is not 408 media masters. Restored owned storage then admitted
another original image. Final counts/measurements are in the JSON result.

AES-256-GCM encrypts an authenticated ZIP containing schema, data-only SQL, an
allowlisted regular-file tar and private verification fingerprints. Tampering
produced no plaintext output. The helper also tests wrong keys, no overwrite,
invalid format/key, private-path boundaries and oversized input. It is a bounded
**64 MiB in-memory fixture helper**, not a production large-media backup solution.
Snapshots preserve fictional session/worker-placeholder bytes; they do not certify
Baileys credential recovery, in-flight quarantine/maintenance recovery or production
session-invalidating policy. Actual worker fake restart tests are in BOT_LIFECYCLE.md.

The 5/25-client phases make 100/300 TLS read requests, respectively. Report p50/p95/
p99/errors and after-load CPU/memory/I/O samples. These brief local measurements
have no sustained-load, peak-memory, recipient/SDK, write-heavy, large-media or
production-host capacity claim. Local healthy/unavailable alerts are runtime
verified; off-host routing, queue/disk alerts, retention and on-call response remain
unverified. No provider outage was induced.

Artifact/archive/Windows shell-checkout boundary unit tests: 5/5. Retained pure/fake Node regressions plus
new backup case: 103/103; startup guards 9/9. The unchanged extractor service has
its earlier explicit-dependency 6/6 evidence. Native HTTP/schema and Docker product/
OAuth results are retained from the immediately preceding application checkpoints;
this phase adds deployment templates/harnesses, not application changes.

Failure history is retained privately. The first run stopped before any DB because
OpenSSL's default config path was unavailable; use the installed config explicitly.
Two TLS attempts then stopped on read-only Nginx default temp directories; all temp
paths now use tmpfs. The initial broad Node selection passed 103 assertions but
failed one extra service-suite setup because its required isolated dependency path
was not supplied; rerunning the prior suite selection plus backup passed 103/103.
One PowerShell invocation entered Python without executing tests; it was corrected
and is not counted as a pass. No failing run was used as readiness evidence.

Remaining production gates: selected private staging host/TLS/DNS, supported patched
runtime/image assessment, durable production quota/storage and streaming backups,
off-host keys/restore/alerts, supervised actual SDK pairing/reconnect/large media,
reviewed held-maintenance handling, real extractor promotion policy and capped
owner-approved provider tests. Production CSP still uses inline script/style
allowances and needs actual browser/asset review. No publishing is authorized.
