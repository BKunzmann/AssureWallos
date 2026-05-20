/* AssureWallos: subscriptions table view */

const ASSURE_TABLE_COLUMNS_KEY = "assure_subscriptions_table_columns";
const ASSURE_TABLE_GROUP_KEY = "assure_subscriptions_table_group";
const ASSURE_TABLE_PRESETS_KEY = "assure_subscriptions_table_presets";
const ASSURE_TABLE_DEFAULT_PRESET_KEY = "assure_subscriptions_table_default_preset_id";
const ASSURE_TABLE_PRESETS_MIGRATED_KEY = "assure_subscriptions_table_presets_server_migrated";
const ASSURE_TABLE_PRESETS_API = "endpoints/insurance/table_view_presets.php";
const ASSURE_TABLE_PRESETS_LIST_API = "endpoints/insurance/table_view_presets_list.php";

let assureTablePresetsCache = [];
let assureTablePresetsDefaultId = "";
let assureTablePresetsServerOk = false;

let assureTableColumnRegistry = [];
let assureTableVisibleColumns = [];
let assureTableCategories = [];
let assureTablePayments = [];
let assureTableInsuranceTypes = [];
let assureTableInsuranceGroups = [];
let assureTableMembers = [];
let assureTableDimensions = { sortOptions: [], groupPrimary: [], groupSecondary: [] };
let assureTableState = { sort: "next_payment", sortDir: "ASC", group: "none", group2: "" };
let assureTableSearchDebounce = null;

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
      const groupLabels = { abo: "Abo", versicherung: "Versicherung", vertrag: "Vertrag", meta: "Meta" };
      title.textContent = groupLabels[currentGroup] || currentGroup;
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
  assureTableClosePresetsPanel();
}

function assureTableClosePresetsPanel() {
  const panel = document.getElementById("assure-presets-panel");
  if (panel) panel.classList.remove("is-open");
}

function assureTableTogglePresetsPanel() {
  const panel = document.getElementById("assure-presets-panel");
  if (!panel) return;
  assureTableCloseMorePanel();
  assureTableCloseColumnsPanel();
  panel.classList.toggle("is-open");
}

function assureTableLoadPresetsLocal() {
  try {
    const raw = localStorage.getItem(ASSURE_TABLE_PRESETS_KEY);
    const parsed = raw ? JSON.parse(raw) : [];
    return Array.isArray(parsed) ? parsed : [];
  } catch {
    return [];
  }
}

function assureTableLoadPresets() {
  return assureTablePresetsCache;
}

function assureTablePresetsApiHeaders() {
  return {
    "Content-Type": "application/json",
    "X-CSRF-Token": window.csrfToken || "",
  };
}

async function assureTablePresetsApiPost(body) {
  const res = await fetch(ASSURE_TABLE_PRESETS_API, {
    method: "POST",
    headers: assureTablePresetsApiHeaders(),
    body: JSON.stringify(body),
  });
  return res.json();
}

async function assureTableRefreshPresetsFromServer() {
  try {
    const res = await fetch(ASSURE_TABLE_PRESETS_LIST_API);
    const data = await res.json();
    if (data.success && data.server) {
      assureTablePresetsServerOk = true;
      assureTablePresetsCache = Array.isArray(data.presets) ? data.presets : [];
      assureTablePresetsDefaultId = data.defaultId ? String(data.defaultId) : "";
      return true;
    }
  } catch (err) {
    console.error("Presets laden fehlgeschlagen", err);
  }
  assureTablePresetsServerOk = false;
  assureTablePresetsCache = assureTableLoadPresetsLocal();
  try {
    assureTablePresetsDefaultId = localStorage.getItem(ASSURE_TABLE_DEFAULT_PRESET_KEY) || "";
  } catch {
    assureTablePresetsDefaultId = "";
  }
  return false;
}

