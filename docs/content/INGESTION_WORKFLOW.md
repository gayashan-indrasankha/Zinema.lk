# Administrative ingestion - ZL-15

**PARTIAL.** Implemented as an internal, CLI-only service boundary outside the web
document root. There is no production administrator adapter, HTTP endpoint, new
dashboard, upload field or browser integration. This follows the owner's fallback
instruction for unresolved RBAC. Existing admin routes remain unchanged.

## Implemented command sequence

1. A trusted Authorization adapter permits `content.create`. Create a validated
   synthetic metadata draft, optionally associated with a verified canonical item.
   Unassociated drafts may be stored but cannot be submitted/published. Existing
   association/type cannot be reassigned after rights assets exist.
2. `content.edit` registers an opaque holder identity, asset metadata and a grant
   for an exact holder/asset/use/channel/territory/time window. Registration alone
   never verifies a holder, asset or licence.
3. `rights.verify` independently verifies a holder and its private evidence
   reference; then verifies an entered grant. `assets.review` separately marks
   the asset available. These operations require trusted infrastructure authority,
   not client-supplied roles. Synthetic tests provide fictional adapters only.
4. `content.edit` submits the current draft. The Catalogue port must confirm its
   actual type/ID and parent relationship. Default lookup reports unverified.
5. A different `content.review` actor approves or rejects the submitted revision.
   Approval requires at least one independently verified nonrevoked grant, but
   does not itself make any asset publicly available.
6. `content.publish` publishes a precise asset/territory/web scope only from its
   approved version. Every selected asset/territory pair must pass current rights
   policy. Real public playback remains denied by the separate delivery gate.
7. `content.publish` may unpublish; `content.revoke` may revoke a holder or grant.
   Metadata/assets/rights editing withdraws prior approval and returns to draft.
   Editing metadata also clears asset availability, requiring asset review again.
8. `content.audit` reads bounded version-ordered audit pages. No evidence document,
   contact information or arbitrary notes are exposed in events.

## Validation and safe inputs

- Metadata: required supported type/title; bounded UTF-8 plain text; genres and
  language allowlists; integer nonnegative runtime up to 86400 seconds; 1888-2200
  release year; bounded tags and cast/crew roles. HTML/control characters denied.
  SQL punctuation in legitimate titles is stored using bindings, not interpolated.
- Canonical references: strict positive decimal ID strings up to 18 digits, with
  verified parent relationship for episodes. No schema/key-type claim or FK is
  inferred from that application contract. Unsupported Shot collection parents
  are deferred rather than guessed.
- Assets: role/content compatibility, MIME allowlist, declared SHA-256 shape,
  integer duration and opaque `asset:` / `provenance:` references. No filenames,
  remote URL, traversal path, document body or arbitrary file is accepted.
- Grants: existing aggregate-local asset/holder IDs, role-matching permission,
  supported channel/territories, strict UTC window and private evidence reference.
  Unknown fields, client approval flags, duplicate values/holders/assets/grants,
  unknown IDs and incompatible relationships are rejected.
- Limits: at most 8 holders, 16 assets, 64 grants, 12 tags and 30 credits per record;
  persisted aggregate JSON is capped at 60000 bytes. Read/audit page size is 1-100.

## Failure and concurrency

Every mutation supplies the expected version. Record update and audit INSERT form
one transaction. Stale writes return VERSION_CONFLICT; they do not overwrite an
approval/revocation or add an event. The caller must reload and obtain renewed
human intent; never automatically replay an approval/publication against changed
content. Duplicate canonical workflows are rejected by a unique database key.

Database/authorization errors return fixed codes, not SQL, credentials, private
evidence or arbitrary exception text. A failed audit write rolls back its record
update. Test-only persistence verifies the synthetic database marker/UUID and
refuses a migration or overly privileged identity for normal ingestion.

## Live administrative integration gate

ZL-19 must establish authenticated identities, per-record permissions, ownership,
session expiry, CSRF protection for every mutation, rate limiting and render-time
escaping. ZL-14 must supply complete canonical/administrator schemas and verified
lookup/write contracts. Until then the bootstrap refuses non-CLI use and default
authorization/lookup adapters deny access. Do not wire this service to the existing
session flag or add a public upload handler as a workaround.

ZL-42 owns byte validation, quarantine, authorized upload, provenance processing
and storage. SHA/MIME/availability fields here are declarations reviewed in tests,
not proof that media bytes were inspected. No media is fetched or distributed.
