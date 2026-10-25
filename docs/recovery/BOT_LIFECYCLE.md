# Local bot lifecycle follow-up

Started 2026-10-25 from clean extractor checkpoint 2bd6305. The owner authorized
ingestion, scheduler, disconnect/restart, queue recovery and log-redaction checks.
Only fictional local files and fake sockets/APIs/timers are permitted. The actual
entry point must still refuse live startup; no Baileys session, QR, account, group
or recipient is authorized. Overall launch remains NO GO.

Before-change static findings:

- Warming interruption saves remaining files, then unconditional completion cleanup
  removes that state. Corrupt state is treated as absent and can be discarded.
- Repeated scheduler starts can register duplicate jobs; schedules/window depend
  on the host's implicit time zone. Stop cancels jobs but not an active warming loop.
- UploadQueue.clear clears deduplication for active work as well as pending work.
- A reconnect can be scheduled while its previous connect promise is unresolved.
- FileManager accepts unvalidated identifiers/extensions, follows directory links,
  lists non-media files and overwrites existing media masters on save.
- The worker still prints recipient/group/name/path and arbitrary error values.
  There is no explicit graceful shutdown handler.

Intended: retain recoverable checkpoints and stop safely, reject ambiguous/corrupt
state, use explicit Asia/Colombo scheduling, one job per schedule, preserve active
queue ownership, serialize reconnects, contain atomic/no-overwrite media ingestion,
and omit raw legacy log arguments. Verify defects before changing behavior. Actual
SDK/provider/large-media deployment evidence remains separate from local fakes.


Executed baseline: 1/9 passed, 8/9 failed in bot-recovery.test.cjs; the independent
master/part hash restart check passed. Baseline TAP remains private. Additional
static findings: legacy !process consumes stored token/recipient queues outside
claims; range maintenance duplicates retry loops; cache mapping failures are
swallowed; send deadlines remain alive after acknowledgement. Hold legacy queue
bytes, share checkpointed range/all maintenance, require send + mapping acknowledgement,
and retain ambiguous in-flight work for explicit review rather than blind retries.
A shared job gate prevents maintenance overlap. Git retains the replaced duplicate
paths; these were not previously runtime-certified live behaviors.

A separate fake recipient test reproduced six sends after one lost acknowledgement.
The repaired path marks uncertainty at the first send failure and does not reconnect
or fall through to another tier. Authenticated bridge readiness now uses a read-only
health contract: the old TEST_CONNECTION probe failed its own token-format rule.
Metadata refresh errors no longer assume that another upload is required. Group
refresh also checkpoints before a send and requires explicit acknowledgement/log
completion; unsafe direct-file refresh bypasses are disabled.


## Final executed local verification, 2026-10-26

- Expanded complete Node suite: 102/102. This includes 22 filesystem/recovery,
  13 lifecycle/queue/logger, 6 fake entrypoint and 6 API-boundary cases (some cases
  exercise multiple assertions); exact subtest names/counts are in the TAP record.
- Real Node HTTP client + owned SQL adapter + fake recipient sends: 10/10.
  The new private health probe verifies readiness without a token mutation.
- Delivery protocol/socket suite: 6/6. Retained Docker integration: 88/88 plus
  OAuth helper 7/7; disabled environment 21/21. Fresh native HTTP 94/94 and
  schema/claim bridge 22/22 passed. Test dates now use Asia/Colombo rather than
  a historical hardcoded audit date.
- All real WhatsApp startup remains behind the original live guard. Fake SDK
  entrypoint tests substitute the complete dependency graph in a VM which refuses
  HTTP/HTTPS, real Baileys, dotenv and actual authentication-store imports. The
  real CLI guard is separately executed in the same suite. No real SDK connection,
  device, account, recipient, group, QR or production API is used.

The first no-overwrite filesystem run inside the restricted host sandbox was 7/9:
within-workspace hard-link publication was denied there. The same nine tests with
permission for those owned fixture operations passed 9/9; expanded tests pass.
An initial fake-harness arrow callback syntax was corrected before its 6 runtime
cases ran. These harness/permission failures are distinct from repaired behavior.

## Resulting behavior and remaining gates

Atomic no-overwrite ingestion validates IDs/types/extensions, rejects links and
non-media discovery entries, and verifies idempotent existing bytes. Only owned
unique test directories are removed by test cleanup. It does not scan codecs,
malware or certify large-file memory use. Source masters are never overwritten.

Warming uses atomic private checkpoints, explicit pause/resume, bounded ranges,
a shared maintenance gate and single-attempt sends. Unconfirmed send/mapping/log
completion stays held, including after restart and force refresh. The same engine
handles manual ranges and aging work. Group refresh checkpoints too. Legacy cold
queues are preserved and cannot bypass the claim protocol. Corrupt evidence fails
closed. No automatic operator decision or exactly-once external delivery is claimed.

A private worker lease excludes cooperating owners of one session directory;
stale/changed locks stay held, never trigger credential/session deletion. SIGTERM/
SIGINT quiesce new work, cancel timers/jobs and drain commands up to five seconds.
Active ambiguous sends remain governed by held claims/checkpoints. A stale open
cannot resurrect health/jobs; reconnects cannot overlap unresolved attempts. These
are cooperative filesystem/process guarantees, not a hostile-root or power-loss
transaction guarantee. Review stale leases and uncertain maintenance separately;
no automatic stale-lock removal or held-maintenance resolution tool is exposed.

Default logging drops all legacy raw arguments and emits fixed structured events
and bounded counts. SDK logging is silent; QR payloads stop the worker rather than
enter logs. **A separately reviewed owner-controlled pairing procedure is required
before an approved live account can be connected.** Actual SDK compatibility,
large media/expiry, real reconnect/acknowledgement and recipient experience remain
externally unverified. Logs need deployment retention/access and actual alert routing.

Current phase results do not authorize deployment, live tests, production access,
credential rotation, historical session import, remote Git or publication. Next:
isolated staging artifact/recovery/load/security rehearsal with synthetic data.
