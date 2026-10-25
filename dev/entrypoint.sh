#!/bin/sh
set -eu
test "$APP_ENV" = development
test "$ALLOW_LIVE_INTEGRATIONS" = false
for adapter in WHATSAPP_MODE MEDIA_MODE EXTRACTOR_MODE PAYMENT_MODE; do
    eval 'value=${'"$adapter"'}'
    test "$value" = disabled
done
export DB_PASS="$(cat /run/secrets/db_password)"
export GOOGLE_CLIENT_SECRET="$(cat /run/secrets/oauth_secret)"
export WHATSAPP_API_KEY="$(cat /run/secrets/adapter_key)"
mkdir -p /var/lib/zinema/sessions /var/www/html/uploads
chown www-data:www-data /var/lib/zinema/sessions /var/www/html/uploads
exec "$@"
