<?php

require_once '../../includes/connect_endpoint.php';
require_once '../../includes/validate_endpoint.php';
require_once '../../includes/insurance/ins_endpoint_json.php';
ins_send_json_headers();
require_once '../../includes/insurance/ins_repository.php';

$name = $_POST['name'] ?? '';
$symbol = $_POST['symbol'] ?? '';
$code = $_POST['code'] ?? '';
$group = Ins_Repository::insAddInsuranceGroup($db, $userId, $name, $symbol, $code);
$success = $group !== null;

echo json_encode([
    'success' => $success,
    'group' => $group,
    'taxonomy' => $success ? Ins_Repository::insLoadTaxonomy($db, $userId) : [],
    'message' => $success ? 'Gruppe angelegt.' : 'Gruppe konnte nicht angelegt werden.',
]);

$db->close();
