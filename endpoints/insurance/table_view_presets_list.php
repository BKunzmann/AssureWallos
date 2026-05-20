<?php

require_once '../../includes/connect_endpoint.php';
require_once '../../includes/insurance/subscriptions_table_presets.php';

header('Content-Type: application/json; charset=UTF-8');

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Nicht angemeldet.']);
    $db->close();
    exit;
}

$userId = (int) $userId;
$data = Ins_Subscriptions_Table_Presets::insListForUser($db, $userId);

echo json_encode([
    'success' => true,
    'presets' => $data['presets'],
    'defaultId' => $data['defaultId'],
    'server' => Ins_Subscriptions_Table_Presets::insTableReady($db),
], JSON_UNESCAPED_UNICODE);

$db->close();
