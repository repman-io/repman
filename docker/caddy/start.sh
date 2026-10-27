#!/bin/sh
# Picks the TLS mode and the handling of plain http for the Caddyfile, then runs Caddy.

if [ -n "${ACME_EMAIL}" ]; then
    REPMAN_TLS=acme
elif [ -f /ssl/certs/server.crt ] && [ -f /ssl/private/server.key ]; then
    REPMAN_TLS=custom
else
    REPMAN_TLS=internal
fi

if [ "${APP_URL_SCHEME}" = "http" ]; then
    REPMAN_HTTP=serve
else
    REPMAN_HTTP=redirect
fi

export REPMAN_TLS REPMAN_HTTP
echo "TLS: ${REPMAN_TLS}, plain http: ${REPMAN_HTTP}"

exec caddy run --config /etc/caddy/Caddyfile --adapter caddyfile
