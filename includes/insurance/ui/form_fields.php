<?php
/**
 * AssureWallos insurance fields injected into the Wallos subscription form.
 */
?>
<div class="form-group-inline grow">
  <input type="checkbox" id="is_insurance" name="is_insurance" onchange="assureToggleInsuranceFields()">
  <label for="is_insurance" class="grow">Dies ist eine Versicherung</label>
</div>

<div class="form-group hide" id="assure-insurance-fields">
  <div class="inline">
    <div class="split50">
      <label for="policy_number">Versicherungsnummer</label>
      <input type="text" id="policy_number" name="policy_number" autocomplete="off">
    </div>
    <div class="split50">
      <label for="claims_hotline">Schaden-Hotline</label>
      <input type="text" id="claims_hotline" name="claims_hotline" autocomplete="off">
    </div>
  </div>
</div>

<div class="form-group hide" id="assure-insurance-values">
  <div class="inline">
    <div class="split50">
      <label for="insurance_sum">Deckungssumme</label>
      <div class="inline">
        <input type="text" id="insurance_sum" name="insurance_sum" autocomplete="off" inputmode="decimal"
          placeholder="0,00">
        <span class="assure-currency-symbol">€</span>
      </div>
    </div>
    <div class="split50">
      <label for="deductible">Selbstbeteiligung</label>
      <div class="inline">
        <input type="text" id="deductible" name="deductible" autocomplete="off" inputmode="decimal"
          placeholder="0,00">
        <span class="assure-currency-symbol">€</span>
      </div>
    </div>
  </div>
</div>
