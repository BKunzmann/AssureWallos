/* AssureWallos: Versicherungs-Taxonomie in den Einstellungen (UX wie Wallos-Währungen). */

const assureTaxonomySaveSvg = `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 48 48" id="File-Check-Alternate--Streamline-Plump.svg" height="48" width="48">
  <desc>File Check Alternate Streamline Icon: https://streamlinehq.com</desc>
  <g id="file-check-alternate--file-common-check">
    <path id="Subtract" class="accent-color" d="M13.582 2.137C16.326 1.823 20.685 1.5 27 1.5a165 165 0 0 1 5.13 0.077 1.5 1.5 0 0 1 0.4 0.068c1.098 0.343 4.029 1.564 8.123 5.578 3.862 3.787 5.195 6.563 5.63 7.781a1.5 1.5 0 0 1 0.087 0.45c0.08 2.153 0.13 4.655 0.13 7.546 0 7.57 -0.343 12.478 -0.669 15.432 -0.32 2.9 -2.518 5.1 -5.413 5.431 -2.744 0.314 -7.103 0.637 -13.418 0.637 -1.044 0 -2.035 -0.009 -2.974 -0.025A14.458 14.458 0 0 0 28.5 34c0 -8.008 -6.492 -14.5 -14.5 -14.5a14.44 14.44 0 0 0 -6.492 1.531c0.053 -6.464 0.364 -10.773 0.66 -13.463 0.32 -2.9 2.519 -5.1 5.414 -5.431Z" stroke-width="1"></path>
    <path id="Intersect" class="main-color" d="M46.348 15.25c-2.42 -0.001 -6.57 -0.04 -8.948 -0.268 -2.598 -0.249 -4.641 -2.321 -4.896 -4.975 -0.214 -2.233 -0.253 -5.99 -0.254 -8.421 0.095 0.01 0.188 0.03 0.28 0.059 1.098 0.343 4.029 1.564 8.123 5.578 3.862 3.787 5.195 6.563 5.63 7.781 0.029 0.08 0.05 0.163 0.065 0.246Z" stroke-width="1"></path>
    <path id="Subtract_2" class="main-color" fill-rule="evenodd" d="M14 46c6.627 0 12 -5.373 12 -12s-5.373 -12 -12 -12S2 27.373 2 34s5.373 12 12 12Z" clip-rule="evenodd" stroke-width="1"></path>
    <path id="Subtract_3" class="accent-color" fill-rule="evenodd" d="M20.611 31.185a2 2 0 1 0 -3.222 -2.37l-4.413 6.002L10.5 32.01a2 2 0 0 0 -3 2.647l4.118 4.666a2 2 0 0 0 3.111 -0.138l5.882 -8Z" clip-rule="evenodd" stroke-width="1"></path>
  </g>
</svg>`;

const assureTaxonomyDeleteSvg = `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 48 48" id="Recycle-Bin-2--Streamline-Plump.svg" height="48" width="48">
  <g id="recycle-bin-2--remove-delete-empty-bin-trash-garbage">
    <path id="Union" class="accent-color" d="M43.318 15.934a1.5 1.5 0 0 0 -1.618 -1.591c-3.016 0.246 -8.46 0.52 -17.721 0.52 -9.215 0 -14.65 -0.271 -17.675 -0.516a1.5 1.5 0 0 0 -1.618 1.59c0.888 13.84 1.74 21.07 2.253 24.547 0.332 2.252 1.85 4.217 4.226 4.788 2.445 0.588 6.55 1.227 12.837 1.227 6.286 0 10.392 -0.64 12.837 -1.227 2.375 -0.57 3.894 -2.536 4.226 -4.788 0.513 -3.477 1.365 -10.708 2.253 -24.55Z" stroke-width="1"/>
    <path id="Union_2" class="main-color" d="M23.37 1a8 8 0 0 0 -7.034 4.188c-3.411 0.072 -6 0.182 -7.814 0.282 -2.312 0.127 -4.692 1.242 -5.7 3.605 -0.244 0.57 -0.475 1.212 -0.663 1.919 -0.68 2.548 1.302 4.622 3.657 4.822 3.057 0.258 8.614 0.548 18.161 0.548 9.549 0 15.106 -0.29 18.162 -0.549 2.374 -0.2 4.291 -2.261 3.751 -4.785a16.68 16.68 0 0 0 -0.294 -1.167c-0.824 -2.831 -3.517 -4.277 -6.188 -4.411a260.66 260.66 0 0 0 -7.744 -0.264A8 8 0 0 0 24.631 1H23.37Z" stroke-width="1"/>
    <path id="Vector_831_Stroke" class="main-color" fill-rule="evenodd" d="M17.8 23.01a2 2 0 0 1 2.19 1.791l1 10a2 2 0 0 1 -3.98 0.398l-1 -10a2 2 0 0 1 1.79 -2.189Z" clip-rule="evenodd" stroke-width="1"/>
    <path id="Vector_832_Stroke" class="main-color" fill-rule="evenodd" d="M30.2 23.01a2 2 0 0 0 -2.19 1.791l-1 10a2 2 0 0 0 3.98 0.398l1 -10a2 2 0 0 0 -1.79 -2.189Z" clip-rule="evenodd" stroke-width="1"/>
  </g>
</svg>`;

