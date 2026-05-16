<?php
/**
 * <title>, PWA-Kurzname und Branding-Stylesheet.
 * Erwartet: assure_brand.php geladen; optional $i18n, $version.
 */
$assureTitle = htmlspecialchars(assure_page_title(isset($i18n) ? $i18n : null), ENT_QUOTES, 'UTF-8');
$assureShort = htmlspecialchars(assure_short_title(), ENT_QUOTES, 'UTF-8');
$assureBrandCssVersion = isset($version) ? (string) $version : assure_version_tag();
?>
  <title><?= $assureTitle ?></title>
  <meta name="apple-mobile-web-app-title" content="<?= $assureShort ?>">
  <link rel="stylesheet" href="styles/insurance/brand.css?<?= htmlspecialchars($assureBrandCssVersion, ENT_QUOTES, 'UTF-8') ?>">
