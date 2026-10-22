#!/bin/sh
set -eu
cd /var/www/html
mkdir -p storage/app/public storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
APP_KEY="$(php .docker/initialize.php)"
export APP_KEY
# Serve requests only after all pending migrations have completed successfully.
php artisan migrate --force --no-interaction
exec docker-php-entrypoint "$@"
