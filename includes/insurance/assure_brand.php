<?php

require_once __DIR__ . '/assure_version.php';

/**
 * Produktname für AssureWallos (UI, Titel, E-Mails Phase 2).
 */
function assure_app_name(): string
{
    return 'AssureWallos';
}

/**
 * Kurzbeschreibung, wenn kein $i18n-Kontext vorliegt.
 */
function assure_app_tagline_fallback(): string
{
    return 'Insurance & subscriptions';
}

/**
 * Browser-Titel und Logo-tooltip.
 *
 * @param array<string, string>|null $i18n Aktive Übersetzungstabelle
 */
function assure_page_title(?array $i18n = null): string
{
    $tagline = assure_app_tagline_fallback();
    if ($i18n !== null && function_exists('translate')) {
        $translated = translate('assure_app_tagline', $i18n);
        if ($translated !== '[i18n String Missing]') {
            $tagline = $translated;
        }
    }

    return assure_app_name() . ' – ' . $tagline;
}

/**
 * Kurztitel für PWA / Apple „Add to Home Screen“.
 */
function assure_short_title(): string
{
    return assure_app_name();
}

/**
 * Escaped title-Attribut für Logo-Container.
 *
 * @param array<string, string>|null $i18n
 */
function assure_logo_title_attr(?array $i18n = null): string
{
    return htmlspecialchars(assure_page_title($i18n), ENT_QUOTES, 'UTF-8');
}

/**
 * Relativer Pfad zum Branding-Stylesheet (ab Webroot).
 */
function assure_brand_stylesheet_path(): string
{
    return 'styles/insurance/brand.css';
}

/**
 * Relativer Pfad zum kombinierten Logo-Snippet (Wallos-SVG + Assure-Label).
 */
function assure_logo_svg_include_path(): string
{
    return 'includes/insurance/ui/logo_inner.php';
}

/**
 * Pfad zu PNG-Logo (hell), falls <img> statt SVG genutzt wird.
 */
function assure_logo_png_path(): string
{
    return 'images/siteicons/assurewallos.png';
}

/**
 * Pfad zu PNG-Logo (dunkel).
 */
function assure_logo_png_white_path(): string
{
    return 'images/siteicons/assurewalloswhite.png';
}

/**
 * GitHub-URL des Wallos-Upstream-Projekts.
 */
function assure_wallos_upstream_url(): string
{
    return 'https://github.com/ellite/Wallos';
}

/**
 * Wallos-Upstream-Release-Notes für die aktuelle Basisversion.
 */
function assure_wallos_upstream_release_url(): string
{
    global $version;

    $tag = !empty($version) ? (string) $version : 'v' . assure_wallos_base_number();

    return assure_wallos_upstream_url() . '/releases/tag/' . $tag;
}

/**
 * Wallos-Upstream-Issues.
 */
function assure_wallos_upstream_issues_url(): string
{
    return assure_wallos_upstream_url() . '/issues';
}

?>
