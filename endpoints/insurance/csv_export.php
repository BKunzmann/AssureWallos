<?php

require_once '../../includes/connect_endpoint.php';
require_once '../../includes/insurance/ins_repository.php';
require_once '../../includes/insurance/ins_csv_import.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(401);
    exit;
}

if (!Ins_Repository::insSchemaReady($db)) {
    http_response_code(503);
    exit;
}

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="assurewallos-export.csv"');
header('X-Content-Type-Options: nosniff');

echo Ins_Csv_Import::insExportUserCsv($db, (int) $userId);

$db->close();
exit;
