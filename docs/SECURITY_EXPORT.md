# Zinema.lk security and public-export preparation - Jira ZL-10

Date: 2026-10-23 (Asia/Colombo). Preparation scope: COMPLETE after the checks
recorded below. Publication and a real project export remain **NOT APPROVED**.
No claim is made that every secret has been removed or every included file is secure.

Publication follow-up (2026-10-23): after the preparation handoff, the owner
explicitly requested incremental commits, review branches and GitHub PRs for the
existing changes. That request authorizes publishing the reviewed ZL-9/ZL-10
changes to the existing repository for review. The no-publication statements
below describe the original ZL-10 preparation scope. A real project `--export`,
PR merge, deployment, account changes and production access remain unauthorized
by this follow-up. Branches and PR results are recorded in the recovery handoff.

## 1. Purpose

Prepare a conservative local export process without changing the existing
application, deleting historical material, contacting live services or publishing
anything. Sensitive original files stay in place. Unsafe/uncertain candidates are
excluded rather than rewritten. The result is a candidate package for manual
review, not a runnable/security-certified release.

## 2. Scope

The existing extracted workspace, current working tree and uncommitted changes
are authoritative. HEAD is a historical reference, not a substitute for current
files. No ZIP is extracted or required. The exporter reads current approved files
from disk and does not call Git, import application modules or use the network.

At ZL-10 start: branch `main`, HEAD
`7e8d325c4fc206c3a2664652de99e560bae44795`, 259 tracked files, no tracked changes,
and three untracked ZL-9 documents. All 176 ZL-9 checksums matched. The existing
BASELINE.md and baseline-source-sha256.txt are preserved byte-for-byte. The
contribution record is extended without replacing its ZL-9 history.

Authorized changes are root ignore/example guidance, two new export/test scripts,
this security guide, an appended contribution record and additive recovery
status/decision records required by AGENTS.md. No PHP/business logic, bot logic,
frontend rendering, authentication, migration, dependency lockfile or runtime
configuration is changed. No application runtime tests are performed.

## 3. Current Sensitive Asset Categories

Inventory uses paths/metadata only for actual environments, sessions, dumps,
logs, private evidence and media. No sensitive contents or filenames containing
private identities are reproduced. Directories are pruned rather than recursively
listing authentication/dependency/media records.

| Priority | Category / component | Observed tracking state | Potential exposure | Protection / next action |
| --- | --- | --- | --- | --- |
| HIGH | Six real bot environment instances | Untracked and ignored; contents not read | Service/account credentials or operational identifiers may be present | Keep local; exporter excludes independently of ignore rules. |
| HIGH | Bot authentication/session directories | No tracked children observed; ignored | Account impersonation/session takeover if exposed | Never inspect/copy into fixtures; exclude and preserve in place. |
| HIGH | Website user database dump | Untracked and ignored; metadata only | User/account/media/operational data may be present | Never import or export; classify privately before any future cleanup. |
| HIGH | Private recovery evidence and preserved history | No tracked children observed; ignored | Historic credentials, sessions, originals or diagnostic material may be present | Exclude the entire area; no history copying. |
| HIGH | Three tracked source references with local-path patterns | Two bot migration variants and one website bot-monitoring page | Personal/development path information; actual values suppressed | Content gate withholds these files; owner-reviewed follow-up needed before including them. |
| MEDIUM | Logs and bot export/debug artifact | Untracked/ignored; two maintenance logs and a bot export identified | Tokens, request/recipient details or private diagnostics may be present | Do not open during inventory; exclude logs/exports and define redacted logging in later scope. |
| MEDIUM | Private uploads/local bot media | No tracked children observed in inventoried areas | User privacy and media-rights concerns | Exclude directories and binary media; use separately approved sample media later. |
| MEDIUM | Six tracked SQL references | These are source references, not all data dumps | Seeds, local paths or executable data/procedural logic may require review | Exact migration allowlist plus schema-only content gate; never use a blanket SQL ignore. |
| MEDIUM | Four tracked environment examples | Local template inspection passed | Examples can become a future leakage path if real values are pasted | Exact template/key allowlist, synthetic credentials, local URLs and disabled flags. |
| LOW | Generated dependencies and bundled vendor library | node_modules ignored; bundled PHPMailer has 70 tracked files | Accidental bulk packaging, third-party material and licensing obligations | Export excludes both; retain original tracked vendor files and review dependency restoration/licensing. |
| MEDIUM | Six historical/temporary bot variants | Untracked/ignored; contents not inspected | Embedded identifiers/configuration or obsolete account handling may remain | Preserve originals; exclude; do not declare them safe to delete. |

