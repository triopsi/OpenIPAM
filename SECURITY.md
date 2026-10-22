# Security

## Supported versions

Security fixes target the latest release from `main`. Older releases and the pre-modernization stack should be upgraded after backing up data and the application key.

## Report a vulnerability

Please use [GitHub private vulnerability reporting](https://github.com/triopsi/OpenIPAM/security/advisories/new) if enabled. If the form is unavailable, contact the maintainer through the contact details on their GitHub profile to arrange a private report. Do not post exploit details, credentials or private network inventory in a public issue.

Include the affected version or commit, reproduction steps, impact and a minimal example using fictional data. No response deadline is promised.

## Deployment model

OpenIPAM currently assumes trusted users. API self-registration is public, authenticated users can access the panel and there is no enforced administrative role boundary. Restrict access with a VPN or an access-controlled reverse proxy. Panel email 2FA does not extend to API token authentication. Treat API tokens as credentials.

The first-account installer is available only while the users table is empty. Complete it on your trusted network before exposing a fresh instance. Preserve the generated `storage/app.key` (or your explicit `APP_KEY`), configure HTTPS and real SMTP, and back up database and storage. Development credentials are examples for isolated local setups. See the [deployment guide](docs/deployment.md) for the production configuration and upgrade process.

## Dependency audit notes (22 October 2026)

The upgraded Composer lockfile and npm production dependencies have no known advisories in the current audit. After restoring semantic-release 25 and its compatible plugins, the full npm development audit reports seven high-severity package entries, all rooted in the same advisory: `GHSA-vfj7-8cjw-p6xm` through semantic-release → micromatch → braces. There are no critical or moderate findings in the current audit. [The upstream advisory](https://github.com/advisories/GHSA-vfj7-8cjw-p6xm) lists no patched release. It concerns stack exhaustion while processing deeply nested glob patterns. Release branch/rule patterns are repository-controlled; keep changes to release configuration subject to review. Node tooling is absent from the production image.

CI blocks on Composer and npm production dependency audits. The weekly workflow also audits the complete npm tree and will report this outstanding development-tool advisory until upstream provides a fix. Do not use `npm audit fix --force` to downgrade the release tooling to obsolete versions. The current npm suggestion selects semantic-release 15 and older plugins; that dependency tree brings back dozens of advisories, including critical ones. `npm update` cannot correct this downgrade because it stays within the version ranges in `package.json`. Restore the supported major versions deliberately and commit both `package.json` and `package-lock.json`.
