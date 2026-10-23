# Recovery status

Updated: 2026-10-23.

## Current phase

Owner-requested GitHub source-publication phase: PARTIAL. The requested seven
branches and seven open, non-draft PRs are published for owner review. The final
handoff brings the new Conventional Commit count to 25. Syntax and secret scans
passed; the inherited whitespace-check findings are retained and unresolved.
Application recovery, schema compatibility and runtime readiness are unverified.
No historical numbered phase is assumed complete. No PR was merged or deployed.

### Completed publication work and files

- Added exclusions, line-ending rules, contribution/PR guidance and a source
  syntax script; expanded the root README and recovery records.
- Imported 259 tracked files across shared website foundations, accounts/Shots,
  catalogue/playback, administration, delivery and extraction. Preserve the
  existing source layout; detailed commits/PRs are indexed in `PUBLISHING.md`.
- Removed embedded contact numbers and a roadmap contact address; disabled empty
  support destinations. Replaced sample token/message identifiers, corrected
  example local paths and labeled historical READMEs. Originals are preserved
  outside Git. No credentials were rotated or live functionality enabled.
- Preserved the modified extractor repository's metadata locally before importing
  its reviewed current files. Its previous history is not part of this publication.
- Corrected contributor wording to describe optional strict PHP checking without
  implying a CI workflow exists. CI and dependency auditing remain future work.

### Executed verification and evidence

- **Statically inspected:** explicit staging manifests/diff summaries and filtered
  configuration/contact findings before every commit. Not an exhaustive security
  or line-by-line review of every legacy feature.
- **Executed syntax checks:** Node 24.13.1 `--check`, 34 JavaScript/CJS files,
  zero failures; PHP 8.2.12 `-n -l`, 164 PHP files, zero failures. Checked each
  cumulative tracked snapshot before committing. No application modules executed.
- **Executed data-format checks:** all six tracked JSON files parsed successfully.
- **Executed security checks:** Gitleaks 8.30.1 on every exact staged snapshot and
  the complete 24-commit outgoing source/workflow history before initial push;
  zero remaining findings. The handoff snapshot is checked before its commit;
  outgoing history is scanned again before its push. Scanner archive SHA-256
  matched the official release metadata. Default rules used; no finding allowlist.
- Supplemental staged checks rejected private paths, Git links, numeric contact/
  group identifiers and invitation links. Checked credential-bearing URL patterns:
  no findings. Verified ignore rules for environments, dump, sessions, exports and
  private preserved metadata. Only product logo/icon binaries were imported;
  no movie, episode, user-upload or demonstration media was added.
- **Whitespace check: NON-CLEAN.** `git diff --cached --check` reported 1,798
  inherited trailing-whitespace/EOF warnings across the 24 import commits. No
  conflict-marker finding. Source whitespace was preserved to avoid changing
  legacy string/template content without behavior tests. This unresolved check
  is why the phase remains PARTIAL; it is not reported as a pass.
- **Test-covered:** feature regression coverage not established; no functional
  test suite executed. **Runtime verified:** no application behavior.
  **Externally verified:** GitHub repository metadata, successful branch push and
  creation of seven open PRs. No database, WhatsApp, email, payment, production
  infrastructure or media source was contacted.
- Limitations: scanners can miss secrets; no dependency vulnerability, full
  security, browser, performance, database compatibility or media-rights audit
  completed. Source syntax does not establish a secure or runnable application.

### Git and handoff

Starting root commit: `e2c239d7e0fc608d96e2623227a727ba3286b64d` on main.
Source-import boundary: `375a989aa413a02cf4d909b5fab8ec6576c09950`.
All seven source branches were pushed successfully with an atomic push. The
handoff commit remains on `feat/video-extractor`, updating PR #7. Its exact hash,
final clean-status check and remote-state verification are reported at handoff;
the private local execution ledger also records each commit and check.

Review [PR #1](https://github.com/gayashan-indrasankha/Zinema.lk/pull/1) through [PR #7](https://github.com/gayashan-indrasankha/Zinema.lk/pull/7) in the order in
`PUBLISHING.md`. Use merge commits to retain history and verify/retarget each
successor after merging its base. No merges were performed. Ignored legacy data,
originals and scanner reports remain only on the owner's machine.

### Unresolved risks and next recommended task

Inherited whitespace findings remain unresolved. Existing runtime/schema/security
risks from the discovery below remain. Do not enable live integrations or treat
historical SQL as setup instructions. Publishing source is not production approval.

Exact next recommended Codex task after owner PR review:

"Perform a read-only reconciliation of Zinema's existing recovery changes and SQL
generations. Produce a canonical schema proposal, route/bootstrap map and
prioritized defect list with source evidence. Include a behavior-safe proposal
for the retained whitespace findings. Do not import data, start live integrations
or change application behavior."

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
