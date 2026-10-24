# Zinema.lk

Zinema is a legacy PHP/MySQL entertainment discovery project with movie and TV
catalogues, a vertical short-video feed, accounts, social interactions and content
administration. Separate Node.js components contain WhatsApp delivery and video
URL extraction logic.

This repository is being imported for review. Source presence and syntax checks
do not establish working features. Database reconciliation, security repair and
an isolated reproducible development environment remain future work. Live
integrations are guarded; do not use old component READMEs as setup instructions.

| Location | Component |
| --- | --- |
| `Zinema.lk Website/` | PHP pages, APIs, administration and browser assets |
| `Zinema.lk Website/fb-video-api/` | Guarded extraction service |
| `whatsapp-bot-deploy/` | Guarded Baileys delivery bot and workers |
| `docs/recovery/` | Evidence, decisions, known risks and review order |

Start with [recovery status](docs/recovery/STATUS.md), the
[source-based project description](docs/recovery/PROJECT_DESCRIPTION.md) and
[initial publication plan](docs/recovery/PUBLISHING.md). Follow
[the contribution workflow](CONTRIBUTING.md) for branches, commits and checks.

Component `.env.example` files contain placeholders. PHP reads process variables;
there is no root application or automatic root dotenv loader. Private data,
credentials, sessions and media are excluded. A complete sanitized database and
safe local setup have not yet been established.

For syntax validation only, use `node scripts/check-source.cjs`. Set `PHP_BINARY`
to your PHP executable if necessary. This command does not start the project.

The first ZL-12 refactoring slices retain the existing PHP include and bot import
paths. Disabled ads helpers now live in `apps/web/src/Views/Ads.php`; the pure bot
configuration parser lives in `whatsapp-bot-deploy/src/config/environment.js`.
The old paths are compatibility shims. Keep the complete repository layout when
packaging: copying only `Zinema.lk Website/` now omits its external helper module.
Keep that website folder as the intended document root; do not expose the workspace root.

Run the isolated regression suite without installing packages or starting services:

```powershell
$env:PHP_BINARY='C:/xampp/php/php.exe' # Or the absolute path to your PHP executable.
node --test tests/refactor/*.test.cjs scripts/test-public-export.cjs
```

Tests use fictional input, pure helper imports and temporary synthetic exports.
PHP must be available; an unavailable executable fails the suite. These tests do
not verify the full website, database, browser player or live integrations. See
[ZL-12 execution and rollback](docs/architecture/REFACTOR_EXECUTION.md) for the
implemented scope, packaging policy, measured results and remaining route gates.


## Reproducible local environments (ZL-13)

Use [local setup](docs/development/LOCAL_SETUP.md), [local staging](docs/development/STAGING_SETUP.md),
[CI pipeline](docs/development/CI_PIPELINE.md), [environment matrix](docs/development/ENVIRONMENT_MATRIX.md)
and [operations / executed verification](docs/development/ENVIRONMENT_OPERATIONS.md).
The environment is PARTIAL: isolated services and synthetic tests run, while
application readiness is held for ZL-14. No production deployment is configured.


## Database migrations and current limits (ZL-14)

The verified database definition currently covers four tables only. See the
[schema inventory](docs/database/SCHEMA_INVENTORY.md),
[migration guide](docs/database/MIGRATION_GUIDE.md) and
[executed results](docs/database/SCHEMA_TEST_RESULTS.md). The new test harness
uses a fresh disposable database; it does not migrate existing environment volumes.
ZL-14 remains PARTIAL and application readiness remains blocked.

## Licensed catalogue foundation (ZL-15)

The isolated rights-workflow service and two additive test migrations are PARTIAL.
They do not enable real publication or integrate the unverified legacy admin/schema.
Start with [catalogue architecture](docs/content/CATALOGUE_ARCHITECTURE.md),
[rights model](docs/content/CONTENT_RIGHTS_MODEL.md),
[ingestion](docs/content/INGESTION_WORKFLOW.md),
[publishing policy](docs/content/PUBLISHING_POLICY.md),
[executed tests](docs/content/CONTENT_TEST_RESULTS.md) and
[operations](docs/content/CONTENT_OPERATIONS.md). Public streaming remains denied.

## Catalogue API boundary (ZL-16)

The additive [catalogue API](docs/api/CATALOGUE_API.md), [search service](docs/api/SEARCH_ARCHITECTURE.md)
and [entitlement policy](docs/api/ENTITLEMENT_POLICY.md) are tested locally with
synthetic data. ZL-16 remains PARTIAL. The real endpoint fails closed with 503;
canonical/identity/rights integration and protected media delivery are unresolved.
See [executed results](docs/api/CATALOGUE_TEST_RESULTS.md) and
[integration gates](docs/api/INTEGRATION_GUIDE.md). Legacy callers are unchanged.

## Authorized HLS foundation (ZL-17)

ZL-17 is PARTIAL: local synthetic HLS, verified Shot-link resolution, a scoped
playback session, per-resource authorization and opt-in tab-local progress are
implemented and tested. Production playback remains unconfigured. Start with
[playback architecture](docs/streaming/PLAYBACK_ARCHITECTURE.md),
[actual tests](docs/streaming/PLAYBACK_TEST_RESULTS.md) and
[operations/rollback](docs/streaming/STREAMING_OPERATIONS.md). No real media or
CDN is enabled. Existing movie Watch no longer inserts an unprotected MP4 URL.

## Integration quarantine (ZL-18)

Unapproved payment, legacy extraction, unofficial delivery and external-media
operations are blocked regardless of environment flags. Historical code is
preserved; a separate offline simulator has no provider transport. ZL-17 original
local HLS remains independently tested. ZL-18 is PARTIAL because private/deployed
entry points and full legacy integration remain unverified. See
[inventory](docs/security/INTEGRATION_QUARANTINE.md),
[feature controls](docs/security/FEATURE_FLAGS.md) and
[executed checks](docs/security/QUARANTINE_TEST_RESULTS.md).

## Identity hardening foundation (ZL-19)

ZL-19 is PARTIAL. [Identity architecture](docs/security/AUTHENTICATION_ARCHITECTURE.md)
and [executed checks](docs/security/AUTH_TEST_RESULTS.md) describe isolated PHP
security services and three narrow legacy fixes. No production identity adapter,
MFA enrollment/recovery or complete admin/API hardening is enabled. Review the
[release blockers and rollback](docs/security/AUTH_ROLLBACK.md) before any rollout.
Existing account/session requirements are not replaced without recovery approval.
