<?php
/**
 * AssureWallos admin settings for Paperless-ngx read-only archive (instance-wide).
 */

if (!isset($userId) || (int) $userId !== 1) {
    return;
}

require_once __DIR__ . '/../ins_paperless_config.php';
require_once __DIR__ . '/../assure_version.php';

$assure_paperless_settings_public = [];
if (isset($db) && Ins_Paperless_Config::insPaperlessReady($db)) {
    $assure_paperless_settings_public = Ins_Paperless_Config::insPublicSettings(
        Ins_Paperless_Config::insLoad($db)
    );
}
?>
<section class="account-section" id="assure-paperless-settings">
  <header>
    <h2>Paperless-ngx</h2>
  </header>
  <div class="account-settings-list">
    <div class="settings-notes">
      <p>
        <i class="fa-solid fa-circle-info"></i>
        Zeigt archivierte Dokumente aus Paperless im Versicherungsformular an (nur Lesen, kein Upload aus AssureWallos).
        <a href="<?= htmlspecialchars(assure_docs_url('docs/de/PAPERLESS-ADMIN.md'), ENT_QUOTES, 'UTF-8') ?>"
          target="_blank" rel="noreferrer">Einrichtungsanleitung (Admin)</a>
        ·
        <a href="<?= htmlspecialchars(assure_docs_url('docs/de/PAPERLESS-BENUTZER.md'), ENT_QUOTES, 'UTF-8') ?>"
          target="_blank" rel="noreferrer">Benutzerhilfe</a>
      </p>
    </div>

    <script type="application/json" id="assure-settings-paperless-bootstrap"><?php
      echo json_encode(
          $assure_paperless_settings_public,
          JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
      );
    ?></script>

    <div class="form-group-inline">
      <input type="checkbox" id="assure_paperless_enabled" name="assure_paperless_enabled">
      <label for="assure_paperless_enabled" class="grow">Paperless-Archiv in Versicherungen anzeigen</label>
    </div>

    <div class="form-group">
      <label for="assure_paperless_base_url">Basis-URL</label>
      <input type="text" id="assure_paperless_base_url" name="assure_paperless_base_url" autocomplete="off"
        inputmode="url" spellcheck="false" placeholder="https://paperless.example.lan">
      <span class="assure-field-note">Ohne abschließenden Schrägstrich. Bei Docker im LAN: Hostname in der Webhook-Allowlist (Administration) eintragen.</span>
    </div>

    <div class="form-group">
      <label for="assure_paperless_api_token">API-Token</label>
      <input type="password" id="assure_paperless_api_token" name="assure_paperless_api_token" autocomplete="off"
        placeholder="Token aus Paperless → Mein Profil">
      <span id="assure_paperless_token_indicator" class="assure-field-note assure-paperless-token-ok" hidden></span>
    </div>

    <div class="assure-paperless-match-block">
      <h3 class="assure-paperless-subheading">Dokumente zuordnen</h3>

      <div class="form-group assure-paperless-match-header">
        <label for="assure_paperless_match_mode">Zuordnung</label>
        <select id="assure_paperless_match_mode" name="assure_paperless_match_mode">
          <option value="custom_field">Custom Field — Vertrags-ID (empfohlen)</option>
          <option value="tag">Tag — Vertrags-ID</option>
          <option value="search">Volltext — Versicherungsnummer</option>
        </select>
        <span class="assure-field-note">Vertrags-ID = interne Abonnement-ID in AssureWallos (nicht die Versicherungsnummer im Formular).</span>
      </div>

      <div class="assure-paperless-match-panels">
        <div id="assure_paperless_panel_custom_field" class="assure-paperless-match-panel">
          <label for="assure_paperless_custom_field_name">Name des Custom Fields in Paperless</label>
          <input type="text" id="assure_paperless_custom_field_name" name="assure_paperless_custom_field_name"
            autocomplete="off" placeholder="assure_subscription_id">
          <p class="assure-paperless-mode-help">In Paperless ein Custom Field anlegen (z.&nbsp;B. Typ <strong>Integer</strong>). Pro Dokument den Wert = <strong>Vertrags-ID</strong> setzen (steht im Versicherungsformular unter „Dies ist eine Versicherung“).</p>
        </div>

        <div id="assure_paperless_panel_tag" class="assure-paperless-match-panel hide">
          <label for="assure_paperless_tag_prefix">Tag-Präfix</label>
          <input type="text" id="assure_paperless_tag_prefix" name="assure_paperless_tag_prefix"
            autocomplete="off" placeholder="aw-sub-">
          <p class="assure-paperless-mode-help">Jedes Dokument erhält einen Tag <code>{Präfix}{Vertrags-ID}</code>, z.&nbsp;B. <code>aw-sub-42</code> bei Vertrags-ID 42. Es wird kein Tag „enthält Versicherungsnummer“ gesucht — der Name muss exakt passen.</p>
        </div>

        <div id="assure_paperless_panel_search" class="assure-paperless-match-panel hide">
          <p class="assure-paperless-mode-help">Sucht in Paperless in <strong>Titel und Dokumentinhalt (OCR)</strong> nach dem Feld <strong>Versicherungsnummer</strong> aus dem Versicherungsformular. Keine Toleranz für Leerzeichen/Bindestriche — der Text muss in Paperless vorkommen.</p>
        </div>
      </div>

      <div id="assure_paperless_fallback_wrap" class="form-group-inline assure-paperless-fallback">
        <input type="checkbox" id="assure_paperless_use_fallback" name="assure_paperless_use_fallback">
        <label for="assure_paperless_use_fallback" class="grow">Zusätzlich nach Versicherungsnummer suchen, wenn obige Zuordnung keine Treffer liefert</label>
      </div>
    </div>

    <div class="assure-paperless-cache-block form-group">
      <h3 class="assure-paperless-subheading">Leistung</h3>
      <label for="assure_paperless_cache_ttl">Cache (Sekunden, 0 = aus)</label>
      <input type="number" id="assure_paperless_cache_ttl" name="assure_paperless_cache_ttl" min="0" max="86400" value="300">
      <span class="assure-field-note">Zwischenspeicher der Paperless-Liste pro Vertrag, um wiederholte API-Aufrufe zu reduzieren.</span>
    </div>

    <div class="buttons">
      <input type="button" class="secondary-button thin" id="assure_paperless_test" value="Verbindung testen">
      <input type="button" class="thin" id="assure_paperless_save" value="Paperless-Einstellungen speichern">
    </div>
    <p id="assure-paperless_status" class="assure-field-note" role="status" aria-live="polite" hidden></p>
  </div>
</section>
