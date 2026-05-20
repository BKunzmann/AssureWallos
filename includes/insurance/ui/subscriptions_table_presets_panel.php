<?php
/**
 * Presets panel (⋮ menu) for table view state.
 */
?>
<div class="filtermenu assure-presets-menu on-dashboard">
  <div class="filtermenu-content assure-presets-panel" id="assure-presets-panel">
    <div class="filter-title">Gespeicherte Ansichten</div>
    <div id="assure-presets-list" class="assure-presets-list"></div>
    <div class="assure-presets-actions">
      <button type="button" class="secondary-button thin" id="assure-preset-save">Aktuelle Ansicht speichern…</button>
    </div>
    <div class="filter-title assure-presets-advanced-title">Ansicht einstellen</div>
    <label class="assure-preset-field">
      <span>Sortieren nach</span>
      <select id="assure-preset-sort"></select>
    </label>
    <label class="assure-preset-field">
      <span>Richtung</span>
      <select id="assure-preset-sort-dir">
        <option value="ASC">Aufsteigend</option>
        <option value="DESC">Absteigend</option>
      </select>
    </label>
    <label class="assure-preset-field">
      <span>Gruppieren</span>
      <select id="assure-preset-group"></select>
    </label>
    <label class="assure-preset-field">
      <span>Untergruppe</span>
      <select id="assure-preset-group2">
        <option value="">— Keine —</option>
      </select>
    </label>
    <button type="button" class="button thin" id="assure-preset-apply">Ansicht anwenden</button>
  </div>
</div>
