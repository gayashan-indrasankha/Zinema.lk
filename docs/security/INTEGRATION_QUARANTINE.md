# Integration quarantine - ZL-18

2026-10-24. Initial inventory/design before application changes. Current extracted
workspace: 409 eligible inputs, 101 existing dirty paths, empty index, HEAD
958dd9eaf97c72ea6e76648eae6814d775f03641. Preserve all prior working-tree changes.

SOURCE CONFIRMED: main Baileys entry checks configuration but production flags
could satisfy its previous startup guard. Direct imports of API client, forwarder,
upload queue, cache warmer/refresher, file manager and health monitor bypass that
entry. Forwarder loads pending requests/message cache; workers schedule refresh,
forward, upload and retry. Existing payment handlers exit 403 before provider/SQL;
WhatsApp bridge relies on a mode guard after environment loading. Token helper
functions are independently callable and can mint/store tokens or expose groups.
Extractor route has an early fixture/disabled return, but imports HTTP/framework
dependencies and starts its own listener. Admin/browser extractor callers and
legacy browser cached-origin fallback remain separate invocation paths.

Classification: historical WhatsApp/Baileys, extraction, external media and
IdeaMart payment/subscription operations LEGACY / UNAPPROVED. Offline generated
HLS and isolated synthetic delivery are SIMULATED. Official provider/CDN/payment
replacement PLANNED. Mail/OAuth and private historical variants UNRESOLVED, not
production approved. No production integration classified APPROVED AND VERIFIED.

Decision: one shared JSON policy used by small PHP/Node gates, live availability
always false irrespective of environment/legacy approval flags. Only test/local
explicit offline adapters may simulate; legacy implementations never use that
permission. Add early route/module guards plus checks at worker/action boundaries,
prevent queue/credential/entitlement side effects, preserve historical bodies and
interfaces, and show neutral unavailable UI. Public status reports safe capability
only. Keep ZL-17 modules/media policy byte-preserved and verify real local HLS again.
No live bot, session read, extraction, payment, production DB, Git write or ZL-19.

Private historical variants stay excluded and unread under ZL-10 protection. They
cannot be certified or executed in this task; managed image/export containment is
a separate verified boundary, not control of arbitrary owner-run private scripts.
Final inventory, tests, exact limitations and rollback follow after execution.

## Implemented inventory and disposition