No real environment, authentication, dump or log file was found tracked in the
inspected current index. This is a bounded current-tracking observation, not a
full historical exposure assessment. No credential value is confirmed exposed by
this task. Unknown private-area contents remain unknown.

## 4. Existing Git Ignore Protection

The existing root and three component .gitignore files were read and preserved.
Only the root was extended. Existing protections for environments, sessions,
private recovery, media/uploads, logs, dumps, generated dependencies and historical
variants remain. The existing `!.env.example` exception remains effective.

Root additions protect environment-style local files and personal package-manager
configuration; common auth-state/cookie/credential stores; named dump/backup SQL
patterns and local database files; archives; PID/cache/Python output; build/dist;
and additional private audio/video formats. No blanket `*.sql`, source-extension
or lockfile exclusion was introduced. Nested ignore files need no changes because
root protections apply beneath them and their example exceptions remain valid.

Executed `git check-ignore --no-index --stdin` assertions: **21 sensitive-path
positives and 19 required-source/example/migration/lockfile negatives passed**.
Using `--no-index` ensures tracked files cannot hide an accidental ignore match.

Ignore rules affect ordinary addition of untracked paths only. They do not remove
tracked files, prevent force-add operations or scrub previous commits. Export
protection is implemented separately; it does not rely on these rules.

## 5. Recommended Environment Configuration

Only example templates were read. Real environment instances were neither opened
nor copied. All four current templates passed the export example validator.
Existing component examples and comments remain unchanged.

| Contract | Existing example / supported names |
| --- | --- |
| Website | `Zinema.lk Website/.env.example`: APP_ENV, BASE_URL, database fields, SESSION_SAVE_PATH, adapter modes and explicit live-integration flag |
| Delivery | `whatsapp-bot-deploy/.env.example`: API_URL/API_KEY, BOT_INSTANCE_ID, isolated session/media paths, ALLOW_PRODUCTION_API, ALLOW_LIVE_WHATSAPP and empty operational identifiers |
| Extractor fixture | `Zinema.lk Website/fb-video-api/.env.example`: APP_ENV, EXTRACTOR_MODE, PORT and FAKE_MEDIA_URL |
| Root guidance | `.env.example`: existing APP_ENV and pointers to component contracts; there is no root application/dotenv loader |

The root template now appends **FUTURE / NOT IMPLEMENTED** proposals only:

- FUTURE_DELIVERY_MODE and FUTURE_DELIVERY_SIMULATOR_URL: disabled mode/local
  simulator proposal. No simulator is implemented or enabled.
- FUTURE_AI_PROVIDER, FUTURE_AI_API_KEY and FUTURE_AI_MODEL: disabled provider and
  synthetic credential/model placeholders.
- FUTURE_AI_ENABLED=false, FUTURE_AI_TIMEOUT_MS, FUTURE_AI_REQUESTS_PER_MINUTE,
  FUTURE_AI_MAX_INPUT_CHARS and FUTURE_AI_MAX_REQUESTS_PER_DAY: proposed future
  controls, not currently enforced limits.

