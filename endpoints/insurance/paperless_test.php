<?php

require_once '../../includes/connect_endpoint.php';
require_once '../../includes/validate_endpoint_admin.php';
require_once '../../includes/insurance/ins_endpoint_json.php';
ins_send_json_headers();
require_once '../../includes/insurance/ins_paperless_config.php';
require_once '../../includes/insurance/ins_paperless_client.php';

$data = $_POST;
if (empty($data['base_url'])) {
    $json = json_decode((string) file_get_contents('php://input'), true);
    if (is_array($json)) {
        $data = array_merge($data, $json);
    }
}

$settings = Ins_Paperless_Config::insLoad($db);
$baseUrl = trim((string) ($data['base_url'] ?? $settings['base_url']));
$apiToken = trim((string) ($data['api_token'] ?? ''));

if ($apiToken === '' || $apiToken === '********') {
    $apiToken = (string) $settings['api_token'];
}

if ($baseUrl === '' || $apiToken === '') {
    echo json_encode([
        'success' => false,
        'message' => 'Bitte Basis-URL und API-Token angeben (Token zuerst eintragen oder speichern).',
    ]);
    $db->close();
    exit;
}

$result = Ins_Paperless_Client::insTestConnection($db, (int) $userId, $baseUrl, $apiToken);

echo json_encode([
    'success' => $result['success'],
    'message' => $result['message'],
]);

$db->close();
