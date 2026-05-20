<?php

require_once '../../includes/connect_endpoint.php';
require_once '../../includes/getdbkeys.php';
require_once '../../includes/list_subscriptions.php';
require_once '../../includes/getsettings.php';
require_once '../../includes/insurance/subscriptions_table_bootstrap.php';
require_once '../../includes/insurance/subscriptions_table_columns.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(403);
    exit;
}

$columnParam = isset($_GET['columns']) ? explode(',', (string) $_GET['columns']) : null;
$columnIds = Ins_Subscriptions_Table_Columns::insNormalizeVisible($columnParam);
$columnDefs = Ins_Subscriptions_Table_Columns::insById();

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
    (int) $mainCurrencyId
);

$assure_table_rows = $loaded['displayRows'];
$assure_table_columns = $columnIds;
$assure_table_column_defs = $columnDefs;

if (count($loaded['subscriptions']) === 0) {
    ?>
    <div class="no-matching-subscriptions">
      <p>Keine passenden Verträge.</p>
      <button class="button" type="button" onClick="clearFilters()">
        <span class="fa-solid fa-minus-circle"></span>
        <?= htmlspecialchars(translate('clear_filters', $i18n), ENT_QUOTES, 'UTF-8') ?>
      </button>
      <img src="../../images/siteimages/empty.png" alt="" />
    </div>
    <?php
} else {
    include __DIR__ . '/../../includes/insurance/ui/subscriptions_table_view.php';
}

$db->close();
