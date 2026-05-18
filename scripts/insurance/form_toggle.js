const assureMoneyFormat = new Intl.NumberFormat("de-DE", {
  minimumFractionDigits: 2,
  maximumFractionDigits: 2,
});
const assureMoneyFieldIds = ["insurance_sum", "deductible"];
const assureTextFieldIds = [
  "policy_number",
  "claims_hotline",
  "ins_documents",
  "insurer_name",
  "broker_name",
  "tariff_name",
  "policyholder",
  "insured_persons",
  "beneficiary",
  "document_url",
  "portal_url",
  "portal_notes",
  "contract_status",
  "end_date",
  "main_due_date",
  "minimum_term_months",
  "cancellation_period_value",
  "cancellation_period_unit",
  "renewal_period_months",
  "cancellation_status",
  "bank_account_label",
  "contact_name",
  "contact_phone",
  "contact_email",
  "claim_reference_notes",
];
let assureInsuranceTaxonomy = [];
/** Verhindert Schleifen, wenn #category und #is_insurance sich gegenseitig setzen. */
let assureCategoryInsuranceProgrammatic = false;

function assureRegisterStylesheet() {
  if (document.querySelector('link[href^="styles/insurance/form.css"]')) {
    return;
  }

  const link = document.createElement("link");
  link.rel = "stylesheet";
  link.href = "styles/insurance/form.css";
  document.head.appendChild(link);
}

/**
 * Zeigt die interne Vertrags-ID (subscriptions.id) für Paperless-Zuordnung an.
 */
function assureUpdateContractIdHint() {
  const hint = document.querySelector("#assure-contract-id-hint");
  const valueEl = document.querySelector("#assure-contract-id-value");
  const subEl = document.querySelector("#assure-contract-id-sub");
  const isInsurance = document.querySelector("#is_insurance");
  const idEl = document.querySelector("#id");

  if (!hint || !valueEl) {
    return;
  }

  const show = !!(isInsurance && isInsurance.checked);
  hint.classList.toggle("hide", !show);

  if (!show) {
    return;
  }

  const sid = idEl && idEl.value ? parseInt(idEl.value, 10) : 0;
  if (sid > 0) {
    valueEl.textContent = String(sid);
    if (subEl) {
      subEl.textContent = " — für Paperless z. B. Tag aw-sub-" + sid + " oder Custom Field mit diesem Wert.";
    }
  } else {
    valueEl.textContent = "—";
    if (subEl) {
      subEl.textContent = " — erscheint nach dem ersten Speichern des Vertrags.";
    }
  }
}

function assureToggleInsuranceFields() {
  const isInsurance = document.querySelector("#is_insurance");
  const fieldGroups = [
    document.querySelector("#assure-insurance-classification"),
    document.querySelector("#assure-insurance-contract-fields"),
    document.querySelector("#assure-insurance-terms"),
    document.querySelector("#assure-insurance-values"),
    document.querySelector("#assure-insurance-contact"),
    document.querySelector("#assure-insurance-documents"),
    document.querySelector("#assure-paperless-archive"),
  ];

  fieldGroups.forEach((group) => {
    if (!group) return;
    group.classList.toggle("hide", !isInsurance.checked);
    group.querySelectorAll("input, select, textarea, button").forEach((field) => {
      field.disabled = !isInsurance.checked;
    });
  });

  assureUpdateDocumentUploadButtonState();
  assureUpdateContractIdHint();

  if (typeof assureLoadPaperlessArchive === "function" && typeof assurePaperlessArchiveReset === "function") {
    if (isInsurance.checked) {
      const sidEl = document.querySelector("#id");
      const sid = sidEl && sidEl.value ? parseInt(sidEl.value, 10) : 0;
      if (sid > 0) {
        assureLoadPaperlessArchive(sid);
      }
    } else {
      assurePaperlessArchiveReset();
    }
  }
}

/**
 * Liefert die Wallos-Kategorie-ID für „Versicherung“, falls subscriptions.php sie als
 * data-assure-insurance-category-id am #category-Select gesetzt hat.
 *
 * @returns {string|null}
 */
function assureGetInsuranceCategoryId() {
  const category = document.querySelector("#category");
  if (!category) return null;
  const raw = category.getAttribute("data-assure-insurance-category-id");
  return raw && String(raw).trim() !== "" ? String(raw) : null;
}

