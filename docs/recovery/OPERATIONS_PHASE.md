# Local operator and upload-storage phase

Date: 2026-10-25. State: IN PROGRESS. Starting tree clean on
`zinema/local-dev-safety`, head `b9dead6`. This is the owner's explicitly requested
follow-up to the completed local development phase, not a production/publishing phase.

## Intended behavior, before implementation

- The operator works through a development-only CLI, outside the web document root.
  Verify the exact owned local database, storage marker and disabled production
  adapters before inspecting or changing state. Do not expose token strings,
  receipts, recipient hashes, credentials or raw error diagnostics.
- List/review held claims with an integer revision. After stopping workers and
  reviewing fictional delivery evidence, explicitly record delivered or not-delivered.
  Lock the token and atomically write an append-only audit row; reject stale reviews,
  conflicting decisions, young claims and missing acknowledgements. An identical
  repeat is idempotent. Both outcomes permanently close the old claim; neither
  retries delivery, releases it for reuse nor issues a replacement token.
- The audit is an operator attestation, not external proof. Waiting does not prove
  non-delivery. Uncertain facts stay held. Real WhatsApp and provider access remain
  disabled; no exactly-once guarantee is introduced.
- Enforce development upload byte and file limits under one filesystem lock. Count
  retained/unregistered physical files too. Keep an ownership marker and manifest
  outside the public upload directory; fail closed on corrupt state or unsafe links.
  Never adopt old files just because their names look generated.
- Provide a read-only inventory of known media reference columns and managed files.
  Referenced/shared, young, changed, missing, linked and unknown assets are held.
  Only registered, unchanged, old, unreferenced files become review candidates.
  The inventory is advisory; there is no deletion/apply command in this phase.
- Test with separate fictional fixture storage and fake SQL delivery claims, including
  simultaneous operators, stale workers, quota races, shared/encoded references,
  provenance/tamper handling and boundary guards. Retain prior regression assertions.
  Record executed results and limitations, preserve all runtime data, and stop owned
  services. No production connections, remote Git actions or publishing.

Implementation, executed evidence, security review and handoff follow below.

Additional statically inspected defect: `cleanupExpiredTokens()` and the quarantined
cron's SQL do not exclude claimed tokens, so expiry alone could erase unresolved
delivery evidence if those routines were called. Preserve every claimed/reconciled
token in these cleanup predicates; keep the cron disabled. Exercise the utility
against a connection-local temporary table containing only new fictional rows,
without deleting anything from the existing development token table.

## Implemented changes

- `dev/operator.php`, `dev/operations.php` and `dev/local.py operator` implement
  guarded list/review/resolve and advisory inventory commands. No operator HTTP
  endpoint is installed. Numeric IDs/revisions, fictional actor/evidence references,
  strict request keys and explicit stopped-worker acknowledgement constrain decisions.
- Canonical schema v3 and the additive `003-local-operations.sql` add claim revisions,
  reconciliation time and one append-only-through-tool audit per token. Row locking
  makes decision/audit atomic. The bot bridge advances revisions and refuses closed
  claims. Both outcomes permanently close the old claim without release or resend.
- `includes/upload_storage.php` and the existing validated-upload helper enforce
  physical-byte/file quotas with one lock, exclusive creation and private provenance.
  The default is 64 MiB/500 files. An over-quota replacement returns 507 before its
  catalogue write and preserves old media. Corrupt ownership/manifest or unsafe links
  fail closed; retained/unknown files still consume quota. State is a separate volume.
- Read-only inventory covers persisted media/path columns, excludes SQL views to
  avoid duplicate derived references, decodes one URL/HTML layer and bounds reference
  reads/traversal. It distinguishes referenced/shared/young/changed/missing/unknown/
  unsafe files from old unreferenced managed review candidates. No cleanup mutation
  command exists. Original/legacy media is never adopted by filename appearance.
- The optional expiry utility and still-disabled cron predicates retain all claimed/
  reconciled evidence. The regression invokes the utility only on a temporary table
  shadowing the real name on one verified fictional-fixture connection.
- The launcher now rejects remote Docker contexts/host overrides, pins a local
  socket/named-pipe context, and supplies an explicit empty Compose environment file.
  Preserved `.env` files are not loaded for interpolation. Public configuration,
  dependency locks and production-adapter defaults retain their prior restrictions.
- Separate fixture media/state volumes and a non-published loopback fixture server
  isolate quota/tamper tests. Existing development configuration, data, media and
  all earlier recovery reports remain preserved. Native result prefix `OPERATIONS`
  avoids overwriting historical repair or development evidence.

## Executed verification

| Executed check | Result | Evidence classification |
| --- | --- | --- |
| Operator/quota/inventory regression suite | 57/57 passed | runtime verified, fictional fixtures |
| Direct CLI list/review/acknowledge/resolve/audit/replay/inventory | 7/7 passed | manual runtime verified |
| Docker context/env-file boundary unit cases | 7/7 passed | test-covered and executed; remote metadata simulated |
| Retained fake mail/OAuth/upload/claim HTTP suite | 88/88 passed | runtime verified in guarded Docker |
| OAuth state/PKCE helper suite | 7/7 passed | test-covered and executed |
| Owned development exposure/isolation/storage-marker checks | 20/20 passed | runtime verified |
| Fresh canonical schema and SQL bridge (MariaDB 10.4) | 22/22 passed; 38 tables, 2 views, zero legacy rows | runtime verified |
| Retained Windows/XAMPP HTTP suite | 94/94 passed; no new PHP diagnostics | runtime verified |
| Retained fake-client bot/configuration suites | 12/12 and 10/10 passed | test-covered and executed |
| Token delivery/API-client protocol suite | 6/6 passed | test-covered and executed; local fake socket only |
| Extractor disabled/local-fixture requests | 4/4 passed | runtime verified |
| Scoped npm audit: bot lockfile / locked extractor HTTP subset | Exit 0 each; zero advisories returned | scoped dependency evidence |
| PHP / JavaScript syntax | 182/182 and 41/41 passed | executed static verification |
| Python AST / JSON parsing | 10 / 37 files passed | executed static verification |

