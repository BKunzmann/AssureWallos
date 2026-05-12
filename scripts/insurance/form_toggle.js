const assureMoneyFormat = new Intl.NumberFormat("de-DE", {
  minimumFractionDigits: 2,
  maximumFractionDigits: 2,
});
const assureMoneyFieldIds = ["insurance_sum", "deductible"];

function assureToggleInsuranceFields() {
  const isInsurance = document.querySelector("#is_insurance");
  const fieldGroups = [
    document.querySelector("#assure-insurance-fields"),
    document.querySelector("#assure-insurance-values"),
    document.querySelector("#assure-insurance-documents"),
  ];

  fieldGroups.forEach((group) => {
    if (!group) return;
    group.classList.toggle("hide", !isInsurance.checked);
    group.querySelectorAll("input, select").forEach((field) => {
      field.disabled = !isInsurance.checked;
    });
  });
}

function assureResetInsuranceForm() {
  const isInsurance = document.querySelector("#is_insurance");
  if (isInsurance) {
    isInsurance.checked = false;
  }

  ["policy_number", "insurance_sum", "deductible", "claims_hotline", "ins_documents"].forEach((fieldId) => {
    const field = document.querySelector(`#${fieldId}`);
    if (field) {
      field.value = "";
    }
  });

  const documentType = document.querySelector("#ins_document_type");
  if (documentType) {
    documentType.value = "policy";
  }

  assureRenderDocuments([]);
  assureToggleInsuranceFields();
}

function assureFillInsuranceForm(subscription) {
  const isInsurance = document.querySelector("#is_insurance");
  if (!isInsurance) return;

  isInsurance.checked = parseInt(subscription.is_insurance || 0, 10) === 1;

  const details = subscription.insurance_details || {};
  assureSetFieldValue("policy_number", details.policy_number);
  assureSetFieldValue("insurance_sum", assureFormatMoneyValue(details.insurance_sum));
  assureSetFieldValue("deductible", assureFormatMoneyValue(details.deductible));
  assureSetFieldValue("claims_hotline", details.claims_hotline);
  assureSetFieldValue("ins_documents", "");
  assureRenderDocuments(subscription.insurance_documents || []);
  assureToggleInsuranceFields();
}

function assureSetFieldValue(fieldId, value) {
  const field = document.querySelector(`#${fieldId}`);
  if (field) {
    field.value = value ?? "";
  }
}

function assureRenderDocuments(documents) {
  const container = document.querySelector("#assure-existing-documents");
  if (!container) return;

  container.innerHTML = "";

  if (!documents.length) {
    return;
  }

  const list = document.createElement("ul");
  list.className = "assure-documents-list";

  documents.forEach((documentItem) => {
    const item = document.createElement("li");
    const link = document.createElement("a");
    link.href = `endpoints/insurance/serve_document.php?id=${encodeURIComponent(documentItem.id)}`;
    link.target = "_blank";
    link.rel = "noopener noreferrer";
    link.textContent = `${assureDocumentTypeLabel(documentItem.doc_type)}: ${documentItem.original_name}`;

    const deleteButton = document.createElement("button");
    deleteButton.type = "button";
    deleteButton.className = "warning-button thin";
    deleteButton.textContent = "Löschen";
    deleteButton.addEventListener("click", () => assureDeleteDocument(documentItem.id, item));

    item.appendChild(link);
    item.appendChild(deleteButton);
    list.appendChild(item);
  });

  container.appendChild(list);
}

function assureDeleteDocument(documentId, element) {
  if (!confirm("Dokument wirklich löschen?")) {
    return;
  }

  const formData = new FormData();
  formData.append("id", documentId);

  fetch("endpoints/insurance/delete_document.php", {
    method: "POST",
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
  assureRegisterMoneyFormatting();
  assureResetInsuranceForm();
});
