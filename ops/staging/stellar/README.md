# Disabled Stellar staging candidate

This folder prepares review files; it does not activate, upload, install or deploy.
The owner selected Stellar annual hosting (screenshot US$2.28/month equivalent),
with the bot and full films on a personal machine. Actual account capabilities and
bot-machine inventory is owner-reported; sustained capacity remains unverified. Keep all provider/production/publishing
switches off. No account credentials or real domains belong in these templates.

## Prepare and check locally

```powershell
php ops/staging/stellar/check.php
python -X utf8 dev/prepare_stellar.py
python -X utf8 dev/test_stellar.py
python -X utf8 dev/rehearse_stellar.py --native
```

Use the full local PHP executable path if it is not on PATH. The native rehearsal
uses the existing Windows XAMPP Apache/PHP, a newly chosen loopback port and no
database. It checks owned 403 responses and explicit PHP CLI prepend behaviour,
then stops its exact owned process. The default command and `--native` both use
this native runtime with bounded local-command timeouts. No Docker dependency,
engine restart or image download is involved in the delivered rehearsal.

The exporter writes a **new** ignored `.runtime/stellar-candidates/...` bundle and
retains a separate source proof. It refuses existing targets, path/symlink escapes
and insufficient scratch space. Website bytes come from committed HEAD through
the existing allowlist exporter; candidate files/readiness tool are current hashed
sources. The manifest records provenance and every output hash. No remote Git,
provider, account, schema execution or real credential loading occurs.

Layout:

- `public_html/`: website-only source, unconditional Apache deny rule and `.user.ini`.
- `zinema-private/staging/`: disabled PHP candidate/bootstrap/checker/capability
  probe, data-free canonical schema and commented scheduler notes.
- `bot-tools/bot-readiness.cjs`: standalone read-only tool for the **bot machine**.

`public_html` is a layout label, not permission to overwrite an account's live
document root. Private configuration must remain outside the actual staging
document root. No Node backend, installed dependencies, dev routes, legacy data,
original full media, credentials/session store, private key or logs are exported.

## What stays disabled

The Apache guard denies every URL, including static assets. The independent PHP
prepend guard returns 503 before loading configuration or application code.
The validated private runtime adapter is implemented; no runtime-config.php is distributed and activation remains disabled. This is a locked
review bundle, not a reachable staging site. Do not remove these controls merely
to make a page load; any private deployment needs its own reviewed account-specific
bootstrap, private access control, credentials and authorization.

All integration adapters, live switches, fixture switches, backups, collectors,
external alerts and production upload-quota activation stay disabled. Proposed
5 GB managed uploads plus 0.5 GB quarantine are not applied quotas. The account-owned quota/quarantine adapter is locally fake-tested; actual account permissions, filesystem locks and capacity must still be verified before enabling uploads.
Full 2 GiB movies are neither website uploads nor website backup payloads.

## Account capability gates

Official sources were reviewed on 2026-10-28. They guide this candidate but do not
verify the selected account, provide policy approval or authorize connections.

