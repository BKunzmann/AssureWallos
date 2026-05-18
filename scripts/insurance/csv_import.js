/* AssureWallos: CSV import on profile page (preview + commit). */

let assureCsvBatchToken = "";

function assureCsvEndpoint(path) {
  const base = document.body?.dataset?.appUrl;
  if (typeof base === "string" && base.length > 0) {
    return base.replace(/\/$/, "") + "/" + path.replace(/^\//, "");
  }
  return path;
}

function assureCsvSetStatus(message, isError) {
  const el = document.getElementById("assure_csv_status");
  if (!el) return;
  el.textContent = message || "";
  el.hidden = !message;
  el.classList.toggle("assure-csv-status-error", !!isError);
}

function assureCsvSetButtons(previewVisible) {
  const previewBtn = document.getElementById("assure_csv_preview_btn");
  const commitBtn = document.getElementById("assure_csv_commit_btn");
  const cancelBtn = document.getElementById("assure_csv_cancel_btn");
  if (previewBtn) previewBtn.classList.toggle("hide", previewVisible);
  if (commitBtn) commitBtn.classList.toggle("hide", !previewVisible);
  if (cancelBtn) cancelBtn.classList.toggle("hide", !previewVisible);
}

function assureCsvResetPreview() {
  assureCsvBatchToken = "";
  const wrap = document.getElementById("assure_csv_preview_wrap");
  const body = document.getElementById("assure_csv_preview_body");
  const summary = document.getElementById("assure_csv_summary");
  const dupWrap = document.getElementById("assure_csv_duplicate_wrap");
  if (wrap) wrap.classList.add("hide");
  if (body) body.innerHTML = "";
  if (summary) summary.textContent = "";
  if (dupWrap) dupWrap.classList.add("hide");
  const dupCheck = document.getElementById("assure_csv_include_duplicates");
  if (dupCheck) dupCheck.checked = false;
  assureCsvSetButtons(false);
}

function assureCsvStatusLabel(status) {
  if (status === "ok") return "OK";
  if (status === "warning") return "Warnung";
  if (status === "error") return "Fehler";
  return status;
}

function assureCsvRenderPreview(data) {
  const wrap = document.getElementById("assure_csv_preview_wrap");
  const body = document.getElementById("assure_csv_preview_body");
  const summary = document.getElementById("assure_csv_summary");
  const dupWrap = document.getElementById("assure_csv_duplicate_wrap");
  if (!wrap || !body || !summary) return;

  const s = data.summary || {};
  summary.textContent = `Zeilen: ${s.total || 0} — OK: ${s.ok || 0}, Warnungen: ${s.warning || 0}, Fehler: ${s.error || 0}, importierbar: ${s.importable || 0}`;

  body.innerHTML = "";
  let hasWarningDup = false;

  (data.rows || []).forEach((row) => {
    const tr = document.createElement("tr");
    tr.className = `assure-csv-row-${row.status || "ok"}`;

    const messages = (row.messages || []).join(" ");
    if (row.status === "warning" && messages.toLowerCase().includes("existiert bereits")) {
      hasWarningDup = true;
    }

    tr.innerHTML = `
      <td>${row.line ?? ""}</td>
      <td><span class="assure-csv-badge assure-csv-badge--${row.status}">${assureCsvStatusLabel(row.status)}</span></td>
      <td>${escapeHtml(row.name || "")}</td>
      <td>${escapeHtml(row.policy_number || "")}</td>
      <td class="assure-csv-messages">${escapeHtml(messages)}</td>
    `;
    body.appendChild(tr);
  });

  if (dupWrap) {
    dupWrap.classList.toggle("hide", !hasWarningDup);
  }

  wrap.classList.remove("hide");
  assureCsvSetButtons(true);
}

function escapeHtml(text) {
  const div = document.createElement("div");
  div.textContent = text;
  return div.innerHTML;
}

function assureCsvPreview() {
  const fileInput = document.getElementById("assure_csv_file");
  const categorySelect = document.getElementById("assure_csv_default_category");
  if (!fileInput || !fileInput.files || !fileInput.files[0]) {
    assureCsvSetStatus("Bitte eine CSV-Datei auswählen.", true);
    return;
  }

  assureCsvSetStatus("Vorschau wird geladen…");
  assureCsvResetPreview();

  const fd = new FormData();
  fd.append("csv_file", fileInput.files[0]);
  fd.append("csrf_token", window.csrfToken || "");
  if (categorySelect && categorySelect.value) {
    fd.append("default_category_id", categorySelect.value);
  }

  fetch(assureCsvEndpoint("endpoints/insurance/csv_import_preview.php"), {
    method: "POST",
    credentials: "same-origin",
    body: fd,
  })
    .then((r) => r.json())
    .then((data) => {
      if (!data.success) {
        assureCsvSetStatus(data.message || "Vorschau fehlgeschlagen.", true);
        return;
      }

      assureCsvBatchToken = data.batch_token || "";
      if (!assureCsvBatchToken) {
        assureCsvSetStatus("Import-Sitzung konnte nicht erstellt werden.", true);
        return;
      }

      const s = data.summary || {};
      assureCsvRenderPreview(data);
      assureCsvSetStatus(
        (s.importable || 0) > 0
          ? "Vorschau bereit — prüfen und „Import starten“ klicken."
          : "Keine importierbaren Zeilen — bitte CSV korrigieren.",
        (s.importable || 0) === 0
      );
    })
    .catch(() => {
      assureCsvSetStatus("Vorschau fehlgeschlagen.", true);
    });
}

function assureCsvCommit() {
  if (!assureCsvBatchToken) {
    assureCsvSetStatus("Bitte zuerst eine Vorschau laden.", true);
    return;
  }

  if (!confirm("Import jetzt starten? Es werden nur neue Verträge angelegt.")) {
    return;
  }

  const dupCheck = document.getElementById("assure_csv_include_duplicates");
  const fd = new FormData();
  fd.append("batch_token", assureCsvBatchToken);
  fd.append("csrf_token", window.csrfToken || "");
  if (dupCheck && dupCheck.checked) {
    fd.append("include_duplicates", "1");
  }

  assureCsvSetStatus("Import läuft…");

  fetch(assureCsvEndpoint("endpoints/insurance/csv_import_commit.php"), {
    method: "POST",
    credentials: "same-origin",
    body: fd,
  })
    .then((r) => r.json())
    .then((data) => {
      const msg = data.message || (data.success ? "Import abgeschlossen." : "Import fehlgeschlagen.");
      assureCsvSetStatus(msg, !data.success && (data.created || 0) === 0);

      if ((data.created || 0) > 0) {
        assureCsvResetPreview();
        const fileInput = document.getElementById("assure_csv_file");
        if (fileInput) fileInput.value = "";
        if (typeof showSuccessMessage === "function") {
          showSuccessMessage(`${data.created} Vertrag/Verträge importiert.`);
        }
      }
    })
    .catch(() => {
      assureCsvSetStatus("Import fehlgeschlagen.", true);
    });
}

function assureCsvEnsureStyles() {
  if (document.querySelector('link[href*="styles/insurance/form.css"]')) {
    return;
  }
  const link = document.createElement("link");
  link.rel = "stylesheet";
  const v = document.querySelector('script[src*="csv_import.js"]')?.src?.split("?")[1];
  link.href = v ? `styles/insurance/form.css?${v}` : "styles/insurance/form.css";
  document.head.appendChild(link);
}

function assureCsvInit() {
  const block = document.getElementById("assure-csv-import");
  if (!block) return;

  assureCsvEnsureStyles();

  const previewBtn = document.getElementById("assure_csv_preview_btn");
  const commitBtn = document.getElementById("assure_csv_commit_btn");
  const cancelBtn = document.getElementById("assure_csv_cancel_btn");

  if (previewBtn && previewBtn.dataset.bound !== "1") {
    previewBtn.dataset.bound = "1";
    previewBtn.addEventListener("click", assureCsvPreview);
  }
  if (commitBtn && commitBtn.dataset.bound !== "1") {
    commitBtn.dataset.bound = "1";
    commitBtn.addEventListener("click", assureCsvCommit);
  }
  if (cancelBtn && cancelBtn.dataset.bound !== "1") {
    cancelBtn.dataset.bound = "1";
    cancelBtn.addEventListener("click", () => {
      assureCsvResetPreview();
      assureCsvSetStatus("");
    });
  }
}

document.addEventListener("DOMContentLoaded", assureCsvInit);
