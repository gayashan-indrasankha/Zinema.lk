# Initial source publication

Date: 2026-10-25. State: **PARTIAL — publication workflow prepared; legacy whitespace check unresolved**.

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

Prepared **36 conventional commits**, including this final handoff, on five
ordered branches. All PRs are open, ready for review and were externally verified
as mergeable into `main` before this handoff update. Merge in this order:

| Order | PR | Added commits in this checkpoint |
| --- | --- | --- |
| 1 | [Foundation](https://github.com/gayashan-indrasankha/Zinema.lk/pull/1) | 7 |
| 2 | [Website](https://github.com/gayashan-indrasankha/Zinema.lk/pull/2) | 11 |
| 3 | [Administration](https://github.com/gayashan-indrasankha/Zinema.lk/pull/3) | 4 |
| 4 | [Adapters](https://github.com/gayashan-indrasankha/Zinema.lk/pull/4) | 7 |
| 5 | [Tests, evidence and documentation](https://github.com/gayashan-indrasankha/Zinema.lk/pull/5) | 7 |

The first 35 import commit hashes and file boundaries are in
`PUBLISHING_RESULTS.json`, through `944116cc5a7734d0afc16bc0ec91019423c22da1`.
This final handoff commit is discoverable in the file's Git history and the
execution handoff. `main` remains at
`f89da9b61fe92f4023e773b7965a02d4bdd72721`; nothing was merged.

- **Statically inspected / syntax validated:** selected publication files,
  staged paths/diff statistics and source/index hash matches; PHP **167/167**,
  JavaScript/CJS **38/38**, JSON parsing successful. Counts cover selected source,
  excluding ignored historical programs/dependencies; they differ from earlier
  broader repair syntax inventories. No exhaustive new code audit is implied.
- **Test-covered and executed:** configuration **10/10**, fake-client bot
  **12/12**, exit 0. Both suites repeated before each of the 35 source import
  commits against the complete local candidate. Intermediate checkpoint branches
  do not yet contain every test/application file.
- **Secret/data checks executed:** 35 staged scans and the complete new history
  scan exit 0, zero findings; 12 private-path ignore assertions and three public
  template assertions passed. No embedded gitlink is present. Private source
  originals, SQL rows, media, credentials and runtime artifacts are preserved
  locally. The final documentation update is scanned again before commit/push.
- **Unresolved check:** the full import whitespace check exits **2**, with
  **1,709 findings across 111 files**, affecting 28 import groups. These are
  retained legacy formatting findings. The phase remains **PARTIAL** for this
  unresolved check; functional checks and secret scans passed. A broad formatting
  rewrite was kept outside the import, and no failed check is described as passed.
- **Externally verified:** pushed heads matched local branch checkpoints; all
  five PRs were open, non-draft, mergeable and targeted `main`. No automated CI or
  production check is claimed. Final post-handoff head verification is reported
  in the execution handoff.
- **Runtime limits:** HTTP/database/browser checks were not repeated in this
  publication phase. Their earlier repaired-source evidence remains in the
  repair reports. Optional support-contact configuration is syntax inspected;
  live support contact was never exercised.

Working tree was clean after the 35 import commits and before adding this
handoff. The final commit/push must leave it clean on `codex/import-evidence`.
Ignored private/runtime files intentionally remain local.

Next recommended Codex task after owner review:

> Prepare a reproducible isolated development environment and fake mail/OAuth
> end-to-end tests using the canonical schema. Review upload handling and
> distributed token reservation. Preserve the publication history and recovery
> records; keep live integrations and deployment disabled.
