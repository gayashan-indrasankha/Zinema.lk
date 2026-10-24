# Rights and entitlements — ZL-16

Status: PARTIAL. Implemented policies are tested with fictional identities,
licences and access evidence; they are not real distribution approval.

Rights answer whether a verified asset may be used by the platform. The unchanged
ZL-15 Policy checks publication scope, asset availability, independently reviewed
holder/grant evidence, revocation, territory, web channel, permission and UTC
effective window (start inclusive, expiry exclusive). A canonical record must
independently exist. Entitlements answer whether this trusted requester may use
that specific asset and operation. Both are required; neither substitutes for
the other. Missing metadata eligibility also suppresses preview/playback eligibility.

ContextProvider resolves identity, account status, verified age, territory evidence
and an opaque quota key without receiving request parameters. Missing context,
unverified/unsupported territory or invalid trusted identity fails closed. An
inactive authenticated account receives 401 before repository access. Client
user_id, territory, channel, role and is_premium parameters are rejected; no header
is installed as a trusted identity/geolocation provider. No IP-geolocation accuracy
is claimed. Production authentication, trusted location and age verification
remain unimplemented integration gates.

AccessRules provides an explicit rule per canonical type/ID and operation:
`mode` public/authenticated/grant, plus integer minimum_age 0–120. Missing,
unknown or malformed rules deny. Anonymous users can receive public metadata
where both rights and the rule allow it. Authenticated mode still requires a
trusted active subject. Grant mode consults Grants using subject, type, canonical
ID, asset ID, operation, territory, web channel and current time. No client claim,
legacy subscription_expiry_date or WhatsApp token is accepted as access evidence.
MissingGrants always denies; missing subscription integration is not free access.

Grant persistence/revocation and validity are responsibilities of the trusted
adapter. Fictional tests bind grants to the actual subject/territory/asset and
expire them; no production grant table is invented. User A's evidence cannot
authorize user B. Age-restricted rules require verified age evidence. No watch
history, session token, user ID, licence evidence or internal denial detail is
serialized. Hidden and nonexistent IDs use the same 404 error contract (fresh
request correlation IDs differ). Constant-time existence hiding is not claimed.

Separate metadata, preview and playback decisions never mint a media locator.
The existing Content Policy::canStream remains unchanged and denied. ZL-16's
playbackEligible means only the tested decision prerequisites passed; actual
protected delivery is absent. See [availability](AVAILABILITY_CONTRACT.md).

Required before production: reconcile ZL-14 schema, wire approved ZL-15 licence
operations, verify ZL-19 identity/admin authorization, implement ZL-55 access rules
and auditable grants, trustworthy territory/age provenance and shared abuse
controls. These are PROPOSED DEPENDENCIES, not verified Jira dependency edges or
claims that the referenced issues are complete.