/**
 * Fallback-Kategorie, wenn die Checkbox „Versicherung“ ausgeht: bevorzugt ID 1 („No category“), sonst erste andere Option.
 *
 * @param {string} insuranceCatId
 * @returns {string|null}
 */
function assureFallbackCategoryValueWhenUncheckingInsurance(insuranceCatId) {
  const category = document.querySelector("#category");
  if (!category) return null;
  const opts = Array.from(category.querySelectorAll("option"));
  const noCat = opts.find((o) => o.value === "1");
  if (noCat && noCat.value !== String(insuranceCatId)) {
    return "1";
  }
  const other = opts.find((o) => o.value !== String(insuranceCatId));
  return other ? other.value : null;
}

/**
 * Beim Öffnen eines Abos: is_insurance und Kategorie „Versicherung“ angleichen (ohne Endlosschleife).
 */
function assureReconcileCategoryInsuranceFromDb() {
  const insuranceCatId = assureGetInsuranceCategoryId();
  const category = document.querySelector("#category");
  const isInsurance = document.querySelector("#is_insurance");
  if (!isInsurance) return;

  if (!insuranceCatId || !category) {
    assureToggleInsuranceFields();
    return;
  }

  assureCategoryInsuranceProgrammatic = true;
  try {
    if (isInsurance.checked) {
      category.value = insuranceCatId;
    } else if (category.value === insuranceCatId) {
      isInsurance.checked = true;
    }
  } finally {
    assureCategoryInsuranceProgrammatic = false;
  }
  assureToggleInsuranceFields();
}

/**
 * Koppelt Wallos-Kategorie „Versicherung“ und Checkbox #is_insurance (gegenseitige Steuerung).
 * Ohne data-assure-insurance-category-id bleibt nur das Ein-/Ausblenden der Versicherungsfelder aktiv.
 */
function assureRegisterCategoryInsuranceSync() {
  const category = document.querySelector("#category");
  const isInsurance = document.querySelector("#is_insurance");
  if (!category || !isInsurance) return;

  const insuranceCatId = assureGetInsuranceCategoryId();

  const onIsInsuranceChange = function () {
    if (assureCategoryInsuranceProgrammatic) return;
    assureCategoryInsuranceProgrammatic = true;
    try {
      if (insuranceCatId) {
        if (isInsurance.checked) {
          category.value = insuranceCatId;
        } else if (category.value === insuranceCatId) {
          const fb = assureFallbackCategoryValueWhenUncheckingInsurance(insuranceCatId);
          if (fb !== null) {
            category.value = fb;
          }
        }
      }
    } finally {
      assureCategoryInsuranceProgrammatic = false;
    }
    assureToggleInsuranceFields();
  };

  const onCategoryChange = function () {
    if (assureCategoryInsuranceProgrammatic || !insuranceCatId) return;
    assureCategoryInsuranceProgrammatic = true;
    try {
      isInsurance.checked = category.value === insuranceCatId;
    } finally {
      assureCategoryInsuranceProgrammatic = false;
    }
    assureToggleInsuranceFields();
  };

  isInsurance.addEventListener("change", onIsInsuranceChange);
  if (insuranceCatId) {
    category.addEventListener("change", onCategoryChange);
  }
}

function assureResetInsuranceForm() {
  const isInsurance = document.querySelector("#is_insurance");
  if (isInsurance) {
    isInsurance.checked = false;
  }

  [...assureMoneyFieldIds, ...assureTextFieldIds, "insurance_type_id", "assure_new_group_name", "assure_new_type_name"].forEach(
    (fieldId) => {
      const field = document.querySelector(`#${fieldId}`);
      if (field) {
        field.value = "";
      }
    }
  );

  assureSetFieldValue("contract_status", "active");
  assureSetFieldValue("cancellation_period_unit", "months");
  assureSetFieldValue("cancellation_status", "none");

  const documentType = document.querySelector("#ins_document_type");
  if (documentType) {
    documentType.value = "policy";
  }

  assureRenderTaxonomy(assureInsuranceTaxonomy, null);
  assureRenderDocuments([]);
  if (typeof assurePaperlessArchiveReset === "function") {
    assurePaperlessArchiveReset();
  }
  assureToggleInsuranceFields();
  assureUpdateContractIdHint();
}