async function assureTableMaybeMigrateLocalPresets() {
  if (!assureTablePresetsServerOk) return;
  try {
    if (localStorage.getItem(ASSURE_TABLE_PRESETS_MIGRATED_KEY)) return;
  } catch {
    return;
  }
  const local = assureTableLoadPresetsLocal();
  if (local.length === 0) {
    try {
      localStorage.setItem(ASSURE_TABLE_PRESETS_MIGRATED_KEY, "1");
    } catch {
      /* ignore */
    }
    return;
  }
  let defaultLocalId = "";
  try {
    defaultLocalId = localStorage.getItem(ASSURE_TABLE_DEFAULT_PRESET_KEY) || "";
  } catch {
    /* ignore */
  }
  try {
    const data = await assureTablePresetsApiPost({
      action: "import_local",
      presets: local,
      defaultId: defaultLocalId,
    });
    if (data.success) {
      assureTablePresetsCache = data.presets || [];
      assureTablePresetsDefaultId = data.defaultId ? String(data.defaultId) : "";
      localStorage.setItem(ASSURE_TABLE_PRESETS_MIGRATED_KEY, "1");
      localStorage.removeItem(ASSURE_TABLE_PRESETS_KEY);
      localStorage.removeItem(ASSURE_TABLE_DEFAULT_PRESET_KEY);
    }
  } catch (err) {
    console.error("Preset-Migration fehlgeschlagen", err);
  }
}

function assureTableCaptureView() {
  return {
    name: "",
    sort: assureTableState.sort,
    sortDir: assureTableState.sortDir,
    group: assureTableState.group,
    group2: assureTableState.group2 || "",
    columns: [...assureTableVisibleColumns],
    savedAt: new Date().toISOString(),
  };
}

function assureTableApplyPreset(preset, skipFetch = false) {
  if (!preset) return;
  assureTableState.sort = preset.sort || "next_payment";
  assureTableState.sortDir = preset.sortDir || "ASC";
  assureTableState.group = preset.group || "none";
  assureTableState.group2 = preset.group2 || "";
  if (Array.isArray(preset.columns) && preset.columns.length > 0) {
    const valid = preset.columns.filter((id) =>
      assureTableColumnRegistry.some((c) => c.id === id)
    );
    if (valid.length > 0) {
      assureTableVisibleColumns = valid;
      assureTableSaveVisibleColumns(valid);
      assureTableApplyColumnPanelChecks();
    }
  }
  try {
    localStorage.setItem(
      ASSURE_TABLE_GROUP_KEY,
      JSON.stringify({ group: assureTableState.group, group2: assureTableState.group2 })
    );
  } catch {
    /* ignore */
  }
  assureTableSyncPresetSelects();
  if (!skipFetch) {
    assureFetchSubscriptionsTable();
  }
}

function assureTableFillSelect(sel, options, value, emptyOption = null) {
  if (!sel) return;
  sel.innerHTML = "";
  if (emptyOption) {
    const opt = document.createElement("option");
    opt.value = emptyOption.value;
    opt.textContent = emptyOption.label;
    sel.appendChild(opt);
  }
  options.forEach((o) => {
    const opt = document.createElement("option");
    opt.value = o.id;
    opt.textContent = o.label;
    sel.appendChild(opt);
  });
  if (value !== undefined && value !== null) {
    sel.value = value;
  }
}

function assureTableSyncPresetSelects() {
  const sortSel = document.getElementById("assure-preset-sort");
  const groupSel = document.getElementById("assure-preset-group");
  const group2Sel = document.getElementById("assure-preset-group2");
  const dirSel = document.getElementById("assure-preset-sort-dir");
  if (sortSel) sortSel.value = assureTableState.sort;
  if (dirSel) dirSel.value = assureTableState.sortDir;
  if (groupSel) groupSel.value = assureTableState.group;
  if (group2Sel) group2Sel.value = assureTableState.group2 || "";
}

