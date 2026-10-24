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

diagnose_mpm() {
    echo '[apache] MPM LoadModule locations (available files may be disabled):' >&2
    find -L /etc/apache2 -type f \( -name '*.conf' -o -name '*.load' \) \
        -exec grep -HnE '^[[:space:]]*LoadModule[[:space:]]+mpm_' {} + >&2 || :
    echo '[apache] Include chain (may be incomplete when parsing fails):' >&2
    apache2ctl -t -D DUMP_INCLUDES >&2 || :
}

# Normalize once, in every environment, before the first Apache config check.
# Do not hide module-tool failures or add hand-written LoadModule directives.
if ! a2dismod mpm_event mpm_worker || ! a2enmod mpm_prefork; then
    diagnose_mpm
    exit 1
fi

echo '[apache] Enabled MPM loader directives (file:line):'
grep -HnE '^[[:space:]]*LoadModule[[:space:]]+mpm_' /etc/apache2/mods-enabled/*.load || :
if ! apache2ctl configtest; then
    diagnose_mpm
    exit 1
fi

if ! loaded_modules="$(apache2ctl -M)"; then
    diagnose_mpm
    exit 1
fi
active_mpm="$(printf '%s\n' "$loaded_modules" | awk '$1 ~ /^mpm_.*_module$/ { print $1 }')"
printf '[apache] Active MPM: %s\n' "$active_mpm"
if [ "$active_mpm" != 'mpm_prefork_module' ]; then
    echo '[apache] Expected exactly one loaded MPM: mpm_prefork_module.' >&2
    diagnose_mpm
    exit 1
fi

# Runtime variables, not image-build secrets, populate Laravel's cache.
# Never migrate, seed, regenerate APP_KEY, or rebuild knowledge here.
if [ "${APP_ENV:-production}" = 'production' ]; then
    if [ -z "${APP_KEY:-}" ]; then
        echo 'Set the existing production APP_KEY before starting this container.' >&2
        exit 1
    fi
    php artisan config:cache --no-interaction
    php artisan view:cache --no-interaction
fi

chown -R www-data:www-data storage bootstrap/cache
exec apache2-foreground
