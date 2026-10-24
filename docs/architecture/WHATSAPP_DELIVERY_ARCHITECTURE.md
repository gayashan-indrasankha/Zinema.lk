# WhatsApp delivery architecture — Jira ZL-11

Date: 2026-10-23. **CURRENT SOURCE: STATICALLY INSPECTED. LIVE MEDIA DELIVERY:
CONDITIONAL / NOT RUNTIME VERIFIED.** No provider/account/session was contacted.
See [current trace](CURRENT_ARCHITECTURE.md), [debt](TECHNICAL_DEBT.md),
[independent streaming path](STREAMING_ARCHITECTURE.md) and [target](TARGET_ARCHITECTURE.md).

## Preserve the existing architecture as evidence

| Stage | Source and current behavior | Production concern |
| --- | --- | --- |
| Entry gate | whatsapp-watch.php and bot index.js require explicit production/live enablement | No local end-to-end simulator currently establishes this journey |
| Request token | includes/whatsapp_token.php reads content/parts and creates expiring tokens; caller and default expiry differ | Rate limiter call commented out; canonical token schema and claim policy unresolved |
| Validation bridge | api/whatsapp/validate-token.php returns data for several invalid statuses; index.js:927–939 mainly requires data and rejects already-used | Expired/inactive status may reach send logic; preserve documented no_message_id distinction, define a strict allowlist |
| Website client | config/api-client.js uses the PHP bridge and handles HTTP outcomes | 16 literal endpoint names have files; existence is not response/auth correctness |
| Cached send | config/multi-tier-forwarder.js checks mappings/cache and configured storage groups | Recipient acknowledgement, stale mapping and provider permission unverified |
| Local fallback | forwarder and file-manager.js access configured private media roots | Rights, path containment, existence, size and provider support unverified; no files read |
| Queue | upload-queue.js prioritizes/FIFO, limits concurrency/pending and coalesces content/part work | Coalescing must retain each recipient's acknowledgement; clear/cancel needs fake-clock tests |
| Warm/refresh | cache-warmer.js, auto-refresher.js and schedules maintain mappings | Warm success is not recipient send; daily cron and interval comments differ |
| Health | health-monitor.js sends heartbeat/metrics through bridge | OS health does not prove successful user delivery |
| Session | index.js Baileys auth/reconnect logic; private per-sender session cleanup branch | Sensitive state mutation; owner-authorized account operations only, never test with real accounts |
| Persistence | pending/cache state under runtime configuration; warmer uses code-relative warming-state.json | Warming state ignore gap exists; safe-export default hold is a separate protection |
| SQL | PHP bridge plus structural SQL generations; alternate config/database.js uses mysql2 | Table/ID/status vocabulary conflicts; installed schema not verified |

Exact paths and line ranges are in [B01–B12/W19–W20 inventory](MODULE_INVENTORY.md).
Production mode guards must remain. No Baileys startup, QR, login, refresh, group
operation or filesystem cleanup was executed. Ignored alternative bot programs
remain metadata-only; an unreferenced file is not proven obsolete.

## Proposed redemption and delivery contract

1. PHP authorizes the content, rights, opt-in and delivery scenario, then issues a
   short-lived opaque request token bound to subject/content/part and permitted
   adapter. Store a non-reversible verifier where suitable; never log the token.
2. Authenticated provider/worker redemption validates status, expiry and intended
   recipient, then **atomically** changes ISSUED to CLAIMED with a lease and
   idempotency key. Invalid/inactive/expired/used cases are denied before any send.
3. A durable recipient delivery record transitions CLAIMED → QUEUED → SENDING →
   SENT or FAILED/UNKNOWN. The provider acknowledgement determines SENT; warming,
   queue admission or uploading an asset is not recipient success.
4. A timeout after send is UNKNOWN until reconciled using provider IDs/status where
   supported. Do not blindly retry and send duplicate media. A bounded reclaim
   policy applies only when prior outcome permits safe retry.
5. Separate per-recipient jobs from shared asset preparation/cache work. A cached
   mapping includes content version, provider, expiry, rights scope and verification
   state; no global message ID substitutes for recipient authorization.
6. Keep credentials/session roots out of document root and source exports. A
   transport failure emits a typed result, bounded retry and redacted event.

Conceptual states and storage require a future canonical migration and contract
tests, not a change to existing token tables during ZL-11.