function assureTableInitPresetSelects() {
  const sortOpts = assureTableDimensions.sortOptionsAll || assureTableDimensions.sortOptions || [];
  const groupOpts = assureTableDimensions.groupPrimaryAll || assureTableDimensions.groupPrimary || [];
  const group2Opts = assureTableDimensions.groupSecondaryAll || assureTableDimensions.groupSecondary || [];
  assureTableFillSelect(document.getElementById("assure-preset-sort"), sortOpts, assureTableState.sort);
  assureTableFillSelect(document.getElementById("assure-preset-group"), groupOpts, assureTableState.group);
  assureTableFillSelect(
    document.getElementById("assure-preset-group2"),
    group2Opts.filter((o) => o.id !== "none"),
    assureTableState.group2 || "",
    { value: "", label: "— Keine —" }
  );
  assureTableSyncPresetSelects();
}

function assureTableRenderPresetsList() {
  const list = document.getElementById("assure-presets-list");
  if (!list) return;
  const presets = assureTableLoadPresets();
  const defaultId = assureTablePresetsDefaultId;
  list.innerHTML = "";
  if (!assureTablePresetsServerOk) {
    const hint = document.createElement("div");
    hint.className = "assure-preset-empty assure-preset-hint";
    hint.style.padding = "0.5rem 0.75rem";
    hint.style.fontSize = "0.85rem";
    hint.textContent =
      "Ansichten nur lokal (Migration fehlt?). Nach DB-Update werden sie geräteübergreifend synchronisiert.";
    list.appendChild(hint);
  }
  if (presets.length === 0) {
    const empty = document.createElement("div");
    empty.className = "assure-preset-empty";
    empty.style.padding = "0.5rem 0.75rem";
    empty.style.fontSize = "0.85rem";
    empty.textContent = "Noch keine Ansichten gespeichert.";
    list.appendChild(empty);
    return;
  }
  presets.forEach((preset) => {
    const row = document.createElement("div");
    row.className = "assure-preset-item" + (String(preset.id) === String(defaultId) ? " is-default" : "");

    const loadBtn = document.createElement("button");
    loadBtn.type = "button";
    loadBtn.className = "assure-preset-item-name";
    loadBtn.textContent = preset.name || "Ansicht";
    loadBtn.addEventListener("click", () => {
      assureTableApplyPreset(preset);
      assureTableClosePresetsPanel();
    });

    const starBtn = document.createElement("button");
    starBtn.type = "button";
    starBtn.className = "secondary-button thin";
    starBtn.title = "Als Standard (geräteübergreifend)";
    starBtn.textContent = "★";
    starBtn.addEventListener("click", (e) => {
      e.stopPropagation();
      assureTableSetDefaultPreset(preset.id);
    });

    const delBtn = document.createElement("button");
    delBtn.type = "button";
    delBtn.className = "secondary-button thin";
    delBtn.title = "Löschen";
    delBtn.textContent = "×";
    delBtn.addEventListener("click", (e) => {
      e.stopPropagation();
      if (!confirm(`Ansicht „${preset.name}“ löschen?`)) return;
      assureTableDeletePreset(preset.id);
    });

    row.appendChild(loadBtn);
    row.appendChild(starBtn);
    row.appendChild(delBtn);
    list.appendChild(row);
  });
}

async function assureTableSetDefaultPreset(presetId) {
  if (assureTablePresetsServerOk) {
    try {
      const data = await assureTablePresetsApiPost({ action: "set_default", id: parseInt(presetId, 10) });
      if (data.success) {
        assureTablePresetsCache = data.presets || assureTablePresetsCache;
        assureTablePresetsDefaultId = data.defaultId ? String(data.defaultId) : String(presetId);
        assureTableRenderPresetsList();
        return;
      }
      alert(data.message || "Standard konnte nicht gespeichert werden.");
    } catch {
      alert("Standard konnte nicht gespeichert werden.");
    }
    return;
  }
  try {
    localStorage.setItem(ASSURE_TABLE_DEFAULT_PRESET_KEY, String(presetId));
    assureTablePresetsDefaultId = String(presetId);
  } catch {
    /* ignore */
  }
  assureTableRenderPresetsList();
}

