<?php

require_once '../../includes/connect_endpoint.php';
require_once '../../includes/validate_endpoint.php';
require_once '../../includes/insurance/ins_endpoint_json.php';
ins_send_json_headers();
require_once '../../includes/insurance/ins_repository.php';

$typeId = intval($_POST['type_id'] ?? 0);
$success = $typeId > 0 && Ins_Repository::insDeleteInsuranceType($db, $userId, $typeId);

echo json_encode([
    'success' => $success,
    'taxonomy' => $success ? Ins_Repository::insLoadTaxonomy($db, $userId) : [],
    'message' => $success ? 'Versicherungsart gelöscht.' : 'Versicherungsart konnte nicht gelöscht werden.',
]);

$db->close();
