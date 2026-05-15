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
$type = $groupId > 0 ? Ins_Repository::insAddInsuranceType($db, $userId, $groupId, $name, $symbol, $code) : null;
$success = $type !== null;

echo json_encode([
    'success' => $success,
    'type' => $type,
    /** Für die Einstellungs-UI: neu angelegte Zeile unten anpinnen, bis sie gespeichert wurde */
    'new_type_id' => $success && is_array($type) && isset($type['id']) ? (int) $type['id'] : null,
    'taxonomy' => $success ? Ins_Repository::insLoadTaxonomy($db, $userId) : [],
    'message' => $success ? 'Versicherungsart angelegt.' : 'Versicherungsart konnte nicht angelegt werden.',
]);

$db->close();
