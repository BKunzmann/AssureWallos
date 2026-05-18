<?php
/**
 * Read-only Paperless-ngx archive block in the insurance documents section.
 */
?>
<div class="assure-paperless-archive-wrap hide assure-section" id="assure-paperless-archive">
  <div class="assure-section-heading assure-paperless-archive-heading">
    <h4>Paperless-Archiv</h4>
    <div class="assure-paperless-archive-toolbar hide" id="assure-paperless-archive-toolbar" role="group" aria-label="Archiv-Ansicht">
      <span class="assure-paperless-view-label">Ansicht</span>
      <button type="button" class="secondary-button thin assure-paperless-view-btn is-active" data-view="list" aria-pressed="true">
        Liste
      </button>
      <button type="button" class="secondary-button thin assure-paperless-view-btn" data-view="preview" aria-pressed="false">
        Vorschau
      </button>
    </div>
  </div>
  <p id="assure-paperless-archive-status" class="assure-field-note" role="status" aria-live="polite">
    Archiv wird geladen…
  </p>
  <div id="assure-paperless-archive-list" class="assure-paperless-archive-list" hidden></div>
</div>
