# Production staging requirements — not a deployment

Owner-approved launch scope: free access, website catalogue/shorts/trailers, full
movies and episodes through WhatsApp. Hosting is undecided. Planning assumption:
a modest single-server beta with a separately isolated staging environment.
No host, DNS, certificate, cloud resource or public service has been provisioned.
The 2026-10-26 local rehearsal is documented in STAGING_REHEARSAL.md. Its temporary
CA and isolated production-mode fixture are not provisioned production infrastructure.

## Candidate topology

- TLS reverse proxy -> production PHP/Apache or PHP-FPM application artifact.
- Private MariaDB with a fresh canonical v4 schema, explicit migrations/defaults
  and a separate migration identity. Runtime credentials have CRUD permissions,
  not schema administration. Do not serve/import the workspace or legacy dumps.
- A separate supervised Node bot process with its own account/session directory,
  read-only access to ingested original media and private writable operational state.
  Use the HTTP claim adapter; the obsolete direct-SQL bot adapter is not a deployment
  requirement. WhatsApp cached/group copies are optional delivery caches, not the
  durable media master or backup.
- A private extractor process with explicit resource/concurrency/time budgets.
  Browser fallback remains blocked. The administrator POST/CSRF server proxy and
  private keyed service now pass local fake tests (EXTRACTOR_CONNECTION.md). Live
  and production promotion remain blocked pending a reviewed transport/provider
  policy and controlled test; do not publish the raw API.
- Stable storage for uploads, original bot media, sessions, journals and logs, with
  ownership/permissions and capacity alerts. Keep private directories outside the
  public document root. Development quarantine/quotas are not a certified production
  distributed storage policy.

Do not deploy dev/web.Dockerfile, dev aliases/control/fixture scripts, fake OAuth/
SMTP, demo accounts or their secrets. Produce an allowlisted application artifact
and verify a negative path matrix for .env/.git/.runtime/backups/logs/includes,
internal partials, development endpoints and executable uploads. If using Nginx,
translate/test the Apache denial/rewrite rules; .htaccess is not an Nginx policy.

## Runtime and release contract

