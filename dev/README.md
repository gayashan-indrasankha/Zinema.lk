# Run the whole system locally (Windows, no Docker required)

## Current owner-selected WhatsApp mode

The owner has explicitly requested real WhatsApp for the local site. The private
owned runtime now selects `dev/native/whatsapp-live.cjs`, using Baileys from
`D:\Zinema.lk\whatsapp-bot-deploy\node_modules`. Its older entry, saved session,
dotenv and group jobs are not executed. Fresh private login state is used.

Open http://127.0.0.1:8088/__local/pairing.php as the local administrator and scan
with the configured phone's WhatsApp Linked devices. Only that owner account and
the same test recipient are allowed. The site reports delivery offline until the
socket is actually linked. After pairing, the administrator's `/__local/self-test.php`
can send one original generated clip per button press, capped at20MiB. Check the
phone for receipt; submission alone is not a delivery/read guarantee.

Start/Stop batch files select this saved private mode automatically. Real WhatsApp
requires internet; website, database and other fake providers remain local. No
production or publishing is enabled. Simulator HTTP/browser suites refuse this
live runtime rather than causing real sends. Five pure policy tests are at
`docs/recovery/tests/native-whatsapp-live.test.cjs`; guard/disabled-profile tests
remain safe. See `docs/recovery/REAL_WHATSAPP_LOCAL.md` for exact scope/evidence.

## Starting the local stack

The owner currently wants local development only. Start the complete local demo:

**Double-click `Start-Zinema-Local.bat` in the project root.** It starts all six
local services and opens the website when ready. Keep that terminal window open.
Double-click `Stop-Zinema-Local.bat` for a normal stop that retains saved data.
The start script supports Python on PATH or the Windows `py -3` launcher, works
from another current directory, and leaves errors visible. It never downloads
packages, elevates privileges, changes global XAMPP or changes the saved WhatsApp mode.
If already running, it verifies/reuses the existing services and opens the site.

Equivalent terminal command:

```powershell
cd C:\xampp\htdocs\Zinema.lk
python -X utf8 dev/native.py up
```

Keep its terminal open. Repeating `up` reuses a verified running instance.

- Website: http://127.0.0.1:8088
- Administrator: http://127.0.0.1:8088/admin/login.php
- Local console/inbox: http://127.0.0.1:8088/__local/index.php
- Read fictional login details privately from the `access_file` path printed at
  startup; no credentials need to be pasted into chat.

The default unapproved/new-machine profile runs real local PHP/SQL and the actual keyed fb-video-api HTTP app with an
original-media fixture, plus fake SMTP/OAuth and a separate Node WhatsApp simulator.
Only localhost is used. The simulator never loads Baileys or contacts phones;
Facebook URLs are validated but never fetched. Payments and real integrations stay
disabled. Full-content website playback is not enabled; a tiny original generated
clip stands in for the simulated chat attachment.

In a second terminal:

```powershell
python -X utf8 dev/native.py status
python -X utf8 dev/native.py restart-bot
python -X utf8 dev/native.py down
```

Normal stop/up preserves the owned database, sessions, uploads/quarantine and
simulated-delivery ledger. Mail/code-flow sink memory is ephemeral; use a new
fictional signup or resend flow after stopping it. Never reset/delete the runtime
folder to recover an uncertain claim. Inspect the CLI operator with a private
request JSON, e.g. `python dev/native.py operator <absolute-private-request-file>`;
`{"action":"inventory"}` is read-only. Existing reviewed cleanup/reconciliation
requirements remain. Held simulator intents stop new work and require review.

Requires the existing Windows XAMPP installation under C:\xampp (PHP modules incl.
GD), Python, Node and ffmpeg. Existing locked extractor HTTP dependencies are reused
from owned `.runtime/audit-*/extractor-deps` after lockfile comparison. No dependency
or browser download occurs at startup. For a clean machine, copy the package.json
and package-lock.json from `docs/recovery/tests/extractor-http` into the ignored
`.runtime/native-local/dependencies` folder and explicitly install using
`npm.cmd ci --prefix .runtime/native-local/dependencies --ignore-scripts --no-audit --no-fund`
before starting. Dependency installation requires network access. The default
simulator works locally; privately approved real WhatsApp requires internet access.

The launcher refuses occupied/unknown ports (8088,3307,11025,9081,3334,9090), never
restarts Docker globally, and uses its own PHP ini rather than global XAMPP settings.
Native fake SMTP uses 11025 so Docker/other mail tools can keep 1025. An older owned
native configuration moves to 11025 only during a stopped start; accounts, keys,
uploads and database remain intact. The Docker profile keeps its original 1025.
Do not also start the website-only audit fixture on these same ports. New source
is mirrored on a normal stop/up; user uploads and DB records are preserved.

Verification commands:

```powershell
python -X utf8 dev/test_native_guards.py
python -X utf8 dev/test_native_system.py
python -X utf8 dev/test_native_baseline.py
node dev/native_browser.cjs <absolute-existing-playwright-package-directory>
```

Browser checks require an existing Playwright package and Chrome. Read
[whole-local evidence](../docs/recovery/LOCAL_WHOLE_SYSTEM.md) for exact results,
profile boundaries and the active runtime. These suites cover fictional substitutes
and refuse a live WhatsApp profile. Separate owner-approved WhatsApp evidence is
in `docs/recovery/REAL_WHATSAPP_LOCAL.md`; production remains disabled.

