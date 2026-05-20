<?php

require_once '../../includes/connect_endpoint.php';
require_once '../../includes/getdbkeys.php';
require_once '../../includes/list_subscriptions.php';
require_once '../../includes/getsettings.php';
require_once '../../includes/insurance/subscriptions_table_bootstrap.php';
require_once '../../includes/insurance/subscriptions_table_columns.php';
require_once '../../includes/insurance/subscriptions_table_data.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(403);
    exit;
}

$columnParam = isset($_GET['columns']) ? explode(',', (string) $_GET['columns']) : null;
$columnIds = Ins_Subscriptions_Table_Columns::insNormalizeVisible($columnParam);

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
    $columnIds
);

$exportIds = isset($_GET['ids']) ? array_filter(array_map('intval', explode(',', (string) $_GET['ids']))) : [];
$blocks = $loaded['renderBlocks'];
if ($exportIds !== []) {
    $blocks = array_values(array_filter($blocks, static function (array $block) use ($exportIds): bool {
        if (($block['type'] ?? '') !== 'data' || !isset($block['row']['id'])) {
            return ($block['type'] ?? '') === 'group_header' || ($block['type'] ?? '') === 'group_sum';
        }

        return in_array((int) $block['row']['id'], $exportIds, true);
    }));
}

$csv = Ins_Subscriptions_Table_Data::insRowsToCsv(
    $loaded['displayRows'],
    $columnIds,
    $blocks
);
$filename = 'assurewallos-vertraege-' . date('Y-m-d') . '.csv';

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: no-store');

echo $csv;
$db->close();
