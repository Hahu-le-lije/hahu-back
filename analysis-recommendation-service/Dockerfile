# syntax=docker/dockerfile:1

############################
# 1. BASE PHP DEPENDENCIES
############################
FROM php:8.4-apache-bookworm AS base

WORKDIR /var/www/html

ENV APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr \
    PORT=8080

# System dependencies
RUN apt-get update && apt-get install -y --no-install-recommends \
    libicu-dev \
    libpng-dev \
    libpq-dev \
    libzip-dev \
    unzip \
    git \
 && rm -rf /var/lib/apt/lists/*

# PHP extensions
RUN docker-php-ext-install \
    bcmath \
    exif \
    gd \
    intl \
    opcache \
    pcntl \
    pdo_pgsql \
    pgsql \
    sockets \
    zip

# Apache config (Laravel public/)
RUN a2enmod headers rewrite \
 && sed -ri -e 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/*.conf \
 && printf '%s\n' \
    '<Directory /var/www/html/public>' \
    '    AllowOverride All' \
    '    Require all granted' \
    '</Directory>' > /etc/apache2/conf-available/app.conf \
 && a2enconf app


############################
# 2. PHP DEPENDENCIES (Composer)
############################
FROM php:8.4-cli AS vendor

WORKDIR /app

RUN apt-get update && apt-get install -y --no-install-recommends \
    unzip git libzip-dev libpq-dev libicu-dev libpng-dev \
 && docker-php-ext-install sockets zip intl pdo_pgsql pgsql \
 && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --prefer-dist \
    --no-interaction \
    --optimize-autoloader


############################
# 3. FRONTEND BUILD (Node)
############################
FROM node:20-alpine AS assets

WORKDIR /app

COPY package*.json ./
RUN npm install

COPY . .
RUN npm run build


############################
# 4. APPLICATION BUILD LAYER
############################
FROM base AS app

WORKDIR /var/www/html

# Copy full Laravel app
COPY . .

# Copy vendor dependencies
COPY --from=vendor /app/vendor ./vendor

# Copy frontend build output
COPY --from=assets /app/public/build ./public/build

# Ensure Laravel writable directories exist
RUN mkdir -p storage bootstrap/cache \
 && chown -R www-data:www-data storage bootstrap/cache \
 && chmod -R 775 storage bootstrap/cache

# Entry point
COPY docker/docker-entrypoint.sh /usr/local/bin/docker-entrypoint
RUN chmod +x /usr/local/bin/docker-entrypoint

# Optimize Laravel (optional but recommended)
RUN php artisan config:cache || true \
 && php artisan route:cache || true \
 && php artisan view:cache || true

EXPOSE 8080

ENTRYPOINT ["docker-entrypoint"]
CMD ["apache2-foreground"]
