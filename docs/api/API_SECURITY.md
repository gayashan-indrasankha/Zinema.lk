# Catalogue API security — ZL-16

Implemented and tested locally; production integration remains PARTIAL. The
[test report](CATALOGUE_TEST_RESULTS.md) distinguishes executed evidence from gaps.

- The additive public factory is disconnected and fails closed. No environment
  switch, client flag or fallback dataset enables a public catalogue. Existing
  authentication/admin routes are not imported or trusted by this endpoint.
- Trusted context, access rules, canonical lookup and grants are distinct ports.
  Rights policy is reused unchanged. Forged user/territory/premium query keys fail
  validation. Unknown rights/rules/identity never grant access.
- GET only; other methods return 405/Allow GET. Scalar UTF-8, parameter/byte bounds,
  numeric bounds and fixed enums guard request parsing. No new write/upload/fetch,
  session, payment, AI, extractor or messaging action exists.
- Native PDO bound values and fixed SQL expressions prevent SQL input becoming
  syntax. A guarded local synthetic reader has SELECT only on rights projection
  and target marker; excess privileges are refused. No production connection
  adapter is provided. Private Database.sql is excluded from inspection/scanning.
- DTOs explicitly allow id, contentType, title, synopsis, genre, language,
  releaseYear, runtimeSeconds, tags, availability and optional public parent.
  String IDs avoid JavaScript integer loss. Complete rows/aggregates are never
  serialized. No holder/evidence/grant/storage/origin/account/session data.
- JSON hex-escapes HTML delimiter characters; Content-Type is JSON UTF-8 and
  nosniff is set. Consumers must still render strings as text, not innerHTML.
- Every response uses private, no-store and Vary Authorization/Cookie. No shared
  response/access cache is introduced. Fresh policy evaluation observes licence
  expiry/revocation. Cursor tokens are HMAC-signed, 300 seconds, contextual and
  contain only public last-returned tuples. They are not bearer access grants.
- Candidate budget 200, page max 50, response max 262,144 bytes, query max 4,096
  bytes and title query max 200 bytes bound work/output. SELECT time hint is one
  second, not proof that JSON scan cost scales. Exhaustion yields no partial data.
- FixedWindowLimiter is bounded per process; invalid requests with trusted context
  consume quota and identities cannot evict existing quotas. It is a test/single
  process implementation, reset on process restart. The actual disconnected
  factory does not establish trusted quota identity. Distributed/edge protection
  is a production blocker; legacy SQL rate-limit DDL side effects are not reused.
- Error codes/messages are an explicit allowlist. Unknown exceptions, including
  arbitrary ApiFailure reasons, become generic INTERNAL_ERROR. Hidden/missing IDs
  share 404. Random server-generated request IDs are returned in header/body.
  PHP display_errors is disabled by the physical entry. No custom persistent
  request/auth/query logging is introduced. Future telemetry may record safe code,
  status, duration and correlation ID, never session/token/rights evidence. Existing
  web-server access-log retention/redaction still needs deployment review; it is
  not certified by this endpoint.

Limits: no production auth/geolocation/age/grant adapter, WAF/distributed limiter,
production DAST/load test, release approval or complete legacy UI execution. Old
media paths are unchanged and are not secured by this separate controller. Secret
scanning excludes protected private stores/history/binaries and is not a guarantee
of exhaustive privacy/security. No production infrastructure or real users were used.