async function assureTableDeletePreset(presetId) {
  if (assureTablePresetsServerOk) {
    try {
      const data = await assureTablePresetsApiPost({ action: "delete", id: parseInt(presetId, 10) });
      if (data.success) {
        assureTablePresetsCache = data.presets || [];
        assureTablePresetsDefaultId = data.defaultId ? String(data.defaultId) : "";
        assureTableRenderPresetsList();
        return;
      }
      alert(data.message || "Löschen fehlgeschlagen.");
    } catch {
      alert("Löschen fehlgeschlagen.");
    }
    return;
  }
  assureTablePresetsCache = assureTableLoadPresetsLocal().filter((p) => String(p.id) !== String(presetId));
  try {
    localStorage.setItem(ASSURE_TABLE_PRESETS_KEY, JSON.stringify(assureTablePresetsCache));
  } catch {
    /* ignore */
  }
  assureTableRenderPresetsList();
}

async function assureTableSaveCurrentPreset() {
  const name = prompt("Name für diese Ansicht:", "");
  if (name === null) return;
  const trimmed = name.trim();
  if (!trimmed) {
    alert("Bitte einen Namen eingeben.");
    return;
  }
  const payload = assureTableCaptureView();
  payload.name = trimmed;

  if (assureTablePresetsServerOk) {
    try {
      const data = await assureTablePresetsApiPost({ action: "save", ...payload });
      if (data.success) {
        assureTablePresetsCache = data.presets || [];
        assureTablePresetsDefaultId = data.defaultId ? String(data.defaultId) : assureTablePresetsDefaultId;
        assureTableRenderPresetsList();
        return;
      }
      alert(data.message || "Speichern fehlgeschlagen.");
    } catch {
      alert("Speichern fehlgeschlagen.");
    }
    return;
  }

  payload.id = `p_${Date.now()}`;
  const presets = assureTableLoadPresetsLocal();
  presets.push(payload);
  try {
    localStorage.setItem(ASSURE_TABLE_PRESETS_KEY, JSON.stringify(presets));
    assureTablePresetsCache = presets;
  } catch {
    /* ignore */
  }
  assureTableRenderPresetsList();
}

function assureTableApplyFromPresetPanel() {
  const sort = document.getElementById("assure-preset-sort")?.value;
  const sortDir = document.getElementById("assure-preset-sort-dir")?.value || "ASC";
  const group = document.getElementById("assure-preset-group")?.value || "none";
  const group2 = document.getElementById("assure-preset-group2")?.value || "";
  assureTableState.sort = sort || "next_payment";
  assureTableState.sortDir = sortDir;
  assureTableState.group = group;
  assureTableState.group2 = group2;
  try {
    localStorage.setItem(
      ASSURE_TABLE_GROUP_KEY,
      JSON.stringify({ group: assureTableState.group, group2: assureTableState.group2 })
    );
  } catch {
    /* ignore */
  }
  assureTableClosePresetsPanel();
  assureFetchSubscriptionsTable();
}

function assureTableInitFilterState() {
  if (!activeFilters["insurance_groups"]) activeFilters["insurance_groups"] = [];
  if (!activeFilters["insurance_types"]) activeFilters["insurance_types"] = [];
  if (!activeFilters["contract_status"]) activeFilters["contract_status"] = [];
  if (activeFilters["is_insurance"] === undefined) activeFilters["is_insurance"] = "";
}

function assureTableBuildFilterQuery(baseUrl) {
  let url = baseUrl;
  const append = (key, val) => {
    if (val === undefined || val === null || val === "") return;
    if (Array.isArray(val) && val.length === 0) return;
    url += url.includes("?") ? "&" : "?";
    url += `${key}=${encodeURIComponent(val)}`;
  };
  append("categories", activeFilters["categories"].join(","));
  append("members", activeFilters["members"].join(","));
  append("payments", activeFilters["payments"].join(","));
  append("state", activeFilters["state"]);
  append("renewalType", activeFilters["renewalType"]);
  append("is_insurance", activeFilters["is_insurance"]);
  append("insurance_groups", activeFilters["insurance_groups"].join(","));
  append("insurance_types", activeFilters["insurance_types"].join(","));
  append("contract_status", activeFilters["contract_status"].join(","));
  const searchVal = document.querySelector("#search")?.value?.trim();
  if (searchVal) append("q", searchVal);
  append("sort", assureTableState.sort);
  append("sortOrder_dir", assureTableState.sortDir);
  append("group", assureTableState.group);
  append("group2", assureTableState.group2);
  append("columns", assureTableVisibleColumns.join(","));
  return url;
}

