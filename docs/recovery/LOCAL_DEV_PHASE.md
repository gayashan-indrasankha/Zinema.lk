# Local development and safety phase

Date: 2026-10-25. State: COMPLETE within the authorized local scope.

Owner scope: reproducible local development, fake mail/OAuth tests, upload and distributed token-claim review, and a sanitized local Git baseline. Production integrations, publishing and deployment remain disabled. Starting checkout: clean `zinema/import-evidence`, commit `bf370a4`; work continues on local-only `zinema/local-dev-safety`. Existing import branches and review history are preserved.

## Intended behavior and verification plan

- Pin official development image digests and test a fresh, isolated database from the canonical schema, without importing preserved data. Publish only loopback development ports and deny runtime internet egress. Do not mount the workspace root, credentials, session archives or legacy media into containers.
- Local SMTP accepts only fictional `.invalid` recipients and never relays. Exercise signup, verification, resend, reset, expiry/replay and failed delivery without printing email links or credentials.
- Local OAuth mimics only the authorization-code flow and fictional verified/unverified identities. Bind single-use state and PKCE to the browser session, reject unverified/mismatched identity linking and refresh the authenticated session. No real provider is contacted.
- Review upload paths for MIME/extension confusion, dangerous filenames, oversized content, unsafe media processing, path traversal and replacement/data-loss behavior. Add executable negative cases and scoped fixes for confirmed defects; retain existing verified valid flows.
- Review cross-process token use. Before a send, require one database-backed claim with owner binding. Treat uncertain delivery after a crash conservatively; do not claim distributed exactly-once delivery. Test concurrent claim attempts, wrong-owner completion, expiry, replay and failed delivery using fake recipients only.
- Finish with executed regressions, staged/working-diff review, dedicated secret/data checks and a traceable local commit. Do not push, create/modify remote PRs, merge, deploy, rotate production credentials or rewrite history.

Implementation and final results will be recorded here and in separate JSON artifacts; previous reports remain historical evidence.

## Implemented changes

- `dev/` supplies the pinned Compose runtime, generated private configuration,
  additive development migration, fictional seed, no-relay SMTP/provider fakes,
  protected fixture controls, HTTP suite and environment verifier. `.dockerignore`
  allowlists image inputs and `.gitattributes` keeps Linux shell entrypoints LF.
  Start/stop/test instructions and build limits are in `dev/README.md`.
- `oauth-start.php`, `includes/oauth.php`, the callback and five sign-in URL
  builders implement session-bound, expiring/single-use state and S256 PKCE.
  Unverified provider identities and silent/rebound email linking are rejected.
  Password-account linking requires the already signed-in initiating owner;
  successful OAuth login regenerates the session. Local mail refuses recipients
  outside reserved `.invalid` domains.
- `includes/uploads.php` and the five catalogue handlers reject unsafe uploads,
  rewrite accepted images and generate storage names. Existing media is retained,
  collection paths come from the database, episode thumbnails use local paths,
  and trailer upload no longer invokes a shell. Development Apache disables script
  execution in uploads and serves only generated media filenames.
- Canonical schema v2 and `002-token-claims.sql` add hashed claim receipts, owner
  hash and delivery state while removing expiry's automatic ON UPDATE mutation.
  The SQL bridge and bot now claim/begin/send/complete, hold uncertain sends and
  deny the legacy consume bypass. Validation resolves full-file fallback at part
  zero and omits delivery data on rejection. Token/bulk response logging was removed.
- Retained test drivers support a bounded LOCAL_DEV result prefix, preserving the
  historical repair results. Schema positive consumption follows the claim protocol;
  the old bypass, expiry and repeated-use negative assertions remain.

## Executed verification

| Executed check | Result | Classification |
| --- | --- | --- |
| Official digest and fixed image-library development build | Passed; healthy services and repeated starts | runtime verified |
| Fake mail/OAuth, uploads and concurrent SQL claim HTTP suite | 88/88 passed | runtime verified |
| OAuth state/PKCE helper cases | 7/7 passed | test-covered and executed |
| New bot delivery and fake HTTP API-client suite | 6/6 passed | test-covered and executed |
| Owned-container exposure, isolation and private-input checks | 17/17 passed | runtime verified |
| Retained Windows HTTP suite | 94/94 passed; no new PHP diagnostics | runtime verified |
| Fresh canonical database + retained bridge suite | 22/22 passed; 37 tables, 2 views, zero legacy rows | runtime verified |
| Retained configuration / fake-client bot suites | 10/10 and 12/12 passed | test-covered and executed |
| Extractor disabled/local-fixture requests | 4/4 passed; owned processes stopped | runtime verified |
| npm audit: bot lockfile / temporary HTTP extractor dependencies | Exit 0 each, zero advisories returned | scoped dependency evidence |
| Browser invalid sign-in, fake OAuth sign-in, sign-out | Passed; fictional member → guest verified | manual runtime verified |

