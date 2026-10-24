# Playback authorization - ZL-17

Status PARTIAL. Public factories remain unconfigured; no production access is
issued. Local fixtures are fictional independently reviewed workflow records,
not legal approval for real titles. No protected/private SQL or credentials read.

| Route | Method / input | Result |
| --- | --- | --- |
| api/playback/link.php | GET shot_id | Fixed eligible movie/series detail link |
| api/playback/details.php | GET type,id | Allowlisted metadata plus current watchable/state |
| api/playback/session.php | POST JSON type,id | Ready descriptor and scoped HttpOnly cookie, or deny |
| api/playback/media.php | GET file, optional Range | Reauthorized allowlisted resource/range, or deny |

Session creation requires an explicitly configured trusted origin, exact Origin,
application/json and X-Zinema-Playback: 1. No arbitrary query overrides. The custom
header/JSON requirement plus exact same-origin check and SameSite cookie protect
against cross-site form/credential issuance; cross-site Fetch Metadata is denied.
No wildcard CORS or client provider/user/premium/licence flag is accepted.
Inputs and JSON body are bounded. Production composition must supply real trusted
identity/territory/age/rules/grants and distributed limits; current process-local
quotas are not multi-worker protection. Existing legacy rate-limit DDL is not reused.

Service requires canonical type/id and parent, eligible metadata, published scope,
current independently verified asset-specific streaming grant, territory/web channel,
active user/age/access entitlement and provider readiness. Local package digest must
match the selected reviewed asset SHA-256. Metadata discovery alone cannot stream.
ZL-15 Policy::canStream remains unchanged; this is a new isolated synthetic provider
composition using its rights predicates, not a bypass enabling real delivery.

The HMAC session cookie is HttpOnly, SameSite=Strict, Path=/api/playback/, max 60
seconds; Secure on HTTPS. Only the verified loopback development origin uses HTTP.
The signed claims bind trusted identity/territory/age context, content, asset,
record version, digest, issuance and expiry. Issuance caps lifetime by the relevant
licence end. Every resource request verifies the cookie and fresh policy/version,
including segments/captions/ranges; copied cross-user, expired, revoked or changed
asset sessions fail. No access token is placed in HTML, JSON, local storage, URLs
or telemetry. One playback cookie supports one active asset per browser cookie jar;
simultaneous different-title tabs can replace it and must reauthorize.

Local fixture access checks exact file/hash/extension/size and rewrites only
whitelisted manifest references through the same protected endpoint. There is no
static fixture route under the web root. Unknown/traversal resources deny.
Responses are private/no-store, nosniff, same-origin CORP and no-referrer; 206 ranges
are protected too. Missing/hidden content share 404; missing/expired session 401;
origin denial 403; bad input 400; method 405; invalid range 416; quota 429;
unconfigured/dependency 503; unexpected errors generic 500 with request ID.
No raw exceptions, origins, filesystem locations or licensing evidence are returned.

Production CDN is NOT CONFIGURED / NOT VERIFIED. For a future CloudFront design,
[signed cookies are documented for multi-file HLS access](https://docs.aws.amazon.com/AmazonCloudFront/latest/DeveloperGuide/private-content-choosing-signed-urls-cookies.html).
That is a design option only: no distribution, key, signature, origin restriction,
CORS/cache behavior or edge revocation has been configured/tested here. ZL-44 must
verify the complete resource namespace and private-origin denial; ZL-45 integrates
its approved descriptor. Local PHP serving is not that architecture or DRM.
