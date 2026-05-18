<?php

require_once '../../includes/connect_endpoint.php';
require_once '../../includes/insurance/ins_paperless_config.php';
require_once '../../includes/insurance/ins_paperless_client.php';
require_once '../../includes/insurance/ins_paperless_matcher.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(401);
    exit;
}

$subscriptionId = (int) ($_GET['subscription_id'] ?? 0);
$documentId = (int) ($_GET['document_id'] ?? 0);

if ($subscriptionId <= 0 || $documentId <= 0) {
    http_response_code(400);
    exit;
}

if (!Ins_Paperless_Matcher::insDocumentBelongsToSubscription($db, (int) $userId, $subscriptionId, $documentId)) {
    http_response_code(404);
    $db->close();
    exit;
}

$settings = Ins_Paperless_Config::insLoad($db);
if (!Ins_Paperless_Config::insIsConfigured($settings)) {
    http_response_code(503);
    $db->close();
    exit;
}

$response = Ins_Paperless_Client::insGetBinary(
    $db,
    (int) $userId,
    (string) $settings['base_url'],
    (string) $settings['api_token'],
    '/api/documents/' . $documentId . '/thumb/'
);

$db->close();

if (!$response['success'] || $response['body'] === null) {
    http_response_code($response['status'] > 0 ? $response['status'] : 502);
    exit;
}

$ttl = max(0, (int) ($settings['cache_ttl_seconds'] ?? 300));
$maxAge = $ttl > 0 ? $ttl : 300;

header('Content-Type: ' . ($response['content_type'] ?? 'image/png'));
header('Content-Length: ' . strlen($response['body']));
header('Cache-Control: private, max-age=' . $maxAge);
header('X-Content-Type-Options: nosniff');
echo $response['body'];
exit;
