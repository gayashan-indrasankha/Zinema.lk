# Production deployment and operations — Jira ZL-11

Date: 2026-10-23. **PROPOSED TARGET / NOT PROVISIONED / NOT RUNTIME VERIFIED.**
See [target boundaries](TARGET_ARCHITECTURE.md), [streaming](STREAMING_ARCHITECTURE.md),
[production gaps and Jira](PRODUCTION_GAP_ANALYSIS.md), [migration](MIGRATION_PLAN.md).

## Observed starting point

The current website expects Apache rewrite rules, PHP sessions, MySQLi bootstrap
and some conflicting PDO consumers; the bot has a lockfile but the extractor has
no adjacent lockfile. The website lock has no package entries and no adjacent
manifest. No tracked first-party deployment workflow, Docker configuration or
application regression suite was established in the inventory. PHPMailer is
bundled vendor code, not a root Composer restore plan. Existing recovery/export
checks are useful packaging evidence, not production application coverage.
Current DNS, host, installed schema, backup configuration and running systems
were not inspected. Existing credentials/configuration are not deployment authority.

## Logical minimum and environment separation

~~~mermaid
flowchart TB
  U[Browser] --> E[HTTPS edge / application proxy]
  E --> W[Apache PHP application]
  W --> DB[Private MySQL]
  W --> Q[Durable job queue / outbox]
  Q --> M[Restricted media worker]
  M --> O[Private object origin]
  U --> C[Authorized video CDN]
  C --> O
  W -. optional .-> A[AI provider boundary]
  W -. conditional .-> N[Delivery adapter worker]
  W --> T[Redacted telemetry and alerts]
  DB --> B[Encrypted isolated backup]
  O --> B
~~~

One modest application instance is a reasonable initial candidate; it has an
acknowledged single-instance availability limit. Choose managed DB and backups
where the approved cost reduces the solo operator's patch/restore burden. A
single-host DB alternative requires explicit downtime, isolation, encryption,
backup and restore ownership; it is not equally resilient by assumption.
Do not add Kubernetes, a service mesh or multiple services for folder aesthetics.
Use dedicated workers only for resource/time isolation or optional integrations.

| Environment | Runtime/data | Identity/network | Promotion rule |
| --- | --- | --- | --- |
| Development | Pinned PHP/MySQL and Node toolchain; fictional SQL/media; fake mail/delivery/AI | Loopback/isolated network; local secrets excluded; no production tokens | Reproducible bootstrap and tests, not developer machine assumptions |
| Staging | Production-shaped configuration and same immutable build; sanitized fixtures only | Separate accounts/DB/buckets/keys/domain; deny live message sends and real customer mail | Migration/restore/player/security smoke gates; approve release artifact |
| Production | Reviewed immutable artifact and versioned schema; licensed assets only | Least-privilege service identities, private DB/origin, monitored HTTPS edge | Explicit owner release authorization, canary and rollback decision |

Never clone production data into development by convenience. Sanitization itself
needs an approved process; fictional fixtures are the default. Place PHP sessions,
uploads/quarantine, logs, bot state and secrets outside document root/source exports.
The proposed web public directory contains intended handlers/assets only. Keep
existing guards/default-off integrations during the compatibility migration.

## Runtime, network and vendor choice

PHP web requests own sessions, catalogue, admin, entitlement and playback metadata.
MySQL stays on a private network with a restricted app identity; a separate migration
identity has bounded DDL privileges. Node delivery processes remain optional and
off until approved; an AI service is optional, not a required new runtime. Media
jobs use isolated workers or managed encoding; HTTP requests must not run lengthy
encodes. Durable jobs survive process restarts with leases and bounded retries.

