# Authentication architecture - ZL-19

Status: PARTIAL. Read-only audit preceded changes. The extracted working tree,
including all earlier uncommitted work, is authoritative. Snapshot: ignored local
ZL-19 recovery record; no private SQL, accounts or sessions were inspected.

## Evidence and safe implementation decision

Source inspection confirms multiple password_verify / PASSWORD_DEFAULT paths,
not the algorithms or contents of deployed records. Complete users, admins and
password_resets DDL remains unavailable under ZL-14. No speculative account
migration or production identity adapter will be created. Existing MySQLi/PDO
contracts and real administrator recovery remain unverified.

Implement independent typed PHP security services and synthetic fixtures; retain
production activation blocked. Existing account access requirements will not be
replaced before approved recovery and rollout. Small error/CSRF input robustness
fixes are allowed without changing valid credential or session contracts.

## Initial static findings

| Severity | Evidence | Impact / disposition |
| --- | --- | --- |
| HIGH | admin/config.php: 100-year cookie/GC; no login rotation | Fixation and stale sessions; replacement lifecycle tested in isolation, rollout pending |
| HIGH | api/auth-handler.php, login.php, signup.php versus ajax_login.php | Verification policy bypass through alternate paths; adapter consolidation blocked by schema |
| HIGH | admin/config.php check_login versus admin/functions.php | Conflicting admin_id/admin_logged_in flags; no trusted RBAC, status refresh or MFA |
| HIGH | admin CRUD pages and trailers.php uploads | Missing CSRF, GET deletes, extension-only names, public storage and shell execution; production release blocked |
| HIGH | ajax_reset_password.php | Token checked before transaction, update not conditional on unused token; no session revocation; replacement requires atomic adapter |
| HIGH | google-callback.php and profile.php | No OAuth state binding; email-based linking without complete verified provider proof; keep provider disabled |
| MEDIUM | ajax_login.php / login.php / admin/login.php | Enumeration, absent rate limiting, debug/error disclosure |
| MEDIUM | includes/csrf_helper.php | Malformed token types reach hash_equals; future timestamps accepted |
| MEDIUM | logout.php / admin/logout.php | GET logout and inconsistent session store bootstrap |
| MEDIUM | admin/config.php / ajax_login.php | Raw remember token, no independently verified expiry/revocation/rotation contract |
| MEDIUM | includes/rate_limiter.php | Caller-controlled forwarded IP precedence, local bypass, non-atomic counters; not reused for auth |

Paths in this table are relative to Zinema.lk Website/. Findings are statically
inspected, not live exploit evidence or proof of installed production behavior.
No complete route, database, MFA or production readiness claim is made.

## Implemented boundary and scope (2026-10-24)

ZL-19 is PARTIAL. Pure Identity definitions contain Authentication, Password,
Recovery, Audit, Abuse/WindowLimiter, Csrf, Authorization, SessionState,
NativeSession and Upload. Store/MailSink/Clock/StepUp are explicit trusted ports.
Factory::production throws 503; bootstrap performs no environment/session/DB/mail
startup. No public identity/admin route, guessed schema, new framework or package.
Only the test router binds fictional identities, local counters and mail sink.

Three existing application edits: robust legacy CSRF input/expiry checks; admin
login hides debug and database exception details; duplicate AJAX signup no longer
deletes expired unverified accounts. Existing valid account requirements, hashes,
100-year sessions, remember cookies and role flags are otherwise preserved pending
schema/recovery/approved rollout. Legacy vulnerabilities remain release blockers.

### Current first-party flow map

All paths below are relative to Zinema.lk Website/. Confidence is STATICALLY
INSPECTED; test-executed replacement behavior is identified separately.

