/* AssureWallos: subscriptions table view */

const ASSURE_TABLE_COLUMNS_KEY = "assure_subscriptions_table_columns";

let assureTableColumnRegistry = [];
let assureTableVisibleColumns = [];
let assureTableCategories = [];
let assureTablePayments = [];
let assureTableInsuranceTypes = [];

function assureTableParseJson(id) {
  const el = document.getElementById(id);
  if (!el) return [];
  try {
    return JSON.parse(el.textContent || "[]");
  } catch {
    return [];
  }
}

function assureTableGetVisibleColumns() {
  try {
    const stored = localStorage.getItem(ASSURE_TABLE_COLUMNS_KEY);
    if (stored) {
      const parsed = JSON.parse(stored);
      if (Array.isArray(parsed) && parsed.length > 0) {
        const valid = parsed.filter((id) =>
          assureTableColumnRegistry.some((c) => c.id === id)
        );
        if (valid.length > 0) return valid;
      }
    }
  } catch {
    /* ignore */
  }
  return assureTableColumnRegistry.filter((c) => c.default).map((c) => c.id);
}

function assureTableSaveVisibleColumns(ids) {
  assureTableVisibleColumns = ids;
  try {
    localStorage.setItem(ASSURE_TABLE_COLUMNS_KEY, JSON.stringify(ids));
  } catch {
    /* ignore */
  }
  assureTableApplyColumnVisibility();
}

function assureTableApplyColumnVisibility() {
  const table = document.getElementById("assure-subscriptions-table");
  if (!table) return;

  const visible = new Set(assureTableVisibleColumns);
  table.querySelectorAll("[data-column]").forEach((cell) => {
    const col = cell.getAttribute("data-column");
    cell.classList.toggle("col-hidden", !visible.has(col));
  });
}

function assureTableCollectVisibleFromPanel() {
  return assureTableColumnRegistry
    .filter((col) => {
      const cb = document.getElementById(`assure-col-cb-${col.id}`);
      return cb && cb.checked;
    })
    .map((col) => col.id);
}

function assureTableApplyColumnPanelChecks() {
  assureTableColumnRegistry.forEach((col) => {
    const cb = document.getElementById(`assure-col-cb-${col.id}`);
    if (cb) {
      cb.checked = assureTableVisibleColumns.includes(col.id);
    }
  });
}

function assureTableBuildColumnPanel() {
  const list = document.getElementById("assure-columns-list");
  if (!list) return;

  list.innerHTML = "";
  let currentGroup = "";

  assureTableColumnRegistry.forEach((col) => {
    if (col.group !== currentGroup) {
      currentGroup = col.group;
      const title = document.createElement("div");
      title.className = "assure-column-group-title";
      title.textContent = currentGroup === "versicherung" ? "Versicherung" : "Abo";
      list.appendChild(title);
    }

    const item = document.createElement("div");
    item.className = "assure-column-item";

    const cb = document.createElement("input");
    cb.type = "checkbox";
    cb.id = `assure-col-cb-${col.id}`;
    cb.value = col.id;
    cb.checked = assureTableVisibleColumns.includes(col.id);

    const label = document.createElement("label");
    label.htmlFor = cb.id;
    label.textContent = col.label;

    cb.addEventListener("change", () => {
      const next = assureTableCollectVisibleFromPanel();
      if (next.length === 0) {
        cb.checked = true;
        return;
      }
      assureTableSaveVisibleColumns(next);
      assureFetchSubscriptionsTable();
    });

    item.appendChild(cb);
    item.appendChild(label);
    list.appendChild(item);
  });
}

function assureTableToggleColumnsPanel() {
  const panel = document.getElementById("assure-columns-panel");
  if (!panel) return;
  panel.classList.toggle("is-open");
}

function assureTableCloseColumnsPanel() {
  const panel = document.getElementById("assure-columns-panel");
  if (panel) panel.classList.remove("is-open");
}

function assureTableToggleMorePanel() {
  const panel = document.getElementById("assure-toolbar-more-panel");
  if (!panel) return;
  panel.classList.toggle("is-open");
  const toggle = document.getElementById("assure-toolbar-more-toggle");
  if (toggle) {
    toggle.setAttribute("aria-expanded", panel.classList.contains("is-open") ? "true" : "false");
  }
}

function assureTableCloseMorePanel() {
  const panel = document.getElementById("assure-toolbar-more-panel");
  if (panel) panel.classList.remove("is-open");
  const toggle = document.getElementById("assure-toolbar-more-toggle");
  if (toggle) toggle.setAttribute("aria-expanded", "false");
}

