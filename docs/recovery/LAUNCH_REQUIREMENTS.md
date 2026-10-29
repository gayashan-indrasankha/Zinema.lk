# Fresh-launch requirements — website hosting and owner-run bot

Date: 2026-10-28. State: **Owner selected Stellar shared hosting; audience/file
targets confirmed; actual host compatibility and personal-server capacity pending**.
Continues the five-document uncommitted planning draft based on `759e4a0` on
`zinema/promotion-controls`; the earlier US$100 combined envelope is superseded.
This is a product/capacity brief, not a purchase, deployment or configuration change.
The owner has now confirmed a bot-machine hardware/volume report; see
BOT_MACHINE_INVENTORY.md. It records approximately 121.51 GiB free volume space
and 1.55 GiB free RAM at collection, without proving throughput or load capacity.

## Confirmed scope

- Fresh site and new data; no legacy user-data migration.
- Free access. Payment/subscription handlers remain disabled.
- Website: catalogue, search, movie/series/episode details, Shots/trailers, accounts,
  saved content and the existing administrator workflows in PRODUCTION_FEATURES.md.
- Full movies/episodes: WhatsApp delivery only; no website full-content streaming.
- Owner runs the WhatsApp bot on a personal machine/server. Paid hosting budget
  covers the website only; do not include a cloud bot/media fleet in that budget.
- Owner targets: 500 registered users, 50 daily users, 20 simultaneous website
  users, and full-content files up to **2 GiB (2,147,483,648 bytes)**. These are
  requirements, not measured capacity or proven provider capabilities.
- Durable owned original media is required. WhatsApp copies are delivery caches,
  not the master library or backup.
- Local fictional fixtures only until separate account/recipient-specific approval.
  Production access, provider connections, spending, deployment and publishing
  remain disabled. Public documentation/pricing research is not an integration test.

## First-month envelope

Owner-confirmed numbers are identified below. All other figures are engineering
assumptions, not measured demand, approved spending, enforced runtime limits or
demonstrated capacity. One GiB is 1,073,741,824 bytes.

| Requirement | Proposed value | Meaning / acceptance |
| --- | --- | --- |
| Website hosting budget | Owner-selected Stellar: screenshot US$2.28/month equivalent, billed yearly | About US$27.36 by multiplication; actual checkout/renewal/tax/extras unverified |
| Registered audience | 500 accounts | Owner-confirmed target; start with 10 invited testers before expansion |
| Daily audience | 50 distinct active users | Owner-confirmed target; registration count is not concurrency |
| Simultaneous website users | 20 active sessions | Owner-confirmed target; proposed mix includes two admins; not 20 media sends |
| Website sustained load | 5 HTTP requests/second for 2 hours | Mixed public/member/admin workload with fictional data and fake providers |
| Website burst | 10 HTTP requests/second for 5 minutes | Recover to normal queue/latency without lost writes or duplicate jobs |
| Catalogue at launch | Proposed 50 movies, 10 series, 100 episodes | Unconfirmed: 150 full-content files on the personal server, one rendition each |
| Additional website media | 50 Shots, 20 trailers | Original/licensed demonstration fixtures for testing; separately account for their bytes |
| Full-content average / desired maximum | Assumed 512 MiB average; owner requires up to 2 GiB | Actual average unknown; provider/attachment/byte-boundary verification remains blocked |
| Full-content library | 75 GiB at assumed average; 300 GiB if every file is 2 GiB | Personal-server disk plus temporary, quarantine and backup capacity; no full-video cloud volume is planned |
| Daily full-content demand | 20 completed deliveries | Model all 20 as physical uploads for conservative network budgeting; not a verified provider allowance |
| Bot dispatch | One active delivery; at most 30 waiting | Proposed backpressure; one pending request per account, explicit busy/held result, no blind replay after an uncertain send |
| Extractor | One active extraction; browser fallback disabled | Review provider transport separately; no browser fallback required for this launch |
| Availability objective | 99.5% per calendar month | Proposed measured target, roughly 3.6 hours unavailable in a 30-day month; a single host has no failover guarantee |

The assumed delivery model is 20 x 0.5 GiB x 30 = **300 GiB/month** from the
personal server, with a **600 GiB/month** planning allowance for transport/recovery
overhead. If every file is 2 GiB, raw uploads alone are **1,200 GiB/month**, or
**2,400 GiB/month** with the same 2x allowance, before backups. These are personal
internet requirements, not website-host traffic. This allowance never authorizes
retrying an uncertain recipient send. Record the actual average before sizing.
Queue capacity is not a completion-time promise: measure large-file service time
and set admission/queue-age thresholds accordingly. The existing 300-second queue
alert is a local policy value, not a measured full-film delivery deadline.

## Website-only hosting budget

| Website host option | Reference base monthly price | Decision |
| --- | ---: | --- |
| Owner-selected Stellar shared hosting, 20 GB account storage | US$2.28 monthly equivalent billed yearly, from screenshot | Replaces earlier US$6–12 VPS planning; actual host acceptance pending |
| Bot and full-content storage | Owner's personal machine | No cloud bot/media volume included in website-hosting estimate |
| Staging | Local isolated rehearsal initially | Exact-provider parity test remains required; temporary remote staging needs separate approval/costing |

