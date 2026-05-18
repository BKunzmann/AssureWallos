<?php

require_once '../../includes/connect_endpoint.php';
require_once '../../includes/validate_endpoint.php';
require_once '../../includes/insurance/ins_endpoint_json.php';
require_once '../../includes/insurance/ins_csv_import.php';

ins_send_json_headers();

$demoMode = getenv('DEMO_MODE');
if (!empty($demoMode)) {
    echo json_encode([
        'success' => false,
        'message' => 'Import ist im Demo-Modus nicht verfügbar.',
    ]);
    $db->close();
    exit;
}

$token = trim((string) ($_POST['batch_token'] ?? ''));
if ($token === '') {
    echo json_encode([
        'success' => false,
        'message' => 'Ungültige Import-Sitzung.',
    ]);
    $db->close();
    exit;
}

$includeDuplicates = !empty($_POST['include_duplicates']);

$result = Ins_Csv_Import::insCommitBatch($db, (int) $userId, $token, [
    'include_duplicates' => $includeDuplicates,
]);

echo json_encode($result);

$db->close();
