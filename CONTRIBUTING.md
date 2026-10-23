# Contributing to Zinema

Read `AGENTS.md` and `docs/recovery/STATUS.md` before making changes. This is a
legacy source import, not a verified development or production environment.

## Branches and commits

Create short-lived branches from the appropriate reviewed base:
`feat/<feature>`, `fix/<defect>`, `chore/<maintenance>`, or `docs/<topic>`.
Use `type(scope): imperative description`, for example
`fix(auth): reject expired reset tokens`. Keep one coherent change per commit.
Never invent old development dates, authorship, test results or feature history.

The initial import uses dependent PRs, starting with repository foundations and
then shared website code. Review and merge them in the order recorded in
`docs/recovery/PUBLISHING.md`. A later PR targets the preceding branch so its diff
contains only its own area. After merging a base PR, retarget its successor to
`main` and verify the diff. Prefer merge commits for this import to retain the
reviewed incremental commits and ancestry. Do not squash a base while dependent
branches still rely on its commit identities without first reconciling the stack.

## Before a commit or PR

Inspect working and staged changes; stage explicit paths. Run
`node scripts/check-source.cjs` for tracked JavaScript and PHP syntax. Set
`PHP_BINARY` to a local PHP executable if it is not on PATH. CI requires PHP;
local absence is reported as an unperformed check. Run appropriate regression
tests when changing behavior. Syntax success is not a functional test.

Use Gitleaks 8.30.1 with `--redact=100` against a snapshot of the exact staged
tree before committing and against the complete outgoing history before pushing.
Do not scan the workspace indiscriminately or print raw matches. Review the
staged file list for ignored secrets, session material, exports and private media.
Never use `git add .` as a substitute for that review. Do not bypass findings
with broad allowlists. Keep scanner reports and preserved evidence outside Git.

Describe imported behavior, checks actually executed, known defects and PR
dependencies. Merge approval belongs to the owner. Publication does not authorize
deployment, real-user contact, production access or credential rotation.
