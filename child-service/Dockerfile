# syntax=docker/dockerfile:1

FROM node:22-alpine AS assets
WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci

COPY resources ./resources
COPY public ./public
COPY vite.config.js ./
RUN npm run build

FROM composer:2 AS vendor
WORKDIR /app

RUN apk add --no-cache linux-headers \
        && docker-php-ext-install sockets

COPY . .
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-progress \
    --prefer-dist \
    --optimize-autoloader

FROM php:8.3-apache-bookworm AS runtime
WORKDIR /var/www/html

ENV APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr \
    PORT=8080

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libicu-dev \
        libpq-dev \
        libzip-dev \
      libsqlite3-dev \
        unzip \
    && docker-php-ext-install \
        intl \
        opcache \
        pdo_mysql \
        pdo_pgsql \
        pdo_sqlite \
        sockets \
        zip \
    && a2enmod rewrite \
    && sed -ri -e 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/*.conf \
    && printf '%s\n' \
        '<Directory /var/www/html/public>' \
        '    AllowOverride All' \
        '    Require all granted' \
        '</Directory>' > /etc/apache2/conf-available/laravel.conf \
    && a2enconf laravel \
    && rm -rf /var/lib/apt/lists/*

COPY --from=vendor /app ./
COPY --from=assets /app/public/build ./public/build
COPY docker/cloud-run-entrypoint.sh /usr/local/bin/cloud-run-entrypoint

RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod +x /usr/local/bin/cloud-run-entrypoint

EXPOSE 8080

ENTRYPOINT ["cloud-run-entrypoint"]
CMD ["apache2-foreground"]
