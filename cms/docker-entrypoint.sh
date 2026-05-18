#!/bin/sh
set -eu

: "${PORT:=8080}"
: "${LARAVEL_OPTIMIZE:=true}"
: "${RUN_MIGRATIONS:=false}"

# Update Apache port configuration
sed -i "s/^Listen .*/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:[0-9][0-9]*>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

# Run migrations if enabled
if [ "${RUN_MIGRATIONS}" = "true" ]; then
    echo "Running database migrations..."
    php artisan migrate --force --no-interaction
fi

# Optimize Laravel if enabled
if [ "${LARAVEL_OPTIMIZE}" = "true" ]; then
    echo "Optimizing Laravel configuration..."
    php artisan config:cache --no-ansi
    php artisan route:cache --no-ansi
    php artisan view:cache --no-ansi
fi

# Generate app key if not present
if [ -z "${APP_KEY:-}" ]; then
    echo "Generating APP_KEY..."
    php artisan key:generate --no-ansi
fi

echo "CMS service starting on port ${PORT}..."
exec apache2-foreground
