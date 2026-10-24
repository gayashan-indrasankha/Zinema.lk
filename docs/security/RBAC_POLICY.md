# Authorization policy - ZL-19

Status: PARTIAL. Existing source has separate admins and users, session admin_id,
an alternate admin_logged_in helper and a users.is_admin comment-delete check.
Complete permitted role/identity DDL is absent. These are observed checks, not a
verified production role model. No role migration or real assignment is made.

The replacement Authorization service rereads the trusted Account adapter and
checks active, verified and matching epoch on every call. Unknown permissions,
missing identities, wrong owners and missing permissions deny. Role names, user
IDs and MFA flags in request bodies cannot grant authority. Principal exposes
only opaque subject and epoch, never a password hash.

| Permission | Required boundary | Current production binding |
| --- | --- | --- |
| account.read / account.change | Explicit trusted permission; subject equals owner | None |
| content.edit | Explicit permission plus verified session-bound step-up | None; denied |
| content.review | Separate permission plus step-up | None; denied |
| content.publish | Separate permission plus step-up; independent ZL-15 rights approval still required | None; denied |
| admin.enter / admin.users / admin.settings | Specific permission plus step-up | None; denied |
| admin.upload | Specific permission plus step-up, ownership and CSRF at upload controller | None; denied |
| audit.read | Specific permission plus step-up, trusted log reader | None; denied |

Synthetic registration grants only account.read and account.change. Permission
separation is explicit; no wildcard, super-admin default or hierarchy is invented.
MissingStepUp denies every privileged permission. This does not retrofit legacy
routes: their session-flag guards, GET deletes and missing CSRF remain release
blockers. Factory::production throws IDENTITY_NOT_CONFIGURED (503); there is no
new public privileged route or environment switch to enable the fixture.

Content/Catalogue/Playback adapters remain byte-preserved. Identity permissions
do not replace canonical content, territory, age, independent review, asset-rights
or requester entitlement checks. Their production identity wiring is unresolved.
See [route inventory](AUTH_ROUTE_INVENTORY.json) and [architecture](AUTHENTICATION_ARCHITECTURE.md).