The first HTTP run was 79/81: traversal was normalized by PHP before validation,
which changed the retained image path and caused the subsequent oversize
preservation assertion to fail too. Checking `full_path` fixed the defect; the
expanded final suite passes. The first new API-client unit execution had a relative
fixture path, corrected to absolute. Its next socket execution was denied by the
sandbox (EACCES); the permitted loopback execution passed 6/6. These failures were
investigated, not reported as passing. Final sanitized records are `LOCAL_DEV_*`
JSON and `evidence/local_dev-*.tap`; private build/debug logs remain ignored.

The manual browser test used the computer-use skill and only loopback pages. It
confirmed rendered invalid-login feedback, a member profile through the fake
provider, then the confirmation and guest state after sign-out. The screenshot
`evidence/local-fake-oauth.png` contains only a fictional generated username.

## Limits and next work

No production database, real email/provider, payment, live WhatsApp, customer media,
remote Git operation or publication was used. Real services are **not yet verified**.
The runtime images are development-only. Image/library versions are fixed, but
package repository retention and alternate CPU architectures limit reproducibility.
The legacy Windows fixture and Docker stack run sequentially because both own 8088.

The dedicated secret scan does not establish absence of all secrets or production
readiness. Dependency audit scopes omit the full extractor/Chromium and old installed
dependency trees; no container vulnerability scanner or antivirus was executed.
Video checks cover size/type/signature and inert serving, not codec/malware safety.
Storage quotas and media cleanup remain future work; old/shared media was preserved.
The trusted shared bot API key can impersonate worker identifiers. Cross-process
claiming prevents competing honest workers; external transport ambiguity and lost
acks prevent an exactly-once guarantee. Held delivery needs a reviewed operator
reconciliation workflow before any live integration phase.

The historical publishing phase remains PARTIAL for legacy whitespace findings;
this phase does not resolve that unrelated imported formatting or alter its remote
review branches. Local baseline security review and final Git state follow below.

Next recommended Codex task: **Implement a local-only operator reconciliation
workflow for held token deliveries and an upload quota/cleanup inventory, using
fictional fixtures; keep production integrations and publishing disabled.**

Reference guidance used for design review: [Docker network isolation](https://docs.docker.com/compose/how-tos/networking/),
[OAuth security BCP](https://www.rfc-editor.org/rfc/rfc9700.html), and
[OWASP upload guidance](https://cheatsheetseries.owasp.org/cheatsheets/File_Upload_Cheat_Sheet.html).

## Sanitized Git baseline review

The starting worktree was clean at `bf370a4`; all work stays on local-only
`zinema/local-dev-safety`. No existing branch/history was rewritten and no remote
was contacted. The reviewed index initially contained 334 files; the final candidate contains 337 files. Eight ignore
sentinels passed and no forbidden private/data paths were present. `.gitignore`
was reviewed: generated credentials, sessions, database files, logs, dependencies,
legacy SQL and uploads remain excluded. The original red-frame fixture is text
base64 and the browser evidence contains only a fictional generated member.

Gitleaks 8.30.1 was available from the previously verified official archive; its
archive checksum was rechecked against the saved upstream checksum record. With
redaction, default rules, an empty suppression file and inline allows ignored,
staged changes, an exported full candidate tree and all local Git history each
returned exit 0 with zero findings. No production/private archive was included
in the candidate scan. Scanner reports are private; sanitized counts are recorded
in `LOCAL_DEV_SECURITY_RESULTS.json`. New staged whitespace checks pass after
removing inherited trailing whitespace from the extracted token-data helper.
Historical publication whitespace findings remain unresolved as noted above.

Final PHP/JavaScript syntax, JSON/Python parsing, clean-source reproduction and
Git commit/stop state are recorded in the executed handoff below.

## Executed verification and local baseline

A second source tree was exported from the reviewed Git index with no `.git`,
private configuration, dependencies, database files, user sessions or legacy
media. It built successfully, created a fresh canonical MariaDB development
volume and passed the same 88 HTTP, 7 OAuth helper and 17 environment checks.
This validates the pinned build and clean-source workflow independently of the
original upgraded development volume. `LOCAL_DEV_REPRODUCTION_RESULTS.json`
records the result. Both Docker stacks and the owned XAMPP/extractor/bridge
processes were stopped; ports 8088, 9081, 3307, 8089 and 3334 were verified closed.
Generated development configuration, media and named database volumes were retained.

Final static checks passed: PHP 177/177, JavaScript 41/41, Python AST 8 files and
JSON parsing 26 files. New CJS behavior is covered by the executed Node
suites. The working/staged changes were reviewed, unrelated legacy formatting was
preserved, and no secrets/private files were staged. After the clean-source runtime test, only documentation/evidence and bot command/response
diagnostic redaction changed; the diagnostic-only edit passed Node syntax checking. The final staged/candidate/history scans returned zero findings. Source baseline
`9c48b7ca8a2b6535b6da0b408d49bbdfe7280f1a` was committed locally and left a clean
worktree. This completion record follows in a documentation-only local commit;
its hash is available from Git history. No push, deployment or remote review was performed.
