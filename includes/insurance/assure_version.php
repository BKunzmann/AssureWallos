<?php

/**
 * AssureWallos release version (SemVer, ohne führendes „v“).
 * Nur bei AssureWallos-Releases erhöhen — nicht bei reinen Wallos-Upstream-Merges.
 */
$assure_version = '0.1.2';

/** GitHub-Repository für AssureWallos-Releases (owner/repo). */
$assure_github_repo = 'BKunzmann/AssureWallos';

/**
 * AssureWallos-Versionsnummer ohne „v“ (z. B. 0.1.0).
 */
function assure_version_number(): string
{
    global $assure_version;

    return ltrim((string) $assure_version, 'v');
}

/**
 * Git-Tag / Release-Label (z. B. v0.1.0).
 */
function assure_version_tag(): string
{
    return 'v' . assure_version_number();
}

/**
 * Wallos-Upstream-Version aus includes/version.php ($version), sonst 0.0.0.
 */
function assure_wallos_base_number(): string
{
    global $version;

    if (!empty($version)) {
        return ltrim((string) $version, 'v');
    }

    return '0.0.0';
}

/**
 * Anzeige für About-Seite und Logs (z. B. AssureWallos 0.1.0 (Wallos 4.8.4)).
 */
function assure_version_display(): string
{
    return 'AssureWallos ' . assure_version_number() . ' (Wallos ' . assure_wallos_base_number() . ')';
}

/**
 * URL zu den Release Notes auf GitHub.
 */
function assure_release_notes_url(): string
{
    return 'https://github.com/' . assure_github_repo() . '/releases/tag/' . assure_version_tag();
}

/**
 * GitHub-Repository-Slug für AssureWallos.
 */
function assure_github_repo(): string
{
    global $assure_github_repo;

    return (string) $assure_github_repo;
}

/**
 * URL zur Issue-Liste des AssureWallos-Repos.
 */
function assure_issues_url(): string
{
    return 'https://github.com/' . assure_github_repo() . '/issues';
}

?>
