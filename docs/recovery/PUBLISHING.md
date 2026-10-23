# Initial source publication

## Completed main integration

The owner subsequently merged PRs #1-#7 into their configured bases. Only #1
targeted main; the others targeted their preceding feature branches. Therefore
main initially contained five commits. Those were the initial README, the three
foundation commits and the first PR merge commit.

The owner's subsequent explicit request authorized completing integration.
[PR #8](https://github.com/gayashan-indrasankha/Zinema.lk/pull/8) merged the
complete reviewed project from `feat/whatsapp-delivery` into main with merge
commit `626e35110f2acb89391c56831daf2712ee9be08b`. All 26 original commits are
preserved as ancestors. The integration tree exactly matched the reviewed source
tree; source changes were not reapplied. Main contained 30 commits at this point,
including its initial commit and GitHub merge commits.

This documentation-only follow-up records the completed integration without
altering application source. The old review order and handoff below are historical;
new feature work should start from the integrated main. Existing feature branches
are retained. Integration does not resolve the recorded runtime/security/schema
risks or authorize deployment. See STATUS.md for executed verification and limits.

Owner authorization: 2026-10-23. Target: `gayashan-indrasankha/Zinema.lk`, an
existing public repository. Starting commit: `e2c239d` (`add readme`). The root
was initialized by the owner after the earlier static discovery. The import is
new history dated when performed; it does not reconstruct prior development.

## Initial review order (historical)

| Order | Branch | Scope | PR base |
| --- | --- | --- | --- |
| 1 | `chore/repository-foundation` | Exclusions, recovery records, contributor workflow and syntax tooling | `main` |
| 2 | `feat/website-foundation` | Configuration, shared PHP, bundled mail library and shared presentation | `chore/repository-foundation` |
| 3 | `feat/accounts-and-shots` | Accounts, profile and short-video interactions | `feat/website-foundation` |
| 4 | `feat/catalogue-and-playback` | Movies, series, collections, trailers and player assets | `feat/accounts-and-shots` |
| 5 | `feat/administration` | Content administration, analytics and historical website migrations | `feat/catalogue-and-playback` |
| 6 | `feat/whatsapp-delivery` | Website bridge, guarded bot, delivery modules and historical bot SQL | `feat/administration` |
| 7 | `feat/video-extractor` | Guarded extraction service and final publication handoff | `feat/whatsapp-delivery` |

Each branch adds only its reviewed paths, in small Conventional Commits. The
stack order reflects source-import dependencies; intermediate branches are not
complete runnable applications. Review before merging, use merge commits to keep
the incremental history, and retarget each successor to `main` after its base
merges. Nothing is merged or deployed automatically.

## Publication decisions

- Keep existing source layout to preserve include paths and observable structure.
  Feature branches divide review scope; directories are not rearranged for style.
- Keep `.env`, database dumps, sessions, logs, caches, downloaded dependencies,
  private media, exports and incomplete historical variants outside Git.
- Preserve the extractor's nested `.git` directory under the ignored
  `.recovery-private/legacy-fb-video-api.git` before importing its current reviewed
  source as ordinary files. Its unscreened commit history is not published.
  The nested starting commit was `f31602e`; `.gitignore` and `index.js` were
  modified, and `.env.example` and `environment.js` were untracked. Those current
  changes are retained in the import. No nested bot repository exists now.
- Back up publication-edited originals under ignored `.recovery-private/originals`.
  Remove embedded contact numbers, use explicit fake token/message examples,
  redact a roadmap contact address, and label outdated component READMEs.
  Empty support destinations are disabled. No credentials are rotated.
- Retain legacy schema/migration files as historical source references. They are
  not a canonical schema, have not been applied and are not installation steps.
- Retain the bundled PHPMailer source and its license. No application-wide
  license or demonstration-media rights are inferred from dependency licenses.

## Verification standard

Run Gitleaks 8.30.1 (official Windows archive checksum verified) on every exact
staged snapshot before committing, and on outgoing Git history before pushing.
Its initial generic-key finding was a sample request token in a comment; replace
it with an explicit fake placeholder instead of suppressing the rule.

Run non-executing syntax checks on tracked PHP and JavaScript before each commit.
Check staged and working diffs using redacted methods. Record retained legacy
whitespace separately from parser or security failures. Check that no Git links,
session files, database dumps, exports or unreviewed media enter the index.

These checks cannot prove functional correctness, database compatibility, absence
of all secrets, dependency safety or media rights. No application, database,
WhatsApp connection, payment, email or production service is started in this
phase. Known recovery defects remain listed in `PROJECT_DESCRIPTION.md`.

## Published review stack (initial handoff)

All seven PRs were created open and non-draft on 2026-10-23. None was merged.
The final branch adds two documentation commits after its two source commits,
for 26 new commits overall. Source-boundary hashes below exclude the final
handoff commit to avoid a self-referential document hash.

| Order | PR | Branch | New commits | Source boundary |
| --- | --- | --- | --- | --- |
| 1 | [#1](https://github.com/gayashan-indrasankha/Zinema.lk/pull/1) | `chore/repository-foundation` | 3 | `1dfd7c763e58` |
| 2 | [#2](https://github.com/gayashan-indrasankha/Zinema.lk/pull/2) | `feat/website-foundation` | 4 | `8f91f9d464e8` |
| 3 | [#3](https://github.com/gayashan-indrasankha/Zinema.lk/pull/3) | `feat/accounts-and-shots` | 3 | `2acdfe8e259a` |
| 4 | [#4](https://github.com/gayashan-indrasankha/Zinema.lk/pull/4) | `feat/catalogue-and-playback` | 4 | `171346a0a624` |
| 5 | [#5](https://github.com/gayashan-indrasankha/Zinema.lk/pull/5) | `feat/administration` | 3 | `40dc0b8faeef` |
| 6 | [#6](https://github.com/gayashan-indrasankha/Zinema.lk/pull/6) | `feat/whatsapp-delivery` | 5 | `335bde895c1e` |
| 7 | [#7](https://github.com/gayashan-indrasankha/Zinema.lk/pull/7) | `feat/video-extractor` | 4 | `375a989aa413` |

Review in this order. Preserve the incremental history with merge commits and
verify the next PR's base/diff after each merge. Main remains the owner's
initial `e2c239d` until a reviewed merge is performed. No merge is authorized
or performed by this task.

## Incremental commit index

| Commit | Conventional Commit subject |
| --- | --- |
| `6f7841382c13` | chore(repo): add publication exclusions and line ending rules |
| `e2e991ec640b` | docs(recovery): establish source import and review workflow |
| `1dfd7c763e58` | chore(quality): add non-executing syntax checks and PR template |
| `899829a30540` | chore(config): import guarded website environment and shared helpers |
| `0ba4dd533ed7` | chore(deps): import bundled PHPMailer with license notices |
| `09930ec7bf55` | feat(web): import shared layout navigation and site assets |
| `8f91f9d464e8` | docs(web): preserve historical starter and player reference notes |
| `7ba6789208a0` | feat(auth): import account login and registration handlers |
| `b0a54e49b2ae` | feat(auth): import email verification and password recovery flows |
| `2acdfe8e259a` | feat(shots): import feed profile and social interactions |
| `db4c2e080cdd` | feat(movies): import catalogue search and movie comments |
| `84a2c18cb488` | feat(series): import series listings and episode selection |
| `cc2a79d3a49e` | feat(collections): import grouped movie browsing |
| `171346a0a624` | feat(playback): import trailer and player presentation paths |
| `d661fb0b378b` | feat(admin): import console access layout and shared assets |
| `3221f43957a1` | feat(admin): import catalogue and site management screens |
| `40dc0b8faeef` | feat(analytics): import reporting and guarded maintenance sources |
| `6cf01d93a149` | feat(delivery): import guarded website bridge and request tokens |
| `1bcb511972c3` | chore(integrations): preserve quarantined download and payment handlers |
| `739948ecc6f7` | chore(bot): import dependency lock and guarded configuration |
| `7943754be043` | feat(bot): import delivery entry point queues and maintenance workers |
| `335bde895c1e` | chore(database): preserve historical bot schemas for reconciliation |
| `f50a57d3b58c` | chore(extractor): import environment and service manifest |
| `375a989aa413` | feat(extractor): import guarded URL extraction service |

The final `docs(recovery): record publication checks and PR handoff` commit
contains the review links, verification record and contributor wording correction.
The follow-up `docs(recovery): correct handoff whitespace and commit totals`
removes an extra EOF blank line introduced by that handoff and records the final
totals without amending history. Exact hashes are in the private execution ledger;
the final published head is reported at handoff.