function assureFillInsuranceForm(subscription) {
  const isInsurance = document.querySelector("#is_insurance");
  if (!isInsurance) return;

  isInsurance.checked = parseInt(subscription.is_insurance || 0, 10) === 1;

  const details = subscription.insurance_details || {};
  if (Array.isArray(subscription.insurance_taxonomy)) {
    assureInsuranceTaxonomy = subscription.insurance_taxonomy;
  }

  assureRenderTaxonomy(assureInsuranceTaxonomy, details.insurance_type_id);

  assureMoneyFieldIds.forEach((fieldId) => {
    assureSetFieldValue(fieldId, assureFormatMoneyValue(details[fieldId]));
  });
  assureTextFieldIds.forEach((fieldId) => {
    assureSetFieldValue(fieldId, details[fieldId]);
  });
  assureSetFieldValue("ins_documents", "");
  assureSetFieldValue("contract_status", details.contract_status || "active");
  assureSetFieldValue("cancellation_period_unit", details.cancellation_period_unit || "months");
  assureSetFieldValue("cancellation_status", details.cancellation_status || "none");

  assureRenderDocuments(subscription.insurance_documents || []);
  assureSyncUrlLinks();
  assureReconcileCategoryInsuranceFromDb();

  assureUpdateContractIdHint();

  if (typeof assureLoadPaperlessArchive === "function") {
    const subId = subscription.id ?? document.querySelector("#id")?.value;
    assureLoadPaperlessArchive(subId);
  }
}

function assureSetFieldValue(fieldId, value) {
  const field = document.querySelector(`#${fieldId}`);
  if (field) {
    field.value = value ?? "";
  }
}

function assureLoadInsuranceTaxonomy(selectedTypeId = null) {
  const typeEl = document.querySelector("#insurance_type_id");
  const groupEl = document.querySelector("#insurance_group_id");
  let preserveType = selectedTypeId;
  if (preserveType == null && typeEl && typeEl.value) {
    const parsed = parseInt(typeEl.value, 10);
    preserveType = Number.isNaN(parsed) ? null : parsed;
  }
  let preserveGroup = null;
  if (groupEl && groupEl.value) {
    const parsedG = parseInt(groupEl.value, 10);
    preserveGroup = Number.isNaN(parsedG) ? null : parsedG;
  }

  fetch("endpoints/insurance/list_taxonomy.php", {
    method: "POST",
    credentials: "same-origin",
    cache: "no-store",
    headers: {
      "X-CSRF-Token": window.csrfToken,
    },
  })
    .then((response) => response.json())
    .then((data) => {
      if (!data.success) return;
      assureInsuranceTaxonomy = data.taxonomy || [];
      assureRenderTaxonomy(assureInsuranceTaxonomy, preserveType, preserveGroup);
    })
    .catch(() => {
      assureInsuranceTaxonomy = [];
      assureRenderTaxonomy([], null);
    });
}

function assurePrepareInsuranceForSubmit() {
  ["insurance_sum", "deductible"].forEach((fieldId) => {
    const field = document.querySelector(`#${fieldId}`);
    if (field && !field.disabled) {
      field.dispatchEvent(new Event("blur", { bubbles: true }));
    }
  });
}

window.assurePrepareInsuranceForSubmit = assurePrepareInsuranceForSubmit;

function assureRenderTaxonomy(taxonomy, selectedTypeId = null, selectedGroupId = null) {
  const groupSelect = document.querySelector("#insurance_group_id");
  const typeSelect = document.querySelector("#insurance_type_id");
  if (!groupSelect || !typeSelect) return;

  groupSelect.innerHTML = "";
  typeSelect.innerHTML = "";

  if (!taxonomy.length) {
    groupSelect.appendChild(new Option("Keine Gruppen verfügbar", ""));
    typeSelect.appendChild(new Option("Keine Arten verfügbar", ""));
    return;
  }

  const selectedType = selectedTypeId ? parseInt(selectedTypeId, 10) : null;
  let activeGroupId = selectedGroupId ? parseInt(selectedGroupId, 10) : null;

  taxonomy.forEach((group) => {
    const option = new Option(group.name, group.id);
    groupSelect.appendChild(option);

    if (!activeGroupId && selectedType && (group.types || []).some((type) => parseInt(type.id, 10) === selectedType)) {
      activeGroupId = parseInt(group.id, 10);
    }
  });

  if (!activeGroupId) {
    activeGroupId = parseInt(taxonomy[0].id, 10);
  }

  groupSelect.value = activeGroupId.toString();
  assureRenderTypeOptions(activeGroupId, selectedType);
}

