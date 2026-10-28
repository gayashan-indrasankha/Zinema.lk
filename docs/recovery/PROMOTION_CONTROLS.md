# Staging promotion controls — local implementation, 2026-10-26

Started clean at 6840da630bdd5fdfc8eea3f39a306f53d51ecea1. The owner authorized
local implementation/testing, not provider connections, production or publication.
Hosting and budget remain undecided; free access and WhatsApp-only full content
remain the launch scope. This phase does not repeat completed recovery work.

Intended behavior before changes:

- Pairing is a separate owner-reviewed operation, never an ordinary worker side
  effect. Fake challenges stay in private files, have a short expiry, and require
  an explicit matching identity/challenge review. Existing sessions are preserved.
  No real SDK/account or QR is loaded here. An ordinary unregistered worker stops
  before starting a socket. A real provider adapter still needs specific approval.
- Held maintenance review acquires the worker's lease, binds a decision to the
  exact checkpoint fingerprint, preserves a durable audit intent, and can recover
  interrupted publication. Verified completion requires matching metadata evidence;
  withdrawal never requeues the ambiguous item. Unknown legacy group checkpoints
  remain held. No recipient send is available to the operator tool.
- Private extractor configuration uses an authenticated Unix socket with bounded
  requests. The promotion fixture can exercise production configuration locally;
  it returns original fixtures only. Real upstream/browser access stays disabled.
- Production storage/backup/monitoring policy is explicit and validated. Exercise
  quota enforcement on isolated production-mode fixtures; stream authenticated
  backups and a separate local mirror without buffering entire files. Restore
  plaintext becomes visible only after authentication. Monitoring consumes bounded
  metrics and emits fixed events to a local outbox, without secrets/raw diagnostics.
- Regression tests cover review races, stale evidence, expiry, interrupted recovery,
  path/link/overwrite boundaries, backup tampering/large inputs, socket auth and
  disabled egress. Retain earlier results; record actual execution below.

Production host/provider acceptance, off-host key custody/routing, sustained load,
actual large-media SDK behavior and final publishing approval remain separate gates.

## Executed local evidence

- Pure/fake Node suite: **127/127** (including signed receipt/window shutdown,
  fake owner pairing, review audit/recovery/exclusion, Windows replacement errors,
  streaming encryption/mirror/restore and monitoring transitions). TAP retained in
  evidence/promotion-all-node.tap. Actual VM worker tests refuse real SDK/network
  dependencies. No real session or pairing challenge was used.
- Coordinated promotion rehearsal: **29/29**; source TLS/security **25/25**, restore
  TLS/security **23/23**, authenticated Unix administrator path **8/8**, stopped
  service handling **2/2**, production-config quota **6/6 on each target**. No new
  PHP warnings/notices/fatal errors/deprecations. See PROMOTION_RESULTS.json.
- Streamed SQL/tar/ZIP64/encryption/local-mirror/restore matched **38 tables,
  2 views, 540 row fingerprints and all 411 media/private-state/session files**
  before resumed writes. Most files are fictional PHP sessions; the worker state
  is a fictional placeholder. Real Baileys credential recovery is not certified.
- 400 TLS catalogue requests at 5/25 clients returned zero HTTP errors. Final p95
  38.95/121.70 ms on this local machine; after-load resource samples are not peak
  capacity. A separate **128 MiB** fictional backup round trip passed in a Node
  child with 32 MiB JS heap and measured peak RSS below the asserted 160 MiB budget.
  No representative movie transfer or sustained production traffic was attempted.
- Artifact/archive/stream boundaries **6/6**, local-target guards **9/9**, strict
  policy validator passed. Retained product **112/112**, fake mail/OAuth **88/88 +
  7/7**, operations **57/57**, quarantine **51/51**, environment **21/21**, SQL/fake
  delivery **10/10**, extractor HTTP **33/33**, pure PHP client **14/14**, fresh native
  HTTP **94/94**, schema/claim **22/22**, protocol **6/6** and extractor service
  **6/6** passed. Separate results preserve prior
  evidence. Dependency manifests/locks did not change; advisory audits were not
  refreshed in this phase. No container vulnerability scan/penetration test claim.

The intended configuration and executable request contract are documented in
`../../ops/README.md`. Promotion runs record the exact **reviewed index tree** and
base commit, not an assertion that uncommitted code was already a commit. Deployment
images receive no operator helper/dev alias or secret. Private raw SQL, keys, session
filenames, backup payloads, challenges, proofs and diagnostics stay ignored.

## Investigated failures

The first focused run was 42/44: Windows denied fsync on a read-only descriptor;
the streaming publisher now opens its own generated file read/write before fsync.
The next 15-case operations run passed. The first complete selection was 118/119:
adding a null socket property changed the existing disabled configuration shape.
That property is now emitted only in the new private mode; the retained contract
is preserved, with explicit new-mode negative tests.

A later retained checkpoint-pause case failed intermittently. Targeted runs
reproduced it (9/10 passed), then category-only instrumentation captured Windows
renameSync EPERM. Atomic replacement now retries only transient Windows sharing
errors, six attempts and at most 115 ms waiting, without unlinking old evidence.
Permanent/exhausted failures remain closed. **50/50** targeted post-fix pause runs
and the final **127/127** complete suite passed. Earlier failed evidence remains
private; no failing run was counted as acceptance.
The separate service-test host dependency lookup stopped before tests because its
old temporary install was absent. The service suite then passed in the owned,
lockfile-built Node image with only three read-only source mounts, fake credentials,
no network route/published port and temporary dependency wiring. No dependency was
installed/downloaded or real provider contacted for that fallback.

## Handoff and remaining acceptance

Local promotion controls are complete within the executed fake/isolated scope.
Production-readiness remains **NO GO**. No host was provisioned, SDK/provider
connected, real recipient contacted, credential rotated, remote Git pushed or
publication performed. All owned services are stopped; fixture volumes/keys and
failed-run evidence are retained. Static and secret-scan results/limits are in
PROMOTION_STATIC_RESULTS.json and PROMOTION_SECURITY_RESULTS.json.
PHP198/JS54/CJS25/Python21 syntax checks passed; final JSON parsing passed. Verified
Gitleaks 8.30.1 staged/full 526-file candidate/all-local-history/allowlisted-artifact
scans returned zero findings and 21 private ignore checks passed. Default rules do
not prove every secret absent; preserved private/legacy/fixture files were excluded.

Next owner decision: select the isolated staging host and budget. Then prepare the
exact host/runtime/TLS/CSP/storage/supervisor/key-custody/backup/alert configuration
and account-specific approved pairing issuer. Review the capped live plan with
dedicated account/recipient/media references, time window and stop authority before
any provider access. Actual off-host recovery, real SDK/large media, sustained load,
browser/codec matrix and independent security review remain gates. Publishing still
needs separate explicit approval after those results pass.
