<?php

require_once '../../includes/connect_endpoint.php';
require_once '../../includes/validate_endpoint.php';
require_once '../../includes/insurance/ins_repository.php';

$postData = file_get_contents('php://input');
$data = json_decode($postData, true);
$ids = $data['ids'] ?? [];
$fields = $data['fields'] ?? [];

if (!is_array($ids) || $ids === [] || !is_array($fields) || $fields === []) {
    echo json_encode(['success' => false, 'message' => 'Ungültige Anfrage.']);
    $db->close();
    exit;
}

$allowed = ['category_id', 'payment_method_id', 'insurance_type_id', 'inactive', 'auto_renew', 'payer_user_id'];
$updates = [];
foreach ($allowed as $key) {
    if (array_key_exists($key, $fields) && $fields[$key] !== '' && $fields[$key] !== null) {
        $updates[$key] = (int) $fields[$key];
    }
}
$contractStatus = isset($fields['contract_status']) ? trim((string) $fields['contract_status']) : '';

if ($updates === [] && $contractStatus === '') {
    echo json_encode(['success' => false, 'message' => 'Keine erlaubten Felder.']);
    $db->close();
    exit;
}

$updated = 0;
$skipped = 0;
$warnings = [];

$db->exec('BEGIN');
try {
    foreach ($ids as $rawId) {
        $subscriptionId = (int) $rawId;
        if ($subscriptionId <= 0) {
            $skipped++;
            continue;
        }

        $check = $db->prepare('SELECT id, is_insurance FROM subscriptions WHERE id = :id AND user_id = :userId');
        $check->bindValue(':id', $subscriptionId, SQLITE3_INTEGER);
        $check->bindValue(':userId', $userId, SQLITE3_INTEGER);
        $sub = $check->execute()->fetchArray(SQLITE3_ASSOC);
        if (!$sub) {
            $skipped++;
            continue;
        }

        if (isset($updates['category_id'])) {
            $stmt = $db->prepare('UPDATE subscriptions SET category_id = :val WHERE id = :id AND user_id = :userId');
            $stmt->bindValue(':val', $updates['category_id'], SQLITE3_INTEGER);
            $stmt->bindValue(':id', $subscriptionId, SQLITE3_INTEGER);
            $stmt->bindValue(':userId', $userId, SQLITE3_INTEGER);
            $stmt->execute();
            $updated++;
        }

        if (isset($updates['payment_method_id'])) {
            $stmt = $db->prepare('UPDATE subscriptions SET payment_method_id = :val WHERE id = :id AND user_id = :userId');
            $stmt->bindValue(':val', $updates['payment_method_id'], SQLITE3_INTEGER);
            $stmt->bindValue(':id', $subscriptionId, SQLITE3_INTEGER);
            $stmt->bindValue(':userId', $userId, SQLITE3_INTEGER);
            $stmt->execute();
            $updated++;
        }

        if (isset($updates['inactive'])) {
            $stmt = $db->prepare('UPDATE subscriptions SET inactive = :val WHERE id = :id AND user_id = :userId');
            $stmt->bindValue(':val', $updates['inactive'], SQLITE3_INTEGER);
            $stmt->bindValue(':id', $subscriptionId, SQLITE3_INTEGER);
            $stmt->bindValue(':userId', $userId, SQLITE3_INTEGER);
            $stmt->execute();
            $updated++;
        }

        if (isset($updates['auto_renew'])) {
            $stmt = $db->prepare('UPDATE subscriptions SET auto_renew = :val WHERE id = :id AND user_id = :userId');
            $stmt->bindValue(':val', $updates['auto_renew'], SQLITE3_INTEGER);
            $stmt->bindValue(':id', $subscriptionId, SQLITE3_INTEGER);
            $stmt->bindValue(':userId', $userId, SQLITE3_INTEGER);
            $stmt->execute();
            $updated++;
        }

        if (isset($updates['payer_user_id'])) {
            $stmt = $db->prepare('UPDATE subscriptions SET payer_user_id = :val WHERE id = :id AND user_id = :userId');
            $stmt->bindValue(':val', $updates['payer_user_id'], SQLITE3_INTEGER);
            $stmt->bindValue(':id', $subscriptionId, SQLITE3_INTEGER);
            $stmt->bindValue(':userId', $userId, SQLITE3_INTEGER);
            $stmt->execute();
            $updated++;
        }

        if ($contractStatus !== '') {
            if (!Ins_Repository::insSchemaReady($db)) {
                $warnings[] = 'Versicherungs-Schema nicht bereit.';
                $skipped++;
                continue;
            }
            if (empty($sub['is_insurance'])) {
                $warnings[] = 'Vertrag ' . $subscriptionId . ': kein Versicherungsvertrag — Vertragsstatus übersprungen.';
                $skipped++;
                continue;
            }
            $exists = $db->prepare('SELECT subscription_id FROM assure_insurance_details WHERE subscription_id = :id');
            $exists->bindValue(':id', $subscriptionId, SQLITE3_INTEGER);
            $row = $exists->execute()->fetchArray(SQLITE3_ASSOC);
            if ($row) {
                $stmt = $db->prepare(
                    'UPDATE assure_insurance_details SET contract_status = :status WHERE subscription_id = :id'
                );
            } else {
                $stmt = $db->prepare(
                    'INSERT INTO assure_insurance_details (subscription_id, contract_status) VALUES (:id, :status)'
                );
            }
            $stmt->bindValue(':status', $contractStatus, SQLITE3_TEXT);
            $stmt->bindValue(':id', $subscriptionId, SQLITE3_INTEGER);
            $stmt->execute();
            $updated++;
        }

        if (isset($updates['insurance_type_id'])) {
            if (!Ins_Repository::insSchemaReady($db)) {
                $warnings[] = 'Versicherungs-Schema nicht bereit.';
                $skipped++;
                continue;
            }
            if (empty($sub['is_insurance'])) {
                $warnings[] = 'Vertrag ' . $subscriptionId . ': kein Versicherungsvertrag — Versicherungsart übersprungen.';
                $skipped++;
                continue;
            }

            $exists = $db->prepare('SELECT subscription_id FROM assure_insurance_details WHERE subscription_id = :id');
            $exists->bindValue(':id', $subscriptionId, SQLITE3_INTEGER);
            $row = $exists->execute()->fetchArray(SQLITE3_ASSOC);

            if ($row) {
                $stmt = $db->prepare(
                    'UPDATE assure_insurance_details SET insurance_type_id = :typeId WHERE subscription_id = :id'
                );
            } else {
                $stmt = $db->prepare(
                    'INSERT INTO assure_insurance_details (subscription_id, insurance_type_id) VALUES (:id, :typeId)'
                );
            }
            $stmt->bindValue(':typeId', $updates['insurance_type_id'], SQLITE3_INTEGER);
            $stmt->bindValue(':id', $subscriptionId, SQLITE3_INTEGER);
            $stmt->execute();

            $flag = $db->prepare('UPDATE subscriptions SET is_insurance = 1 WHERE id = :id AND user_id = :userId');
            $flag->bindValue(':id', $subscriptionId, SQLITE3_INTEGER);
            $flag->bindValue(':userId', $userId, SQLITE3_INTEGER);
            $flag->execute();
            $updated++;
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
    'success' => true,
    'updated' => $updated,
    'skipped' => $skipped,
    'warnings' => array_values(array_unique($warnings)),
]);
$db->close();
