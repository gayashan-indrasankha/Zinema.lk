# Recovery decisions

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
