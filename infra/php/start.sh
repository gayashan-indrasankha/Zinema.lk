#!/bin/sh
set -eu
umask 077
mkdir -p /runtime/sessions /tmp/apache2
if [ "${ZINEMA_PROFILE:-}" = development ] && [ "$1" = apache2-foreground ]; then
    exec "$@" -D ZINEMA_DEVELOPMENT
fi
exec "$@"