**Provider-neutral interface — planned:** capabilities(), createDeliveryRequest(),
claimRequest(), prepareAsset(), sendToRecipient(), queryStatus(), cancelRequest().
Input is an authorized immutable request DTO: internal request/content/version/
part identifiers, server-validated recipient reference, expiry, permitted media
class and idempotency key. Output is a typed accepted/sent/failed/unknown outcome
with opaque provider reference; it is never an unchecked “success” boolean.
A deterministic fake adapter is the default in development. PHP owns authorization;
the delivery service owns queue execution; a provider adapter owns transport only.

An approved integration may deliver a notification or expiring authorized playback
link instead of media when that is the permitted scenario. Do not assume an official
API supports the existing cached group-forwarding model. Preserve the old adapter
disabled until a compliant decision and separate owner authorization.

## Production feasibility decision — CONDITIONAL

| Required decision | Evidence currently available | Gate / owner action |
| --- | --- | --- |
| A. Official capabilities | Current source uses Baileys, not evidence of an approved official business delivery architecture | Verify official supported APIs/scenarios, sender/recipient model, callbacks and authentication |
| B. Opt-in/scenarios | Request-token code exists; valid provider opt-in records/policy not established | Document affirmative consent, opt-out and message categories/templates where applicable |
| C. Media types/limits | Local upload and cached forwarding exist in source | Verify permitted formats, bytes/duration, upload/link rules and expiry; no invented numeric limits |
| D. Terms/account approvals | No account/contract reviewed | Owner verifies provider terms, approved account, restrictions and required reviews |
| E. Distribution rights | Technical media access is not licensing evidence | Verify messaging/forwarding rights separately from website streaming, territories and expiry |
| F. Reliability/throughput | Queue defaults and retries are local code intentions | Verify rate/quality limits and delivery receipt semantics; test synthetic adapter first |
| G. Cost | No current quotes verified | Estimate official message/media costs plus hosting, storage, monitoring and support |
| H. Fallback | Proposed independent CDN path; not yet built | Disable messaging cleanly; retain catalogue and authorized CDN viewing plus clear user status |

All current provider capabilities, limits, approval rules, receipt semantics and
prices above are **REQUIRES CURRENT VENDOR VERIFICATION.** No official documentation
was accessed in this phase and no suitability or legal assurance is implied.
Go requires all A–H recorded, licensing/consent/security gates passed, an approved
account/scenario and explicit owner permission for live testing. Until then live
WhatsApp video delivery is **CONDITIONAL**, not a first-launch dependency.
No platform-limit bypasses, mass-account workarounds or unauthorized group forwarding.

## Reliability, privacy and acceptance

Set bounded recipient/asset queues, per-adapter rate control, retry budgets,
circuit breakers, dead-letter review and maximum message age. Persist leases/jobs
outside the code root; choose process timezone explicitly. Worker restart tests
must prove no silent loss or duplicate recipient send. Cache refresh/warming is
optional and must not hold core viewing hostage.

Monitor requests/claims/sent/failed/unknown, queue age, retries, cache misses and
adapter availability; separate provider acceptance from user delivery. Redact
recipients, message contents, access tokens, group IDs, full API payloads and session
paths. Retention/deletion follows explicit privacy policy. No real users in tests.

Future acceptance: expiry/inactive/used denial; atomic concurrent redemption; API
auth/replay rejection; no_message_id allowed transition only; recipient fan-out;
warm-vs-send distinction; queue cap/clear/cancel; stale cache/local-file absent;
timeout after send; restart recovery; private-state containment and messaging-off
CDN availability. These tests are **NOT EXECUTED**.
ZL-7 owns feasibility/adapters; ZL-3 token/data consistency; ZL-40 rights/consent;
ZL-39 secrets/security/reliability; ZL-8 fake-contract tests. Existing stories: ZL-28 technical feasibility, ZL-54 official approval,
ZL-29 optional adapter, ZL-30 redemption/observability and ZL-18 quarantine;
see [the supplied Jira map](PRODUCTION_GAP_ANALYSIS.md).

## 2026-10-24 - ZL-18 runtime disposition

The historical cached-forwarding/queue architecture above remains preserved, not
production approved. Central entry/action guards now deny all live startup/API/
worker/cron/token behavior, independent of flags. A separate bounded offline
simulator has no transport or real store. No source/queue/session deletion or
platform approval occurred. ZL-28/29/30/54 remain proposed future decisions. See
[actual status](../integrations/WHATSAPP_STATUS.md) and
[executed denial evidence](../security/QUARANTINE_TEST_RESULTS.md).
