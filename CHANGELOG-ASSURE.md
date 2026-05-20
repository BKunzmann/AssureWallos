# AssureWallos Changelog

Versionsnummern folgen [Semantic Versioning](https://semver.org/) für **AssureWallos** nur.
Die Wallos-Basisversion steht in `includes/version.php` und wird bei Upstream-Merges angepasst.

## [0.3.0] - 2026-05-19

### Added

- **Vertragstabellenansicht** (`subscriptions_table.php`): Spalten-Registry mit ~40 Spalten, freie Spaltenwahl, erweiterte Versicherungsfelder.
- **Sortierung und Gruppierung** über Spaltenköpfe (Klick + Menü); Gruppierung mit Summenzeilen; erweiterte Filter (Versicherungsgruppe/-art, Vertragsstatus) im Filter-Dropdown.
- **Benannte Ansichten (Presets):** Sort, Gruppe und Spalten pro Nutzer in der DB (`assure_user_table_presets`), einmalige Übernahme aus `localStorage`.
- Migration `9999_99_08_assure_table_view_presets.php`.
- Tabellen-**CSV/PDF-Export** mit Gruppenzeilen; erweiterte **Batch-Aktionen** in der Tabellenansicht.

### Changed

- `docker-compose.hub.yaml`: Image-Tag **0.3.0**.

## [0.2.1] - 2026-05-19

### Added

- CSV-**Export** im AssureWallos-Format (`csv_export.php`) für Roundtrip mit dem Import.
- Import: Textkodierung wählbar, erweiterte Preis-/Datums-Parsing, optionale Vorschau-Spalten.

### Fixed

- Abo-Listen und Statistik bei **leerem Zahler** (`payer_user_id`) nach CSV-Import (keine PHP-Warnungen mehr).

### Changed

- `docker-compose.hub.yaml`: Image-Tag **0.2.1**.

## [0.2.0] - 2026-05-19

### Added

- **Paperless-ngx** (read-only): Archivierte Dokumente im Versicherungsformular anzeigen (Custom Field, Tag oder Volltext); Admin-Einstellungen, Vorschau-Thumbnails, Liste/Vorschau-Umschalter.
- Migration `9999_99_07_assure_paperless_settings.php` (Instanz-Konfiguration + optionaler Cache).
- **CSV-Import** (Profil): Abonnements inkl. Versicherungsfelder importieren (Vorschau → Bestätigung, nur neue Datensätze).
- Doku (DE): `docs/de/PAPERLESS-ADMIN.md`, `docs/de/PAPERLESS-BENUTZER.md`, `docs/de/CSV-IMPORT.md`; Verweise in README und About-Seite.

### Changed

- `docker-compose.hub.yaml`: Image-Tag **0.2.0**.

## [0.1.2] - 2026-05-16

### Changed

- README: AssureWallos-Installation (Docker Hub), Entwicklung, Release-Checkliste; Verweis auf Wallos-Upstream.
- `docker-compose.hub.yaml`: Standard-Host-Port **8283** (Parallelbetrieb mit Wallos auf 8282).
- CI Docker Hub: Build nur noch bei Git-Tag `v*` oder `workflow_dispatch`, nicht bei jedem Branch-Push.

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
