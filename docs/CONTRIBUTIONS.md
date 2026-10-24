# Zinema.lk contribution and provenance record - Jira ZL-9

Audit date: 2026-10-23 (Asia/Colombo).
Reference HEAD: `7e8d325c4fc206c3a2664652de99e560bae44795` on `main`.
The authoritative project is the existing extracted `Zinema.lk/` workspace,
including its working tree and uncommitted changes. HEAD records committed
history; it does not include these three untracked ZL-9 documents. A historical
ZIP is not the active source and is not required for completion of Jira tasks.
Scope, source references, exclusions and verification are in
[BASELINE.md](BASELINE.md). The pre-audit eligible-file evidence is in
[baseline-source-sha256.txt](baseline-source-sha256.txt).

This record separates observable pre-existing source, owner-reported history,
dated repository/audit activity and future plans. Source presence is not proof
of runtime success or of original feature creation during a competition.

## Section A - Existing Project Foundation

These implementations are present at the pinned baseline. Their original
creation dates and per-file human/AI authorship are not independently verified.
No feature below is claimed newly implemented by ZL-9.

| Foundation | Observable source evidence | Present classification / limit |
| --- | --- | --- |
| Movie catalogue, search and filters | `Zinema.lk Website/movies.php`, `movie-details.php`, `find_movies.php`, `api/movies.php`, `api/search_content.php` | Source-confirmed; runtime not verified. |
| TV series, episodes and seasons | Website `tv-series.php`, `series-details.php`, `api/series.php` | Source-confirmed; current database compatibility not established. |
| Collections | Website `collections.php`, `collection-details.php`, `api/collections.php` | Source-confirmed grouped browsing. |
| Shots discovery feed | Website `index.php`, its linked-content queries and browser handlers | Source-confirmed history-aware/random selection; not an established AI recommender. |
| Accounts | Website login/signup, `ajax_*`, `verify-email.php`, reset pages, `google-callback.php`, `api/auth-handler.php` | Source-confirmed parallel flows; OAuth/mail have configuration gates. |
| Social interactions and profile | Website Shot APIs and `profile.php` | Source-confirmed likes, comments, favorites and view-history paths. |
| Content administration | Website `admin/` pages, settings, analytics, bot tracking | Source-confirmed forms/reports; security and runtime concerns remain. |
| Browser playback and resume | Website `movie-details.php::playMovie`, `series-details.php::playEpisode`, localStorage use | Native browser playback implementation; not verified as operating now. |
| Token-based WhatsApp request architecture | Website `includes/whatsapp_token.php`, `whatsapp-watch.php`, bridge API; bot `index.js::handleTokenRequest` | Source-confirmed; production/live startup restricted and current correctness not established. |
| Cached-message/cache-group/local-file fallbacks | Bot `config/multi-tier-forwarder.js::forwardVideo`, `file-manager.js`, `upload-queue.js` | Source-confirmed fallback/priority/coalescing design; reliability not reproduced. |
| Bot operations | Bot health monitor, cache warmer, auto-refresher and website monitoring endpoints | Source-confirmed operational modules; none started in this audit. |
| Extractor source | Website `fb-video-api/` | Legacy HTTP/browser extraction exists, but the current route returns a local fixture or disabled response first. |
| Legacy commercial/download/maintenance paths | Website download/payment/subscription/job entry points | Code retained behind unconditional recovery exits; not active completed features. |

Schema files, helpers, browser assets, historical starter pages, bundled mail
library and prior workflow documentation are part of the existing foundation.
Their presence does not establish consistency, complete test coverage or a safe
production environment. No transpilation, DRM, adaptive media pipeline, permanent
WhatsApp CDN or machine-learning recommendation system is credited without evidence.

## Section B - Owner-Reported History

The owner states that:

- This is a personal project developed independently with substantial AI-assisted
  coding. It must not be represented as entirely manually written code.
- The core local journey previously succeeded: Shots -> movie details -> WhatsApp
  content request -> bot delivery.
- The service has not been publicly launched. Publication of source in a Git
  repository does not establish that an application service was publicly operated.
- For ZL-9 and future Jira tasks, the existing extracted workspace, current Git
  working tree and uncommitted changes are authoritative. The original ZIP is
  historical only; it must not replace current files or gate task completion.

These statements are recorded as owner reports. They do not identify an exact
earlier working commit, environment, database installation, media set or date of
feature implementation. No old runtime was reconstructed, no real users were
contacted, and no integration was executed to reproduce the statements.

Historical commits or preserved private material could provide additional
provenance in a separately authorized task. This audit neither opens that material
nor assumes it is safe to publish.

## Section C - Hackathon-Period Changes

The requested competition context is LaunchPad Hacks 2026, with a period starting
October 16, 2026. That start date and context are owner-supplied and not externally
verified in this no-network audit. No competition eligibility conclusion is made.

All 32 commits reachable from the baseline have local Git date metadata on
2026-10-23. This independently observable repository record establishes dated
repository/import activity, **not the original creation date of imported code**.
Git author/committer timestamps are recorded metadata, not trusted time-stamped
attestations. Filesystem dates or archive dates are not substituted for provenance.

| Activity observed within the supplied date window | Local evidence | What can be credited | What cannot be inferred |
| --- | --- | --- | --- |
| Initial root README history | `e2c239d7e0fc608d96e2623227a727ba3286b64d` | A root repository commit records this artifact. | The project or its features were first created then. |
| Recovery/publication exclusions and workflow | `6f7841382c13a7c00acfca84de9b8f440fe7f0b7`, `e2e991ec640bb8e767dee72258e7ea6feccb2251` | Repository workflow/import documentation appears in dated history. | Every recovery guard or architecture decision originated in that commit/window. |
| Syntax-only checking utility | `1dfd7c763e5856137f861d2e23464ec9c6aa5555`; `scripts/check-source.cjs` | The utility entered this repository in a dated workflow commit. | Functional test coverage, current execution success or feature readiness. |
| Legacy website/bot/extractor import | Commit subjects explicitly say `import`; examples `7ba6789208a0b4113998642d0b40df8c57e6499d`, `7943754be0432071a846e0952b545bf928ad8fc0`, `375a989aa413a02cf4d909b5fab8ec6576c09950` | Reviewed source became part of root Git history during the window. | Original implementation of auth, delivery or extraction during the competition. |
| Publication handoff and correction | `d883407790b015b48c4e6502882f53089db7e105`, `7506cd1820e0cc8ae98ae98ac2b581b987eb7081` | Dated publication records/correction are present. | A new product feature or a new historical authorship claim. |
| Complete-source integration into main | `626e35110f2acb89391c56831daf2712ee9be08b`; documentation `303ce1460292019c268a8f94ee08b3517655c9a2`; final baseline merge | Local commit ancestry records integration and a documentation follow-up. | New implementation of imported functionality or independently verified runtime success. |
| ZL-9 current-source audit | The three new audit outputs, eligible-input hashes and preserved baseline HEAD | Documentation/static-audit work performed in the current session. Outputs remain untracked and uncommitted by instruction. | An independently time-attested competition submission or a new AI product capability. |

For all legacy feature implementation dates and original recovery-code authorship:
**Origin/date not independently verified.** No inspected source evidence warrants
crediting conversational AI, mood recommendations, voice interaction or a complete
delivery simulation as an implemented competition-period feature.

This log should be extended only with concrete dated changes, file lists,
executed test outcomes and reviewable evidence. Keep actual implementation work
separate from source import, documentation, workflow maintenance and demonstrations.
Record working-tree/uncommitted contributions as such until committed: a HEAD
identifier alone cannot attest to their contents or dates. The ZL-9 clarification
updates only its existing documentation; it does not restart the source audit or
create a new implementation milestone.

## Section D - Future Planned Features

The following are proposals, not completed features or ZL-9 deliverables:

| Planned feature | Intended future contribution | Current evidence boundary |
| --- | --- | --- |
| AI conversational movie assistant | Natural-language discovery conversation | No such implementation identified in inspected first-party source. |
| Mood-based recommendations | Recommendations using expressed mood/preferences | Existing filters/feed queries are not evidence of this feature. |
| Catalogue-grounded recommendation engine | Suggest items grounded in actual catalogue content | No grounded model/retrieval contract or execution verified. |
| Optional AI voice interaction | Speech input/output for the assistant | No voice feature credited as complete. |
| Animated voice-assistant UI | Visual companion to voice interaction | Planned presentation work, not the existing generic video/UI code. |
| AI-to-Shots integration | Link recommendations/conversation to relevant Shots/content | Existing Shot-to-content links alone do not establish AI integration. |
| Controlled delivery simulation | Fictional/sample local substitute for delivery demonstrations | Extractor fixture mode is a narrow stub, not an end-to-end delivery simulation. |
| Reliability/accessibility improvements | Tested delivery contracts, usable interaction and accessible controls | Requirements and independent verification still needed. |

