<?php

require_once '../../includes/connect_endpoint.php';
require_once '../../includes/validate_endpoint.php';
require_once '../../includes/insurance/ins_endpoint_json.php';
ins_send_json_headers();
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

$document = null;
if ($documentId) {
    $row = Ins_Repository::insGetDocument($db, (int) $documentId, $userId);
    if ($row) {
        $document = [
            'id' => (int) $row['id'],
            'doc_type' => (string) $row['doc_type'],
            'original_name' => (string) ($row['original_name'] ?? ''),
        ];
    }
}

echo json_encode([
    'success' => $documentId !== null && $document !== null,
    'document_id' => $documentId,
    'document' => $document,
    'message' => ($documentId !== null && $document !== null) ? 'Document uploaded.' : 'Document could not be uploaded.',
]);

$db->close();
