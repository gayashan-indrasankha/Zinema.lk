# Production gap analysis and Jira dependency map — ZL-11

Date: 2026-10-23. **Planning complete; production readiness NOT ESTABLISHED.**
All CURRENT claims below are static evidence, not runtime success. Proposed target
controls, workloads and release gates are unimplemented. Existing extracted source,
HEAD and uncommitted content are distinct; no ZIP restoration is involved.

Read the [51-group exact-path inventory](MODULE_INVENTORY.md),
[current routes/data flows](CURRENT_ARCHITECTURE.md), [target](TARGET_ARCHITECTURE.md),
[streaming](STREAMING_ARCHITECTURE.md), [AI/voice](AI_ARCHITECTURE.md),
[delivery](WHATSAPP_DELIVERY_ARCHITECTURE.md), [operations](PRODUCTION_DEPLOYMENT.md),
[migration](MIGRATION_PLAN.md) and [source defect register](TECHNICAL_DEBT.md).

## Evidence and readiness decision

**Not ready for public production.** This is an evidence-based release hold, not a
percentage score. Blocking areas include unreconciled schema/adapters; auth/admin
and upload safety; absent verified licensed rights/publication; no authorized HLS/
private-CDN path; unproven staging, monitoring, backup restore and release gates.
Messaging is conditional and AI/voice are optional planned capabilities.

Source-confirmed means an inspected code path exists, not that it executes correctly.
Owner-reported previously working journeys remain historical claims (see
[contribution provenance](../CONTRIBUTIONS.md)); current guards intentionally disable
some paths. Runtime verified: **none in this architecture task**. Prior syntax/
packaging results keep their narrower scope. Vendor/service/legal assertions not
independently checked are labelled rather than treated as facts.

## Twenty-four production capability gaps

Each row identifies current evidence, target, gap, severity, prerequisites, concrete
future action and existing Jira owners. W/B/D/G identifiers resolve to exact paths
and evidence in the inventory; TD identifiers resolve to the debt register.
Severity is architectural release risk, not a measured incident/exploit score.
All dependencies in this document are **PROPOSED DEPENDENCY**, not verified Jira
dependency links. A mapped issue is not asserted complete, configured or in progress.