function assureTableBuildFilterQuery(baseUrl) {
  let url = baseUrl;
  const append = (key, val) => {
    if (!val || (Array.isArray(val) && val.length === 0)) return;
    url += url.includes("?") ? "&" : "?";
    url += `${key}=${encodeURIComponent(val)}`;
  };
  append("categories", activeFilters["categories"].join(","));
  append("members", activeFilters["members"].join(","));
  append("payments", activeFilters["payments"].join(","));
  append("state", activeFilters["state"]);
  append("renewalType", activeFilters["renewalType"]);
  append("columns", assureTableVisibleColumns.join(","));
  return url;
}

function assureFetchSubscriptionsTable() {
  const container = document.getElementById("assure-table-container");
  if (!container) return;

  const url = assureTableBuildFilterQuery("endpoints/subscriptions/get_table.php");
  fetch(url)
    .then((r) => r.text())
    .then((html) => {
      container.innerHTML = html;
      assureTableApplyColumnVisibility();
      assureTableBindTableEvents();
      assureTableSearch();
      const mainActions = document.getElementById("main-actions");
      if (mainActions) {
        if (html.includes("no-matching-subscriptions") || html.includes("assure-table-empty-row")) {
          /* keep visible */
        } else {
          mainActions.classList.remove("hidden");
        }
      }
    })
    .catch((err) => console.error("Tabellen-Reload fehlgeschlagen", err));
}

function assureTableBindTableEvents() {
  const table = document.getElementById("assure-subscriptions-table");
  if (!table) return;

  const selectAll = document.getElementById("assure-table-select-all");
  if (selectAll) {
    selectAll.checked = false;
    selectAll.onchange = () => {
      table.querySelectorAll(".assure-row-select").forEach((cb) => {
        if (!cb.closest("tr").classList.contains("assure-row-hidden")) {
          cb.checked = selectAll.checked;
          cb.closest("tr").classList.toggle("assure-row-selected", selectAll.checked);
        }
      });
      assureTableUpdateBatchBar();
    };
  }

  table.querySelectorAll(".assure-row-select").forEach((cb) => {
    cb.addEventListener("click", (e) => e.stopPropagation());
    cb.addEventListener("change", () => {
      cb.closest("tr").classList.toggle("assure-row-selected", cb.checked);
      assureTableUpdateBatchBar();
    });
  });

  table.querySelectorAll(".assure-table-data-row").forEach((row) => {
    row.addEventListener("click", (e) => {
      if (e.target.closest("input, button, a, label")) return;
      const id = row.getAttribute("data-id");
      if (id && typeof openEditSubscription === "function") {
        openEditSubscription(e, parseInt(id, 10));
      }
    });
  });
}

function assureTableSelectedIds() {
  const ids = [];
  document.querySelectorAll(".assure-row-select:checked").forEach((cb) => {
    const v = parseInt(cb.value, 10);
    if (v > 0) ids.push(v);
  });
  return ids;
}

function assureTableUpdateBatchBar() {
  const bar = document.getElementById("assure-batch-bar");
  const countEl = document.getElementById("assure-batch-count");
  if (!bar || !countEl) return;

  const ids = assureTableSelectedIds();
  const n = ids.length;
  if (n > 0) {
    bar.classList.remove("hide");
    bar.hidden = false;
    countEl.textContent = `${n} ausgewählt`;
  } else {
    bar.classList.add("hide");
    bar.hidden = true;
  }
}

function assureTableClearSelection() {
  document.querySelectorAll(".assure-row-select:checked").forEach((cb) => {
    cb.checked = false;
    cb.closest("tr")?.classList.remove("assure-row-selected");
  });
  const selectAll = document.getElementById("assure-table-select-all");
  if (selectAll) selectAll.checked = false;
  assureTableUpdateBatchBar();
}

function assureTablePopulateBatchValue(action) {
  const sel = document.getElementById("assure-batch-value");
  if (!sel) return;
  sel.innerHTML = "";
  sel.classList.add("hide");
  sel.hidden = true;

  if (action === "category") {
    assureTableCategories.forEach((c) => {
      const opt = document.createElement("option");
      opt.value = c.id;
      opt.textContent = c.name;
      sel.appendChild(opt);
    });
    sel.classList.remove("hide");
    sel.hidden = false;
  } else if (action === "payment") {
    assureTablePayments.forEach((p) => {
      const opt = document.createElement("option");
      opt.value = p.id;
      opt.textContent = p.name;
      sel.appendChild(opt);
    });
    sel.classList.remove("hide");
    sel.hidden = false;
  } else if (action === "insurance_type") {
    assureTableInsuranceTypes.forEach((t) => {
      const opt = document.createElement("option");
      opt.value = t.id;
      opt.textContent = t.name;
      sel.appendChild(opt);
    });
    sel.classList.remove("hide");
    sel.hidden = false;
  }
}

