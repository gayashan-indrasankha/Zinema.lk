# Zinema recovery engineering rules

These rules apply to this workspace and all subdirectories. Recover the legacy
Zinema project through small, evidence-driven phases into a secure, reproducible,
professionally engineered, CV-ready project. Existing code is evidence, not proof
that its behavior is correct.

## Current scope

The initial authorization covered AGENTS.md/recovery-workflow setup. Subsequent
owner requests authorized static project discovery and, on 2026-10-23, a reviewed
GitHub import using feature branches, incremental commits and open PRs. The GitHub
request authorizes publishing reviewed, sanitized source to the owner's existing
repository; it does not authorize merging PRs, deployment or production access.
Limit changes in this publishing phase to repository workflow, documentation and
publication sanitization. Application repair and database reconstruction require
a separate owner request. Do not infer completion of historical numbered phases.
Future work may include architecture and behavior documentation, defect repair,
security hardening, database reconstruction, automated testing, development
environments, Git, CI/CD preparation, and portfolio documentation.

## Before every phase

1. Inspect current Git status. If the working directory is not a Git repository,
   record that fact; do not treat a failed status command as a clean working tree.
2. Read `docs/recovery/STATUS.md` and `docs/recovery/DECISIONS.md`.
3. Confirm the requested phase has not already been completed and identify its
   authorized scope, existing changes, unresolved risks, and blockers.
4. Continue from the existing state. Do not restart or overwrite prior work.

## Evidence and verification

- Never claim something works without an appropriate executed test. Distinguish
  **statically inspected**, **test-covered**, **runtime verified**,
  **externally verified**, and **not yet verified**. Test coverage alone does not
  mean that a test passed; record execution and results separately.
- Document intended behavior and evidence before changing legacy behavior. Prefer
  small, understandable changes over large rewrites. Preserve observable,
  verified behavior during refactoring.
- Add regression tests for confirmed defects wherever reasonably possible.
- Finish every phase with verification appropriate to its scope. Investigate
  failures; if unresolved, mark the phase **PARTIAL** and document them. Never
  hide failures or label unperformed checks as successful.
- Use only fictional/sample accounts and legally usable demonstration media.
  Never contact real users while testing.

## Secrets, data, and external services

- Never expose secret values in output or put them into Git. Mask passwords,
  API keys, OAuth secrets, database/email/WhatsApp credentials, tokens, and session
  identifiers. Avoid commands that dump potentially sensitive configuration,
  logs, environment variables, database contents, or session files. Report
  findings using paths, categories, and redacted evidence.
- Never connect to or modify production infrastructure without explicit owner
  authorization. This includes production databases, production email, live
  zinema.lk infrastructure, important WhatsApp accounts, production WhatsApp
  groups, cloud hosting, and DNS. Legacy configuration is not authorization.
- Use isolated local services and sanitized development/test databases. Verify
  the target before any database operation. Never run `DROP DATABASE`,
  `DROP TABLE`, `TRUNCATE`, mass `DELETE`, or equivalent destructive operations
  against an unknown or production-like database.
- Never destroy legacy information just because it looks obsolete. Before
  deleting or replacing important files, determine their purpose, check their
  references, preserve evidence when necessary, and explain why they are obsolete.
  Keep sensitive preserved evidence out of Git and public documentation.
- Treat WhatsApp as an optional delivery adapter, not as a permanent CDN. Real
  WhatsApp access requires explicit authorization; use local substitutes in tests.

## Authorization boundaries

Within an authorized phase, safe local work may proceed autonomously: reading
source, searching files, documenting findings, modifying development code,
installing reasonable development dependencies, running tests and static/security
checks, running local Docker services, creating sanitized test databases, and
using local Git with verified commits. This does not expand the requested phase.

Stop and obtain explicit owner authorization before:

- Deleting irreplaceable project data.
- Rotating credentials.
- Contacting real WhatsApp or connecting to production.
- Creating a public GitHub repository or pushing the first remote repository.
- Deploying anything or spending money on hosting/services.

Do not push anything to GitHub before the explicit publishing phase. Do not
automatically cross irreversible approval gates. Prepare safe, concrete,
reviewable local work before requesting approval. Authorization already given
for a specific action need not be requested again, but do not infer production
or publishing authorization from permission to perform local recovery.

## Git discipline

- Preserve existing user changes. Do not stage unrelated files or use blanket
  staging without reviewing what it includes.
- Before every commit: run `git status`, inspect the working and staged diffs,
  and run relevant tests and static/security checks. Use redacted inspection
  methods when a diff could contain secrets; do not print raw secrets.
- Before the first commit and every release: inspect staged files, inspect
  `.gitignore`, run available secret scanning, and check for production data,
  credentials, session material, and private media. Record scanner availability,
  scope, limitations, and findings; absence of a scanner is not a passed scan.
- Commit only when the phase is internally consistent. Use traceable,
  Conventional Commit messages where practical, for example
  `chore(security): sanitize legacy configuration`,
  `build(dev): add reproducible development environment`, or
  `docs: prepare portfolio documentation`.
- Never rewrite or amend previous commits merely to hide a mistake. Explain
  mistakes and their correction. Record the resulting commit hash when applicable.

## Recovery records and handoff

Maintain `docs/recovery/STATUS.md` at the end of every phase with:

- Current phase and completion state.
- Completed work and files changed.
- Checks/tests executed, their results, and evidence classifications.
- Security checks and their limitations.
- Git status and commit hash, if applicable.
- Unresolved risks, blockers, and next recommended phase.

Maintain `docs/recovery/DECISIONS.md` for important architectural and workflow
decisions, including context, rationale, consequences, and verification status.
Do not present assumptions as established architecture or verified behavior.

At handoff, report what changed, verification and its limits, anything requiring
the owner's attention, and the exact next recommended Codex task. Keep these
records sanitized; never include secrets or production data.
