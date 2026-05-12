<?php

require_once '../../includes/connect_endpoint.php';
require_once '../../includes/validate_endpoint.php';
require_once '../../includes/insurance/ins_repository.php';

$documentId = intval($_POST['id'] ?? 0);

if ($documentId <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid document.',
    ]);
    exit;
}

$success = Ins_Repository::insDeleteDocument($db, $documentId, $userId);

echo json_encode([
    'success' => $success,
    'message' => $success ? 'Document deleted.' : 'Document could not be deleted.',
]);

$db->close();

?>
