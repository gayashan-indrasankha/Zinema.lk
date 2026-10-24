# Jira ZL-19 execution report

Date: 2026-10-24. Current extracted working tree is authoritative.

## 1. Completion status

**PARTIAL - Core authentication security components and narrow legacy fixes are
implemented locally. Production identity, MFA and operational dependencies remain
unresolved.** No complete or production-ready claim.

## 2. Executive summary

Audited current auth/admin paths and preserved prior uncommitted recovery work.
Implemented independent PHP services, synthetic contract/HTTP/browser fixtures,
and three source-backed legacy fixes. Production factory remains unconfigured;
no new public privileged route, schema guess, real account requirement or rollout.

## 3. Existing authentication architecture

Separate HTML, AJAX, JSON and OAuth paths share a legacy MySQLi/session bootstrap;
comments/trailers also reference PDO. User/admin session flags differ. The complete
[flow map and static findings](AUTHENTICATION_ARCHITECTURE.md) distinguish actual
source checks from new isolated services and future integration.

## 4. Upstream dependency verification

ZL-14 users/admins/password_resets complete permitted DDL is unavailable. ZL-15
rights administration, ZL-16 trusted identity/entitlements and ZL-17 production
provider wiring remain PARTIAL. Their source and all SQL/manifests are preserved.
ZL-18 quarantine remains intact. No Jira completion or configured dependency edge
is inferred from local implementation.

## 5. Vulnerabilities discovered

HIGH: verification-policy bypass across alternate login/signup, 100-year sessions
without rotation/revocation, flag-only admin access, unsafe admin writes/uploads,
raw reset/remember tokens and non-atomic reset claim, OAuth state/linking trust and
anonymous deletion of expired unverified accounts. MEDIUM: enumeration/throttling,
CSRF malformed types/future timestamps, error disclosure and GET logout. Full safe
relative-path evidence/dispositions appear in the architecture and route inventory.
Only the three narrow legacy fixes below resolve their specific legacy findings.

## 6. Password improvements

New Password service uses supported PHP Argon2id or explicit bcrypt fallback with
safe byte limits. Known PHP hash verification and successful-login rehash are
synthetically tested. No historical row/format inspection, mass conversion or
real password change. Existing source uses password_hash/password_verify.

## 7. Registration and login changes

Isolated registration normalizes/validates, returns a generic duplicate response,
assigns only account permissions and never auto-verifies/logs in. Login validates
active/verified state, uses generic failures, throttle and required audit. Existing
ajax_signup now preserves expired unverified identities; admin/login suppresses
sensitive errors. Other legacy login/registration contracts are not replaced.

## 8. Sessions

Separate strict cookie-only namespace, secure production cookie parameters,
explicit loopback HTTP exception, login ID rotation, idle/absolute expiry, active
status/epoch resolution and logout invalidation are executed in fixtures. Epoch
change revokes concurrent old sessions. Legacy cookies/stores are not adopted,
modified or claimed secure. [Session detail](SESSION_MANAGEMENT.md).

## 9. Verification

Purpose-bound random digest token, expiry, one-use consume, no auto-login, resend
invalidation and abuse controls work in the fixture. Legacy verification GET and
raw-token migration remain unresolved. No real email was sent.

## 10. Password reset and recovery

Recovery contract atomically consumes digest, updates subject hash/epoch,
invalidates tokens and audits in one Store transaction. Synthetic rollback,
expired/replayed/malformed/disabled checks pass. Real SQL persistence/races and
mail outbox are blocked. [Recovery design](ACCOUNT_RECOVERY.md).

## 11. RBAC

Explicit trusted permissions and fresh active/verified/epoch checks; self-account
ownership; no caller role flags, wildcard or invented hierarchy. Every privileged
permission also requires a verified session-bound MFA port, missing by default.
No legacy content/admin role binding is claimed. [Permission matrix](RBAC_POLICY.md).

## 12. Administrative improvements

Generic admin errors; deny-by-default independent authorization; static inventory
of all existing admin routes and unsafe mutations. Existing real admin access is
not changed to mandatory MFA/roles without approved recovery. Legacy admin routes
are still release blockers, not universally protected by the new boundary.

## 13. MFA and owner recovery

**BLOCKED, not implemented.** No vetted existing library, verified account binding,
encrypted-secret/key lifecycle, HTTPS or tested owner recovery. No mock challenge
stands in for MFA. MissingStepUp denial and post-reset bypass denial are tested;
enrollment/recovery/replay/removal tests are NOT RUN. [Prerequisites](ADMIN_MFA.md).

## 14. CSRF

Independent session synchronizer token plus exact trusted Origin/POST controller
policy; malformed/type/expiry/forgery and legitimate AJAX tests pass. Legacy helper
now safely rejects array/future/expired input. Comprehensive legacy admin/API
integration is unresolved. [Coverage and limits](CSRF_PROTECTION.md).

## 15. Upload security

Independent PNG/JPEG byte/MIME/header/size/path/extension inspector and generated
filenames are tested. Storage/persistence remains 503, no new endpoint or shell
execution. Legacy uploads, full decoding/ownership/storage/private agreements and
malware scanning remain blocked. [Upload limits](ADMIN_UPLOAD_SECURITY.md).

## 16. Audit logging

Fixed non-secret event fields, action/result allowlists and transaction-coupled
required audit. Synthetic audit failure denies login/rolls mutations back.
No production durable log, retention/tamper resistance or admin viewer is claimed.
[Audit details](ADMIN_AUDIT_LOGGING.md).

## 17. Abuse prevention