Implementing any item requires separate owner authorization and its own issue,
baseline comparison, contribution record and appropriate executed tests. ZL-9 does
not enable guards, modify code, reconstruct databases or begin these features.

## Section E - AI Assistance Disclosure

Known involvement:

- The owner reports substantial AI-assisted coding during independent development
  of the original project. Human ownership/direction does not mean every line was
  manually authored, nor does AI assistance prove every line was AI-generated.
- The current interaction includes AI-assisted static source inspection,
  documentation drafting, hash/inventory orchestration and document verification.
- Earlier repository publication/integration work is described in local Git and
  recovery records; those records are not a per-line authorship ledger.

Specific AI tool names, model/version identifiers, prompts, sessions, exact
human/AI contribution percentages and per-file authorship are **not independently
verified from owner-provided evidence**. They are intentionally not invented here.
The versions of ordinary application dependencies are not AI-tool provenance.

The owner remains responsible for reviewing generated code/documents, verifying
claims and following the competition's applicable disclosure rules. This document
makes no legal ownership, originality or eligibility determination and contains
no private prompts, authentication material or personal development-machine paths.

## Section F - Contribution Log Template

Record actual outcomes. "Test added" and "test executed/passed" are different
facts. Use "not executed" when appropriate. Cite commit/PR identifiers, relative
paths and sanitized artifacts; never paste credentials, sessions, user rows,
private URLs, phone numbers or logs.

| Date | Jira Issue | Change | Files | Tests | Evidence | AI Assistance | Limitations |
| --- | --- | --- | --- | --- | --- | --- | --- |
| 2026-10-23 | ZL-9 | Current-repository static baseline and provenance audit | `docs/BASELINE.md`; `docs/CONTRIBUTIONS.md`; `docs/baseline-source-sha256.txt` | Metadata/hash/document checks; no application runtime tests | Pinned baseline commit; 176 eligible-file hashes; BASELINE verification record | AI-assisted inspection/drafting/validation; specific tools/models unverified | Excluded sensitive material; original feature dates and runtime behavior unverified; no commit created |
| YYYY-MM-DD | ZL-ISSUE | Concrete new change, separated from existing foundation | Relative changed paths | Commands actually executed and outcomes, or not executed | Reviewed commit/PR/artifact identifiers | Describe known assistance; mark unknown details unverified | Scope, missing evidence and remaining risks |

Next recommended issue: **Jira ZL-10 - Sanitize sensitive files and define safe
public export.** It is a recommendation only; no ZL-10 action is authorized or
performed by this audit.

## ZL-10 - Safe export preparation (2026-10-23)

This entry extends the historical ZL-9 record above. ZL-10 was separately
authorized and is now complete within local preparation/synthetic-test scope;
the previous next-issue wording remains a historical ZL-9 statement. Current
working-tree contributions below remain uncommitted and are not attested by HEAD.

| Date | Jira Issue | Change | Files | Tests | Evidence | AI Assistance | Limitations |
| --- | --- | --- | --- | --- | --- | --- | --- |
| 2026-10-23 | ZL-10 | Metadata-only sensitive inventory; ignore/example protections; deny-by-default local export, validation and synthetic tests; security guidance and recovery handoff | `.gitignore`; `.env.example`; `scripts/prepare-public-export.cjs`; `scripts/test-public-export.cjs`; `docs/SECURITY_EXPORT.md`; this log; `docs/recovery/STATUS.md`; `docs/recovery/DECISIONS.md` | Both `node --check` commands passed; `node scripts/test-public-export.cjs`: 32 passed, zero failed/skipped/cancelled; `git check-ignore --no-index --stdin`: 21 positive and 19 negative assertions passed; four template gates passed; real-source default dry run created no output | SECURITY_EXPORT records policy, holds and executed verification; original 176-file baseline checked before changes and preserved with explicit workflow deltas | AI-assisted policy/design, implementation, documentation and local verification; owner review required; no unverifiable per-line/tool/model authorship claims | No real export/publication, app runtime testing, live services, account changes or historical exposure audit; bounded detectors and media/dependency rights still require independent review |

Application source, migrations, dependency files and private data were preserved.
The baseline document and original checksum manifest remain byte-identical; all
previous content of this log remains unchanged. The only changed original
manifest inputs are `.gitignore` and the two recovery records, intentionally
modified by this issue. New-artifact secret-scan results and exact dry-run counts
are recorded in SECURITY_EXPORT after execution. No claim is made that every
secret was removed or that the repository was published by this work.

Final verification: 188 files eligible in a no-write real-source dry run, four
source candidates held. Gitleaks 8.30.1 scanned the eight new/modified artifacts
through `stdin --redact=100 --no-banner --log-level error`: exit 0, no findings;
no report files or raw matches retained. Of the original 176 eligible inputs,
173 remain unchanged and the three workflow deltas above are explicit. HEAD,
index and branch are preserved; private-file metadata checks passed without
reading the protected contents. No historical/full-repository security scan is
claimed by this entry.

Next recommended issue: **Jira ZL-11 - Map modules and classify keep, repair,
isolate, archive.** Not started. Separate approval is still required for a real
project export, publishing, credential/account changes or destructive cleanup.

## ZL-9/ZL-10 - Review publication follow-up (2026-10-23)

The owner subsequently authorized staging, incremental commits, branch pushes
and PR creation for the existing changes. Earlier statements that the work was
uncommitted or publishing was unauthorized describe the earlier audit/preparation
handoffs. This follow-up publishes those reviewed artifacts for review; it does
not implement or re-date the legacy application, execute a real project export,
merge a PR or authorize production access. Previous contribution content above
is retained without revision.

