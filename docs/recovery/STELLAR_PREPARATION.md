# Stellar preparation and read-only bot readiness

Date: 2026-10-28. **Preparation complete within local scope; launch NO GO.**
Started clean at `12c9954758b9718cabe25816d328b28db0198582` on
`zinema/two-host-rehearsal`. This phase changes tooling/configuration candidates and
records, not application behaviour. Earlier source-publication commits/PRs and
local test evidence are preserved. No new commit, remote operation or deployment.

## Delivered artifacts

- `ops/staging/stellar/`: disabled candidate PHP, private bootstrap guard, strict
  CLI checker, CLI-only capability probe, user-INI template, Apache deny guard,
  commented scheduler plan and operational runbook.
- `dev/prepare_stellar.py`: exports a new ignored review bundle using committed
  allowlisted website source, separate private schema/placeholders and a standalone
  bot readiness tool. Current candidate/readiness bytes are hashed in its manifest.
  Refuses overwrite/escape/link paths and checks scratch space before creating it.
- `ops/bot-readiness.cjs`: standalone read-only, dependency-free Node inventory for
  the actual bot machine. It prints sanitized JSON and does not load the bot or
  preserved config, read credentials/sessions/media, enumerate directory contents,
  use network APIs, run speed/IO tests, write files or change settings.
- Regression coverage for metadata/path/privacy/argument handling, disabled flags,
  exports, bootstrap denial and local Apache access boundaries. Updated hosting
  metadata, launch checklist and handoff records.

The [runbook](../../ops/staging/stellar/README.md) gives exact commands/layout.
To collect the missing machine details, copy the single readiness file to the
**actual bot machine**, then run `node bot-readiness.cjs`. Optional local-media path
and owner-estimated upload/always-on values are documented there. Estimates remain
labelled unverified, storage permissions may yield unknown, and the report always
needs review. Developer-workstation results do not fill in unknown bot-machine data.

## Hosting conclusions

The owner-selected screenshot price remains US$2.28/month equivalent billed yearly,
not a verified checkout/renewal amount. No hosting account was accessed. Public
provider/PHP documentation was reviewed; it is capability/policy evidence only.

Namecheap documents PHP selector settings and a managed Node app feature. PHP's
`.user.ini` applies to CGI/FastCGI, not Apache module mode. This candidate avoids
assuming a specific handler; actual private prepend/session/cookie/CSP behaviour
must be verified before activation. A CLI extension flag is not web-SAPI proof.
[PHP INI rules](https://www.php.net/manual/en/configuration.file.per-user.php),
[Namecheap PHP configuration](https://www.namecheap.com/support/knowledgebase/article.aspx/9314/2219/how-to-edit-phpini-on-shared-servers/).

The provider prohibits unattached daemons and cron intervals below five minutes.
Its managed Node feature does not establish compatibility/permission for the
current standalone Unix-socket extractor. The hosting metadata now records that
adapter as unimplemented for the provider-managed model. No systemd/Docker/root
access, private socket permission or browser fallback is presumed on Stellar.
[Resource rules](https://www.namecheap.com/support/knowledgebase/article.aspx/157/22/do-you-have-any-server-resource-restrictions/),
[Managed Node setup](https://www.namecheap.com/support/knowledgebase/article.aspx/10047/2182/how-to-work-with-nodejs-app/).

The exported Apache policy denies all routes and the independent PHP prepend guard
returns 503 before the application. Activation/private environment injection is
intentionally not implemented in this preparation. No real credentials, origin,
account path, scheduler, quota, database or provider is activated. The proposed
5 GB uploads/0.5 GB quarantine remain unapplied. Existing local storage helpers do
not implement general shared-account production quotas. That implementation and
the managed/private extractor remain launch work, not merely final switch changes.

## Executed verification

Exact sanitized results and execution classifications: STELLAR_RESULTS.json.
Static/security scopes/limitations: STELLAR_CHECKS.json.

- Readiness regressions17 and retained pure configuration guards11 passed together
  (28 total). Tests exercise default unknowns, CPU/RAM/boot-age metadata, no identity/
  path/env leakage, estimate labelling, typed fields, fixed-drive queries, remote/
  automount/symlink rejection, unsupported/failing probes and fixed CLI errors.
- Candidate tests8 passed, including twelve safety-switch/path/scheduler mutation
  subcases. Existing artifact boundaries6, local target guards9 and two-host
  guards12 passed. Child/subcase counts are not added to suite totals.
- Windows collector smoke executed on this development workstation only. Default
  storage stayed unknown as intended; sandboxed CIM yielded unknown gracefully.
  The same read-only probe outside the sandbox collected local volume metadata.
  No report was represented as the owner's actual bot machine. Linux storage
  branches are fake-tested; actual Linux-machine collection is not claimed.
- Native loopback Apache verified denial of root, PHP/admin/API/static/INI/upload
  and private-layout paths using an owned-process response marker. PHP CLI prepend
  stopped before application bootstrap. Exported checker/capability probes passed.
  The exact owned process stopped and the ephemeral loopback port closed. No PHP
  web execution, database, provider request or application-data import occurred.
- Application, live SDK, full 2 GiB, browser/device/load and off-host recovery
  suites were not rerun; prior reports remain historical evidence. This phase's
  additions do not alter application entrypoints or the default disabled policy.

One initial bundle test compared raw Git LF bytes with the existing Windows export
CRLF policy and failed. It was corrected to compare normalized source while keeping
exact exported hashes; all eight final candidate tests pass. No application defect
was inferred from that test mismatch.

Docker became unresponsive during `docker create` for the optional locked-server
rehearsal. A bounded status query timed out; the exact owned Docker client was
identified by parent PID/case label and stopped. No `docker start` was issued by
that run. Its aborted report is retained, not counted as successful HTTP evidence.
No global engine restart or unrelated-container operation occurred. The Docker path
was removed from the delivered rehearsal. Native Apache supplies the guard proof,
with ten-second command bounds and a regression for timeout failure. Docker may retain stopped metadata for the labelled failed
case until its daemon is available; no running candidate server was started by it.

## Remaining work and next task

Owner follow-up now confirms the actual machine and supplies hardware/volume
metadata; see BOT_MACHINE_INVENTORY.md. CPU/RAM/volume inventory is owner-reported;
load capacity, folder use/writability, upload and continuous availability remain
unverified. Independent network and recovery information is still required.
Boot age or an owner estimate does not prove continuous uptime or upload capacity.
The report's Node major match is not a supported-patch/dependency certification.

The selected account still needs a separately authorized private capability review:
actual handler/versions/extensions, private paths, managed extractor, quotas/locks,
database privileges, cron, encrypted independent backups/key custody, restore,
alerts and private access control. Do not remove deny/503 guards to bypass these
gates. Real pairing/recipient/2 GiB provider tests and publishing require later
explicit approval; tiny-send approval does not authorize large-media stress.

Next recommended task: **Review the actual bot-machine readiness JSON and privately
obtained account capability report; implement the provider-approved extractor and
shared-host private bootstrap/storage/recovery adapters locally with fakes before
requesting any account-specific staging activation.** All external adapters,
production access, deployment and publishing remain disabled.