function assureRenderTypeOptions(groupId, selectedTypeId = null) {
  const typeSelect = document.querySelector("#insurance_type_id");
  if (!typeSelect) return;

  typeSelect.innerHTML = "";

  const group = assureInsuranceTaxonomy.find((item) => parseInt(item.id, 10) === parseInt(groupId, 10));
  const types = group?.types || [];

  if (!types.length) {
    typeSelect.appendChild(new Option("Keine Arten verfügbar", ""));
    return;
  }

  types.forEach((type) => {
    typeSelect.appendChild(new Option(type.name, type.id));
  });

  if (selectedTypeId && types.some((type) => parseInt(type.id, 10) === parseInt(selectedTypeId, 10))) {
    typeSelect.value = selectedTypeId.toString();
  }
}

function assureRegisterTaxonomyHandlers() {
  const groupSelect = document.querySelector("#insurance_group_id");
  if (groupSelect) {
    groupSelect.addEventListener("change", function () {
      assureRenderTypeOptions(groupSelect.value, null);
    });
  }
}

/**
 * Erzeugt eine Zeile in der Liste „Versicherungsdokumente“ (Download + Löschen per Mülleimer-Icon wie in den Einstellungen).
 * Wird von assureRenderDocuments und dem Sofort-Upload nach erfolgreichem POST genutzt.
 *
 * @param {{ id: number|string, doc_type: string, original_name?: string }} documentItem
 * @returns {HTMLDivElement}
 */
function assureCreateDocumentRowElement(documentItem) {
  const row = document.createElement("div");
  row.className = "form-group-inline assure-document-row";
  row.dataset.assureDocumentId = String(documentItem.id);

  const meta = document.createElement("span");
  meta.className = "assure-document-type-pill";
  meta.textContent = assureDocumentTypeLabel(documentItem.doc_type);

  const link = document.createElement("a");
  link.className = "grow assure-document-download";
  link.href = `endpoints/insurance/serve_document.php?id=${encodeURIComponent(documentItem.id)}`;
  link.target = "_blank";
  link.rel = "noopener noreferrer";
  link.textContent = documentItem.original_name || "Dokument";

  const delBtn = document.createElement("button");
  delBtn.type = "button";
  delBtn.className = "image-button medium";
  delBtn.setAttribute("title", "Dokument entfernen");
  delBtn.setAttribute("aria-label", "Dokument entfernen");
  const delIconTpl = document.getElementById("assure-insurance-delete-icon-svg");
  if (delIconTpl && delIconTpl.content) {
    delBtn.appendChild(delIconTpl.content.cloneNode(true));
  }
  delBtn.addEventListener("click", () => assureDeleteDocument(documentItem.id, row));

  row.appendChild(meta);
  row.appendChild(link);
  row.appendChild(delBtn);
  return row;
}

function assureRenderDocuments(documents) {
  const container = document.querySelector("#assure-existing-documents");
  if (!container) return;

  container.innerHTML = "";

  if (!documents.length) {
    return;
  }

  documents.forEach((documentItem) => {
    container.appendChild(assureCreateDocumentRowElement(documentItem));
  });
}

/**
 * Fügt ein frisch hochgeladenes Dokument oben in die Liste ein (neueste zuerst, wie beim Laden).
 *
 * @param {{ id: number|string, doc_type: string, original_name?: string }} documentItem
 */
function assurePrependDocumentRow(documentItem) {
  const container = document.querySelector("#assure-existing-documents");
  if (!container) return;
  const row = assureCreateDocumentRowElement(documentItem);
  container.prepend(row);
}

/**
 * Blendet den „Hochladen“-Button ein, sobald Dateien gewählt sind und Versicherung aktiv ist.
 */
function assureUpdateDocumentUploadButtonState() {
  const input = document.querySelector("#ins_documents");
  const btn = document.querySelector("#assure-document-upload-btn");
  if (!input || !btn) return;

  const isInsurance = document.querySelector("#is_insurance");
  const hasFiles = !!(input.files && input.files.length);
  const show = hasFiles && isInsurance && isInsurance.checked;
  btn.hidden = !show;
}

/**
 * Lädt die aktuell im Datei-Feld ausgewählten Dateien per API hoch (ein Request pro Datei).
 *
 * @param {HTMLInputElement} input
 * @param {(visible: boolean) => void} setUploadStatusVisible
 * @returns {{ uploaded: number, lastError: string|null }}
 */