No current code reads these future names. They do not activate AI, incur API
usage, start a service or change the existing delivery guard. Implementing such
configuration requires a separate feature task. Never populate examples with
real values, even temporarily. Current root/component examples remain local and
disabled, with explicit fake credentials and no live group/phone identifiers.

## 6. Public Export Inclusion Policy

Tool: [scripts/prepare-public-export.cjs](../scripts/prepare-public-export.cjs).
Original ZL-10 policy version: `zl10-v1`; current ZL-12 policy is
`zl12-leaves-v1` (exact refactor delta in section 20). Node standard library only; Python was unavailable and
no interpreter or package was installed.

Inclusion requires **all** of the following:

1. A repository-relative path appears in the tool's explicit reviewed path set.
2. It is within an approved root (`Zinema.lk Website`, `whatsapp-bot-deploy`,
   `docs`, `scripts`, `.github`) or is an explicitly approved root text/config file.
3. Its file type matches the text/source policy or an exact approved template/SQL
   reference path. Extension alone does not approve a file.
4. It is a normal, single-link file with no symlink/junction ancestor and is within
   the file-size bound. UTF-8 decoding, JSON (where applicable), privacy/credential
   checks and example/SQL-specific checks must pass.
5. Its current bytes can be rechecked before copying; its SHA-256 and size are
   recorded and validated without normalization or rewriting.

Approved source roots are not automatically trusted wholesale. A new/untracked
file absent from the explicit reviewed path set is excluded and counted as
unapproved. This does not make that working-tree content non-authoritative: it
means export approval requires a separate policy review. The tool never replaces
it with HEAD or a historic copy. Eligible approved working-tree changes are read
as they exist, including uncommitted bytes.

Required package lockfiles and ordinary source documentation are approved. Exact
schema references may pass only a deliberately narrow schema-only SQL lexer and
statement policy. DDL timestamp/foreign-key clauses and quoted comments are not
misclassified as data statements. DML, stored routines, executable SQL comments,
unrecognized statements or uncertain source content are held. Nothing is executed.

The exporter includes no Git history and does not attempt a functional build.
Excluding vendor/media or uncertain application files can produce an incomplete
runtime package; manual review must resolve dependencies and held candidates.

## 7. Public Export Exclusion Policy

The policy excludes these categories even if Git already tracks them or an ignore
rule explicitly permits them:

- Real environments and variants; only four exact, validated examples qualify.
- Authentication/session/cookie/key stores, private keys/certificates and local
  package-manager credential configuration.
- Database dumps/backups/local databases and data-bearing or uncertain SQL.
- Logs, debug exports, queue/state/cache/PID/temporary runtime files.
- Private recovery material, original archives, backup archives and Git metadata.
- node_modules, vendor libraries (including bundled PHPMailer), build output and
  generated dependency artifacts.
- Binary/media assets, including existing logos/icons pending rights review;
  private upload/media directories are never traversed for export content.
- Historical variants, unapproved paths/types, oversized or undecodable files.
- Symlinks/junctions/hardlinks, unsafe names/relative paths and content candidates
  with credential/contact/personal-path/private-URL findings.

Findings consist only of reason categories, counts and known approved relative
paths. Matches/values are never logged or retained in reports. Unknown filenames
are not echoed. Safe dependency lock metadata is treated differently from
first-party literal contact strings; lockfiles still undergo credential/URL checks.
This is a bounded local heuristic gate, not a complete secret detector or rights
audit. Before publication, perform independent review and an approved secret scan.

## 8. Export Script Usage

```powershell
node scripts/prepare-public-export.cjs --help

# Choose an absolute local path outside the source tree. The directory must not exist.
$exportDirectory = Read-Host 'Absolute destination outside the workspace'

# Default action: inspect only. Explicit --dry-run is also supported.
node scripts/prepare-public-export.cjs --output $exportDirectory

# FUTURE OWNER-APPROVED ACTION ONLY: copy approved candidate files locally.
node scripts/prepare-public-export.cjs --output $exportDirectory --export

# Read-only verification of that separate candidate export against current source.
node scripts/prepare-public-export.cjs --output $exportDirectory --validate
```

