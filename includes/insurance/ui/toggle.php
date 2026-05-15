<?php
/**
 * AssureWallos toggle that decides whether a Wallos subscription should be treated as an insurance.
 * Kept as a separate include so the Wallos field flow (price -> cycle -> dates -> ...) stays intact.
 * Synchronisation mit der Kategorie „Versicherung“: scripts/insurance/form_toggle.js (assureRegisterCategoryInsuranceSync).
 */
?>
<div class="form-group assure-insurance-toggle-wrap">
  <div class="form-group-inline grow assure-insurance-toggle">
    <input type="checkbox" id="is_insurance" name="is_insurance">
    <label for="is_insurance" class="grow">Dies ist eine Versicherung</label>
  </div>
</div>
