# Payment/subscription integration - ZL-18

LEGACY / UNAPPROVED. Source-confirmed provider is IdeaMart: api/subscribe.php
constructs a provider subscription request then updates users subscription expiry;
api/payment-notify.php accepts status/subscriber fields and changes access. The
historical callback has no verified provider signature/replay contract and logs
raw notifications. Those bodies remain preserved and unreachable. Their presence
and old application-ID comments are not evidence of a working approved provider.

Both routes now use the central 403 JSON denial before environment/DB/session/body
logging/provider operations. PAYMENT_MODE cannot activate them. Forged success,
REGISTERED/subscriber fields, admin cookies, methods or production approval flags
cannot grant an entitlement. Repeated HTTP requests were tested, with no new local
queue/session/log artifact. No transaction, subscription, refund or real webhook
was sent; no historical payment/account record was read or changed.

subscribe.js retains its historical implementation below an early unavailable
state; submit is disabled and form requests are cancelled. Shared capability UI
also disables the control. The server is authoritative even if the UI is bypassed.
No new payment adapter or guessed subscription/user schema exists. No verified
background payment reconciler was found in the reviewed eligible first-party
sources; this does not certify private deployments or external provider settings.

An actual approved replacement needs owner/provider onboarding, permitted business
use and content rights, verified account/schema/entitlement model, authenticated
callbacks with replay/idempotency controls, sandbox evidence, audit/retention,
error/reconciliation/refund procedures and a separate production/release approval.
No provider-specific signature scheme is invented here. Real providers may retry
rejected callbacks; deployment/operational reconciliation must be reviewed before
any production change. ZL-19 and access/rights work remain prerequisites, not
completed dependencies. See [tests](../security/QUARANTINE_TEST_RESULTS.md) and
[rollback](../security/QUARANTINE_ROLLBACK.md).
