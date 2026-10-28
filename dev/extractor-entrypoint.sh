#!/bin/sh
set -eu
test "$ALLOW_LIVE_INTEGRATIONS" = false
test "$PORT" = 3334
if test "$EXTRACTOR_MODE" = private-fixture; then
    test "$APP_ENV" = production
    test "${ZINEMA_PROMOTION_FIXTURES:-}" = 1
    test "$EXTRACTOR_SOCKET" = /run/zinema/extractor.sock
else
    test "$APP_ENV" = development
    test "$EXTRACTOR_MODE" = local
fi
export EXTRACTOR_API_KEY="$(cat /run/secrets/extractor_key)"
exec node index.js
