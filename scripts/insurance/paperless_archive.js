/* AssureWallos: read-only Paperless archive list in the insurance form. */

const ASSURE_PAPERLESS_VIEW_KEY = "assure_paperless_archive_view";

let assurePaperlessArchiveSubscriptionId = 0;
let assurePaperlessArchiveDocuments = [];

function assurePaperlessArchiveEndpointUrl(path) {
  const base = document.body?.dataset?.appUrl;
  if (typeof base === "string" && base.length > 0) {
    return base.replace(/\/$/, "") + "/" + path.replace(/^\//, "");
  }
  return path;
}

function assurePaperlessFormatDate(iso) {
  if (!iso) return "";
  const d = new Date(iso);
  if (Number.isNaN(d.getTime())) return iso;
  return d.toLocaleDateString("de-DE", { year: "numeric", month: "2-digit", day: "2-digit" });
}

function assurePaperlessArchiveGetViewMode() {
  try {
    const stored = localStorage.getItem(ASSURE_PAPERLESS_VIEW_KEY);
    return stored === "preview" ? "preview" : "list";
  } catch {
    return "list";
  }
}

function assurePaperlessArchiveSetViewMode(mode) {
  const view = mode === "preview" ? "preview" : "list";
  try {
    localStorage.setItem(ASSURE_PAPERLESS_VIEW_KEY, view);
  } catch {
    /* ignore */
  }
  assurePaperlessArchiveSyncViewButtons(view);
  assurePaperlessArchiveRender(assurePaperlessArchiveDocuments);
}

function assurePaperlessArchiveSyncViewButtons(mode) {
  const toolbar = document.getElementById("assure-paperless-archive-toolbar");
  if (!toolbar) return;

  toolbar.querySelectorAll(".assure-paperless-view-btn").forEach((btn) => {
    const active = btn.dataset.view === mode;
    btn.classList.toggle("is-active", active);
    btn.setAttribute("aria-pressed", active ? "true" : "false");
  });
}

function assurePaperlessArchiveThumbUrl(subscriptionId, documentId) {
  return assurePaperlessArchiveEndpointUrl(
    `endpoints/insurance/paperless_thumb.php?subscription_id=${encodeURIComponent(subscriptionId)}&document_id=${encodeURIComponent(documentId)}`
  );
}

function assurePaperlessArchiveSetStatus(message) {
  const status = document.getElementById("assure-paperless-archive-status");
  if (!status) return;
  status.textContent = message || "";
  status.hidden = !message;
}

function assurePaperlessArchiveSetToolbarVisible(visible) {
  const toolbar = document.getElementById("assure-paperless-archive-toolbar");
  if (!toolbar) return;
  toolbar.classList.toggle("hide", !visible);
}

function assurePaperlessArchiveRenderList(documents) {
  const list = document.getElementById("assure-paperless-archive-list");
  if (!list) return;

  list.classList.remove("assure-paperless-archive-list--preview");
  list.innerHTML = "";

  documents.forEach((doc) => {
    const row = document.createElement("div");
    row.className = "form-group-inline assure-paperless-archive-row";

    const meta = document.createElement("span");
    meta.className = "assure-paperless-archive-date";
    meta.textContent = assurePaperlessFormatDate(doc.created);

    const link = document.createElement("a");
    link.className = "grow assure-paperless-archive-link";
    link.href = doc.archive_url || "#";
    link.target = "_blank";
    link.rel = "noopener noreferrer";
    link.textContent = doc.title || "Dokument";

    const open = document.createElement("a");
    open.className = "secondary-button thin assure-paperless-open-btn";
    open.href = doc.archive_url || "#";
    open.target = "_blank";
    open.rel = "noopener noreferrer";
    open.textContent = "In Paperless";

    row.appendChild(meta);
    row.appendChild(link);
    row.appendChild(open);
    list.appendChild(row);
  });
}

function assurePaperlessArchiveRenderPreview(documents, subscriptionId) {
  const list = document.getElementById("assure-paperless-archive-list");
  if (!list) return;

  list.classList.add("assure-paperless-archive-list--preview");
  list.innerHTML = "";

  documents.forEach((doc) => {
    const card = document.createElement("article");
    card.className = "assure-paperless-preview-card";

    const thumbLink = document.createElement("a");
    thumbLink.className = "assure-paperless-preview-thumb";
    thumbLink.href = doc.archive_url || "#";
    thumbLink.target = "_blank";
    thumbLink.rel = "noopener noreferrer";
    thumbLink.title = doc.title || "Dokument in Paperless öffnen";

    const img = document.createElement("img");
    img.className = "assure-paperless-preview-img";
    img.alt = doc.title ? `Vorschau: ${doc.title}` : "Dokumentvorschau";
    img.loading = "lazy";
    img.decoding = "async";
    img.src = assurePaperlessArchiveThumbUrl(subscriptionId, doc.id);
    img.addEventListener("error", () => {
      img.classList.add("is-broken");
      img.removeAttribute("src");
    });

    thumbLink.appendChild(img);

    const body = document.createElement("div");
    body.className = "assure-paperless-preview-body";

    const title = document.createElement("a");
    title.className = "assure-paperless-preview-title";
    title.href = doc.archive_url || "#";
    title.target = "_blank";
    title.rel = "noopener noreferrer";
    title.textContent = doc.title || "Dokument";

    const meta = document.createElement("span");
    meta.className = "assure-paperless-preview-date";
    meta.textContent = assurePaperlessFormatDate(doc.created);

    const open = document.createElement("a");
    open.className = "secondary-button thin assure-paperless-open-btn";
    open.href = doc.archive_url || "#";
    open.target = "_blank";
    open.rel = "noopener noreferrer";
    open.textContent = "In Paperless";

    body.appendChild(title);
    body.appendChild(meta);
    body.appendChild(open);

    card.appendChild(thumbLink);
    card.appendChild(body);
    list.appendChild(card);
  });
}

function assurePaperlessArchiveRender(documents) {
  const list = document.getElementById("assure-paperless-archive-list");
  if (!list) return;

  if (!documents.length) {
    list.innerHTML = "";
    list.hidden = true;
    assurePaperlessArchiveSetToolbarVisible(false);
    assurePaperlessArchiveSetStatus("Keine passenden Dokumente in Paperless gefunden.");
    return;
  }

  list.hidden = false;
  assurePaperlessArchiveSetStatus("");
  assurePaperlessArchiveSetToolbarVisible(true);
  assurePaperlessArchiveSyncViewButtons(assurePaperlessArchiveGetViewMode());

  const mode = assurePaperlessArchiveGetViewMode();
  if (mode === "preview" && assurePaperlessArchiveSubscriptionId > 0) {
    assurePaperlessArchiveRenderPreview(documents, assurePaperlessArchiveSubscriptionId);
  } else {
    assurePaperlessArchiveRenderList(documents);
  }
}

function assurePaperlessArchiveReset() {
  const wrap = document.getElementById("assure-paperless-archive");
  const list = document.getElementById("assure-paperless-archive-list");
  assurePaperlessArchiveSubscriptionId = 0;
  assurePaperlessArchiveDocuments = [];

  if (list) {
    list.innerHTML = "";
    list.hidden = true;
    list.classList.remove("assure-paperless-archive-list--preview");
  }
  if (wrap) {
    wrap.classList.add("hide");
  }
  assurePaperlessArchiveSetToolbarVisible(false);
  assurePaperlessArchiveSetStatus("");
}

function assurePaperlessArchiveInitToolbar() {
  const toolbar = document.getElementById("assure-paperless-archive-toolbar");
  if (!toolbar || toolbar.dataset.bound === "1") {
    return;
  }
  toolbar.dataset.bound = "1";
  assurePaperlessArchiveSyncViewButtons(assurePaperlessArchiveGetViewMode());

  toolbar.addEventListener("click", (event) => {
    const btn = event.target.closest(".assure-paperless-view-btn");
    if (!btn || !btn.dataset.view) return;
    assurePaperlessArchiveSetViewMode(btn.dataset.view);
  });
}

/**
 * Lädt Paperless-Dokumente für die geöffnete Versicherung (read-only).
 *
 * @param {number} subscriptionId
 */
function assureLoadPaperlessArchive(subscriptionId) {
  const wrap = document.getElementById("assure-paperless-archive");
  if (!wrap) return;

  assurePaperlessArchiveInitToolbar();

  const isInsurance = document.querySelector("#is_insurance");
  if (!isInsurance || !isInsurance.checked) {
    assurePaperlessArchiveReset();
    return;
  }

  const subId = parseInt(String(subscriptionId), 10);
  if (!subId || subId <= 0) {
    wrap.classList.remove("hide");
    assurePaperlessArchiveSetToolbarVisible(false);
    assurePaperlessArchiveSetStatus("Bitte speichern Sie den Vertrag, um das Paperless-Archiv zu laden.");
    return;
  }

  wrap.classList.remove("hide");
  assurePaperlessArchiveSetStatus("Archiv wird geladen…");
  assurePaperlessArchiveSetToolbarVisible(false);

  const url = assurePaperlessArchiveEndpointUrl(
    `endpoints/insurance/paperless_documents.php?subscription_id=${encodeURIComponent(subId)}`
  );

  fetch(url, {
    method: "GET",
    credentials: "same-origin",
    cache: "no-store",
    headers: {
      "X-CSRF-Token": window.csrfToken,
    },
  })
    .then((response) => response.json())
    .then((data) => {
      if (!data.success) {
        assurePaperlessArchiveSubscriptionId = subId;
        assurePaperlessArchiveDocuments = [];
        assurePaperlessArchiveRender([]);
        assurePaperlessArchiveSetStatus(data.message || "Paperless-Archiv konnte nicht geladen werden.");
        return;
      }

      if (!data.enabled) {
        assurePaperlessArchiveReset();
        return;
      }

      assurePaperlessArchiveSubscriptionId = subId;
      assurePaperlessArchiveDocuments = data.documents || [];
      assurePaperlessArchiveRender(assurePaperlessArchiveDocuments);
    })
    .catch(() => {
      assurePaperlessArchiveSubscriptionId = subId;
      assurePaperlessArchiveDocuments = [];
      assurePaperlessArchiveRender([]);
      assurePaperlessArchiveSetStatus("Paperless-Archiv konnte nicht geladen werden.");
    });
}

document.addEventListener("DOMContentLoaded", assurePaperlessArchiveInitToolbar);
