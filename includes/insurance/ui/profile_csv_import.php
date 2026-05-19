<?php
/**
 * AssureWallos CSV export/import — Profil Konto, Blöcke wie Wallos (h3 + form-group + settings-notes).
 */

require_once __DIR__ . '/../ins_repository.php';
require_once __DIR__ . '/../assure_version.php';

$assure_csv_import_ready = isset($db, $userId) && Ins_Repository::insSchemaReady($db);
$assure_csv_import_categories = [];

if ($assure_csv_import_ready) {
    require_once dirname(__DIR__, 2) . '/getdbkeys.php';
    foreach ($categories as $id => $row) {
        $assure_csv_import_categories[] = [
            'id' => (int) $id,
            'name' => (string) ($row['name'] ?? ''),
        ];
    }
}

$assure_csv_demo = !empty($demoMode);
?>
<div>
  <h3>AssureWallos: Abos &amp; Versicherung exportieren</h3>
  <?php if (!$assure_csv_import_ready): ?>
    <div class="settings-notes">
      <p>Versicherungsmodul nicht bereit — bitte Datenbank migrieren.</p>
    </div>
  <?php elseif ($assure_csv_demo): ?>
    <div class="settings-notes">
      <p>Im Demo-Modus nicht verfügbar.</p>
    </div>
  <?php else: ?>
    <div class="form-group-inline wrap">
      <input type="button" value="Als CSV exportieren" class="secondary-button thin mobile-grow"
        onclick="window.location.assign('endpoints/insurance/csv_export.php')">
    </div>
    <div class="settings-notes">
      <p>
        <i class="fa-solid fa-circle-info"></i>
        Enthält alle Abo- und Versicherungsfelder (eigenes Format, nicht der Wallos-Export oben).
        <a href="<?= htmlspecialchars(assure_docs_url('docs/de/CSV-IMPORT.md'), ENT_QUOTES, 'UTF-8') ?>"
          target="_blank" rel="noreferrer">Anleitung</a>
      </p>
    </div>
  <?php endif; ?>
</div>

<?php if ($assure_csv_import_ready && !$assure_csv_demo): ?>
<div id="assure-csv-import">
  <h3>AssureWallos: Abos &amp; Versicherung importieren</h3>
  <div class="settings-notes">
    <p>
      <i class="fa-solid fa-circle-info"></i>
      Vorschau, dann Bestätigung. Nur neue Verträge — kein Update.
      <a href="endpoints/insurance/csv_import_template.php" download>Import-Vorlage herunterladen</a>
    </p>
  </div>

  <script type="application/json" id="assure-csv-import-bootstrap"><?php
    echo json_encode(
        ['categories' => $assure_csv_import_categories],
        JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
    );
  ?></script>

  <div class="form-group">
    <label for="assure_csv_file">CSV-Datei</label>
    <input type="file" id="assure_csv_file" accept=".csv,text/csv" />
  </div>
  <div class="form-group">
    <label for="assure_csv_encoding">Textkodierung der CSV-Datei</label>
    <select id="assure_csv_encoding" class="thin">
      <option value="auto">Automatisch (empfohlen)</option>
      <option value="utf-8">UTF-8</option>
      <option value="windows-1252">Windows / Excel (Deutsch)</option>
    </select>
  </div>
  <div class="settings-notes">
    <p>Bei kaputten Umlauten: „Windows / Excel“ wählen oder Datei als „CSV UTF-8“ speichern.</p>
  </div>
  <div class="form-group">
    <label for="assure_csv_default_category">Standard-Kategorie (wenn Spalte leer)</label>
    <select id="assure_csv_default_category" class="thin">
      <option value="">— erste Kategorie —</option>
      <?php foreach ($assure_csv_import_categories as $cat): ?>
        <option value="<?= (int) $cat['id'] ?>"><?= htmlspecialchars($cat['name'], ENT_QUOTES, 'UTF-8') ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="form-group-inline wrap">
    <input type="button" class="secondary-button thin mobile-grow" id="assure_csv_preview_btn" value="Vorschau laden">
  </div>

  <p id="assure_csv_status" class="assure-field-note" role="status" aria-live="polite" hidden></p>

  <div id="assure_csv_preview_wrap" class="assure-csv-preview-wrap" hidden>
    <div id="assure_csv_summary" class="assure-csv-summary settings-notes"></div>
    <div class="assure-csv-preview-options">
      <div class="form-group-inline assure-csv-option-row hide" id="assure_csv_duplicate_wrap" hidden>
        <input type="checkbox" id="assure_csv_include_duplicates">
        <label for="assure_csv_include_duplicates">Duplikat-Warnungen trotzdem importieren</label>
      </div>
      <div class="form-group-inline assure-csv-option-row">
        <input type="checkbox" id="assure_csv_show_extra_cols">
        <label for="assure_csv_show_extra_cols">Weitere Spalten anzeigen (Zyklus, Datum, Versicherungsart)</label>
      </div>
    </div>
    <div class="assure-csv-preview-table-wrap">
      <table class="assure-csv-preview-table">
        <thead>
          <tr>
            <th scope="col" class="assure-csv-col-line notranslate" translate="no">Zeile</th>
            <th scope="col">Status</th>
            <th scope="col">Name</th>
            <th scope="col">Preis</th>
            <th scope="col">Kategorie</th>
            <th scope="col" class="assure-csv-col-extra hide">Zyklus</th>
            <th scope="col" class="assure-csv-col-extra hide">N.&nbsp;Zahlung</th>
            <th scope="col" class="assure-csv-col-extra hide">Versicherungsart</th>
            <th scope="col">Versicherungsnr.</th>
              <th scope="col" class="assure-csv-col-messages">Hinweise</th>
          </tr>
        </thead>
        <tbody id="assure_csv_preview_body"></tbody>
      </table>
    </div>
    <div class="form-group-inline wrap hide" id="assure_csv_actions_wrap" hidden>
      <input type="button" class="thin mobile-grow" id="assure_csv_commit_btn" value="Import starten">
      <input type="button" class="secondary-button thin mobile-grow" id="assure_csv_cancel_btn" value="Abbrechen">
    </div>
  </div>
</div>
<?php endif; ?>
