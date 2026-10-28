#!/bin/sh
set -eu
test "$APP_ENV" = production
test "$ALLOW_LIVE_INTEGRATIONS" = false
test "$DB_HOST" = 127.0.0.1
test "$DB_NAME" = zinema_recovery_development
test "$BASE_URL" = https://127.0.0.1:8443
if test "${UPLOAD_QUOTA_MODE:-}" = staging; then
    test "${ZINEMA_PROMOTION_FIXTURES:-}" = 1
fi
for adapter in MAIL OAUTH WHATSAPP MEDIA PAYMENT; do
    eval 'value=${'"$adapter"'_MODE}'
    test "$value" = disabled
done
if test "$EXTRACTOR_MODE" = private-fixture; then
    test "${ZINEMA_PROMOTION_FIXTURES:-}" = 1
else
    test "$EXTRACTOR_MODE" = disabled
fi
export DB_PASS="$(cat /run/secrets/db_password)"
mkdir -p /var/lib/zinema/sessions /var/www/html/uploads
chmod 700 /var/lib/zinema/sessions
chown www-data:www-data /var/lib/zinema/sessions /var/www/html/uploads
exec "$@"
