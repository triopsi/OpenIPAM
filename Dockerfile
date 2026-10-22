# syntax=docker/dockerfile:1
FROM php:8.4-apache-bookworm AS base
WORKDIR /var/www/html
RUN apt-get update && apt-get install -y --no-install-recommends \
    curl git unzip libicu-dev libonig-dev libpng-dev libpq-dev libsqlite3-dev libzip-dev \
    && docker-php-ext-install -j"$(nproc)" bcmath gd intl mbstring opcache pcntl pdo_mysql pdo_pgsql pdo_sqlite zip \
    && a2enmod rewrite headers \
    && sed -i 's/Listen 80/Listen 8080/' /etc/apache2/ports.conf \
    && echo 'ServerName localhost' > /etc/apache2/conf-available/servername.conf \
    && a2enconf servername \
    && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
COPY .docker/apache.conf /etc/apache2/sites-available/000-default.conf
EXPOSE 8080

FROM node:25-bookworm-slim AS node

FROM base AS development
ENV COMPOSER_ALLOW_SUPERUSER=1
COPY --from=node /usr/local/ /usr/local/
RUN apt-get update && apt-get install -y --no-install-recommends openssh-client pre-commit \
    && rm -rf /var/lib/apt/lists/*

FROM node AS frontend
WORKDIR /app
COPY package.json package-lock.json ./
RUN --mount=type=cache,target=/root/.npm npm ci --no-audit --no-fund
# Filament's theme imports its vendor CSS and scans PHP components.

FROM base AS dependencies
ENV COMPOSER_ALLOW_SUPERUSER=1
COPY composer.json composer.lock ./
RUN --mount=type=cache,target=/tmp/composer-cache \
    COMPOSER_CACHE_DIR=/tmp/composer-cache composer install --no-dev --no-scripts --prefer-dist --no-interaction
COPY . .
RUN composer dump-autoload --no-dev --optimize

FROM frontend AS assets
COPY --from=dependencies /var/www/html/vendor ./vendor
COPY vite.config.js ./
COPY app ./app
COPY resources ./resources
RUN npm run build

FROM base AS production
ARG APP_VERSION=dev
ENV APP_ENV=production APP_DEBUG=false LOG_CHANNEL=stderr APP_VERSION=${APP_VERSION} \
    DB_CONNECTION=sqlite DB_DATABASE=/var/www/html/storage/database.sqlite QUEUE_CONNECTION=sync
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && echo 'opcache.validate_timestamps=0' > "$PHP_INI_DIR/conf.d/zz-opcache.ini"
COPY --from=dependencies /var/www/html .
COPY --from=assets /app/public/build ./public/build
RUN mkdir -p storage/app/public storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && ln -s /var/www/html/storage/app/public public/storage \
    && chown -R www-data:www-data storage bootstrap/cache /var/run/apache2 /var/lock/apache2
USER www-data
ENTRYPOINT ["sh", "/var/www/html/.docker/entrypoint.sh"]
CMD ["apache2-foreground"]
HEALTHCHECK --interval=30s --timeout=5s --start-period=30s CMD curl -fsS http://localhost:8080/up || exit 1
