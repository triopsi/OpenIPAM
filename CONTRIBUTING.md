# Contributing to OpenIPAM

Help make network inventory easier to maintain. Bug reports, code, tests, translations and documentation improvements are all welcome. You do not need to be an experienced developer to contribute.

Please follow our [community conduct](CODE_OF_CONDUCT.md). Report vulnerabilities privately using [SECURITY.md](SECURITY.md).

## Before you start

Check the [open issues](https://github.com/triopsi/OpenIPAM/issues) for existing reports or related work. For a larger feature or a change to the data model, open an issue explaining the problem and proposed behavior before implementing it.

Use fictional devices, addresses and users in examples and tests. Remove credentials and private network details from logs and screenshots.

## 1. Get the development source

Development takes place in the source repository. The published container image is intended for running OpenIPAM; it does not provide an editable development workspace.

Fork [triopsi/OpenIPAM](https://github.com/triopsi/OpenIPAM) into your GitHub account, then clone your fork:

```sh
git clone git@github.com:YOUR-USERNAME/OpenIPAM.git
cd OpenIPAM
git remote add upstream https://github.com/triopsi/OpenIPAM.git
git fetch upstream
git switch main
git merge --ff-only upstream/main
git switch -c feat/your-change
```

Replace `YOUR-USERNAME` with your GitHub username. If you do not use SSH, clone with `https://github.com/YOUR-USERNAME/OpenIPAM.git` instead.

Use the latest `upstream/main` as the starting point for contributions. The optional `develop` branch has CI coverage, but pull requests target `main`.

## 2. Choose a development environment

The examples below use PostgreSQL. Application port `9080`, Mailpit port `9025` and Vite port `9517` are separate from SplitNest's defaults so both projects can run together.

### Option A: VS Code Devcontainer

Requirements: Docker with Compose, VS Code and its Dev Containers extension. PHP, Composer and Node are provided inside the container.

1. Open your cloned repository in VS Code.
2. Run **Dev Containers: Reopen in Container** from the command palette.
3. Wait for `postCreateCommand` to finish. It installs locked dependencies, prepares `.env`, creates an application key if needed, migrates the development database and builds assets.
4. Open the app and use the browser installer to create your first development account. The console command `php artisan user:create` remains available if preferred.

Open [localhost:9080/admin](http://localhost:9080/admin). Development email appears in [Mailpit](http://localhost:9025).

The Devcontainer mounts your host's `~/.ssh` read-only and copies it into the container with SSH-compatible permissions. Make sure that directory exists before opening the container. It preserves SSH aliases and known hosts and ignores macOS's `UseKeychain` option under Linux. An encrypted key may still ask for its passphrase.

Check GitHub authentication from the container terminal:

```sh
ssh -T git@github.com
git fetch upstream
```

GitHub's successful SSH greeting returns exit code 1 because interactive shell access is unavailable. If a host-key confirmation appears, verify the fingerprint before accepting it. If you prefer HTTPS and do not have `~/.ssh`, remove the SSH mount from your local `.devcontainer/devcontainer.json` before reopening; do not include that personal adjustment in your PR.

If Git asks for your commit identity, set your own name and email inside the container:

```sh
git config --global user.name "Your Name"
git config --global user.email "YOUR-GITHUB-EMAIL"
```

Use the email associated with your GitHub account or its GitHub-provided no-reply address. Configuration and SSH details are covered in the [development reference](docs/development.md#git-over-ssh).

### Option B: Docker Compose and your own editor

From the repository root:

```sh
docker compose up -d --build
docker compose exec app sh scripts/dev-setup.sh
```

Open [localhost:9080/admin](http://localhost:9080/admin). Your checkout is mounted into the app container, so edits in your editor are available immediately. Run PHP and npm commands through `docker compose exec app`; use Git on the host with your normal credentials.

### MariaDB development variant

Use the standalone MariaDB Compose file instead of the PostgreSQL default:

```sh
docker compose -f compose.mariadb.yaml up -d --build
docker compose -f compose.mariadb.yaml exec app sh scripts/dev-setup.sh
```

Include `-f compose.mariadb.yaml` in every subsequent Compose command. For the Devcontainer, change `dockerComposeFile` to `../compose.mariadb.yaml` locally and rebuild it. Keep personal environment choices out of the PR unless the change itself concerns that configuration.

The variants have separate database volumes. Changing variants does not migrate your data.

### Option C: Native tools

Use PHP 8.4 or newer with the required extensions, Composer 2 and Node 24.10 or newer within Node 24. The full extension list is in the [development reference](docs/development.md#native-development).

If you use nvm, run `nvm install` and `nvm use` in the repository to select its `.nvmrc` version. Then:

```sh
sh scripts/dev-setup.sh
```

The native setup uses SQLite on a fresh checkout. Use the browser installer to create your first account. Set `APP_URL=http://localhost:8000` in `.env`, then start the development processes:

```sh
composer dev
```

If you are not running a mail catcher, use `MAIL_MAILER=log` in `.env`. Existing `.env` files are preserved, so check the database connection before running setup on an older checkout.

## 3. Work on your change

In a Devcontainer terminal, run tools directly. With host-side Compose development, prefix commands with `docker compose exec app`.

For live frontend updates, start the Vite watcher in a separate terminal:

```sh
# Inside the Devcontainer:
npm run dev

# From a host terminal using Compose:
docker compose exec app npm run dev
```

Use one of these commands for your chosen environment. Apache already serves the application in the container; no additional `php artisan serve` process is needed there. Native `composer dev` already starts Vite.

If you stop Vite and want to return to built assets, run `npm run build`. Remove a leftover `public/hot` file if the browser still tries to contact a watcher that is no longer running.

Follow the existing application structure:

- Keep reusable IP-assignment logic in `app/Services` and panel behavior in `app/Filament`.
- Add meaningful regression tests for changed behavior, including validation, authorization and failure cases where relevant.
- For panel forms and actions, test the affected behavior through Livewire when appropriate.
- Update both English and German translations in `resources/lang` for interface changes.
- Use migrations for schema changes and describe their upgrade implications.
- Update user or developer documentation when the change affects their workflow.

Install existing dependencies with `composer install` and `npm ci`. Update dependencies deliberately and commit the matching lockfile. Avoid `npm audit fix --force`: its proposed major-version changes can downgrade release tooling and introduce more vulnerabilities. Review the [dependency audit notes](SECURITY.md#dependency-audit-notes-22-october-2026) before addressing the outstanding upstream advisory. Never commit `.env`, SSH files, database files, generated vendor assets, `vendor` or `node_modules`.

## 4. Test and format

Every PHPUnit test method must have an English PHPDoc block stating its goal and a separate `Expect:` line describing the observable result. This includes methods marked with `#[Test]`; put their PHPDoc above the attribute. Describe what the assertions actually verify, including relevant validation errors, database changes or response codes.

```php
/**
 * Reject an installer submission with mismatched passwords.
 *
 * Expect: A password validation error is returned and no account is created.
 */
public function test_setup_requires_matching_password_confirmation(): void
{
    // Arrange, act and assert.
}
```

`composer test:docs` checks every test method for both parts. It also runs automatically with `composer test` and in CI.

Always point automated tests at a disposable database. The Compose services inject their development database connection, which takes precedence over the SQLite settings in `phpunit.xml`. Use these explicit overrides to keep your development inventory intact.

**Inside a Devcontainer or a native terminal:**

```sh
DB_CONNECTION=sqlite DB_DATABASE=:memory: composer test
composer lint:fix
composer lint
npm run lint-js
npm run build
composer validate --strict
```

**From the host with Docker Compose:**

```sh
docker compose exec -e DB_CONNECTION=sqlite -e DB_DATABASE=:memory: app composer test
docker compose exec app composer lint:fix
docker compose exec app composer lint
docker compose exec app npm run lint-js
docker compose exec app npm run build
docker compose exec app composer validate --strict
```

For a focused test run inside the container:

```sh
DB_CONNECTION=sqlite DB_DATABASE=:memory: php artisan test tests/Feature/Api/V1
DB_CONNECTION=sqlite DB_DATABASE=:memory: php artisan test --filter=DeviceManagementTest
```

Run the checks relevant to your change. Documentation-only changes need a review of links, examples and instructions rather than application tests. For visible changes, also exercise the affected screen in the browser and include screenshots in your PR.

CI checks PHP 8.4/8.5, the complete suite on SQLite, PostgreSQL and MariaDB, migration rollback, Pint, ESLint, asset compilation, dependency audits, Compose configuration and production-image startup. Database integration tests run against dedicated CI databases. Container changes should also be verified with a production build:

```sh
docker build --target production -t openipam:local .
```

Optional: run `pre-commit install` inside the Devcontainer to enable the repository's local Pint, ESLint, PHPUnit-documentation and Composer-validation hooks. These do not replace CI.

## 5. Keep your branch current

Before opening a PR, fetch upstream changes and rebase your feature branch:

```sh
git fetch upstream
git rebase upstream/main
```

Resolve any conflicts, rerun affected checks and inspect `git diff` before committing. After dependency changes from upstream, rerun `composer install`, `npm ci` and `npm run build` in your development environment. Apply new development migrations with `php artisan migrate`.

## 6. Submit a pull request

Use Conventional Commits for commit messages and the PR title:

```text
feat: add subnet utilization filter
fix: preserve primary IP assignment
docs: clarify the homelab installation
chore(deps): update dependencies
```

Use `!` or a `BREAKING CHANGE:` footer for a breaking change. Choose a title that describes the resulting behavior.

Stage only files belonging to your change, then commit and push your branch:

```sh
git add PATH-TO-CHANGED-FILE
git commit -m "feat: describe your change"
git push -u origin feat/your-change
```

Open a pull request from your fork's feature branch to **`triopsi/OpenIPAM:main`** and complete the PR template. Include:

- The problem and how the change addresses it.
- Related issues, using `Closes #123` when applicable.
- The checks you ran and their results.
- Screenshots for visible interface changes.
- Any migrations, configuration changes or operator actions needed during an upgrade.

Keep the PR focused and respond constructively to feedback. If you rebase a branch that you already pushed, update only your own feature branch with `git push --force-with-lease`.

## Maintainer notes

Pull requests and `develop` run CI directly. Changes pushed to `main` run the same checks through the release workflow before semantic-release creates a version tag and GitHub release. Images are built from the release tag for `linux/amd64` and `linux/arm64` and published to GHCR and Docker Hub (`triopsi/openipam`) with version and `latest` tags. Both registries are part of every image release.

Repository configuration is managed in GitHub:

- Protect `main` with PR review and the CI checks shown on a pull request.
- Use a merge strategy that preserves the Conventional Commit PR title, such as squash merging.
- Allow the release job's `GITHUB_TOKEN` to create releases and publish packages.
- For Docker Hub, set the Action secrets `DOCKERHUB_USERNAME` and `DOCKERHUB_TOKEN`. The token must have write access to `triopsi/openipam`.
- Enable private vulnerability reporting for the reporting link in `SECURITY.md`.

A manual release-workflow run can republish images for an existing stable version after a failed publication. The weekly security audit includes development tooling; known upstream findings are documented in [SECURITY.md](SECURITY.md).
