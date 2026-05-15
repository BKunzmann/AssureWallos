<?php
/**
 * AssureWallos settings block for user-editable insurance taxonomy (Wallos-Währungs-UX).
 */

require_once __DIR__ . '/../ins_repository.php';

$assure_settings_taxonomy_bootstrap = [];
if (isset($db, $userId) && Ins_Repository::insTaxonomyReady($db)) {
    $assure_settings_taxonomy_bootstrap = Ins_Repository::insLoadTaxonomy($db, (int) $userId);
}
?>
<section class="account-section" id="assure-insurance-settings">
  <header>
    <h2>AssureWallos</h2>
  </header>
  <div class="account-settings-list">
    <div class="settings-notes">
      <p>
        <i class="fa-solid fa-circle-info"></i>
        Versicherungsgruppen und -arten wie Währungen bearbeiten: Namen speichern oder Zeile löschen. Mitgelieferte Standardwerte können ebenfalls geändert oder entfernt werden.
      </p>
    </div>
    <script type="application/json" id="assure-settings-taxonomy-bootstrap"><?php
      echo json_encode(
          $assure_settings_taxonomy_bootstrap,
          JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
      );
    ?></script>
    <div class="account-currencies assure-insurance-taxonomy">
      <h3>Versicherungsgruppen</h3>
      <div id="assure-settings-groups"></div>
      <div class="buttons">
        <input type="submit" class="thin mobile-grow" id="assure_settings_add_group" value="<?= htmlspecialchars(translate('add', $i18n), ENT_QUOTES, 'UTF-8') ?>">
      </div>
      <h3>Versicherungsarten</h3>
      <div id="assure-settings-types"></div>
      <div class="buttons">
        <input type="submit" class="thin mobile-grow" id="assure_settings_add_type" value="<?= htmlspecialchars(translate('add', $i18n), ENT_QUOTES, 'UTF-8') ?>">
      </div>
    </div>
  </div>
</section>