async function assureUploadPendingInsuranceDocuments(input, setUploadStatusVisible) {
  const files = input.files && input.files.length ? Array.from(input.files) : [];
  if (!files.length) {
    return { uploaded: 0, lastError: null };
  }

  const docTypeEl = document.querySelector("#ins_document_type");
  const docType = docTypeEl && docTypeEl.value ? docTypeEl.value : "other";

  const sidEl = document.querySelector("#id");
  const subId = sidEl && sidEl.value ? parseInt(sidEl.value, 10) : 0;

  let uploaded = 0;
  let lastError = null;

  setUploadStatusVisible(true);
  try {
    for (const file of files) {
      const formData = new FormData();
      formData.append("subscription_id", String(subId));
      formData.append("doc_type", docType);
      formData.append("document", file, file.name);

      try {
        const response = await fetch("endpoints/insurance/upload_document.php", {
          method: "POST",
          credentials: "same-origin",
          cache: "no-store",
          headers: {
            "X-CSRF-Token": window.csrfToken,
          },
          body: formData,
        });
        const data = await response.json();
        if (data.success && data.document) {
          uploaded += 1;
          assurePrependDocumentRow(data.document);
        } else {
          lastError = data.message || "Dokument konnte nicht hochgeladen werden.";
          break;
        }
      } catch (e) {
        lastError = "Dokument konnte nicht hochgeladen werden.";
        break;
      }
    }
  } finally {
    setUploadStatusVisible(false);
  }

  return { uploaded, lastError };
}

/**
 * Dateiauswahl zeigt „Hochladen“; Klick startet den Upload zu endpoints/insurance/upload_document.php.
 */
function assureRegisterDocumentUploadUi() {
  const input = document.querySelector("#ins_documents");
  const btn = document.querySelector("#assure-document-upload-btn");
  if (!input || !btn) return;

  const statusEl = document.querySelector("#assure-document-upload-status");
  const setUploadStatusVisible = (visible) => {
    if (statusEl) statusEl.hidden = !visible;
  };

  input.addEventListener("change", function () {
    if (statusEl) statusEl.hidden = true;
    assureUpdateDocumentUploadButtonState();
  });

  btn.addEventListener("click", async function () {
    const isInsurance = document.querySelector("#is_insurance");
    if (!isInsurance || !isInsurance.checked) {
      showErrorMessage("Bitte aktivieren Sie „Dies ist eine Versicherung“, um Dokumente hochzuladen.");
      return;
    }

    const sidEl = document.querySelector("#id");
    const subId = sidEl && sidEl.value ? parseInt(sidEl.value, 10) : 0;
    if (!subId || subId <= 0) {
      showErrorMessage("Bitte speichern Sie das Abonnement zuerst, damit Dokumente hochgeladen werden können.");
      return;
    }

    const files = input.files && input.files.length ? Array.from(input.files) : [];
    if (!files.length) {
      return;
    }

    btn.disabled = true;
    input.disabled = true;

    let uploaded = 0;
    let lastError = null;
    try {
      const result = await assureUploadPendingInsuranceDocuments(input, setUploadStatusVisible);
      uploaded = result.uploaded;
      lastError = result.lastError;
    } finally {
      input.value = "";
      input.disabled = false;
      btn.disabled = false;
      assureUpdateDocumentUploadButtonState();
    }

    if (lastError) {
      if (uploaded > 0) {
        showErrorMessage(`${lastError} (${uploaded} Datei(en) wurde(n) dennoch hochgeladen.)`);
      } else {
        showErrorMessage(lastError);
      }
    } else if (uploaded > 0 && typeof showSuccessMessage === "function") {
      showSuccessMessage(uploaded === 1 ? "Dokument hochgeladen." : `${uploaded} Dokumente hochgeladen.`);
    }
  });
}

function assureDeleteDocument(documentId, element) {
  if (!confirm("Dokument wirklich löschen?")) {
    return;
  }

  const formData = new FormData();
  formData.append("id", documentId);

  fetch("endpoints/insurance/delete_document.php", {
    method: "POST",
    credentials: "same-origin",
    cache: "no-store",
    headers: {
      "X-CSRF-Token": window.csrfToken,
    },
    body: formData,
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        element.remove();
      } else {
        showErrorMessage(data.message || "Dokument konnte nicht gelöscht werden.");
      }
    })
    .catch(() => showErrorMessage("Dokument konnte nicht gelöscht werden."));
}