function assureTableRunBatch() {
  const action = document.getElementById("assure-batch-action")?.value;
  const ids = assureTableSelectedIds();
  if (!action || ids.length === 0) return;

  if (action === "delete") {
    if (!confirm(`${ids.length} Vertrag/Verträge wirklich löschen?`)) return;
    fetch("endpoints/subscription/batch_delete.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        "X-CSRF-Token": window.csrfToken,
      },
      body: JSON.stringify({ ids }),
    })
      .then((r) => r.json())
      .then((data) => {
        if (data.success || data.deleted > 0) {
          assureTableClearSelection();
          assureFetchSubscriptionsTable();
        } else {
          alert(data.message || "Löschen fehlgeschlagen.");
        }
      });
    return;
  }

  const valueSel = document.getElementById("assure-batch-value");
  const val = valueSel?.value;
  if (!val) {
    alert("Bitte einen Wert wählen.");
    return;
  }

  const fields = {};
  if (action === "category") fields.category_id = parseInt(val, 10);
  if (action === "payment") fields.payment_method_id = parseInt(val, 10);
  if (action === "insurance_type") fields.insurance_type_id = parseInt(val, 10);

  fetch("endpoints/subscription/batch_update.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
      "X-CSRF-Token": window.csrfToken,
    },
    body: JSON.stringify({ ids, fields }),
  })
    .then((r) => r.json())
    .then((data) => {
      if (data.warnings?.length) {
        alert(data.warnings.join("\n"));
      }
      if (data.success) {
        assureTableClearSelection();
        assureFetchSubscriptionsTable();
      } else {
        alert(data.message || "Aktualisierung fehlgeschlagen.");
      }
    });
}

/** Clientseitige Suche wie searchSubscriptions() in subscriptions.js (Kartenansicht). */
function assureTableSearch() {
  const searchInput = document.querySelector("#search");
  if (!searchInput) return;

  const searchContainer = searchInput.parentElement;
  const searchTerm = searchInput.value.trim().toLowerCase();

  if (searchTerm.length > 0) {
    searchContainer?.classList.add("has-text");
  } else {
    searchContainer?.classList.remove("has-text");
  }

  document.querySelectorAll(".assure-table-data-row").forEach((row) => {
    const name = (row.getAttribute("data-name") || "").toLowerCase();
    const policy = (row.getAttribute("data-policy") || "").toLowerCase();
    const match = !searchTerm || name.includes(searchTerm) || policy.includes(searchTerm);
    row.classList.toggle("assure-row-hidden", !match);
  });
}

function assureTableHydrateFiltersFromUrl() {
  const params = new URLSearchParams(window.location.search);
  let hasFilter = false;

  const applyList = (ajaxKey, pageKey, dataAttr) => {
    const raw = params.get(ajaxKey) || params.get(pageKey);
    if (!raw) return;
    const ids = raw.split(",").filter(Boolean);
    if (ids.length === 0) return;
    activeFilters[ajaxKey] = ids;
    hasFilter = true;
    ids.forEach((id) => {
      const item = document.querySelector(`.filter-item[${dataAttr}="${id}"]`);
      if (item) item.classList.add("selected");
    });
  };

  applyList("categories", "category", "data-categoryid");
  applyList("members", "member", "data-memberid");
  applyList("payments", "payment", "data-paymentid");

  if (params.has("state")) {
    const state = params.get("state");
    activeFilters["state"] = state;
    hasFilter = true;
    document.querySelectorAll('.filter-item[data-state]').forEach((el) => {
      el.classList.toggle("selected", el.getAttribute("data-state") === state);
    });
  }

  if (params.has("renewalType")) {
    const renewalType = params.get("renewalType");
    activeFilters["renewalType"] = renewalType;
    hasFilter = true;
    document.querySelectorAll('.filter-item[data-renewaltype]').forEach((el) => {
      el.classList.toggle("selected", el.getAttribute("data-renewaltype") === renewalType);
    });
  }

  const clearBtn = document.querySelector("#clear-filters");
  if (clearBtn) {
    clearBtn.classList.toggle("hide", !hasFilter);
  }

  return hasFilter;
}

function assureTableExportCsv() {
  const url = assureTableBuildFilterQuery("endpoints/insurance/table_export_csv.php");
  window.location.href = url;
}

