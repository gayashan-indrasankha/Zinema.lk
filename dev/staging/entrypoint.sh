#!/bin/sh
set -eu
test "$APP_ENV" = production
test "$ALLOW_LIVE_INTEGRATIONS" = false
test "$DB_HOST" = 127.0.0.1
test "$DB_NAME" = zinema_recovery_development
test "$BASE_URL" = https://127.0.0.1:8443
for adapter in MAIL OAUTH WHATSAPP MEDIA EXTRACTOR PAYMENT; do
    eval 'value=${'"$adapter"'_MODE}'
    test "$value" = disabled
done
export DB_PASS="$(cat /run/secrets/db_password)"
mkdir -p /var/lib/zinema/sessions /var/www/html/uploads
chmod 700 /var/lib/zinema/sessions
chown www-data:www-data /var/lib/zinema/sessions /var/www/html/uploads
exec "$@"
