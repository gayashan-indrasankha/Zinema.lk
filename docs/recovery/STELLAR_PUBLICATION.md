# Stellar and local acceptance source publication

Started 2026-10-28; published 2026-10-29.
**COMPLETE within source-review scope; broader launch plan PARTIAL / NO GO.**

## Scope and preserved work

The owner requested logical incremental staging/Conventional Commits and PRs, then
explicitly chose to wait for the active acceptance chat and include its completed
fixes. Initial inspection found 65 changed paths at `12c9954758b9718cabe25816d328b28db0198582`.
The completed combined snapshot contains **79 changed paths** and 591 candidate
files. No index or branch changes occurred while that chat was editing. After it
finished, the combined snapshot was rescanned and tested, then staged in nineteen
logical source/documentation groups plus this final publication handoff.

Existing working files, history and the original source branch were preserved.
The audit helper's earlier Stellar changes and later preview fixes were staged
separately using verified saved/current snapshots; its working file was never
overwritten. Application behavior was not newly modified by the publication pass.
No schema change, activation, host purchase, power-setting change, real account/
recipient/provider, production, credential rotation, merge or deployment occurred.
Source-review publication authorization does not enable disabled runtime switches.

## Ordered PRs

All PRs target `main`; merge in order, using normal merge commits to preserve the
individual commits. Later PRs initially include predecessors until those merge.
Each PR body links the unique preceding-checkpoint comparison.

| Order | PR | Branch | Added commits |
| --- | --- | --- | --- |
| 1 | [#22](https://github.com/gayashan-indrasankha/Zinema.lk/pull/22) | `zinema/stellar-preparation` | 6 |
| 2 | [#23](https://github.com/gayashan-indrasankha/Zinema.lk/pull/23) | `zinema/stellar-adapters` | 5 |
| 3 | [#24](https://github.com/gayashan-indrasankha/Zinema.lk/pull/24) | `zinema/stellar-worker-pressure` | 3 |
| 4 | [#25](https://github.com/gayashan-indrasankha/Zinema.lk/pull/25) | `zinema/local-acceptance` | 6 |

The first checkpoint contains exclusions, sanitized readiness/hosting metadata,
read-only tooling, locked templates/exporters and preparation evidence. Adapters
contain shared operator/storage routines, account validation, extractor queue and
outbound transport. Worker pressure contains admission/stream/send gates and
regressions. Acceptance contains the user-facing fixes, complete preview fixtures,
automated journeys, limitations and this handoff. Intermediate checkpoints are
review boundaries, not standalone production releases.

All PRs were verified open/non-draft against main before the handoff update.
Source head before this final documentation commit: `eaab87c6c5d49471613ddb88c13fe0560f373c15`. The nineteen
immutable hashes, paths and checks are in STELLAR_PUBLICATION_RESULTS.json; the
final handoff hash is in file history and the execution handoff. Initial pushed
heads matched their local checkpoints. Remote main remains
`2e85a54f9a7e2ad0dad9b2602c96e3f44b51fb21`; the behind local main and original source branch are kept.

## Fresh executed verification

- **Static/syntax:** PHP213/213, JavaScript-CJS88/88, Python28/28 and JSON105/105
  passed for the settled 591-file source candidate. Staged paths/diff statistics
  were inspected; Git-filtered index hashes matched the declared reviewed source
  snapshots. Relevant syntax/JSON and whitespace checks ran before every commit.
- **Test-covered and executed:** Node171/171 with zero skips; pure target,
  artifact, candidate and adapter Python49/49. Focused checks repeated for applicable
  staged groups. A standalone PHP render confirms the disabled delivery page
  returns 503, offers catalogue browsing and emits no delivery contact link.
  Integrated checks do not certify each intermediate checkpoint in isolation.
- **Security:** Gitleaks8.30.1 archive checksum and binary/archive equality were
  verified. Full candidate, nineteen staged source groups and complete new source
  history scans return zero findings. Twenty-two private ignore checks and the
  full new-work whitespace check pass. Final staged documents/updated history are
  scanned again before commit/push. No allowlist was added to suppress findings.
- **Data/privacy:** numeric WhatsApp IDs are explicit fictional entrypoint tests;
  SQL writes are code/fictional fixtures, not a production dump. Actual machine
  reports remain sanitized and are not capacity proof. Private runtime config,
  accounts/device stores, original media/rows, passwords, keys, backups, logs and
  generated browser artifacts stay excluded. Default pattern scans cannot prove
  absence of every secret or personal datum; no exhaustive license audit is made.

## Included acceptance and remaining limits

The completed local acceptance phase passed **16/16 automated Chrome journeys**
and **94/94 HTTP checks**, with no new PHP diagnostics/page errors. It corrected
missing preview logos/GD configuration, unavailable-delivery UX, administrator
trailer preview paths/optional poster handling, upload guidance and catalogue
metadata. Interactive browser tooling failed, so no manual interaction pass is
claimed; screenshots were reviewed separately. The reports and reproduction driver
are committed, but these full browser/HTTP journeys were not replayed by this
publication pass. Earlier Stellar/TLS/SQL/memory evidence retains its own scope.

The preview deliberately remains at localhost:8088 for owner review. Publication
did not start, stop, refresh or mutate that preview/database. Private access and
exact owned stop instructions remain in LOCAL_ACCEPTANCE.md; no access file was
published. Test syntax and pure/fake fixtures use separate owned scratch state.

Stellar is recorded as not purchased; actual account/handler/TLS/permissions and
provider compatibility are unverified. Read-only inventory found sleep/hibernate
timers; settings were not changed. Sustained upload, independent backup/new-target
restore, real SDK/2-GiB delivery and live acceptance remain gates. No new dependency
advisory, container vulnerability, penetration or production-readiness certification
is claimed. See NEXT_VERIFICATION.md before any separately authorized external work.

## Next recommended task

> Review the localhost results, choose an owner-approved always-on policy and independent recovery destination, and obtain hosting capability confirmation before purchase or private staging. Approve exact receivers and caps before any upload-speed or live-provider test.
