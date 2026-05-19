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

function assureCsvSetVisible(el, visible) {
  if (!el) return;
  el.classList.toggle("hide", !visible);
  el.hidden = !visible;
}

function assureCsvSetActionsVisible(visible) {
  const actions = document.getElementById("assure_csv_actions_wrap");
  assureCsvSetVisible(actions, visible);
}

function assureCsvResetPreview() {
  assureCsvBatchToken = "";
  const wrap = document.getElementById("assure_csv_preview_wrap");
  const body = document.getElementById("assure_csv_preview_body");
  const summary = document.getElementById("assure_csv_summary");
  const dupWrap = document.getElementById("assure_csv_duplicate_wrap");
  assureCsvSetVisible(wrap, false);
  if (body) body.innerHTML = "";
  if (summary) summary.textContent = "";
  assureCsvSetVisible(dupWrap, false);
  const dupCheck = document.getElementById("assure_csv_include_duplicates");
  if (dupCheck) dupCheck.checked = false;
  assureCsvSetActionsVisible(false);
}

function assureCsvStatusLabel(status) {
  if (status === "ok") return "OK";
  if (status === "warning") return "Warnung";
  if (status === "error") return "Fehler";
  return status;
}

function assureCsvFormatMessages(messages) {
  if (Array.isArray(messages)) {
    return messages.join(" ");
  }
  return messages ? String(messages) : "";
}

function assureCsvSetExtraColumnsVisible(visible) {
  document.querySelectorAll(".assure-csv-col-extra").forEach((el) => {
    assureCsvSetVisible(el, visible);
  });
  const tableWrap = document.querySelector(".assure-csv-preview-table-wrap");
  if (tableWrap) {
    tableWrap.classList.toggle("assure-csv-preview-table-wrap--scroll-x", !!visible);
  }
}

/** Spaltenkopf fest „Zeile“ (kein title, nicht übersetzen/abschneiden). */
function assureCsvFixLineHeader() {
  const th = document.querySelector(".assure-csv-preview-table th.assure-csv-col-line");
  if (!th) return;
  th.setAttribute("translate", "no");
  th.classList.add("notranslate");
  th.removeAttribute("title");
  th.textContent = "Zeile";
}

function assureCsvRenderPreview(data) {
  const wrap = document.getElementById("assure_csv_preview_wrap");
  const body = document.getElementById("assure_csv_preview_body");
  const summary = document.getElementById("assure_csv_summary");
  const dupWrap = document.getElementById("assure_csv_duplicate_wrap");
  if (!wrap || !body || !summary) {
    assureCsvSetStatus("Vorschau-Bereich nicht gefunden (Seite neu laden).", true);
    return;
  }

  try {
    const s = data.summary || {};
    summary.textContent = `Zeilen: ${s.total || 0} — OK: ${s.ok || 0}, Warnungen: ${s.warning || 0}, Fehler: ${s.error || 0}, importierbar: ${s.importable || 0}`;

    body.innerHTML = "";
    let hasWarningDup = false;

    const rows = Array.isArray(data.rows) ? data.rows : [];
    rows.forEach((row) => {
      const tr = document.createElement("tr");
      tr.className = `assure-csv-row-${row.status || "ok"}`;

      const messages = assureCsvFormatMessages(row.messages);
      if (row.status === "warning" && messages.toLowerCase().includes("existiert bereits")) {
        hasWarningDup = true;
      }

      tr.innerHTML = `
      <td class="assure-csv-col-line">${row.line ?? ""}</td>
      <td><span class="assure-csv-badge assure-csv-badge--${row.status}">${assureCsvStatusLabel(row.status)}</span></td>
      <td>${escapeHtml(row.name || "")}</td>
      <td>${escapeHtml(row.price || "")}</td>
      <td>${escapeHtml(row.category || "")}</td>
      <td class="assure-csv-col-extra hide">${escapeHtml(row.cycle || "")}</td>
      <td class="assure-csv-col-extra hide">${escapeHtml(row.next_payment || "")}</td>
      <td class="assure-csv-col-extra hide">${escapeHtml(row.insurance_type || "")}</td>
      <td>${escapeHtml(row.policy_number || "")}</td>
      <td class="assure-csv-messages assure-csv-col-messages">${escapeHtml(messages)}</td>
    `;
      body.appendChild(tr);
    });

    const showExtra = document.getElementById("assure_csv_show_extra_cols");
    assureCsvSetExtraColumnsVisible(!!(showExtra && showExtra.checked));

    assureCsvFixLineHeader();

    assureCsvSetVisible(dupWrap, hasWarningDup);
    assureCsvSetVisible(wrap, true);
    assureCsvSetActionsVisible(true);
  } catch (err) {
    console.error("CSV preview render failed:", err);
    assureCsvSetStatus("Vorschau konnte nicht angezeigt werden.", true);
  }
}

