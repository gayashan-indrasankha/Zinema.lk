# CI pipeline — ZL-13

Workflow: [ci.yml](../../.github/workflows/ci.yml). GitHub-hosted execution is
**PENDING**, because this task does not commit or push. Local execution of its
commands is evidence about the local environment, not a green GitHub Actions run.

## Pipeline contract

Pull requests, manual dispatch, and pushes to main/feat/fix/chore/docs branches
run one Ubuntu 24.04 job with a 30-minute timeout. Repository permission is only
`contents: read`; checkout credentials are not persisted. Checkout and setup-node
are pinned to verified immutable commits. No production secrets, artifacts,
deployment permissions or external service accounts are used. Fork PRs receive
no additional privileges; do not change this workflow to pull_request_target.

1. Check the local Linux Docker daemon, initialize unique CI configuration, and
   validate Compose without printing resolved credentials.
2. Scan reviewed text with digest-pinned Gitleaks 8.30.1. Scanner input is stdin;
   its container has no mounts or network and redacts all findings.
3. Build Apache/PHP and MySQL, then start the isolated CI project.
4. Parse reviewed PHP/JS and run the existing export and ZL-12 tests plus new
   environment/configuration/health tests inside the image.
5. Run `node scripts/database-test.cjs ci` on a new independently labelled tmpfs
   database: four-table migrations/ledger/locking, synthetic integrity, preserved
   PHP/offline Node probes, encrypted restore and outage recovery. Managed CI
   storage remains untouched.
6. Check HTTP containment, dependency availability, least privilege and the
   explicit schema-readiness hold.
7. Always stop only the owned CI project. Volumes are retained; hosted runner
   disposal supplies the eventual temporary-environment boundary.

The CI project gets a timestamp suffix. Port 8082 is loopback-only. Credentials
are freshly generated local values; neither repository secrets nor existing
workspace `.env` files are required. MySQL is internal. The web ingress network permits egress; disabled provider
flags and local-only configuration are the application boundary. Runner image
pulls/build dependencies also require network access.

## Local reproduction

```powershell
node scripts/environment.cjs doctor
node scripts/environment.cjs init ci
node scripts/environment.cjs config ci
node scripts/environment.cjs secrets
node scripts/environment.cjs up ci
node scripts/environment.cjs test ci
node scripts/database-test.cjs ci
node scripts/environment.cjs health ci
node scripts/environment.cjs audit ci
node scripts/environment.cjs down ci
```

`scripts/verify.cjs` needs no Git metadata in its image. It reports parser/test
counts and versions. Test fixtures are fictional and never boot a live bot.
The bot's lockfile root dependency maps are checked against its manifest; this
does not prove a complete reproducible npm installation. No provider packages
are installed/executed. The extractor has no lockfile, remains quarantined and
is not started. Full npm vulnerability assessment and bot/extractor compatibility
are NOT RUN. Composer is not introduced: existing PHPMailer source is preserved.

## Failure handling and limits

Failures stop the job; the final shutdown step still runs. Source/test diagnostics
are bounded, and resolved settings and raw logs are withheld. There are no uploaded
artifacts. For local diagnosis use profile `status`, `logs`, `health` and `audit`;
never dump the resolved Compose JSON or environment into an issue.

A persistent local checkout reuses its managed CI configuration and volume when
`init ci` is repeated. It is not automatically a fresh database. Hosted CI starts
from a fresh runner checkout and generates a new project identity. Do not delete
the local CI volume to imitate that boundary; fixture reset awaits ZL-14.

Schema readiness is expected to fail as an application state while the pipeline
checks that this failure is honest and stable. It is not a release gate proving
login, catalogue, playback or deployment readiness. ZL-14 must supply reviewed
schema/fixtures and later issues must extend actual business regression gates.
See [executed verification](ENVIRONMENT_OPERATIONS.md).

