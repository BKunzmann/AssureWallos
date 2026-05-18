<?php

require_once '../../includes/connect_endpoint.php';
require_once '../../includes/insurance/ins_endpoint_json.php';
ins_send_json_headers();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    echo json_encode([
        'success' => false,
        'enabled' => false,
        'documents' => [],
        'message' => 'Nicht angemeldet.',
    ]);
    exit;
}

require_once '../../includes/insurance/ins_paperless_matcher.php';

$subscriptionId = (int) ($_GET['subscription_id'] ?? 0);

if ($subscriptionId <= 0) {
    echo json_encode([
        'success' => false,
        'enabled' => false,
        'documents' => [],
        'message' => 'Ungültige Anfrage.',
    ]);
    $db->close();
    exit;
}

$result = Ins_Paperless_Matcher::insDocumentsForSubscription($db, (int) $userId, $subscriptionId);

echo json_encode([
    'success' => $result['success'],
    'enabled' => $result['enabled'],
    'documents' => $result['documents'],
    'message' => $result['message'],
]);

$db->close();