function escapeHtml(text) {
  const div = document.createElement("div");
  div.textContent = text;
  return div.innerHTML;
}

/**
 * Liest die gewählte Datei sofort in den Speicher (vermeidet net::ERR_UPLOAD_FILE_CHANGED,
 * wenn Cloud-Sync/Excel die Datei auf der Platte zwischen Auswahl und Upload ändert).
 */
function assureCsvSnapshotFile(file) {
  return new Promise((resolve, reject) => {
    if (!file) {
      reject(new Error("no_file"));
      return;
    }
    const reader = new FileReader();
    reader.onload = () => {
      resolve(new Blob([reader.result], { type: file.type || "text/csv" }));
    };
    reader.onerror = () => reject(reader.error || new Error("read_failed"));
    reader.readAsArrayBuffer(file);
  });
}

function assureCsvPreviewUploadFailed(err) {
  console.error("CSV preview upload failed:", err);
  const name = err && err.name ? String(err.name) : "";
  const msg = err && err.message ? String(err.message) : "";
  if (name === "NotReadableError" || /changed|modif/i.test(msg)) {
    assureCsvSetStatus(
      "Datei konnte nicht gelesen werden (wurde nach der Auswahl verändert). " +
        "CSV erneut speichern, Excel schließen, nicht aus einem Cloud-Sync-Ordner während des Uploads.",
      true
    );
    return;
  }
  if (msg === "invalid_json") {
    assureCsvSetStatus(
      "Server-Antwort ungültig (kein JSON). CSV als UTF-8 speichern oder Vorlage nutzen. Details in der Browser-Konsole (F12).",
      true
    );
    return;
  }
  if (/^http_5/.test(msg)) {
    assureCsvSetStatus("Server-Fehler bei der Vorschau (HTTP 5xx). Bitte Logs prüfen.", true);
    return;
  }
  if (/^http_4/.test(msg)) {
    assureCsvSetStatus("Vorschau abgelehnt (HTTP 4xx). Bitte neu anmelden und erneut versuchen.", true);
    return;
  }
  assureCsvSetStatus("Vorschau fehlgeschlagen. Details in der Browser-Konsole (F12).", true);
}

function assureCsvPreview() {
  const fileInput = document.getElementById("assure_csv_file");
  const categorySelect = document.getElementById("assure_csv_default_category");
  const previewBtn = document.getElementById("assure_csv_preview_btn");
  if (!fileInput || !fileInput.files || !fileInput.files[0]) {
    assureCsvSetStatus("Bitte eine CSV-Datei auswählen.", true);
    return;
  }

  const file = fileInput.files[0];
  assureCsvSetStatus("Vorschau wird geladen…");
  if (previewBtn) previewBtn.disabled = true;

  assureCsvSnapshotFile(file)
    .then((blob) => {
      assureCsvResetPreview();

      const fd = new FormData();
      fd.append("csv_file", blob, file.name || "import.csv");
      fd.append("csrf_token", window.csrfToken || "");
      if (categorySelect && categorySelect.value) {
        fd.append("default_category_id", categorySelect.value);
      }
      const encodingSelect = document.getElementById("assure_csv_encoding");
      if (encodingSelect && encodingSelect.value) {
        fd.append("csv_encoding", encodingSelect.value);
      }

      return fetch(assureCsvEndpoint("endpoints/insurance/csv_import_preview.php"), {
        method: "POST",
        credentials: "same-origin",
        cache: "no-store",
        body: fd,
      });
    })
    .then(async (r) => {
      const bodyText = await r.text();
      if (!r.ok) {
        throw new Error(`http_${r.status}`);
      }
      let data;
      try {
        data = bodyText ? JSON.parse(bodyText) : null;
      } catch (parseErr) {
        console.error("CSV preview: invalid JSON response", bodyText.slice(0, 400));
        throw new Error("invalid_json");
      }
      if (!data || typeof data !== "object") {
        throw new Error("invalid_json");
      }
      return data;
    })
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
    .catch((err) => {
      assureCsvPreviewUploadFailed(err);
    })
    .finally(() => {
      if (previewBtn) previewBtn.disabled = false;
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
  assureCsvFixLineHeader();

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

  const extraCols = document.getElementById("assure_csv_show_extra_cols");
  if (extraCols && extraCols.dataset.bound !== "1") {
    extraCols.dataset.bound = "1";
    extraCols.addEventListener("change", () => {
      assureCsvSetExtraColumnsVisible(extraCols.checked);
    });
  }
}

if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", assureCsvInit);
} else {
  assureCsvInit();
}
