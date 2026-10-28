# Production feature checklist — fresh free WhatsApp launch

Owner decisions, 2026-10-25: **free access; full movies/episodes via WhatsApp only**.
Shots/trailers and catalogue browsing remain website features. Hosting is undecided.
Use a small single-server beta plus separate staging as a planning assumption,
not a purchased platform or a measured capacity promise. Publishing remains disabled.

The route inventory indexes 92 first-party PHP entry candidates, including internal
partials and disabled diagnostics. `PRODUCTION_ROUTE_INVENTORY.json` identifies each.
The GET sweep verifies boundaries/error responses, not complete feature behavior.
This checklist maps functional groups to executed tests and explicit remaining gaps.
Final executed counts and limitations are recorded in PRODUCTION_AUDIT.md.
Follow-up evidence on 2026-10-26: EXTRACTOR_CONNECTION.md, BOT_LIFECYCLE.md and
STAGING_REHEARSAL.md. The corresponding rows below reflect those local results;
all real provider and selected-host acceptance remains pending.

| Feature | Automatic local evidence | Manual browser evidence | Launch gap / acceptance |
| --- | --- | --- | --- |
| Shots empty/populated feed and navigation | Product seed/routes; retained HTTP | Empty feed and populated original red-frame feed viewed | Real extracted media expiry/refresh still needs controlled provider test |
| Shot likes/unlikes | Product + retained native HTTP | Controls inspected; mutation is automatically tested | Device/accessibility matrix remains limited |
| Shot favourites/profile persistence | Product create/read/toggle | Saved an original fictional shot and verified profile collection | Wider device matrix pending |
| Shot comments/view history | Product write/read, invalid target/length checks; native HTTP | Feed controls inspected | Comment moderation/abuse policy and sustained write load remain staging work |
| Shot share controls | Source/route inspection | Controls inspected; no external share submitted | Native-share/copy fallback is not externally verified |
| Movie catalogue/pagination/genres | Product + retained native routes | Catalogue/cards and clean movie detail visited | Fresh real catalogue ingestion required |
| Series/seasons/episodes | Product related-data APIs and CRUD | Administrator/public form/navigation inspection | Actual episode handoff must pass controlled WhatsApp test |
| Collections and linked movies | Product API/CRUD/collection-shot regression | Navigation inspected | Mixed real catalogue sample still needed in staging |
| Global live search | Product APIs, 5 DOM-fixture tests, malformed-input checks | Search from clean movie URL and followed result | Browser engine matrix limited |
| Full-content watch CTA | Product WhatsApp-only route assertions | Earlier broken legacy downloader identified | Real handoff/delivery is a release blocker until approved tests pass |
| Website full-movie/episode streaming | Removed entry points; legacy media fields retained optional | Owner explicitly excluded | Outside first-launch scope; MEDIA_MODE remains disabled |
| Sign-up / verification / resend | Retained fake SMTP integration, expiry/reuse cases | Sign-up UI inspected in earlier recovery; no new real account created | Real mailbox/provider test needs approval |
| Password sign-in / remember-me | Retained HTTP, shared password admission unit/process races | Member UI and admin password login exercised | Production proxy/rate configuration must be verified |
| Password reset / expired/reset replay | Retained fake SMTP/native HTTP | No password changed through browser automation | Real-mail delivery requires approval; no credential rotation performed |
| Google OAuth sign-in/linking | Retained state/PKCE/identity/failure tests | Fictional provider member sign-in completed | Real app consent/callback/account test needs approval |
| Member/admin session access and logout | Product/native HTTP; nonexistent-admin session regression | Guest profile, signed-in profile and administrator dashboard | Production HTTPS/session revocation/timeout rehearsal required |
| Member profile saved shots | Product read/toggle | Saved collection observed after navigation | No claim of profile features absent from source |
| Administrator dashboard/analytics | Product authenticated routes and guest denial; native analytics events | Dashboard viewed | Metric integrity/retention/alert thresholds need staging verification |
| Administrator movies | Product HTTP create/edit/delete + upload negatives | Created and edited a fictional movie through actual form | No manual deletion was performed |
| Administrator series/episodes | Product HTTP create/edit/delete; original image validation | Form/navigation inspection | Destructive actions tested automatically on exact created fixtures |
| Administrator collections | Product create/edit/delete and retained shared media | Form/navigation inspection | Files remain retained for reviewed cleanup |
| Administrator shots | Product create/edit/delete, exact URL validation, collection enum fix | Form/navigation inspection | Live extraction and actual source-video suitability remain blocked |
| Administrator trailers | Product original MP4 create/edit/delete, inactive visibility/preview regression | Form/navigation inspection | Real browser/codec matrix remains unverified |
| Administrator settings | Product fictional key CRUD; bounded token/rate configuration tests | Form/navigation inspection | Bootstrap defaults separated from request-time DDL |
| Administrator bot tracking | Product authentication/rendering boundary | Counts/status view inspection; no token values exported | Real heartbeat, reconnect and operational alerts require controlled test |
| Administrator responsive layout | Source repair; HTTP regression | Desktop create/edit and mobile overflow reproduction; post-fix content width 375/375 and static sidebar verified | Not a full WCAG assessment |
| Trailer list/detail/player feed | Product APIs; 10 Node UI fixtures; native HTTP | Shared link selected original motion clip; playback progressed and spinner cleared | Safari/iOS/real-source codec matrix pending |
| Trailer like/favourite/view/share | Product actions; inactive interaction denial; failed-action/guest fixtures | Member Save changed to Saved; no external share performed | Native share and sustained write load not certified |
| WhatsApp token issuance and assignment | Actual PHP-issued fictional token -> Node/SQL fake-delivery integration | No wa.me link followed | No real phone/account/group contacted |
| Claims/one owner/expiry/replay/completion | Retained 32-worker claim races; 10 Node/SQL/fake delivery cases and protocol tests; lost ACK stops after one attempt | Backend protocol has no independent UI | External acknowledgement ambiguity stays held; no exactly-once claim |
| Bot cached/group/local-disk forwarding tiers | Retained fake-client component tests | No live client/UI launched | Live group lookup/media transfer still requires approval |
| Bot commands/administrator identity | Exact identity tests, guarded entry point, static command review | No WhatsApp commands sent | Full Baileys event/command surface not yet live verified |
| Bot incoming/upload queues | Bounded queue/dedup/drain and persisted maintenance/held recovery tests | Backend component | Real SDK and sustained ingestion/overload remain staging requirements |
| Bot reconnect/health/session handling | Serialized reconnect, worker lease, stale-event/heartbeat, fake entrypoint/shutdown tests | No real QR/session access | Actual SDK/account/pairing/supervisor compatibility remains unverified |
| Bot cache warming/auto-refresh/night jobs | Explicit-time-zone/idempotent schedule, shared gate, restart/checkpoint/held tests in 102-case suite | Fake SDK only; no group upload | Actual schedules/groups/large media and reviewed held-maintenance handling pending |
| fb-video-api health/disabled/local modes | Private actual PHP/Node 33-case contract, 14 client/4 cURL faults, 6 service checks | Administrator fake health/preview and stopped/disabled states verified | Live promotion/provider extraction remains blocked |
| Facebook URL parsing/HTML extraction | Original HTML fixtures; deceptive hosts/unsafe media rejection | Shot form validation reviewed | Real provider HTML/login/deleted/expired cases need approval |
| Extractor HTTPS transport | Mock DNS/HTTPS, pinned-address/redirect/body/deadline tests | Backend component | No real DNS/provider extraction executed; outbound staging controls required |
| Extractor browser fallback/dependencies | Full declared npm audits; pinned upgrade/import test | No browser launched for extraction | Explicit compatible Chrome binary, sandbox/network isolation and full fallback tests remain blocked |
| Upload type/size/quotas/quarantine/restore | Retained integration/operations/quarantine races and recovery suite | Administrator form exercised; no private media imported | Quota/quarantine workflow is local-only; production volume policy needs validation |
| Held delivery reconciliation/audit | Retained operator tests | Prior direct CLI evidence; no real-delivery decision made | Trusted worker/evidence attestations remain a limitation |
| Payments/subscriptions | Disabled endpoint boundary checks | Owner selected free access | Excluded; handlers stay disabled and must not be advertised |
| Unsafe downloader/cron/migration/diagnostics | Disabled/denied boundary checks | Legacy download link replaced in public CTA | No old fetcher/cron enabled |
| Security headers/CSRF/secrets/error boundaries | Retained HTTP, URL/input/throttle tests, source review, Gitleaks | Browser auth/role states and search review | Deployment-specific TLS/proxy/CSP/secret rotation/pen test still required |
| Capacity/recovery/operations | Local TLS 5/25-client 400-request load, stopped-writer encrypted restore, all-row/file comparisons and unavailable alert passed | No selected-host browser run | Sustained/large-media/host capacity, off-host keys/backups/alerts and rollout rollback remain gates |

Evidence classifications: source indexing is **statically inspected** only. A
written test is **test-covered**, with execution/results recorded separately.
Executed HTTP/CLI/browser evidence is **runtime verified locally**. No provider
integration is **externally verified** in this phase. Any unlisted action, browser,
codec, scheduler transition or failure combination is **not yet verified**.

The matrix deliberately does not equate 92 responding entry points with 92 working
features or label every administrator action manually tested. Exact manual actions
and screenshots are recorded in PRODUCTION_MANUAL.md. See PRODUCTION_STAGING.md
and PRODUCTION_LIVE_TEST_PLAN.md for the remaining concrete acceptance gates.
