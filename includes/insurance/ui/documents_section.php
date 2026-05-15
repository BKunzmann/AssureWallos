<?php
/**
 * AssureWallos document upload fields injected into the Wallos subscription form.
 * Layout: Stack mit gleichem vertikalen Rhythmus wie Wallos .account-settings-list (gap),
 * keine verschachtelten .form-group, damit keine Margin-Doppelungen entstehen.
 */
?>
<div class="form-group hide assure-section" id="assure-insurance-documents">
  <div class="assure-section-heading">
    <h4>Versicherungsdokumente</h4>
  </div>
  <div class="assure-document-stack">
    <div class="assure-document-stack-block">
      <label for="document_url">Externer Dokumentlink</label>
      <div class="assure-link-field">
        <input type="text" id="document_url" name="document_url" autocomplete="off" placeholder="https://">
        <a href="#" target="_blank" rel="noopener noreferrer" data-assure-link-for="document_url" class="hide">Öffnen</a>
      </div>
    </div>
    <div class="inline">
      <div class="split33">
        <label for="ins_document_type">Dokumenttyp</label>
        <select id="ins_document_type" name="ins_document_type">
          <option value="policy">Police</option>
          <option value="claim">Schadensprotokoll</option>
          <option value="invoice">Rechnung</option>
          <option value="correspondence">Schriftverkehr</option>
          <option value="cancellation">Kündigung</option>
          <option value="other">Sonstiges</option>
        </select>
      </div>
      <div class="split66 assure-document-files-wrap">
        <label for="ins_documents">Dateien</label>
        <div class="form-group-inline assure-document-upload-row">
          <div class="grow assure-document-file-input-wrap">
            <input type="file" id="ins_documents" name="ins_documents[]" accept=".pdf,image/png,image/jpeg,image/webp" multiple>
          </div>
          <div class="assure-document-upload-toolbar">
            <span id="assure-document-upload-status" class="assure-document-upload-status" role="status" aria-live="polite" hidden>Wird hochgeladen…</span>
            <button type="button" id="assure-document-upload-btn" class="secondary-button thin" hidden>Hochladen</button>
          </div>
        </div>
      </div>
      <template id="assure-insurance-delete-icon-svg"><?php include __DIR__ . '/../../../images/siteicons/svg/delete.php'; ?></template>
    </div>
    <div id="assure-existing-documents" class="assure-document-entries" aria-live="polite"></div>
  </div>
</div>
