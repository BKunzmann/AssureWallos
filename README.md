<div align="center">
  <picture>
    <source media="(prefers-color-scheme: dark)" srcset="./images/siteicons/walloswhite.png">
    <source media="(prefers-color-scheme: light)" srcset="./images/siteicons/wallos.png">
    <img alt="AssureWallos" src="./images/siteicons/wallos.png">
  </picture>

  <p><strong>AssureWallos</strong> — Insurance-aware fork of <a href="https://github.com/ellite/Wallos">Wallos</a></p>
  <p>Self-hosted subscription &amp; insurance management (Wallos core + Assure extensions)</p>

  [![GitHub](https://img.shields.io/github/stars/BKunzmann/AssureWallos?style=flat-square)](https://github.com/BKunzmann/AssureWallos)
  [![Docker](https://img.shields.io/docker/pulls/bkunzmann/assurewallos?style=flat-square)](https://hub.docker.com/r/bkunzmann/assurewallos)
  [![Wallos upstream](https://img.shields.io/github/stars/ellite/Wallos?style=flat-square&label=Wallos)](https://github.com/ellite/Wallos)
</div>

## Table of Contents

- [Introduction](#introduction)
- [Features](#features)
- [Installation (Docker Hub)](#installation-docker-hub)
- [Development (local build)](#development-local-build)
- [Usage](#usage)
- [Releases (maintainers)](#releases-maintainers)
- [Based on Wallos](#based-on-wallos)
- [License](#license)
- [Links](#links)

## Introduction

**AssureWallos** is a specialized fork of [Wallos](https://github.com/ellite/Wallos) for households that want to track **subscriptions and insurance policies** in one self-hosted app. It keeps Wallos’ subscription workflow and adds insurance-specific data (taxonomy, documents, form hooks) with minimal changes to the upstream codebase for easier merges.

- **AssureWallos version:** see `includes/insurance/assure_version.php` and the About page in the app.
- **Wallos base version:** see `includes/version.php` (updated on upstream merges).
- **Changelog (fork):** [CHANGELOG-ASSURE.md](CHANGELOG-ASSURE.md)

## Features

**From Wallos (unchanged core):** subscriptions, categories, multi-currency, notifications, OIDC, themes, mobile UI, statistics, and more — see [Wallos](https://github.com/ellite/Wallos).

**AssureWallos additions:**

- Insurance flag and taxonomy on subscriptions
- Upload/storage for insurance documents (`images/uploads/insurance_docs`)
- AssureWallos branding (UI, manifest, e-mail subjects)
- Separate SemVer and Docker images (`bkunzmann/assurewallos`)

## Installation (Docker Hub)

For **end users** who only need a running instance (no local build):

1. Create a directory and copy [`docker-compose.hub.yaml`](docker-compose.hub.yaml) into it (or clone this repo).
2. Create persistent data folders (if missing):

   ```bash
   mkdir -p db logos images/uploads/insurance_docs
   ```

3. Start (image tag must match a published release on Docker Hub):

   ```bash
   docker compose -f docker-compose.hub.yaml pull
   docker compose -f docker-compose.hub.yaml up -d
   ```

4. Open **http://localhost:8283/** (default host port **8283** so a parallel Wallos instance on 8282 does not conflict). Change the port mapping in the compose file if needed.

| Setting | Value |
|--------|--------|
| Image | `bkunzmann/assurewallos:0.1.2` (Docker tag **without** `v`; Git release tag is `v0.1.2`) |
| Branch preview | `bkunzmann/assurewallos:feature-insurance-core` |
| Hub | https://hub.docker.com/r/bkunzmann/assurewallos |

**Update:** bump the `image:` version in `docker-compose.hub.yaml`, then `pull` and `up -d` again. Run migrations in the browser if prompted: `http://your-host:8283/endpoints/db/migrate.php`.

**Advanced configuration (German):** [docs/de/DOCKER-ENDNUTZER.md](docs/de/DOCKER-ENDNUTZER.md) — PUID/PGID, custom data paths, moving the document upload folder, optional healthcheck, troubleshooting. **Security:** [docs/de/SICHERHEIT.md](docs/de/SICHERHEIT.md) — HTTPS, reverse proxy, Synology, hardening.

## Development (local build)

For **developers** on this repository (live code via bind mount):

```bash
docker compose up -d          # or: docker compose up -d --build
docker compose logs -f
```

- Compose file: [`docker-compose.yaml`](docker-compose.yaml) — builds from `Dockerfile`, image `assurewallos:local`, mounts the repo into the container.
- App: **http://localhost:8282/**
- Stop: `docker compose down`

Main branch for Assure work: `feature/insurance-core`.

## Usage

Open the app URL in a browser. On first run, create the admin user, then configure categories, currencies, and (optionally) a [Fixer](https://fixer.io/#pricing_plan) API key in settings — same as Wallos.

## Releases (maintainers)

1. Set `$assure_version` in `includes/insurance/assure_version.php` (SemVer **without** leading `v`, e.g. `0.1.2`).
2. Update `docker-compose.hub.yaml` → `image: bkunzmann/assurewallos:X.Y.Z` (same number, **no** `v` prefix).
3. Add an entry to [CHANGELOG-ASSURE.md](CHANGELOG-ASSURE.md).
4. Commit, then create and push the Git tag (triggers CI build to Docker Hub):

   ```bash
   ./scripts/docker-release-tag.sh X.Y.Z
   git push origin vX.Y.Z
   ```

5. GitHub Actions (`.github/workflows/assurewallos-docker.yaml`) needs secrets `DOCKERHUB_USERNAME` and `DOCKERHUB_TOKEN` (Docker Hub access token with Read & Write).

Published image tags follow the Git tag: `v0.1.2` → Docker tags `0.1.2`, `0.1`, and optionally `latest`.

## Based on Wallos

AssureWallos is a fork; copyright and license for the Wallos core remain with the original authors. We keep Wallos credits and GPLv3 notices in the app (About page).

| Topic | Where |
|--------|--------|
| Upstream repo | https://github.com/ellite/Wallos |
| Upstream Docker | `bellamy/wallos` |
| Bare-metal install | [Wallos README — Baremetal](https://github.com/ellite/Wallos#baremetal) (same PHP/nginx steps apply to a manual AssureWallos deploy) |
| API docs | https://api.wallosapp.com/ |
| Wallos demo | https://demo.wallosapp.com (Wallos only, not AssureWallos) |

OIDC, screenshots, translations, and contributing guidelines for the **Wallos core** are described in the upstream project. Assure-specific changes belong in this repo under `includes/insurance/` and `// START ASSUREWALLOS MOD` hooks.

## License

This project is licensed under the [GNU General Public License, Version 3](LICENSE.md) — see [LICENSE.md](LICENSE.md) file for details (inherited from Wallos).

## Links

- AssureWallos: https://github.com/BKunzmann/AssureWallos
- Wallos upstream: https://github.com/ellite/Wallos · https://wallosapp.com
- Docker Hub (AssureWallos): https://hub.docker.com/r/bkunzmann/assurewallos
