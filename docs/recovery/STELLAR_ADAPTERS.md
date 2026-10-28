# Stellar adapters and worker resource rehearsal

Authorized scope: local implementation and fictional regression tests only.
Existing uncommitted preparation files and publication history are retained.
No account, provider, recipient, production, deployment or publishing access.

## Intended behavior before implementation

- A private PHP bootstrap validates an account-specific configuration before
  injecting application environment. The distributed candidate remains disabled.
  Private staging requires an exact HTTPS origin and explicit client IP access;
  loopback fixtures use an isolated test database and scratch directories.
- Shared hosting runs request-bounded PHP, not an unattached extractor daemon.
  An independently authenticated personal-machine worker polls a bounded private
  extraction queue. Browser requests enqueue and poll using administrator/CSRF
  authorization. Expired or uncertain claims are held, never replayed blindly.
  Fake extraction produces only a fictional same-origin managed clip.
- Account-owned upload state lives outside the document root. Ownership no longer
  assumes root or www-data. Existing quota, quarantine and recovery locks remain.
- The bot checks RSS, available system RAM and media-volume reserve before new
  ingestion or delivery. Streaming ingestion rechecks pressure and discards only
  its own incomplete temporary file on failure. An in-flight send is never retried
  merely because a pressure check or acknowledgement fails.
- Virtual large streams and small real scratch files test limits without filling
  the owner's media volume. Measured local resource use is distinct from actual
  SDK/2 GiB recipient acceptance, network throughput or independent backup proof.

## Executed evidence

**Local runtime verified**, using fictional accounts and a generated clip:

| Suite | Result |
| --- | --- |
| Retained Node plus worker/transport tests | 171/171 |
| Adapter/queue unit contracts | 14/14, including 15 config mutation subcases |
| Disabled candidate, artifact and local/two-host guards | 35/35; candidate has 12 mutation subcases |
| Native PHP/SQL/account CLI/outbound fake-worker rehearsal | 29/29 |
| Retained website HTTP regressions on fresh canonical DB | 94/94 |

343 checks pass; mutation subcases are not counted again.
Static checks pass for 212 PHP, 87 JavaScript/CJS, 28 Python and 99 JSON files.
Gitleaks 8.30.1 (saved upstream checksum and binary verified) scanned 581 source
candidate files with zero findings; 22 private ignore checks and whitespace checks
pass. This excludes generated private credentials/sessions/media and the final
scan-summary file; pattern scanning does not prove absence of all secrets. No
advisory/container/penetration scan or real-host verification was performed.
Schema content matches HEAD after normalizing checkout CRLF to Git LF. The native rehearsal
reports zero new PHP warning/notice/fatal/deprecation diagnostics. It confirms admin
and CSRF separation, independent worker authentication, offline admission, bounded
requests, no incomplete Shot save, verified same-origin results, account-owned
quota state, audited quarantine/restore and exact owned-process/port cleanup.
Queue contracts cover concurrent claims, wrong receipts/owner, stale revisions,
queued/claimed expiry, corruption, capacity and reviewed archive without replay.
Node covers restart intent, lost acknowledgements, shared-state worker exclusion,
offline pressure heartbeat, no new SDK send, midstream pressure and injected ENOSPC.

The final virtual 2 GiB stream ran in a fresh process with a 64 MiB V8 heap limit:
peak RSS **48.68 MiB**, RSS growth **4.90 MiB**, no physical large media file.
The real 64 MiB scratch ingestion, inside the complete Node suite, peaked at
**154.52 MiB RSS**, growth **26.68 MiB**. RSS includes native buffers, unlike heap
usage alone. These are observed samples with fake sinks/senders, not SDK or
personal-machine capacity guarantees. Assertions enforce bounded growth and peaks;
no 2 GiB Buffer, full-volume fill, real media directory or network speed test.

Exact structured evidence: [test results](STELLAR_ADAPTERS_TESTS.json),
[native rehearsal](STELLAR_ADAPTERS_REHEARSAL.json),
[retained HTTP](STELLAR_ADAPTERS_RETAINED_HTTP.json) and
[source/security checks](STELLAR_ADAPTERS_CHECKS.json).
Earlier failed sandbox/network/hard-link attempts and path/fixture-guard corrections
remain in ignored scratch evidence; the final runs pass. Expired queued items now
remain readable held records across subsequent inventory calls. The old Docker
operations/quarantine integration suites were not rerun; their shared algorithms
were exercised through the new native account CLI. Docker was not restarted.

## Reproduce and review

From the checkout root, with existing XAMPP, Node, Python and ffmpeg:

```powershell
python -X utf8 dev/test_stellar_adapters.py
python -X utf8 dev/test_stellar.py
python -X utf8 dev/test_staging.py
python -X utf8 dev/test_local_guard.py
python -X utf8 dev/test_two_host_guard.py
node --test --test-isolation=none docs/recovery/tests/staging-worker.test.cjs
python -X utf8 dev/rehearse_stellar_adapters.py
```

The native script refuses occupied fixture ports, verifies fresh SQL identity,
exports explicit hashed current public-source overlays into a new locked package,
activates only that fictional loopback copy and stops its exact services. The
normal exporter still uses committed website source and no overlays. Neither
export is a release. Runtime credentials, sessions, clip/DB bytes, logs and backup
keys remain ignored/private. Linux private file ownership/modes and Windows ACLs
still require actual-account review. No schema change: canonical v4 remains
38 tables and two invoker views. Source and prior uncommitted files are preserved;
no Git commit, push or public PR was made in this phase.

## Remaining launch gates

- Separately approve and verify actual Stellar PHP handler/.user.ini, exact private
  origin/client access/TLS, account-owned directories/locks, resource/inode limits
  and future approved queue transport. The distributed candidate stays disabled.
- Verify a patched Node/dependency set, sustained realistic memory/CPU/disk load,
  actual media ingestion and real SDK buffering with one approved account/recipient.
  Resource sampling stops new work; it is not an OS memory cap or mid-send SDK
  cancellation. Queue/state flush/rename does not prove power-loss durability on
  the provider filesystem. A stale local worker lock requires reviewed recovery.
- Measure sustained personal-server upload in a separately approved capped test.
  Confirm power/sleep/supervisor/reboot behavior and monitor resource/held-work
  alerts through a verified owner route. No alerts or cron jobs were installed.
- Verify independent encrypted backups, separate key custody and a new-target
  timed restore. The prior local mirror/restore is not independent backup proof.
- Review content/account/provider acceptance and limited live-test approval before
  real extraction or WhatsApp delivery. Publish only after explicit launch approval.

**Local adapter scope is complete; launch remains NO GO.** Actual hosting, upload
speed, independent backups, providers, recipients and publishing remain unverified
or disabled. The next task is evidence collection and an approved private staging
rehearsal, not public deployment.