| Date | Jira Issue | Change | Files | Tests | Evidence | AI Assistance | Limitations |
| --- | --- | --- | --- | --- | --- | --- | --- |
| 2026-10-23 | ZL-9 / ZL-10 publication follow-up | Incremental review of existing work: two baseline/provenance commits and six export/protection/test/documentation commits; branch publication and two review PRs | The same ten baseline/export/workflow paths already identified above; additional handoff wording only in this log, SECURITY_EXPORT and recovery STATUS/DECISIONS | Per-commit staged-tree Gitleaks and whitespace checks; syntax: 164 PHP and up to 36 JS/CJS files, zero failures; 32 synthetic tests passed; 40 targeted ignore and 265 required-path checks passed; four template gates passed; dry run: 188 eligible files, four holds, no output | [PR #10](https://github.com/gayashan-indrasankha/Zinema.lk/pull/10), baseline tip `76ec6df6ef75443f76db963d7ec47a7ab7b63248`; [PR #11](https://github.com/gayashan-indrasankha/Zinema.lk/pull/11), final handoff SHA reported after commit/push | AI-assisted grouping, explicit staging, verification and PR preparation; owner reviews and approves merges | Scans cover exact staged text and outgoing changes, not every historical secret; existing binaries excluded from text scans and unchanged; no feature/runtime verification, real export, deployment or account changes |

Commit sequence before this final handoff commit:

- `70d4635`: `docs(baseline): record ZL-9 source audit and integrity manifest`
- `76ec6df`: `docs(provenance): document ZL-9 project history and AI assistance`
- `4748282`: `chore(security): extend ZL-10 private artifact exclusions`
- `93a4d51`: `docs(config): describe disabled future AI and delivery settings`
- `78a665d`: `feat(export): add deny-by-default public export preparation`
- `e726552`: `test(export): cover exclusion rules and filesystem safety`
- `7aa28ff`: `docs(security): document ZL-10 export policy and review gates`

The eighth commit records the ZL-10 contribution entry and this recovery handoff.
Both PRs target `main`. Review/merge #10 first and then #11 with **Create a merge
commit** to retain the incremental history; no PR is merged by this follow-up.
The original baseline document/checksum bytes remain unchanged, and the original
contribution record remains intact. Recommended next issue remains **Jira ZL-11**;
it has not started.

## ZL-9 - Documentation-only authority recheck (2026-10-23)

The existing extracted workspace, working tree and uncommitted content remain
authoritative; the ZIP is historical and is not required for acceptance. The
original audit/provenance text above is retained. Its untracked/uncommitted-output
statements describe the original ZL-9 handoff, not the present committed state.

At this recheck's start, HEAD was
`a2e5aebf55320e891f5580e7b8825bb32e72cdd8` on
`feat/zl-10-safe-public-export`: 265 tracked files, clean working tree and empty
index. All three ZL-9 documents were already committed by the later authorized
publication work. Current eligible-input hashing checked all 176 original paths:
173 match; `.gitignore` and recovery STATUS/DECISIONS differ as explained by the
existing records. BASELINE section 16 records their current hashes. The original
manifest and all 176 entries remain unchanged; no original source is restored.

This continuation only adds historical/current-state clarification to BASELINE.md
and appends this entry. It is AI-assisted documentation/verification, not a new
source audit, feature implementation or claim of original authorship. These edits
remain uncommitted and are not represented by HEAD. Existing contribution history,
application/source/data, recovery records, Git references and index are preserved.
No archive, live service, database, runtime test, staging/commit/push or further
ZL-10 work is involved. Acceptance remains COMPLETE within the bounded ZL-9 scope;
runtime/security/production readiness and excluded private contents remain unverified.

Executed verification: 176 eligible inputs unchanged during this follow-up;
manifest bytes and the complete prior contribution record preserved; ten local
links, code fences and `git diff --check` passed. Gitleaks 8.30.1 scanned only
the three ZL-9 documents through fully redacted stdin: exit 0, no findings or
report files. Bounded privacy checks passed. Only the two authorized Markdown
files are modified; HEAD/tree/branch and the empty index are unchanged.

## ZL-11 - Module inventory and reversible restructuring blueprint (2026-10-23)

Owner-authorized static architecture phase: COMPLETE within inventory/design
scope. The existing extracted workspace and uncommitted changes were authoritative;
no ZIP was used. Phase entry was HEAD
`a2e5aebf55320e891f5580e7b8825bb32e72cdd8` on
`feat/zl-10-safe-public-export`, with an empty index and existing modifications
to BASELINE.md and this log. Previous contribution content is retained in full;
the existing BASELINE edit is not changed by ZL-11.

| Date | Jira issue | Work completed | Files | Verification actually executed | AI assistance | Limits / next task |
| --- | --- | --- | --- | --- | --- | --- |
| 2026-10-23 | ZL-11 | Static first-party dependency/route/DDL inventory, 51-group dispositions, current/target architecture, 28-item debt register, reversible migration and test gates; planned-only AI/privacy/delivery boundaries | New docs/architecture/MODULE_INVENTORY.md, CURRENT_ARCHITECTURE.md, TARGET_ARCHITECTURE.md, MIGRATION_PLAN.md, TECHNICAL_DEBT.md; append this log | Direct PHP 8.2.12 -n -l: 101 first-party files passed; Node 24.13.1 --check: 36 files passed. 150 literal PHP include candidates exist; three computed paths statically traced. 16 bot endpoint files exist; eight referenced targets absent. Inventory accounts for 265 tracked paths, including 70 grouped vendor files. 126 local doc links/code fences/privacy checks passed. Gitleaks 8.30.1 redacted stdin scan of five new docs: exit 0, no findings/reports. 188 pre-append protected hashes unchanged; HEAD/tree/branch/index unchanged | AI-assisted source inspection, classification/design, documentation and local verification; no invented per-line authorship/model attribution | Dynamic/deployment dependencies, installed schema, app runtime and rights unverified; no tests installed or app/project scripts run; ZL-12 recommended, not started |

Module statistics are responsibility groups, not feature counts: KEEP 10,
REPAIR 22, ISOLATE 12, ARCHIVE CANDIDATE 2, UNRESOLVED 5. Two excluded/metadata
groups are additional and not counted as tracked-source modules. Archive
candidates remain in place; no file is approved for deletion. Planned AI,
voice, ranking/retrieval and delivery simulation are proposals only.

Important observations include PDO/MySQLi and SQL-generation mismatches,
token validity/acknowledgement gaps, relative-URL/state-path coupling, conditional
public-feed extraction and unprotected generated warmer-state filename. These
are documented for separate work, not repaired or demonstrated as live exploits.
The direct git check-ignore probe for the warmer-state path returned exit 1
(not ignored); its actual contents were not opened.

Parser commands were invoked directly over approved first-party files; neither
application modules nor existing project start/test/check scripts were executed.
The historical ZL-10 result of 32 synthetic export tests remains historical and
is not counted as a ZL-11 runtime test. No server, database, scheduler, provider,
real media/account, installation, public export or network service was used.

Final artifact/source/prefix checks are repeated after this append and reported
at handoff. Only the five new documents and this append are ZL-11 changes;
recovery records and original baseline/checksum files remain unchanged from
phase entry. No staging, commits, branches, reset, history rewrite or publishing.
Future task: **Jira ZL-12 - Reorganize source into modular folders without
breaking URLs**, beginning with the prerequisite fixture/compatibility/rollback
gates in MIGRATION_PLAN. Do not bulk-move source, change document roots or start
that task automatically.

Final ZL-11 verification passed after the append: 187 protected existing text
files byte-identical, complete previous contribution prefix retained, and only
the intended log append differs among 188 protected paths. All 128 local links
across six artifacts resolve; code-fence/privacy/whitespace checks passed.
Gitleaks scanned the six artifacts with full redaction: exit 0, no findings or
report files. Original manifest, existing BASELINE edit, HEAD/tree/branch and
empty index are preserved. Application runtime and Mermaid rendering remain
NOT VERIFIED; no later Jira phase or Git publication was performed.


## ZL-11 — Production architecture extension and supplied Jira mapping (2026-10-23)

This entry appends to the completed static inventory record; it does not replace
prior contribution history or restart ZL-9/ZL-10. The authoritative input remains
the extracted workspace and current working-tree content, including owner edits.
The original ZIP is historical only. HEAD remains
a2e5aebf55320e891f5580e7b8825bb32e72cdd8 on feat/zl-10-safe-public-export;
the index was empty. The pre-existing BASELINE edit and baseline manifest remain
preserved. Existing architecture drafts were extended, not discarded.

**ZL-11 acceptance: COMPLETE for architecture documentation and planning only.**
Production readiness is NOT ESTABLISHED. No issue is assumed complete from local
source, and no Jira task, issue status or configured dependency was changed.

Created five focused production designs:

- [STREAMING_ARCHITECTURE.md](architecture/STREAMING_ARCHITECTURE.md)
- [AI_ARCHITECTURE.md](architecture/AI_ARCHITECTURE.md)
- [WHATSAPP_DELIVERY_ARCHITECTURE.md](architecture/WHATSAPP_DELIVERY_ARCHITECTURE.md)
- [PRODUCTION_DEPLOYMENT.md](architecture/PRODUCTION_DEPLOYMENT.md)
- [PRODUCTION_GAP_ANALYSIS.md](architecture/PRODUCTION_GAP_ANALYSIS.md)

Carefully updated the existing [inventory](architecture/MODULE_INVENTORY.md),
[current architecture](architecture/CURRENT_ARCHITECTURE.md),
[target architecture](architecture/TARGET_ARCHITECTURE.md),
[migration plan](architecture/MIGRATION_PLAN.md) and
[debt register](architecture/TECHNICAL_DEBT.md). Original source/path dossiers,
51 group classifications, route/DDL evidence and M01–M21 relocation mappings remain.
The extension documents eight journeys, 24 separate capability gaps, minimum/growth
private-origin HLS delivery, AI/voice contracts, conditional messaging, deployment,
security/rights, operational recovery and reversible release gates.

A targeted static check found the existing trailer thumbnail exec command in
admin/trailers.php:17–29 interpolates an unquoted upload-derived path. Added TD29,
bringing the source debt register from 28 to 29 items. No exploit, upload, decoder
or application handler was executed. The same targeted first-party search found
no Hls/m3u8/dash.js integration or rights-expiry/territory/entitlement/rights-holder
field matches. These bounded searches do not establish deployed-system absence.

The owner supplied 12 epics and 43 story keys/titles as the planning reference,
reporting verification through their connected Atlassian account. All module
dossiers, 28 prior debt items, TD29 and 24 gap categories now map to relevant existing
stories; no new keys or duplicate issues were invented. Dependencies inferred by
architecture are labelled **PROPOSED DEPENDENCY**, not independently checked Jira
links. Earlier read-only backlog attempts failed: the web reader could not access
the page, and browser/desktop runtime initialization reported a missing path.
No further Jira browser access was attempted after the supplied-list instruction.

Executed checks in this extension, before this append:

- Read-only Git status/HEAD/tree/branch/index inspection and metadata/hash capture;
  reused the prior static inventory and parser results rather than rerunning the audit.
- Trusted inline Node standard-library documentation checker: ten architecture
  files plus contribution log; 187 local links resolved; balanced code fences,
  no trailing whitespace/replacement characters, bounded sensitive-pattern checks;
  195 nonvendor tracked paths accounted for plus 70 grouped vendor paths.
- Count assertions passed: 51 groups, 24 gap categories, 43 supplied stories,
  eight explicit journeys and 29 debt dossiers.
- Gitleaks 8.30.1 redacted stdin scan of those eleven artifacts: exit 0, no findings
  and no report files. Scanner stdout/stderr was captured, not exposed raw.
- git diff --check: exit 0. Git HEAD/tree/branch and empty index unchanged.
- SHA-256 comparison: all 258 tracked text paths represented by the preserved
  eligible-source baseline plus extension-start snapshot matched before this append.
  This comprises the 188 eligible source/document paths plus 70 bundled vendor text
  paths; four example templates and three binaries are outside this text-hash scope.
  The existing contribution log was still exactly 25,695 bytes before appending.
  Final verification after the append checks the full previous prefix by its digest,
  and requires only this intended log append to differ among the 258 text paths.

The earlier continuing ZL-11 parser results remain: PHP 8.2.12 -n -l passed for
101 first-party files and Node 24.13.1 --check passed for 36 JS/CJS files. They were
not rerun in this production extension; they are syntax evidence, not functional
tests. Historical ZL-10 synthetic export tests were not rerun or relabelled app tests.
No project script, service, bot, scheduler, database, encoder, media player, cloud,
AI/voice or payment integration was executed. No source/configuration/migration/
dependency change, file move/delete, installation, Git mutation, commit, branch,
push, deployment, paid operation or live infrastructure contact occurred.
The only network attempt was the owner-supplied Jira planning page; no application
external integration or stored credential was used.

AI assistance supported static interpretation, planning, documentation and bounded
validation. No unverifiable model/per-line authorship or historical feature dates
are claimed. Provider facts/prices are **REQUIRES CURRENT VENDOR VERIFICATION**;
rights/privacy/legal clearance, installed schema, actual runtime behavior, hosting
and recovery/SLO objectives remain unverified future acceptance gates. Mermaid
source is provided but not rendered. Secret scans are bounded artifact checks,
not a complete historical/security/legal assessment.

Recovery STATUS/DECISIONS remain preserved historical inputs because the current
request limits writes to architecture and contribution documentation. Current
handoff decisions/status are recorded here and in the architecture documents.

Exact next recommended task: **ZL-12 — Refactor modules incrementally without route
regressions**, beginning with current-state reconciliation and the smallest covered
leaf. Missing environment/regression gates map to ZL-13/31; do not move affected
code before those gates are available. ZL-12 has NOT started.

Final production-extension verification: all 197 local links across eleven artifacts
resolve; 51 groups, 24 gaps, 43 supplied stories, eight journeys and 29 debt items
match their assertions. Of 258 protected tracked text paths, 257 remain byte-identical;
only this authorized contribution append differs, and its complete previous
25,695-byte prefix matches the captured SHA-256 digest. HEAD/tree/branch/index and
the pre-existing BASELINE/manifest content remain unchanged. A final whitespace
check first reported an extra blank line at this log EOF; it was removed before
repeating the document/secret/whitespace and preservation checks. No source was
changed to make a check pass.


## ZL-9/ZL-11 — Incremental review publication (2026-10-23)

The owner's explicit request authorizes publication of the already prepared
documentation through incremental commits and review PRs. Earlier statements that
no commits were made remain accurate historical audit records. This follow-up
does not implement architecture or start ZL-12.

- [PR #12](https://github.com/gayashan-indrasankha/Zinema.lk/pull/12):
  docs/zl-9-authority-clarification -> main, two commits for baseline authority
  clarification and its contribution entry; tip 087b9d3b3f009ce5d820f316b80d0d868802d7c0.
- [PR #13](https://github.com/gayashan-indrasankha/Zinema.lk/pull/13):
  docs/zl-11-production-architecture -> main, ten architecture-document commits
  and this final contribution/recovery handoff. Eleven ZL-11 commits plus the two
  shared clarification ancestors; thirteen unique new commits overall.
- Merge #12 first, then #13, using **Create a merge commit** to preserve the
  incremental history. Both target main; #13's shared diff reduces after #12.
  No PR was merged. #13 was initially draft while the final handoff was prepared;
  final ready-for-review state, remote SHA and clean status are checked after push.
- Staged exact document paths step by step, beginning with baseline/provenance,
  then module inventory/current/target, specialized streaming/AI/delivery/operations,
  gaps/debt/migration and handoff. The ZL-9 contribution prefix was staged directly
  through the index, retaining the full working file and all later ZL-11 content.
- Before every commit: reviewed status and working/staged changes; document/link/
  fence/count/privacy and staged whitespace checks passed; Gitleaks 8.30.1 scanned
  exact index text with full redaction; five synthetic sensitive-ignore probes
  passed. Three unchanged existing binaries were excluded from text scans.
- Executed node scripts/check-source.cjs with PHP_BINARY pointing to the available
  PHP 8.2.12 binary and REQUIRE_PHP=true: 164 PHP and 36 JS/CJS files, zero syntax
  failures. Later documentation commits reused this result only while preservation
  checks confirmed all source bytes unchanged. No functional/runtime test claimed.
- Complete outgoing-history scans passed before the initial pushes; the final
  handoff is checked before commit and the resulting range again before final push.
- All 272 snapshot paths outside this log and the two recovery records remain
  byte-identical; the entire prior contribution prefix is retained. Source, SQL,
  dependencies, examples/binaries, baseline manifest/clarification and architecture
  bytes remain unchanged. Prior recovery content is retained as historical records.

AI assistance supported commit grouping, explicit staging, validation and PR
preparation. Full commit results are recorded in Git; no development dates,
feature completion or per-line authorship is invented. See
[recovery status](recovery/STATUS.md) and [decisions](recovery/DECISIONS.md).
Final commit hash and ready PR state are reported after publication.
Scans cover exact staged/outgoing content, not every historical/private asset;
rights, vendor facts, runtime and production readiness remain unverified.
No real export, deployment, live integration, database/account operation,
history rewrite, credential change or additional Jira task was performed.


## ZL-12 — Safe incremental leaf refactoring (2026-10-23)

**PARTIAL overall; selected B1/B2 batches implemented and functionally verified.**
Current extracted workspace remains authoritative. Started at
804ec5ab07503c3fb0c311b4349443b87184e28f on docs/zl-11-production-architecture,
with 275 tracked paths, clean worktree and empty index. No prior user edits existed.

Moved the disabled ads implementation to apps/web/src/Views/Ads.php and the pure
bot configuration parser to whatsapp-bot-deploy/src/config/environment.js.
Both implementations retain their original bytes. Old files remain small PHP/
CommonJS shims; eight PHP callers and three bot import contexts retain their paths.
No public handler/asset/rewrite, DB/schema, entry guard, worker, state/media root,
dependency, lockfile or framework was changed.

Added tests/fixtures/ads-contract.php and three tests/refactor/*.test.cjs files.
Before edits: 35 leaf contracts and 32 existing synthetic export tests passed.
After B1 with dependency regressions: 73 passed; after B2: 78; final suite:
**85 passed, zero failed, zero cancelled, zero skipped**.
Executed node --test tests/refactor/*.test.cjs scripts/test-public-export.cjs with
PHP_BINARY set to the available local PHP executable. Direct parser loop:
166 PHP (-n -l) / 40 JS/CJS (--check), zero failures. Includes new untracked code;
parser counts include bundled PHP vendor files. No app runtime pass is implied.

Reviewed exactly two new source paths in the existing exporter, versioned as
zl12-leaves-v1. Required shim→module pairs fail closed at planning, export-before-
write and manifest validation. Eleven new synthetic packaging regressions passed;
unknown app files stay excluded before opening. Actual-source no-write plan:
190 eligible files, the same four manual holds, no output created. Synthetic fixture
exports only; no real source export or publication.

Boundary tests compare legacy/direct PHP contracts and identical CommonJS exports,
resolve imports from three real consumer contexts without executing those consumers,
and rehearse PHP/Node rollback and reapply in isolated temporary fixtures.
Rollback retains the canonical implementation, checks for subsequent owner edits,
then copies current-session bytes to the old slot and reviews paired export policy.
No historical checkout/ZIP reset or live private-state restoration.

Updated [execution report](architecture/REFACTOR_EXECUTION.md),
[current architecture](architecture/CURRENT_ARCHITECTURE.md), root developer README,
[security policy](SECURITY_EXPORT.md) and recovery status/decisions while retaining
previous contribution content. AI assistance supported analysis, characterization,
implementation, tests and documentation; no historical feature dates or per-line
original authorship are claimed.

Full HTTP/Apache/public-access denial, DB/auth/session, browser/player and bot/
worker behavior remain BLOCKED/NOT TESTED. Existing ZL-11 defects, rights/vendor
uncertainties and production readiness are not resolved by this refactor.
HEAD/tree/branch/all refs/index remain unchanged; no staging, commit, branch,
push, deployment, dependency install, production contact or live integration.
Final hash/link/secret/whitespace checks are recorded after this append.

Exact next task recommendation: **ZL-13 — Build reproducible local staging and CI
environments**. Further stateful changes require its isolated route fixtures and
the relevant ZL-14/31 schema/regression gates. No ZL-13 or later issue was started.

Final ZL-12 checks: 266 of 275 original tracked paths byte-identical; nine intended
changes; both relocated implementations match their starting hashes; prior log
prefix and Git state retained. All 48 local links, fences/privacy and whitespace
checks passed; Gitleaks redacted scan of 16 changed/new artifacts returned exit 0,
no findings/reports. Final regression run: 85 passed, zero failed/cancelled/skipped.
Historical manifest retains its bytes; the new README/two-shim deltas are explicit
in REFACTOR_EXECUTION, alongside the three pre-existing workflow deltas.


## 2026-10-23 - ZL-13 - Reproducible development, local staging and CI

AI-assisted implementation with Codex; the owner retains review/approval.
Built from the current extracted workspace, preserving uncommitted ZL-12 work.
Status: PARTIAL because canonical application schema/business integration remain
blocked by ZL-14; GitHub-hosted workflow execution is pending.

Added compose.yaml, exact .dockerignore/infra/environment-files.json, PHP/MySQL
Dockerfiles, Apache/PHP configuration, safe init/entrypoint hooks, three fictional
environment templates, minimal health/CLI DB audit, scripts/environment.cjs and
scripts/verify.cjs, tests/environment fixtures/tests, and .github/workflows/ci.yml.
Added five docs/development guides. Updated README setup links, architecture,
security export and recovery records; appended Linux line-ending rules. The sole
application edit is the guarded local db hostname exception in PHP environment
configuration. Existing PHP/Node feature code, SQL and ZL-12 leaves are preserved.

Architecture: two services, read-only/non-root/no extra capabilities, internal
unpublished MySQL, scoped loopback web ingress, independent local credentials and
volumes. No bind mounts or live integrations; Node is a CLI test runtime. Web
egress remains possible and no claim of firewall isolation is made. Empty DB
with DML-only app access; no fabricated schema or production data. Sessions use
tmpfs; no media/SQL/session/private-store files are in the final image.

Executed: local host suite and dev/staging/CI image suites each pass 125 tests,
zero failures/skips; 109 PHP/42 JS parser passes per run. Docker image build and
three profile startups pass; health 200/200/503 and eight safe HTTP paths per
profile pass. Driver/least-privilege audits show zero app tables. Runtime
credentials/volume/port isolation, wrong fictional DB password, DB stop/recovery,
web restart, actual Docker ignore fixture and exact 190-file final image pass.
Gitleaks 8.30.1: 187 text inputs, redacted/no findings. actionlint 1.7.7 passes;
official download checksum verified; shellcheck/pyflakes not run. GitHub Actions
not executed remotely. Normal down retains all three database volumes.

Failures corrected: synthetic username-prefix mismatch; Windows Compose discovery
paths; overly broad initial Docker ignore exceptions (build stopped, fictional
matcher test added); missing .dockerignore image input; internal-only publishing
failure; transient HTTP timeout during stopped-DB resolution (bounded resolver
settings and classified request errors added, outage check subsequently passed).
Aborted local BuildKit cache was not inspected or pruned. See the operations
record for exact limits. No full website/browser/schema/auth/player acceptance
is claimed; no provider npm installation/vulnerability audit or real integration.

No Git staging, commits, branch changes, resets, pushes, deployment or production
contact. Preserve previous contribution entries and historical checksum records.
Production prerequisites include ZL-14 migrations/fixtures/access reconciliation,
business/browser regressions, approved rights/storage/streaming, deployment,
secrets management, backup, observability and supported-runtime upgrade.

Final ZL-13 checks: expanded Gitleaks scan covers 198 reviewed text inputs,
exit 0; full preserved/new source parses 170 PHP and 43 JS without failures.
All 58 local documentation links, fences and diff whitespace checks pass.
Preservation: 274/282 inputs byte-identical, eight intended changes, no missing
or unexpected changes; prior contribution prefix and HEAD/tree/branch/refs/index
unchanged. Post-teardown inspection confirms zero owned containers and three
retained database volumes. New public-candidate files: 25; no publication occurred.


## 2026-10-23 - ZL-14 - Verified partial schema and safe migration tooling

AI-assisted engineering with Codex; owner retains review/approval. Status PARTIAL.
The owner confirmed no sanitized complete DDL is available and restricted migration
scope to four independently defined tables. The private Database.sql was not
accessed/copied; no missing catalogue/account/social/token model was invented.

Inventory: six historical SQL references, two embedded CREATE definitions and
134 legacy first-party PHP/JS files. Static candidate extraction/manual filtering
identified 34 queried names; the evidence index covers 39 defined/referenced
tables/views, including 24 without complete permitted DDL. Exact consumers,
observed names, missing contracts and required owner evidence are documented.
Historical SQL, private files and all legacy application source remain unchanged.

Implemented database/Runner.php, migrations.json and four LF/checksummed CREATE
files for site_settings, rate_limits, movie_parts and episode_parts. Added CLI
migrate.php, new independently labelled/tmpfs-only database-test.cjs, schema/
failure/access/digest fixtures and offline Node query-shape tests. Extended the
existing exact Docker input manifest/checker, line-ending rules, verify runner,
one context-protection assertion and existing CI workflow. Added six database
guides plus a machine-readable evidence index; updated setup/architecture/security/
recovery docs. No competing ORM, runtime DDL rewrite or global PDO mode change.

Runner: validates order/file SHA before connect; explicit test role/local target,
server UUID and hashed synthetic marker before schema writes; advisory locking;
running/applied/failed ledger and attempts; exact column/default/index/constraint
checks; checksum/history/ledger drift rejection; post-DDL forward reconciliation
only for a verified match. Runtime and migration privileges stay separate. No
DROP, reset, destructive conversion or real-volume migration is provided.

Actual final execution: host/image 145 synthetic tests passed, zero failed/skipped;
115 PHP / 44 JS context parser passes. Dedicated database assertions: 42 passed,
including fresh/repeated install, two original part CREATE fixture adoption and
row preservation, uniqueness/nullability, bound values/DML rollback, real post-DDL
SQL failure/recovery, interrupted-state reconciliation, schema/ledger/checksum
drift, concurrent locking, wrong marker/UUID and invalid synthetic credentials.
Original PDO/MySQLi modules work when explicitly included; repeated PDO named
parameters remain supported. Two preserved helpers' runtime CREATE calls fail
under the DML-only identity: observed unresolved compatibility blockers.

EXPLAIN selects unique_movie_part, setting_key and idx_ip_action on tiny fixtures;
no measured performance improvement claimed and no redundant legacy index dropped.
No new FK/cascade to missing catalogue parents. Offline Node reader tests use a
mock pool; actual mysql2/full delivery/database compatibility remains unverified.

Synthetic mysqldump -> AES-256-GCM memory roundtrip -> separate empty marked restore
matched schema/data hashes and all four restored ledger versions. No plaintext/key
file or operational backup artifact. Paused-server failure/recovery passed after
scoping a mysqlnd read timeout to new migration connection construction and restoring
the previous setting. Legacy application PHP configuration/behavior is preserved.

Failures investigated: slow initial string scanner replaced with a bounded linear
scanner; UI labels removed from static table findings. An outage initially exceeded
the intended deadline; the synthetic server was safely unpaused and cleaned up.
The final scoped timeout test passed. Inherited Compose image labels caused a
correct ownership refusal during a transient run; new test containers now use
independent project/run labels without weakening safety checks. Exploratory PHP
edits were withdrawn and exact starting hashes verified after the clarification.

Image build/context guards and local startup/HTTP containment passed; managed CI
DB audit still shows zero application tables, DML-only account, readiness false.
Gitleaks 8.30.1 passed on 201 reviewed context text inputs. actionlint 1.7.7 passed;
hosted GitHub Actions NOT RUN, shellcheck/pyflakes NOT RUN. No provider, production,
public staging, paid/cloud resource or actual media/account activated. Normal CI
shutdown retains its volume; disposable cleanup never removes existing volumes.

Remaining: 24 complete schema definitions, conflicting token/message/view/routine
contracts, runtime DDL/PDO route wiring and full application/auth/social/browser/
mysql2 compatibility. Full upgrades, canonical referential integrity, real backups,
production migration and rights/AI/streaming models remain unverified/out of scope.
Do not start ZL-15 automatically. Resume ZL-14 only with approved missing evidence
and an explicit compatibility scope. No Git staging, commits, branches, history
rewrite, export, push or deployment. Final expanded scan/hash/link/teardown results
are recorded below after validation.

Final ZL-14 evidence: 307 starting inputs retained, 289 byte-identical, 18 intended
shared infrastructure/documentation deltas and 21 new files. All application and
tracked historical SQL bytes, baseline checksum files and the prior contribution
prefix are preserved. Full syntax sweep: 176 PHP / 45 JS, zero failures. All 84
local links, fences and whitespace checks pass. Final image audit: 204 files with
exact matching hashes, no missing/extra paths. Expanded redacted Gitleaks scan:
219 reviewed text files, exit 0/no findings (not private/history/binary assurance).
No ZL-14 containers/networks or managed containers remain; all three managed DB
volumes are retained. HEAD commit/tree, local branches/tags and empty staged state
are unchanged. All-refs equality did NOT pass: origin/main changed from
45b9309f4a25 to 94f7708be599 and origin/HEAD followed during the session. No remote
Git/ref-write operation was issued by this task; the observed tracking change is
recorded and left intact. Working-tree preservation, not remote ref stability,
establishes this task's source baseline. ZL-14 remains PARTIAL.

## 2026-10-23 - Owner-authorized incremental publication of ZL-12 to ZL-14

Continued the current extracted workspace with all existing source and uncommitted
work. The owner explicitly requested staging, logical Conventional Commits,
feature branches and PRs for review. Verified existing ZL-11 PR #13 merged and
origin/main 94f7708 has the same tree as implementation HEAD 804ec5a.

Prepared two review units: refactor/zl-12-leaf-modules (four commits, PR #14), then
feat/zl-13-14-environments-migrations with separate container, probe, migration,
disposable-test, context-tooling, CI and documentation commits. The latter depends
on the first; no PR merge or main write is performed. Final packaging has both
PRs target main: merge #14 first, then #15 using merge commits to retain history.
Shared ancestors disappear from #15's diff automatically; no retargeting is needed.

Each exact staged snapshot is scanned with Gitleaks 8.30.1, with explicit stage
membership, diff/whitespace checks and relevant parser/tests. Final refactor
snapshot: 85 passing tests. Integrated infrastructure snapshot: 145 passing tests,
115 PHP / 44 JavaScript parsers, no failures/skips. Host PHP is C:/xampp/php/php.exe
8.2.12 and Node is 24.13.1; the first attempt lacked PHP on PATH and was rerun
successfully using that executable. Staged tests use isolated copies of index
blobs, so unrelated working-tree files cannot make them pass.

No new application behavior edits, reset, stash, amend, history rewrite, private
Database.sql inspection, credentials publication, production contact or automatic
ZL-15 work. Refactor documentation clarifies its historical audit boundary; prior
contribution content remains intact. Earlier Docker/database execution evidence
is preserved rather than reported as new publication execution. Final remote,
CI and preservation evidence is recorded in the publication handoff. ZL-12,
ZL-13 and ZL-14 remain PARTIAL regardless of publication.

Final publication handoff: PR #14 (four refactor commits) and PR #15 (ten additional
commits including this handoff) are open and ready for review:
https://github.com/gayashan-indrasankha/Zinema.lk/pull/14 and
https://github.com/gayashan-indrasankha/Zinema.lk/pull/15. Both target main; neither
is merged. Complete outgoing history and every cumulative staged snapshot passed
Gitleaks with full redaction. The integrated staged suite passed 145 tests and
115 PHP/44 JS parsers; actionlint and all 98 inspected local links/fences passed.
All 328 initial eligible inputs remain: only REFACTOR_EXECUTION, CONTRIBUTIONS,
DECISIONS and STATUS documentation bytes changed during publication. Every initial
source/configuration/test/SQL/baseline byte and the complete prior contribution
prefix is preserved. Original 64 changed/new paths are committed. Before this
handoff, index/worktree were clean and both published branch tips matched their
local tips. Final post-handoff hash/status is reported separately to avoid a
self-referential commit hash. Actual hosted PR/push runs for 4c183fb are in progress;
final-tip checks must be verified after this handoff push, not inferred from local
passes. No local services, database operations, live integrations or production
resources were used by this publishing phase.

## 2026-10-24 - Jira ZL-15 licensed catalogue and rights foundation (PARTIAL)

AI-assisted development: Codex inspected the current extracted source and existing
recovery/database records, drafted the current-to-target map before module changes,
and implemented and tested the bounded foundation under owner instructions. This
records actual new work, not original authorship, commercial rights verification
or a claim that Jira is complete.

Implemented apps/web/src/Content/ contracts, validation, rights policy, protected
workflow and PDO repository. Added default-deny authority/canonical lookup ports,
asset-specific holder/grant/territory/channel/time checks, independent human review,
publication/unpublication/revocation, immutable canonical associations, bounded
metadata discovery and CAS/audit atomicity. Public playback remains denied. No
legacy admin/UI/API/upload, bot, extractor, AI or real media integration was added.

Added database/content-migrations.json, explicit ContentMigrations loader, versions
0005 content_rights_records and 0006 content_rights_events, and CLI/test integration.
These are interim workflow/audit tables, not replacement catalogue tables or a
fully normalized production schema. No FK to unverified catalogue/users. Original
four SQL files/manifest/checksums remain unchanged. Fixed one reproduced Runner
parser defect: only integer display widths normalize, preserving VARCHAR(20).
No legacy PHP/MySQLi/PDO business behavior was altered.

Extended reviewed Docker context/ignore, verify/test harness, workflow and environment
context test. Added tests/content/ and six docs/content/ guides; updated README,
architecture, migration/CI/security guides and recovery records. No new dependency.
A dedicated synthetic service role has records SELECT/INSERT/UPDATE, audit
SELECT/INSERT and marker SELECT only. Current bootstrap is CLI-only; real RBAC,
CSRF, catalogue writes/lookup and public streaming remain explicit integration holds.

Executed: baseline host 145 passes; final host/image 246 tests, no failures/skips,
128 PHP/46 JS parsers (host PHP 8.2.12/Node 24.13.1; image 8.2.35/24.22.1). Broader
source parsers: 189 PHP/47 JS, zero failures. Disposable MySQL 8.4.13 rights checks
pass 13 migration and 19 service assertions, a real concurrent editor race (one
commit/one stale rejection) and excessive-privilege refusal. Original 42 database
assertions, legacy access probes, encrypted synthetic backup/restore and outage
recovery pass. Existing settings/rate-limiter DDL blockers remain, not concealed.
Managed CI audit still has zero app tables; live/dependencies 200, ready 503.
Final image has exactly 222 approved inputs with no extra/missing/hash mismatch.
Context Gitleaks passes 219 text inputs; final expanded scan and teardown evidence
are appended below. Actionlint 1.7.7 passes; shellcheck/pyflakes and hosted execution
of these uncommitted changes are NOT RUN.

Failures were investigated: scan flagged a synthetic setting key (fixture simplified,
no allowlist); new migration exposed the VARCHAR width parser defect (failing
regression then fix); driver native-prepare false/zero shape required precise
acceptance in the new repository. Corrected runs pass and failed temp runs cleaned
up. No raw credential, contract, user data or protected dump was exposed/read.

Initial state: clean index/tree, 328 tracked files, branch
feat/zl-13-14-environments-migrations at 958dd9eaf97c72ea6e76648eae6814d775f03641.
No staging, commit, branch/ref write, reset, stash, history rewrite, fetch or push.
Prior contribution content is preserved as a byte prefix. All legacy website/bot/
extractor, historical SQL, original four migrations and baseline records are
preserved. No production database, real WhatsApp/extractor/provider or ZL-16 work.

ZL-15 remains PARTIAL: missing canonical catalogue/accounts/admin DDL and verified
RBAC/CSRF, unverified legacy route/browser compatibility, real licensing/evidence
operations, normalized production rights relationships, entitlement and protected
media delivery block completion. See docs/content/CONTENT_TEST_RESULTS.md for
actual commands/scenarios, acceptance gaps, dependency boundaries and Jira comment.

Final ZL-15 verification: all 328 initial eligible files remain (312 byte-identical,
16 reviewed tooling/documentation changes), plus 24 new files; no unexpected change
or deletion. All website/bot/extractor source, historical SQL, four original SQL
migrations/manifest, dependency assets and baseline records are byte-preserved.
The entire previous contribution record is an exact prefix. HEAD remains
958dd9eaf97c72ea6e76648eae6814d775f03641; branch/local heads/tags are unchanged and
index is empty. Changes remain unstaged/uncommitted; no Git publishing occurred.

Final host and image suites pass 246 tests and 128 PHP/46 JS parsers; full eligible
source parsing passes 189 PHP/47 JS. Rights DB results: 13 migration +19 service
assertions, one winner/one rejected stale editor in actual concurrent processes,
and excessive-privilege refusal. Original 42 DB assertions and synthetic encrypted
backup/outage checks pass. Image audit: exactly 222 paths, no extra/missing/hash
mismatch. Expanded Gitleaks 8.30.1 scan: 233 reviewed text inputs, exit 0, no findings;
private legacy stores/history/binaries excluded. 90 inspected local links, Markdown
fences/new-text checks and git diff --check pass. Actionlint passes; shellcheck/
pyflakes and hosted execution of these changes are NOT RUN. Unconfigured content
migration CLI returns MIGRATION_TARGET_REFUSED before a connection attempt.

Local HTTP probes for new Content/bootstrap.php, content-migrations.json and content
test driver paths all return 404 under the website document root. Managed CI
connectivity/audit still shows zero app tables and readiness 503. Normal shutdown
completes: zero ZL-15 containers/networks, zero managed containers, all three original
managed database volumes retained. No production, real users/media/agreements,
WhatsApp, extractor, paid AI, private dump or ZL-16 work. ZL-15 remains PARTIAL.

## 2026-10-24 - ZL-16 catalogue API/search and entitlement boundary (PARTIAL)

AI-assisted implementation by the coding assistant, reviewed through executed
local checks; no claim of independent human review or legal licence approval.
The owner explicitly requested ZL-16 against the current extracted workspace,
including all uncommitted ZL-15 work. Read/audited existing route consumers,
verified partial database/rights contracts, and recorded the initial API design.

Added apps/web/src/Catalogue/ (7 PHP files), the additive website
api/catalogue/v1.php entry, tests/catalogue/ (7 test/fixture files) and seven
API guides. Updated reviewed context/.dockerignore, verify/database harness, CI,
README, current architecture, security/CI guides and recovery records. No existing
website/bot/extractor or Content module, SQL definition or migration manifest was
changed. No guessed canonical/account/subscription schema or new index.

Implemented native prepared title search, metadata filters, four allowlisted
sorts, contextual signed keyset pagination, canonical/parent checks, separate
current asset rights and requester entitlements, bounded validation/DTO/error/
resource controls. Public factory stays disconnected with 503; no real auth,
territory/licence/grant provider or media delivery was fabricated. Playback and
preview remain unavailable even if synthetic eligibility passes.

Executed: host/image parser and full suite 332 passes, no failures/skips; 141 PHP /
48 JS context parsers; full eligible syntax 202 PHP / 49 JS. Catalogue MySQL:
13 migration prerequisites +24 assertions on 524 fictional records, SELECT-only
reader DML refusal, excess privilege refusal, outage/recovery. Existing rights
suite 13+19, actual concurrent-edit conflict and privilege gate pass. Original
42 database assertions, legacy PDO/MySQLi probes, encrypted synthetic backup/
restore and outage/recovery pass. HTTP tests/probes verify additive safe failure,
forged input rejection, metadata filtering in a test-only composition and hidden
ID denial. Legacy full-browser flows remain unverified.

Measured 20 service reads/page size 20: p50 70.69 ms, p95 82.60 ms, max 102.12 ms,
max JSON 8,886 bytes. Search ALL/filesort; detail existing unique reference index.
Not a scalability/SLA claim. Initial fixture failures were corrected without
weakening CLI bootstrap or clock-rollback guards; final regressions pass.

Image audit matches all 237 reviewed host paths/hashes. Context Gitleaks passed
234 text files; final expanded scan and preservation/teardown below. Actionlint
passes, shellcheck/pyflakes NOT RUN; hosted CI for these uncommitted changes NOT
VERIFIED. No production/private dump, real media/users, WhatsApp, extractor, AI,
payment, cloud, Git write or later Jira task. Canonical schema, real rights,
trusted identity/territory/age/grants, distributed limits, protected delivery and
legacy integration keep ZL-16 PARTIAL. See docs/api/CATALOGUE_TEST_RESULTS.md.

## Final ZL-16 handoff verification

All 352 starting eligible files remain: 340 byte-identical and 12 reviewed shared
CI/tooling/documentation updates; 22 new files (374 total). No unexpected source
change or deletion. All pre-existing website/bot/extractor/Content module bytes,
SQL files/manifests, dependency/baseline evidence and previous contribution prefix
are preserved. Git HEAD/tree/branch/local heads/tags unchanged, empty index;
62 current dirty paths include the 40 pre-existing ZL-15 paths. No Git writes.

Final Gitleaks 8.30.1 expanded scan: 279 reviewed text inputs, exit 0, no findings;
private stores/history/binaries excluded. Context scan separately passed 234 text
inputs. Actionlint passes (shellcheck/pyflakes NOT RUN). 84 local links checked,
none broken, Markdown fences balanced and git diff --check passes (existing
LF-to-CRLF advisory only). No current hosted CI execution was performed.

Final host/image suites: 332 passes, no failure/skip; context syntax 141 PHP/48 JS.
Full eligible syntax: 202 PHP/49 JS. Catalogue SQL prerequisites 13 plus 24 checks,
reader/excess-privilege/outage gates pass. Prior content/database regression suites
pass as listed above. Final image exactly 237 matching host paths/hashes.

Managed CI audit: least privilege verified, zero application tables, schemaReady
false; HTTP live/dependency/ready 200/200/503. Owned CI services stopped normally,
all three existing managed database volumes retained. No ZL-14/ZL-15/ZL-16
fixture containers/networks remain; no managed ZL-13 containers remain. No private
SQL, production, real users/media/licences, messaging/extractor/paid AI/cloud,
commit/push or ZL-17 work occurred. ZL-16 remains PARTIAL.

## 2026-10-24 - ZL-17 authorized HLS/navigation foundation (PARTIAL)

AI-assisted implementation by the coding assistant, with executed local checks;
no independent human review or real legal distribution approval is claimed.
Owner authorized ZL-17 against the current extracted workspace, preserving all
prior working-tree ZL-15/ZL-16 changes and prohibiting commits/push/production/
private SQL/live providers or automatic ZL-18.

Audited Shot associations, direct movie MP4/player/progress behavior, series/iframe
paths, rewrites and upstream partial contracts. Added Playback services/API/view,
validated Shot resolution, exact-origin session issuance and current per-resource
rights/entitlement/version/digest checks. Default provider/composition stays
unconfigured. Added native-HLS/MSE player, safe metadata DOM, bounded failures,
privacy-limited events and opt-in tab-local resume. Generated original 12-second
HLS/tone/captions, with no copyrighted inputs or production media. Exact hls.js
1.7.3 vendor/licence/hash and playwright-core 1.64.1 test lock recorded.

Reviewed legacy changes are limited to index Shot links, movie Watch replacement
(removing raw MP4 interpolation/URL logging and unscoped progress writes), and
additive catalogue-view gates in movie/series details. Existing series defaults,
other website/bot/extractor, Content/Catalogue modules and SQL/manifests preserved.
No new schema, history store, real CDN signing, media ingestion/encoding service,
payment, AI, WhatsApp or extraction activation.

Created seven streaming guides, updated architecture/README/CI/security/API cross
references and recovery records. Strict image context 265 paths (262 text), still
six SQL files; generated media, state/session keys, node_modules and browser
profiles excluded. Existing verification runner/CI extended, no duplicate pipeline.

Executed host/image: 388 tests, zero failures/skips, context parsers 156 PHP/56 JS.
Full eligible syntax 217 PHP/57 JS. New PHP/player unit tests 56, plus 49 actual
Chrome browser/protocol checks. Desktop native HLS and forced-MSE 390px viewport
play/seek/captions/progress/recovery pass; not physical mobile/Safari evidence.
Missing/corrupt resources, expired descriptor, no-cookie resources, origin denial,
revocation/expiry, protected range and teardown pass. Public Apache APIs remain
503 with no-store, view pages 200, private module/media paths 404. Managed CI
has zero app tables and readiness 503. Historical fresh-MySQL suites were not
rerun in this no-schema-change phase; their prior reports remain historical.

Last local startup samples 4,647 ms native /5,903 ms forced MSE; repeated runs
varied about 1.34-6.081 seconds. Two-sample fixture evidence, no speedup/SLA or
production scale claim. Image inventory exactly matches 265 host paths/hashes.
Gitleaks context 262 and expanded 314 reviewed text inputs pass; private stores,
history/generated media/binaries excluded. npm audit reports zero known package
advisories; actionlint passes, shellcheck/pyflakes NOT RUN, hosted CI NOT VERIFIED.

ZL-17 PARTIAL: canonical/account/media contracts, real rights/identity/territory/
age/grant operations, distributed limits, protected CDN/origin, real devices,
full legacy end-to-end/security/load/release checks remain blocked/unverified.
Prior contribution prefix and Git HEAD/tree/refs/index preservation, final links
and cleanup are recorded in the handoff. ZL-18 recommended separately, not started.

## Final ZL-17 handoff verification

All 374 starting eligible files remain: 358 byte-identical, 16 authorized reviewed
application/tooling/documentation updates, plus 35 new files (409 total). No
unexpected change/deletion. Earlier Content/Catalogue implementations, all SQL/
manifests, bots/extractor, baseline/dependency evidence and prior contribution
prefix are byte-preserved. Only index Shot actions, movie Watch/player removal
and additive movie/series view gates change existing application source. Git
HEAD/tree/branch/local heads/tags remain unchanged, index empty; 101 current dirty
paths include all 62 pre-existing paths. No staging, commit, push or history writes.

Host/image full suite passes 388 tests, zero failure/skip; 156 PHP/56 JS context
parsers. Full eligible syntax passes 217 PHP/57 JS. Actual browser/protocol suite
passes 49 assertions with generated original HLS; native desktop Chrome plus
forced-MSE mobile viewport, not Safari/physical-device certification. Last startup
samples 4,647/5,903 ms, no production SLA claim. Image exactly 265 host-matching
paths/hashes. Context Gitleaks 262 and expanded 314 text inputs pass; private stores,
history/generated media/binaries excluded. npm audit zero reported advisories;
actionlint passes, shellcheck/pyflakes NOT RUN, hosted CI NOT VERIFIED. 102 local
links resolve (including URL-encoded spaces), fences balanced, git diff --check
passes with only the existing line-ending advisory.

Managed CI audit retains zero application tables and ready 503; normal shutdown
completed, zero managed containers and all three original database volumes
retained. Temporary generator containers used --rm; loopback browser servers were
stopped by ownership-scoped cleanup. No ZL-17 fixture container/network remains.
No production/private SQL/real media/users/licences, live WhatsApp/extractor,
payment/AI/cloud, publishing or ZL-18 work. ZL-17 remains PARTIAL.

## 2026-10-24 - ZL-18 integration quarantine (PARTIAL)

AI-assisted implementation by the coding assistant with executed local checks;
no independent human/provider/legal approval is claimed. Continued from the
current extracted 409-file workspace and 101 existing dirty paths, preserving
prior ZL-15/16/17 work. No commit/push/deploy, production, live sessions/providers,
real transactions/media/recipients or automatic ZL-19.

Inventoried startup/import/callback/queue/cron/browser/admin paths. Added shared
JSON policy with PHP/Node fail-closed gates: live WhatsApp, extraction, payments
and legacy external media cannot activate through flags. Added 10 Node entry and
114 method/action guards, 31 early PHP route guards, nine token-helper operation
checks and sanitized capability status. Preserved all historical token, cached
forwarding, queue/retry/refresh and extraction bodies; no existing queue/private
store was accessed or deleted. Bot dev no longer starts a filesystem watcher.

Added a bounded offline token/queue/cache-forward/extraction simulator with no
transport/store/timer, affected UI unavailable controls and disabled origin/embed/
episode fallbacks. Central controls do not replace trusted auth/rights/CDN work.
Prior Playback/Content/Catalogue and HLS/progress files stay byte-preserved.
Seven security/integration guides, static operation evidence, architecture/CI/
README/security export, contribution and recovery records updated.

Verification: host/image 524 tests, zero failures/skips; context syntax 161 PHP /
63 JS, full eligible syntax 222 PHP /64 JS. Dedicated read-only network-none
container: 134 quarantine tests pass with zero mounts/private sessions. Direct
entry tripwires observe zero transport/provider-import/store-write/private-read/
timer activity. Actual browser unavailable UI 8 assertions and unchanged HLS
49 assertions pass; no external request. 31 host routes deny GET/POST; in image
30 are guarded and withheld admin source is absent/404. Fake payment callbacks
cannot reach entitlement SQL. No schema/database harness change; managed CI audit
retains zero app tables and readiness 503. Historical fresh DB suites not rerun.

Strict context 279 paths/276 text; exact image/hash and final cleanup evidence
below. Expanded Gitleaks passes 338 reviewed text/JSON inputs; private stores,
history/generated media/binaries excluded. Actionlint passes; shellcheck/pyflakes
NOT RUN, hosted CI NOT VERIFIED. Corrected earlier test expectations for obsolete
flag activation/error text, a report-parser regex and a whitespace check failure;
no security guard relaxed. Full local results and exact file delta are recorded
in docs/security/QUARANTINE_TEST_RESULTS.md.

PARTIAL: private historical variants, deployed processes/schedules/provider state,
full legacy canonical/account/admin flows, separate unresolved mail/OAuth approval
and broader release/security/device gates remain unverified. Managed-image testing
does not control arbitrary owner-run private scripts or globally firewall web
egress. Recommend ZL-19 separately; no subsequent Jira task was started.

## Final ZL-18 handoff verification

All 409 initial eligible inputs remain: 342 byte-identical, 67 reviewed authorized
source/tooling/documentation updates, plus 22 new files (431 total), no deletion.
Content/Catalogue/Playback, HLS/progress source, SQL/migration manifests and baseline
evidence are unchanged. Ten historical Node implementation bodies are identical
to initial bytes after removing only guard additions. Previous contribution history
is an exact prefix. Git HEAD/tree/branch/local heads/tags unchanged; index empty;
176 current dirty paths include all prior 101. No staging/commit/push/history write.

Final host/image full suite 524 passes, no failure/skip; context syntax 161 PHP/63 JS,
full eligible syntax 222 PHP/64 JS. Read-only network-none quarantine run 134 passes,
no host/session mounts. Actual unavailable-UI 8 and unchanged HLS 49 browser checks
pass with zero external operations. Final image exactly 279 reviewed host-matching
paths/hashes, no extras/missing/symlinks. Expanded Gitleaks passes 338 reviewed
text/JSON inputs; excluded private stores/history/generated media/binaries are not
certified. Actionlint passes; shellcheck/pyflakes NOT RUN; hosted CI NOT VERIFIED.
113 local links resolve, Markdown fences balanced and git diff --check passes
(existing line-ending advisory only). The flagged trailing space was corrected.

Managed CI audit retains zero app tables and readiness 503; owned services stopped
normally, zero managed containers, all three existing database volumes retained.
Network-none verification containers used --rm; temporary HTTP/browser servers
closed through owned cleanup. No ZL-18 fixture container/network remains. No real
session, payment, WhatsApp message, extraction, private SQL, production operation,
real media/recipient, credential rotation, deploy or ZL-19 work occurred.
ZL-18 remains PARTIAL for the explicit private/deployed and full-integration gaps.

## 2026-10-24 - ZL-19 authentication/admin security foundation (PARTIAL)

AI-assisted source audit, implementation and executed local verification. Audited
109 eligible first-party website PHP sources lexically with bounded manual auth/
admin tracing; 57 relevant source inventory entries, not a complete runtime graph.
Added apps/web/src/Identity typed services and tests/identity contract, real PHP
HTTP/session, actual legacy-controller and Chromium protocol fixtures. Implemented
password hashing/verification/gradual rehash, generic isolated registration/login,
bounded abuse controls, digest purpose-bound recovery, epoch/session lifecycle,
CSRF, explicit permissions, audit failure rollback and image upload inspection.

Legacy changes are limited to includes/csrf_helper.php input/expiry robustness,
admin/login.php generic errors/display suppression, ajax_signup.php preservation
of existing expired unverified identities. Existing real hash/session/account
requirements and earlier Content/Catalogue/Playback/quarantine source are preserved.
MFA is NOT implemented; MissingStepUp denies new privileged permissions. Existing
legacy admin flags/CSRF/uploads remain release blockers. No authentication migration
or guessed identity DDL; four base/two rights SQL definitions remain byte-identical.

Final host PHP 8.2.12/Node 24.13.1 and pinned image PHP 8.2.35/Node 24.22.1: 566 tests
pass, zero failure/skip, context syntax 174 PHP/68 JS. Full eligible syntax 235 PHP/
69 JS passes. Read-only network-none 176 tests pass, no host/session mounts. Actual
identity browser 18, original HLS 49 and quarantine UI 8 pass; no external request.
Exact image manifest has 297 matching paths, no symlinks. Managed schema readiness
remains blocked, no app migration. Hosted CI, production SQL/MFA/mail/HTTPS/owner
recovery, complete legacy journeys and fresh advisory audit are NOT VERIFIED/RUN.

Ten required auth guides plus safe route inventory/full execution report; existing
README/architecture/CI/security-export/recovery records updated. Previous contribution
entries are preserved as an exact prefix. Source/Git/secret scan/cleanup evidence
is recorded in final recovery STATUS. No real identity/private SQL/session/key/
provider/production access, account/password conversion, staging/commit/push or
ZL-20. Production release requires verified sanitized identity DDL, shared stores,
TLS/mail, vetted MFA and owner recovery, full route/upload controls and approved
compatibility rollout. No local implementation implies Jira completion.
