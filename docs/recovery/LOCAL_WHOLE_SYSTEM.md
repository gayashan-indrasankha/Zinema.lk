# Whole local system — 2026-10-29

Owner explicitly deferred hosting and requested the whole system locally. Start
from clean `ee3d223` on `zinema/local-acceptance`; preserve publication history and
the existing owned website-only preview/data. No remote Git or hosting work.

Intended local behavior before implementation:

- One native launcher uses installed XAMPP/Python/Node and local HTTP dependencies;
  Docker's local engine is timing out and will not be restarted globally.
- New owned fictional DB/media/session state, explicit private PHP bootstrap,
  loopback services only. Default application/provider/production flags stay off.
- Website/admin use real local PHP/SQL, local-only mail sink and fake OAuth.
  Administrator can inspect fictional verification/reset links in a local inbox.
- The actual fb-video-api HTTP app runs with a keyed generated-media adapter;
  no Facebook fetch/browser or provider credentials. Only managed fixture media.
- A separate Node simulator uses the real claim/begin/complete client and original
  generated files. Its acknowledgement/outbox is fictional; no Baileys, pairing,
  phones, recipients or external send. Handoff links remain inside localhost.
- Demo controls expose health/inbox/simulated chat, online/offline state and private
  CLI operator access. Restart never replays held or uncertain delivery work.
- Validate positive registration/verification/reset/OAuth/admin/extractor/token/
  simulated delivery and offline/restart behavior; keep launchers and credentials
  private, document a single start/status/stop workflow and leave it available.

Actual WhatsApp and Facebook cannot operate disconnected from their providers.
This phase provides a complete local substitute workflow, not real integrations.
Execution results and exact limitations will be recorded at handoff.

## Running instance

The full local instance is `.runtime/audit-20261029-004837`, with its pointer in
`.runtime/native-local/active.json`. It is left running. Website 8088, database
3307, SMTP 1025, fake OAuth 9081, extractor 3334 and simulator 9090 bind only to
127.0.0.1. The database, uploads, sessions and simulator ledger survive normal
stop/up. Fake mailbox/OAuth authorization memory is ephemeral. The earlier
website-only fixture was stopped with closed ports and all its files retained.

Use the native start/status/stop instructions at the top of dev/README.md. Login
as the fictional administrator using the private `preview-access.txt` path printed
at startup; member credentials are in the same file. Open `/__local/index.php` for
online/offline controls, sample delivery links and simulator outbox; open its Inbox
link to verify `.invalid` signups/reset messages. "Google" sign-in uses the local
fictional provider. Manage Shots' test button talks to the authenticated local
extractor app and returns only the managed original clip. No provider fetch occurs.

Sample movies 1/2 and episodes 1/2 have generated files in the simulator's private
media directory. Added catalogue items need their own legally usable local media;
the simulator honestly refuses items without a local file before claiming them.
The native fixture has a 256 MiB website-upload quota / 64 MiB quarantine and uses
account-owned state without root/chown. Existing CLI operator review, revision,
audit/quarantine/restore rules remain. No permanent purge was added.

## Evidence and corrections

- Connected HTTP/SQL tests exercise signup, inbox verification, reset/one-time use,
  fake OAuth, keyed extractor/authentication/unsafe-target denial, localhost handoff,
  real Node claim/begin/complete with simulated attachment, duplicate refusal,
  offline admission, restart and native storage inventory. No real recipient.
- Automated browser checks cover console controls, simulated chat, signup/inbox,
  fake OAuth and administrator extraction. Legacy CDN attempts are blocked by the
  local policy: three attempted, three failed, no nonlocal responses. No page JS
  errors. This is automated browser execution, not a manual interaction claim.
- Six pure guards cover valid private context, thirteen unsafe configuration
  mutations, owner mismatch, env-flag-only denial, three MIME transfer encodings
  and refusal of non-fixture Node targets. No service/database starts in that suite.
- A normal whole-stack stop/resume preserved user/claim counts, the exact simulator
  ledger and upload-manifest hashes. A consumed token could not replay afterward.
  Repeating `up` verified ownership and reused the existing processes.
- Original disabled-profile HTTP94 ran on separate fresh ports/database and passed,
  with zero PHP diagnostics; its owned temporary services stopped with closed ports.
  Node171 and retained pure Python49 also pass. Earlier phase reports are retained.

Initial native startup found the console needed Apache mod_alias; the installed
module was added to this fixture's config. First connected check stopped at an
inbox error: unconditional quoted-printable decoding damaged 8-bit token URLs.
The existing local sink now projects MIME-aware safe action links, with regression
coverage for 8-bit, quoted-printable and base64. Its raw message contract remains
for old tests. All failed scratch evidence is retained; later passing runs are
reported separately. A browser observer initially missed redirected OAuth requests;
the flow succeeded and the observer was corrected to use request events.

Structured evidence: LOCAL_WHOLE_RESULTS.json, LOCAL_WHOLE_BROWSER.json,
LOCAL_WHOLE_PERSISTENCE.json, WHOLE_LOCAL_BASELINE_RETAINED_HTTP.json and
LOCAL_WHOLE_CHECKS.json. Private browser screenshots and service logs live inside
the runtime's native folder. No original user media, legacy config/credentials or
real WhatsApp session was read/imported. No power settings, global XAMPP settings,
Docker engine, remote repository or published site was changed.

