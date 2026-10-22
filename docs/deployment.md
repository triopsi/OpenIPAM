# Deployment

## Production image with SQLite

`compose.production.yaml` is a standalone deployment configuration. It uses persistent SQLite storage by default and publishes the app at http://localhost:9080. The [README quick start](../README.md#quick-start) provides a smaller copy-and-paste Compose file for an installation without a source checkout.

On every container start, OpenIPAM:

1. Uses an explicit `APP_KEY` if supplied, or reads/generates `storage/app.key` under an exclusive file lock.
2. Runs all pending database migrations. If a migration fails, startup stops before serving requests.
3. Starts the web server. If the selected database has no users, the login page leads to the first-account installer.

The generated key, SQLite database and uploads are kept in the `storage` volume. A damaged stored key causes startup to fail rather than silently replacing it. The installer closes as soon as an account exists, and checks again on submission under a shared database lock. An old installer tab cannot create an additional account after setup finishes.

### Building the current source

The registry must contain a release with the updated initialization and installer before the README's image can be used. To run this source before publication, clone the repository and build the production target:

```sh
git clone https://github.com/triopsi/OpenIPAM.git
cd OpenIPAM
docker compose -f compose.production.yaml build app
docker compose -f compose.production.yaml up -d --no-build --wait
```

Open http://localhost:9080/admin and complete the installer. No key generation or console account command is required.

For a published image, use `docker compose -f compose.production.yaml pull` instead of building. Set `OPENIPAM_IMAGE` to a specific version tag for controlled upgrades. When using the repository's production file, include `-f compose.production.yaml` in every command; the root default Compose file starts a development environment.

## HTTPS and configuration

For an HTTPS deployment, copy `.env.production.example` to `.env.production`, set your public `APP_URL`, keep `SESSION_SECURE_COOKIE=true` and pass `--env-file .env.production` to Compose. A blank or omitted `APP_KEY` uses the persisted generated key. An existing installation should retain its original explicit key.

Put a VPN or access-controlled HTTPS reverse proxy in front of the app. API registration is public and authenticated users share inventory and user-management access. The application trusts forwarded proxy headers, so keep the backend reachable only by your trusted proxy. The [README](../README.md#https--remote-access) describes same-host, remote and Docker-network proxy setups.

The app runs as UID/GID 33 (`www-data`). New named volumes inherit writable directories from the image. Restored volumes or bind mounts must be writable by that user. The entrypoint never edits a host `.env` file. Console commands invoked with `docker exec` can read the stored key through the application's configuration as well.

## MariaDB and PostgreSQL

The [README's expandable MariaDB example](../README.md#choose-your-database) is a standalone alternative for a new installation. It waits for a healthy database before starting the app. The image also includes PostgreSQL support.

For an external database, set `DB_CONNECTION` (`mariadb` or `pgsql`), `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME` and `DB_PASSWORD`. Persist and back up the database independently of the app's storage volume. The app still needs its storage volume for the generated key and uploads. Multiple app containers must share the same key and database; migration orchestration for a multi-replica rollout should happen before routing traffic to the new replicas.

Changing a database connection does not move data. Do not use the source repository's development Compose files as production deployments.

## Email and background jobs

Configure SMTP before enabling email 2FA: `MAIL_MAILER=smtp`, host, port, scheme, credentials and sender. Use `MAIL_SCHEME=smtp` for STARTTLS-capable SMTP or `smtps` for implicit TLS. With `MAIL_MAILER=log`, messages are written to container logs rather than delivered. Email 2FA protects browser sign-in; API tokens are a separate authentication mechanism.

The application and container default to `QUEUE_CONNECTION=sync`. Email notifications are sent directly during the request; no queue worker is required. Keep this setting for the supplied Compose deployments. A separate worker is needed if you explicitly choose database queues. Redis requires a compatible PHP client or extension in a custom image; it is not included in the base image.

## Upgrade an existing installation

1. Stop writes and back up the SQL database, storage and the original application key. Test that the backup can be restored.
2. Keep the same database engine, credentials and volume mounts. The old `docker-compose.yml` used MariaDB 10.11 and different volume names. The new examples create separate volumes and do not import old data. Use a supported database upgrade or logical dump/restore when changing database versions.
3. Keep the original `APP_KEY` in the app environment. It takes precedence over a generated key; do not remove it in the expectation that a new key will be equivalent. Preserve existing uploads and the public URL as well.
4. Select a new application image tag and recreate the app container. Pending migrations run automatically on every start; there is no `AUTO_MIGRATE` opt-in or opt-out. Startup fails if the database is unavailable or migrations fail.
5. Verify login, device lists, assignments, CSV import/export and API access. Existing accounts bypass the installer. A setup form on an established installation can mean the app is pointing at a new or wrong database; check the connection and volume mounts before creating another account.

From a folder containing the README's standalone Compose file:

```sh
docker compose pull
docker compose up -d --wait --remove-orphans
```

For the repository's production file, use the corresponding `-f` and optional `--env-file` arguments. Remove obsolete `AUTO_MIGRATE` and `ADMIN_*` settings. You may still use `php artisan user:create` for console-based account management, but it is optional.

This source update also moves Laravel 12 → 13, Filament 3 → 5, Livewire 3 → 4 and Tailwind 3 → 4. Rebuild assets when deploying source. Clear old application caches during a stopped upgrade with `php artisan optimize:clear`. Reverting an image does not reverse database migrations: restore the matching database backup when rolling back a deployment.

## Backup and restore

For the README's SQLite Compose file, stop the app and archive its storage while offline. Override the entrypoint for this maintenance command so it does not run migrations during the backup:

```sh
docker compose stop app
mkdir -p backups
docker compose run --rm --no-deps --entrypoint tar app \
  -C /var/www/html/storage -czf - . > backups/openipam-storage.tar.gz
docker compose start app
```

This includes `storage/app.key`, SQLite and uploaded files. Keep an explicit `APP_KEY`, SMTP credentials and any database password separately in secure backups. For MariaDB/PostgreSQL, also take a database-native backup; the app's storage archive does not contain the external SQL database.

To restore, stop the app, restore storage into an empty volume with ownership writable by UID/GID 33, restore the matching external database if applicable, and retain the original key. Start the intended image and verify the restored inventory. Do not use `down -v` unless you intend to delete stored data.

## Diagnostics

```sh
docker compose ps
docker compose logs --tail=100 app
```

`/up` is the container liveness route. Monitor database connectivity and critical application flows separately. Startup errors about a stored key should be resolved by restoring the correct key and permissions, not by deleting the key file.
