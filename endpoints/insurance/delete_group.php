<?php

require_once '../../includes/connect_endpoint.php';
require_once '../../includes/validate_endpoint.php';
require_once '../../includes/insurance/ins_endpoint_json.php';
ins_send_json_headers();
require_once '../../includes/insurance/ins_repository.php';

$groupId = intval($_POST['group_id'] ?? 0);
$success = $groupId > 0 && Ins_Repository::insDeleteInsuranceGroup($db, $userId, $groupId);

echo json_encode([
    'success' => $success,
    'taxonomy' => $success ? Ins_Repository::insLoadTaxonomy($db, $userId) : [],
    'message' => $success ? 'Gruppe gelöscht.' : 'Gruppe konnte nicht gelöscht werden.',
]);

$db->close();
