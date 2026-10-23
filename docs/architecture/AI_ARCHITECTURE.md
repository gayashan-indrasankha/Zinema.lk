# AI and voice architecture — Jira ZL-11

Date: 2026-10-23. **PLANNED / NOT IMPLEMENTED / NOT RUNTIME VERIFIED.**
See [target modules](TARGET_ARCHITECTURE.md), [stream authorization](STREAMING_ARCHITECTURE.md),
[current evidence](CURRENT_ARCHITECTURE.md) and [production gaps](PRODUCTION_GAP_ANALYSIS.md).

## Existing foundation and deliberate separation

W06 catalogue/search, W08 Shots and W09 profile contain relevant catalogue IDs,
social records and views, not a verified AI recommendation engine. The current
index.php feed uses unwatched/random/fallback database selection; its extractor
branch resolves a preview URL, not natural-language preferences. Existing future
AI environment examples describe a proposal, not an installed model integration.
No AI API, microphone, STT/TTS provider, SDK or conversation store was activated.

Keep identity, catalogue repositories, rights and playback decisions in PHP/MySQL.
Initially a PHP Companion application boundary can call a provider through an
interface. A separate Node AI worker/service is justified only for streaming
responses, cancellable provider work or independent resource limits that cannot
be served simply in PHP. It receives projected DTOs, never database/session
credentials, unrestricted SQL, arbitrary network tools or object-store authority.
This avoids introducing a mandatory service just because Node exists.

## Grounded recommendation sequence

~~~mermaid
flowchart LR
  U[Text or explicit voice input] --> P[Validated preference extraction]
  P --> C[PHP authorized catalogue retrieval]
  C --> F[Deterministic filters and ranking]
  F --> L[Optional bounded explanation model]
  L --> V[Validate candidate IDs and explanation]
  V --> R[Cards and authorized Shots previews]
  R --> D[Existing detail URLs]
  D --> A[Fresh playback authorization]
  L -. failure .-> B[Deterministic text and results]
  F --> B
~~~

Preference extraction accepts bounded mood, genres, languages, duration, exclusions
and optional explicit preferences. Treat dialogue and catalogue descriptions as
untrusted input; neither can change policy or add tools. Validate structured model
output against an allowlist and size bounds. Ambiguity becomes a follow-up question,
not guessed entitlement or silently stored sensitive inference.

Backend retrieval uses parameterized queries and a documented catalogue schema,
with publication/territory/rights/entitlement filters applied before candidate
projection. Return canonical IDs, escaped display data, eligible preview IDs and
safe detail-route references. The model never receives raw media locations, signing
keys, payment data, account email or the whole user history. Do not send retained
watch history to a provider by default.

Rank deterministically using explicit preferences, genre/language/duration matches
and documented tie-breaks. Avoid making unverified quality/popularity scores appear
objective. Supply a bounded candidate set to an optional explanation model, then
reject IDs outside that set and unsupported claims about availability. If the model
invents a title or emits malformed output, retain the validated deterministic set.
Recheck eligibility before display when stale and **always** before playback.
A card is discovery information, never a playback grant.

Join Shots by the existing linked content type/ID after verifying the preview's
rights, publication and visibility. No eligible Shot means a licensed poster and
text card, not extractor fallback. Preserve existing movie/series detail routes
through compatibility wrappers; AI does not bypass ContentAccess.

## Proposed data contracts and interfaces

| Boundary | Input | Output / policy |
| --- | --- | --- |
| POST /api/v1/companion/recommendations | Bounded text, optional opaque conversation ID, locale, explicit filters, personalization consent | Request ID, validated title/episode IDs, reason codes/text, authorized preview references, safe detail routes; no permanent playable URL |
| CatalogueQuery interface inside PHP | Typed preferences, server identity/territory context, limit/cursor | Eligible catalogue DTOs only; provider cannot supply SQL or override identity |
| ExplanationProvider interface | Bounded preferences and candidate DTOs with request budget | Schema-constrained explanations tied to candidate IDs; validated or discarded |
| Conversation repository | Minimal opted-in preferences/turn summary and retention expiry | User-scoped records; deletion/export and access control; no raw audio default |
| POST /api/v1/companion/feedback | Validated result ID and explicit useful/not-useful preference | Idempotent feedback event with consent; not automatic training permission |
| POST /api/v1/voice/transcriptions | Explicit microphone consent, bounded permitted audio, locale | Transcript and confidence/error state; delete temporary audio after request policy |
| POST /api/v1/voice/speech | Validated safe answer text and voice preference | Bounded audio response and transcript; cancellable, no autoplay without user intent |