| ID / category | Current state: static evidence | Target state | Gap | Severity | Dependencies | Recommended action | Existing Jira story / epic |
| --- | --- | --- | --- | --- | --- | --- | --- |
| G01 Website architecture | W01/W02/W06–W08 mixed handlers, globals and relative URLs; TD01/11/13. | Incremental PHP boundaries with stable wrappers and explicit dependencies. | Route/include/CWD behavior and DB adapter consistency unproved. | High | ZL-13 test environment; route inventory | Refactor one covered slice; preserve method/query/cookie/asset contracts. | ZL-12; ZL-13; ZL-31 |
| G02 Frontend UX and accessibility | W07 native controls/localStorage, W08 inline feed and W13 navigation; no executed a11y/device audit. | Keyboard/mobile/localized player, captions, accessible status and reduced motion. | No demonstrated responsive/error/assistive-technology parity. | High | Stable routes; licensed fixtures; player contract | Measure existing UX; test and improve focus/controls/text fallback per journey. | ZL-27; ZL-45; ZL-34 |
| G03 Authentication and authorization | W02 long session lifetime; W04 parallel auth; W15 login calls/GET delete; TD06/07. | One secure lifecycle, server-side ownership/RBAC, admin MFA and auditable sensitive actions. | Cookie/CSRF/session/role enforcement not consistently verified. | Critical | Canonical users schema; fake mail/identity; auth regression suite | Unify policy incrementally; test denied access and reset/verification paths. | ZL-19; ZL-51 |
| G04 Database integrity and migrations | W02 PDO/MySQLi mismatch; D01/D02 incompatible token SQL; W18 runtime DDL; TD01–03/10. | Canonical versioned schema, typed IDs, constraints/indexes, transactions and explicit adapters. | Installed schema/data completeness unverified; legacy scripts cannot all be applied. | Critical | Structural reconciliation; fictional schema fixtures; backups | Define canonical contracts then additive reviewed migrations; EXPLAIN with synthetic data. | ZL-14; ZL-47; ZL-50 |
| G05 Movie and series catalogue | W06/W07/W10 listing/detail/search; collections and episode references exist. | Published licensed catalogue, stable IDs, pagination/search and entitlement-filtered DTOs. | Full canonical schema/indexes and rights filtering not established. | Critical | ZL-14 schema; ZL-15 rights metadata | Reconcile catalogue/season/episode relationships and enforce eligibility in API. | ZL-15; ZL-16 |
| G06 Shots functionality | W08 social queries and linked IDs; W16 requires share URL/extractor branch. | Legal self-managed previews, likes/comments/favorites/history and authorized title links. | Extraction conditional, linked eligibility and social/runtime contracts unverified. | High | Canonical social data; licensed previews; rights; player | Restore Shots-to-detail/HLS journey using owned permitted assets; isolate extraction. | ZL-17; ZL-18; ZL-45; ZL-25 |
| G07 User profiles and viewing history | W09 saved Shots and W07 device-local progress; no verified complete multi-device history. | Consented user-scoped history/watchlists, resume, account export/delete and privacy controls. | Device resume is not server history; ownership/retention/merge semantics unverified. | High | ZL-14/19 account/data contracts; privacy policy | Specify and test history/favorite ownership, consent and account controls within existing website stories. | ZL-19; ZL-17; ZL-23; ZL-53 |
| G08 Administration and moderation | W14–W17 CRUD/uploads, trailer status update and analytics source exist. | RBAC/MFA, validation/review/publication and moderation/takedown audit. | No established licensed publish gate; destructive GET and unsafe upload/shell boundaries. | Critical | Auth roles; canonical asset/rights model | Separate draft/edit/review/publish authority; gate mutations and moderate safely. | ZL-19; ZL-52; ZL-55 |
| G09 Media ingestion | W15 trailer uploads under public tree and unquoted ffmpeg command; W16 share URLs. | Authorized quarantine, type/size/provenance validation and idempotent jobs. | No established bounded secure ingest pipeline; unsafe shell construction. | Critical | ZL-19 roles; ZL-15/52 rights; ZL-46 storage plan | Replace shell interpolation in a separately authorized repair; implement isolated ingest. | ZL-42; ZL-51 |
| G10 Video transcoding | admin/trailers.php:24–31 attempts one thumbnail if exec exists. | Durable resource-bounded adaptive encode jobs and validated versions. | Thumbnail extraction is not HLS encoding, ladder or recovery. | High | Authorized ingest; queue; cost decision | Pilot licensed sample ladder, job reconciliation/retries and output quality checks. | ZL-41; ZL-43 |
| G11 HLS playback | W07 movie-details.php:582–619 inserts direct MP4; no Hls/m3u8 integration matches. | Authorized ABR player with tracks, seeking, accessible states and renewal. | No verified HLS packaging/player/browser test path. | High | ZL-43 renditions; ZL-44 access; detail compatibility | Adapt existing players via descriptor API; preserve routes/resume then test legal clips. | ZL-45; ZL-17 |
| G12 CDN and media security | W07 direct media URL; messaging/extractor caches are not a streaming CDN. | Private origin and signed manifest/segment/track access plus rights-aware renewal. | No origin isolation, edge authorization or takedown behavior established. | Critical | ZL-41/46 vendor choice; entitlement/rights model | Implement scoped short-lived access and verify origin/segment denial. | ZL-44; ZL-48; ZL-55 |
| G13 Content rights and entitlement controls | No targeted rights-field matches; W19 request tokens do not establish licensed eligibility. | Verified grants, territory/window, publication and server entitlement gates. | No inspected evidence grants distribution rights to existing private media. | Critical | Owner licensing evidence; canonical asset identity | Create rights records/review, deny by default and test expiry/revocation across discovery/playback. | ZL-15; ZL-16; ZL-52; ZL-55 |
| G14 WhatsApp integration feasibility | B01 Baileys; W19/W20 bridge, tokens, cache/local fallback; TD04/05/18/28. | Approved provider-neutral optional delivery with opt-in and correct claim/ack. | Official scenario/limits/approval unknown; status acceptance and atomicity defects. | Critical | Provider gate and messaging rights; canonical tokens | Keep live off; verify ZL-28/54 jointly; fake adapter and strict redemption before any approval. | ZL-28; ZL-54; ZL-29; ZL-30; ZL-18 |
| G15 AI recommendations | W08 random/unwatched feed; future settings only, no verified AI. | Authorized retrieval, deterministic ranking, constrained explanations and optional consented feedback. | No model orchestration, evaluation, budgets or grounded ID validation. | High | Canonical licensed catalogue; stable API; privacy | Define eval first; implement constrained recommender and observable provider boundary. | ZL-20; ZL-21; ZL-22; ZL-23; ZL-25 |
| G16 Voice interaction | No implemented STT/TTS/permission state machine established. | Explicit mic permission, text/transcript fallback, stop/mute and accessible animation. | No verified consent/audio retention/provider/device/a11y behavior. | Medium | Text companion; privacy review; a11y requirements | Implement launcher then optional STT/TTS adapters with explicit controls and quotas. | ZL-24; ZL-26; ZL-27 |
| G17 Hosting and networking | Apache/PHP/Node runtime assumptions in W01/W02/B12; deployed target not inspected. | Separate dev/staging/prod, private DB/origin, HTTPS/DNS and least privilege. | No verified host, network exposure, TLS renewal or environment isolation. | Critical | Approved budget/region; pinned runtime and architecture | Choose minimal host topology; create reviewed IaC/network/DNS plan before provision. | ZL-46; ZL-48; ZL-13 |
| G18 CI/CD and environment management | Bot lock exists; extractor lock absent; orphan website lock; no tracked workflow established. | Pinned reproducible builds, immutable artifact, versioned migrations, staging gates and approval. | No proven reproducible environment/release pipeline or migration rollback. | High | Canonical schema; test suite; scoped CI identity | Build environments and security/regression gates, then promotion and rollback jobs. | ZL-13; ZL-31; ZL-47; ZL-36 |
| G19 Monitoring and observability | W17 analytics and B09 heartbeat; source logs may contain sensitive data; TD09. | Redacted app/job/player metrics, SLO dashboards and actionable tested alerts. | OS heartbeat is not end-user health; no monitored deployment/SLO evidence. | High | Runtime topology; privacy/event schema; named operator | Instrument request-to-playback/jobs and validate alert delivery using synthetic events. | ZL-49; ZL-30; ZL-35 |
| G20 Backup and disaster recovery | SQL references/recovery records exist; private dumps excluded; no restore executed. | Encrypted isolated DB/object backups, key recovery, measured RPO/RTO and restore drills. | Presence of historical files proves neither valid backups nor recovery. | Critical | Canonical schema/object versions; retention/keys; host choice | Approve objectives and retention; automate and restore legal fixtures in isolation. | ZL-50; ZL-35; ZL-47 |
| G21 Application and infrastructure security | CSRF/rate primitives exist; TD04/06/07/09/12/16/28 show unresolved boundaries. | RBAC/MFA, CSRF/XSS/query/upload defenses, signed access, secret/dependency review and incident response. | Static/parser checks are not exploitation, vulnerability or infrastructure assurance. | Critical | Safe staging; legal fixtures; secrets/data boundary | Apply threat-model repairs separately; run staged security/abuse tests before release. | ZL-19; ZL-30; ZL-48; ZL-51; ZL-39 |
| G22 Performance and scalability | Query-heavy feed/admin, process-local queue and direct media URLs; no load results. | Measured query indexes/pagination, bounded workers/cache and CDN egress controls. | No throughput, concurrency, latency or cost capacity evidence. | High | Canonical DB; telemetry; HLS/CDN; approved workload | EXPLAIN/load on synthetic staging; tune from measurements; cap queue and spend. | ZL-16; ZL-43; ZL-44; ZL-49; ZL-51 |
| G23 Legal, licensing and privacy requirements | Source functionality and private media metadata; no contracts/policies/clearance inspected. | Verified rights incl. posters/clips/subtitles; privacy/terms/complaints/support and compliant publishing. | Jurisdiction, grants, retention and takedown ownership unverified. | Critical | Owner/legal review and product jurisdictions; asset inventory | Document rights/territories/expiry, complaints/removal and customer data controls. | ZL-33; ZL-52; ZL-53; ZL-54; ZL-55 |
| G24 Testing and production readiness | 101 PHP +36 JS parser passes; 32 export tests historical, not application tests; TD20. | Unit/schema/contracts/route/browser/security/load/restore/rights gates and monitored release. | No executed app/runtime/production evidence; source syntax cannot authorize launch. | Critical | Reproducible staging; fictional/licensed fixtures; implemented controls | Build regression suite, monitored alpha/beta, security review and canary/rollback evidence. | ZL-31; ZL-32; ZL-36; ZL-51 |