The default source is the tool's current repository root. `--source` can select an
explicit absolute source for synthetic tests or a separately approved workspace;
it is not a way to package arbitrary directories because the same allowlist and
content gates apply. Source/output parents must not be links. UNC/network/device
paths and explicit traversal/relative arguments are rejected. An output inside,
equal to, or enclosing the source is rejected. The destination parent must exist.

The tool has no upload/push/email/publish command or overwrite/force option. Any
existing destination, even empty, is refused. An interrupted/failed export may
leave a partial destination; the tool does not delete it automatically. Review it
and select a new absent destination for a later attempt.

Only synthetic-fixture exports were executed in ZL-10. A real project `--export`
still requires explicit owner approval; this task did not grant or exercise it.

## 9. Dry-Run Instructions

Run the default command with a new absolute output path. It reports action,
policy version, current included count, excluded category counts, approved-path
review candidates and the continuing manual-publication requirement. It does not
create the output directory, generate a report file, copy source or change Git.

The real-workspace dry run was executed with an absent destination outside the
source; absence was checked before and after. It created no output. Re-run after
reviewing relevant working-tree changes rather than assuming a previous count is
permanent. Final observed counts and manual holds are recorded in section 10.

Do not interpret a successful dry run as permission to export or publish, proof of
complete sanitization, or proof that application features run.

## 10. Export Validation

The separate export contains `public-export-manifest.json`, with schema/policy
version, source kind, relative file paths, counts, sizes and SHA-256 hashes.
It contains no absolute source/destination path, raw finding or credential value.
The manifest itself is excluded from its file count to avoid self-reference.

Validation checks schema/key types, duplicate/case-colliding/unsafe entries,
approved paths, actual directory/file membership, file counts, hashes and content
policy, and equality with current source bytes. It rejects added environment/Git
files, missing/tampered files, linked paths, unexpected directories and unsafe
manifest substitutions. It does not rely on a manifest's claim of safety alone.

Executed local verification:

| Command/check | Actual result |
| --- | --- |
| `node --check scripts/prepare-public-export.cjs` | Passed. |
| `node --check scripts/test-public-export.cjs` | Passed. |
| `node scripts/test-public-export.cjs` | 32 tests passed; zero failed, skipped or cancelled. Synthetic temporary fixtures only. |
| Ignore assertions through `git check-ignore --no-index --stdin` | 21 positive protections and 19 negative source/example/migration/lockfile assertions passed. |
| Complete tracked-file and new-artifact ignore check | All 265 checked paths remain visible to Git; no required tracked file or new audit/export artifact is unintentionally ignored. |
| Four existing example-template content checks | Passed, including disabled live flags and clearly synthetic credentials. |
| Actual-source default dry run | Passed: 188 eligible files; no destination created and no real project files copied. Four approved-path candidates held, as listed below. |
| Gitleaks 8.30.1, `stdin --redact=100 --no-banner --log-level error` | Exit 0, no findings across the eight new/modified artifacts. No report file written; private stores and full history not scanned. |
| New/modified Markdown privacy checks | No personal absolute paths or operational contact identifiers detected by bounded checks. |
| Preservation checks against pre-task evidence | HEAD/tree/branch/tracked count/index unchanged; original ZL-9 baseline and checksum manifest, AGENTS.md and existing syntax utility byte-identical; previous contribution content and original root example bytes retained; three component examples byte-identical. |
| Original 176-file checksum comparison | 173 unchanged; only `.gitignore`, `docs/recovery/STATUS.md` and `docs/recovery/DECISIONS.md` intentionally differ. Original manifest retained without regeneration. |
| Sensitive metadata and tracked-change scope | Inventoried private files retain sizes/presence and all inventoried directories remain; only four authorized tracked workflow/example/documentation paths differ. No tracked application source, migration, dependency or asset change/deletion. |

