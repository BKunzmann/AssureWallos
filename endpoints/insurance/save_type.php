<?php

require_once '../../includes/connect_endpoint.php';
require_once '../../includes/validate_endpoint.php';
require_once '../../includes/insurance/ins_endpoint_json.php';
ins_send_json_headers();
require_once '../../includes/insurance/ins_repository.php';

$typeId = intval($_POST['type_id'] ?? 0);
$groupId = intval($_POST['group_id'] ?? 0);
$name = $_POST['name'] ?? '';
$symbol = $_POST['symbol'] ?? '';
$code = $_POST['code'] ?? '';
$success = $typeId > 0 && $groupId > 0 && Ins_Repository::insSaveInsuranceType($db, $userId, $typeId, $groupId, $name, $symbol, $code);

echo json_encode([
    'success' => $success,
    'taxonomy' => $success ? Ins_Repository::insLoadTaxonomy($db, $userId) : [],
    'message' => $success ? 'Versicherungsart gespeichert.' : 'Versicherungsart konnte nicht gespeichert werden (Name in Gruppe schon vergeben oder ungültig).',
]);

$db->close();
