# Administrative audit logging - ZL-19

Status: PARTIAL. Audit emits a fixed allowlisted action/result schema: random event
ID, timestamp, opaque optional actor, action, opaque optional target and result.
There is no arbitrary contextual metadata or exception text. Passwords, email,
session IDs/cookies, reset/MFA secrets, recovery codes and private documents have
no event field. Invalid actions/identifiers fail with a sanitized error.

Implemented service events include register, successful/failed login, verification,
reset request/completion and password change; fixture controller includes logout.
The bounded vocabulary also supports denied access, role change, publication and
upload, but their production controllers are not bound. Do not call these existing
legacy-admin coverage. ZL-15 audit remains independent and byte-preserved.

Account/token changes and required audit share Store.transaction. Failed required
audit prevents login and rolls reset/registration back in executed synthetic tests.
Read access requires audit.read and verified step-up through Authorization; without
an MFA adapter it denies. There is no new public audit viewer or log-read API.

The Store contract is append-only for application events. No production audit
adapter, retention purge, independent log collector, encryption, backup, tamper
evidence or write-once infrastructure is configured. Test fixture files are owned
by the test process and removed; this is not durable or tamper-proof logging.
Production needs retention/privacy policy, bounded storage/alerting, restricted
reader credentials, transaction failure drills and monitored delivery of events.
