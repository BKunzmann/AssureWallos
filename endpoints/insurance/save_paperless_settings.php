<?php

require_once '../../includes/connect_endpoint.php';
require_once '../../includes/validate_endpoint_admin.php';
require_once '../../includes/insurance/ins_endpoint_json.php';
ins_send_json_headers();
require_once '../../includes/insurance/ins_paperless_config.php';

if (!Ins_Paperless_Config::insPaperlessReady($db)) {
    echo json_encode([
        'success' => false,
        'settings' => [],
        'message' => 'Datenbank-Migration fehlt. Bitte /endpoints/db/migrate.php im Browser aufrufen.',
    ]);
    $db->close();
    exit;
}

$data = $_POST;
if (empty($data['base_url']) && empty($data['enabled'])) {
    $json = json_decode((string) file_get_contents('php://input'), true);
    if (is_array($json)) {
        $data = array_merge($data, $json);
    }
}

$success = Ins_Paperless_Config::insSave($db, $data);
$settings = Ins_Paperless_Config::insLoad($db);

$message = 'Paperless-Einstellungen gespeichert.';
if ($success && Ins_Paperless_Config::insPublicSettings($settings)['has_token']) {
    $message .= ' API-Token ist hinterlegt (wird aus Sicherheitsgründen nicht erneut angezeigt).';
}

echo json_encode([
    'success' => $success,
    'settings' => Ins_Paperless_Config::insPublicSettings($settings),
    'message' => $success ? $message : 'Einstellungen konnten nicht gespeichert werden.',
]);

$db->close();
