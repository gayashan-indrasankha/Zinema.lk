# Authorized local repair phase

Date: 2026-10-25. State: **COMPLETE within the authorized local repair scope**.

The owner authorized authentication/CSRF and bot delivery repairs, schema reconciliation and making the retained regression tests pass. Production integrations and publishing stay disabled. Git status confirms neither the root nor nested bot is a repository. A private source/test baseline is preserved under excluded `.recovery-private/` before edits.

## Intended behavior before implementation

- All password-login routes accept only verified accounts and regenerate the session identifier on authentication. Registration must require verification and report mail failure honestly; it must not grant a session before verification. Remember-me login must enforce verification and be revocable on logout.
- Browser state changes require an unsafe HTTP method plus a session-bound CSRF token. JSON, form, multipart and DELETE callers can supply the same token through a request header or field. Admin deletion must use POST. Successful UI and HTTP test callers must supply real session tokens; missing/invalid-token assertions remain negative tests.
- A single sanitized canonical schema describes local development and tests. Preserve the original dump/migrations as historical evidence; never import their data. Reconcile existing entity names instead of inventing duplicate movie-comment stores. Test data and local database targets remain isolated.
- A bot operation returns success only after a valid client acknowledgement. Every coalesced waiter settles on success or failure; one recipient's delivery failure must not become a successful token redemption. Timeout handles must be released. All bot tests use fake clients; real WhatsApp startup remains blocked.
- Trailer/comment routes must execute meaningful populated queries against the canonical schema, not merely return an empty successful response. View-history uniqueness is enforced in the database.

The original assessment and result files are baseline evidence. Repair verification will be recorded separately so failures are not erased from history. Dependency-advisory remediation and live-service certification are not implied by passing functional regressions.

## Delivered behavior

- `includes/session.php`, `auth.php` and `registration.php` provide consistent session, verified-password login and registration handling. Successful password/admin/verification login regenerates the session ID. Session cookies are HTTP-only, SameSite=Lax, session-lived and secure on HTTPS. Existing unverified user sessions are rejected on bootstrap.
- All three password-login routes reject unverified accounts. Both alternate registration paths now use the same verification-required policy; disabled mail prevents account creation, while a failed enabled delivery returns an honest pending-account result without granting a session.
- Remember-me credentials are stored as SHA-256 digests with server-side expiry. Logout revokes them and uses POST plus CSRF. Password reset also clears persistent login credentials. Existing raw legacy remember tokens cannot authenticate under the new policy; no preserved account credentials were rotated or used.
- Browser writes share server-side CSRF enforcement in the bootstrap. Form fields and a same-origin fetch header supply tokens. Wrong, missing, array-valued and cross-session tokens are rejected. Admin deletions and logout use protected POST forms. The existing disabled payment/download routes still exit before their legacy behavior.
- Native mysqli and PDO use the same explicit `DB_PORT`. Trailer and movie-comment paths initialize PDO deliberately. Trailer poster/video fields map to catalogue/administration fields. Movie comments use the existing `movie_comments` store with reply linkage and owner-authorized deletion.
- The canonical schema fixes view-history uniqueness and missing identities, adds the required trailer/operational fields, and reconciles multipart/full delivery mappings. See [schema reconciliation](SCHEMA_RECONCILIATION.md). The original dump was not imported.
- Bot operations now require a valid send acknowledgement. Coalesced requests share pending results, settle failures, report recipient-specific delivery failures correctly and release timeout handles. Cache-warmer completion alone is not treated as recipient delivery. Token consumption rejects already-used, inactive or expired tokens.
- An additional populated-dashboard failure found during final log review was fixed: token distribution uses `is_active`, `is_used` and expiry rather than a conflicting `status` column. The regression suite now checks PHP diagnostics as well as HTTP responses.
- Unreferenced, incomplete auxiliary bot entry points were changed to explicit fail-closed quarantine stubs. Their original bytes are preserved privately with the repair baseline; the current bot modules remain the supported implementation.

## Executed verification

