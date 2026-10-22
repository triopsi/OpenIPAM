<p align="center"><img src="openipam_banner.png" alt="OpenIPAM" width="640"></p>

# OpenIPAM

IP address management and device inventory for small teams and homelabs. Built with Laravel 13, Filament 5 and Livewire 4.

[![CI](https://github.com/triopsi/OpenIPAM/actions/workflows/ci.yml/badge.svg)](https://github.com/triopsi/OpenIPAM/actions/workflows/ci.yml)
[![Release](https://github.com/triopsi/OpenIPAM/actions/workflows/release.yml/badge.svg)](https://github.com/triopsi/OpenIPAM/actions/workflows/release.yml)
[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)

- Manage IPv4/IPv6 addresses, subnets, address groups and device assignments.
- Track device names, hostnames, MAC addresses, locations and service URLs.
- Import and export CSV files, create address ranges and assign available IPs.
- Use the Sanctum REST API for integrations.
- English and German interface, profile settings and email two-factor authentication.

![OpenIPAM dashboard](assets/screenshot-01.png)

## Start developing

Install Docker with Compose v2, clone the repository, then run:

```sh
git clone https://github.com/triopsi/OpenIPAM.git
cd OpenIPAM
docker compose up -d --build
docker compose exec app sh scripts/dev-setup.sh
docker compose exec app php artisan user:create
```

Open [localhost:9080](http://localhost:9080). The user command prompts for a name, email and password. Development email is captured by [Mailpit](http://localhost:9025).

PostgreSQL is the default. For MariaDB, use the standalone alternative for **every** Compose command:

```sh
docker compose -f compose.mariadb.yaml up -d --build
docker compose -f compose.mariadb.yaml exec app sh scripts/dev-setup.sh
docker compose -f compose.mariadb.yaml exec app php artisan user:create
```

VS Code users can choose **Dev Containers: Reopen in Container**. It uses the same Dockerfile and Compose setup and installs dependencies automatically. See the [development guide](docs/development.md) for MariaDB Devcontainers, native setup, tests and asset watching.

## Run a release

A separate production image runs PHP/Apache as `www-data`, with compiled assets and no Node or development dependencies. The production Compose example uses persistent SQLite storage.

Follow the [deployment guide](docs/deployment.md) to configure an application key, initialize the database and run a release. The same guide covers backups and upgrades from the old Docker setup. Back up existing databases, storage and the original application key before migrating an installation.

OpenIPAM currently assumes trusted users: API registration is public, and every authenticated user can access the panel. Deploy behind an access-controlled reverse proxy or VPN. Email 2FA protects the panel; API tokens are a separate authentication mechanism.

## Documentation and contribution

| Guide | Contents |
| --- | --- |
| [Development](docs/development.md) | Docker, Devcontainer, native setup and checks |
| [Deployment](docs/deployment.md) | Production configuration, backup and upgrades |
| [API reference](docs/api.md) | Authentication, endpoints and examples |
| [Contributing](CONTRIBUTING.md) | Forks, branches, pull requests and releases |
| [Security](SECURITY.md) | Reporting vulnerabilities privately |

Bug reports, documentation improvements and pull requests are welcome. Start with the [contribution guide](CONTRIBUTING.md) and [open issues](https://github.com/triopsi/OpenIPAM/issues).

Released under the [MIT license](LICENSE).
