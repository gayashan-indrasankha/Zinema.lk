# Next verification gates — 2026-10-28

Owner confirmed Stellar has **not been purchased**. No account deployment or
purchase is authorized by this worksheet. Free access and WhatsApp-only full
content remain the launch scope. All live/publishing switches stay off.

| Gate | Concrete next evidence | Current result |
| --- | --- | --- |
| Local website | Account/admin/search, generated PNG/MP4, unavailable delivery, responsive views | Automated browser and retained HTTP results in LOCAL_ACCEPTANCE.md; interactive tools unavailable |
| Hosting before purchase | Provider confirmation of compatible supported PHP/extensions, account-owned private paths, database/user grants, HTTPS, client access restrictions, quotas/inodes/process/CPU/RAM/cron policy | Not verified; no account exists |
| Private staging after approval | Separate origin and DB/user; disabled review bundle; exact private PHP handler/.user.ini/prepend/permissions/locking tests | Not deployed or activated |
| Always-on personal machine | Owner-approved power policy, supervisor identity/startup, controlled restart and failure recovery, measured uptime | Nonzero idle timers observed; no settings changed or restart triggered |
| Sustained upload | Approved receiver/account, request and byte caps, duration/window, bandwidth cost and stop conditions | No receiver/approval; no speed test performed |
| Independent recovery | Separate machine/location/storage owner, independent encryption-key custody, generated first backup, isolated new-target restore, hash/row match and timed recovery | No independent destination supplied; prior local mirrors are not this proof |
| Capped live integration | Exact dedicated accounts/original clip/own recipients, approval revision, window, stop operator, delivery/attempt caps | PRODUCTION_LIVE_TEST_PLAN.md prepared; not approved/executed |
| Publishing | Review all above evidence, rollback/alerts/rights/privacy/support readiness and explicit publishing authorization | Disabled; NO GO |

## Host evidence without disclosing credentials

After separate account-access authorization, the private CLI-only
`ops/staging/stellar/host-capabilities.php` prints version/module flags without
loading the application or connecting to a database. Never expose phpinfo or
place credentials/configuration/probes in a public document root. CLI evidence
does not establish the web handler or `.user.ini` behavior; both need testing.
The owner can share its sanitized JSON, not passwords or cPanel/session material.
Do not buy hosting solely on the passing local test results.

## Personal-machine observations and next controlled test

Read-only query found AC/DC sleep-idle settings of 10,800 seconds (three hours)
and hibernate-idle settings of 900 seconds. Those are configuration observations,
not measured shutdown times or proof the machine actually sleeps. Windows sleep
and hibernate timers depend on the supported power model; hibernate timers can
apply after sleep. See [Microsoft sleep timers](https://learn.microsoft.com/en-us/windows-hardware/design/device-experiences/display--sleep--and-hibernate-idle-timers).
Always-on service therefore remains unverified. Do not infer it from boot age.

Owner should choose an operating policy, arrange recovery power and supervision,
then approve a scheduled fake-worker restart test. First prove a stopped worker
produces unavailable status, stale work stays held, only one worker restarts and
no duplicate send occurs. Do not force sleep/reboot during personal use or alter
system settings as a side effect of an inventory. A real SDK restart is a separate
approved account test after fictional recovery passes.

For an initial network probe, prepare one owner-approved HTTPS receiver and one
upload capped at 16 MiB/60 seconds, no automatic retries, no user media retained,
with verified TLS and a stop operator. This is a proposed **unapproved** small
connectivity/throughput sample, not sustained or 2 GiB performance acceptance.
Agree a larger sustained byte/time budget only after that first result. Do not
use an arbitrary third-party speed-test service without approval.

## Live scope remains separate

The existing first-live plan caps small original media sends at five and the
test window at one hour, with dedicated owner-controlled identities. It does not
approve a 2 GiB stress test. Agree exact large-file bytes, send count, bandwidth,
recipients and window separately after upload/storage/SDK prerequisites pass.
Approval of testing never authorizes production users, public publishing, DNS,
billing, credential rotation or continued unattended live connections.
