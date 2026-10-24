# Environment requirements and separation — ZL-13

## Actual requirements and selected baseline

| Area | Source evidence / selection | Verification boundary |
| --- | --- | --- |
| PHP | Legacy PHP, MySQLi and PDO, sessions, JSON, file handling; selected PHP 8.2.35 to retain 8.2 baseline | Syntax/tests differ from full route compatibility; support ends 2026-12-31 |
| Extensions | mysqli, pdo_mysql, OPcache added; cURL used by existing optional handlers; JSON/session/fileinfo from base | External handlers remain disabled; PHPMailer has optional mbstring fallback; no observed GD/ZIP callers |
| Apache | Website-only document root; mod_rewrite and mod_headers; preserved .htaccess and slug rules | Safe static/denial paths tested separately from DB-backed rewrites |
| PHP libraries | Three existing PHPMailer src classes plus license; no Composer manifest added | Source preserved; SMTP/OAuth never contacted |
| Node | Bot declares >=14; extractor >=18; select Node 24 LTS, digest-pinned base, observed 24.22.1 | Parser/pure-test baseline only; provider npm installation/runtime not verified |
| Packages | Bot package-lock v3 retained; Baileys/mysql2/scheduler/pino/dotenv etc. Extractor lacks lock | Bot root manifest/lock consistency tested; no install scripts or vulnerability audit |
| MySQL | Select 8.4.13 LTS, utf8mb4/utf8mb4_unicode_ci, dedicated DML account | Multiple historical schema generations remain unreconciled; no legacy SQL imported |
| Frontend | Existing HTML/PHP/CSS/JS and media URLs; no frontend build manifest introduced | Static asset/guard checks only; browser, HLS and media provenance not verified |
| Runtime storage | Sessions isolated in tmpfs; MySQL in owned named volume; no upload/media mount | Upload workflows deliberately unavailable on read-only source tree |
| Existing infrastructure | No compatible root recovery Compose/workflow/bootstrap existed at phase start | Legacy service-specific material is preserved; no live service is reused |

Runtime references: [PHP policy](https://www.php.net/supported-versions.php),
[Node LTS policy](https://nodejs.org/en/about/previous-releases),
[MySQL release model](https://dev.mysql.com/doc/refman/8.4/en/mysql-releases.html).
All image digests and action commits are recorded directly in Dockerfiles and CI.

## Logical environments

| Property | Development | Local staging | CI | Future production |
| --- | --- | --- | --- | --- |
| Project | zinema-zl13-development | zinema-zl13-staging | zinema-zl13-ci-timestamp | Not provisioned |
| Services | web + db | web + db | web + db, transient test command | Requires approved architecture |
| PHP APP_ENV | development | test | test | production, future review |
| Web mapping | 127.0.0.1:8080 -> 8080 | 127.0.0.1:8081 -> 8080 | 127.0.0.1:8082 -> 8080 | HTTPS design pending |
| DB name | zinema_recovery_development | zinema_recovery_test | zinema_recovery_test | Not configured |
| DB app user | zinema_recovery_development | zinema_recovery_staging | zinema_recovery_ci | Least privilege to be reviewed |
| DB/network storage | Own project volume/network | Distinct volume/network | Distinct timestamp project volume/network | Backup/resilience pending |
| Credentials | New local random app/root values | Independently generated | Independently generated | Secret manager plan pending |
| Session storage | Own web tmpfs | Own web tmpfs | Own web tmpfs | Design pending |
| External modes | All six disabled | All six disabled | All six disabled | Approval-gated, not implemented here |
| Live/AI flags | false | false | false | No credentials configured |
| Logging | Errors private, display off; bounded count viewer | Same; no debug response | Bounded diagnostics; no artifact upload | Observability plan pending |
| Database port | Unpublished | Unpublished | Unpublished | Private network design pending |
| Runtime egress | DB internal; web ingress permits egress | Same | Same | Policy pending |
| Readiness | 503 until ZL-14 | 503 until ZL-14 | 503 contract checked | Unverified |

## Configuration and ownership contracts

The helper owns only `infra/environments/<profile>/.env`; it does not load legacy
credentials. Actual PHP variable names are `BASE_URL`, `DB_HOST`, `DB_NAME`,
`DB_USER`, **DB_PASS**, `SESSION_SAVE_PATH` and `APP_ENV`. No DB_PASSWORD/APP_URL
rename is introduced. MySQL uses MYSQL_DATABASE/MYSQL_USER/MYSQL_PASSWORD/
MYSQL_ROOT_PASSWORD only in its container. Root credentials never enter web.

The only application configuration change permits hostname `db` when explicitly
opted into RECOVERY_CONTAINER_MODE=local. Existing loopback, recovery database
name, recovery-user prefix, local URL and disabled integration guards remain.
All providers are optional and absent. No Node HTTP service, worker or scheduler
is needed for these PHP process/dependency checks. Health routes bypass legacy
admin/session/bootstrap DDL, while checking both PHP database drivers.

Source snapshots use an exact reviewed manifest and deny-by-default Docker
context. Git/private stores, actual `.env`, SQL dumps, auth/session material,
uploads, logs, backups and unreviewed paths are excluded. The known held admin
tracking page remains outside the image; this omission is a quarantine limitation,
not a completed feature. The ZL-10 public export policy is separate and unchanged.


## ZL-14 scope update

The managed dev/staging/CI databases remain empty/unmigrated. Database tests use
a fourth, temporary server on an independent internal network with memory-backed
storage, fresh runtime/migration/root identities and a verified synthetic marker.
No managed volume is shared or reset. Only four new reviewed CREATE-only SQL
paths enter the image; original SQL/dumps remain excluded. The 204-file build
context is separate from public export approval. Readiness remains 503 because
the complete canonical database is unavailable.
[Database matrix/evidence](../database/SCHEMA_INVENTORY.md).
