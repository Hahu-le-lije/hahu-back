# syntax=docker/dockerfile:1

FROM node:20-alpine AS assets
WORKDIR /app

COPY package*.json ./
RUN npm install

COPY . .
RUN npm run build

FROM composer:2 AS vendor
WORKDIR /app

COPY composer.json ./
RUN composer install \
    --no-interaction \
    --prefer-dist

COPY . .
RUN composer run-script post-autoload-dump || true

FROM php:8.3-apache-bookworm AS runtime
WORKDIR /var/www/html

ENV APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr \
    PORT=8080

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libicu-dev \
        libpng-dev \
        libpq-dev \
        libzip-dev \
        unzip \
    && docker-php-ext-install \
        bcmath \
        exif \
        gd \
        intl \
        opcache \
        pcntl \
        pdo_pgsql \
        pgsql \
        sockets \
        zip \
    && a2enmod headers rewrite \
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
COPY docker/docker-entrypoint.sh /usr/local/bin/docker-entrypoint

RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod +x /usr/local/bin/docker-entrypoint

EXPOSE 8080

ENTRYPOINT ["docker-entrypoint"]
CMD ["apache2-foreground"]
