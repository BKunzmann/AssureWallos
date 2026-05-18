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

if (empty($_FILES['csv_file']) || !is_uploaded_file($_FILES['csv_file']['tmp_name'] ?? '')) {
    echo json_encode([
        'success' => false,
        'message' => 'Bitte eine CSV-Datei auswählen.',
    ]);
    $db->close();
    exit;
}

$file = $_FILES['csv_file'];
if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
    echo json_encode([
        'success' => false,
        'message' => 'Datei-Upload fehlgeschlagen.',
    ]);
    $db->close();
    exit;
}

if ((int) ($file['size'] ?? 0) > Ins_Csv_Import::MAX_FILE_BYTES) {
    echo json_encode([
        'success' => false,
        'message' => 'Datei ist zu groß (max. 2 MB).',
    ]);
    $db->close();
    exit;
}

$raw = (string) file_get_contents($file['tmp_name']);
$parsed = Ins_Csv_Import::insParseCsv($raw);

if (!$parsed['success']) {
    echo json_encode([
        'success' => false,
        'message' => $parsed['message'],
    ]);
    $db->close();
    exit;
}

$uiDefaults = [
    'default_category_id' => (int) ($_POST['default_category_id'] ?? 0),
    'default_currency_id' => (int) ($_POST['default_currency_id'] ?? 0),
];

$preview = Ins_Csv_Import::insPreview($db, (int) $userId, $parsed['rows'], $uiDefaults);

echo json_encode($preview);

$db->close();
