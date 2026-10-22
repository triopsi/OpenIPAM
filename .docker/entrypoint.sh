#!/bin/sh
set -eu
cd /var/www/html
mkdir -p storage/app/public storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
: "${APP_KEY:?Set a persistent APP_KEY (see docs/deployment.md).}"
if [ "${AUTO_MIGRATE:-false}" = "true" ]; then
    php artisan migrate --force --no-interaction
fi
exec docker-php-entrypoint "$@"
