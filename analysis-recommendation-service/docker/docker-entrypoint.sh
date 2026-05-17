#!/usr/bin/env sh
set -e

# 1. Bind the Apache Port dynamically (Needed for Cloud Run, Heroku, AWS, etc.)
: "${PORT:=8080}"
sed -ri -e "s/^Listen .*/Listen ${PORT}/" /etc/apache2/ports.conf
sed -ri -e "s/<VirtualHost \*:[0-9]+>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

# 2. Fix directory permissions
# Laravel NEEDS write access to the storage and bootstrap/cache directories.
# 'www-data' is the default Apache user on Debian/Ubuntu.
echo "Setting directory permissions..."
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# 3. Laravel Production Optimizations
# Instead of just clearing the cache, you should build it for faster performance.
echo "Caching Laravel configuration, routes, and views..."
php artisan route:cache --no-interaction
php artisan view:cache --no-interaction
php artisan event:cache --no-interaction

php artisan config:clear --no-interaction

if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
    echo "Running migrations..."
    php artisan migrate --force --no-interaction
fi

exec "$@"
