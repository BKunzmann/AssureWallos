# AssureWallos Changelog

Versionsnummern folgen [Semantic Versioning](https://semver.org/) für **AssureWallos** nur.
Die Wallos-Basisversion steht in `includes/version.php` und wird bei Upstream-Merges angepasst.

## [0.1.2] - 2026-05-16

### Changed

- README: AssureWallos-Installation (Docker Hub), Entwicklung, Release-Checkliste; Verweis auf Wallos-Upstream.
- `docker-compose.hub.yaml`: Standard-Host-Port **8283** (Parallelbetrieb mit Wallos auf 8282).

### Added

- Endnutzer-Doku: `docs/de/DOCKER-ENDNUTZER.md` (PUID/PGID, Datenpfade, Upload-Umzug, Healthcheck, Fehlerbehebung).
- `docs/de/SICHERHEIT.md` (Reverse Proxy, HTTPS, Synology, Firewall, App-Hardening).
- Logo: „Assure“ oben links über dem originalen Wallos-SVG (`logo_inner.php`, `brand.css`).
- Phase 2: `manifest.json` (AssureWallos), E-Mail-Branding (`assure_mail_brand.php`).
- Docker Hub CI (`assurewallos-docker.yaml`), `docker-compose.hub.yaml`, Release-Skript `scripts/docker-release-tag.sh`.

## [0.1.1] - 2026-05-16

### Added

- Branding Phase 1: `assure_brand.php`, AssureWallos-Logo (SVG), Titel/Logo in Header und Auth-Seiten.
- About-Seite: AssureWallos- und Wallos-Upstream-Abschnitte getrennt.

## [0.1.0] - 2026-05-16

### Added

- Zwei-Ebenen-Versionierung: `includes/insurance/assure_version.php` und Anzeige auf der About-Seite.
- Insurance-Core (Versicherungs-Flag, Taxonomie, Dokumente, Formular-Hooks).