| Component / paths | Entry and callers / exchanged data | State and risk / disposition / confidence |
| --- | --- | --- |
| whatsapp-bot-deploy/index.js; npm start/dev | Bot startup, credentials/session, live socket and request messages | LEGACY / UNAPPROVED; unconditional pre-import denial; no flag activation; source and simulated bypass tests verified |
| config/api-client.js, multi-tier-forwarder.js | API token lookup, cache/message mappings, forwarding and upload fallback | LEGACY / UNAPPROVED; module and action guards; original interfaces/bodies retained; no actual data/store used |
| upload-queue.js, cache-warmer.js, auto-refresher.js, health-monitor.js, file-manager.js | Queue admission/dispatch/retry, scheduled cache refresh, local media and heartbeat | LEGACY / UNAPPROVED; independent pre-import and method checks; no timer/store/provider initialization; invocation tests verified |
| config/database.js | Historical direct SQL/token access | LEGACY / UNAPPROVED; central entry plus existing hard throw; no DB connection |
| api/whatsapp/*.php; includes/whatsapp_token.php | Redemption/mapping/statistics/group links and usable request tokens | LEGACY / UNAPPROVED; all 21 bridge files denied before bootstrap, nine helper operation guards; no credential or queue creation |
| download.php, whatsapp-watch.php, pages/download.php, server-check.php, find_movies.php | Browser request/delivery/debug paths | LEGACY / UNAPPROVED; centralized early 403; UI no actionable delivery link |
| admin/whatsapp-bot-tracking.php; admin/cron/*.php | Admin mutations and token/stat maintenance | LEGACY / UNAPPROVED; guarded before auth/config/SQL; no historical queue cleanup; admin source withheld from image |
| fb-video-api/index.js, environment.js | Express entry, HTTP/Chromium extraction; browser/admin callers | LEGACY / UNAPPROVED; no framework/browser/transport import or listener; extraction actions separately guarded; pure config remains inspectable |
| admin/manage_shots.php; index.php | cURL/URL tests/health checks; cached-CDN loader/fallback | LEGACY / UNAPPROVED extraction/media; central cURL capability false, no browser extraction/cache-origin load; previews show unavailable |
| api/subscribe.php, api/payment-notify.php; js/subscribe.js | IdeaMart subscription initiation/callback; users expiry changes | LEGACY / UNAPPROVED; early 403 before request/body logging/SQL/provider; UI disabled; fake callbacks cannot grant access |
| js/video-player.js, js/main.js, series-details.php | Historical iframe/direct-media playback | LEGACY / UNAPPROVED external media; operation returns unavailable and episode locator output withheld; browsing preserved, no unsafe fallback |
| shared/offline-simulator.cjs | Fictional token/queue/cache-forward and extraction metadata | SIMULATED; explicit test/local only, bounded memory, no transport or persistence |
| apps/web/src/Playback; catalogue/content policies | Original generated HLS and metadata/entitlement decisions | SIMULATED / TESTED foundation; byte-preserved; real HLS browser regression passed; production factory remains 503 |
| Future official messaging, payment and CDN providers | Separate approved adapters | PLANNED; no endpoint/key/provider approval fabricated |
| Mail/OAuth and private historical variants/deployed jobs | Separate auth/external deployment boundaries | UNRESOLVED; existing mail/OAuth guards retained, no new approval; protected variants excluded/unread; production NOT VERIFIED |

Policy flags/defaults are in [feature controls](FEATURE_FLAGS.md). All four targeted
live capabilities are unavailable regardless of environment. Status exposes no
provider, credential, token or private path. The offline simulator is an explicit
separate boundary, not a switch into historical Baileys/extraction code.

## Static external-call audit and preservation

[Machine-readable evidence](EXTERNAL_CALL_AUDIT.json) records lexical operation
locations in 18 eligible targeted first-party source files (ten with retained
operation patterns). This is a bounded lexical audit, not exhaustive dynamic
call-graph proof. Ten Node entry modules have an unconditional gate before imports;
110 worker/API methods plus main/extractor actions have guards. PHP entry and
operation coverage is verified separately. Remaining socket/send/queue/extraction/
callback code is historical and unreachable through these tested entries.

Hash verification reconstructed the exact recorded starting bytes for the ten
Node implementation files and proved their bodies identical after removing only
ZL-18 guard additions. No token, cached-forwarding, queue/retry, refresh or extractor
implementation was deleted. Session/queue/media/private dump contents were not read.

## Intentional UI and safety changes

Legacy extraction/cached-origin Shot previews and direct episode/embed/media actions
are unavailable; metadata/linked-content/social browsing code remains. Existing
subscription controls cannot initiate a request, and delivery links lose their
href and gain accessible disabled text. This is an intentional quarantine, not a
claim that old unapproved streaming still works. ZL-17's authorized local-HLS and
progress components are unchanged and tested separately. No data model or real
production setting was modified.

## Remaining exposure and completion limits

ZL-18 is PARTIAL. Private historical variants, external schedulers/deployments and
full legacy DB/account/admin flows cannot be verified under current protections/
missing canonical schemas. They must not be run as alternate entry points.
The managed image excludes them; that does not police arbitrary owner-run scripts.
Mail/OAuth are separately unresolved and retain their prior configuration gates;
production flags are not new approval for them. Historical unused main.min.js has
no eligible first-party callers and was retained, not executed/certified. Other
legacy metadata APIs/origin contracts and local upload defects remain release
risks under the recorded architecture/auth/media tasks.

The managed web environment is not a global egress firewall. Dedicated read-only
network-none tests and operation tripwires establish only the tested quarantine
paths. No production worker was stopped, callback received, credential rotated or
account approval verified. Review production state separately before deployment.
See [results](QUARANTINE_TEST_RESULTS.md), [WhatsApp](../integrations/WHATSAPP_STATUS.md),
[extraction](../integrations/LEGACY_EXTRACTION.md), [payments](../integrations/PAYMENT_STATUS.md)
and [rollback](QUARANTINE_ROLLBACK.md). No ZL-19 is started automatically.