function assureTableSyncUrl() {
  const params = new URLSearchParams();
  const add = (key, val) => {
    if (!val || (Array.isArray(val) && val.length === 0)) return;
    params.set(key, Array.isArray(val) ? val.join(",") : String(val));
  };
  add("categories", activeFilters["categories"]);
  add("members", activeFilters["members"]);
  add("payments", activeFilters["payments"]);
  add("state", activeFilters["state"]);
  add("renewalType", activeFilters["renewalType"]);
  add("is_insurance", activeFilters["is_insurance"]);
  add("insurance_groups", activeFilters["insurance_groups"]);
  add("insurance_types", activeFilters["insurance_types"]);
  add("contract_status", activeFilters["contract_status"]);
  const searchVal = document.querySelector("#search")?.value?.trim();
  if (searchVal) params.set("q", searchVal);
  if (assureTableState.sort && assureTableState.sort !== "next_payment") params.set("sort", assureTableState.sort);
  if (assureTableState.sortDir) params.set("sortOrder_dir", assureTableState.sortDir);
  if (assureTableState.group && assureTableState.group !== "none") params.set("group", assureTableState.group);
  if (assureTableState.group2) params.set("group2", assureTableState.group2);
  const qs = params.toString();
  const next = qs ? `${window.location.pathname}?${qs}` : window.location.pathname;
  window.history.replaceState({}, "", next);
}

function assureTableLabelFor(key, id) {
  const maps = {
    sort: Object.fromEntries((assureTableDimensions.sortOptions || []).map((o) => [o.id, o.label])),
    group: Object.fromEntries((assureTableDimensions.groupPrimary || []).map((o) => [o.id, o.label])),
    group2: Object.fromEntries((assureTableDimensions.groupSecondary || []).map((o) => [o.id, o.label])),
  };
  return maps[key]?.[id] || id;
}

function assureTableRenderChips() {
  const wrap = document.getElementById("assure-table-state-chips");
  if (!wrap) return;
  wrap.innerHTML = "";

  const addChip = (label, onClear) => {
    const chip = document.createElement("span");
    chip.className = "assure-state-chip";
    chip.innerHTML = `${label} <button type="button" class="assure-chip-clear" aria-label="Entfernen">×</button>`;
    chip.querySelector("button").addEventListener("click", onClear);
    wrap.appendChild(chip);
  };

  if (assureTableState.sort && assureTableState.sort !== "next_payment") {
    const dir = assureTableState.sortDir === "DESC" ? "▼" : "▲";
    addChip(`Sortiert: ${assureTableLabelFor("sort", assureTableState.sort)} ${dir}`, () => {
      assureTableState.sort = "next_payment";
      assureTableState.sortDir = "ASC";
      assureFetchSubscriptionsTable();
    });
  }

  if (assureTableState.group && assureTableState.group !== "none") {
    let label = `Gruppiert: ${assureTableLabelFor("group", assureTableState.group)}`;
    if (assureTableState.group2) {
      label += ` › ${assureTableLabelFor("group2", assureTableState.group2)}`;
    }
    addChip(label, () => {
      assureTableState.group = "none";
      assureTableState.group2 = "";
      try {
        localStorage.removeItem(ASSURE_TABLE_GROUP_KEY);
      } catch {
        /* ignore */
      }
      assureFetchSubscriptionsTable();
    });
  }

  if (activeFilters["is_insurance"] === "1") addChip("Nur Versicherungen", () => { activeFilters["is_insurance"] = ""; assureFetchSubscriptionsTable(); });
  if (activeFilters["is_insurance"] === "0") addChip("Nur Abos", () => { activeFilters["is_insurance"] = ""; assureFetchSubscriptionsTable(); });
}

