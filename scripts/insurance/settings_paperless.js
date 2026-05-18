/* AssureWallos: Paperless-ngx instance settings (admin). */

function assurePaperlessEndpointUrl(path) {
  const base = document.body?.dataset?.appUrl;
  if (typeof base === "string" && base.length > 0) {
    return base.replace(/\/$/, "") + "/" + path.replace(/^\//, "");
  }
  return path;
}

function assurePaperlessNotify(message, isError) {
  if (typeof showErrorMessage === "function" && isError) {
    showErrorMessage(message);
  } else if (typeof showSuccessMessage === "function" && !isError) {
    showSuccessMessage(message);
  }

  const el = document.getElementById("assure-paperless_status");
  if (!el) return;
  el.hidden = !message;
  el.textContent = message || "";
  el.classList.toggle("assure-paperless-status-error", !!isError);
  el.classList.toggle("assure-paperless-status-ok", !isError && !!message);
}

function assurePaperlessUpdateTokenIndicator(hasToken) {
  const indicator = document.getElementById("assure_paperless_token_indicator");
  if (!indicator) return;
  indicator.hidden = !hasToken;
  indicator.textContent = hasToken
    ? "API-Token ist gespeichert (Feld absichtlich leer — nur zum Ändern neu eintragen)."
    : "";
}

function assurePaperlessApplySettings(data) {
  if (!data || typeof data !== "object") return;

  const bootstrap = document.getElementById("assure-settings-paperless-bootstrap");
  if (bootstrap) {
    bootstrap.textContent = JSON.stringify(data);
  }

  const enabled = document.getElementById("assure_paperless_enabled");
  const baseUrl = document.getElementById("assure_paperless_base_url");
  const matchMode = document.getElementById("assure_paperless_match_mode");
  const customField = document.getElementById("assure_paperless_custom_field_name");
  const tagPrefix = document.getElementById("assure_paperless_tag_prefix");
  const fallback = document.getElementById("assure_paperless_use_fallback");
  const cacheTtl = document.getElementById("assure_paperless_cache_ttl");
  const token = document.getElementById("assure_paperless_api_token");

  if (enabled) enabled.checked = parseInt(data.enabled, 10) === 1;
  if (baseUrl) baseUrl.value = data.base_url || "";
  if (matchMode) matchMode.value = data.match_mode || "custom_field";
  if (customField) customField.value = data.custom_field_name || "assure_subscription_id";
  if (tagPrefix) tagPrefix.value = data.tag_prefix || "aw-sub-";
  if (fallback) fallback.checked = parseInt(data.use_policy_number_fallback, 10) === 1;
  if (cacheTtl) cacheTtl.value = data.cache_ttl_seconds ?? 300;
  if (token) {
    token.value = "";
    token.placeholder = data.has_token
      ? "Neues Token nur zum Ersetzen eintragen"
      : "Token aus Paperless → Mein Profil";
  }

  assurePaperlessUpdateTokenIndicator(!!data.has_token);
  assurePaperlessSyncMatchModeUi();
}

function assurePaperlessApplyBootstrap() {
  const el = document.getElementById("assure-settings-paperless-bootstrap");
  if (!el || !String(el.textContent || "").trim()) {
    return;
  }

  try {
    assurePaperlessApplySettings(JSON.parse(el.textContent));
  } catch (e) {
    /* ignore */
  }
}

function assurePaperlessCollectFormData() {
  const fd = new FormData();
  const enabled = document.getElementById("assure_paperless_enabled");
  const baseUrl = document.getElementById("assure_paperless_base_url");
  const token = document.getElementById("assure_paperless_api_token");
  const matchMode = document.getElementById("assure_paperless_match_mode");
  const customField = document.getElementById("assure_paperless_custom_field_name");
  const tagPrefix = document.getElementById("assure_paperless_tag_prefix");
  const fallback = document.getElementById("assure_paperless_use_fallback");
  const cacheTtl = document.getElementById("assure_paperless_cache_ttl");

  fd.append("csrf_token", window.csrfToken || "");
  fd.append("enabled", enabled && enabled.checked ? "1" : "0");
  fd.append("base_url", baseUrl ? baseUrl.value.trim() : "");
  fd.append("api_token", token ? token.value.trim() : "");
  fd.append("match_mode", matchMode ? matchMode.value : "custom_field");
  fd.append("custom_field_name", customField ? customField.value.trim() : "");
  fd.append("tag_prefix", tagPrefix ? tagPrefix.value.trim() : "");
  fd.append("use_policy_number_fallback", fallback && fallback.checked ? "1" : "0");
  fd.append("cache_ttl_seconds", cacheTtl ? String(cacheTtl.value) : "300");

  return fd;
}

function assurePaperlessSetButtonsBusy(busy) {
  ["assure_paperless_save", "assure_paperless_test"].forEach((id) => {
    const btn = document.getElementById(id);
    if (!btn) return;
    btn.disabled = !!busy;
    btn.classList.toggle("disabled", !!busy);
  });
}

function assurePaperlessFetchJson(url, formData) {
  return fetch(url, {
    method: "POST",
    credentials: "same-origin",
    cache: "no-store",
    headers: {
      "X-CSRF-Token": window.csrfToken,
    },
    body: formData,
  }).then(async (response) => {
    const text = await response.text();
    let data;
    try {
      data = JSON.parse(text);
    } catch (e) {
      throw new Error(
        response.ok
          ? "Ungültige Server-Antwort."
          : `Server-Fehler (${response.status}). Seite neu laden oder Migration prüfen.`
      );
    }
    if (!response.ok && !data.message) {
      data.message = `Server-Fehler (${response.status}).`;
      data.success = false;
    }
    return data;
  });
}

function assurePaperlessSave() {
  const formData = assurePaperlessCollectFormData();
  const tokenVal = formData.get("api_token");
  const baseVal = formData.get("base_url");

  if (!baseVal) {
    assurePaperlessNotify("Bitte eine Basis-URL eintragen.", true);
    return Promise.resolve({ success: false });
  }

  assurePaperlessSetButtonsBusy(true);
  assurePaperlessNotify("Speichern…", false);

  return assurePaperlessFetchJson(
    assurePaperlessEndpointUrl("endpoints/insurance/save_paperless_settings.php"),
    formData
  )
    .then((data) => {
      if (data.success) {
        if (data.settings) {
          assurePaperlessApplySettings(data.settings);
        }
        assurePaperlessNotify(data.message || "Gespeichert.", false);
      } else {
        assurePaperlessNotify(data.message || "Speichern fehlgeschlagen.", true);
      }
      return data;
    })
    .catch((err) => {
      assurePaperlessNotify(err.message || "Speichern fehlgeschlagen.", true);
      return { success: false };
    })
    .finally(() => {
      assurePaperlessSetButtonsBusy(false);
    });
}

function assurePaperlessTest() {
  const formData = assurePaperlessCollectFormData();

  if (!formData.get("base_url")) {
    assurePaperlessNotify("Bitte Basis-URL eintragen.", true);
    return Promise.resolve({ success: false });
  }

  if (!formData.get("api_token")) {
    const indicator = document.getElementById("assure_paperless_token_indicator");
    if (!indicator || indicator.hidden) {
      assurePaperlessNotify(
        "Bitte API-Token eintragen (oder zuerst speichern, dann ohne Token testen).",
        true
      );
      return Promise.resolve({ success: false });
    }
  }

  assurePaperlessSetButtonsBusy(true);
  assurePaperlessNotify("Verbindung wird geprüft…", false);

  return assurePaperlessFetchJson(
    assurePaperlessEndpointUrl("endpoints/insurance/paperless_test.php"),
    formData
  )
    .then((data) => {
      assurePaperlessNotify(
        data.message || (data.success ? "Verbindung OK." : "Verbindung fehlgeschlagen."),
        !data.success
      );
      return data;
    })
    .catch((err) => {
      assurePaperlessNotify(err.message || "Verbindungstest fehlgeschlagen.", true);
      return { success: false };
    })
    .finally(() => {
      assurePaperlessSetButtonsBusy(false);
    });
}

function assurePaperlessSyncMatchModeUi() {
  const mode = document.getElementById("assure_paperless_match_mode")?.value || "custom_field";
  const panelCustom = document.getElementById("assure_paperless_panel_custom_field");
  const panelTag = document.getElementById("assure_paperless_panel_tag");
  const panelSearch = document.getElementById("assure_paperless_panel_search");
  const fallbackWrap = document.getElementById("assure_paperless_fallback_wrap");

  if (panelCustom) {
    panelCustom.classList.toggle("hide", mode !== "custom_field");
  }
  if (panelTag) {
    panelTag.classList.toggle("hide", mode !== "tag");
  }
  if (panelSearch) {
    panelSearch.classList.toggle("hide", mode !== "search");
  }
  if (fallbackWrap) {
    fallbackWrap.classList.toggle("hide", mode === "search");
  }
}

function assurePaperlessRegisterHandlers() {
  const section = document.getElementById("assure-paperless-settings");
  if (!section) return;

  assurePaperlessApplyBootstrap();

  const matchMode = document.getElementById("assure_paperless_match_mode");
  if (matchMode) {
    matchMode.addEventListener("change", assurePaperlessSyncMatchModeUi);
  }

  const saveBtn = document.getElementById("assure_paperless_save");
  if (saveBtn) {
    saveBtn.addEventListener("click", (e) => {
      e.preventDefault();
      assurePaperlessSave();
    });
  }

  const testBtn = document.getElementById("assure_paperless_test");
  if (testBtn) {
    testBtn.addEventListener("click", (e) => {
      e.preventDefault();
      assurePaperlessTest();
    });
  }
}

if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", assurePaperlessRegisterHandlers);
} else {
  assurePaperlessRegisterHandlers();
}
