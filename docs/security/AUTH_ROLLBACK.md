# Authentication rollback and recovery - ZL-19

Status: local change rollback documented; real administrator recovery NOT VERIFIED.
No real rollout occurred. Do not deploy new account requirements before owner
recovery rehearsal and explicit approval. No live session/password/schema changes.

Existing application edits: includes/csrf_helper.php (malformed/expired input),
admin/login.php (generic errors/no displayed stack), ajax_signup.php (preserve
existing expired unverified identities). New Identity modules and isolated tests
are additive. CI manifest, verify discovery and browser job are extended.
No account migration, historical migration edit or database conversion exists.

1. Preserve current working tree and ignored ZL-19 snapshot. It includes prior
   ZL-15 through ZL-18 work; HEAD is not an equivalent backup of those changes.
2. Disable any future new adapter binding first. There is currently none and
   Factory::production returns 503. Do not enable old provider/fallback routes.
3. Review only the three changed legacy files against the phase-start hashes;
   do not reset/checkout all files or copy from an archive. Apply a reviewed small
   correction rather than reintroducing account deletion/error disclosure.
4. Keep new/legacy session stores and cookie namespaces distinct. Do not transfer
   raw flags into a trusted identity. Preserve real stores; do not purge sessions.
5. Before future adoption, verify sanitized DDL and synthesize old/new account,
   hash, uniqueness, verification, reset and remember contracts. Rehearse owner
   enrollment, one-use MFA recovery, account disable/re-enable and rollback while
   maintaining an independent authorized recovery path. No master bypass route.
6. Run identity, legacy-controller, full regression and actual browser/HLS checks.
   Restore no production data and run no migration as a local documentation action.

Synthetic reset failure tests prove account/token/audit rollback in the test
adapter. They do not prove production SQL restore, MFA recovery, mail delivery or
concurrent cutover. The source snapshot is integrity evidence, not a deployed DB
backup. Real recovery, pilot compatibility and rollout approval remain blockers.
