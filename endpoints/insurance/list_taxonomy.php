<?php

require_once '../../includes/connect_endpoint.php';
require_once '../../includes/validate_endpoint.php';
require_once '../../includes/insurance/ins_endpoint_json.php';
ins_send_json_headers();
require_once '../../includes/insurance/ins_repository.php';

$taxonomy = Ins_Repository::insLoadTaxonomy($db, $userId);

echo json_encode([
    'success' => true,
    'taxonomy' => $taxonomy,
]);

$db->close();
