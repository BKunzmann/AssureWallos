<?php

require_once '../../includes/connect_endpoint.php';
require_once '../../includes/validate_endpoint.php';

$postData = file_get_contents('php://input');
$data = json_decode($postData, true);
$ids = $data['ids'] ?? [];

if (!is_array($ids) || $ids === []) {
    echo json_encode(['success' => false, 'message' => 'Keine Verträge ausgewählt.']);
    $db->close();
    exit;
}

$deleted = 0;
$failed = 0;
$errors = [];

$db->exec('BEGIN');
try {
    foreach ($ids as $rawId) {
        $subscriptionId = (int) $rawId;
        if ($subscriptionId <= 0) {
            $failed++;
            $errors[] = 'Ungültige ID.';
            continue;
        }

        $deleteStmt = $db->prepare('DELETE FROM subscriptions WHERE id = :subscriptionId AND user_id = :userId');
        $deleteStmt->bindValue(':subscriptionId', $subscriptionId, SQLITE3_INTEGER);
        $deleteStmt->bindValue(':userId', $userId, SQLITE3_INTEGER);

        if ($deleteStmt->execute()) {
            $clearStmt = $db->prepare(
                'UPDATE subscriptions SET replacement_subscription_id = NULL
                 WHERE replacement_subscription_id = :subscriptionId AND user_id = :userId'
            );
            $clearStmt->bindValue(':subscriptionId', $subscriptionId, SQLITE3_INTEGER);
            $clearStmt->bindValue(':userId', $userId, SQLITE3_INTEGER);
            $clearStmt->execute();
            $deleted++;
        } else {
            $failed++;
            $errors[] = 'Vertrag ' . $subscriptionId . ' konnte nicht gelöscht werden.';
        }
    }
    $db->exec('COMMIT');
} catch (Throwable $e) {
    $db->exec('ROLLBACK');
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    $db->close();
    exit;
}

echo json_encode([
    'success' => $failed === 0,
    'deleted' => $deleted,
    'failed' => $failed,
    'errors' => $errors,
]);
$db->close();
