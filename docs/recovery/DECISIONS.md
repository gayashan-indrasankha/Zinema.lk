# Recovery decisions

## 2026-10-23 - Integrate the complete reviewed source into main

Context: The owner merged the seven original PRs. Only PR #1 targeted main;
PRs #2-#7 targeted the preceding feature branches, so main retained only the
repository foundations. The owner then explicitly requested completing the merge.

Decision: Create PR #8 from `feat/whatsapp-delivery` into main and merge it with
GitHub's merge method, supplying its expected head SHA. That branch contained all
26 original project commits and exactly the reviewed source tree. Preserve branch
history and feature branches; do not squash, rebase or rewrite existing commits.

Rationale: One integration PR brings the complete reviewed project and original
incremental history into main without reapplying application changes. Retained
merge-only commits on other feature branches do not represent missing source.

Consequences: Main now contains the full reviewed project. Record the completed
merge through a documentation-only PR. Runtime recovery and unresolved legacy
whitespace remain separate work; deployment and production access are not granted.

Verification: PR #8 merged at `626e35110f2acb89391c56831daf2712ee9be08b`.
All 26 original commit SHAs passed ancestry checks. Main's integration tree
matched the reviewed tree. Re-executed 164 PHP and 34 JavaScript syntax checks
passed; Gitleaks found no secrets in the remaining non-merge source history.

## 2026-10-23 - Retain inherited whitespace and report partial verification

Context: All staged source snapshots passed syntax and secret checks, but the
imported legacy text produced 1,798 whitespace/EOF warnings from Git diff checks.

Decision: Preserve those source lines during publication, record the non-clean
check explicitly and mark the phase PARTIAL. Do not apply bulk whitespace edits
to string/template bodies without behavior evidence merely to obtain a green check.

Consequences: The requested branch/commit/PR workflow is prepared for review,
while formatting validation remains unresolved. No functional readiness claim is
made. A later authorized recovery phase should determine safe formatting changes.

Verification: Warning counts and command outputs are preserved in private local
execution records. PHP/JavaScript syntax and publication secret checks passed;
the final counts and limits are recorded in STATUS.md.

## 2026-10-23 - Publish the reviewed legacy source as a dependent PR stack

Context: The owner initialized a public GitHub repository and explicitly asked
for feature branches, staged incremental Conventional Commits and open PRs.

Decision: Publish reviewed, sanitized source in seven dependent branches, from
repository foundations through the website, administration, delivery and
extractor. Keep real creation dates and identify commits as legacy imports.
Preserve source paths, existing changes and private originals. Preserve nested
extractor Git metadata locally and exclude its unscreened history. Keep main
unchanged until owner review. The precise scopes are in `PUBLISHING.md`.

Rationale: Small coherent changes and narrow PR diffs make the existing project
reviewable. Artificial micro-commits or fabricated feature history would not
improve engineering quality. The explicit request supplies publication approval;
no extra publishing confirmation is inferred from the earlier setup boundary.

Consequences: Intermediate branches are incomplete import snapshots. Review and
merge in sequence, retaining ancestry. Runtime defects, schema reconciliation,
deployment and production access remain outside this phase. Embedded contacts
are removed for publication, so support links and fallback contacts are disabled.
Historical setup documents are labeled to avoid treating them as verified steps.

Verification: Initial Git state and remote metadata inspected. Gitleaks 8.30.1
installed from its official release with SHA-256 verification. Final executed
checks and commit references are recorded in STATUS.md at handoff.

## 2026-10-23 — Scope the owner request as source-based project discovery

Context: The owner requested deep project understanding and a whole-project description after providing rules that require explicit authorization for investigation. This new request authorizes investigation/documentation, but does not request application repair or deployment.

Decision: Inspect local code and schema structure, run non-executing syntax checks, and document findings. Preserve application code and all existing user changes. Do not start services, import databases, contact integrations or initialize/commit the workspace.

Rationale: Explain the project using implementation evidence while respecting the recovery boundaries.

Consequences: Feature descriptions express intended/source-described behavior. Runtime functionality, database compatibility and production readiness remain unverified.

Verification: Git/status and filtered static inspections executed; 31 selected JavaScript syntax checks passed. PHP unavailable on PATH. Details in STATUS.md.

## 2026-10-23 — Do not infer recovery history from code comments

Context: Recovery records were missing, but existing files contain recovery guards and comments referring to earlier numbered work. The nested bot repository also has user changes.

Decision: Document the observed checkout and preserve it. Do not assert completion of any numbered recovery phase or overwrite existing recovery implementation.

Rationale: Code comments establish current text, not historical authorization, test execution or completion.

Consequences: Reconciliation of prior changes and schema generations is the next recommended task.

Verification: Presence of guards and Git modifications statically inspected; historical provenance not yet verified.