The current development database was upgraded additively and initialized repeatedly;
the fresh native database validates the complete canonical v3 creation file. This
phase does not claim a new fresh-Docker-source-export run; that was performed for
v2 in the prior phase. Current guarded Docker startup and upgraded-volume regressions
were executed successfully. Reports use `OPERATIONS_*`; debug logs, credentials,
manifest data and raw CLI/HTTP responses remain private under ignored `.runtime/`.

Failure investigation is retained explicitly. The first operations execution was
47/48: the hard-link fixture tried to span separate named-volume filesystems, so
it created only a symlink. Metadata confirmed the application retained files and
blocked writes; the fixture was corrected to create its hard link on one volume,
without weakening the assertion. That run then passed 48/48. The expanded cleanup
case initially produced 49/50 because MariaDB rejected a same-name temporary-table
LIKE source (1066) before cleanup ran. An explicit minimal temporary fixture table
fixed the test setup. The expanded 51-case run and 52-case runs all passed. Final review serialized first initialization with the same
admission lock and added a concurrent-initialization preservation check; the final
53-case operations, 88-case integration and 20 environment checks passed again.
Missing-manifest fail-closed handling and owned-gateway preflight added four more
assertions; the final 57-case suite and retained 88/20 checks passed.
These were investigated fixture failures, not silently omitted checks.

The final CLI/environment/gateway guard uses seven separate boundary cases with mocked
client context metadata. The real local context/configuration and guarded startup
were executed afterward. No remote daemon was contacted by these negative tests.

## Limits, preservation and next task

Operator evidence is a fictional attestation, not externally verified delivery.
Age does not prove workers stopped or that a recipient did not receive anything.
The shared worker key and trusted Docker/database/filesystem access remain security
boundaries. An existing external send cannot be cancelled and exactly-once delivery
is not established. Audit rows are append-only through this tool; database admins
can still alter them directly. No real WhatsApp/mail/OAuth/payment was contacted.

Quota accounting is per owned local volume, not distributed storage or temporary
processing-space accounting. Filesystem/catalogue writes are separate and can leave
unknown/orphan files after a crash. Manifest hashes detect drift, not a malicious
trusted editor. Inventory is an advisory snapshot over the recorded reference
columns and limited decoding rules. It cannot prove the absence of future or
arbitrary embedded/remote references, or certify codec/malware safety. There is no
media deletion/apply feature, antivirus or container vulnerability scan in this phase.

Only generated partial/staging files, fixture-only links and one explicitly generated
missing-file fixture were removed. No existing application/legacy media or database
record was deleted by the operator/inventory tools or expiry-cleanup test. Named
volumes, private configuration and recovery evidence were retained. Docker, native
Apache/MySQL, bridge and extractor test processes were stopped at verification end.
Production integrations, remote Git operations and publishing remain disabled.
Historical publication whitespace findings remain unresolved and are not relabeled.

Exact next recommended task: **Add recoverable quarantine and explicitly reviewed
cleanup for owned development uploads, with audit, restore and concurrency tests;
keep production integrations and publishing disabled.**

Staged review, secret scanning and local commit details follow in the handoff.

A later verification attempt was an orchestration failure: the owned XAMPP fixture
was still on 8088 when Docker tried to publish it. Startup returned 1; subsequent
attempted harnesses failed before feature checks (2/3 and 0/1). That setup evidence
was preserved privately. The partial Docker stack was stopped, final source was
refreshed into the owned native fixture, 22 schema/bridge and 94 HTTP checks passed,
and native services were stopped. Docker was then started with explicit fail-fast
sequencing and the final 57/88/20 checks passed. The test driver now verifies the
running, correctly labelled loopback gateway before transmitting any fixture key.
No volume, legacy media or existing database record was removed by this correction.

Final static verification passed for all 182 PHP and 41 JavaScript files, 10
Python files and 37 JSON files. This follows the final source edits.

## Local security and handoff review

The work started from a clean `b9dead6` tree on `zinema/local-dev-safety`; no user
changes, historical results or earlier commits were reset. The public candidate
contains 363 files. `.gitignore` was reviewed unchanged: credentials, manifests,
sessions, runtime databases/media, dependencies, debug logs and preserved private
legacy evidence remain excluded. Eight private-path ignore sentinels passed and
no forbidden data path was indexed. Generated fixture media stays in Docker volumes;
no new media binary or personal identity is included in this phase.

Gitleaks 8.30.1 was available; its archive checksum matches the saved upstream
record. Redacted staged, full-candidate and all-local-history scans returned exit 0
with zero findings, with default rules, empty suppressions and inline allows ignored.
Final updated candidates are scanned again before commit. The scanner cannot prove
absence of every secret; ignored private/legacy evidence is preserved and not
certified safe. Two scoped npm audits returned zero advisories; the full extractor,
old dependency trees, containers and malware/codec safety remain outside those scopes.
New staged whitespace checks pass; historical publication whitespace remains partial.

Only local commits are prepared. No remote Git operation, publishing, deployment,
production infrastructure connection or credential rotation was performed. Final
commit hash, clean status and closed-service verification are recorded at handoff.
