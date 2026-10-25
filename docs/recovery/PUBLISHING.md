# Initial source publication

Date: 2026-10-25. State: **IN PROGRESS**.

The owner explicitly requested staging, logical commits, branches, pushes to the
existing GitHub repository and pull requests for review into `main`. This is the
publication authorization for this phase. It does not authorize merging,
deployment, live integrations, credential rotation or contacting real users.

## Provenance and review structure

The starting repository contains only `README.md` at initialization commit
`f89da9b`; the project source is untracked. Existing files include previously
repaired behavior and recovery evidence. Import commits describe the source as
it exists today, use the configured owner identity and current timestamps, retain
upstream attribution, and do not reconstruct an unavailable development history.
Artificially splitting files into 100+ commits or branches would not establish
ownership or improve review.

Five branch checkpoints organize one ordered import series:

1. `codex/import-foundation`: exclusions, recovery rules, environment template,
   canonical and historical SQL, PHPMailer, shared PHP helpers.
2. `codex/import-website`: shared presentation, accounts, catalogue, Shots,
   comments, trailers, profile, starter pages and protected downloads.
3. `codex/import-admin`: administration layout/authentication, catalogue/content
   management, analytics/settings and disabled maintenance entry points.
4. `codex/import-adapters`: bot API, fake/live guards, bot modules/dependencies
   and extractor source.
5. `codex/import-evidence`: reproducible checks, historical and repair evidence,
   project documentation, provenance and this publication handoff.

All PRs target `main`. Branches form an ordered series: merge in the order above.
Later PRs initially contain earlier commits; their diffs shrink when earlier PRs
merge. Intermediate import checkpoints are not standalone runnable products.
Keep normal merge commits to retain the reviewed commit series. Nothing is
automatically merged or deployed.

## Publication preparation

- Preserved original material and tooling under ignored `.recovery-private/`.
- Reviewed `.gitignore` and kept environment secrets, sessions, authentication
  stores, logs, original SQL rows, uploads, installed dependencies and runtime
  fixtures outside the publication candidate.
- Excluded `whatsapp-bot-deploy/exports.txt`: a generated dependency-symbol
  diagnostic export, with no source reference found. The original is preserved.
- Excluded the empty root `package-lock.json`: zero packages and no matching root
  package manifest. Bot and HTTP-test lockfiles remain included.
- Replaced hardcoded support telephone numbers with optional
  `SUPPORT_WHATSAPP_NUMBER` configuration; empty configuration disables the four
  password-support launchers. The existing live-service gates remain active.
  Removed a fallback contact in the gated watch route and sanitized legacy
  documentation examples. No real recipient was contacted.
- Changed the illustrative token in an API comment to `DEMO_TOKEN` after
  identifying a Gitleaks false positive. No scanner finding was suppressed.
- Imported the extractor from a snapshot without its embedded `.git`, while
  preserving that local repository and its changes. No submodule pointer is
  intended in the parent repository.
- Replaced the mixed-encoding starter README with a current project entry point;
  the original bytes are retained privately and in the initialization commit.

## Verification and limits

Executed checks, source counts, commit boundaries and remote PR verification are
recorded in the final handoff below and in `PUBLISHING_RESULTS.json`.

Gitleaks 8.30.1 was downloaded from its upstream release and its archive matched
the accompanying SHA-256 checksum file. This verifies download consistency, not
independent binary authenticity. Initial scanning found one illustrative-token
false positive; the comment was changed rather than allowlisted. Supplemental
pattern review covered contact identifiers, credential assignments, SQL data
statements and remote media URLs. Historical migrations contain placeholder bot
health rows and procedure writes, not a production dump. Third-party contact and
license notices are retained.

Scans apply only to the explicitly selected source and new publication history.
Ignored private material is not a public scan target. Pattern checks cannot prove
the absence of every secret, personal datum or licensing issue. Source syntax and
fake-client tests do not certify live adapters or production readiness. Earlier
repair HTTP/browser evidence is retained as historical evidence; it is not
claimed as a new publication-phase runtime test.

## Handoff

Pending: staged-content checks, logical import commits, history scan, branch
pushes and PR creation. `main` must remain at its original head until owner merge.

Next recommended Codex task after owner review:

> Prepare a reproducible isolated development environment and fake mail/OAuth
> end-to-end tests using the canonical schema. Review upload handling and
> distributed token reservation. Preserve the publication history and recovery
> records; keep live integrations and deployment disabled.
