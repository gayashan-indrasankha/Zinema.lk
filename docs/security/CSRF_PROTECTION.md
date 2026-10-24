# CSRF protection - ZL-19

Status: PARTIAL. The replacement Csrf synchronizer token is random 32-byte hex,
session-bound, constant-time compared and expires after 3,600 seconds. Missing,
wrong-type, malformed, future-dated and expired tokens deny. Rotation follows
login because NativeSession establishes a fresh session payload.

The isolated controller requires POST, exact configured Origin and a token in
X-CSRF-Token for JSON mutations. It rejects unsafe GETs, wrong Origin, missing
AJAX tokens, malformed JSON and bodies over 16 KiB. An Origin header alone is
not authorization; CORS is not used as CSRF protection. Browser and HTTP fixtures
exercise legitimate submissions and negative requests; no public identity route
has been added. A form controller can submit the same token via a hidden field.

The existing includes/csrf_helper.php now checks token/session types and length,
rejects non-hex/future/expired state and avoids hash_equals TypeError on arrays.
Its existing function names, hidden-field HTML, session keys and valid-token
contract are preserved. Its actual PHP regression passes in an isolated session
folder. It is not automatically replaced by the new session namespace.

Coverage gaps: legacy API auth-handler, administrative CRUD/GET deletes, logout,
Shots/comments/trailer interaction APIs, OAuth state and verification GET mutation
still lack complete standardized protection. Existing valid real-account access
requirements were not replaced without recovery/rollout approval. The entire
application is NOT CSRF-hardened; full integration remains a release blocker.
