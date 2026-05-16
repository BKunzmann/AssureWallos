# AssureWallos Insurance Module

This directory contains the insurance extension for AssureWallos.

Wallos core continues to own the `subscriptions` table and subscription lifecycle. The insurance module only adds a small `is_insurance` flag to `subscriptions`; all insurance-specific fields and documents are stored in `assure_*` tables.

Core interaction points:

- `hooks/after_save.php` is called by `endpoints/subscription/add.php` after Wallos creates or updates a subscription.
- `hooks/after_load.php` is called by `endpoints/subscription/get.php` before the edit form JSON is returned.
- `ui/form_fields.php` and `ui/documents_section.php` are included by `subscriptions.php` inside the existing subscription form.

Keep future insurance logic in this directory and expose it through small `assure_*` hook functions.

## Versioning

- AssureWallos release: `assure_version.php` (`$assure_version`, SemVer).
- Wallos upstream: `includes/version.php` (only bump on merge from ellite/Wallos).
- Changelog: `CHANGELOG-ASSURE.md` at repo root.
- About page: `ui/about_version.php` (included from `about.php`).
