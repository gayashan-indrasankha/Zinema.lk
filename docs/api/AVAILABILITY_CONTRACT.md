# Availability contract — ZL-16

The controller returns current server decisions, never a media URL. Availability
is contextual and time-sensitive. Do not infer access from a catalogue row alone.

| Field | Implemented meaning |
| --- | --- |
| discoverable | Current metadata asset rights, canonical existence and metadata entitlement all pass |
| metadataAvailable | Same present metadata-display boundary; no poster/media URL is included |
| previewEligible | Eligible shot/trailer asset rights and preview entitlement; requires metadata eligibility |
| previewAvailable | Always false: no protected preview delivery adapter exists |
| playbackEligible | Eligible film/episode asset rights and playback entitlement; requires metadata eligibility |
| playbackAvailable | Always false: no protected streaming provider exists |
| deliveryState | Always not_configured in this implementation |

Lists omit ineligible records completely. Details, availability and denied playback
use 404 CONTENT_UNAVAILABLE for both hidden and missing IDs. They do not return
private titles, hidden totals or specific licence/user denial reasons. A
discoverable and playback-eligible request to resource=playback returns 503
PLAYBACK_NOT_CONFIGURED. It never returns a fake playable URL, token, cached
WhatsApp message identifier, origin address, storage reference or licence evidence.
General dependency failure returns a safe 503 when classified by the repository;
unexpected exceptions return generic 500 without implementation details.

Example fictional metadata availability (only in injected synthetic tests):

```json
{"discoverable":true,"metadataAvailable":true,"previewEligible":true,"previewAvailable":false,"playbackEligible":false,"playbackAvailable":false,"deliveryState":"not_configured"}
```

This may describe public promotional metadata with unresolved premium playback.
It proves neither media delivery nor legal permission for a real title. Even
playbackEligible=true is not an authorization token. Future ZL-44/ZL-45 code must
recheck current identity, rights, territory, grant and requested asset at issuance
and validate a short-lived protected delivery action. Do not treat an earlier
discovery response or client boolean as authority. Existing legacy MP4/origin
paths remain outside this boundary and require coordinated ZL-17/ZL-18 review.

Linked episodes/Shots additionally require discoverable parent metadata. Episode
availability is assessed separately from the series. No unverified season model,
episode sequence or poster URL contract is fabricated.
