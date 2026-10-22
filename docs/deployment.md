# Deployment

## Production image with SQLite

`compose.production.yaml` is standalone. It runs a prebuilt image, exposes the application on loopback port 8080 and persists SQLite, uploads and logs in the `storage` volume. Put an HTTPS reverse proxy with access control in front of it. Public API registration and panel access currently assume trusted users; there is no administrative role boundary. The application trusts forwarded proxy headers, so keep the backend reachable only by your trusted proxy.

```sh
cp .env.production.example .env.production
# Generate a key, then paste the complete output into APP_KEY in .env.production:
printf 'base64:'; openssl rand -base64 32
```

Set `APP_URL` to the external HTTPS URL. Keep the same key across restarts and upgrades and store it with your backups. Startup fails if `APP_KEY` is absent. The entrypoint never edits `.env`, creates users or replaces application keys.

The first release from this updated source must be published before its registry image can be pulled. To build the current checkout locally instead:

```sh
docker compose --env-file .env.production -f compose.production.yaml build app
```

Initialize the database and create the first user:

```sh
docker compose --env-file .env.production -f compose.production.yaml run --rm app php artisan migrate --force
docker compose --env-file .env.production -f compose.production.yaml run --rm app php artisan user:create
docker compose --env-file .env.production -f compose.production.yaml up -d --no-build
```

For a published image, set `OPENIPAM_IMAGE` to a specific version tag, pull it before initialization, and skip the local build. `AUTO_MIGRATE=false` is the default; set it to `true` only when you deliberately want migrations during startup. All production commands in this guide use the production env file and Compose file; the default `docker compose` command selects development.

The process runs as `www-data`. New Docker named volumes inherit writable directories from the image. Existing bind mounts or volumes must be writable by UID/GID 33. A fresh SQLite database file is created by Laravel's forced migration. Uploaded files are available through `public/storage`.

For local HTTP smoke tests, use `APP_URL=http://localhost:8080` and `SESSION_SECURE_COOKIE=false`. Keep secure cookies enabled for HTTPS deployments.

## Email and background jobs

Configure `MAIL_MAILER=smtp`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_SCHEME`, credentials and sender before enabling email 2FA. With `MAIL_MAILER=log`, emails are written to container logs. `MAIL_SCHEME=smtp` supports STARTTLS; use `smtps` when the provider requires implicit TLS.

The supplied configurations use synchronous jobs. No Redis or queue worker is required. If you switch to database queues, provision a separate worker with the same image, environment and storage. For Redis, supply a compatible PHP client/extension as well; it is not included in the base image.

## Upgrade an existing installation

1. Stop writes and back up the existing SQL database, storage volume and original `.env` including `APP_KEY`. Confirm that a backup can be restored.
2. Keep the existing database engine and data directory when upgrading. The old `docker-compose.yml` used MariaDB 10.11 and explicitly named containers; the new Compose files create different volumes and do not import old data. Do not attach a MariaDB 10.11 data directory directly to a new major database image. Use a logical dump/restore or the database vendor's supported upgrade procedure.
3. Transfer runtime settings to your deployment configuration. Preserve `APP_KEY`, database credentials, uploads and the public URL. Remove the old `ADMIN_*` bootstrap variables; use `php artisan user:create` for new users. Existing accounts remain in the database.
4. Deploy a pinned image, run `php artisan migrate --force` against the retained database, then verify login, device lists, IP assignments, CSV import/export and API access.

The production example uses SQLite for a fresh installation. To keep an existing MariaDB/PostgreSQL database, create a deployment-specific Compose file with the production image and persistent storage; set `DB_CONNECTION` (`mariadb` or `pgsql`), host, port, database, username and password. Both drivers are included. Never use the development Compose variants for a production deployment.

The source update moves Laravel 12 → 13, Filament 3 → 5, Livewire 3 → 4 and Tailwind 3 → 4. Rebuild assets and use the updated lockfiles. Remove stale application caches during a stopped upgrade with `php artisan optimize:clear`. Existing email 2FA behavior remains application-managed. Restoring the previous image alone may not reverse database migrations: restore the matching database backup when rolling back.

## Backup and restore

For the SQLite example, stop the application before copying its storage volume so the database and any journal files are consistent:

```sh
docker compose --env-file .env.production -f compose.production.yaml stop app
mkdir -p backups
docker compose --env-file .env.production -f compose.production.yaml run --rm --no-deps app \
  tar -C /var/www/html/storage -czf - . > backups/openipam-storage.tar.gz
docker compose --env-file .env.production -f compose.production.yaml start app
```

Back up `.env.production` separately in a secure location. On a replacement installation with the same image and key, stop the application, restore the archive into an empty storage volume and start it again. Keep ownership writable by UID/GID 33. For external databases, use their native backup tools in addition to backing up storage. Test restores before relying on backups.

Useful diagnostics:

```sh
docker compose --env-file .env.production -f compose.production.yaml ps
docker compose --env-file .env.production -f compose.production.yaml logs --tail=100 app
```

`/up` is the container liveness route. Monitor database connectivity and your critical application flows separately.