## Boundaries

Native privileges come only from the private validated bootstrap: test DB 3307,
loopback origins, explicit owner marker, private fixed directories and live flags
false. An environment flag alone cannot enable the native bridge or storage.
Normal disabled/local/container/staging profiles retain their earlier boundaries.
The native alias/bootstrap/console is not part of normal website exports.

The simulator uses the real local token protocol and hashes generated media in a
bounded stream, but its acknowledgement is fake. No real bot account, SDK socket,
nightly provider/group job, actual Facebook extraction, QR pairing or external
message runs. Existing fake lifecycle tests cover schedules; this launcher does
not certify real SDK schedules or delivery. The fake sender has a 128 MiB disk
reserve for tiny demo files; production worker defaults are unchanged. Local
services are not an OS network sandbox or a production security certification.

State is bounded: 200 simulator intents/outbox records; full/uncertain state stops
admission for review rather than deleting evidence or silently replaying. A process
crash may leave held work; do not clear its ledger automatically. This phase tests
normal process stop/restart, not an OS/power-loss recovery. No 2 GiB file transfer,
real provider size acceptance, sustained load or real-account claim follows.

Source remains uncommitted for review. Hosting, publishing and real integrations
are outside this local-only request. The immediate next step is to use the running
console and exercise desired local workflows; report any missing behavior against
this profile rather than switching on a live adapter.

## Final executed results

| Check | Result |
| --- | --- |
| Connected native integration | 28/28 |
| Automated whole-local browser | 8/8; zero page errors |
| Native bootstrap/MIME/target guards | 6/6 (13 config mutation and 3 MIME subcases) |
| Retained Node regressions | 171/171, zero skips |
| Retained pure Python regressions | 49/49 |
| Separate disabled-profile HTTP baseline | 94/94; zero PHP diagnostics; temporary ports closed |
| Normal whole-stack persistence | 5/5 comparisons/replay checks |
| Repeated start | Verified `already_running`; no duplicate session started |

The running whole-local PHP log also contains zero warnings/notices/fatals/
deprecations. Final browser console/chat screenshots were visually inspected.
Initial failure observations are retained in this record and private test artifacts;
the passing counts do not hide the earlier MIME/observer/config corrections.

Syntax: PHP220, JavaScript/CJS90, Python32, JSON110 pass. Gitleaks 8.30.1 archive
and binary verified against the saved checksum/archive; 611 source candidate files
scanned, zero findings. Ignore sentinels22 and whitespace checks pass. These scans
exclude private runtime credentials/sessions/DB/mail/ledger/media/logs and the final
summary, and cannot prove absence of every secret. No dependency/advisory refresh
or image/penetration/live security scan was performed. Schema content (normalized
checkout line endings) and disabled promotion-policy bytes remain unchanged.

Final runtime status: website/OAuth/extractor/simulator HTTP200, SMTP listening,
simulator online, no held work. It remains available for the owner's local use.
The source changes are unstaged; HEAD remains ee3d223. No new commit/push/PR was
made in this phase. The prior publishing work was preserved rather than rewritten.

## Continued local run: SMTP conflict resolved

The next continuation found all previous native process IDs absent. Port1025 was
occupied by Docker/WSL; the readiness check refused stale ownership rather than
contacting that listener. No Docker process/settings/container was changed.

Native SMTP now uses127.0.0.1:11025. Older owned native configuration changes only
its mail-port field during stopped startup, after checking all native destination
ports are free. Keys, SQL records, owned uploads and simulated delivery ledger
remain intact. PHP and the fake runner accept only1025/11025 for native mail;
arbitrary service ports/hosts are rejected. Docker default1025 is unchanged.

Fresh tests pass: native guards9/9 (including migration, occupied-port refusal and
arbitrary-port rejection), integration28/28 and automated browser8/8, zero page
errors/nonlocal responses. A normal whole-stack stop/resume then preserved user
and completed-claim counts and exact ledger/upload-manifest hashes; native11025
and unrelated1025 remained available. Repeating up returned already_running.
LOCAL_WHOLE_RESUME.json records all six comparisons. The earlier retained Node171,
Python49 and disabled-profile HTTP94 reports were not rerun in this continuation.

The same runtime is left running at localhost8088, all four HTTP health checks200,
SMTP11025 listening, simulator online and no held work. It remains fictional-only.
Use the private preview-access.txt and keep the launcher terminal open when you
start it yourself. Hosting/publishing/live accounts remain disabled/outside scope.
Changes remain unstaged/uncommitted at ee3d223. The panel-open request was queued;
automated browser evidence does not claim manual interaction by the owner.

Fresh whole-source syntax checks pass PHP220/JS-CJS90/Python32/JSON112. Verified
Gitleaks8.30.1 scans613 source candidate files, zero findings; private ignore22,
whitespace, canonical schema and disabled promotion policy checks pass. Runtime
credentials/session/DB/media/logs remain excluded and unprinted. Default-pattern
scanning is not a complete secret/security certification. No advisory refresh or
live-provider check was executed. LOCAL_WHOLE_CHECKS.json records this fresh run.
