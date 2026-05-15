<?php
/**
 * AssureWallos insurance fields injected into the Wallos subscription form.
 */
if (!function_exists('assure_label_with_help')) {
  function assure_label_with_help(string $for, string $label, string $help): void
  {
    ?>
    <label for="<?= htmlspecialchars($for) ?>" class="assure-label-with-help">
      <?= htmlspecialchars($label) ?>
      <span class="fa-solid fa-circle-question assure-help" title="<?= htmlspecialchars($help) ?>"></span>
    </label>
    <?php
  }
}
?>
<div class="form-group hide assure-section" id="assure-insurance-classification">
  <div class="assure-section-heading">
    <h4>Versicherungstyp</h4>
  </div>
  <div class="inline">
    <div class="split50">
      <label for="insurance_group_id">Gruppe</label>
      <select id="insurance_group_id" autocomplete="off"></select>
    </div>
    <div class="split50">
      <label for="insurance_type_id">Art</label>
      <select id="insurance_type_id" name="insurance_type_id" autocomplete="off"></select>
    </div>
  </div>
  <p class="assure-field-note">Eigene Gruppen und Arten verwaltest du in den Einstellungen im Block „AssureWallos“.</p>
</div>

<div class="form-group hide assure-section" id="assure-insurance-contract-fields">
  <div class="assure-section-heading">
    <h4>Vertragsdaten</h4>
  </div>
  <div class="inline">
    <div class="split50">
      <?php assure_label_with_help('insurer_name', 'Versicherer', 'Die Versicherungsgesellschaft, die den Vertrag trägt, z.B. Allianz, HUK oder Hannoversche.'); ?>
      <input type="text" id="insurer_name" name="insurer_name" autocomplete="off">
    </div>
    <div class="split50">
      <?php assure_label_with_help('broker_name', 'Makler / Vermittler', 'Die Person oder Firma, über die der Vertrag vermittelt oder betreut wird. Das kann leer bleiben, wenn der Vertrag direkt beim Versicherer liegt.'); ?>
      <input type="text" id="broker_name" name="broker_name" autocomplete="off">
    </div>
  </div>
  <div class="inline">
    <div class="split50">
      <label for="tariff_name">Tarif / Produktname</label>
      <input type="text" id="tariff_name" name="tariff_name" autocomplete="off">
    </div>
    <div class="split50">
      <label for="policy_number">Versicherungsnummer</label>
      <input type="text" id="policy_number" name="policy_number" autocomplete="off">
    </div>
  </div>
  <div class="inline">
    <div class="split50">
      <?php assure_label_with_help('policyholder', 'Versicherungsnehmer', 'Die Person, die den Vertrag abgeschlossen hat und rechtlich Vertragspartner des Versicherers ist.'); ?>
      <input type="text" id="policyholder" name="policyholder" autocomplete="off">
    </div>
    <div class="split50">
      <?php assure_label_with_help('beneficiary', 'Begünstigte / Bezugsberechtigte', 'Personen, die im Leistungsfall Geld oder Leistungen erhalten, z.B. bei Lebens- oder Unfallversicherungen.'); ?>
      <input type="text" id="beneficiary" name="beneficiary" autocomplete="off">
    </div>
  </div>
  <?php assure_label_with_help('insured_persons', 'Versicherte Personen / Objekte', 'Wer oder was ist versichert? Zum Beispiel Familienmitglieder, ein Fahrzeug, ein Gebäude oder ein Hausstand.'); ?>
  <textarea id="insured_persons" name="insured_persons" rows="2" class="thin" autocomplete="off"></textarea>
  <label for="contract_status">Vertragsstatus</label>
  <select id="contract_status" name="contract_status">
    <option value="active">Aktiv</option>
    <option value="pending">Beantragt / in Prüfung</option>
    <option value="cancelled">Gekündigt</option>
    <option value="expired">Abgelaufen</option>
    <option value="replaced">Ersetzt</option>
  </select>
</div>

