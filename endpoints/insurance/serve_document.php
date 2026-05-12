<?php

require_once '../../includes/connect_endpoint.php';
require_once '../../includes/insurance/ins_repository.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(401);
    exit;
}

$documentId = intval($_GET['id'] ?? 0);
$document = $documentId > 0 ? Ins_Repository::insGetDocument($db, $documentId, $userId) : null;

if ($document === null || !is_file($document['path'])) {
    http_response_code(404);
    exit;
}

$mimeType = $document['mime_type'] ?: 'application/octet-stream';
$originalName = str_replace('"', '', $document['original_name']);

header('Content-Type: ' . $mimeType);
header('Content-Length: ' . filesize($document['path']));
header('Content-Disposition: inline; filename="' . $originalName . '"');
header('X-Content-Type-Options: nosniff');
readfile($document['path']);

$db->close();
exit;

?>
