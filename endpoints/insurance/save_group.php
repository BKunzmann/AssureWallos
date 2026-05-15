<?php

require_once '../../includes/connect_endpoint.php';
require_once '../../includes/validate_endpoint.php';
require_once '../../includes/insurance/ins_endpoint_json.php';
ins_send_json_headers();
require_once '../../includes/insurance/ins_repository.php';

$groupId = intval($_POST['group_id'] ?? 0);
$name = $_POST['name'] ?? '';
$symbol = $_POST['symbol'] ?? '';
$code = $_POST['code'] ?? '';
$success = $groupId > 0 && Ins_Repository::insSaveInsuranceGroup($db, $userId, $groupId, $name, $symbol, $code);

echo json_encode([
    'success' => $success,
    'taxonomy' => $success ? Ins_Repository::insLoadTaxonomy($db, $userId) : [],
    'message' => $success ? 'Gruppe gespeichert.' : 'Gruppe konnte nicht gespeichert werden (Name bereits vergeben oder ungültig).',
]);

$db->close();
