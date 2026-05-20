<?php

require_once '../../includes/connect_endpoint.php';
require_once '../../includes/validate_endpoint.php';
require_once '../../includes/insurance/subscriptions_table_presets.php';

header('Content-Type: application/json; charset=UTF-8');

$userId = (int) $userId;

if (!Ins_Subscriptions_Table_Presets::insTableReady($db)) {
    echo json_encode([
        'success' => false,
        'message' => 'Preset-Tabelle nicht verfügbar. Bitte Migration ausführen.',
    ]);
    $db->close();
    exit;
}

$postData = file_get_contents('php://input');
$data = json_decode($postData, true);
if (!is_array($data)) {
    echo json_encode(['success' => false, 'message' => 'Ungültige Anfrage.']);
    $db->close();
    exit;
}

$action = (string) ($data['action'] ?? '');

try {
    if ($action === 'save') {
        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '') {
            throw new InvalidArgumentException('Name fehlt.');
        }
        $preset = Ins_Subscriptions_Table_Presets::insCreate(
            $db,
            $userId,
            $name,
            $data,
            !empty($data['setDefault'])
        );
        if ($preset === null) {
            throw new RuntimeException('Speichern fehlgeschlagen.');
        }
        $list = Ins_Subscriptions_Table_Presets::insListForUser($db, $userId);
        echo json_encode([
            'success' => true,
            'preset' => $preset,
            'presets' => $list['presets'],
            'defaultId' => $list['defaultId'],
        ], JSON_UNESCAPED_UNICODE);
    } elseif ($action === 'delete') {
        $id = (int) ($data['id'] ?? 0);
        if ($id <= 0 || !Ins_Subscriptions_Table_Presets::insDelete($db, $userId, $id)) {
            throw new RuntimeException('Löschen fehlgeschlagen.');
        }
        $list = Ins_Subscriptions_Table_Presets::insListForUser($db, $userId);
        echo json_encode([
            'success' => true,
            'presets' => $list['presets'],
            'defaultId' => $list['defaultId'],
        ], JSON_UNESCAPED_UNICODE);
    } elseif ($action === 'set_default') {
        $id = (int) ($data['id'] ?? 0);
        if ($id <= 0 || !Ins_Subscriptions_Table_Presets::insSetDefault($db, $userId, $id)) {
            throw new RuntimeException('Standard konnte nicht gesetzt werden.');
        }
        $list = Ins_Subscriptions_Table_Presets::insListForUser($db, $userId);
        echo json_encode([
            'success' => true,
            'defaultId' => (string) $id,
            'presets' => $list['presets'],
        ], JSON_UNESCAPED_UNICODE);
    } elseif ($action === 'import_local') {
        $presets = $data['presets'] ?? [];
        if (!is_array($presets)) {
            throw new InvalidArgumentException('Ungültige Preset-Liste.');
        }
        $defaultLocalId = (string) ($data['defaultId'] ?? '');
        $imported = 0;
        $newDefaultId = null;
        foreach ($presets as $preset) {
            if (!is_array($preset)) {
                continue;
            }
            $name = trim((string) ($preset['name'] ?? ''));
            if ($name === '') {
                continue;
            }
            try {
                $created = Ins_Subscriptions_Table_Presets::insCreate($db, $userId, $name, $preset, false);
                if ($created !== null) {
                    $imported++;
                    if ($defaultLocalId !== '' && (string) ($preset['id'] ?? '') === $defaultLocalId) {
                        $newDefaultId = (int) $created['id'];
                    }
                }
            } catch (Throwable $e) {
                if (!str_contains($e->getMessage(), 'UNIQUE')) {
                    throw $e;
                }
            }
        }
        if ($newDefaultId !== null) {
            Ins_Subscriptions_Table_Presets::insSetDefault($db, $userId, $newDefaultId);
        }
        $list = Ins_Subscriptions_Table_Presets::insListForUser($db, $userId);
        echo json_encode([
            'success' => true,
            'imported' => $imported,
            'presets' => $list['presets'],
            'defaultId' => $list['defaultId'],
        ], JSON_UNESCAPED_UNICODE);
    } else {
        throw new InvalidArgumentException('Unbekannte Aktion.');
    }
} catch (InvalidArgumentException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} catch (Throwable $e) {
    $msg = $e->getMessage();
    if (str_contains($msg, 'UNIQUE')) {
        $msg = 'Eine Ansicht mit diesem Namen existiert bereits.';
    }
    echo json_encode(['success' => false, 'message' => $msg]);
}

$db->close();
