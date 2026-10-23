# Local staging — ZL-13

This is restricted local staging, not a deployment or a production certification.
It uses the same reviewed Apache/PHP and MySQL images, read-only filesystems,
bounded logs, non-root users and disabled external adapters as development.
No cloud infrastructure, DNS, public ingress or TLS certificate is provisioned.

```powershell
node scripts/environment.cjs init staging
node scripts/environment.cjs config staging
node scripts/environment.cjs up staging
node scripts/environment.cjs health staging
node scripts/environment.cjs audit staging
node scripts/environment.cjs test staging
node scripts/environment.cjs down staging
```

The project is `zinema-zl13-staging`, with its own named volume, internal network,
generated credentials and loopback port **8081**. MySQL is internal; web also
has an ingress network. No development data is copied.
The application identity is `zinema_recovery_staging`. The database's internal
name is `zinema_recovery_test`: staging and CI use separate MySQL servers/volumes,
so the shared internal database name does not share storage or credentials.

The PHP contract accepts only development/test/production. Accordingly staging
uses **APP_ENV=test**, with ZINEMA_PROFILE=staging for infrastructure identity.
Do not set APP_ENV=production to bypass local safeguards. `BASE_URL` remains
http://127.0.0.1:8081. Runtime DB_HOST=db requires the explicit
RECOVERY_CONTAINER_MODE=local guard. Host development continues to accept only
127.0.0.1 by default.

Error display is disabled in all containers. Access logs exclude query strings;
raw logs remain private. The database is unpublished; only the web port is bound
to loopback. No bot, extractor, payment, email, OAuth, AI, worker, cron, CDN or
storage service starts. An absent optional Node provider cannot block process
liveness. Node is used as a test runner in the web image, not as a running bot.

Representative boundaries are present, but application schema, upload storage,
auth/session workflows, browser/player behavior, HTTPS, proxy trust, backup,
observability and deployment are unverified production prerequisites. Both
dependency drivers may succeed while readiness correctly returns 503. ZL-14
must provide reviewed migrations and synthetic seed contracts before staging can
serve verified application journeys.

Shutdown retains the staging volume. Do not remove it or reuse its credentials
for another profile. Changing passwords in a populated profile's `.env` does not
rotate MySQL accounts: initialization scripts run only on an empty volume. Stop
and plan an explicit local migration instead of overwriting credentials or
deleting storage. [Operations and results](ENVIRONMENT_OPERATIONS.md).


## ZL-14 handoff

No migrations were applied to this staging volume. The verified four-table subset
runs in a separate, newly created synthetic test instance. It cannot supply full
staging acceptance or change readiness to 200. Full schema/fixtures and legacy
compatibility are still required; see [inventory](../database/SCHEMA_INVENTORY.md).