- The provider's PHP selector/configuration guidance must be matched to the actual
  PHP handler. `.user.ini` is processed by CGI/FastCGI, not Apache module mode,
  and changes may be cached. Do not combine incompatible `php_value` directives
  blindly. Verify prepend behaviour in a private staging origin before any access.
  [PHP per-directory INI](https://www.php.net/manual/en/configuration.file.per-user.php),
  [Namecheap PHP configuration](https://www.namecheap.com/support/knowledgebase/article.aspx/9314/2219/how-to-edit-phpini-on-shared-servers/).
  Error/session paths are private placeholders. `expose_php` is a php.ini-only
  setting and is deliberately not placed in `.user.ini`; configure it through the
  provider and verify actual headers. The Apache candidate also removes the
  version header when mod_headers is available.
  [PHP core settings](https://www.php.net/manual/en/ini.core.php).
- Verify a patched compatible PHP version, 64-bit integers, mysqli/pdo_mysql,
  GD, fileinfo, OpenSSL, cURL, mbstring, JSON and sessions. Keep session paths
  private, confirm open_basedir/private-path permissions, exact HTTPS origin,
  cookies, proxy identity and CSP. Never publish phpinfo or environment dumps.
  After separate account-access authorization, the private CLI-only
  `host-capabilities.php` prints only version/module flags. CLI results do not
  establish the web handler, its INI settings or account permissions.
- cPanel's documented Node feature is provider-managed. The provider disallows
  unattached daemons; this project's standalone Unix-socket extractor is not a
  shared-host deployment. The new PHP queue/personal-machine outbound worker is locally fake-tested; real provider extraction and actual account transport are unverified. No raw API,
  tunnel, background daemon or browser fallback is enabled.
  [Node application feature](https://www.namecheap.com/support/knowledgebase/article.aspx/10047/2182/how-to-work-with-nodejs-app/),
  [Provider resource rules](https://www.namecheap.com/support/knowledgebase/article.aspx/157/22/do-you-have-any-server-resource-restrictions/).
- Provider cron intervals must be at least five minutes, with no more than five
  simultaneous jobs. The commented plan proposes one non-overlapping collector
  per five minutes and hourly database/state backup; no cron entries are supplied
  or installed. Bot heartbeats originate from the personal process, not cron.
  Account-approved private CLI bootstrap, locking, redacted logs, key custody,
  bounded retention, independent encrypted copies and timed restore remain gates.
  The local-only operator tools are not automatically portable account cron jobs.
- Shared account storage includes other sites, database, mail, logs and backup
  files. Confirm inode/CPU/RAM/process/request limits, real usage, off-host recovery
  and alert delivery. Docker/native tests do not certify provider capacity.

## Bot-machine readiness

Copy `ops/bot-readiness.cjs` (or the bundle's copy) to the actual bot machine and run
it with a normal trusted Node installation. It has no project dependencies:

```powershell
node bot-readiness.cjs
node bot-readiness.cjs --media-root "D:\bot-media"
```

The second path is an example: use the existing **local** media directory on that
machine. On Linux use an absolute local path such as `/srv/zinema/media`. Do not
point it at network shares. No directory contents or media bytes are read. A
Windows local drive-type query uses noninteractive PowerShell/CIM with a hidden
window; denied permissions/nonstandard Windows installation yield unknown storage.
Linux checks the local mount table before lstat/statfs and rejects remote/automount
filesystems and symlink components. Other systems keep storage unknown.

Output contains OS family/release/architecture, logical CPU count, RAM, current
boot age, Node version/baseline and optional volume free space. It omits hostname,
username, device serials, network addresses, media paths, credentials, sessions and
environment values. It never loads the bot, imports an SDK, contacts an API, runs
a speed/IO benchmark, tests writes, changes power settings or creates a report file.
It prints JSON to stdout; save/share that sanitized output only if desired.
It reports volume space, not application quotas or tested writability.

Optional `--upload-mbps 10 --always-on yes` records owner estimates **as unverified**.
Those options do not measure upload or continuous availability. Without them the
fields remain unknown. Exact 2 GiB transfer time is only an ideal calculation;
provider/account/codec/byte-limit acceptance is still unverified. A Node 24 major
match is the locally tested baseline, not proof of a current security patch.
Developer-workstation or container smoke runs are never substituted for the actual
bot machine's report, and every report remains `needs_review`.

Next: collect that actual-machine summary and review account capabilities privately
under separate authorization; resolve the managed extractor and recovery/monitoring
requirements before any approved staging activation or capped live tests.

## Implemented local account adapters

See [adapter evidence](../../../docs/recovery/STELLAR_ADAPTERS.md). The runtime
loader reads only a fixed private `runtime-config.php`, validates private/public
separation, exact owner/worker client IPs, account DB naming, HTTPS, disabled
providers and private state paths. `runtime-config.example.php` is disabled and
contains placeholders. Linux requires account-owned 0700 private directories and
0600 configuration; verify Windows ACLs separately. Proxy headers are ignored.
Create sessions/storage/extractor/logs outside public_html during an approved
future setup; this code never creates or contacts an account.

The fictional local harness explicitly selects `local-fixture`, a loopback origin,
an isolated test DB and fresh scratch folders. It removes only the blanket deny
inside that owned copy, retaining private-file restrictions. This is never a
command for activating an actual account. The default exporter remains locked.

`operator.php` is private CLI only, reads bounded JSON from stdin and supports
initialize, inventory and the existing reviewed quarantine/restore/recovery/history
operations. The original local-only CLI guard remains in place. Actor/evidence
labels are still fictional (`demo_`, `fixture:`); no real maintenance action or
permanent purge is enabled. Account uploads remain <=20 MiB; full content remains
on the personal machine. State and quarantine consume the account budget too.

The browser queues/polls extraction with admin authentication and CSRF. A separate
64-hex worker key authenticates `api/extractor-work.php`; it never appears in HTML.
The queue has <=32 retained jobs, one active claim, 60-second claim / 120-second
work lease, 15-minute queued expiry and a 90-second heartbeat freshness window.
Stale claims, invalidated media or uncertain results stay held; no automatic
replay. Reviewed CLI archival uses current revision, worker-stop attestation and
bounded persistent audit. At 128 audit events the ledger stops for archival review;
there is no automatic deletion/rotation. Filesystem locks are cooperative and
single-account/single-filesystem only, not distributed coordination or DR proof.

`fb-video-api/outbound-worker.cjs` is executable only for explicit local fixtures
under this checkout's `.runtime`. It opens no inbound listener and performs no
provider fetch. Its private durable intent is written before extraction; restart
or lost acknowledgement blocks new work. A leftover worker lock also blocks work.
After a crash, stop workers, review website inventory/audit and preserve the local
intent/lock as evidence. Start a fresh owned fixture directory only after explicit
review; do not delete an uncertain intent or resume it automatically. The fixed-origin HTTPS transport policy has fake request tests (TLS verification, no redirect/proxy, bounded body/deadline); its external switches remain off. Real HTTPS acceptance, provider extraction and host supervision remain approval gates.

Run the local regressions from the checkout:

```powershell
python -X utf8 dev/test_stellar_adapters.py
python -X utf8 dev/rehearse_stellar_adapters.py
node --test --test-isolation=none docs/recovery/tests/staging-worker.test.cjs
```

The web rehearsal needs free loopback ports 8088/3307 and an additional chosen
port, existing XAMPP/Node/ffmpeg, and scratch space. It refuses unknown listeners,
creates a fresh DB and generated clip, and stops only owned processes. Its native
mod_php prepend does not certify CGI/FastCGI `.user.ini` on Stellar. No cron,
external monitoring alert, remote backup transport or real staging deployment is
installed. Independent backups and sustained upload remain unverified.
