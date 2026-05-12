<?php
/**
 * AssureWallos document upload fields injected into the Wallos subscription form.
 */
?>
<div class="form-group hide" id="assure-insurance-documents">
  <label for="ins_documents">Versicherungsdokumente</label>
  <div class="inline">
    <div class="split33">
      <select id="ins_document_type" name="ins_document_type">
        <option value="policy">Police</option>
        <option value="claim">Schadensprotokoll</option>
        <option value="invoice">Rechnung</option>
        <option value="correspondence">Schriftverkehr</option>
        <option value="cancellation">Kündigung</option>
        <option value="other">Sonstiges</option>
      </select>
    </div>
    <div class="split66">
      <input type="file" id="ins_documents" name="ins_documents[]" accept=".pdf,image/png,image/jpeg,image/webp" multiple>
    </div>
  </div>
  <div id="assure-existing-documents"></div>
</div>
