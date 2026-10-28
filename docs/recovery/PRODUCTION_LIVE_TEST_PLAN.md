# Controlled live-integration test plan — approval required, not executed

Scope: a fresh, free, WhatsApp-only full-content launch. This document grants no
permission. The owner must explicitly approve each provider/account/recipient
scope after the local/staging prerequisites in PRODUCTION_STAGING.md are satisfied.
No real account, mailbox, Facebook resource, WhatsApp recipient/group, production
server, DNS entry or hosting resource was used by the test suites.

## Approval record (keep actual identifiers privately)

Record a plan revision/source commit, approved staging origin, selected cases,
provider/account ownership, dedicated test account reference, exact allowlisted
recipient/inbox/group references, legally usable media references, send/request
caps, test window, person who can stop the run, and approval time. Store actual
addresses, numbers, URLs, secrets and session details in ignored private records,
not this repository or chat output. Approval for tests does not authorize publishing,
credential rotation, important WhatsApp accounts/groups, billing or real users.

| Case | Concrete action after approval | Cap / expected evidence |
| --- | --- | --- |
| MAIL-01 | Send one verification and one reset message to one owner-controlled test inbox | 2 messages; delivery/expiry/one-time use verified, no bearer values in report |
| OAUTH-01 | Sign in through a test-mode provider application with one owner-controlled test identity | 1 successful flow and one locally simulated denial; correct account/link/session result |
| FB-01 | Extract one owner-published original demonstration clip through the authenticated staging proxy | At most 2 fetch attempts; valid media, metadata/error classification, no bypass/login scraping |
| FB-02 | Check one owner-specified deleted/unavailable original fixture | At most 1 fetch; controlled unavailable result, no credential/cookie import |
| WA-01 | Connect one dedicated approved test bot account with no imported historical session; owner performs device/QR authorization | 1 connection; online identity/heartbeat/status, no QR/session output in reports |
| WA-02 | Issue a movie token for an original small clip and deliver to one approved owner-controlled recipient | 1 media send; correct identity/content and server completion; record only opaque evidence references |
| WA-03 | Repeat the consumed token and try a second approved recipient | 0 additional media sends; unavailable/consumed result |
| WA-04 | Deliver one original episode/part fixture and inspect size/hash | At most 1 media send; correct part/content; no production group |
| WA-05 | Controlled disconnect/restart then deliver one fresh original fixture | At most 1 media send; one monitor/store writer/worker, bounded recovery and no duplicate send |
| WA-06 | If required, test one designated storage/cache test group with an approved participant list | At most 1 upload and 1 forward, counted in global cap; verify original remains in durable storage |

Global initial cap: **5 media sends**, one test bot account, two explicitly approved
recipient identities at most, and only the optional approved test group. Use the
original tiny red-frame fixture first; agree a small representative file size before
expanding. Do not test flood limits, large-file stress, permanent account actions,
real-customer delivery or payments under this plan. Local faults cover retry/timeout
and high-concurrency behavior without repeatedly contacting a provider.

Stop immediately on an unexpected recipient/group, repeated/ambiguous send, loss of
claim ownership, account restriction, provider warning/challenge, credential/session
leak, unexpected traffic, retry-cap breach or owner stop request. Hold uncertain
claims for reviewed reconciliation; never automatically reissue them. Do not delete
or reset account/session evidence to continue. Provider/device safety challenges
remain for the owner; no bypass is authorized.

After the run: stop workers, disable approved live adapters, verify no further
outbound work, retain private evidence, and produce a sanitized per-case result
(passed/failed/blocked/not run). Record actual counts and any uncertainty. Discarding
or rotating provider sessions/secrets requires its own authorization. A passing
small controlled test is not sustained production reliability or provider policy
compliance; review the selected WhatsApp integration's account/policy suitability
before committing a production account.