| Check | Result | Evidence |
| --- | --- | --- |
| Final application/HTTP/security assertions | **94/94 passed**, exit 0 | [REPAIR_RESULTS.json](REPAIR_RESULTS.json) |
| Configuration/startup guards | **10/10 passed**, exit 0 | [REPAIR_SERVICE_RESULTS.json](REPAIR_SERVICE_RESULTS.json) |
| Bot module regressions with fake recipients | **12/12 passed**, exit 0 | Same service report and repair-prefixed TAP files |
| Empty schema rebuild and local SQL adapter checks | **19/19 passed**, exit 0 | [REPAIR_SCHEMA_RESULTS.json](REPAIR_SCHEMA_RESULTS.json) |
| Extractor disabled/local HTTP checks | **4/4 passed** | Service report |
| PHP syntax | **167/167 passed** | Application report |
| JavaScript syntax | **40/40 passed** | Application report |
| Final request PHP diagnostics | **0 warnings, notices, fatal errors or deprecations** | Application report |
| Clean dependency install and offline import/serialization | **Passed**; no socket/auth store started | [REPAIR_STATIC_CHECKS.json](REPAIR_STATIC_CHECKS.json) |

The original failing expectations remain negative security tests. Positive request tests were updated to send valid session tokens and to use POST for deletion/logout. Populated trailer queries, comment replies/ownership, cookie revocation, duplicate recipients and failed recipient acknowledgements were added so an empty successful response or blanket denial cannot make the suite green.

The test fixture uses Apache/PHP on loopback 8088, a new MariaDB data directory on 3307, fictional `example.invalid` accounts and generated media. Database identity is checked before SQL operations. The schema adapter check briefly starts a **SQL-only** local PHP bridge on 8089 with a fake API key and all other integrations disabled; it never imports Baileys or contacts WhatsApp. Both views also rebuild from the final canonical file in another empty database.

Browser checks reproduced rejection of the previously accepted unverified login; verified AJAX login and saved Shots still work with CSRF protection. The trailer page loads its generated clip. Admin login, token-bearing POST forms and POST logout were exercised. Screenshots: [rejected login](evidence/repair-unverified-login-rejected.png), [saved Shot](evidence/repair-csrf-save-profile.png), [trailer playback](evidence/repair-trailer-playback.png), [admin POST controls](evidence/repair-admin-post-controls.png).

All temporary Apache/MySQL, extractor and local bridge processes were stopped; their test ports were closed. No original XAMPP/production database was used. Browser test tabs were closed.

## Dependency and security evidence

The bot remains on Baileys **6.x**, with lockfile version **6.7.24**, a **protobufjs ^7.6.6** security override and a Node **>=20** requirement (tested with Node 24.13.1). A clean isolated `npm ci --ignore-scripts` succeeded; factory exports and a fictional protobuf encode/decode round-trip passed. The previous installed bot `node_modules` tree was preserved; a future approved runtime should install the repaired lockfile rather than assume that old tree is current.

Executed npm audits reported **zero findings** for the repaired bot lockfile and the separately locked extractor HTTP test subset (Express 4.22.3, CORS 2.8.6, Got 11.8.6). The HTTP subset is under `tests/extractor-http/`; it is not a complete extractor dependency audit. Chromium/Puppeteer and live extraction remain quarantined and unverified.

Dedicated gitleaks/trufflehog scanners were unavailable. No comprehensive secret, penetration, privacy or media-rights certification is claimed. Initial harness/schema errors, a mixed-collation view error and the dashboard failure were investigated and corrected; their historical private logs were not erased. Final results are recorded separately from the baseline assessment.

## Git state, preserved work and limits

At phase start neither directory was a repository. During work a root repository appeared with existing commit `f89da9b` and branch `main` tracking `origin/main`; most application files are untracked. No Git initialization, staging, commit, remote operation or push was performed by this repair. The nested bot resolves to the same root repository. A comment-only difference in the old bot schema and README differences were also observed; their provenance is unestablished and they were preserved. Do not attribute every baseline difference to this repair.

The original schema data and private material remain excluded from publication. The private baseline preserves pre-repair source and test evidence; the canonical schema has no rows, account credentials, environment-specific definers or database-selection statements. This phase does not provide an in-place migration for an existing database, deduplicate legacy data, or certify the complete product for production.

Live WhatsApp, OAuth, payment, external mail and actual Facebook extraction were not enabled or contacted. Distributed token reservation across multiple bot processes, large uploads, reconnect behavior, full email/OAuth journeys, upload hardening and broad accessibility/performance checks remain future work.

Exact next recommended Codex task:

> Prepare a reproducible local development environment and fake mail/OAuth integration tests, review uploads and distributed token claims, and establish a sanitized local Git baseline after a dedicated secret/data review. Preserve existing Git work. Keep production integrations, publishing and deployment disabled.