| Entry / caller | Data and checks | Session / failure / remaining boundary |
| --- | --- | --- |
| signup.php form | users; CSRF, basic email/username, PHP default hash | Immediate user_id login; verification differs from AJAX; unverified DB constraints |
| ajax_signup.php from shared modals | users; CSRF/validation, verification token and mail | No automatic duplicate deletion after this fix; email delivery/state remain unverified |
| login.php form | users; CSRF/password_verify | user_id; no consistent verification/status/rotation/throttle |
| ajax_login.php modals | users; CSRF/password_verify/is_verified | user_id; raw remember token; enumeration/rate/status issues |
| api/auth-handler.php JSON | users; prepared password query, registration | No method/CSRF/verification parity; user_id; wildcard CORS |
| admin/login.php | admins; prepared query/password_verify | admin_id only; now generic errors; no CSRF/MFA/throttle |
| admin/config.php | MySQLi/users/remember token | 100-year session; shared bootstrap; admin_id guard; no verified revocation |
| admin/functions.php | Alternate admin_logged_in flag | Conflicts with admin_id; no trusted permission adapter |
| ajax_forgot_password.php | users/password_resets; CSRF; random raw reset token/mail | Raw token storage and nonuniform mail failure; no rate/revocation |
| reset-password.php / ajax_reset_password.php | password_resets/users; CSRF, expiry/used then transaction | Non-atomic token claim before transaction; no shared session invalidation |
| ajax_resend_verification.php / verify-email.php | users; raw verification token / expiry | Enumeration and GET mutation/auto-login; no atomic consume/rotation |
| google-callback.php / profile.php OAuth link | users; provider mode, OAuth exchange/profile | No state binding; email-based linking trust unresolved; mode remains disabled in tests |
| logout.php / admin/logout.php | PHP session only | GET, inconsistent configured session store, remember-cookie handling unresolved |
| api/check-session.php | users subscription expiry, user session | Status is not a rights/entitlement grant; cache/CORS/identity contract unresolved |
| profile.php | user_favorites/shots, server session subject | Query filters by session user; full profile/privacy/history runtime NOT VERIFIED |
| api/comments.php / api/includes/comment_helpers.php | comments/users/movies; session, users.is_admin, owner checks | PDO bootstrap conflict; absent CSRF; object deletion policy incomplete |
| api/add_comment.php, like_shot.php, favorite_shot.php, record_view.php | social tables and user_id | No complete CSRF/status/schema verification; favorite/history are subject-scoped in source |
| api/trailers.php / includes/TrailerManager.php | trailers/interactions/user session | Repeated PDO names and missing full DDL/CSRF; no auth rewrite |
| admin/index.php / analytics.php | dashboard queries | Session flag only; no production runtime proof |
| admin/movies.php / series.php / episodes.php / collections.php | content tables, public uploads, GET deletes | No granular permissions/CSRF; unsafe uploads and data lifecycle release blockers |
| admin/manage_shots.php / settings.php | shots/admins/site_settings | Session flag only; GET/POST changes unprotected; extraction remains quarantined |
| admin/run_migration.php | Historical destructive SQL body | Existing unconditional 403 remains; body not executed |
| admin/cron/* / whatsapp-bot-tracking.php | Historical provider/statistics work | ZL-18 gate still denies before side effects |

Static source searches found no independently verified JWT identity or normal
password-change endpoint; these are not invented as existing functionality.
The new password-change service is fixture-tested only. The [machine inventory](AUTH_ROUTE_INVENTORY.json)
records initial line locations (before the small fixes), not stable post-edit lines.

### Additional source-backed finding

HIGH: ajax_signup.php formerly deleted expired unverified identities during
anonymous signup. The deletion branch was removed; generic duplicate recovery
response preserves identities. An actual-controller test rejects any unexpected
SQL operation and any mail call with a fictional expired identity. No real record
was read, changed or deleted. Other findings above remain unresolved unless a
specific narrow fix is explicitly described.

### Upstream and release gates

ZL-14 remains PARTIAL: sanitized users/admins/password_resets DDL, identity/unique
constraints, status semantics, role relationships, hash column capacity and CAS/
locking rules are missing. No account migration is justified. Four-table and
content migration SQL bytes/manifests stay intact. Repeated PDO parameter names,
MySQLi/PDO differences and runtime DDL concerns remain documented, not guessed away.
ZL-15/16/17 identity and rights integration remain PARTIAL; no permission grant
substitutes for their independent canonical/rights/entitlement checks. ZL-18 remains
in force; payments/messaging/extraction/external-media remain disabled.

Production needs shared limiter/session/audit/token persistence; verified unique
email/username/collation compatibility; digest migration from raw legacy tokens;
TLS/proxy trust; observable mail outbox; vetted MFA/encrypted key lifecycle/owner
recovery; full admin/API CSRF and safe upload binding. No current setting enables
this release. Inferred follow-up relationships are PROPOSED DEPENDENCY, not verified
Jira links: ZL-31 test gates, ZL-33/53 privacy, ZL-48 TLS, ZL-51 security review,
ZL-55 administrative policy. ZL-20 is not started.
