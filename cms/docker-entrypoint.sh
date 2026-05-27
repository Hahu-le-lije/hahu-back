#!/bin/sh
set -eu

: "${PORT:=8080}"

sed -i "s/^Listen .*/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:[0-9][0-9]*>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

# Optionally generate an APP_KEY on boot if it's missing and the user requests it
# Set GENERATE_APP_KEY_ON_BOOT=true to enable. Generated key will be written to the
# app .env file (attempts /var/www/html/.env then ./ .env) and exported into the
# environment for the current process.
if [ -z "${APP_KEY:-}" ] && [ "${GENERATE_APP_KEY_ON_BOOT:-false}" = "true" ]; then
	echo "[entrypoint] APP_KEY missing — generating new key"
	NEW_KEY=$(php artisan key:generate --show)
	# prefer common container env file path
	ENV_FILE="/var/www/html/.env"
	if [ ! -f "$ENV_FILE" ]; then
		if [ -f ".env" ]; then
			ENV_FILE=".env"
		fi
	fi
	if [ -n "$ENV_FILE" ]; then
		if [ -f "$ENV_FILE" ]; then
			if grep -q '^APP_KEY=' "$ENV_FILE"; then
				sed -i "s|^APP_KEY=.*|APP_KEY=${NEW_KEY}|" "$ENV_FILE"
			else
				printf "\nAPP_KEY=%s\n" "$NEW_KEY" >> "$ENV_FILE"
			fi
		else
			printf "APP_KEY=%s\n" "$NEW_KEY" > "$ENV_FILE"
		fi
	fi
	export APP_KEY="$NEW_KEY"
fi

# Optionally run migrations on boot. Enable by setting RUN_MIGRATIONS=true.
if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
	echo "[entrypoint] Running database migrations (force)"
	php artisan migrate --force
fi

# Cache config, routes and views on boot by default. Disable by setting
# CACHE_ON_BOOT=false
if [ "${CACHE_ON_BOOT:-true}" = "true" ]; then
	echo "[entrypoint] Caching config, routes and views"
	php artisan config:cache || true
	php artisan route:cache || true
	php artisan view:cache || true
fi

exec "$@"
