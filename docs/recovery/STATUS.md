# Recovery status

Updated: 2026-10-23.

## Current phase

Owner-requested GitHub source-publication phase: IN PROGRESS. Authorized scope:
reviewed source import, publication sanitization, logical branches, incremental
commits and open PRs. No merges, deployment, production access or application
recovery is authorized by this phase. See `PUBLISHING.md` for review order.

Current starting state: root Git repository on `main`, tracking `origin/main` at
`e2c239d`; only README tracked. Existing application and recovery files are
untracked. Earlier findings below describe the prior discovery, before the owner
initialized the root repository. A modified nested extractor repository was
preserved locally before its reviewed source import; no nested bot repository is
currently present. No historical numbered phase is assumed complete.

## Previous completed phase

Owner-requested project-understanding and description phase: COMPLETE within
static-discovery scope. Application recovery, remediation and runtime readiness
are NOT complete. The following historical checks are not current publication
verification; the final publication record will state fresh results.

## Starting state

- Workspace root is not a Git repository; failed root status was not treated as clean.
- `docs/recovery/STATUS.md` and `DECISIONS.md` did not exist when checked.
- Nested bot repository status initially failed Git's ownership check. A command-scoped `safe.directory` exception allowed read-only status; no global Git configuration was changed.
- Existing bot modifications: `.env.example`, `.gitignore`, `config/api-client.js`, `config/database.js`, `config/file-manager.js`, `config/multi-tier-forwarder.js`, `index.js`; untracked `config/environment.js`. Preserved without staging or changes.
- Existing source already includes recovery guards. Their origin and historical phase completion have not been established.

## Completed work and changed files

- Traced the principal website, account, Shots, catalogue, playback, administration, bot and extractor paths.
- Inspected dependency manifests and SQL structure without importing SQL or displaying data rows.
- Added `PROJECT_DESCRIPTION.md`, this status record and `DECISIONS.md`. No application source changed.

## Checks and evidence

- Git status: root not a repository; nested bot changes recorded above. No commit created; commit hash not applicable.
- Static source inspection: completed for the main subsystems and selected implementation paths; not an exhaustive line-by-line audit.
- JavaScript syntax: `node --check`, 31 selected standalone files, 31 passed, zero failures. Excluded dependencies, minified files, historical bot variants, scheduler sample and `refresh-method-new.js`.
- PHP lint/runtime: not performed; PHP unavailable on PATH.
- Test-covered: feature test coverage not established. No application regression suite executed.
- Runtime verified / externally verified: no application features. No services started, database operations or external connections performed.
- Security: used filtered reads, omitted sensitive contexts, avoided session/environment/log dumps and SQL data. gitleaks/trufflehog unavailable on PATH; no dedicated secret scan passed or claimed. Not a comprehensive vulnerability or privacy audit.

## Unresolved risks and blockers

- Inconsistent PDO/MySQLi bootstrap paths and code/schema mismatches need reconciliation.
- Existing quarantines interrupt download/payment flows; WhatsApp and extractor recovery gates alter legacy behavior.
- Sensitive legacy configuration, session directories and logs exist; do not publish without a later authorized sanitization and review.
- Existing application changes lack corresponding recovery records. Root version history is absent.
- No isolated runtime/database fixture has been established or tested in this phase.

## Next recommended phase

Read-only recovery-state and canonical-schema reconciliation. Exact next owner task is recorded in `PROJECT_DESCRIPTION.md`. Do not proceed automatically into remediation, database reconstruction or publishing.
