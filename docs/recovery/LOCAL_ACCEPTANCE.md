# Local acceptance review — 2026-10-28

Scope authorized by the owner: run an isolated localhost preview; exercise
catalogue/search, account screens, administrator add/edit, generated image/clip
uploads and honest unavailable delivery. Only fictional accounts/new canonical
DB/generated media. Preserve the previous uncommitted adapter work and history.

Actual Stellar/account access, speed-test traffic, personal power/sleep/reboot
changes, independent target/backup credentials, provider accounts/recipients,
live extraction/WhatsApp and publishing remain outside this local execution.
The prepared live test plan requires specific accounts/recipients, caps and approval.

Intended checks: UI journeys plus retained HTTP regression; verify database identity
before any test SQL, generated media only, no full-content website playback,
no external browser requests, and explicit disabled/offline messages. Any confirmed
application defect gets a narrow fix and rerun. Tool limitations are recorded as
unverified manual coverage rather than a browser pass. Results pending.

## Confirmed observations before fixes

- The preview mirror excluded both tracked brand PNGs, producing a broken logo in
  the actual catalogue screenshot (404). This is a harness defect; add only those
  two explicit static paths, not arbitrary images/user media, to prepare/refresh.
- Disabled WhatsApp handoff returned a technical recovery-configuration message.
  Preserve HTTP 503 and the pre-DB/token gate, but render a friendly delivery
  unavailable page with a local catalogue link and no request/success implication.
- Catalogue metadata still advertises full website streaming despite the agreed
  WhatsApp-only full-content scope. Record as a remaining content/SEO review item.

Interactive tool initialization failed with kernel-assets path-not-found, including
one reset/retry. Headless Chrome with a fresh profile works. All non-loopback page
requests are denied; screenshots are visually inspected separately. No claim of
an interactive/manual browser pass.

Further browser findings: native Apache inherited a global PHP configuration without
GD, so a valid generated PNG was rejected. The installed extension exists. Use a
private minimal ini for this fixture with required modules, private sessions/logs,
and the 20 MiB application upload cap; never edit global XAMPP settings. Uploaded
trailer previews also resolved relative paths beneath /admin/uploads and failed to
play. Prefix owned uploads with BASE_URL in administrator previews. The upload form
advertised 100 MB/MOV despite the MP4/WebM 20 MiB validator; align its copy/accept.

Initial browser result was 9/15. Several failures were test-driver assumptions
(case-transformed username, missing required rating, and an edit depending on a
failed upload), not new application defects. Fix the driver to assert semantic
identity and valid form input; retain failed screenshots privately and rerun.

The strict private PHP run also exposed htmlspecialchars(null) in the administrator
trailer poster for uploads without thumbnails. Initial retained HTTP was 93/94;
keep diagnostics enabled and coalesce the optional poster to an empty string.
Rerun after refreshing owned source; do not suppress deprecation reporting.

## Current local results and handoff

- Final automated browser: **16/16 pass**, zero page JavaScript errors and zero
  external page requests. Fixtures include actual form PNG/MP4 upload, rejected
  SVG masquerading as PNG, add/edit retaining the image, real generated MP4 decode
  and advancing playback in both administrator preview and public feed, search
  navigation, honest disabled signup, login/logout separation and mobile widths.
  The driver is repeatable on this owned fixture; new records get fictional names.
- Screenshots were separately visually inspected: member profile, administrator
  create/upload, mobile form/table and unavailable-delivery page. Interactive
  browser tools failed initialization; **no new manual interaction pass** claimed.
- Final retained HTTP: **94/94 pass**, zero new PHP warnings/notices/fatals/deprecations.
  Static/secret results are recorded in the linked JSON file.
  Prior failed 9/15 browser and 93/94 HTTP runs remain private evidence; fixes were
  verified through actual browser input/media playback and fresh HTTP log offsets.
- Fixed local preview logo/static allowlist and isolated PHP/GD configuration;
  friendly disabled handoff preserves 503 and stops before DB/token access;
  administrator uploaded-video URLs now resolve correctly; optional empty posters
  no longer emit PHP deprecations; upload copy/accept matches 20 MiB MP4/WebM;
  the observed movie catalogue metadata now states WhatsApp delivery. Wider
  catalogue/copy, all codecs/devices and every admin action are not certified.

