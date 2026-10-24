# Administrator MFA - ZL-19

Status: BLOCKED for implementation/activation. MFA is NOT implemented. No mock
challenge, request Boolean or password-only login is described as MFA. Every
replacement privileged permission denies through MissingStepUp. Existing legacy
admin login is unchanged apart from safe error disclosure; it is not MFA-protected
and must not be represented as production-ready or universally fail-closed.

TOTP is a proposed local-first method; no supported vetted TOTP/WebAuthn package
is present in the reviewed application dependency set. We did not implement a
custom algorithm or add an unverified package. Complete account binding DDL,
encrypted-secret storage, managed encryption-key lifecycle, trusted HTTPS,
transactional replay counters, shared throttling and owner recovery are missing.

Required before implementation and rollout:

1. Verify sanitized account/role DDL and existing administrator access/recovery.
2. Select and review a maintained vetted library and its dependency/license policy.
3. Establish encryption keys outside Git/public root with rotation/backup procedure.
4. Build pending enrollment tied to fresh password proof; activate only after a
   successful challenge. Verify account status and epoch on every attempt.
5. Atomically reject replayed time steps; enforce bounded shared attempt limits.
6. Generate random one-use recovery codes, store only digests, show once, and
   consume atomically. No master bypass code. Regeneration revokes old codes.
7. Require step-up for factor removal, re-enrollment and role elevation. Reset
   must preserve MFA enrollment and cannot mint a verified step-up session.
8. Execute enrollment, expiry, replay, concurrent recovery, alternate-route and
   reset-bypass tests, then rehearse owner recovery and obtain rollout approval.

Those enrollment/recovery tests are NOT RUN because the implementation and
required adapters do not exist. Tests currently prove denial without MFA, including
a password reset followed by a correct login. No real administrator was enrolled,
locked out, given a new requirement or had a factor/password changed.
