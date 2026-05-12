<?php

require_once '../../includes/connect_endpoint.php';
require_once '../../includes/validate_endpoint.php';
require_once '../../includes/insurance/ins_repository.php';

$subscriptionId = intval($_POST['subscription_id'] ?? 0);
$docType = $_POST['doc_type'] ?? 'other';

if ($subscriptionId <= 0 || empty($_FILES['document'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid document upload request.',
    ]);
    exit;
}

$documentId = Ins_Repository::insStoreSingleUploadedDocument($db, $subscriptionId, $userId, $docType, $_FILES['document']);

echo json_encode([
    'success' => $documentId !== null,
    'document_id' => $documentId,
    'message' => $documentId !== null ? 'Document uploaded.' : 'Document could not be uploaded.',
]);

$db->close();

?>
