<?php
/**
 * Toolbar for subscriptions_table.php (AssureWallos).
 * Include from page scope (expects $headerClass, $i18n, filter/sort vars from header.php).
 */
if (!isset($headerClass)) {
    $headerClass = 'main-actions';
}
?>
  <header class="<?= htmlspecialchars($headerClass, ENT_QUOTES, 'UTF-8') ?> assure-subs-toolbar" id="main-actions">
    <button type="button" class="button" onClick="addSubscription()">
      <i class="fa-solid fa-circle-plus"></i>
      <?= translate('new_subscription', $i18n) ?>
    </button>
    <div class="top-actions assure-subs-toolbar__panel">
      <div class="search">
        <input type="text" autocomplete="off" name="search" id="search" placeholder="<?= translate('search', $i18n) ?>"
          onkeyup="searchSubscriptions()" />
        <span class="fa-solid fa-magnifying-glass search-icon"></span>
        <span class="fa-solid fa-xmark clear-search" onClick="clearSearch()"></span>
      </div>
      <div class="assure-subs-toolbar__tools">
        <div class="filtermenu on-dashboard">
          <button type="button" class="button secondary-button" id="filtermenu-button" title="<?= translate('filter', $i18n) ?>">
            <i class="fa-solid fa-filter"></i>
          </button>
          <?php
          $assure_include_table_filter_extra = true;
          include __DIR__ . '/../../filters_menu.php';
          ?>
        </div>
        <div class="sort-container">
          <button type="button" class="button secondary-button" value="Sort" onClick="toggleSortOptions()" id="sort-button"
            title="<?= translate('sort', $i18n) ?>">
            <i class="fa-solid fa-arrow-down-wide-short"></i>
          </button>
          <?php include __DIR__ . '/../../sort_options.php'; ?>
        </div>
        <div class="filtermenu assure-toolbar-more-menu on-dashboard">
          <button type="button" class="button secondary-button" id="assure-toolbar-more-toggle" title="Mehr" aria-haspopup="true" aria-expanded="false">
            <i class="fa-solid fa-ellipsis-vertical" aria-hidden="true"></i>
          </button>
          <div class="filtermenu-content" id="assure-toolbar-more-panel">
            <button type="button" class="assure-more-menu-item" id="assure-columns-toggle">
              <i class="fa-solid fa-columns" aria-hidden="true"></i>
              <span>Spalten</span>
            </button>
            <button type="button" class="assure-more-menu-item" id="assure-export-csv">
              <i class="fa-solid fa-file-csv" aria-hidden="true"></i>
              <span>CSV</span>
            </button>
            <button type="button" class="assure-more-menu-item" id="assure-export-pdf">
              <i class="fa-solid fa-file-pdf" aria-hidden="true"></i>
              <span>PDF</span>
            </button>
            <button type="button" class="assure-more-menu-item" id="assure-presets-toggle">
              <i class="fa-solid fa-bookmark" aria-hidden="true"></i>
              <span>Ansichten</span>
            </button>
          </div>
          <?php include __DIR__ . '/subscriptions_table_presets_panel.php'; ?>
          <div class="filtermenu assure-columns-menu on-dashboard">
            <div class="filtermenu-content" id="assure-columns-panel">
              <div class="filter-title">Spalten anzeigen</div>
              <div class="assure-columns-actions">
                <button type="button" class="secondary-button thin" id="assure-columns-default">Standard</button>
                <button type="button" class="secondary-button thin" id="assure-columns-all">Alle</button>
              </div>
              <div class="assure-columns-groups" id="assure-columns-list"></div>
            </div>
          </div>
        </div>
        <a href="subscriptions.php" class="button secondary-button assure-subs-toolbar__view" title="<?= translate('subscriptions', $i18n) ?>">
          <i class="fa-solid fa-table-cells"></i>
          <span class="assure-subs-toolbar__label">Kartenansicht</span>
        </a>
      </div>
    </div>
  </header>
