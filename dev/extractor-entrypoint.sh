#!/bin/sh
set -eu
test "$APP_ENV" = development
test "$EXTRACTOR_MODE" = local
test "$ALLOW_LIVE_INTEGRATIONS" = false
test "$PORT" = 3334
export EXTRACTOR_API_KEY="$(cat /run/secrets/extractor_key)"
exec node index.js
