# Catalogue integration guide — ZL-16

Status: PARTIAL. Use [API](CATALOGUE_API.md), [policy](ENTITLEMENT_POLICY.md),
[availability](AVAILABILITY_CONTRACT.md) and [evidence](CATALOGUE_TEST_RESULTS.md).

## Existing consumers

All legacy PHP/JS files remain byte-preserved. Homepage js/main.js still calls
api/movies.php; series and collections scripts retain their old response shapes;
navigation/page search retains api/live_search.php. Admin search_content.php stays
separate. No frontend caller is silently redirected to v1.php. Static preservation
and guard/asset HTTP smoke tests are evidence, not full browser compatibility:
missing canonical/account schemas prevent a representative legacy end-to-end run.

The additive versioned resource contract avoids breaking old arrays/envelopes.
Do not treat this as remediation of legacy direct MP4/origin links or as permission
to bypass existing blocked delivery routes. Enabling an existing app route requires
its own source/schema/auth/rights verification.

## Composition gate

The public entry loads only pure policy/catalogue classes; it never loads the
CLI-only workflow bootstrap, existing admin configuration or session handler.
Factory::unconfigured deliberately installs MissingContext/UnconfiguredRepository.
There is no supported production enable flag. Future reviewed code must compose:

1. Verified canonical catalogue repository and relationships, without guessed DDL.
2. Current rights projection with actual independently approved licence evidence.
3. Server-side ContextProvider with trusted account/territory/age provenance and
   privacy-preserving stable quota identity; never direct request claims.
4. Explicit AccessRules and Grants adapters with per-subject/asset/operation/
   territory/channel validity and revocation semantics.
5. Shared abuse limits, stable protected cursor signing key and bounded DB budgets.
6. Regression tests for current callers and legal/release approval before exposure.

PdoSearchRepository is a guarded local synthetic integration adapter. It reads the
two-table ZL-15 workflow model, not a completed normalized canonical catalogue.
It refuses unknown/production targets and overprivileged accounts. Do not weaken
its marker/UUID guard as a deployment shortcut. No new migration/index is needed
for ZL-16; existing manifests and six source SQL files are preserved.

## Future consumers and delivery

SearchService returns structured, rights- and requester-filtered metadata suitable
for later AI retrieval. Synopsis/tags are verified contract fields; mood taxonomy,
embeddings, ranking, personalization and provider calls are absent. An LLM must
not decide access or invent canonical IDs. Linked Shots require eligible parents;
actual protected preview/playback actions are absent. Treat metadata as untrusted
text in prompts/UI. Future action issuance must recheck policy on the server.

ZL-17/ZL-45 can integrate Shots/movie playback only after canonical relationships
and ZL-44 protected delivery exist. ZL-19 and ZL-55 supply missing identity/access
administration. ZL-20–ZL-25 may reuse the query/DTO boundary after integration and
evaluation. These are PROPOSED DEPENDENCIES, not verified Jira links or completed
features. No later task starts automatically.

## Troubleshooting and rollback

503 CATALOGUE_NOT_CONFIGURED is expected from the actual public entry. It is not
a reason to point it at historical/private SQL or disable guards. 503
SEARCH_BUDGET_EXCEEDED means eligibility could not be established within 200
candidates; narrow a supported filter or review a canonical projection. 503
PLAYBACK_NOT_CONFIGURED means eligibility passed but delivery is absent. 404 does
not disclose whether content exists. 400 means contract/cursor invalid; restart
pagination after expiry/context change. Unexpected exceptions give generic 500;
use the correlation ID and sanitized local diagnostics, never publish credentials.

Tests run only on fresh uniquely labelled tmpfs MySQL containers and synthetic
fixtures. Their ownership-checked finally cleanup removes only their own server/
network. Managed environment volumes are never reset/migrated by these suites.
Use `node scripts/environment.cjs down ci` to stop managed CI while retaining
volumes; never use volume deletion as a test reset.

Rollback requires no database rollback: ZL-16 adds no schema change. Keep the
public factory disconnected or disable only the new v1.php route in a reviewed
future deployment. Revert only this task's reviewed Catalogue module/API/tests/
context/CI/documentation delta after preserving the current working tree. Do not
reset/clean Git or discard pre-existing ZL-15 changes. No deployment or Git commit
was made during this task.

## 2026-10-24 - Subsequent ZL-17 local playback boundary

ZL-17 now adds separate Playback link/details/session/media services and an
authorized original local-HLS test path. Catalogue v1.php remains unconfigured
and its availability contract still promises no actual delivery. Legacy movie
Watch and Shot navigation changes are documented in
[the current flow](../streaming/SHOTS_TO_MOVIE_FLOW.md); no production identity,
rights/CDN adapter or catalogue enable flag was added. See
[actual playback tests](../streaming/PLAYBACK_TEST_RESULTS.md).