## Supplied Jira planning reference and provenance

The owner supplied the following **12 epics and 43 stories** after reporting
verification through their connected Atlassian account on 2026-10-23. This list is
the planning reference; it was not independently retrieved here. The backlog URL
could not be read through web tools, and both browser/desktop runtimes failed to
initialize with a missing-path error. No Jira issue or dependency was modified;
no further browser access is required.

Story titles supersede earlier generic issue-TBD placeholders where a clear
relationship exists. No new keys or duplicate issue proposals are created.
ZL-9's historical title refers to a ZIP; its authoritative source policy remains
the current extracted working tree and existing recovery records.

| Epic | Existing story | Supplied title |
| --- | --- | --- |
| ZL-1 — Secure repository baseline and supply-chain protection | ZL-9 | Audit original ZIP, preserve immutable baseline and provenance |
| ZL-1 — Secure repository baseline and supply-chain protection | ZL-10 | Sanitize sensitive files and define safe public export |
| ZL-2 — Maintainable architecture and reproducible environments | ZL-11 | Map modules and plan production architecture |
| ZL-2 — Maintainable architecture and reproducible environments | ZL-12 | Refactor modules incrementally without route regressions |
| ZL-2 — Maintainable architecture and reproducible environments | ZL-13 | Build reproducible local staging and CI environments |
| ZL-3 — Canonical data model and managed content catalogue | ZL-14 | Reconcile canonical SQL schema and reliable database access |
| ZL-3 — Canonical data model and managed content catalogue | ZL-15 | Build licensed catalogue ingestion and content rights metadata |
| ZL-3 — Canonical data model and managed content catalogue | ZL-16 | Production catalogue API search and entitlement enforcement |
| ZL-4 — Production website, accounts, catalogue and viewing UX | ZL-17 | Restore Shots-to-movie production HLS playback |
| ZL-4 — Production website, accounts, catalogue and viewing UX | ZL-18 | Quarantine unapproved payment, extraction and unofficial delivery |
| ZL-4 — Production website, accounts, catalogue and viewing UX | ZL-19 | Harden production authentication and administration |
| ZL-5 — Production-safe AI recommendations and discovery | ZL-20 | Define catalogue-grounded AI evaluation and performance goals |
| ZL-5 — Production-safe AI recommendations and discovery | ZL-21 | Build permission- and catalogue-constrained recommender |
| ZL-5 — Production-safe AI recommendations and discovery | ZL-22 | Build secure observable LLM conversation orchestration |
| ZL-5 — Production-safe AI recommendations and discovery | ZL-23 | Build opt-in personalization and session feedback |
| ZL-6 — Voice companion and accessible user experience | ZL-24 | Accessible, permission-based AI assistant launcher |
| ZL-6 — Voice companion and accessible user experience | ZL-25 | Production conversational movie cards and Shots linkage |
| ZL-6 — Voice companion and accessible user experience | ZL-26 | Opt-in production voice assistant with graceful fallback |
| ZL-6 — Voice companion and accessible user experience | ZL-27 | Mobile accessibility, localization and product UX |
| ZL-7 — Compliant delivery adapters, requests and reliability | ZL-28 | WhatsApp delivery compliance and feasibility gate |
| ZL-7 — Compliant delivery adapters, requests and reliability | ZL-29 | Implement provider-neutral streaming and optional approved messaging |
| ZL-7 — Compliant delivery adapters, requests and reliability | ZL-30 | Harden token redemption, authorization and delivery observability |
| ZL-8 — Production QA, legal readiness, launch and ongoing operations | ZL-31 | Production CI, automated regression and security gates |
| ZL-8 — Production QA, legal readiness, launch and ongoing operations | ZL-32 | Run monitored alpha/beta usability and reliability testing |
| ZL-8 — Production QA, legal readiness, launch and ongoing operations | ZL-33 | Clear distribution rights, privacy and public release obligations |
| ZL-8 — Production QA, legal readiness, launch and ongoing operations | ZL-34 | Create public launch UX, onboarding and help content |
| ZL-8 — Production QA, legal readiness, launch and ongoing operations | ZL-35 | Production operations, release runbooks and support readiness |
| ZL-8 — Production QA, legal readiness, launch and ongoing operations | ZL-36 | Production release gates, canary deployment and rollback |
| ZL-37 — Video ingestion, encoding, storage and CDN | ZL-41 | Choose legal, scalable video delivery reference architecture |
| ZL-37 — Video ingestion, encoding, storage and CDN | ZL-42 | Build authorized upload ingest, asset validation and provenance pipeline |
| ZL-37 — Video ingestion, encoding, storage and CDN | ZL-43 | Implement adaptive HLS transcoding, storage and lifecycle |
| ZL-37 — Video ingestion, encoding, storage and CDN | ZL-44 | Secure CDN and access control for actual streaming |
| ZL-37 — Video ingestion, encoding, storage and CDN | ZL-45 | Integrate production HLS player with Shots and movie pages |
| ZL-38 — Cloud deployment, infrastructure and DevOps | ZL-46 | Select production hosting and establish environments, IaC and costs |
| ZL-38 — Cloud deployment, infrastructure and DevOps | ZL-47 | Build production CI/CD, migrations, rollback and releases |
| ZL-38 — Cloud deployment, infrastructure and DevOps | ZL-48 | Configure domain, DNS, HTTPS and environment security |
| ZL-39 — Observability, security, backup and resilience | ZL-49 | Build application observability, alerting and SLO dashboards |
| ZL-39 — Observability, security, backup and resilience | ZL-50 | Automate encrypted database/object backups and restore drills |
| ZL-39 — Observability, security, backup and resilience | ZL-51 | Perform production security review, DAST, abuse and load tests |
| ZL-40 — Content operations, policy compliance and customer trust | ZL-52 | Build licensing and publication compliance workflow |
| ZL-40 — Content operations, policy compliance and customer trust | ZL-53 | Publish privacy, terms, copyright complaints and support process |
| ZL-40 — Content operations, policy compliance and customer trust | ZL-54 | Perform WhatsApp official-provider feasibility and approval gate |
| ZL-40 — Content operations, policy compliance and customer trust | ZL-55 | Implement safe entitlement, territory and content-moderation administration |

