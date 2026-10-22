# Contributing to OpenIPAM

Contributions to code, translations, tests and documentation are welcome. For larger changes, open an issue describing the problem and proposed behavior first.

## Set up a workspace

Fork [triopsi/OpenIPAM](https://github.com/triopsi/OpenIPAM), clone your fork and create a branch from `main`:

```sh
git clone https://github.com/YOUR-USERNAME/OpenIPAM.git
cd OpenIPAM
git remote add upstream https://github.com/triopsi/OpenIPAM.git
git switch -c feat/your-change
```

Follow the [development guide](docs/development.md) for Docker, MariaDB, Devcontainer or native setup. Use fictional inventory data in examples, issues and tests.

Optional local commit checks: run `pre-commit install` in the Devcontainer. The development image includes pre-commit; `.pre-commit-config.yaml` runs Pint, ESLint and Composer validation on matching staged files. CI remains the required verification.

Please follow our [community conduct](CODE_OF_CONDUCT.md).

## Make a change

- Follow the existing Laravel and Filament structure; put shared assignment logic in services.
- Add meaningful tests for behavior changes, including validation and failure cases. For Filament changes, cover the relevant save or action through Livewire.
- Update English and German translations for interface changes.
- Commit `composer.lock` and `package-lock.json` when changing dependencies. Install with `composer install` and `npm ci`; do not update packages during ordinary setup.
- Update documentation when configuration or behavior changes.
- Keep credentials, `.env`, database files, generated Filament assets, `vendor` and `node_modules` out of commits.

Before submitting, run the relevant tests and checks inside the container (omit the prefix in a Devcontainer terminal):

```sh
docker compose exec app composer test
docker compose exec app composer lint
docker compose exec app npm run lint-js
docker compose exec app npm run build
```

Format PHP using `composer lint:fix`. Pure documentation changes do not require application tests. Container changes should also pass the production build and smoke test described by `.github/workflows/ci.yml`.

## Submit a pull request

Push your branch to your fork and open a pull request against `triopsi/OpenIPAM:main`. Explain the problem, the resulting behavior, tests and any migration steps. Link relevant issues and include screenshots for visible changes. Use the pull request template.

Use Conventional Commits for the PR title and commits:

```text
feat: add subnet utilization filter
fix: preserve primary IP assignment
docs: clarify MariaDB setup
chore(deps): update Laravel dependencies
```

A breaking change uses `feat!:` or a `BREAKING CHANGE:` footer. Keep changes focused and respond constructively to review feedback.

## Maintainer setup and releases

`main` runs the reusable CI workflow through `release.yml`; pull requests and `develop` run CI directly. Checks include tests on PHP 8.4/8.5, database integration, formatting, frontend compilation, audits and a production image smoke test. A weekly audit catches new advisories. Dependabot proposes Composer, npm, Docker, Compose and GitHub Actions updates.

After successful checks on `main`, semantic-release determines the version from Conventional Commits, creates a Git tag and GitHub release, then builds `linux/amd64` and `linux/arm64` images from that release tag. GHCR receives version and `latest` tags. No release ZIP or credentials are bundled. A manual workflow run on `main` can retry image publication for an existing release after inspecting the failed run.

Repository configuration:

- Allow Actions to create releases and publish packages with the repository `GITHUB_TOKEN`.
- Set repository variable `DOCKERHUB_USERNAME` and secret `DOCKERHUB_TOKEN` only if Docker Hub publication is wanted. GHCR requires no additional registry secret.
- Set branch protection/rulesets for `main`: require PR review and the CI checks shown on a pull request. Configure this in GitHub; repository files do not enable protection automatically.
- Choose a merge strategy that retains a Conventional Commit title, such as squash merging.
- Enable private vulnerability reporting before using the reporting link in `SECURITY.md`.

Tags and images are published by GitHub Actions after merging; local development does not publish releases. See [deployment](docs/deployment.md) for operator instructions.