These routes and names are **proposals**, not present handlers. Cookie-authenticated
writes require CSRF protection; rate limits apply to anonymous and authenticated use.
Service-to-service calls need short-lived scoped authentication, timeouts, correlation
IDs and request size limits; never accept a browser assertion of user entitlement.

## Voice and accessible companion state machine

States: TEXT_READY → MIC_PERMISSION_REQUESTED → LISTENING → TRANSCRIBING →
REVIEW_TEXT → REQUESTING → ANSWER_READY → SPEAKING. DENIED, ERROR and CANCELLED
return to text without trapping focus. Only an explicit action requests microphone
permission. No page-load mic activation, hidden background listening or unexpected
audio playback. Browser permission approval remains the user's action.

Show listening state, elapsed time, stop/cancel and a reviewable transcript. Cap
recording duration, stop tracks immediately on cancel/navigation and handle permission
denial, device loss and interruptions. Text entry and the complete response remain
available without audio. Spoken output starts only on a selected voice/play action
or clearly opted-in session setting; stop/mute/volume are always reachable.
Do not make sound the only status indicator.

The avatar is optional presentation, not an input requirement. Provide semantic
buttons/labels, keyboard and screen-reader status, captions/transcripts, visible
focus, no flashing, reduced-motion support and small-screen layouts. Test
interrupting speech with typing and changing focus. Do not claim WCAG conformance
without a separately scoped audit and assistive-technology tests.

Use STT and TTS adapters independently of the recommendation provider, behind the
same server-side secret and quota policy. Browser-native versus remote processing,
supported languages/devices and provider data handling remain selection decisions:
**REQUIRES CURRENT VENDOR VERIFICATION.** No browser or provider capability was tested.

## Privacy, safety and failure recovery

Default to request-only dialogue and no retained raw audio. Obtain specific consent
before personalization/history use, document retention and deletion, and provide a
clear opt-out. Evaluate provider retention, training, region and subprocessors
before selection; do not assert regulatory compliance. Legal/privacy requirements
need owner review appropriate to actual users and operating jurisdictions.

Set maximum text/audio length, turns, candidates, output tokens, timeout, retries,
daily per-user/provider spend and global concurrency. A circuit breaker and a
budget switch return catalogue search/filter results and deterministic explanations.
Cancellation propagates to pending work where supported. Avoid retry storms and
charging duplicate requests; use request IDs and idempotency at the application
boundary. Optional AI/voice outages must not disable catalogue, Shots or streaming.

Logs contain latency, outcome category, token/cost counters and internal opaque
request references; no raw prompts, audio, full provider payload, keys or viewing
history by default. Diagnose using fictional fixtures and sanitized examples.
Provider text is escaped; no arbitrary HTML, tool execution, SQL or external URL
fetching. Version prompts and ranking rules with a reproducible evaluation set.

## Future acceptance and Jira

ZL-5 owns grounded recommendations, ZL-6 voice/accessibility, ZL-3 catalogue,
ZL-4 account/viewing, ZL-40 rights/privacy, ZL-39 security/quotas and ZL-8 evaluation.
Existing stories: ZL-20 evaluation, ZL-21 recommender, ZL-22 orchestration,
ZL-23 personalization, ZL-24 launcher, ZL-25 cards/Shot linkage, ZL-26 voice,
ZL-27 accessibility; see [the supplied Jira map](PRODUCTION_GAP_ANALYSIS.md).
Prerequisites: canonical catalogue IDs and eligibility queries, safe account context,
legal preview fixtures and regression routes. AI and voice are optional after the
core authorized viewing launch gate, unless owner explicitly changes release scope.

Future tests: invented IDs, prompt injection in user/catalogue text, SQL/tool
requests, expired rights, stale candidates, empty catalogue, provider timeout,
malformed output, quota exhaustion, duplicate/cancel requests, cross-account access,
consent revocation, microphone denial, stop/mute, text-only and reduced-motion,
screen-reader/mobile interactions and deletion. No such tests were executed here.