## Cross-epic critical path — PROPOSED DEPENDENCY

1. **ZL-9/10 → ZL-11 → ZL-13 and ZL-31 baseline → bounded ZL-12 slices.**
   Preserve existing source and four export holds; source/route tests are required
   before moving affected behavior. Architecture documentation does not close the
   environment/test prerequisites. A later refactor does not repair defects by itself.
2. **ZL-14 → ZL-15/16 and ZL-19**, with **ZL-52/55** defining rights/publication/
   roles early. Canonical users, content, assets, tokens and repository adapters
   support account security and rights-aware APIs. ZL-18 keeps unapproved adapters
   disabled. Do not wait until public rollout to harden admin uploads.
3. **ZL-41 and ZL-46** decide vendor-neutral media/hosting feasibility, budget and
   recovery objectives. **ZL-42 → ZL-43 → ZL-44 → ZL-45 → ZL-17** establishes legal
   ingest, HLS and Shots-to-playback. ZL-15/19/52/55 are prerequisites to publication
   and public ingest, even if a synthetic pipeline prototype proceeds in parallel.
4. **ZL-48/49/50** build network security, telemetry and restore capability alongside
   the media path. **ZL-47** uses ZL-13/14/31 and these controls for reviewed releases.
   Ops must exist before external alpha traffic, not be deferred after launch.