let assureSettingsTaxonomy = [];
/** Neu angelegte Versicherungsart: bis zum ersten Speichern unten in der Liste anzeigen (Server sortiert sonst). */
let assureSettingsPinNewTypeId = null;

function assureInsuranceEndpointUrl(path) {
  const base = document.body?.dataset?.appUrl;
  if (typeof base === "string" && base.length > 0) {
    return base.replace(/\/$/, "") + "/" + path.replace(/^\//, "");
  }
  return path;
}

function assureSettingsApplyBootstrap() {
  const el = document.getElementById("assure-settings-taxonomy-bootstrap");
  if (!el || !String(el.textContent || "").trim()) {
    return false;
  }
  try {
    const data = JSON.parse(el.textContent);
    if (Array.isArray(data)) {
      assureSettingsTaxonomy = data;
      assureSettingsRenderTaxonomy();
      return true;
    }
  } catch (e) {
    /* ignore */
  }
  return false;
}

function assureSettingsRegisterStylesheet() {
  if (document.querySelector('link[href^="styles/insurance/form.css"]')) {
    return;
  }

  const link = document.createElement("link");
  link.rel = "stylesheet";
  link.href = "styles/insurance/form.css";
  document.head.appendChild(link);
}

function assureSettingsLoadTaxonomy() {
  assureSettingsApplyBootstrap();

  fetch(assureInsuranceEndpointUrl("endpoints/insurance/list_taxonomy.php"), {
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
      assureSettingsTaxonomy = data.taxonomy || [];
      assureSettingsRenderTaxonomy();
    })
    .catch(() => {
      if (!assureSettingsTaxonomy.length) {
        assureSettingsRenderTaxonomy();
      }
    });
}

function assureSettingsTextVal(el) {
  return (el && el.value != null ? String(el.value) : "").trim();
}

function assureSettingsBuildGroupSelect(selectedGroupId) {
  const sel = document.createElement("select");
  sel.className = "thin assure-taxonomy-group-select";
  sel.setAttribute("aria-label", "Versicherungsgruppe");
  assureSettingsTaxonomy.forEach((g) => {
    const o = document.createElement("option");
    o.value = String(g.id);
    o.textContent = g.name;
    if (parseInt(g.id, 10) === parseInt(selectedGroupId, 10)) {
      o.selected = true;
    }
    sel.appendChild(o);
  });
  return sel;
}

function assureSettingsFlattenTypes() {
  const list = [];
  assureSettingsTaxonomy.forEach((group) => {
    (group.types || []).forEach((type) => {
      list.push(type);
    });
  });
  return list;
}

/**
 * Sortiert die flache Typ-Liste so, dass eine frisch angelegte Zeile (Pin) zuletzt erscheint.
 */
function assureSettingsOrderTypesForDisplay(typesFlat) {
  const pinId = assureSettingsPinNewTypeId;
  if (pinId == null) {
    return typesFlat;
  }
  const pid = parseInt(pinId, 10);
  if (Number.isNaN(pid)) {
    assureSettingsPinNewTypeId = null;
    return typesFlat;
  }
  const pinned = typesFlat.filter((t) => parseInt(t.id, 10) === pid);
  const rest = typesFlat.filter((t) => parseInt(t.id, 10) !== pid);
  if (pinned.length === 0) {
    assureSettingsPinNewTypeId = null;
    return typesFlat;
  }
  return rest.concat(pinned);
}

function assureSettingsCreateTypeRowElement(type) {
  const row = document.createElement("div");
  row.className = "form-group-inline";
  row.dataset.assureTypeId = String(type.id);

  const inName = document.createElement("input");
  inName.type = "text";
  inName.name = "name";
  inName.autocomplete = "off";
  inName.value = type.name != null ? String(type.name) : "";
  inName.placeholder = "Art";

  const groupSel = assureSettingsBuildGroupSelect(type.group_id);

  const saveBtn = document.createElement("button");
  saveBtn.type = "button";
  saveBtn.className = "image-button medium";
  saveBtn.name = "save";
  saveBtn.title = "Speichern";
  saveBtn.innerHTML = assureTaxonomySaveSvg;
  saveBtn.addEventListener("click", () => assureSettingsSaveTypeRow(type.id));

  const delBtn = document.createElement("button");
  delBtn.type = "button";
  delBtn.className = "image-button medium";
  delBtn.name = "delete";
  delBtn.title = "Löschen";
  delBtn.innerHTML = assureTaxonomyDeleteSvg;
  delBtn.addEventListener("click", () => assureSettingsDeleteTypeRow(type.id, type.name));

  row.appendChild(inName);
  row.appendChild(groupSel);
  row.appendChild(saveBtn);
  row.appendChild(delBtn);
  return row;
}

function assureSettingsRenderTaxonomy() {
  const groupsEl = document.querySelector("#assure-settings-groups");
  const typesEl = document.querySelector("#assure-settings-types");
  if (!groupsEl || !typesEl) return;

  groupsEl.innerHTML = "";
  typesEl.innerHTML = "";

  if (!assureSettingsTaxonomy.length) {
    groupsEl.textContent = "Keine Versicherungsgruppen vorhanden.";
    typesEl.textContent = "Keine Versicherungsarten vorhanden.";
    return;
  }

  assureSettingsTaxonomy.forEach((group) => {
    const row = document.createElement("div");
    row.className = "form-group-inline";
    row.dataset.assureGroupId = String(group.id);

    const inName = document.createElement("input");
    inName.type = "text";
    inName.name = "name";
    inName.autocomplete = "off";
    inName.value = group.name != null ? String(group.name) : "";
    inName.placeholder = "Gruppenname";

    const saveBtn = document.createElement("button");
    saveBtn.type = "button";
    saveBtn.className = "image-button medium";
    saveBtn.name = "save";
    saveBtn.title = "Speichern";
    saveBtn.innerHTML = assureTaxonomySaveSvg;
    saveBtn.addEventListener("click", () => assureSettingsSaveGroupRow(group.id));

    const delBtn = document.createElement("button");
    delBtn.type = "button";
    delBtn.className = "image-button medium";
    delBtn.name = "delete";
    delBtn.title = "Löschen";
    delBtn.innerHTML = assureTaxonomyDeleteSvg;
    delBtn.addEventListener("click", () => assureSettingsDeleteGroupRow(group.id, group.name));

    row.appendChild(inName);
    row.appendChild(saveBtn);
    row.appendChild(delBtn);
    groupsEl.appendChild(row);
  });

  const typesOrdered = assureSettingsOrderTypesForDisplay(assureSettingsFlattenTypes());
  typesOrdered.forEach((type) => {
    typesEl.appendChild(assureSettingsCreateTypeRowElement(type));
  });

  if (assureSettingsPinNewTypeId != null) {
    const scrollId = assureSettingsPinNewTypeId;
    window.requestAnimationFrame(() => {
      const row = assureSettingsRowByTypeId(scrollId);
      if (row) {
        row.scrollIntoView({ block: "nearest", behavior: "smooth" });
      }
    });
  }
}

function assureSettingsRowByGroupId(groupId) {
  return document.querySelector(`#assure-settings-groups [data-assure-group-id="${groupId}"]`);
}

function assureSettingsRowByTypeId(typeId) {
  return document.querySelector(`#assure-settings-types [data-assure-type-id="${typeId}"]`);
}

function assureSettingsSaveGroupRow(groupId) {
  const row = assureSettingsRowByGroupId(groupId);
  if (!row) return;

  const saveBtn = row.querySelector('button[name="save"]');
  if (saveBtn) {
    saveBtn.classList.add("disabled");
    saveBtn.disabled = true;
  }

  const fd = new FormData();
  fd.append("group_id", groupId);
  fd.append("name", assureSettingsTextVal(row.querySelector('input[name="name"]')));
  fd.append("symbol", "");
  fd.append("code", "");

  fetch(assureInsuranceEndpointUrl("endpoints/insurance/save_group.php"), {
    method: "POST",
    credentials: "same-origin",
    cache: "no-store",
    headers: {
      "X-CSRF-Token": window.csrfToken,
    },
    body: fd,
  })
    .then((r) => r.json())
    .then((data) => {
      if (saveBtn) {
        saveBtn.classList.remove("disabled");
        saveBtn.disabled = false;
      }
      if (!data.success) {
        showErrorMessage(data.message || "Gruppe konnte nicht gespeichert werden.");
        return;
      }
      if (typeof showSuccessMessage === "function") {
        showSuccessMessage(data.message || "Gespeichert.");
      }
      assureSettingsTaxonomy = data.taxonomy || [];
      assureSettingsRenderTaxonomy();
    })
    .catch(() => {
      if (saveBtn) {
        saveBtn.classList.remove("disabled");
        saveBtn.disabled = false;
      }
      showErrorMessage("Gruppe konnte nicht gespeichert werden.");
    });
}

function assureSettingsSaveTypeRow(typeId) {
  const row = assureSettingsRowByTypeId(typeId);
  if (!row) return;

  const saveBtn = row.querySelector('button[name="save"]');
  if (saveBtn) {
    saveBtn.classList.add("disabled");
    saveBtn.disabled = true;
  }

  const groupSel = row.querySelector("select");
  const gid = groupSel ? parseInt(groupSel.value, 10) : 0;

  const fd = new FormData();
  fd.append("type_id", typeId);
  fd.append("group_id", gid);
  fd.append("name", assureSettingsTextVal(row.querySelector('input[name="name"]')));
  fd.append("symbol", "");
  fd.append("code", "");

  fetch(assureInsuranceEndpointUrl("endpoints/insurance/save_type.php"), {
    method: "POST",
    credentials: "same-origin",
    cache: "no-store",
    headers: {
      "X-CSRF-Token": window.csrfToken,
    },
    body: fd,
  })
    .then((r) => r.json())
    .then((data) => {
      if (saveBtn) {
        saveBtn.classList.remove("disabled");
        saveBtn.disabled = false;
      }
      if (!data.success) {
        showErrorMessage(data.message || "Versicherungsart konnte nicht gespeichert werden.");
        return;
      }
      if (parseInt(typeId, 10) === assureSettingsPinNewTypeId) {
        assureSettingsPinNewTypeId = null;
      }
      if (typeof showSuccessMessage === "function") {
        showSuccessMessage(data.message || "Gespeichert.");
      }
      assureSettingsTaxonomy = data.taxonomy || [];
      assureSettingsRenderTaxonomy();
    })
    .catch(() => {
      if (saveBtn) {
        saveBtn.classList.remove("disabled");
        saveBtn.disabled = false;
      }
      showErrorMessage("Versicherungsart konnte nicht gespeichert werden.");
    });
}

function assureSettingsDeleteGroupRow(groupId, groupName) {
  if (!confirm(`Gruppe „${groupName}“ inklusive aller zugehörigen Versicherungsarten wirklich löschen?`)) {
    return;
  }

  const fd = new FormData();
  fd.append("group_id", groupId);

  fetch(assureInsuranceEndpointUrl("endpoints/insurance/delete_group.php"), {
    method: "POST",
    credentials: "same-origin",
    cache: "no-store",
    headers: {
      "X-CSRF-Token": window.csrfToken,
    },
    body: fd,
  })
    .then((r) => r.json())
    .then((data) => {
      if (!data.success) {
        showErrorMessage(data.message || "Gruppe konnte nicht gelöscht werden.");
        return;
      }
      assureSettingsTaxonomy = data.taxonomy || [];
      assureSettingsRenderTaxonomy();
    })
    .catch(() => showErrorMessage("Gruppe konnte nicht gelöscht werden."));
}

function assureSettingsDeleteTypeRow(typeId, typeName) {
  if (!confirm(`Versicherungsart „${typeName}“ wirklich löschen?`)) {
    return;
  }

  const fd = new FormData();
  fd.append("type_id", typeId);

  fetch(assureInsuranceEndpointUrl("endpoints/insurance/delete_type.php"), {
    method: "POST",
    credentials: "same-origin",
    cache: "no-store",
    headers: {
      "X-CSRF-Token": window.csrfToken,
    },
    body: fd,
  })
    .then((r) => r.json())
    .then((data) => {
      if (!data.success) {
        showErrorMessage(data.message || "Versicherungsart konnte nicht gelöscht werden.");
        return;
      }
      if (parseInt(typeId, 10) === assureSettingsPinNewTypeId) {
        assureSettingsPinNewTypeId = null;
      }
      assureSettingsTaxonomy = data.taxonomy || [];
      assureSettingsRenderTaxonomy();
    })
    .catch(() => showErrorMessage("Versicherungsart konnte nicht gelöscht werden."));
}

function assureSettingsAddGroup() {
  const btn = document.querySelector("#assure_settings_add_group");
  if (btn) btn.disabled = true;

  const fd = new FormData();
  fd.append("name", "Neue Gruppe");
  fd.append("symbol", "");
  fd.append("code", "");

  fetch(assureInsuranceEndpointUrl("endpoints/insurance/add_group.php"), {
    method: "POST",
    credentials: "same-origin",
    cache: "no-store",
    headers: {
      "X-CSRF-Token": window.csrfToken,
    },
    body: fd,
  })
    .then((r) => r.json())
    .then((data) => {
      if (btn) btn.disabled = false;
      if (!data.success) {
        showErrorMessage(data.message || "Gruppe konnte nicht angelegt werden.");
        return;
      }
      assureSettingsTaxonomy = data.taxonomy || [];
      assureSettingsRenderTaxonomy();
    })
    .catch(() => {
      if (btn) btn.disabled = false;
      showErrorMessage("Gruppe konnte nicht angelegt werden.");
    });
}

function assureSettingsAddType() {
  const first = assureSettingsTaxonomy[0];
  if (!first || !first.id) {
    showErrorMessage("Bitte zuerst eine Versicherungsgruppe anlegen.");
    return;
  }

  const btn = document.querySelector("#assure_settings_add_type");
  if (btn) btn.disabled = true;

  const fd = new FormData();
  fd.append("group_id", first.id);
  fd.append("name", "Neue Versicherungsart");
  fd.append("symbol", "");
  fd.append("code", "");

  fetch(assureInsuranceEndpointUrl("endpoints/insurance/add_type.php"), {
    method: "POST",
    credentials: "same-origin",
    cache: "no-store",
    headers: {
      "X-CSRF-Token": window.csrfToken,
    },
    body: fd,
  })
    .then((r) => r.json())
    .then((data) => {
      if (btn) btn.disabled = false;
      if (!data.success) {
        showErrorMessage(data.message || "Versicherungsart konnte nicht angelegt werden.");
        return;
      }
      const nid =
        data.new_type_id != null
          ? parseInt(data.new_type_id, 10)
          : data.type && data.type.id != null
            ? parseInt(data.type.id, 10)
            : NaN;
      if (!Number.isNaN(nid)) {
        assureSettingsPinNewTypeId = nid;
      }
      assureSettingsTaxonomy = data.taxonomy || [];
      assureSettingsRenderTaxonomy();
    })
    .catch(() => {
      if (btn) btn.disabled = false;
      showErrorMessage("Versicherungsart konnte nicht angelegt werden.");
    });
}

document.addEventListener("DOMContentLoaded", function () {
  assureSettingsRegisterStylesheet();
  assureSettingsLoadTaxonomy();

  const addGroup = document.querySelector("#assure_settings_add_group");
  if (addGroup) {
    addGroup.addEventListener("click", function (e) {
      e.preventDefault();
      assureSettingsAddGroup();
    });
  }

  const addType = document.querySelector("#assure_settings_add_type");
  if (addType) {
    addType.addEventListener("click", function (e) {
      e.preventDefault();
      assureSettingsAddType();
    });
  }
});