---

# Isolated local development

For the selected Stellar shared-host layout, use `python -X utf8 dev/prepare_stellar.py`
and `python -X utf8 dev/test_stellar.py`. The generated ignored bundle is locked,
has separate private placeholders and never uploads/deploys. The optional locked-
Apache checks are `python -X utf8 dev/rehearse_stellar.py --native` (Windows XAMPP).
The delivered rehearsal does not depend on Docker. See ../ops/staging/stellar/README.md for actual
account/handler/private-extractor and recovery limits. The standalone
`node ops/bot-readiness.cjs` must run on the actual bot machine for meaningful sizing.

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

## Fresh-launch audit checks

For the owner's website-hosting/personal-bot topology, run
`python -X utf8 dev/test_two_host_guard.py` and
`python -X utf8 dev/rehearse_two_host.py`. The latter creates separate local network
namespaces, actual authenticated TLS/PHP/SQL and fake deliveries, restarts both
sides, and streams a 2 GiB fictional file. No host ports/provider route or SDK.
It uses current development source, preserves owned evidence/volumes and removes
only owned containers/network. See ../docs/recovery/TWO_HOST_LOCAL.md for limits;
it is not an exact Namecheap/cPanel deployment or real media acceptance.

Launch scope is free access and WhatsApp-only full movies/episodes; website
shorts/trailers remain. See `../docs/recovery/PRODUCTION_AUDIT.md` for executed
results, outstanding blockers and the approval-only live test plan.

After `python dev/local.py up`, run these sequentially from the workspace root:

```powershell
python dev/local.py test-security
python dev/test_admission_concurrency.py
python dev/local.py test-product
node dev/bot_integration.cjs
python dev/route_smoke.py
python dev/local.py test
python dev/local.py test-operations
python dev/local.py test-quarantine
python dev/verify_environment.py
python dev/local.py down
```

Each suite exits nonzero on failure. Product/SQL bridge tests guard the exact owned
gateway/database and create fictional records, never production data. Product
fixture JSON includes a delivery bearer and must stay ignored/private; report
only projected test labels/booleans/counts. Tests do not start Baileys. Repeat only
affected suites after changes; use fresh fixtures for clean-install verification.

The product fixture additionally uses `fixtures/motion-demo.mp4.b64`, an original
102,793-byte, four-second FFmpeg 8.0.1 `testsrc2=size=160x240:rate=24` clip encoded
with libx264/yuv420p/faststart and no audio. The older red-frame fixture is retained.
Generated fixture rows/media accumulate within the owned volume and quota. Use
the reviewed inventory/quarantine runbook; do not erase volumes to conceal failures.


## Private extractor fixture

The default stack still has EXTRACTOR_MODE=disabled. To rehearse the private
administrator connection with the actual local Node fake:

```powershell
python dev/local.py up
python dev/local.py extractor-up
python dev/local.py test-extractor
python dev/local.py down
python dev/local.py up
```

The separate override mounts a generated independent secret and exposes no Node
port. No provider request/browser starts. Do not run operator mutation tests in
the opt-in mode; their strict disabled-adapter checks remain. Test seed JSON has
private bearer material and stays ignored. See ../docs/recovery/EXTRACTOR_CONNECTION.md.

## Isolated staging rehearsal

See [the staging rehearsal](../docs/recovery/STAGING_REHEARSAL.md). Run
`python -X utf8 dev/test_staging.py`, then `python -X utf8 dev/rehearse_staging.py`.
It exports committed source plus hashed rehearsal templates, creates two new local
fictional targets with no published ports, verifies TLS/security/load, encrypts a
quiesced snapshot and restores/comparisons it. All providers stay disabled; this
does not provision a host or approve a release. Private evidence/keys/volumes remain
under ignored state; never publish the SQL/archive/session fingerprints. The backup
helper is capped at 64 MiB and is only for fixtures. No volume cleanup is automatic.

The promotion follow-up uses `python -X utf8 dev/rehearse_promotion.py` against the
reviewed Git index tree. It adds production-config quota checks, the private Unix
extractor, streaming SQL/media/ZIP64/encryption/local mirror/restore and bounded
local alerts. See [the operator contract](../ops/README.md) and
[promotion evidence](../docs/recovery/PROMOTION_CONTROLS.md). No live adapter,
production path, external backup/alert destination or publishing is enabled.

Local account-adapter and worker pressure tests are documented in
[Stellar adapters](../docs/recovery/STELLAR_ADAPTERS.md).
Use `python -X utf8 dev/test_stellar_adapters.py`,
`python -X utf8 dev/rehearse_stellar_adapters.py` and the Node
`docs/recovery/tests/staging-worker.test.cjs` suite from the checkout root.
These paths use only fictional scratch state/loopback services. They do not enable
account access, provider extraction, real delivery, backup transport or deployment.

For the latest localhost acceptance preview, see
[LOCAL_ACCEPTANCE.md](../docs/recovery/LOCAL_ACCEPTANCE.md). The native fixture now
uses private PHP/GD settings and generates its own PNG/MP4; global XAMPP settings
are unchanged. Browser tests require an existing Playwright package and Chrome.
The owner has not purchased Stellar; no actual hosting/provider test is claimed.