Observed dry-run exclusions: nine pruned directories, six environments, six
historical variants, three personal-path holds, seven sensitive names, one
schema-policy hold, three binary assets and one database dump. Directory counts
describe pruned boundaries, not a recursive count of private contents.
The eligible count is an observation of this working tree, not a permanent
release inventory. Local scanner/metadata checks are bounded evidence, not proof
that every current or historical secret is absent. No failed implementation
tests remain. Application behavior and real-copy validation are outside these
executed checks.

The synthetic suite covers allowed source/current uncommitted bytes/lockfiles/DDL;
never-opening excluded environments/sessions/dumps/dependencies; deny-by-default
unknown files; invalid destinations/traversal/existing outputs; actual junction
escapes and hardlinks; credential-example rejection/redacted reports; SQL lexical
edge cases; source changes; and manifest/content/extra-file tampering.

Four current approved-path candidates are held for manual review:

| Relative source path | Sanitized reason | Action in ZL-10 |
| --- | --- | --- |
| `whatsapp-bot-deploy/database/migration_ultimate_system.sql` | Local-path pattern | Withheld; source untouched. |
| `whatsapp-bot-deploy/database/migration_ultimate_system_FIXED.sql` | Local-path pattern | Withheld; source untouched. |
| `Zinema.lk Website/admin/whatsapp-bot-tracking.php` | Local-path pattern | Withheld; source untouched. |
| `Zinema.lk Website/admin/migrate_shots.sql` | SQL outside the schema-only export grammar | Withheld; no claim that it is a user dump. |

These are candidate-export holds, not confirmed live credential disclosures.
Publicly usable source must be reviewed separately before changing these files or
adding policy exceptions. Original movie/episode-part migrations and the reviewed
legacy bot schema remain eligible when their current bytes pass the content gate.

## 11. Tracked Sensitive File Risks

Current tracking metadata shows four example templates, six SQL references, three
binary logo/icon assets and 70 bundled-library files. These categories require
different treatment; none should be automatically called a leaked credential or
removed solely because of its category. Three tracked source files triggered
local-path-pattern holds; the actual values are not printed.

No real environment/session/dump/log path was identified as tracked in the current
inspected index. Full historic content was not scanned. Existing Git publication
history is not rewritten and remote exposure is not rechecked in ZL-10. New ignore
rules do not repair historical exposure. No file is untracked, removed or relocated.

If later review confirms committed sensitive material, obtain owner approval for
exposure assessment and any account/history response. Do not publish the material
in an issue, screenshot, scan report or remediation commit message.

## 12. Credential Rotation Guidance

Rotation is **not** performed or automatically authorized. If a genuine secret is
confirmed exposed, privately identify its service and scope, obtain owner approval,
revoke/rotate through the legitimate service, update isolated configuration, assess
affected activity, and revalidate. Public examples must remain placeholders.
Changing a source value or adding an ignore rule does not revoke a credential.
History cleanup, if needed, is a separate owner-approved plan; do not reset,
rebase, amend or force-push history in this task.

## 13. WhatsApp Session Protection

Treat Baileys and older authentication directories, keys, browser state and
session backups as account access material. No real sessions are read, copied,
used for tests or exported. The bot's existing startup guard is unchanged.
Use fictional identifiers and a future isolated simulator for demonstrations;
do not connect existing accounts/groups to prove this export process works.
Session invalidation/relinking requires separate explicit account authorization.

## 14. Media Rights and Licensing

No private videos, downloaded catalogue media, uploaded artwork or binary assets
are included automatically. Existing logos/icons are excluded until their rights
are reviewed; exclusion is not a declaration that they are infringing. Use only
owner-approved, legally usable sample assets in a separately authorized package.
Source dependency licenses do not establish rights to application/media content
or competition eligibility. Excluding bundled PHPMailer means restoring it later
requires its applicable license notices and an approved dependency process.

