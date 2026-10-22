# Development

## Shared container setup

The root Dockerfile provides `development` and `production` targets. `compose.yaml` uses PostgreSQL 17; `compose.mariadb.yaml` is a standalone MariaDB 11.4 alternative. Both include Mailpit and use the same application service, `app`. Do not combine the two development files. Database volumes are separate; changing variants does not transfer data.

```sh
docker compose up -d --build
docker compose exec app sh scripts/dev-setup.sh
```

Open the app and use the first-account installer if the database has no users. `php artisan user:create` remains an optional console alternative.

Setup preserves an existing `.env` and application key, installs locked PHP/npm dependencies, runs pending migrations and builds assets. It does not seed demo accounts. The local `.env` is never committed or copied into a production image. Container environment variables take precedence over `.env` values; the development database credentials are local examples.

- Application: http://localhost:9080/admin
- Mailpit: http://localhost:9025
- Set `APP_PORT`, `VITE_PORT` or `MAILPIT_PORT` in `.env` if host ports are occupied.
- Database and SMTP ports are available inside the Compose network. They are not published to the host.

For MariaDB, add `-f compose.mariadb.yaml` to each command. Stop services using `docker compose stop`; `docker compose down` removes containers while preserving named volumes. `down -v` deletes the selected project's database volume.

## Devcontainer

Open the checkout in VS Code with the Dev Containers extension, then choose **Reopen in Container**. `.devcontainer/devcontainer.json` references the root Compose file and runs the shared setup script. PHP, Composer and Node 24 are included in the development target.

To use MariaDB, change `dockerComposeFile` to `../compose.mariadb.yaml` and rebuild the Devcontainer. Application and Mailpit ports are published by Compose; VS Code automatic port forwarding is disabled to avoid duplicate mappings. The workspace is `/var/www/html`; run the commands below directly from its terminal, without `docker compose exec app`.

### Git over SSH

Like SplitNest, the Devcontainer mounts your host's `~/.ssh` read-only at `/ssh-host`. During setup, `scripts/dev-ssh.sh` copies it into `/root/.ssh` only when that directory does not already exist, with directory permissions `700` and file permissions `600`. Host keys and SSH aliases are preserved; macOS's `UseKeychain` option is ignored inside Linux. Your Mac's files are not modified. The mount is specific to the Devcontainer and is not included in production images.

After changing this configuration, choose **Dev Containers: Rebuild and Reopen in Container**. Then verify access with `ssh -T git@github.com` and use `git fetch`, `git pull` or `git push` as usual. GitHub reports successful authentication with exit code 1 because it does not provide shell access. If the host key is not yet trusted, verify its fingerprint against GitHub's published SSH fingerprints before accepting it. Encrypted keys may still prompt for their passphrase; the macOS Keychain is not available inside Linux.

An existing container-side `.ssh` directory is preserved. If you change keys on your Mac later, update the container copy deliberately or recreate the container. Never copy SSH credentials into the repository.

## Assets and checks

```sh
docker compose exec app npm run dev
docker compose exec -e DB_CONNECTION=sqlite -e DB_DATABASE=:memory: app composer test
docker compose exec app composer lint
docker compose exec app npm run lint-js
docker compose exec app npm run build
```

The Vite server listens on container port 5173. Compose maps it to host port 9517 and passes `VITE_PORT` to configure the browser HMR connection. Stop Vite before using built assets; if a terminated watcher leaves `public/hot`, remove that file.

Format PHP with `composer lint:fix`. Run individual tests inside the container with `DB_CONNECTION=sqlite DB_DATABASE=:memory: php artisan test --filter=DeviceManagementTest`. Tests use an in-memory SQLite database unless `DB_CONNECTION` and `DB_DATABASE` are explicitly supplied by the process. Always use a disposable database when overriding these variables: `RefreshDatabase` recreates tables.

CI runs PHP 8.4/8.5 tests, the full suite on PostgreSQL/MariaDB, migration rollback checks, Pint, ESLint, asset compilation, dependency audits and a production-image smoke test. Release publication waits for these checks. Workflow jobs do not install additional analysis packages or rewrite dependency manifests.

## Native development

Requirements: PHP 8.4+, Composer 2, Node 24, npm, and PHP extensions including `bcmath`, `gd`, `intl`, `mbstring`, `pdo_sqlite`, `zip`, DOM/XML and fileinfo. The container also includes `pdo_mysql` and `pdo_pgsql`.

On a fresh clone:

```sh
sh scripts/dev-setup.sh
composer dev
```

Native setup defaults to `database/database.sqlite` and Laravel's development server on http://localhost:8000. Update `APP_URL` accordingly. If you retain an older `.env`, check its database connection before running setup. For native development without Mailpit, use `MAIL_MAILER=log`; use real SMTP or a mail catcher when testing email 2FA.

## Application layout

| Path | Responsibility |
| --- | --- |
| `app/Filament/Resources/{Devices,IpAddresses,IpAddressGroups,Users,Settings}` | Domain resources with their own `Pages` and `RelationManagers` |
| `app/Filament/Auth` | Sign-in, first-account registration and the standalone tabbed profile |
| `app/Filament/Pages`, `app/Filament/Widgets` | Standalone pages and dashboard widgets |
| `app/Providers/Filament/AdminPanelProvider.php` | Panel discovery, translated navigation and middleware |
| `app/Http/Controllers/Api/V1` | REST API |
| `app/Services` | IP assignment logic |
| `resources/views` | Blade templates |
| `resources/lang` | English and German translations |
| `tests/Feature`, `tests/Unit` | PHPUnit coverage |
| `.docker`, `.devcontainer`, `.github` | Runtime, workspace and automation |

Filament discovers resources, standalone pages and widgets automatically. Keep each resource in its plural domain directory. Use `Filament\Actions\Action`, action `schema()` definitions and named schema methods such as `profileForm(Schema $schema)`; avoid the deprecated `MenuItem`, `Placeholder`, `makeForm()` and `getForms()` APIs. Keep user-facing strings in both `resources/lang/en` and `resources/lang/de`. Panel-level labels use translation closures so they resolve after the locale middleware runs.

Optional demo data: `php artisan db:seed`. Inspect `database/seeders` first and use a disposable development database. Production startup never runs seeders.
