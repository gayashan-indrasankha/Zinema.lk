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
