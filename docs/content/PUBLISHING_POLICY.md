# Publication and availability policy - ZL-15

The implemented state machine governs synthetic rights-workflow records only.
It is not a production publication API or a secure wrapper around legacy URLs.

| Action | Required authority | Source state / checks | Result |
| --- | --- | --- | --- |
| create | content.create | Valid metadata; any association independently resolved | draft |
| edit metadata, add holder/asset/grant | content.edit | Valid fields/references; immutable content identity | draft, approval withdrawn |
| verify holder/grant | rights.verify | Independent reviewer; known entity, nonrevoked holder/grant | draft, explicit verification recorded |
| asset availability review | assets.review | Known asset; boolean availability | draft |
| submit | content.edit | draft, verified canonical identity/parent, asset exists | pending_review |
| approve | content.review | pending_review, different submitting/reviewing actor, verified grant, current version | approved |
| reject | content.review | pending_review, independent actor, bounded reason enum | rejected |
| publish | content.publish | approved exact version, canonical identity, current rights for every asset/territory, web only | published |
| unpublish | content.publish | approved or published | unpublished, publication cleared |
| revoke holder/grant | content.revoke | Known holder/grant | revoked, publication cleared |

There is no generic `setState` command. Rejected/unpublished/revoked records need
a valid edit returning to draft, resubmission and renewed independent approval.
Revoked entities remain revoked; a replacement grant is explicit and auditable.
Every change is versioned and logged with a trusted actor and safe subject ID.

## Four distinct decisions

1. **Internal draft:** may exist without an association or licence; authorized
   administration only. Does not create a legacy movie or make it discoverable.
2. **Workflow publication:** explicitly selected assets and territories have
   passed current source-supported rules and independent synthetic review.
3. **Eligible metadata:** a published metadata-role asset additionally needs its
   own active metadata_display grant. Bounded discovery returns only record ID,
   canonical ID/type, title/language and `playable:false`; no storage/evidence URL.
4. **Actual streaming:** always denied by this implementation until verified
   entitlement and protected delivery adapters exist. Rights eligibility alone
   cannot bypass this gate. Messaging/download distribution is also not exposed.

Eligibility checks are evaluated on every call using the trusted server clock and
fresh repository state. Future/expired/revoked grants deny immediately. A stale
publication flag, hidden Watch button, stored licence filename or administrator's
entered ownership claim is never sufficient. No scheduled job is needed to enforce
expiry; an expiry dashboard may be added later without becoming the access gate.

The caller must supply a trusted server-derived territory/channel context; no
client geography or self-asserted entitlement adapter exists here. No decision is
cached into a permanent public media URL. Existing direct MP4/image/trailer URLs
are unchanged and can bypass this new, disconnected boundary. ZL-44/45 must secure
origin, manifests/segments and access renewal before claiming end-to-end protection.

## Human and production approval boundaries

The module records a human-review action through an injected trusted authorization
port; it cannot establish legal ownership itself. All current actors and grants
are synthetic. ZL-52/55 must provide real review procedures, evidence access,
moderation/takedown ownership and legally approved rights scopes. ZL-19 supplies
verified RBAC/CSRF; ZL-16 supplies entitlements/catalogue integration. These are
PROPOSED DEPENDENCIES, not verified Jira relationships or completed work.

No permission setting, feature flag or client command in this module enables
actual public playback. Do not change canStream() into a permissive flag to make
a demonstration look complete.
