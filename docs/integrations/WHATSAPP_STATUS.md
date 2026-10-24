# WhatsApp delivery status - ZL-18

LEGACY / UNAPPROVED for production. Baileys is historical implementation, not
proof of official provider approval. No live account, session or recipient was used.

Preserved design: PHP request tokens/bridge and movie/episode part/message mapping;
Node bot parses requests, looks up cached messages, forwards cached content,
tries cache/storage/local-media alternatives, coalesces concurrent requests and
queues cold work. AutoRefresher, CacheWarmer, UploadQueue and HealthMonitor support
maintenance, retries, schedules and reporting. None of that source is deleted.
No historical queue/message-cache/session data was read, cleared or migrated.

Entry points: whatsapp-bot-deploy/index.js, npm start/dev, config/api-client.js,
auto-refresher.js, cache-warmer.js, database.js, file-manager.js,
health-monitor.js, multi-tier-forwarder.js and upload-queue.js. All deny before
provider/configuration/private-store imports; worker/API methods also have direct
operation guards. npm dev now invokes the blocked Node entry rather than starting
a watcher over possible private stores. Pure configuration import remains safe.
No bot dependency install was performed.

PHP api/whatsapp/*.php (21 files), token helpers, distribution-group helpers,
download/watch/server-check routes, bot admin actions and token/stat cron entries
fail closed before DB, body logging or usable credentials. Nine token-helper
functions have operation guards; direct token/group calls were tested with a fake
connection that must never be used. HTTP GET/POST with forged admin/API/token
claims still deny. The admin tracking source remains withheld from the image;
host guard is tested, image absence is checked as 404, not falsely reported as an
executed deployed handler.

The separate offline DeliverySimulator requires explicit test/local mode and only
accepts movie:501 / episode:701, viewer-a / viewer-b and sample-N requests. It
models single-use SIMULATION_ONLY tokens, deduplication, finite queue, cached-forward
and synthetic-fallback outcomes with externalOperations=0. No transport, file,
phone, session, real message ID or timer exists. It is not a working Baileys or
Business Platform adapter and cannot send a message.

Required future decisions: official-provider feasibility/approval, licensed-content
and recipient/territory permissions, consent/privacy/retention, trusted identity,
authenticated replay-resistant requests, audited entitlement/token redemption,
provider sandbox, limits/retries/observability and operator runbooks. ZL-28/29/30/54
are PROPOSED DEPENDENCIES, not verified Jira edges or completed features. WhatsApp
is optional messaging, never a mandatory browser CDN. ZL-17 HLS remains independent.
Private historical variants remain excluded/unread and cannot be certified for
arbitrary owner-run execution. See [quarantine](../security/INTEGRATION_QUARANTINE.md).
