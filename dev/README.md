# Isolated local development

Requires Docker Engine/Desktop with Linux containers, Compose v2+, and Python 3.11+.
Node.js 24 runs the separate fake bot tests. Run these commands from the repository
root; no native PHP/MySQL installation is required for the Docker workflow.

```powershell
python dev/local.py up
python dev/local.py test
python dev/local.py test-operations
python dev/verify_environment.py
node --test --test-isolation=none docs/recovery/tests/config.test.cjs docs/recovery/tests/bot.test.cjs
node --test docs/recovery/tests/token-delivery.test.cjs
python dev/local.py down
```

Open `http://127.0.0.1:8088`. The profile's **Sign In → Sign in with Google** uses
the fictional provider on port 9081. The normal SQL bot bridge, WhatsApp, media
fetching, extraction and payments stay disabled. No bot process is started.
The tests enable only an allowlisted SQL adapter behind a generated control key.
All account emails must be fictional `.invalid` addresses; SMTP never relays.
The fake provider automatically authorizes its current test identity and is not
a model of provider consent, MFA, production security or Google's complete API.

`up` generates private random configuration under ignored `.runtime/local-dev/`,
builds the pinned images, starts a new database from `database/schema.sql` when
its named volume is empty, checks its identity, applies the additive local claim/reconciliation
migrations, initializes private quota metadata and seeds `demo_admin` / `verified@example.invalid`. Both accounts use
the private value in `.runtime/local-dev/demo_password`. Read that file privately
if a manual password login is needed. Do not paste it into reports or commit it.
Existing demo accounts are not overwritten. Production configuration, databases,
saved sessions, user media and `.env` files are never imported.

The Compose project name is derived from the checkout path. Moving a checkout
with private state requires deliberate ownership reconciliation; the launcher
refuses a mismatched state marker. Use a separate clean checkout for an entirely
new environment. There is deliberately no destructive database reset command.
`down` removes owned containers/networks and preserves database/session/upload
volumes and private local configuration. Never use `down -v` on unknown data.
`test` rebuilds the test image and uses unique fictional records, so it can be
repeated without clearing the database. It writes sanitized JSON to
`.runtime/local-dev/results/`. Rebuild the app with `up` after source edits;
the workspace is not bind-mounted into the server.

The application, database, fakes and test worker share one internal network
namespace with no default route. This retains the application's loopback-only
database and adapter guards. A separate, credential-free TCP gateway publishes
only host loopback ports 8088 and 9081 to fixed internal upstreams. It cannot
select arbitrary destinations or proxy CONNECT requests. Database port 3306 and
SMTP port 1025 are not published. Browser CSP blocks external page resources;
the fake OAuth navigation remains local. Image builds and package audit/install
commands need internet access, but runtime application services do not.

Official PHP, MariaDB and Python image digests are fixed in the Dockerfiles and
Compose file. Image-library development package versions are fixed too. Builds
still depend on those packages remaining available from Debian repositories and
are not claimed bit-for-bit reproducible; base-image/platform and library details
are in `runtime-lock.json`. Deliberate dependency updates require rebuilding and
rerunning checks. No downloaded container vulnerability scanner is bundled.

Development-only health/control/bridge helpers are installed outside the app's
document root and aliased under `/__dev`. Control requests require a generated
key and an exact development database identity; no arbitrary SQL is accepted.
`/dev` is denied in the application's Apache rules. These images, aliases,
fictional accounts and secret mounts must never be used as a production deployment.

The 1,557-byte MP4 fixture is an original red frame generated locally with FFmpeg
8.0.1 (`color=c=red:s=32x32:r=1`, one second, libx264/yuv420p/faststart), stored as
base64 for a portable text fixture. PNGs are generated in Python. No third-party
film, uploaded customer media or real user's identity is included.

For the retained Windows-specific 94-check UI/HTTP suite, canonical-schema bridge
and extractor tests, follow `../docs/recovery/tests/README.md`. Stop Docker first
because both harnesses intentionally use loopback port 8088. Set
`ZINEMA_TEST_PHASE=LOCAL_DEV` to preserve historical repair evidence under new
result filenames. The new claim-client socket test also requires local socket
permission in restricted execution environments.

For held-claim reconciliation and the read-only quota/cleanup inventory, follow
the [local operator runbook](OPERATIONS.md).
For explicitly reviewed public withdrawal, private recovery archives, restore and
interruption handling, follow the [quarantine runbook](QUARANTINE.md).

The launcher requires a local Docker context (Unix socket or local named pipe);
remote contexts and DOCKER_HOST overrides are refused. Compose interpolation uses
the explicit empty `dev/compose.env` instead of preserved environment files.
