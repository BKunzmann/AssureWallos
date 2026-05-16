<?php

require_once __DIR__ . '/assure_brand.php';

/**
 * Absender-Anzeigename für System-E-Mails.
 */
function assure_mail_from_name(): string
{
    return assure_app_name() . ' App';
}

/**
 * Präfix für E-Mail-Betreffzeilen (z. B. „AssureWallos – Reset Password“).
 */
function assure_mail_subject(string $topic): string
{
    return assure_app_name() . ' – ' . $topic;
}

/**
 * HTML-Block mit Assure-Zeile und Wallos-Logo für E-Mail-Bodies.
 */
function assure_mail_logo_html(string $serverUrl): string
{
    $logoUrl = htmlspecialchars(rtrim($serverUrl, '/') . '/images/siteicons/wallos.png', ENT_QUOTES, 'UTF-8');
    $appName = htmlspecialchars(assure_app_name(), ENT_QUOTES, 'UTF-8');

    return '<div style="text-align:center;margin-bottom:12px">'
        . '<p style="margin:0 0 6px;font-family:Barlow,system-ui,sans-serif;font-size:13px;font-weight:700;color:#2858C5">Assure</p>'
        . '<img src="' . $logoUrl . '" alt="' . $appName . '" style="max-width:215px;height:auto" />'
        . '</div>';
}

?>
