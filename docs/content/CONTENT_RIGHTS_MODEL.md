# Rights model - ZL-15

Status: **PARTIAL**, isolated synthetic foundation. No real licence, licensor,
agreement or administrative identity has been verified by this work. Test approval
records are fictional workflow evidence, not commercial distribution permission.

## Logical model and storage

`apps/web/src/Content/Validation.php` validates draft metadata and individual
holder/asset/grant entities. `CatalogueService.php` owns state changes; `Policy.php`
owns reusable eligibility. `PdoRepository.php` stores one bounded, versioned
aggregate and one audit event in a transaction. See [architecture](CATALOGUE_ARCHITECTURE.md).

| Entity | Implemented fields and relationships | Limit |
| --- | --- | --- |
| Workflow record | Random 32-hex ID, version, synthetic data class, state, creator/submission/review, publication scope | Not a canonical movie/series row; one optional existing type/ID reference |
| Metadata draft | Type, title, synopsis, genres, language, runtime, tags, release year, guidance, cast/crew credit roles/names, canonical parent | No claim that draft metadata has been written to the legacy catalogue |
| Holder | Aggregate-local ID, opaque holder reference, entered-by, verified-by, private verification reference, revocation | Identity/contract review must be performed by an authorized human; no contacts stored |
| Asset | Aggregate-local ID, exact role, MIME declaration, duration, SHA-256 declaration, opaque asset/provenance references, availability | Metadata registration only; no upload, byte inspection, transcode, file serving or URL resolution |
| Grant | Exact asset/holder IDs, permission/channel, territories, UTC start/end, private evidence reference, entered-by, verified-by, revocation | No automatic parent-to-episode or full-film-to-preview/artwork inheritance |
| Publication | Specific asset IDs, territories, web channel, approved version | Synthetic workflow state only; not external media publication |
| Audit | ID, record ID/version, actor, action, subject ID, resulting state, UTC event time | No contract text, contact data, filenames, tokens or free-form review notes |

Logical entities have separate identities and references inside the aggregate.
Interim persistence uses bounded TEXT JSON, not a fully normalized relational
rights system. Shared holder normalization, FKs, SQL status CHECK constraints,
global rights queries and production retention require a reviewed canonical
schema. The two new tables do not replace movies, series, episodes, shots or users.

## Permission scope

| Asset role | Required permission |
| --- | --- |
| metadata | metadata_display |
| poster | display |
| film, episode | stream |
| shot, trailer | preview |
| subtitle | subtitle |

Each grant applies to exactly one asset. A poster/trailer/Shot/subtitle/metadata
grant cannot authorize a different full film. Series metadata does not authorize
episode media. Different episodes may have independent grants and expiry dates.
Web, messaging and download channels are distinct. Non-web grant metadata can be
represented, but workflow publication currently accepts web only and no delivery
adapter is implemented for any channel.

Supported territories are the explicit product subset LK, IN, GB, US, CA, AU;
unknown codes and wildcard/global grants are rejected. Supported languages are
si, ta, en, hi, ko, ja, fr, es. These are reviewed input allowlists, not a claim to
implement every country/language or infer a visitor's territory. A trusted
territory-resolution adapter remains required; client geography is not proof.

All effective times use strict `YYYY-MM-DDTHH:mm:ssZ` UTC. Start is inclusive, end
exclusive; end must follow start. A trusted server Clock supplies current time.
There is no client `now`, verification status or actor field accepted by commands.

## Human review and immediate denial

Entered holder and grant claims start unverified. Separate `rights.verify`
authority records independent holder and grant verification. Reviewers cannot
verify their own entered claims; publication review cannot approve its own
submission. The supplied infrastructure adapter must establish those permissions
from a verified identity. Default authorization denies every action.

Evidence references match `evidence:<32 lowercase hex>` and have no public URL or
document resolver. Metadata entry never proves ownership, and an opaque reference
does not itself prove contractual validity. Production legal/identity review,
review provenance, protected evidence storage and retention remain ZL-52/55 work.

Every eligibility call checks publication scope and the current record, asset
availability, independently reviewed holder/grant, revocation, role permission,
territory/channel and current UTC window. Missing/unverified/malformed/expired or
revoked rights deny the request. Expiry is derived at access time; no scheduler is
required. Stored `published` can remain after a grant expires, while eligibility
immediately becomes false. No misleading persisted EXPIRED flag is required.

Revocation records an audited new version and conservatively revokes the workflow
publication. A revoked grant cannot be reverified in place; a new grant and review
are required. No previously issued CDN URL can be invalidated by this foundation,
because it issues no URL. Existing direct media links remain outside enforcement.

`canStream()` always returns denied: ENTITLEMENT_AND_DELIVERY_UNAVAILABLE. Positive
rights eligibility is not a playback token, user entitlement or delivery approval.
