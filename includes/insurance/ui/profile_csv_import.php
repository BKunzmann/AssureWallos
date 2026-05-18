<?php
/**
 * CSV import block on profile page (subscriptions + insurance).
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
<div class="assure-csv-import-block" id="assure-csv-import">
  <h3>CSV-Import (Abos &amp; Versicherung)</h3>
  <p class="assure-field-note">
    <i class="fa-solid fa-circle-info"></i>
    Importiert Verträge aus einer CSV-Datei (Vorschau, dann Bestätigung). Nur neue Datensätze — kein Update.
    <a href="endpoints/insurance/csv_import_template.php" download>Vorlage herunterladen</a>
    ·
    <a href="<?= htmlspecialchars(assure_docs_url('docs/de/CSV-IMPORT.md'), ENT_QUOTES, 'UTF-8') ?>"
      target="_blank" rel="noreferrer">Anleitung</a>
  </p>

  <?php if (!$assure_csv_import_ready): ?>
    <p class="assure-field-note assure-csv-import-disabled">Versicherungsmodul nicht bereit — bitte Datenbank migrieren.</p>
  <?php elseif ($assure_csv_demo): ?>
    <p class="assure-field-note assure-csv-import-disabled">Im Demo-Modus nicht verfügbar.</p>
  <?php else: ?>
    <script type="application/json" id="assure-csv-import-bootstrap"><?php
      echo json_encode(
          ['categories' => $assure_csv_import_categories],
          JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
      );
    ?></script>

    <div class="assure-csv-import-form">
      <div class="form-group">
        <label for="assure_csv_file">CSV-Datei</label>
        <input type="file" id="assure_csv_file" accept=".csv,text/csv" />
      </div>
      <div class="form-group">
        <label for="assure_csv_default_category">Standard-Kategorie (wenn Spalte leer)</label>
        <select id="assure_csv_default_category">
          <option value="">— erste Kategorie —</option>
          <?php foreach ($assure_csv_import_categories as $cat): ?>
            <option value="<?= (int) $cat['id'] ?>"><?= htmlspecialchars($cat['name'], ENT_QUOTES, 'UTF-8') ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="buttons">
        <input type="button" class="secondary-button thin" id="assure_csv_preview_btn" value="Vorschau laden">
        <input type="button" class="thin hide" id="assure_csv_commit_btn" value="Import starten">
        <input type="button" class="secondary-button thin hide" id="assure_csv_cancel_btn" value="Abbrechen">
      </div>
    </div>

    <p id="assure_csv_status" class="assure-field-note" role="status" aria-live="polite" hidden></p>

    <div id="assure_csv_preview_wrap" class="assure-csv-preview-wrap hide">
      <div id="assure_csv_summary" class="assure-csv-summary"></div>
      <label class="form-group-inline assure-csv-duplicate-opt hide" id="assure_csv_duplicate_wrap">
        <input type="checkbox" id="assure_csv_include_duplicates">
        <span class="grow">Zeilen mit Duplikat-Warnung trotzdem importieren</span>
      </label>
      <div class="assure-csv-preview-table-wrap">
        <table class="assure-csv-preview-table">
          <thead>
            <tr>
              <th>Zeile</th>
              <th>Status</th>
              <th>Name</th>
              <th>Versicherungsnr.</th>
              <th>Hinweise</th>
            </tr>
          </thead>
          <tbody id="assure_csv_preview_body"></tbody>
        </table>
      </div>
    </div>
  <?php endif; ?>
</div>