The supplied screenshot identifies Stellar, annual billing, three websites,
20 GB storage, 30 mailboxes and a first-year domain offer. These match Namecheap's
public Stellar plan. The crossed-out US$5.88 amount is not treated as a verified
renewal quote. Multiplying the displayed US$2.28 by 12 gives US$27.36; rounding,
region, promotions, taxes and actual checkout may differ. No account was accessed,
purchased, connected or changed. [Official plan](https://www.namecheap.com/hosting/shared/).

The screenshot price covers the base website hosting only. Taxes, domain renewal,
off-host backups, mail, paid alerts, provider usage and any transfer overage remain
separate unresolved costs. The existing personal machine is not assumed free to
operate: electricity, internet/upload quota, disks, power protection and recovery
storage stay outside this website-hosting figure. Confirm an exact affordable
hosting ceiling and required recurring extras before a purchase.

Host the PHP site, private database and website uploads on the selected account
as a candidate. A private extractor on the same account is conditional on runtime,
process and socket support; shared hosting is not a root-managed VPS. Keep its keyed local
Unix socket and browser fallback disabled, so the current private transport model
does not need an exposed port. No cloud originals or WhatsApp credentials belong
there. Verify the selected shared hosting's Node/private
transport, PHP extensions, permissions, scheduled tasks, backup and security
requirements; the existing Node cPanel offering does not prove this application's
private socket process model works. Public documentation lists Node 24 support
but does not establish exact patch versions/socket permissions for this account.
[Node setup](https://www.namecheap.com/support/knowledgebase/article.aspx/10047/2182/how-to-work-with-nodejs-app/).
If extraction must instead run on the personal machine, design/review an
authenticated private cross-host transport: a Unix socket cannot span hosts.

Public resource comparison lists Stellar at 0.5 CPU core and 1 GB RAM, with 20 GB
storage. These are reference account limits, not demonstrated application capacity.
The local Docker rehearsal is not an emulation of the provider's shared resource,
process or request scheduler. [Resource comparison](https://www.namecheap.com/support/knowledgebase/article.aspx/10065/2279/whats-the-difference-between-managed-wordpress-hosting-and-shared-hosting/).

Start managed website upload planning at 5 GB (5,000,000,000 bytes), 0.5 GB
quarantine and
100 GiB/month website transfer, with a separate database/log/backup allowance.
These remain assumptions and do not raise existing upload limits. All full-video
ingestion and delivery must bypass the website host; tokens/mappings/heartbeats
are small API data. Verify this separation in staging and measure Shots/trailer
bandwidth. Reserve disk for temporary files/quarantine, alert at 75%/85%, stop
admission safely before exhaustion and require review before cleanup.
The account's other websites, mailboxes, database, logs and backup archives share
its space. The 20 GB plan is not a 20 GiB upload quota. The disabled candidate is
ops/hosting/namecheap-stellar.json; existing runtime limits remain unchanged.

## Personal bot server and two-host requirements

- Record owner machine OS, CPU, RAM, free/total disk, sustained upload speed,
  data allowance, hours available, sleep/reboot policy and power protection.
  Do not infer these from the developer workspace or an old deployment.
- Use bot-initiated authenticated HTTPS calls to the website bridge for claims,
  mappings and heartbeats; the inspected client already uses this request model.
  Review TLS, narrowly scoped server credentials, proxy/rate limits, firewall and
  timeout policy on the chosen host. No direct public database or router port
  forwarding is required for this proposed bridge model. Deployment is unverified.
- Website stays browseable if the personal machine is offline, but delivery must
  show an honest unavailable/held state and must not claim success or accept an
  unbounded backlog. Verify stale heartbeat, restart, API outage, acknowledgement
  loss and queue expiry using two isolated local targets/fake clocks first.
- Use a supervised dedicated process/session and the existing reviewed pairing
  and worker lease controls. No automatic live-session/key deletion or reset.
- A 2 GiB upload is 17,179.869184 megabits: at sustained 10/20/50 Mbps the ideal
  transfer-only times are about 28.6/14.3/5.7 minutes. Protocol and provider delays
  add time. Measure throughput; parallel browsing does not imply parallel delivery.
- Durable originals and private session/queue state need encrypted recovery copies
  on a separate device/location with separately held keys. Hosting outside the bot
  machine does not protect the local originals. Retention/capacity/cost need owner
  input: seven full media points are 525 GiB at the assumed average and 2,100 GiB
  if the whole proposed catalogue uses 2 GiB files. Include workspace headroom.
- Distinguish website availability from delivery availability. The proposed 99.5%
  goal is not demonstrated for either; personal uptime, ISP and power failures can
  block delivery while the website remains online.

## Large-file delivery decision — unresolved launch blocker

**2 GiB is an owner-required design target, not a proven WhatsApp limit or working
integration.** The project currently uses a Baileys-based adapter; do not infer
its supported sizes, account suitability or delivery guarantees from WhatsApp
consumer-app behaviour, Cloud API documentation or local fakes.

Before selecting an integration for production, document the exact adapter,
account type, supported MIME/codec/container, attachment mode, size limits,
provider terms and cost. Confirm with current primary provider documentation and
then a separately approved, capped test using owner-controlled accounts/recipients.
Use exact bytes: decimal 2 GB is 2,000,000,000 bytes and is smaller than 2 GiB.
Provider wording such as "2 GB" must not be treated as proof of the 2 GiB boundary.
Verify 64-bit file sizes, streaming/no whole-file buffering, timeout/memory/disk
budgets and below/at/above-limit rejection with local fictional media first.
Current Meta media-reference retrieval was rate-limited; no current numerical
provider limit was established in this phase. The archived Meta SDK documentation
is not used to certify a current production limit.

If the selected route cannot deliver the intended full file, explicitly revise
the maximum/format or choose another suitable route; do not silently split,
compress, switch provider or expose a website download. Browser/device testing
must cover the chosen attachment mode and integrity of the received file.

The initial live-test plan remains capped at five sends and tiny original clips.
It does **not** approve a 2 GiB send. Prepare a separate representative-size test
with an exact byte budget, recipients, window and stop conditions before expansion.

## Staging pass criteria

- Every first-launch feature in PRODUCTION_FEATURES.md has recorded automated
  results and appropriate manual browser evidence. Unsupported optional features
  are explicitly deferred/disabled, not silently counted as passing.
- At the stated synthetic load, p95 normal page/API latency <= 1 second and server
  error rate < 1%, with no authentication/CSRF boundary failures, corrupt writes,
  lost acknowledged work or duplicate deliveries. Exclude media transfer from
  page latency and measure its completion/queue times separately. Record p99,
  CPU/RAM peaks, disk growth and queue age on the exact candidate resources.
- Cover current Chrome/Firefox desktop, Android Chrome and iOS Safari; verify
  login, search, saved content, admin forms, trailers and WhatsApp handoff. Real
  devices/provider actions still require their own approved scope.
- Fail/restart the worker, extractor, web and database under fictional workloads;
  prove safe recovery, ownership, held-work review and redacted logs. Run a
  24-hour synthetic soak including scheduled jobs with all providers fake.
  Model website and personal-server failures independently, including extended
  offline periods, dropped acknowledgements, sleep/reboot and lost connectivity.
- Target maximum recoverable data loss: database/private operational state
  1 hour; media 24 hours. Keep seven daily recoverable media points plus hourly
  database/state recovery points for at least 24 hours, with coordinated manifests.
  Target complete recovery <= 4 hours at the planned library size. These are
  acceptance goals; the scheduler, off-host transport, retention and restoration
  timings are not verified. Test new-target restore and hash/row comparisons.
- Verify encrypted off-host backups, separate recovery-key custody, bounded
  retention and owner-reviewed cleanup. The current full-archive streaming tool
  does not establish this retention/scheduling policy by itself. Include backup
  durations and writer-quiesce interruptions in availability measurements.
- Deliver actual owner alerts for site/database outage, worker heartbeat, held
  work/old queue, disk pressure and overdue backups. Verify alert recovery and
  routing without exposing recipients, session material or raw tokens.
- Close deployment-specific security, provider, content-rights/privacy/support
  and rollout/rollback gates in PRODUCTION_STAGING.md. Publish only after the owner
  reviews the completed evidence and separately authorizes launch.

## Verification and next task

The initial brief was checked for arithmetic, links and record consistency only.
The follow-up in TWO_HOST_LOCAL.md now records actual local TLS/PHP/SQL/client,
offline/busy/restart/outage and 2 GiB fake streaming tests, plus retained suites.
Its source fixes and exact executed results are in TWO_HOST_RESULTS.json; no
provider, actual hosting or personal-machine capacity is verified. No new browser
manual run, sustained production load or deployment is claimed. Public plan
references are not a binding quote or certification of suitability.

The current ops/promotion-policy.json remains local-only with a 32 GiB storage
budget, 20 MiB website upload cap, one-hour local backup interval and local alert
outbox. Those settings were not increased to these proposed launch values.
The 2 GiB full-content target does not change the website upload limit.

Local two-host implementation and results are recorded in TWO_HOST_LOCAL.md.
Next task: confirm the catalogue and personal-machine specifications; review
Stellar's exact runtime/private extractor/backup/monitoring compatibility before
any separately authorized private staging deployment or provider test.
No spending, external account connection, deployment or publication is authorized.

## Local Stellar adapter checkpoint — 2026-10-28

[Adapter evidence](STELLAR_ADAPTERS.md) records the private bootstrap, account-owned
quota/quarantine CLI, authenticated outbound extraction queue and worker resource
pressure tests. Local regressions pass; these are not actual-host acceptance.
The package remains locked, paid access stays excluded and full content remains
WhatsApp-only. Actual PHP handler/permissions/TLS/private access, real extraction,
SDK/account 2 GiB behavior, sustained capacity, upload speed, independent backup
restore and alert/supervisor acceptance remain launch gates. No publishing enabled.
