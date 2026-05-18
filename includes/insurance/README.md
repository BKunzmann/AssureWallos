# AssureWallos Insurance Module

This directory contains the insurance extension for AssureWallos.

Wallos core continues to own the `subscriptions` table and subscription lifecycle. The insurance module only adds a small `is_insurance` flag to `subscriptions`; all insurance-specific fields and documents are stored in `assure_*` tables.

Core interaction points:

- `hooks/after_save.php` is called by `endpoints/subscription/add.php` after Wallos creates or updates a subscription.
- `hooks/after_load.php` is called by `endpoints/subscription/get.php` before the edit form JSON is returned.
- `ui/form_fields.php` and `ui/documents_section.php` are included by `subscriptions.php` inside the existing subscription form.

Keep future insurance logic in this directory and expose it through small `assure_*` hook functions.

## Paperless-ngx (read-only)

AssureWallos **lädt keine Dateien** nach Paperless hoch. Im Versicherungsformular werden archivierte Dokumente aus einer Paperless-Instanz angezeigt (Deep-Link in die Paperless-UI).

- Konfiguration (Admin, User-ID 1): Einstellungen → Abschnitt **Paperless-ngx**
- Code: `ins_paperless_config.php`, `ins_paperless_client.php`, `ins_paperless_matcher.php`
- Endpoints: `endpoints/insurance/paperless_documents.php`, `paperless_thumb.php`, `paperless_test.php`, `save_paperless_settings.php`
- Migration: `migrations/9999_99_07_assure_paperless_settings.php`
- **Endnutzer-Doku (DE):** `docs/de/PAPERLESS-ADMIN.md`, `docs/de/PAPERLESS-BENUTZER.md`

**Zuordnung in Paperless (eine Variante wählen):**

1. **Custom Field** (empfohlen): Feld z. B. `assure_subscription_id` (Integer) = Abonnement-ID in AssureWallos
2. **Tag:** `aw-sub-{id}` (Präfix in den Einstellungen konfigurierbar)
3. **Volltext:** Suche nach `policy_number`; optional Fallback wenn Custom Field/Tag keine Treffer liefern

Bei Paperless im privaten Netz: Hostname in der Wallos-**Webhook-Allowlist** (Administration) eintragen (SSRF-Schutz).

## Versioning

- AssureWallos release: `assure_version.php` (`$assure_version`, SemVer).
- Wallos upstream: `includes/version.php` (only bump on merge from ellite/Wallos).
- Changelog: `CHANGELOG-ASSURE.md` at repo root.
- About page: `ui/about_assure.php`, `ui/about_upstream.php` (included from `about.php`).
- Branding: `assure_brand.php`, `assure_mail_brand.php`, `ui/head_brand.php`, `ui/logo_inner.php` (Assure-Label + Wallos-SVG), `styles/insurance/brand.css`.
