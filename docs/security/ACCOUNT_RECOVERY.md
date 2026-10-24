# Verification and account recovery - ZL-19

Status: PARTIAL. Authentication and Recovery run with fictional accounts and an
in-memory test Store; local HTTP fixture state is protected by an exclusive file
lock. Neither fixture is an account database design or production repository.

Registration validates ASCII email up to 254 bytes, explicitly proposed lowercase
normalization, and a Unicode password of at least 15 code points. Passwords are
not trimmed and have no forced composition rule. New/duplicate registrations
return the same accepted shape, without auto-login or caller-supplied roles.
Existing collation, uniqueness, username mapping and registration compatibility
require verified DDL before adoption. This is not proof of constant-time responses.

New hashes use PHP Argon2id, 64 MiB / 3 passes / 1 thread where available; fallback
is bcrypt cost 12, with explicit 72-byte rejection to avoid silent truncation.
Maximum Argon input is 128 bytes. PHP bcrypt/Argon verify is supported, unknown
formats deny. Actual historical password rows were never inspected. A weaker
known PHP hash is rehashed only after a successful verified active-account login,
within the adapter transaction. No mass rehash, password conversion or real reset.

Recovery request responses are generic, including unknown account/delivery failure.
Only active unverified accounts receive verification; only active verified accounts
receive reset tokens. Cryptographic random tokens are 32 bytes, hex encoded; only
SHA-256 digests enter token storage. Purpose, subject and expiry are bound by the
Store; reset TTL 1,800s, verification TTL 3,600s. Resend replaces the prior token for
that purpose. Tokens expire at the exact boundary and consume once.

Store.consume must atomically remove a matching unexpired digest while locked in
the same transaction as password/verified-state change, epoch increment, token
invalidation and required audit. Any failure must roll back all mutations. The
synthetic rollback tests pass; production SQL races, isolation and constraints are
NOT VERIFIED. No authentication DDL or migration has been invented.

Neither verification nor reset automatically signs in or creates MFA evidence.
Password change requires the current password and matching current identity epoch.
Existing legacy reset raw tokens/transaction race, verification GET auto-login,
remember cookies and lack of revocation are still unresolved integration blockers.

MailSink is an explicit trusted port. Tests deliver only to a memory sink; the
fixture's raw fictional tokens are outside Git/public paths and removed at cleanup.
No real mail, SMS, OAuth or provider is contacted. Production needs a durable
outbox with observable failures, fixed allowlisted link origin, no-referrer pages,
no token logging, delivery/retry policy and approved provider tests. Current sink
failure is hidden from the public response and has no durable retry implementation.

Abuse uses HMAC-separated network/account keys with bounded temporary windows.
Use direct observed peer IP; proxy policy and IPv6/network aggregation are pending.
WindowLimiter is process-local, not distributed protection. The HTTP test fixture
persists its own bounded counters under a file lock; it is not a production option.

A legacy signup defect is fixed: expired unverified accounts are no longer deleted
by anonymous registration. Existing identities are preserved and receive a generic
recovery message. The actual controller regression uses only synthetic substitutes.

Reference: [OWASP recovery guidance](https://cheatsheetseries.owasp.org/cheatsheets/Forgot_Password_Cheat_Sheet.html).