Bounded temporary account/network HMAC dimensions; capacity denies instead of
unbounded allocation. No permanent account lockout or localhost bypass. The
process-local limiter is not distributed protection; test HTTP persistence is
fixture-only. Proxy trust and shared production storage remain unresolved.

## 18. Database migrations

None added/applied. No speculative users/admins/tokens/MFA/audit DDL or foreign
keys. Four verified base and two synthetic rights SQL migrations remain unchanged.
Managed CI audit reports zero application tables and readiness 503. No private SQL
or production database was accessed; no historical schema/data conversion.

## 19. Security test results

Final executed counts, runtime versions, commands, scanner scope and verification
limits are in [AUTH_TEST_RESULTS](AUTH_TEST_RESULTS.md). Tests distinguish static,
executed synthetic, runtime browser and NOT VERIFIED production evidence. Test
harness failures were corrected transparently; no unperformed check is a pass.

## 20. Browser verification

18 real Chromium protocol assertions on the isolated identity fixture pass; no
unexpected requests. This is scripted browser execution, not a human manual test
or full legacy application journey. No Safari/physical-device/TLS certification.

## 21. Existing regressions

Full host/image regression suite plus original HLS 49 and quarantine UI 8 browser
checks pass. Content/Catalogue/Playback and quarantine source are preserved.
Unconfigured catalogue/playback and schema readiness stay closed. Full legacy
homepage/profile/watch-history/admin SQL journeys remain NOT VERIFIED.

## 22. Files created or modified

New Identity files: Contracts.php, Password.php, Guards.php, Audit.php,
Authentication.php, Recovery.php, Session.php, Upload.php, bootstrap.php.
New tests/identity files: fixtures.php, contracts.php, identity.test.cjs,
router.php, server.cjs, http.test.cjs, legacy-prepend.php, legacy.test.cjs, browser.cjs.
Existing website files: includes/csrf_helper.php, admin/login.php, ajax_signup.php.
Shared verification: scripts/verify.cjs, infra/environment-files.json,
.dockerignore, .github/workflows/ci.yml. Documentation: the ten required auth
guides, AUTH_ROUTE_INVENTORY.json, this report; append README, current architecture,
CI pipeline, SECURITY_EXPORT, CONTRIBUTIONS and recovery STATUS/DECISIONS.

## 23. Git and data preservation

Starting HEAD 958dd9eaf97c72ea6e76648eae6814d775f03641 on
feat/zl-13-14-environments-migrations; empty index. Starting eligible file hashes
are saved in the ignored ZL-19 snapshot, distinguishing HEAD from dirty content.
Final checksum/Git/prefix verification is recorded in recovery STATUS. No reset,
stash, clean, stage, commit, push, branch/history alteration or archive extraction.
No real accounts/sessions/media/keys/database records were changed.

## 24. Known risks

The old admin/session/login/API paths retain significant security gaps until
verified adapter integration and approved rollout. The fixture is not a deployable
identity service. Legacy OAuth/MAIL production approvals and state/link trust,
raw-token migration, shared limiter and administrative upload paths remain unsafe
or unverified. A clean scoped scan does not certify excluded private material.

## 25. Production blockers

Sanitized complete identity DDL/contracts; compatibility fixtures; durable atomic
token/epoch/audit stores and concurrency tests; shared session/throttle storage;
mail outbox/delivery; TLS/proxy policy; vetted MFA, managed encryption keys, one-use
recovery and owner rehearsal; complete route CSRF/RBAC/object checks; safe upload
pipeline; privacy/retention; staging canary/rollback and approval. No production
activation until these are resolved.

## 26. Rollback/recovery

Use the phase-start working tree evidence, not HEAD or a historical ZIP. Preserve
prior dirty work; disable future binding before any cutover; apply reviewed small
corrections to the three legacy files. Do not restore anonymous account deletion or
debug disclosure. No schema rollback is needed. Real owner recovery is NOT VERIFIED;
[rollout and rollback prerequisites](AUTH_ROLLBACK.md) remain mandatory.

## 27. Documentation

Authentication architecture, sessions, RBAC, MFA, recovery, CSRF, uploads, audit,
test results, rollback, source inventory and this report. Existing contribution,
recovery, current architecture, CI/security export and README updated factually.

## 28. Remaining dependencies

PROPOSED DEPENDENCY: ZL-31 regression/CI/security gates; ZL-33 and ZL-53 privacy,
customer/security operations; ZL-48 HTTPS/environment security; ZL-51 independent
DAST/abuse/load review; ZL-55 safe administrative policy. Resume missing ZL-14
identity evidence first for live auth integration. ZL-20 follows in planning only;
no Jira link, status or task was modified here.

## 29. Recommended next Jira task

ZL-20 - Define catalogue-grounded AI evaluation and performance goals, only on a
new owner request. It must preserve the current production blockers. For production
authentication, first provide the approved sanitized identity/account DDL and
recovery/rollout requirements so ZL-14/ZL-19 can be resumed. ZL-20 was not started.

## 30. Jira-ready progress comment

ZL-19 PARTIAL: audited current auth/admin source; implemented isolated typed PHP
password, registration/login, recovery-token, session/epoch, CSRF, permission,
audit and upload-inspection services with synthetic security tests. Fixed legacy
malformed CSRF handling, admin error disclosure and anonymous deletion of expired
unverified accounts. Real-browser identity/HLS/quarantine checks pass; see attached
local test report for final counts and scope. Complete identity schema, production
stores/email/TLS, vetted MFA/enrollment/recovery, full legacy route hardening and
approved owner recovery/rollout remain release blockers. No speculative migration,
private SQL, real account/session/provider operation, Git write or ZL-20 work.
Do not mark production authentication or this Jira issue COMPLETE.