<div class="form-group hide assure-section" id="assure-insurance-terms">
  <div class="assure-section-heading">
    <h4>Laufzeit und Fristen</h4>
  </div>
  <p class="assure-field-note">
    Vertragsbeginn wird über das Wallos-Feld „Startdatum“ gepflegt. Die nächste Beitragsfälligkeit bleibt das Wallos-Feld „Nächste Zahlung“.
  </p>
  <div class="inline">
    <div class="split50">
      <?php assure_label_with_help('main_due_date', 'Hauptfälligkeit', 'Der wiederkehrende jährliche Stichtag des Vertrags, z.B. 01.06. Die konkrete nächste Zahlung bleibt oben im Wallos-Feld.'); ?>
      <input type="text" id="main_due_date" name="main_due_date" autocomplete="off" placeholder="z.B. 01.06.">
    </div>
    <div class="split50">
      <label for="end_date">Vertragsende</label>
      <div class="date-wrapper">
        <input type="date" id="end_date" name="end_date" autocomplete="off">
      </div>
    </div>
  </div>
  <div class="inline">
    <div class="split33">
      <label for="minimum_term_months">Mindestlaufzeit (Monate)</label>
      <input type="number" min="0" step="1" id="minimum_term_months" name="minimum_term_months" autocomplete="off">
    </div>
    <div class="split33">
      <label for="cancellation_period_value">Kündigungsfrist</label>
      <input type="number" min="0" step="1" id="cancellation_period_value" name="cancellation_period_value" autocomplete="off">
    </div>
    <div class="split33">
      <label for="cancellation_period_unit">Einheit</label>
      <select id="cancellation_period_unit" name="cancellation_period_unit">
        <option value="months">Monate</option>
        <option value="weeks">Wochen</option>
        <option value="days">Tage</option>
      </select>
    </div>
  </div>
  <div class="inline">
    <div class="split50">
      <label for="renewal_period_months">Verlängerung (Monate)</label>
      <input type="number" min="0" step="1" id="renewal_period_months" name="renewal_period_months" autocomplete="off">
    </div>
    <div class="split50">
      <label for="cancellation_status">Kündigungsstatus</label>
      <select id="cancellation_status" name="cancellation_status">
        <option value="none">Nicht gekündigt</option>
        <option value="planned">Kündigung geplant</option>
        <option value="sent">Kündigung versendet</option>
        <option value="confirmed">Kündigung bestätigt</option>
      </select>
    </div>
  </div>
</div>

<div class="form-group hide assure-section" id="assure-insurance-values">
  <div class="assure-section-heading">
    <h4>Versicherungswerte</h4>
  </div>
  <p class="assure-field-note">
    Beitrag, Zahlungsintervall, Währung, Zahlungsart und automatische Verlängerung werden oben über die normalen Wallos-Felder gepflegt.
  </p>
  <div class="inline">
    <div class="split50">
      <?php assure_label_with_help('insurance_sum', 'Deckungssumme / Versicherungssumme', 'Maximaler Betrag, den der Versicherer im Leistungsfall zahlt. Bei Risikoleben entspricht das typischerweise der Todesfallsumme.'); ?>
      <input type="text" id="insurance_sum" name="insurance_sum" autocomplete="off" inputmode="decimal" placeholder="0,00">
      <span class="assure-currency-hint" data-assure-currency-label></span>
    </div>
    <div class="split50">
      <?php assure_label_with_help('deductible', 'Selbstbeteiligung', 'Betrag, den du im Schadenfall selbst zahlst, bevor die Versicherung leistet.'); ?>
      <input type="text" id="deductible" name="deductible" autocomplete="off" inputmode="decimal" placeholder="0,00">
      <span class="assure-currency-hint" data-assure-currency-label></span>
    </div>
  </div>
  <label for="bank_account_label">Zahlungskonto / Referenz</label>
  <input type="text" id="bank_account_label" name="bank_account_label" autocomplete="off" placeholder="z.B. Haushaltskonto oder IBAN-Endung">
</div>

<div class="form-group hide assure-section" id="assure-insurance-contact">
  <div class="assure-section-heading">
    <h4>Kontakt und Schaden</h4>
  </div>
  <div class="inline">
    <div class="split50">
      <label for="claims_hotline">Schaden-Hotline</label>
      <input type="text" id="claims_hotline" name="claims_hotline" autocomplete="off">
    </div>
    <div class="split50">
      <label for="contact_name">Ansprechpartner</label>
      <input type="text" id="contact_name" name="contact_name" autocomplete="off">
    </div>
  </div>
  <div class="inline">
    <div class="split50">
      <label for="contact_phone">Telefon</label>
      <input type="text" id="contact_phone" name="contact_phone" autocomplete="off">
    </div>
    <div class="split50">
      <label for="contact_email">E-Mail</label>
      <div class="assure-link-field">
        <input type="email" id="contact_email" name="contact_email" autocomplete="off">
        <a href="#" target="_blank" rel="noopener noreferrer" data-assure-link-for="contact_email" class="hide">Öffnen</a>
      </div>
    </div>
  </div>
  <label for="portal_url">Online-Postfach / Kundenportal</label>
  <div class="assure-link-field">
    <input type="text" id="portal_url" name="portal_url" autocomplete="off" placeholder="https://">
    <a href="#" target="_blank" rel="noopener noreferrer" data-assure-link-for="portal_url" class="hide">Öffnen</a>
  </div>
  <label for="portal_notes">Portalnotizen</label>
  <textarea id="portal_notes" name="portal_notes" rows="2" class="thin" autocomplete="off"></textarea>
  <label for="claim_reference_notes">Schadennummern / Hinweise</label>
  <textarea id="claim_reference_notes" name="claim_reference_notes" rows="2" class="thin" autocomplete="off"></textarea>
</div>