5. **ZL-33/53** resolve legal/privacy/customer policies; **ZL-51** executes security,
   abuse/load and fixes; **ZL-32** runs a scoped monitored alpha/beta after safety
   gates; **ZL-34/35** establish help/support/runbooks; **ZL-36** performs owner-approved
   canary, rollback and release. No task title is evidence these gates passed.

The first-launch critical path includes canonical data, secure accounts/admin,
licensed publication, authorized HLS/CDN, network/ops/restore, regression/security/
load/legal gates and controlled release. Optional features cannot silently become
core availability dependencies.

Parallel optional tracks after suitable prerequisites:
- **AI:** ZL-20 → ZL-21 → ZL-22 → ZL-23, with ZL-16/52/55 authorization and ZL-49
  telemetry. ZL-25 uses validated result IDs and authorized Shots.
- **Voice/UX:** ZL-24/27 → ZL-26 on a safe text companion; ZL-25 need not require
  microphone/STT/TTS. Privacy and explicit controls precede audio collection.
- **Messaging:** ZL-28 and ZL-54 are complementary technical/provider and approval
  gates; coordinate evidence instead of creating a duplicate issue. ZL-18 quarantine
  remains; ZL-29/30 can develop fake contracts after ZL-14. Live activation requires
  both gates, ZL-52 rights/consent, ZL-51 review and explicit owner authorization.

## Decision and acceptance ledger

| Requirement | ZL-11 disposition |
| --- | --- |
| Existing source/module/dependency/route/DDL evidence | Complete static map; 265 tracked paths, 51 groups plus excluded metadata boundaries |
| Production gaps, streaming, AI/voice, delivery, operations/security/rights | Documented proposals; no runtime capability claimed |
| Reversible restructuring and release order | Documented with retained M01–M21 mappings and future gate/rollback additions |
| Existing Jira map | Complete against owner-supplied 12-epic/43-story reference; dependencies inferred, not Jira link verification |
| Vendor prices/capabilities and official messaging feasibility | REQUIRES CURRENT VENDOR VERIFICATION; no vendor research/approval claim |
| Application/source and uncommitted preservation | Verified at final handoff by start-snapshot comparisons; contribution prefix retained |
| Production runtime/legal readiness | NOT VERIFIED; future acceptance, not a ZL-11 pass claim |

Open decisions are owner budget/region, workload/SLO/RPO/RTO approval, licensing and
operating jurisdictions, exact canonical schema after reconciliation, official
messaging suitability, provider selection and optional feature launch scope.
These do not prevent completion of a planning task when clearly recorded.
No production service, database, cloud, model or account operation was performed.