The executed development versions are recorded in dev/runtime-lock.json; they are
not a promise that the same versions are suitable for the chosen production host.
Select currently supported patched runtimes and rerun the full suite on the exact
staging images. The extractor dependency candidate requires Node >=24 and a locked
npm ci --ignore-scripts install. Puppeteer 25.16.0's vendor matrix lists Chrome for
Testing 156.0.8078.47; no compatible browser binary has been installed/launched in
this audit. Validate that exact pairing and its sandbox/isolation before fallback.
[Vendor browser compatibility](https://pptr.dev/supported-browsers).

- Pin image/package/browser versions and retain artifact digests, source revision
  and the applicable schema version. No implicit runtime downloads or dotenv reads.
- Configure the intended staging host/base URL, TLS certificate chain, HTTPS-only
  cookies, exact proxy identities, approved OAuth redirect URLs and private paths.
  TRUSTED_PROXY_IPS accepts exact peers; the edge must overwrite X-Real-IP. Do not
  accept arbitrary client-supplied forwarding headers. Verify IPv4/IPv6 behavior.
- Generate/apply live secrets privately only after owner authorization. Do not reuse
  demo secrets or assume preserved configuration is valid; no secret was rotated here.
- Block outbound integrations until their individual controlled tests are approved.
  Keep payment/media-streaming adapters and quarantined diagnostics/cron disabled.
- Set a deliberate application/worker/scheduler timezone, initially Asia/Colombo,
  and verify nightly warming/refresh behavior with fake clocks and restart tests.
- Version all release assets or use content hashes. Administrator CSS/search now
  use hashes; audit remaining CSS/JS and CDN resources before release. Establish a
  production CSP and external-asset policy; local containment is not production CSP
  certification. Validate security/cache headers on the actual reverse proxy.

## Acceptance gates before any users

| Gate | Required evidence | Current state |
| --- | --- | --- |
| Fresh install | Empty owned DB -> v4 -> defaults/admin provisioning -> all suites | Native canonical tests and two fresh local staging targets passed; real host not selected |
| WhatsApp delivery | Approved test account/recipients; real acknowledged original clip; retry/reconnect/held-claim handling | Local Node/SQL/fake sender verified; live test not authorized |
| Full-content handoff | Movie/episode -> token -> correct bot -> delivery; offline/rate-limited/error UX | Local route/token/claim tests; real handoff still blocked |
| Media ingestion | Document how operators put licensed movie/episode full/part files into ID-based bot storage, verify hashes/size and mappings | Atomic no-overwrite ingestion/part mappings fake-tested; large-file operational acceptance pending |
| Extractor HTTP | Administrator proxy/auth, actual private service, DNS/redirect/size/time limits and approved original FB fixture | Private actual PHP/Node connection and faults passed locally; live promotion/provider acceptance pending |
| Extractor browser | Compatible binary, sandbox, egress denial/allowlist, memory/process limits, timeout/cancellation/concurrency | Blocked; no browser fallback certification |
| Mail/OAuth | Approved owner mailboxes/test OAuth account, verified sender/callback/expiry/account-link behavior | Local fakes passed; real providers not tested |
| Restart/scheduler | One worker/session owner; no duplicate timers, persisted work, bounded queues, graceful shutdown and restart | 102-case fake suite plus SQL/fake delivery passed; real SDK/pairing/supervisor acceptance pending |
| Capacity | Identify CPU/RAM/disk/network; measure baseline and expected peak with real-shaped fictional catalogue | 5/25-client local TLS/fictional catalogue and resource samples passed; sustained/large-media/host capacity pending |
| Recovery | Coordinated DB/media/private state backup, encrypted off-host copy, isolated restore and rollback rehearsal | Quiesced authenticated fixture restore matched every row/file; off-host streaming backups/keys and real-state recovery pending |
| Monitoring | Health/readiness, heartbeat staleness, queue depth/age, failed mail/extraction/claims, disk and error alerts | Local healthy/unavailable alert verified; external routing, disk/queue alerts and retention pending |
| Security/abuse | Least privilege, CSRF/roles, rate/proxy policy, log redaction/retention, staged secret/dependency/image scan, independent review | Local checks are scoped; no penetration test/container scan or production policy certification |
| Content/privacy operations | Owner confirms rights to distributed media and appropriate privacy/support/moderation/takedown processes | Owner operational requirement; not a legal compliance assessment |
| Release | All blockers closed, owner reviews exact artifact/config/test evidence, explicit publishing authorization | Not authorized; nothing deployed/pushed |

Candidate load rehearsal: begin with 5 concurrent catalogue readers, then 25 with
realistic record sizes and a separately approved small delivery sample. Record
p50/p95/p99, error rate, memory/CPU, disk, queue age and upstream budgets on the
actual hardware. Choose service objectives after measuring; do not infer capacity
from the current 25-request local smoke or request permission to spam a provider.

Logs should use opaque correlation IDs and omit tokens/receipts/cookies/QR/session
credentials and unnecessary phone/JID/URL details. The raw command-token log was
removed; fixed-event raw-argument suppression now passes fake tests. Actual SDK/
deployment logging, routing and retention remain gates. Never
publish private session stores, generated fixture data, diagnostic logs or media.

## Blocking local work before asking to run live tests

Private extractor, bot fake lifecycle and isolated synthetic recovery/load/outage
rehearsals now pass. Next select the real staging target and budgets, implement
reviewed live extractor transport/pairing/held-maintenance procedures, and verify
production storage, off-host recovery/alerts and exact runtime/security policy.
Verify an isolated browser only if fallback is required. Then review exact accounts,
recipients, original media, caps and stop conditions in PRODUCTION_LIVE_TEST_PLAN.md.
No local pass authorizes a provider connection, deployment or publishing.