function assureTableSetSort(sortKey, toggle = true) {
  if (!sortKey) return;
  if (toggle && assureTableState.sort === sortKey) {
    assureTableState.sortDir = assureTableState.sortDir === "ASC" ? "DESC" : "ASC";
  } else {
    assureTableState.sort = sortKey;
    assureTableState.sortDir = sortKey === "price" || sortKey === "id" ? "DESC" : "ASC";
  }
  assureFetchSubscriptionsTable();
}

function assureTableSetGroup(groupKey, level = "primary") {
  if (level === "secondary") {
    assureTableState.group2 = groupKey || "";
  } else {
    assureTableState.group = groupKey || "none";
    if (groupKey === "none") assureTableState.group2 = "";
  }
  try {
    localStorage.setItem(
      ASSURE_TABLE_GROUP_KEY,
      JSON.stringify({ group: assureTableState.group, group2: assureTableState.group2 })
    );
  } catch {
    /* ignore */
  }
  assureFetchSubscriptionsTable();
}

function assureTableBindHeaderMenus() {
  const dropdown = document.getElementById("assure-th-dropdown");
  if (!dropdown) return;

  const closeDropdown = () => {
    dropdown.classList.add("hide");
    dropdown.hidden = true;
    dropdown.innerHTML = "";
  };

  document.querySelectorAll(".assure-th-label").forEach((label) => {
    label.addEventListener("click", (e) => {
      const th = label.closest("th");
      if (!th?.dataset.sortable) return;
      e.stopPropagation();
      assureTableSetSort(th.dataset.sortKey, true);
    });
  });

  document.querySelectorAll(".assure-th-menu").forEach((btn) => {
    btn.addEventListener("click", (e) => {
      e.stopPropagation();
      const th = btn.closest("th");
      if (!th) return;
      const colId = th.dataset.column;
      const sortKey = th.dataset.sortKey;
      const groupKey = th.dataset.groupKey;
      const rect = btn.getBoundingClientRect();
      dropdown.innerHTML = "";
      dropdown.style.top = `${rect.bottom + window.scrollY}px`;
      dropdown.style.left = `${rect.left + window.scrollX}px`;
      dropdown.classList.remove("hide");
      dropdown.hidden = false;

      const addItem = (text, action) => {
        const item = document.createElement("button");
        item.type = "button";
        item.className = "assure-th-dropdown-item";
        item.textContent = text;
        item.addEventListener("click", () => {
          action();
          closeDropdown();
        });
        dropdown.appendChild(item);
      };

      if (th.dataset.sortable) {
        addItem("Sortieren (aufsteigend)", () => {
          assureTableState.sort = sortKey;
          assureTableState.sortDir = "ASC";
          assureFetchSubscriptionsTable();
        });
        addItem("Sortieren (absteigend)", () => {
          assureTableState.sort = sortKey;
          assureTableState.sortDir = "DESC";
          assureFetchSubscriptionsTable();
        });
      }
      if (th.dataset.groupable) {
        addItem("Gruppieren", () => assureTableSetGroup(groupKey, "primary"));
        addItem("Als Untergruppe", () => assureTableSetGroup(groupKey, "secondary"));
        addItem("Gruppierung aufheben", () => assureTableSetGroup("none", "primary"));
      }
      if (!th.dataset.sortable && !th.dataset.groupable) {
        closeDropdown();
      }
    });
  });

  document.addEventListener("click", closeDropdown);
}