For AWS consideration: object storage/managed encoding/CDN can separate bandwidth
and encoding capacity from the app; managed DB may reduce operations. Compare
equivalent providers against region, measured egress, isolation, backup/restore,
support and operational time, not name recognition. Specific compute/DB/service
features, availability, IAM/signing behavior, limits and prices are
**REQUIRES CURRENT VENDOR VERIFICATION.** No provider/account was inspected or
provisioned. Hosting location, monthly budget, traffic, RPO/RTO and legal residency
remain owner decisions before selection (ZL-46).

DNS changes require an owner-reviewed domain/record plan, existing record inventory,
TTL/cutover/rollback and no accidental mail-record replacement. Terminate HTTPS
with tested automatic renewal and alerting; redirect safely behind trusted proxy
headers, validate canonical host and cookie scope, and verify origin exposure.
Apply HTTPS/HSTS policy only after subdomain/certificate readiness. ZL-48 owns this
future work; no domain or certificate changes occurred.

## CI/CD and versioned migrations — planned

1. ZL-13 defines pinned runtime versions, dependency installation from reviewed
   locks, sanitized environment examples and safe no-network/local test modes.
   Reconcile orphan/missing locks separately; do not assume npm install is a repair.
2. ZL-31 adds parse/lint, unit, canonical-schema integration, route/contract/browser
   tests, dependency/secret scans and legal-fixture export policy checks. Dependency
   install scripts and CI actions need explicit trust review and least privilege.
3. ZL-47 builds one immutable versioned artifact, records source SHA/dependency
   inventory, verifies integrity and promotes the same digest to staging/production.
   Use scoped short-lived deploy identity where supported, protected environments,
   concurrency control and owner approval before production.
4. Apply approved numbered migrations with a separate job/identity, backup/recovery
   point and lock; record version/outcome. Never run all legacy SQL automatically
   or execute schema writes during settings reads.
5. Staging verifies schema compatibility, routes, account security, licensed HLS,
   origin denial, expiry/rights, fake delivery, alerts and restore. Release notes
   record measured gates and known risks rather than a green syntax-only result.
6. ZL-36 controls canary exposure, baseline comparison, promotion thresholds and
   explicit rollback. No workflow, environment, branch or deploy was created here.

Use expand/contract schema changes with an application compatibility window. Stop
on drift or partial migration; use tested forward repair or reviewed restore
rather than destructive down scripts by default. A code rollback must not roll back
rights revocations or require an incompatible older schema.

## Secrets and trust boundaries

| Principal/boundary | Planned controls | Current source-linked gap |
| --- | --- | --- |
| Public browser → PHP | Input limits, output escaping/CSP, safe methods, CSRF for cookie writes, parameterized queries, rate limiting | W01/W04/W06/W08 large mixed handlers; URL and XSS/CSRF parity unverified |
| User → account/session | One session policy; secure HttpOnly cookies, appropriate SameSite, rotation/expiry, password hashing/reset controls | W02/W04 long-lived/parallel session and reset flows need reconciliation |
| Administrator/publisher | Distinct RBAC, MFA, reauthentication for sensitive actions, audited ownership checks | W14–W17 login helper does not prove consistent role enforcement; destructive GET paths |
| Upload → worker | Quarantine, content/magic validation, bounded probe, no shell, restricted egress and resource limits | admin/trailers.php:17–29 unquoted shell construction; W15 upload validation incomplete |
| Viewer → playback | Server entitlement/territory/window evaluation, short scoped access, abuse limits | W07 direct DB URL, no established rights authorization |
| CDN → origin | Private origin authenticated to CDN; deny public list/read, restricted worker write | Planned, no deployed policy reviewed |
| AI provider | Minimal DTOs, server-only key, validated IDs/output, timeout and budget | Planned; cannot use AI to grant rights |
| Messaging provider → app | Approved scenario, scoped request authentication, replay/idempotency protection, atomic token claim | W19/W20 and B03–B05 status/ack/table drift |
| App → DB | Canonical repository contracts, transactions, least privilege, audit of schema changes | PDO/MySQLi mismatch, incompatible token generations, runtime DDL |
| Runtime → secrets | Environment-specific secret store/access, masked CI output, no secrets in artifacts/browser | Real secret contents deliberately excluded; their posture not verified |
| Operator → backup | Encryption in transit/at rest, separate permissions/keys, restore audit | No backup/restore runtime evidence inspected |

