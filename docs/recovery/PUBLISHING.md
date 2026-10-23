# Initial source publication

Owner authorization: 2026-10-23. Target: `gayashan-indrasankha/Zinema.lk`, an
existing public repository. Starting commit: `e2c239d` (`add readme`). The root
was initialized by the owner after the earlier static discovery. The import is
new history dated when performed; it does not reconstruct prior development.

## Review order

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
