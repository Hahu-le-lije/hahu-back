#!/usr/bin/env sh
set -e

: "${PORT:=8080}"

sed -ri -e "s/^Listen .*/Listen ${PORT}/" /etc/apache2/ports.conf
sed -ri -e "s/<VirtualHost \*:[0-9]+>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

php artisan config:clear --no-interaction
php artisan view:cache --no-interaction

exec "$@"
