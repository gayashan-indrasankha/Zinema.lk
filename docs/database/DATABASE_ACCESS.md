# Database access and compatibility - ZL-14

Application PHP and Node source is **unchanged from the ZL-14 starting snapshot**.
Exploratory PHP edits were withdrawn and checked against starting byte hashes
before implementation continued under the owner's four-table clarification.
No credentials, authentication rules, forwarding behavior or PDO defaults were
changed. This task supplies migration/test tooling, not a complete access-layer
repair.

## Current connection contracts

| Consumer | Current contract | Evidence / limit |
| --- | --- | --- |
| web/admin/config.php | MySQLi global $conn, utf8mb4, session/remember-cookie bootstrap | Runtime tested with an empty fictional session and the four-table fixture; normal auth flows blocked by missing users/admins DDL |
| web/includes/database.php | PDO global $pdo, utf8mb4, exception errors | Explicitly including it works against synthetic settings; not automatically loaded by the MySQLi bootstrap |
| api/comments.php, api/trailers.php, trailers.php | Expect $pdo after MySQLi-only include | Confirmed static wiring conflict retained; no speculative route changes |
| includes/TrailerManager.php | Reuses named parameters within statements | Legacy PDO emulation preserved; repeated placeholder behavior runtime verified independently of missing trailer tables |
| bot/config/database.js | Throws before importing mysql2; below it is a legacy pool using DB_PASSWORD | Guard unchanged; no live pool created. Its variable differs from PHP DB_PASS and must not be silently renamed |
| bot main/API client | HTTP bridge, optional delivery contracts | No session, bot, bridge credentials or real messaging activated |
| database/Runner.php | Dedicated native-prepared PDO, no application bootstrap/session | Runtime verified on a marked new test server only |

Actual application configuration remains in includes/environment.php with APP_ENV,
BASE_URL, DB_HOST/DB_NAME/DB_USER/DB_PASS and SESSION_SAVE_PATH. Staging uses test,
as established by ZL-13. No additional environment file is loaded by the runner;
it accepts only the explicit disposable-test contract. All integration modes stay
disabled. Existing runtime connections use their original error handling; the new
runner catches connection/DDL failures and reports only fixed error codes.

The new migration connection uses utf8mb4, a two-second PDO connection timeout,
exception mode and native prepares. It does not globally force native prepares on
legacy queries, whose repeated parameter names need compatibility review first.
PDO and MySQLi connections are separate transactions; no cross-connection atomicity
is claimed. The new DML transaction rollback test is not a DDL rollback guarantee.

## Verified subset and known blockers

Runtime tests execute parameterized settings/part lookups with both PHP APIs,
check injection-shaped input remains a value, and verify existing connection
modules when explicitly included. These observations cover synthetic rows only.
They do not prove full application routing, account/session security or complete
PHP database compatibility.

Settings and RateLimiter currently attempt CREATE during normal use. Their source
is preserved; runtime accounts are not granted CREATE to conceal that coupling.
The access fixture records the actual outcome under a DML-only identity. An
owner-authorized follow-up must move schema initialization out of runtime code
while preserving documented default-setting/rate-limit behavior and testing
affected routes. Existing settings reads may also insert defaults; do not mistake
them for pure reads or execute them against real data as a health check.

Offline Node tests extract only the existing token-reader function into a mock
pool context: missing/inactive/used/expired handling, token normalization and bound
parameters are checked. They neither require mysql2 nor start an integration.
They confirm query expectations for fields absent from the conflicting token DDL;
they are **not an executed mysql2/database compatibility test**. Full Node SQL,
pool/transaction behavior, message/queue/cache contracts and token integrity remain
blocked by unresolved schema generations and the intentionally disabled adapter.

Existing risks outside the four-table scope include raw diagnostic logging in
legacy delivery code, non-atomic token usage, dynamic SQL identifier handling,
runtime DDL and missing PDO route wiring. No new input-dependent SQL interpolation
is added. SQL identifiers in the runner/harness are fixed/allowlisted; all new
value-dependent statements use bindings or generated strictly validated local
bootstrap values. No real record or connection string is printed.