## 15. Public GitHub Readiness Checklist

- [x] Current workspace and pre-existing uncommitted ZL-9 documents preserved.
- [x] Sensitive categories inventoried without opening real secret/data stores.
- [x] Ignore additions verified without hiding required source/examples/SQL/locks.
- [x] Safe templates reviewed; future names explicitly not implemented.
- [x] Explicit export allowlist, dry-run default and separate output validation implemented.
- [x] Synthetic path/link/secret/hash tests executed successfully.
- [ ] Owner approves a real local export action after reviewing dry-run holds.
- [ ] Independent candidate review/secret scan and any historical exposure assessment completed.
- [ ] Dependency licensing/restoration and sample-media rights approved.
- [ ] Owner explicitly approves any publication/push. No publishing was performed here.

Unchecked release gates are deliberate future approval requirements; they do not
claim to be completed by preparation of this tool.

## 16. Devpost Submission Safety Checklist

- [ ] Follow current organizer rules and disclose existing vs competition-period work.
- [ ] Include accurate AI assistance disclosure and measured verification limits.
- [ ] Use an owner-reviewed candidate package, not the raw development workspace.
- [ ] Confirm no real credentials, authentication state, user data or private media.
- [ ] Review documentation and screenshots for personal paths, contacts and tokens.
- [ ] Obtain approval for any demonstration/service access or submission upload.

No organizer/Devpost API was contacted and no competition package was submitted.

## 17. Known Limitations

- The explicit path policy needs human maintenance when adding approved files.
  Unknown files are withheld rather than automatically trusted because Git tracks them.
- Credential/contact/personal-path gates are bounded heuristics and may miss
  secrets or flag harmless examples. No all-secrets-removed guarantee is made.
- The SQL lexer intentionally supports a narrow schema-only subset, not arbitrary
  migrations/procedures. Held source remains in the authoritative workspace.
- Dependency vulnerability/security, application correctness, database consistency,
  media rights and historical exposure are not certified by these checks.
- Source paths and output parents are rechecked and existing files are opened
  exclusively, but the tool is not an OS sandbox against a hostile concurrent
  process changing mounts/paths during filesystem operations. Use a quiescent,
  locally controlled workspace and inspect failures/partial outputs manually.
- Private asset contents are deliberately unverified. Preservation for these is
  based on metadata/no write operations, not reading or hashing their secrets.
- No real project export was executed; full-copy validation was exercised only
  with synthetic source fixtures. A real candidate requires approval and review.

## 18. Manual Approval Requirements

The task authorizes preparation and safe synthetic validation only. Explicit owner
approval is still required before a real project export, any public upload/push,
credential rotation, account/session changes, untracking/deleting sensitive files,
history remediation, production access or deployment. Existing application code
with a finding remains unchanged and withheld; do not silently redact copied code
because approved source must be copied byte-for-byte.

Before any future export/publish decision, review every held category/path and the
candidate manifest, inspect known false negatives/positives with redacted evidence,
confirm destination isolation, establish legally usable fixtures and preserve
originals. The `--export` flag is an explicit local copy action, not publication
approval. No original ZIP or historical restoration is needed.

## 19. Recommended Next Jira Task

**Jira ZL-11 - Map modules and classify keep, repair, isolate, archive.**

Use the current workspace, existing baseline and this export policy. Produce a
dependency/route map and evidence-based module classifications without deleting
or restructuring source. Do not interpret an export exclusion as a deletion
recommendation. ZL-11 is not started by ZL-10.

## 20. ZL-12 refactor dependency review — 2026-10-23

The original ZL-10 policy/results above remain historical evidence. Current policy
version is **zl12-leaves-v1**. The [ZL-12 execution report](architecture/REFACTOR_EXECUTION.md)
documents two byte-preserving internal relocations and their retained legacy shims.

Reviewed and approved exactly two additional text-source paths for this local
candidate planner:

- apps/web/src/Views/Ads.php — unchanged original disabled PHP ad functions.
- whatsapp-bot-deploy/src/config/environment.js — unchanged original pure parser.

The apps root is traversable only along exact approved path prefixes; no whole-tree
or new test/document approval. Independent sensitive-directory/name/content rules
remain. The root and component ignores/examples are unchanged. Unknown app source
is excluded before opening its contents.

The selected legacy shim requires its matching canonical implementation. Planning,
pre-write export validation and manifest validation enforce that exact relationship;
MISSING_MODULE_DEPENDENCY stops a broken or tampered candidate. Eleven new synthetic
cases cover pairs, absent/content-held implementations, mutated plans/manifests and
unapproved code; the existing 32 export tests continue to pass. No live source export
was written. Synthetic exports use fictional fixture bytes only.

Current actual-source no-write plan: **190 eligible files**, the same four manual
holds (three personal-path categories and one SQL grammar hold), no output created.
This is the original 188 eligible paths plus the two reviewed implementations.
It does not clear existing holds, grant media rights or authorize publication.
Old zl10-v1 manifests require their matching policy/review and are not silently
accepted by the new versioned validator. No existing real export was altered.

Rollback of helper placement and these exact policy changes must be reviewed
together; do not leave an exportable shim pointing to an excluded module. All
other Git/publication, secret/private-data and production approval boundaries remain.


## ZL-13 local container boundary (2026-10-23)

The new Docker build manifest in infra/environment-files.json is a separate,
exact local build-input review. It is not the public export allowlist and does
not authorize publishing the new infrastructure files. The zl12-leaves-v1 export
policy, its two canonical helper dependencies, four holds and historical baseline
records remain unchanged. No real export or remote push was performed.

The final CI image contains exactly 190 reviewed files, including .dockerignore;
no actual .env, Git history, SQL dumps, uploads, private sessions/logs or recovery
stores are present. Parent exceptions are followed by child exclusions, then
individual file exceptions. Docker's matcher was verified against new fictional
.env/SQL/Git fixtures. The held admin tracking page is omitted from the image.
Initial broader ignore rules were corrected before the final image; the aborted
attempt's local BuildKit cache was not inspected or pruned.

Generated profile configurations are ignored, independent and not build inputs.
Gitleaks 8.30.1 scans 187 reviewed text files through redacted stdin in a no-mount,
no-network container. This is a bounded scan, not full history/PII assurance.
Local Docker administrators can inspect runtime configuration; production secrets
must never be placed in these profiles. Web ingress permits egress, while every
provider mode/live flag remains disabled. No provider account was contacted.
See [operations](development/ENVIRONMENT_OPERATIONS.md) for results and limits.


## ZL-14 reviewed schema-only build inputs (2026-10-23)

The Docker context now permits exactly four new source-backed SQL migration paths
listed in database/migrations.json. Their CREATE-only shape, one-statement boundary
and SHA-256 bytes are checked before build; all other SQL/dumps remain excluded.
The reviewed context totals 204 files, including synthetic tests and the existing
ZL-12 modules. A sanitized JSON fixture contains only the two original part CREATE
statements, with comments removed, no token ALTERs and no data rows.

This is local build approval, not a public export policy change. The existing
zl12-leaves-v1 public exporter and holds remain untouched; no real export or remote
publication occurs. The excluded private Database.sql is not accessed/copied and
is not a fallback schema source. No real credentials, sessions or data are used.

New generated credentials/run markers stay in process/container memory. Root is
limited to a newly created, verified empty tmpfs-only server. The runner requires
a separate scoped role, server UUID and synthetic target marker; database names
alone never authorize writes. Backup encryption/restore is tested in memory, with
no plaintext/key file. Legacy application source and integration guards remain
unchanged. See [database evidence](database/SCHEMA_TEST_RESULTS.md) for scope/limits.
