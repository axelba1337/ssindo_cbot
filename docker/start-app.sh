#!/bin/sh
set -eu

cd /var/www/html
port="${PORT:-8080}"
case "$port" in
    ''|*[!0-9]*) echo 'PORT must be a numeric TCP port.' >&2; exit 1 ;;
esac
if [ "${#port}" -gt 5 ] || [ "$port" -lt 1 ] || [ "$port" -gt 65535 ]; then
    echo 'PORT must be between 1 and 65535.' >&2
    exit 1
fi

umask 027
mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache

# Preserve the existing optional Aiven CA flow; never print certificate contents.
if [ -n "${AIVEN_CA_CERT:-}" ]; then
    printf '%s\n' "$AIVEN_CA_CERT" > storage/ca.pem
    chmod 640 storage/ca.pem
fi

sed -i -E "s/^[[:space:]]*Listen[[:space:]]+[0-9]+$/Listen ${port}/" /etc/apache2/ports.conf
sed -i -E "s/<VirtualHost[[:space:]]+\*:[0-9]+>/<VirtualHost *:${port}>/" /etc/apache2/sites-available/000-default.conf
apache2ctl configtest

# Runtime variables, not image-build secrets, populate Laravel's cache.
# Never migrate, seed, regenerate APP_KEY, or rebuild knowledge here.
if [ "${APP_ENV:-production}" = 'production' ]; then
    if [ -z "${APP_KEY:-}" ]; then
        echo 'Set the existing production APP_KEY before starting this container.' >&2
        exit 1
    fi
    # Fix Apache MPM conflict at container startup.
    a2dismod -f mpm_event mpm_worker 2>/dev/null || true

    rm -f \
        /etc/apache2/mods-enabled/mpm_event.load \
        /etc/apache2/mods-enabled/mpm_event.conf \
        /etc/apache2/mods-enabled/mpm_worker.load \
        /etc/apache2/mods-enabled/mpm_worker.conf

    a2enmod mpm_prefork

    # Validate the actual runtime Apache configuration.
    apache2ctl configtest || exit 1
    php artisan config:cache --no-interaction
    php artisan view:cache --no-interaction
fi

chown -R www-data:www-data storage bootstrap/cache
exec apache2-foreground