function assureTableExportPdf() {
  if (typeof window.jspdf === "undefined" && typeof window.jsPDF === "undefined") {
    alert("PDF-Bibliothek nicht geladen.");
    return;
  }
  const { jsPDF } = window.jspdf || { jsPDF: window.jsPDF };
  const table = document.getElementById("assure-subscriptions-table");
  if (!table) return;

  const doc = new jsPDF({ orientation: "landscape", unit: "mm", format: "a4" });
  doc.setFontSize(14);
  doc.text("AssureWallos – Vertragsübersicht", 14, 14);
  doc.setFontSize(9);
  doc.text(new Date().toLocaleDateString("de-DE"), 14, 20);

  const headers = [];
  table.querySelectorAll("thead th[data-column]").forEach((th) => {
    if (!th.classList.contains("col-hidden") && th.dataset.column !== "logo") {
      headers.push(th.textContent.trim());
    }
  });

  const body = [];
  table.querySelectorAll("tbody tr.assure-table-data-row:not(.assure-row-hidden)").forEach((tr) => {
    const row = [];
    tr.querySelectorAll("td[data-column]").forEach((td) => {
      if (!td.classList.contains("col-hidden") && td.dataset.column !== "logo") {
        row.push(td.textContent.trim());
      }
    });
    if (row.length) body.push(row);
  });

  if (typeof doc.autoTable === "function") {
    doc.autoTable({
      head: [headers],
      body,
      startY: 26,
      styles: { fontSize: 7, cellPadding: 1.5 },
      headStyles: { fillColor: [59, 130, 246] },
    });
  }

  doc.save(`assurewallos-vertraege-${new Date().toISOString().slice(0, 10)}.pdf`);
}

document.addEventListener("DOMContentLoaded", () => {
  if (!document.getElementById("assure-subscriptions-table-page")) return;

  assureTableColumnRegistry = assureTableParseJson("assure-table-columns-json");
  assureTableCategories = assureTableParseJson("assure-table-categories-json");
  assureTablePayments = assureTableParseJson("assure-table-payments-json");
  assureTableInsuranceTypes = assureTableParseJson("assure-table-insurance-types-json");
  assureTableVisibleColumns = assureTableGetVisibleColumns();

  assureTableBuildColumnPanel();
  assureTableApplyColumnVisibility();
  assureTableBindTableEvents();

  const defaultCols = assureTableColumnRegistry.filter((c) => c.default).map((c) => c.id).join(",");
  if (assureTableVisibleColumns.join(",") !== defaultCols) {
    assureFetchSubscriptionsTable();
  }

  window.fetchSubscriptions = function () {
    assureFetchSubscriptionsTable();
  };

  window.searchSubscriptions = function () {
    assureTableSearch();
  };

  window.clearSearch = function () {
    const searchInput = document.querySelector("#search");
    if (searchInput) searchInput.value = "";
    assureTableSearch();
  };

  if (assureTableHydrateFiltersFromUrl()) {
    assureFetchSubscriptionsTable();
  }

  document.getElementById("assure-toolbar-more-toggle")?.addEventListener("click", (e) => {
    e.stopPropagation();
    assureTableCloseColumnsPanel();
    assureTableToggleMorePanel();
  });

  document.getElementById("assure-columns-toggle")?.addEventListener("click", (e) => {
    e.stopPropagation();
    assureTableCloseMorePanel();
    assureTableToggleColumnsPanel();
  });

  document.addEventListener("click", (e) => {
    const moreMenu = document.querySelector(".assure-toolbar-more-menu");
    if (moreMenu && !moreMenu.contains(e.target)) {
      assureTableCloseMorePanel();
      assureTableCloseColumnsPanel();
    }
  });

  document.getElementById("assure-columns-default")?.addEventListener("click", () => {
    assureTableVisibleColumns = assureTableColumnRegistry.filter((c) => c.default).map((c) => c.id);
    assureTableApplyColumnPanelChecks();
    assureTableSaveVisibleColumns(assureTableVisibleColumns);
    assureFetchSubscriptionsTable();
  });

  document.getElementById("assure-columns-all")?.addEventListener("click", () => {
    assureTableVisibleColumns = assureTableColumnRegistry.map((c) => c.id);
    assureTableApplyColumnPanelChecks();
    assureTableSaveVisibleColumns(assureTableVisibleColumns);
    assureFetchSubscriptionsTable();
  });

  if (typeof window.closeAddSubscription === "function") {
    const origClose = window.closeAddSubscription;
    window.closeAddSubscription = function () {
      origClose.apply(this, arguments);
      assureFetchSubscriptionsTable();
    };
  }

  document.getElementById("assure-batch-action")?.addEventListener("change", (e) => {
    assureTablePopulateBatchValue(e.target.value);
  });

  document.getElementById("assure-batch-run")?.addEventListener("click", assureTableRunBatch);
  document.getElementById("assure-batch-clear")?.addEventListener("click", assureTableClearSelection);
  document.getElementById("assure-export-csv")?.addEventListener("click", (e) => {
    e.stopPropagation();
    assureTableCloseMorePanel();
    assureTableExportCsv();
  });
  document.getElementById("assure-export-pdf")?.addEventListener("click", (e) => {
    e.stopPropagation();
    assureTableCloseMorePanel();
    assureTableExportPdf();
  });
});