ZL-14 provides only a verified four-table subset. The full canonical schema and
application compatibility remain blocked. The current context contains exactly
four new reviewed SQL paths; private/historical SQL is excluded. See
[database tests](../database/SCHEMA_TEST_RESULTS.md).

## 2026-10-24 - ZL-15 local workflow extension

A separate content-mode disposable database step follows the original ZL-14 step:
node scripts/database-test.cjs ci content. It creates a different uniquely labelled
server/network and tests rights migrations, DML/audit atomicity, concurrent editors
and an excessive-privilege refusal. Unit discovery includes tests/content/.
The reviewed context now includes exactly six SQL files: the original four plus
two new workflow CREATE statements, each manifest/hash checked. Historical/private
SQL stays excluded. Managed CI volumes remain unmigrated and readiness stays 503.

The changes are uncommitted and unpushed in this implementation phase. Hosted
execution of this updated workflow is NOT RUN; local image execution is reported
in [content test results](../content/CONTENT_TEST_RESULTS.md). Existing hosted
passes for earlier commits do not verify this new step.

## 2026-10-24 - ZL-16 catalogue checks

Verification discovers tests/catalogue/*.test.cjs, including a loopback PHP HTTP
fixture with no legacy startup. A third fresh disposable database suite runs
node scripts/database-test.cjs ci catalogue after existing database/content steps.
It tests real prepared search SQL, policy filtering, reader privilege boundaries,
query plans, synthetic timing and outage/recovery. Its unique server/network is
removed in finally; managed volumes remain unmigrated. Context is 237 reviewed
files (234 text), still exactly six SQL paths. Current hosted execution is NOT
VERIFIED because these changes are uncommitted/unpushed. See
[local results and limitations](../api/CATALOGUE_TEST_RESULTS.md).

## 2026-10-24 - ZL-17 player and browser verification

The same verification runner now discovers tests/playback/*.test.cjs. Exact
hls.js 1.7.3 / playwright-core 1.64.1 test dependencies have a lockfile; the local
player vendor bytes have an independent SHA-256 notice and regression check.
CI adds script-free npm ci, a Playwright Chromium/dependency install, FFmpeg
installation, original lavfi fixture generation inside .recovery-private and
the actual local PHP/browser journey. No real media/provider/production secret
is needed. The generated bytes and node_modules never enter the application
image or Git. Context is 265 reviewed files, 262 text, still six SQL paths.

Current hosted execution is NOT VERIFIED (no commit/push). Host/image and local
Chrome results, native-versus-forced-MSE distinction and failure coverage are
reported in [playback tests](../streaming/PLAYBACK_TEST_RESULTS.md). Existing
database suites remain in CI; ZL-17 introduces no schema or database harness change.

## 2026-10-24 - ZL-18 quarantine checks

The same verification runner discovers tests/quarantine/*.test.cjs: mode parity,
pre-import operation tripwires, token/helper/callback checks, simulator and direct
GET/POST denials. Existing browser step also runs tests/quarantine/browser.cjs
after unchanged HLS verification. No bot/provider package or real credential is
needed. Managed context is 279 paths /276 text, still six SQL paths. The withheld
admin source stays excluded and tests distinguish its image absence from a real
handler denial. Dedicated local network-none execution verifies 134 contracts
without mounts or private sessions. Hosted CI for these uncommitted changes is
NOT VERIFIED. See [actual results](../security/QUARANTINE_TEST_RESULTS.md).

## 2026-10-24 - ZL-19 identity checks

The same verify runner discovers tests/identity/*.test.cjs. It executes independent
PHP contracts, a real loopback PHP session fixture and actual legacy signup with
synthetic dependencies. The existing browser step adds tests/identity/browser.cjs
using the already pinned Playwright toolchain. No new package or hosted workflow.
The exact image includes 297 reviewed inputs, still the same six verified SQL paths.
No application account schema or real identity credentials enter the fixture.
Local results and unperformed hosted/production gates are recorded in
[authentication test results](../security/AUTH_TEST_RESULTS.md).
