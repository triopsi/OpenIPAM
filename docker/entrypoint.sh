#!/bin/bash
set -euo pipefail

APP_DIR="/var/www/html"
cd "$APP_DIR"

# .env erzeugen, falls fehlt
if [ ! -f "$APP_DIR/.env" ]; then
  echo "==> Creating .env from .env.example..."
  cp "$APP_DIR/.env.example" "$APP_DIR/.env"
fi

# Production Flags setzen (nur wenn du wirklich im Container hardcoden willst)
echo "==> Setting production env flags..."
grep -q "^APP_ENV=" .env && sed -i 's/^APP_ENV=.*/APP_ENV=production/' .env || echo "APP_ENV=production" >> .env
grep -q "^APP_DEBUG=" .env && sed -i 's/^APP_DEBUG=.*/APP_DEBUG=false/' .env || echo "APP_DEBUG=false" >> .env

# APP_KEY
if [ -z "$(grep '^APP_KEY=' .env | cut -d'=' -f2-)" ]; then
  echo "==> Generating APP_KEY..."
  php artisan key:generate --force --no-interaction
fi

# Wichtig: vor Optimierungen erstmal ALLES clearen (sicher für Deploy)
echo "==> Clearing caches..."
php artisan optimize:clear --no-interaction

# Datenbank migrieren (jetzt sind Cache-Tabellen ggf. dabei)
echo "==> Running migrations..."
php artisan migrate --force --no-interaction

# Admin user optional
if [ -n "${ADMIN_USERNAME:-}" ] && [ -n "${ADMIN_PASSWORD:-}" ] && [ -n "${ADMIN_EMAIL:-}" ]; then
  echo "==> Ensuring admin user exists..."
  php artisan tinker --execute="
    \$exists = App\\Models\\User::where('email', '${ADMIN_EMAIL}')->exists();
    if (!\$exists) {
      \$user = App\\Models\\User::create([
        'name' => '${ADMIN_USERNAME}',
        'email' => '${ADMIN_EMAIL}',
        'password' => Hash::make('${ADMIN_PASSWORD}'),
        'gravatar_type' => 'mp',
        'email_two_factor_enabled' => false,
      ]);
      echo 'Admin user created: ' . \$user->email;
    } else {
      echo 'Admin user already exists.';
    }
  " >/dev/null
else
    echo "==> ADMIN_USERNAME, ADMIN_PASSWORD, or ADMIN_EMAIL not set; skipping admin user creation."
fi

# Filament: optimize ok;
echo "==> Filament optimize..."
php artisan filament:optimize --no-interaction || true

# Production caching (nach migrations!)
echo "==> Caching for production..."
php artisan config:cache --no-interaction
php artisan route:cache --no-interaction || true
php artisan view:cache --no-interaction

# Permissions
echo "==> Fixing permissions..."
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# Print version if VERSION file exists
if [ -f "$APP_DIR/VERSION" ]; then
    VERSION=$(cat "$APP_DIR/VERSION")
    echo "==> Application version: $VERSION"
fi

echo "==> Laravel application is ready!"
exec "$@"