function assureRegisterUrlLinks() {
  ["portal_url", "document_url"].forEach((fieldId) => {
    const field = document.querySelector(`#${fieldId}`);
    if (!field) return;

    field.addEventListener("input", assureSyncUrlLinks);
    field.addEventListener("blur", assureSyncUrlLinks);
  });

  const contactEmail = document.querySelector("#contact_email");
  if (contactEmail) {
    contactEmail.addEventListener("input", assureSyncUrlLinks);
    contactEmail.addEventListener("blur", assureSyncUrlLinks);
  }

  assureSyncUrlLinks();
}

function assureNormalizeMailto(value) {
  const trimmed = (value || "").trim();
  if (!trimmed) {
    return "";
  }
  if (/^mailto:/i.test(trimmed)) {
    return trimmed;
  }
  if (/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(trimmed)) {
    return `mailto:${trimmed}`;
  }
  return "";
}

function assureSyncUrlLinks() {
  ["portal_url", "document_url"].forEach((fieldId) => {
    const field = document.querySelector(`#${fieldId}`);
    const link = document.querySelector(`[data-assure-link-for="${fieldId}"]`);
    if (!field || !link) return;

    const url = assureNormalizeUrl(field.value);
    link.classList.toggle("hide", !url);
    if (url) {
      link.href = url;
    }
  });

  const emailField = document.querySelector("#contact_email");
  const emailLink = document.querySelector('[data-assure-link-for="contact_email"]');
  if (emailField && emailLink) {
    const mailto = assureNormalizeMailto(emailField.value);
    emailLink.classList.toggle("hide", !mailto);
    if (mailto) {
      emailLink.href = mailto;
    }
  }
}

function assureNormalizeUrl(value) {
  const trimmed = (value || "").trim();
  if (!trimmed) return "";

  if (/^https?:\/\//i.test(trimmed)) {
    return trimmed;
  }

  return `https://${trimmed}`;
}

function assureRegisterCurrencyHint() {
  const currency = document.querySelector("#currency");
  if (!currency) return;

  const sync = () => {
    const selected = currency.options[currency.selectedIndex];
    const label = selected ? `Währung: ${selected.textContent.trim()}` : "";
    document.querySelectorAll("[data-assure-currency-label]").forEach((element) => {
      element.textContent = label;
    });
  };

  currency.addEventListener("change", sync);
  sync();
}

function assureDocumentTypeLabel(docType) {
  const labels = {
    policy: "Police",
    claim: "Schadensprotokoll",
    invoice: "Rechnung",
    correspondence: "Schriftverkehr",
    cancellation: "Kündigung",
    other: "Sonstiges",
  };

  return labels[docType] || labels.other;
}

function assureParseMoneyValue(value) {
  if (value === null || value === undefined || value === "") {
    return null;
  }

  let normalized = value.toString().trim().replace(/\s/g, "");
  const hasComma = normalized.includes(",");
  const hasDot = normalized.includes(".");

  if (hasComma && hasDot) {
    normalized = normalized.replace(/\./g, "").replace(",", ".");
  } else if (hasComma) {
    normalized = normalized.replace(",", ".");
  }

  const parsed = Number(normalized);
  return Number.isFinite(parsed) ? parsed : null;
}

function assureFormatMoneyValue(value) {
  const parsed = assureParseMoneyValue(value);
  return parsed === null ? "" : assureMoneyFormat.format(parsed);
}

function assureEditableMoneyValue(value) {
  const parsed = assureParseMoneyValue(value);
  if (parsed === null) {
    return "";
  }

  return parsed.toFixed(2).replace(".", ",");
}

function assureRegisterMoneyFormatting() {
  assureMoneyFieldIds.forEach((fieldId) => {
    const field = document.querySelector(`#${fieldId}`);
    if (!field) return;

    field.addEventListener("focus", function () {
      field.value = assureEditableMoneyValue(field.value);
    });
    field.addEventListener("blur", function () {
      field.value = assureFormatMoneyValue(field.value);
    });
  });
}

document.addEventListener("DOMContentLoaded", function () {
  assureRegisterStylesheet();
  assureRegisterMoneyFormatting();
  assureRegisterUrlLinks();
  assureRegisterCurrencyHint();
  assureRegisterTaxonomyHandlers();
  assureRegisterCategoryInsuranceSync();
  assureRegisterDocumentUploadUi();
  assureLoadInsuranceTaxonomy();
  assureResetInsuranceForm();
});