Results: [browser](LOCAL_ACCEPTANCE_BROWSER.json),
[HTTP](LOCAL_ACCEPTANCE_RETAINED_HTTP.json),
[local PHP capability flags](LOCAL_ACCEPTANCE_PHP.json),
[read-only machine policy](LOCAL_ACCEPTANCE_MACHINE.json),
[source/security](LOCAL_ACCEPTANCE_CHECKS.json).
[Next verification worksheet](NEXT_VERIFICATION.md) separates prepared controls
from actual-host/provider/recovery acceptance.

The owner confirmed Stellar has **not been purchased**. No account/database/
private path/HTTPS/CPU limit on that provider was contacted or verified. Local
PHP 8.2.12 plus required modules is compatibility evidence only; security patch
status remains unverified. New canonical data is fictional; no schema changes.

Read-only personal-machine query found sleep idle 10,800 seconds and hibernate
idle 900 seconds for AC and DC. These are configured timers, not measured sleep
or restart events; see [Microsoft timer behavior](https://learn.microsoft.com/en-us/windows-hardware/design/device-experiences/display--sleep--and-hibernate-idle-timers).
Nonzero timers need an owner-reviewed always-on policy. No settings were changed,
no restart/sleep triggered and no media contents enumerated/read. The collector
reports the same media volume metadata and a RAM snapshot; role confirmation comes
from the earlier owner statement, not its generic machineRole field. Sustained
upload, actual SDK/supervisor operation and independent backup remain unverified.

## Preview access and stop

The final owned fixture is `.runtime/audit-20261028-233435`, served only at
`http://127.0.0.1:8088`. It is left running for owner review. Private fictional
login details are in that fixture's `preview-access.txt`; never commit/publish it.
All provider/integration modes remain disabled. No bot is started by the preview.
The private PHP ini changes only this fixture, not global XAMPP/cPanel settings.

Stop from the repository root when finished:

```powershell
python -X utf8 docs/recovery/tests/audit.py stop C:\xampp\htdocs\Zinema.lk\.runtime\audit-20261028-233435
```

Reproduce browser tests against a freshly prepared owned fixture using an existing
Playwright installation (no browser/dependency download is performed):

```powershell
node docs/recovery/tests/local_acceptance.cjs <owned-runtime-path> <installed-playwright-package-directory>
```

Prepare now generates the original 32x32 PNG as `generated-acceptance.png` in the
fixture automatically; its bytes match the actual browser-verified input. The
runtime's `web/demo.mp4` is generated by prepare/ffmpeg.
The test reads only fictional credentials from the fixture source, creates a fresh
headless Chrome profile and denies non-loopback page traffic. It never opens user
browser profiles or real accounts. Browser artifacts remain under ignored
`.runtime/local-acceptance/case-*` and are not exported.

Overall plan: **PARTIAL / launch NO GO**. Local acceptance passes; actual hosting,
always-on configuration, speed, independent backup/restore, approved live identities
and publishing gates remain pending. No purchase, production access, remote Git,
credential rotation, live connection, deployment or publishing in this phase.

## Final verification record

Local checks: browser 16/16 and retained HTTP 94/94 pass (110 combined), no new
PHP warning/notice/fatal/deprecation entries, no browser page errors or non-local
page requests. Static syntax passes: PHP213, JavaScript/CJS88, Python28, JSON104.
Gitleaks 8.30.1 verified against saved checksum/archive scanned 590 source candidate
files with zero findings; 22 ignore sentinels and whitespace checks pass. Private
runtime credentials/sessions/media/logs and generated scan summary are excluded;
this is not a dependency/advisory, penetration, actual-host or all-secret proof.
Schema content and disabled promotion-policy bytes are unchanged (schema comparison
normalizes Windows checkout line endings). Final source remains unstaged/uncommitted
on `zinema/two-host-rehearsal` at HEAD `12c9954` with prior changes preserved.

Handoff independently confirmed outside restricted sandbox networking: localhost
8088/3307 are listening, catalogue returns 200 and the exact owned DB/datadir check
passes. A sandbox-only port probe reported unreachable and was not treated as
service-stop evidence. The owned launcher remains held for user review; no unknown
process/service was restarted. Requested in-app preview opening was queued, not
claimed as successful UI rendering. Generated public trailer screenshot was also
visually inspected after the final passing run.
