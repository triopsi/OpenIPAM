#!/bin/sh
set -eu
cd "$(dirname "$0")/.."
sh scripts/dev-ssh.sh
[ -f .env ] || cp .env.example .env
mkdir -p storage/app/public storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
composer install --no-interaction --prefer-dist
# Keep existing application keys when re-running setup.
if ! grep -Eq '^APP_KEY=.+$' .env; then
    php artisan key:generate --no-interaction
fi
if [ "${DB_CONNECTION:-sqlite}" = "sqlite" ]; then
    [ -f database/database.sqlite ] || touch database/database.sqlite
fi
php artisan migrate --no-interaction
npm ci --no-audit --no-fund
npm run build
if [ ! -L public/storage ]; then
    php artisan storage:link --no-interaction
fi
# The development Apache process runs as www-data.
if [ "$(id -u)" = "0" ]; then
    chown -R www-data:www-data storage bootstrap/cache
    chmod -R g+rwX storage bootstrap/cache
fi
