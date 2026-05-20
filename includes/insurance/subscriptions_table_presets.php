<?php

require_once __DIR__ . '/subscriptions_table_dimensions.php';
require_once __DIR__ . '/subscriptions_table_columns.php';

/**
 * Persists subscriptions table view presets per user (AssureWallos).
 */
class Ins_Subscriptions_Table_Presets
{
    public static function insTableReady(SQLite3 $db): bool
    {
        $result = $db->query(
            "SELECT name FROM sqlite_master WHERE type = 'table' AND name = 'assure_user_table_presets'"
        );
        $row = $result ? $result->fetchArray(SQLITE3_ASSOC) : false;

        return $row !== false;
    }

    /**
     * @return array{presets: list<array<string, mixed>>, defaultId: ?string}
     */
    public static function insListForUser(SQLite3 $db, int $userId): array
    {
        if (!self::insTableReady($db)) {
            return ['presets' => [], 'defaultId' => null];
        }

        $stmt = $db->prepare(
            'SELECT id, name, is_default, payload_json, created_at, updated_at
             FROM assure_user_table_presets
             WHERE user_id = :userId
             ORDER BY name COLLATE NOCASE ASC'
        );
        $stmt->bindValue(':userId', $userId, SQLITE3_INTEGER);
        $result = $stmt->execute();

        $presets = [];
        $defaultId = null;
        while ($result && ($row = $result->fetchArray(SQLITE3_ASSOC))) {
            $preset = self::insRowToPreset($row);
            if ($preset === null) {
                continue;
            }
            $presets[] = $preset;
            if (!empty($row['is_default'])) {
                $defaultId = (string) $preset['id'];
            }
        }

        return ['presets' => $presets, 'defaultId' => $defaultId];
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>|null
     */
    public static function insCreate(SQLite3 $db, int $userId, string $name, array $payload, bool $setDefault = false): ?array
    {
        if (!self::insTableReady($db)) {
            return null;
        }

        $name = trim($name);
        if ($name === '') {
            return null;
        }

        $normalized = self::insNormalizePayload($payload);
        $json = json_encode($normalized, JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            return null;
        }

        $db->exec('BEGIN');
        try {
            if ($setDefault) {
                self::insClearDefault($db, $userId);
            }

            $stmt = $db->prepare(
                'INSERT INTO assure_user_table_presets (user_id, name, is_default, payload_json)
                 VALUES (:userId, :name, :isDefault, :payload)'
            );
            $stmt->bindValue(':userId', $userId, SQLITE3_INTEGER);
            $stmt->bindValue(':name', $name, SQLITE3_TEXT);
            $stmt->bindValue(':isDefault', $setDefault ? 1 : 0, SQLITE3_INTEGER);
            $stmt->bindValue(':payload', $json, SQLITE3_TEXT);
            $stmt->execute();

            $id = (int) $db->lastInsertRowID();
            $db->exec('COMMIT');

            return self::insGetById($db, $userId, $id);
        } catch (Throwable $e) {
            $db->exec('ROLLBACK');
            throw $e;
        }
    }

    public static function insDelete(SQLite3 $db, int $userId, int $presetId): bool
    {
        if (!self::insTableReady($db)) {
            return false;
        }

        $stmt = $db->prepare(
            'DELETE FROM assure_user_table_presets WHERE id = :id AND user_id = :userId'
        );
        $stmt->bindValue(':id', $presetId, SQLITE3_INTEGER);
        $stmt->bindValue(':userId', $userId, SQLITE3_INTEGER);
        $stmt->execute();

        return $db->changes() > 0;
    }

    public static function insSetDefault(SQLite3 $db, int $userId, int $presetId): bool
    {
        if (!self::insTableReady($db)) {
            return false;
        }

        $check = $db->prepare(
            'SELECT id FROM assure_user_table_presets WHERE id = :id AND user_id = :userId'
        );
        $check->bindValue(':id', $presetId, SQLITE3_INTEGER);
        $check->bindValue(':userId', $userId, SQLITE3_INTEGER);
        if (!$check->execute()->fetchArray(SQLITE3_ASSOC)) {
            return false;
        }

        $db->exec('BEGIN');
        try {
            self::insClearDefault($db, $userId);
            $stmt = $db->prepare(
                'UPDATE assure_user_table_presets SET is_default = 1, updated_at = CURRENT_TIMESTAMP
                 WHERE id = :id AND user_id = :userId'
            );
            $stmt->bindValue(':id', $presetId, SQLITE3_INTEGER);
            $stmt->bindValue(':userId', $userId, SQLITE3_INTEGER);
            $stmt->execute();
            $db->exec('COMMIT');

            return true;
        } catch (Throwable $e) {
            $db->exec('ROLLBACK');
            throw $e;
        }
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public static function insNormalizePayload(array $payload): array
    {
        $sort = Ins_Subscriptions_Table_Dimensions::insValidateSortKey((string) ($payload['sort'] ?? 'next_payment'))
            ?? 'next_payment';
        $sortDir = strtoupper((string) ($payload['sortDir'] ?? 'ASC')) === 'DESC' ? 'DESC' : 'ASC';
        $group = Ins_Subscriptions_Table_Dimensions::insValidateGroupKey((string) ($payload['group'] ?? 'none')) ?? 'none';
        $group2Raw = (string) ($payload['group2'] ?? '');
        $group2 = $group2Raw !== ''
            ? (Ins_Subscriptions_Table_Dimensions::insValidateGroupKey($group2Raw) ?? '')
            : '';

        $columns = [];
        if (isset($payload['columns']) && is_array($payload['columns'])) {
            $columns = Ins_Subscriptions_Table_Columns::insNormalizeVisible($payload['columns']);
        }

        return [
            'sort' => $sort,
            'sortDir' => $sortDir,
            'group' => $group,
            'group2' => $group2,
            'columns' => $columns,
        ];
    }

    /**
     * @param array<string, mixed> $row
     */
    private static function insRowToPreset(array $row): ?array
    {
        $decoded = json_decode((string) ($row['payload_json'] ?? ''), true);
        if (!is_array($decoded)) {
            return null;
        }

        $normalized = self::insNormalizePayload($decoded);

        return [
            'id' => (string) (int) $row['id'],
            'name' => (string) ($row['name'] ?? ''),
            'sort' => $normalized['sort'],
            'sortDir' => $normalized['sortDir'],
            'group' => $normalized['group'],
            'group2' => $normalized['group2'],
            'columns' => $normalized['columns'],
            'savedAt' => (string) ($row['updated_at'] ?? ''),
            'isDefault' => !empty($row['is_default']),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function insGetById(SQLite3 $db, int $userId, int $presetId): ?array
    {
        $stmt = $db->prepare(
            'SELECT id, name, is_default, payload_json, created_at, updated_at
             FROM assure_user_table_presets WHERE id = :id AND user_id = :userId'
        );
        $stmt->bindValue(':id', $presetId, SQLITE3_INTEGER);
        $stmt->bindValue(':userId', $userId, SQLITE3_INTEGER);
        $row = $stmt->execute()->fetchArray(SQLITE3_ASSOC);

        return $row ? self::insRowToPreset($row) : null;
    }

    private static function insClearDefault(SQLite3 $db, int $userId): void
    {
        $stmt = $db->prepare(
            'UPDATE assure_user_table_presets SET is_default = 0 WHERE user_id = :userId'
        );
        $stmt->bindValue(':userId', $userId, SQLITE3_INTEGER);
        $stmt->execute();
    }
}
