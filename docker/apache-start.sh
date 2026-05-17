#!/bin/sh
set -eu

: "${PORT:=8080}"

sed -i "s/^Listen .*/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:[0-9][0-9]*>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

if [ "${LARAVEL_OPTIMIZE:-true}" = "true" ]; then
    php artisan config:cache --no-ansi
fi

exec apache2-foreground