function assureTableBindExtraFilters() {
  /* Insurance filter clicks handled in subscriptions.js (ASSUREWALLOS MOD). */
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
      assureTableBindHeaderMenus();
      const tbl = document.getElementById("assure-subscriptions-table");
      if (tbl?.dataset.sort) {
        assureTableState.sort = tbl.dataset.sort;
        assureTableState.sortDir = tbl.dataset.sortDir || "ASC";
      }
      assureTableRenderChips();
      assureTableSyncUrl();
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
        const tr = cb.closest("tr");
        if (!tr || tr.classList.contains("assure-table-group-header") || tr.classList.contains("assure-table-group-sum")) {
          return;
        }
        if (!tr.classList.contains("assure-row-hidden")) {
          cb.checked = selectAll.checked;
          tr.classList.toggle("assure-row-selected", selectAll.checked);
        }
      });
      assureTableUpdateBatchBar();
    };
  }

  table.querySelectorAll(".assure-row-select").forEach((cb) => {
    if (cb.closest(".assure-table-group-header, .assure-table-group-sum")) return;
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
  } else   if (action === "insurance_type") {
    assureTableInsuranceTypes.forEach((t) => {
      const opt = document.createElement("option");
      opt.value = t.id;
      opt.textContent = t.name;
      sel.appendChild(opt);
    });
    sel.classList.remove("hide");
    sel.hidden = false;
  } else if (action === "inactive") {
    [{ v: "0", l: "Aktiv" }, { v: "1", l: "Inaktiv" }].forEach(({ v, l }) => {
      const opt = document.createElement("option");
      opt.value = v;
      opt.textContent = l;
      sel.appendChild(opt);
    });
    sel.classList.remove("hide");
    sel.hidden = false;
  } else if (action === "auto_renew") {
    [{ v: "1", l: "Automatisch" }, { v: "0", l: "Manuell" }].forEach(({ v, l }) => {
      const opt = document.createElement("option");
      opt.value = v;
      opt.textContent = l;
      sel.appendChild(opt);
    });
    sel.classList.remove("hide");
    sel.hidden = false;
  } else if (action === "payer") {
    assureTableMembers.forEach((m) => {
      const opt = document.createElement("option");
      opt.value = m.id;
      opt.textContent = m.name;
      sel.appendChild(opt);
    });
    sel.classList.remove("hide");
    sel.hidden = false;
  } else if (action === "contract_status") {
    ["aktiv", "ruhend", "gekündigt", "beendet", "in Bearbeitung"].forEach((s) => {
      const opt = document.createElement("option");
      opt.value = s;
      opt.textContent = s.charAt(0).toUpperCase() + s.slice(1);
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
  if (action === "inactive") fields.inactive = parseInt(val, 10);
  if (action === "auto_renew") fields.auto_renew = parseInt(val, 10);
  if (action === "payer") fields.payer_user_id = parseInt(val, 10);
  if (action === "contract_status") fields.contract_status = val;

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

/** Server-Suche (Parameter q) mit Debounce. */
function assureTableSearch() {
  const searchInput = document.querySelector("#search");
  if (!searchInput) return;

  const searchContainer = searchInput.parentElement;
  const searchTerm = searchInput.value.trim();

  if (searchTerm.length > 0) {
    searchContainer?.classList.add("has-text");
  } else {
    searchContainer?.classList.remove("has-text");
  }

  clearTimeout(assureTableSearchDebounce);
  assureTableSearchDebounce = setTimeout(() => {
    assureFetchSubscriptionsTable();
  }, 350);
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

  if (params.has("is_insurance")) {
    activeFilters["is_insurance"] = params.get("is_insurance");
    hasFilter = true;
    document.querySelectorAll("[data-insurance-only]").forEach((el) => {
      el.classList.toggle("selected", el.getAttribute("data-insurance-only") === activeFilters["is_insurance"]);
    });
  }

  applyList("insurance_groups", "insurance_groups", "data-insurance-group");
  applyList("insurance_types", "insurance_types", "data-insurance-type");
  applyList("contract_status", "contract_status", "data-contract-status");

  if (params.has("q")) {
    const searchInput = document.querySelector("#search");
    if (searchInput) searchInput.value = params.get("q");
    hasFilter = true;
  }

  if (params.has("sort")) {
    assureTableState.sort = params.get("sort");
  }
  if (params.has("sortOrder_dir")) {
    assureTableState.sortDir = params.get("sortOrder_dir");
  }
  if (params.has("group")) {
    assureTableState.group = params.get("group");
  }
  if (params.has("group2")) {
    assureTableState.group2 = params.get("group2");
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
  table.querySelectorAll("tbody tr.assure-table-data-row").forEach((tr) => {
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

document.addEventListener("DOMContentLoaded", async () => {
  if (!document.getElementById("assure-subscriptions-table-page")) return;

  assureTableColumnRegistry = assureTableParseJson("assure-table-columns-json");
  assureTableCategories = assureTableParseJson("assure-table-categories-json");
  assureTablePayments = assureTableParseJson("assure-table-payments-json");
  assureTableInsuranceTypes = assureTableParseJson("assure-table-insurance-types-json");
  assureTableInsuranceGroups = assureTableParseJson("assure-table-insurance-groups-json");
  assureTableMembers = assureTableParseJson("assure-table-members-json");
  assureTableDimensions = assureTableParseJson("assure-table-dimensions-json") || assureTableDimensions;
  const initialState = assureTableParseJson("assure-table-initial-state-json");
  if (initialState && typeof initialState === "object" && !Array.isArray(initialState)) {
    assureTableState = { ...assureTableState, ...initialState };
  }
  assureTableInitFilterState();
  assureTableVisibleColumns = assureTableGetVisibleColumns();
  try {
    const storedGroup = localStorage.getItem(ASSURE_TABLE_GROUP_KEY);
    if (storedGroup && !window.location.search.includes("group=")) {
      const parsed = JSON.parse(storedGroup);
      if (parsed?.group) assureTableState.group = parsed.group;
      if (parsed?.group2) assureTableState.group2 = parsed.group2;
    }
  } catch {
    /* ignore */
  }

  await assureTableRefreshPresetsFromServer();
  await assureTableMaybeMigrateLocalPresets();

  const urlParams = new URLSearchParams(window.location.search);
  const urlHasViewState =
    urlParams.has("sort") || urlParams.has("group") || urlParams.has("group2");
  let appliedDefaultPreset = false;
  if (!urlHasViewState && assureTablePresetsDefaultId) {
    const def = assureTableLoadPresets().find(
      (p) => String(p.id) === String(assureTablePresetsDefaultId)
    );
    if (def) {
      assureTableApplyPreset(def, true);
      appliedDefaultPreset = true;
    }
  }

  assureTableInitPresetSelects();
  assureTableRenderPresetsList();
  assureTableBuildColumnPanel();
  assureTableApplyColumnVisibility();
  assureTableBindTableEvents();

  const defaultCols = assureTableColumnRegistry.filter((c) => c.default).map((c) => c.id).join(",");
  let shouldFetchTable =
    assureTableVisibleColumns.join(",") !== defaultCols ||
    urlHasViewState ||
    appliedDefaultPreset;

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

  const origClearFilters = window.clearFilters;
  window.clearFilters = function () {
    assureTableInitFilterState();
    activeFilters["categories"] = [];
    activeFilters["members"] = [];
    activeFilters["payments"] = [];
    activeFilters["state"] = "";
    activeFilters["renewalType"] = "";
    activeFilters["is_insurance"] = "";
    activeFilters["insurance_groups"] = [];
    activeFilters["insurance_types"] = [];
    activeFilters["contract_status"] = [];
    assureTableState.group = "none";
    assureTableState.group2 = "";
    if (typeof origClearFilters === "function") origClearFilters();
    assureFetchSubscriptionsTable();
  };

  assureTableBindExtraFilters();
  assureTableBindHeaderMenus();
  assureTableRenderChips();

  if (assureTableHydrateFiltersFromUrl()) {
    shouldFetchTable = true;
  }
  if (shouldFetchTable) {
    assureFetchSubscriptionsTable();
  }

  document.getElementById("assure-presets-toggle")?.addEventListener("click", (e) => {
    e.stopPropagation();
    assureTableCloseMorePanel();
    assureTableCloseColumnsPanel();
    assureTableTogglePresetsPanel();
  });

  document.getElementById("assure-preset-save")?.addEventListener("click", assureTableSaveCurrentPreset);
  document.getElementById("assure-preset-apply")?.addEventListener("click", assureTableApplyFromPresetPanel);

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
      assureTableClosePresetsPanel();
    }
    const presetsMenu = document.querySelector(".assure-presets-menu");
    if (presetsMenu && !presetsMenu.contains(e.target)) {
      assureTableClosePresetsPanel();
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
