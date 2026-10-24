# Feature controls - ZL-18

One config/integration-policy.json defines whatsapp, extraction, payments and
external_media names, existing mode variables, safe messages and simulation
eligibility. PHP apps/web/src/Integrations/FeatureGate.php and Node
shared/integration-gate.cjs consume it. No feature-management framework, new
provider or configuration file containing credentials is introduced.

| Capability | Existing variable | Live default / maximum | Offline simulation |
| --- | --- | --- | --- |
| whatsapp | WHATSAPP_MODE | Disabled, even with live/approval flags | APP_ENV=test and exact local, separate simulator only |
| extraction | EXTRACTOR_MODE | Disabled, even with live/approval flags | APP_ENV=test and exact local, fixed fixture identifier only |
| payments | PAYMENT_MODE | Disabled | None |
| external_media | MEDIA_MODE | Disabled | None; ZL-17 original HLS is a separate verified path |

Missing, false, true, live, malformed/case/whitespace values cannot activate live
features. Existing ALLOW_LIVE_INTEGRATIONS, ALLOW_LIVE_WHATSAPP and
ALLOW_PRODUCTION_API are not approval. Production cannot enable the legacy
providers. The pure bot DTO keeps its interface with liveWhatsApp=false and its
assertion always denies, including caller-forged DTOs. PHP recovery_mode forces
these four legacy modes disabled without requiring their provider credentials.
Mail/OAuth configuration is not rewritten by this issue; it remains separately
unresolved for production and must not be considered approved.

Public GET api/integrations/status.php returns only available=false, disabled or
simulation_only state and a safe message per capability. POST is 405. Simulation
status does not mean a public/live service exists. Browser controls always reflect
the unavailable live capability and never treat localStorage/query/status flags
as permission. Backend, direct route, worker/module and action guards remain
independent of the UI. The status route loads no session, DB or real environment file.

Safe offline example: APP_ENV=test, WHATSAPP_MODE=local, EXTRACTOR_MODE=local.
Use only shared/offline-simulator.cjs; legacy bot/extractor entry points still deny.
No HTTP fixture server or real provider is enabled by those values. The simulator
validates fictional IDs/recipients, bounds in-memory work and rechecks mode before
every operation. Nothing is persisted for later live dispatch.

Tests cover missing/malformed/production values, PHP/Node parity, direct module and
HTTP bypasses, callback replay, simulator mode changes and UI controls. See
[executed results](QUARANTINE_TEST_RESULTS.md). Future activation requires an
explicit approved-provider implementation and source/CI/security/legal review;
changing a Boolean or mode value is insufficient. See [rollback](QUARANTINE_ROLLBACK.md).
