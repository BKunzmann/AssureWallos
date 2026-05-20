<?php

require_once 'includes/header.php';
require_once 'includes/getdbkeys.php';
include_once 'includes/list_subscriptions.php';
require_once 'includes/insurance/subscriptions_table_bootstrap.php';
require_once 'includes/insurance/subscriptions_table_columns.php';
require_once 'includes/insurance/subscriptions_table_dimensions.php';
require_once 'includes/insurance/ins_repository.php';

$assure_table_column_registry = Ins_Subscriptions_Table_Columns::insAll();
$assure_table_column_defs = Ins_Subscriptions_Table_Columns::insById();
$columnParam = isset($_GET['columns']) ? explode(',', (string) $_GET['columns']) : null;
$assure_table_default_columns = Ins_Subscriptions_Table_Columns::insNormalizeVisible($columnParam);

$loaded = Ins_Subscriptions_Table_Bootstrap::insLoad(
    $db,
    (int) $userId,
    $settings,
    $_GET,
    $categories,
    $payment_methods,
    $members,
    $cycles,
    $currencies,
    (int) $mainCurrencyId,
    $assure_table_default_columns
);

$sort = $loaded['sort'];
$sortOrder = $loaded['sortOrder'];
$assure_table_sort = $sort;
$assure_table_sort_dir = $loaded['sortDir'];
$assure_table_group = $loaded['group'];
$assure_table_group2 = $loaded['group2'];
$assure_table_render_blocks = $loaded['renderBlocks'];
$assure_table_columns = $assure_table_default_columns;
$subscriptions = $loaded['subscriptions'];

$assure_insurance_types = [];
$assure_insurance_groups = [];
if (Ins_Repository::insTaxonomyReady($db)) {
    foreach (Ins_Repository::insLoadTaxonomy($db, (int) $userId) as $group) {
        $assure_insurance_groups[] = [
            'id' => (int) ($group['id'] ?? 0),
            'name' => (string) ($group['name'] ?? ''),
        ];
        foreach ($group['types'] ?? [] as $type) {
            $assure_insurance_types[] = [
                'id' => (int) ($type['id'] ?? 0),
                'name' => (string) ($type['name'] ?? ''),
                'group_id' => (int) ($type['group_id'] ?? 0),
            ];
        }
    }
}

$assure_table_dimensions = [
    'sortOptions' => Ins_Subscriptions_Table_Dimensions::insSortOptionsForUi(),
    'sortOptionsAll' => Ins_Subscriptions_Table_Dimensions::insSortOptionsAll(),
    'groupPrimary' => Ins_Subscriptions_Table_Dimensions::insGroupOptionsForUi('primary'),
    'groupSecondary' => Ins_Subscriptions_Table_Dimensions::insGroupOptionsForUi('secondary'),
    'groupPrimaryAll' => Ins_Subscriptions_Table_Dimensions::insGroupOptionsAll('primary'),
    'groupSecondaryAll' => Ins_Subscriptions_Table_Dimensions::insGroupOptionsAll('secondary'),
];

$headerClass = count($subscriptions) > 0 ? 'main-actions' : 'main-actions hidden';
?>
<link rel="stylesheet" href="styles/insurance/form.css?<?= $version ?>">
<link rel="stylesheet" href="styles/insurance/subscriptions_toolbar.css?<?= $version ?>">
<link rel="stylesheet" href="styles/insurance/subscriptions_table.css?<?= $version ?>">
<style>
  .logo-preview:after {
    content: '<?= translate('upload_logo', $i18n) ?>';
  }
</style>

<section class="contain assure-subscriptions-table-page" id="assure-subscriptions-table-page">
  <?php include __DIR__ . '/includes/insurance/ui/subscriptions_table_toolbar.php'; ?>
  <?php include __DIR__ . '/includes/insurance/ui/subscriptions_table_filter_chips.php'; ?>

  <div id="assure-batch-bar" class="assure-batch-bar hide" hidden>
    <span id="assure-batch-count">0 ausgewählt</span>
    <label class="hide" for="assure-batch-action">Aktion</label>
    <select id="assure-batch-action">
      <option value="">Aktion wählen…</option>
      <option value="delete">Löschen</option>
      <option value="category">Kategorie ändern</option>
      <option value="payment">Zahlungsart ändern</option>
      <option value="inactive">Status ändern</option>
      <option value="auto_renew">Verlängerung ändern</option>
      <option value="payer">Zahler ändern</option>
      <?php if ($assure_insurance_types !== []): ?>
        <option value="insurance_type">Versicherungsart ändern</option>
        <option value="contract_status">Vertragsstatus ändern</option>
      <?php endif; ?>
    </select>
    <select id="assure-batch-value" class="hide" hidden></select>
    <button type="button" class="button thin" id="assure-batch-run">Ausführen</button>
    <button type="button" class="secondary-button thin" id="assure-batch-clear">Auswahl aufheben</button>
  </div>

  <div id="assure-table-container">
    <?php
    if (count($subscriptions) === 0) {
        ?>
      <div class="empty-page">
        <img src="images/siteimages/empty.png" alt="<?= translate('empty_page', $i18n) ?>" />
        <p><?= translate('no_subscriptions_yet', $i18n) ?></p>
        <button type="button" class="button" onClick="addSubscription()">
          <i class="fa-solid fa-circle-plus"></i>
          <?= translate('add_first_subscription', $i18n) ?>
        </button>
      </div>
        <?php
    } else {
        include __DIR__ . '/includes/insurance/ui/subscriptions_table_view.php';
    }
    ?>
  </div>
</section>

<script type="application/json" id="assure-table-columns-json"><?=
  json_encode(
      $assure_table_column_registry,
      JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
  )
?></script>
<script type="application/json" id="assure-table-categories-json"><?=
  json_encode(array_values($categories), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)
?></script>
<script type="application/json" id="assure-table-payments-json"><?=
  json_encode(array_values($payment_methods), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)
?></script>
<script type="application/json" id="assure-table-insurance-types-json"><?=
  json_encode($assure_insurance_types, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)
?></script>
<script type="application/json" id="assure-table-insurance-groups-json"><?=
  json_encode($assure_insurance_groups, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)
?></script>
<script type="application/json" id="assure-table-members-json"><?=
  json_encode(array_values($members), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)
?></script>
<script type="application/json" id="assure-table-dimensions-json"><?=
  json_encode($assure_table_dimensions, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)
?></script>
<script type="application/json" id="assure-table-initial-state-json"><?=
  json_encode([
      'sort' => $assure_table_sort,
      'sortDir' => $assure_table_sort_dir,
      'group' => $assure_table_group,
      'group2' => $assure_table_group2,
  ], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)
?></script>

<script src="scripts/subscriptions.js?<?= $version ?>"></script>
<?php // START ASSUREWALLOS MOD
include __DIR__ . '/includes/insurance/ui/subscription_form_overlay.php';
?>
<script src="scripts/insurance/form_toggle.js?<?= $version ?>"></script>
<script src="scripts/insurance/paperless_archive.js?<?= $version ?>"></script>
<script src="scripts/libs/jspdf.umd.min.js?<?= $version ?>"></script>
<script src="scripts/libs/jspdf.plugin.autotable.min.js?<?= $version ?>"></script>
<script src="scripts/insurance/subscriptions_table.js?<?= $version ?>"></script>
<!-- END ASSUREWALLOS MOD -->

<?php
require_once 'includes/footer.php';
?>
