# Two-machine local rehearsal and Stellar hosting candidate

Date: 2026-10-28. **Local fictional scope only; launch remains NO GO.**
Continued the five uncommitted planning documents on `zinema/promotion-controls`
at `759e4a0`. Existing publication history and unrelated legacy evidence remain.

## Requirement and chosen hosting

The owner selected the Stellar annual-billing plan in a screenshot: US$2.28/month
equivalent, 20 GB account storage, three websites and 30 mailboxes. It matches the
[Namecheap plan](https://www.namecheap.com/hosting/shared/). US$27.36 is multiplication
of the displayed monthly equivalent by 12, not a verified checkout total. The
crossed-out price is not accepted as a renewal quote. No account/purchase/host or
DNS was accessed. The screenshot contains no personal-server hardware or upload
speed; those values remain unknown and were requested separately.

The published resource comparison lists 0.5 CPU core and 1 GB RAM for Stellar.
[Official comparison](https://www.namecheap.com/support/knowledgebase/article.aspx/10065/2279/whats-the-difference-between-managed-wordpress-hosting-and-shared-hosting/).
Namecheap documents a Node app feature including Node 24; that does not establish
this project's exact Node patch/private Unix socket/process compatibility.
[Official Node setup](https://www.namecheap.com/support/knowledgebase/article.aspx/10047/2182/how-to-work-with-nodejs-app/).

`ops/hosting/namecheap-stellar.json` is a disabled, non-executable candidate:
all external/deployment/publishing switches are false; a proposed 5 GB upload plus
0.5 GB quarantine budget shares the 20 GB account with other site/database/mail/
log/backup use. No production quota was applied. Full movies, bot credentials and
originals stay on the owner's machine. Domain/renewal/tax/recovery costs still need
review. This is shared hosting, not a root-managed VPS.

## Intended behaviour and implementation

Bot -> authenticated HTTPS -> website metadata bridge -> loopback-only database.
The bot initiates connections; no personal-machine inbound port or public SQL
service is required for this model. The website does not relay full movie bytes.
The extractor remains disabled; its reviewed candidate uses a private socket if
the same hosting account supports it. A socket cannot span two machines. Moving
extraction to the personal machine needs its own reviewed private transport; this
phase does not add a tunnel or expose an extractor endpoint.

- A new internal Docker network places the website/DB/TLS and fake personal bot
  in distinct network namespaces. No host/public ports or default runtime route.
  Database binds loopback, and a test confirms the bot cannot reach its TCP port.
  Ownership labels, fresh schema/volumes, private generated credentials and an
  explicitly trusted two-day test CA are used; no system trust store modification.
- The actual Node API client calls the actual PHP bridge and MariaDB over verified
  TLS. Missing/wrong keys, wrong certificate trust/hostname and unsupported methods
  are tested. API key comparison is timing-safe. Bridge bodies are metadata-only,
  bounded at 64 KiB; heartbeat has a stricter 4 KiB bound, typed/nonnegative metrics
  and explicit online/offline states. Errors are fixed categories and responses
  are no-store. Existing shared-key worker trust is retained, not per-bot isolation.
- New tokens require an online heartbeat within 120 seconds, a valid phone-shaped
  identity, no future heartbeat and a queue below 30. Selection has no fallback bot;
  the insert rechecks eligibility. Offline/busy/failed issuance shows unavailable
  with catalogue browsing available and no active delivery CTA. Token pages are
  no-store. Existing tokens are not revoked solely because of a disconnect.
  Liveness is heartbeat-based, not instantaneous: a sudden loss can take up to
  two minutes to become stale. Queue snapshots are not durable token reservations.
- The real entry point uses a 30-waiting command queue and the forwarder one active
  local upload/30-waiting upload queue. Accepted commands are not a promise of
  completion; interrupted sending/uncertain claims remain held. Restart tests use
  a fresh fake-bot process and restarted website/database with retained volumes.
- `media-policy.js` rejects empty, linked, nonregular, unsupported-extension or
  above-2-GiB local document sources before local sending, and assigns MIME by
  extension. Warming/refresh local-send paths share preflight. This is file/byte
  validation, not media-codec/malware verification or a provider attachment limit.
- FileManager now offers bounded `saveStream`/`readStream`. Stream ingestion checks
  bytes/digest/optional expected length, fsyncs, publishes by no-overwrite link and
  preserves same-byte idempotency. Overflow, digest/length failure, abort or input
  disconnect removes only its own temporary file. Conflicting originals survive.
  The small-buffer compatibility API remains; whole-buffer reading refuses files
  above 16 MiB. Existing cooperative filesystem/host ownership assumptions remain;
  hostile-root mutation or real power-loss durability is not certified.
- A strictly gated development-only flag permits the real watch page under the
  fictional local adapter. Exact origin/database/port and live-disabled checks
  are required. It cannot turn production/live mode into a fixture. The default
  development stack and production guards retain their disabled behaviour.

## Reproduction

Requires Windows XAMPP OpenSSL/PHP paths used by the existing rehearsal, Python,
Docker Desktop Linux engine/local context and pinned local Node/MariaDB/Nginx/PHP
images. Public dependency/image acquisition during builds is distinct from runtime
provider traffic. The launcher rejects remote Docker contexts and host overrides.
No preserved environment, auth store, user data or real media is imported.

```powershell
python -X utf8 dev/test_two_host_guard.py
python -X utf8 dev/rehearse_two_host.py
node --test --test-reporter=tap --test-isolation=none docs/recovery/tests/two-host-media.test.cjs
```

The rehearsal uses the reviewed current source through the development image and
read-only bot module mounts, not a committed release artifact. Its TLS proxy uses
the fixture's fixed loopback application Host behind the `website` TLS name.
It therefore tests separate-host TLS/client/DB semantics, not the final provider
origin, Apache/cPanel proxy, LVE limits or supported production versions.
Private cases/reports/logs/credentials/CA and volumes stay under ignored runtime
state or labelled volumes. Cleanup removes only owned containers/network and keeps
volumes/evidence. `--large-only` runs only the network-disabled large-file fixture;
it is not a substitute for the complete two-host run.

## Executed evidence and failures

Sanitized final counts: TWO_HOST_RESULTS.json. Static/security checks and limits:
TWO_HOST_CHECKS.json. Test labels and their execution are distinct from coverage.

- Local orchestration, TLS/auth/heartbeat/offline UI, actual SQL ownership and
  restart/outage/recovery scenarios executed with fake recipients only.
- An actual 2,147,483,648-byte fictional file is streamed to a fake document sink,
  then ingested and SHA-256/length compared, with a 64 MiB JS heap and 192 MiB
  container/RSS assertion. Sparse fixtures are not playable films. 2 GiB + 1 and
  empty sources are rejected before fake sends. No real SDK/media upload occurs.
- Retained product, fake mail/OAuth, Node claim protocol/lifecycle, guards and
  environment checks were executed. Historical browser/native HTTP/schema and
  extractor service results are retained, not claimed as new tests here.
- Confirmed defects: empty/oversized local sends attempted; no streaming ingestion;
  heartbeat accepted GET, invalid state/negative queue/oversized input; full queue
  admission; fallback assignment risk; undefined watch-page `$has_parts` warning.
  Local fixes and regression cases cover these. The final rehearsal also rejects
  future heartbeats and enforces bounded metadata/no-store responses.
- Setup/test failures were preserved: Docker engine initially stopped and was
  started locally; wrong cached CLI image/disabled-operator setup corrected;
  fixture-origin redirect corrected without following it; fixture control needed
  its own service-auth marker after private-key validation; a fresh TLS agent was
  needed for independent wrong-CA testing; cache checks accept valid combined
  no-store directives. Early offline/count assertions from the broken control
  fixture are not treated as product evidence. Windows sandbox hardlinks needed
  elevated local execution; no publish-overwrite fallback was introduced.
- PHP diagnostic review found 12 repeated undefined-variable warnings despite
  passing scenario assertions. The full-file page flag was made explicit and
  zero PHP warnings/notices/fatals/deprecations became an enforced final check.

## Remaining launch gates

The actual Stellar account's PHP/extensions/database privileges, Node/socket/process
model, HTTPS/proxy/session/CSP, quotas, cron, backup/restore and alert delivery remain
unverified. Docker limits are not proof of shared-host capacity. Browser/device
manual acceptance and sustained load/24-hour soak were not run in this phase.

The personal machine's OS/RAM/CPU/free disk, measured sustained upload, sleep/power/
uptime and independent encrypted backup/key arrangements remain owner inputs.
At 10 Mbps, 2 GiB alone takes about 28.6 minutes ideally, before protocol delays.
Real WhatsApp account suitability/pairing/recipient delivery and exact binary
2-GiB acceptance still require a separately approved capped test. Decimal 2 GB
is smaller than 2 GiB. The existing five tiny-send live plan is unchanged.

Next task: review the selected account's compatibility requirements and supplied
machine details; prepare exact disabled shared-host configuration and backup/
monitoring arrangements, resolving private extractor transport first. Then review
an account/recipient/byte-capped live plan before connecting. No purchase, account
access, provider test, deployment, remote Git or publishing is authorized here.