Rotation, account settings and production access require separate authorization.
Designing these controls does not attest they are configured. Security follow-up
includes CSRF/XSS/authz review, upload/SSRF tests, dependency vulnerability checks,
signed access and token race tests; ZL-51 executes those future release gates.

## Monitoring, operational ownership and incidents

Emit structured events with timestamp UTC, severity, service/version, route/job
category, bounded correlation reference, outcome, latency and safe error code.
Do not log passwords, session/token/signature values, signed URLs, chats, audio,
full prompts/provider responses or raw SQL parameters. Restrict access and set
purpose-based retention; define support-safe diagnostics separately.

Candidate objectives are planning inputs, not achieved guarantees:
99.5% monthly playback-authorization availability, p95 start below 5 seconds under
a specified test profile, RPO at most 24 hours and RTO at most 4 hours. ZL-49/ZL-50
must agree feasibility, measurement and ownership before launch. Monitor app
errors/latency/auth denials, DB pool/slow query/space, queue oldest age and failures,
transcode errors/cost, playback startup/rebuffer, CDN/origin denies, certificate
expiry and backup freshness. Alerts need a destination, severity, actionable
runbook, acknowledgement and a tested synthetic trigger.

ZL-35 names the on-call owner, support hours/escalation, maintenance window and
runbooks for DB failure, broken release, expired signing keys, unauthorized asset,
provider outage, rights complaint, abuse and budget exhaustion. An incident record
includes impact, timeline, containment, evidence preservation, recovery and reviewed
follow-up. Revocation/takedown is an authorization override, never undone by code
rollback. Avoid promises of 24/7 support without staffing.

## Backups and disaster recovery

ZL-50 plans encrypted DB backups plus versioned object inventories/masters, migration
records and non-secret configuration/IaC. Define encryption key recovery separately;
a backup whose key is lost is unusable. Isolate backup permissions from application
write credentials; select off-host/off-account retention where budget permits.
Replication and CDN cache are not substitutes for backup.

Proposed minimum: daily metadata backups with retention approved against privacy
and rights; object version/retention choices per licensing and rebuild requirements.
Point-in-time recovery is an option only after current service verification.
Restore into an isolated empty environment using fictional/legal sample validation;
measure actual loss/time, reconcile asset checksums, migrations and publication
rights, and record evidence. Test partial DB/object restore, credential/key recovery
and unavailable primary. No backup file, dump or production database was opened.

Recovery sequence: contain exposure → preserve sanitized incident evidence →
restore infrastructure/configuration → restore canonical DB/object versions →
verify rights/entitlements and routes → verify signed playback and security →
owner approves limited traffic → observe and expand. Do not replay outbound
messages/mail automatically or restore private sessions into public source.

## Cost and release gates

Approve budgets for fixed host/DB, encode minutes, storage versions, CDN egress,
logs/backups, AI and optional messaging. Tag/environment-separate spend; set
quota/retention/concurrency limits and alerts with a tested degradation plan.
Budget alarms alone do not guarantee a hard cap. Current quotes, tax and regional
rates are **REQUIRES CURRENT VENDOR VERIFICATION.** No spend was authorized.

First public release requires ZL-14/15/16 data and rights, ZL-19 accounts/admin,
ZL-41–45 media, ZL-46–50 operations, ZL-31 regression gates, ZL-33/52/53/55
legal/publication/customer trust, ZL-51 security/load and ZL-36 controlled release.
ZL-32 validates an approved monitored alpha/beta; ZL-34 covers onboarding/help.
Exact ordering is a **PROPOSED DEPENDENCY**, not a statement about Jira links.
AI/voice and messaging can remain off unless the release scope requires them.
