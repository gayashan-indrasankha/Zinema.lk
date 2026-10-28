#!/bin/sh
set -eu
test "$APP_ENV" = development
test "$ALLOW_LIVE_INTEGRATIONS" = false
test "$UPLOAD_QUOTA_MODE" = local
test "$UPLOAD_STORAGE_STATE" = /var/lib/zinema/storage
if test "$WHATSAPP_MODE" = local; then
    test "${ZINEMA_TWO_HOST_FIXTURES:-}" = 1
    test "$BASE_URL" = http://127.0.0.1:8088
    test "$DB_HOST" = 127.0.0.1
    test "$DB_NAME" = zinema_recovery_development
else
    test "$WHATSAPP_MODE" = disabled
fi
for adapter in MEDIA_MODE PAYMENT_MODE; do
    eval 'value=${'"$adapter"'}'
    test "$value" = disabled
done
if test "$EXTRACTOR_MODE" = local; then
    test "${ZINEMA_EXTRACTOR_FIXTURES:-}" = 1
    test "$FB_VIDEO_API_URL" = http://127.0.0.1:3334
    export EXTRACTOR_API_KEY="$(cat /run/secrets/extractor_key)"
else
    test "$EXTRACTOR_MODE" = disabled
fi
export DB_PASS="$(cat /run/secrets/db_password)"
export GOOGLE_CLIENT_SECRET="$(cat /run/secrets/oauth_secret)"
export WHATSAPP_API_KEY="$(cat /run/secrets/adapter_key)"
mkdir -p /var/lib/zinema/sessions /var/www/html/uploads /var/lib/zinema/storage
chown www-data:www-data /var/lib/zinema/sessions /var/www/html/uploads /var/lib/zinema/storage
exec "$@"
