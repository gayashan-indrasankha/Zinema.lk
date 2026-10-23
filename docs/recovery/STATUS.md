# Recovery status

Updated: 2026-10-23.

## Current phase

Owner-authorized ZL-9/ZL-10 review publication: local preparation COMPLETE;
branches pushed and both review PRs opened. The owner explicitly requested
incremental staging/commits and GitHub PRs after the ZL-10 preparation handoff.
That later request authorizes publishing these reviewed changes to the existing
repository; it does not authorize a real project export, PR merge or deployment.

- [PR #10](https://github.com/gayashan-indrasankha/Zinema.lk/pull/10):
  `docs/zl-9-baseline-provenance` -> `main`; two baseline/provenance commits,
  ending at `76ec6df6ef75443f76db963d7ec47a7ab7b63248`.
- [PR #11](https://github.com/gayashan-indrasankha/Zinema.lk/pull/11):
  `feat/zl-10-safe-public-export` -> `main`; six ZL-10 commits, including this
  final contribution/recovery handoff, follow the two ZL-9 ancestor commits.
  Eight new Conventional Commits in total, without fabricated development dates.
- Merge #10 first, then #11, using **Create a merge commit** for both to retain
  the incremental history. Both target main; no retargeting is needed. Until #10
  merges, #11 also displays the shared ZL-9 commits. No PR is merged here.
- Exact paths/content were staged incrementally. The original ZL-9 contribution
  prefix was staged through the index without changing the complete working
  file; subsequent ZL-10 content is included in this final commit. Baseline and
  checksum files remain byte-identical; original contribution history retained.
- Before each commit: inspected status and working/staged diffs; checked staged
  whitespace; Gitleaks 8.30.1 scanned exact staged text blobs with full redaction;
  local syntax checks passed. The three existing logo/icon binaries were excluded
  from text scanning and unchanged. No secret/data/session/media paths added.
- Executed syntax checks with the available local PHP executable: 164 PHP and,
  after staging both new tools, 36 JavaScript/CJS files; zero failures. All 32
  synthetic export tests passed with zero failures/skips/cancellations. All 40
  targeted ignore assertions and 265 required-path checks passed; four example
  gates passed. Actual-source default dry run: 188 eligible files, four holds,
  no output created. No functional application test or real export was executed.
- Gitleaks outgoing-history scans passed for both branch ranges before initial
  push. The final handoff commit is checked before its commit and the complete
  outgoing history is checked again before the final push. This is a bounded
  outgoing-change scan, not a full historical/privacy/vulnerability assessment.
- Local and remote main started at
  `7e8d325c4fc206c3a2664652de99e560bae44795` and are not advanced by this work.
  Existing branches/history and private assets are retained. No reset, stash,
  amend, history rewrite, force-push, production contact or account operation.
- PR #11 was initially opened as a draft while this last record was prepared.
  Final push, ready-for-review transition, remote head agreement, clean worktree
  and last commit SHA are verified and reported at handoff after this commit.

Evidence: executed local checks above and externally verified repository/main,
branch publication and PR creation. Application logic, SQL, dependencies and
assets remain unchanged. Existing risks and four export holds remain; no blanket
security or runtime-readiness claim. ZL-11 has not started.

Exact next recommended Codex task: **Jira ZL-11 - Map modules and classify keep,
repair, isolate, archive**, after owner review of these PRs. Further real export,
credential/account changes, destructive cleanup, deployment and production access
need their own explicit authorization.

## Prior ZL-10 preparation handoff (historical)

Jira ZL-10 - Sanitize sensitive files and define safe public export: COMPLETE
within local preparation and synthetic-validation scope. This record supersedes
the next-task recommendations in the historical sections below. ZL-11 has not
started. A real project export and publication still require explicit approval.

- Authoritative input: current extracted working tree, including uncommitted
  content. No ZIP was used. Starting main HEAD was
  `7e8d325c4fc206c3a2664652de99e560bae44795`, tree
  `7e01e85cc90bbd838144d5b9bddd01122036eea9`, with 259 tracked files and 32
  reachable commits. The index was empty. Three untracked ZL-9 documents already
  existed; they are prior work, not new ZL-10 implementation.
- All 176 eligible ZL-9 source checksums matched before changes. The baseline
  document and checksum manifest remain unchanged. The contribution log is
  extended without changing its previous content. ZL-10 intentionally changes
  three original manifest inputs: root `.gitignore`, this status record and
  `DECISIONS.md`. These workflow/documentation deltas do not replace the baseline.
- Extended root ignore rules; preserved nested rules and all component examples.
  Appended clearly FUTURE / NOT IMPLEMENTED root example settings. Added
  `scripts/prepare-public-export.cjs`, `scripts/test-public-export.cjs` and
  `docs/SECURITY_EXPORT.md`; appended `docs/CONTRIBUTIONS.md` and recovery records.
- The standard-library Node tool uses an explicit path allowlist and content
  gates, defaults to a no-write dry run, rejects unsafe/existing destinations,
  and separately validates hashes/manifests. No source normalization or repair.
- Executed new-script syntax checks and 32 synthetic tests: all passed, zero
  failed/skipped/cancelled. Fixtures exercised actual Windows junctions,
  hardlinks, traversal, sensitive-file non-reading, tampering and SQL policy.
  Ignore checks passed 21 sensitive-path and 19 required-file assertions. All
  four example templates passed their content gate. Actual-source dry run
  created no output directory; final counts are in `../SECURITY_EXPORT.md`.
- **Statically inspected:** safe templates, ignore rules and export policy.
  **Test-covered and executed:** export/validation behavior in synthetic local
  fixtures. **Runtime verified:** only the new export utility, not application
  features. **Externally verified:** none in ZL-10. No app, bot, payment,
  extractor, database or production service was started/contacted.
- Security inventory used metadata only for real environments, sessions, logs,
  dumps and private recovery assets. Redacted local first-party checks held
  three tracked files for local-path patterns and one SQL migration outside the
  conservative schema-only grammar. See SECURITY_EXPORT for sanitized paths.
  Originals, 70 bundled-library files and binary assets remain untouched.
- New-artifact secret-scan results and preservation checks are recorded in
  SECURITY_EXPORT and the contribution entry. No historical secret scan or full
  vulnerability/privacy audit is claimed. Private-content preservation is
  metadata/no-write evidence, not a claim that its contents were inspected.
- Git history/HEAD/index preserved; no staging, commits, resets, stash, push,
  real project export, account changes or publishing. Changes remain reviewable
  and uncommitted. Existing application PHP/JS/SQL, dependencies and assets are
  unchanged; application regression/runtime tests were not rerun for this scope.

Remaining risks: bounded content heuristics can miss secrets; held candidates
need review, dependency/media rights need review, and historical exposure remains
unassessed. Obtain separate authorization for a real export, publication,
credential/session changes or destructive cleanup. No runtime readiness claim.

Exact next recommended Codex task: **Jira ZL-11 - Map modules and classify keep,
repair, isolate, archive.** Continue from current working-tree evidence and
existing recovery records; do not infer deletion authority from an export hold.

## Prior integration handoff (historical)

Owner-authorized integration into main: COMPLETE for Git integration. On
2026-10-23 the owner explicitly requested completing the final merge. PR #8
merged the complete reviewed project from `feat/whatsapp-delivery` into main
using a merge commit, preserving all 26 original incremental commits.

- Integration PR: https://github.com/gayashan-indrasankha/Zinema.lk/pull/8
- Main integration commit: `626e35110f2acb89391c56831daf2712ee9be08b`.
- GitHub reported the PR mergeable with a clean merge state; expected head SHA
  was supplied to the merge operation. No branch protection was bypassed.
- Local main was fast-forwarded to that merge. It contained 30 commits and its
  tree `499372d1e7a41125962eb2d1d8412a2719e88ac7` exactly matched the reviewed
  project tree. Every one of the original 26 commit SHAs passed an ancestry check.
- Re-executed PHP 8.2.12 lint on 164 files and Node 24.13.1 syntax checks on 34
  JavaScript/CJS files: zero failures. Gitleaks 8.30.1 scanned the 23 remaining
  non-merge source commits before integration: no findings. The original three
  foundation commits were already on main and had passed the publication checks.
- This follow-up changes only AGENTS.md, STATUS.md, DECISIONS.md and PUBLISHING.md
  to record authorization, integration and verification. Its explicit staged
  paths, diff and secret scan are checked before commit; no application code is
  changed. The record is integrated through a separate documentation-only PR.
- Evidence: executed syntax/security/ancestry checks and externally verified
  GitHub merge. No application behavior or production service was runtime tested.
- Security limits and the 1,798 inherited whitespace findings below remain.
  The earlier source-publication quality status remains PARTIAL; completing a Git
  merge does not establish runtime, schema or security readiness.

Final documentation commit/merge hashes, clean local status and remote agreement
are reported at handoff and recorded in ignored local verification evidence.
Existing feature branches and private preserved evidence are retained. No
credentials were rotated, production service contacted or deployment performed.

Exact next recommended Codex task:

"Perform a read-only reconciliation of Zinema's existing recovery changes and SQL
generations. Produce a canonical schema proposal, route/bootstrap map and
prioritized defect list with source evidence. Include a behavior-safe proposal
for the retained whitespace findings. Do not import data, start live integrations
or change application behavior."

## Prior publication handoff (historical)

Owner-requested GitHub source-publication phase: PARTIAL. The requested seven
branches and seven open, non-draft PRs are published for owner review. The final
handoff brings the new Conventional Commit count to 26. Syntax and secret scans
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
