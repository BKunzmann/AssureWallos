<?php

require_once __DIR__ . '/ins_paperless_config.php';
require_once __DIR__ . '/ins_paperless_client.php';

/**
 * Resolves Paperless-ngx documents for an insurance subscription (read-only).
 */
class Ins_Paperless_Matcher
{
    /**
     * @return array{success: bool, enabled: bool, documents: array<int, array<string, mixed>>, message: ?string}
     */
    public static function insDocumentsForSubscription(SQLite3 $db, int $userId, int $subscriptionId): array
    {
        if (!Ins_Paperless_Config::insPaperlessReady($db)) {
            return self::insResult(false, false, [], 'Paperless-Integration ist noch nicht migriert.');
        }

        $settings = Ins_Paperless_Config::insLoad($db);
        if (!Ins_Paperless_Config::insIsConfigured($settings)) {
            return self::insResult(true, false, [], null);
        }

        $ownerStmt = $db->prepare('SELECT COUNT(*) AS cnt FROM subscriptions WHERE id = :id AND user_id = :userId');
        $ownerStmt->bindValue(':id', $subscriptionId, SQLITE3_INTEGER);
        $ownerStmt->bindValue(':userId', $userId, SQLITE3_INTEGER);
        $ownerRow = $ownerStmt->execute()->fetchArray(SQLITE3_ASSOC);
        if (!$ownerRow || (int) $ownerRow['cnt'] < 1) {
            return self::insResult(false, true, [], 'Ungültiges Abonnement.');
        }

        $flagStmt = $db->prepare('SELECT is_insurance FROM subscriptions WHERE id = :id AND user_id = :userId');
        $flagStmt->bindValue(':id', $subscriptionId, SQLITE3_INTEGER);
        $flagStmt->bindValue(':userId', $userId, SQLITE3_INTEGER);
        $flagRow = $flagStmt->execute()->fetchArray(SQLITE3_ASSOC);
        if (!$flagRow || (int) $flagRow['is_insurance'] !== 1) {
            return self::insResult(true, true, [], null);
        }

        $cached = self::insReadCache($db, $userId, $subscriptionId, $settings);
        if ($cached !== null) {
            return self::insResult(true, true, $cached, null);
        }

        $details = self::insLoadDetails($db, $subscriptionId);
        $documents = self::insFetchMatchedDocuments($db, $userId, $settings, $subscriptionId, $details);

        if (
            empty($documents)
            && !empty($settings['use_policy_number_fallback'])
            && ($settings['match_mode'] ?? '') !== 'search'
        ) {
            $fallback = self::insFetchByPolicyNumber($db, $userId, $settings, $details);
            $documents = self::insMergeDocuments($documents, $fallback);
        }

        $normalized = [];
        foreach ($documents as $doc) {
            $normalized[] = Ins_Paperless_Client::insNormalizeDocument($doc, (string) $settings['base_url']);
        }

        usort($normalized, static function (array $a, array $b): int {
            return strcmp((string) ($b['created'] ?? ''), (string) ($a['created'] ?? ''));
        });

        self::insWriteCache($db, $userId, $subscriptionId, $normalized, $settings);

        return self::insResult(true, true, $normalized, null);
    }

    public static function insInvalidateCache(SQLite3 $db, int $userId, int $subscriptionId): void
    {
        if (!self::insCacheTableReady($db)) {
            return;
        }

        $stmt = $db->prepare('DELETE FROM assure_paperless_cache WHERE subscription_id = :sid AND user_id = :uid');
        $stmt->bindValue(':sid', $subscriptionId, SQLITE3_INTEGER);
        $stmt->bindValue(':uid', $userId, SQLITE3_INTEGER);
        $stmt->execute();
    }

    /**
     * Ensures a Paperless document is linked to the subscription (via matcher/cache).
     */
    public static function insDocumentBelongsToSubscription(
        SQLite3 $db,
        int $userId,
        int $subscriptionId,
        int $documentId
    ): bool {
        if ($documentId <= 0 || $subscriptionId <= 0) {
            return false;
        }

        $result = self::insDocumentsForSubscription($db, $userId, $subscriptionId);
        if (!$result['success'] || !$result['enabled']) {
            return false;
        }

        foreach ($result['documents'] as $doc) {
            if ((int) ($doc['id'] ?? 0) === $documentId) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<string, mixed> $settings
     * @param array<string, mixed>|null $details
     * @return array<int, array<string, mixed>>
     */
    private static function insFetchMatchedDocuments(
        SQLite3 $db,
        int $userId,
        array $settings,
        int $subscriptionId,
        ?array $details
    ): array {
        $matchMode = (string) ($settings['match_mode'] ?? 'custom_field');

        if ($matchMode === 'tag') {
            return self::insFetchByTag($db, $userId, $settings, $subscriptionId);
        }

        if ($matchMode === 'search') {
            return self::insFetchByPolicyNumber($db, $userId, $settings, $details);
        }

        return self::insFetchByCustomField($db, $userId, $settings, $subscriptionId);
    }

    /**
     * @param array<string, mixed> $settings
     * @return array<int, array<string, mixed>>
     */
    private static function insFetchByCustomField(SQLite3 $db, int $userId, array $settings, int $subscriptionId): array
    {
        $fieldName = trim((string) ($settings['custom_field_name'] ?? 'assure_subscription_id'));
        if ($fieldName === '') {
            return [];
        }

        $query = json_encode([$fieldName, 'exact', $subscriptionId], JSON_UNESCAPED_UNICODE);
        $response = Ins_Paperless_Client::insListDocuments(
            $db,
            $userId,
            (string) $settings['base_url'],
            (string) $settings['api_token'],
            ['custom_field_query' => $query]
        );

        return $response['success'] ? $response['documents'] : [];
    }

    /**
     * @param array<string, mixed> $settings
     * @return array<int, array<string, mixed>>
     */
    private static function insFetchByTag(SQLite3 $db, int $userId, array $settings, int $subscriptionId): array
    {
        $prefix = (string) ($settings['tag_prefix'] ?? 'aw-sub-');
        $tagName = $prefix . $subscriptionId;

        $response = Ins_Paperless_Client::insListDocuments(
            $db,
            $userId,
            (string) $settings['base_url'],
            (string) $settings['api_token'],
            ['tags__name__iexact' => $tagName]
        );

        return $response['success'] ? $response['documents'] : [];
    }

    /**
     * @param array<string, mixed> $settings
     * @param array<string, mixed>|null $details
     * @return array<int, array<string, mixed>>
     */
    private static function insFetchByPolicyNumber(SQLite3 $db, int $userId, array $settings, ?array $details): array
    {
        $policyNumber = trim((string) ($details['policy_number'] ?? ''));
        if ($policyNumber === '') {
            return [];
        }

        $response = Ins_Paperless_Client::insListDocuments(
            $db,
            $userId,
            (string) $settings['base_url'],
            (string) $settings['api_token'],
            ['title_content' => $policyNumber]
        );

        return $response['success'] ? $response['documents'] : [];
    }

    /**
     * @param array<int, array<string, mixed>> $primary
     * @param array<int, array<string, mixed>> $extra
     * @return array<int, array<string, mixed>>
     */
    private static function insMergeDocuments(array $primary, array $extra): array
    {
        $byId = [];
        foreach (array_merge($primary, $extra) as $doc) {
            $id = (int) ($doc['id'] ?? 0);
            if ($id > 0) {
                $byId[$id] = $doc;
            }
        }

        return array_values($byId);
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function insLoadDetails(SQLite3 $db, int $subscriptionId): ?array
    {
        $stmt = $db->prepare('SELECT policy_number FROM assure_insurance_details WHERE subscription_id = :id');
        $stmt->bindValue(':id', $subscriptionId, SQLITE3_INTEGER);
        $row = $stmt->execute()->fetchArray(SQLITE3_ASSOC);

        return $row ?: null;
    }

    /**
     * @param array<string, mixed> $settings
     * @return array<int, array<string, mixed>>|null
     */
    private static function insReadCache(SQLite3 $db, int $userId, int $subscriptionId, array $settings): ?array
    {
        $ttl = (int) ($settings['cache_ttl_seconds'] ?? 0);
        if ($ttl <= 0 || !self::insCacheTableReady($db)) {
            return null;
        }

        $stmt = $db->prepare("
            SELECT payload_json, fetched_at
            FROM assure_paperless_cache
            WHERE subscription_id = :sid AND user_id = :uid
        ");
        $stmt->bindValue(':sid', $subscriptionId, SQLITE3_INTEGER);
        $stmt->bindValue(':uid', $userId, SQLITE3_INTEGER);
        $row = $stmt->execute()->fetchArray(SQLITE3_ASSOC);

        if (!$row) {
            return null;
        }

        $fetchedAt = strtotime((string) $row['fetched_at']);
        if ($fetchedAt === false || (time() - $fetchedAt) > $ttl) {
            return null;
        }

        $decoded = json_decode((string) $row['payload_json'], true);
        return is_array($decoded) ? $decoded : null;
    }

    /**
     * @param array<int, array<string, mixed>> $documents
     * @param array<string, mixed> $settings
     */
    private static function insWriteCache(SQLite3 $db, int $userId, int $subscriptionId, array $documents, array $settings): void
    {
        $ttl = (int) ($settings['cache_ttl_seconds'] ?? 0);
        if ($ttl <= 0 || !self::insCacheTableReady($db)) {
            return;
        }

        $stmt = $db->prepare("
            INSERT INTO assure_paperless_cache (subscription_id, user_id, payload_json, fetched_at)
            VALUES (:sid, :uid, :payload, CURRENT_TIMESTAMP)
            ON CONFLICT(subscription_id, user_id) DO UPDATE SET
                payload_json = excluded.payload_json,
                fetched_at = CURRENT_TIMESTAMP
        ");
        $stmt->bindValue(':sid', $subscriptionId, SQLITE3_INTEGER);
        $stmt->bindValue(':uid', $userId, SQLITE3_INTEGER);
        $stmt->bindValue(':payload', json_encode($documents, JSON_UNESCAPED_UNICODE), SQLITE3_TEXT);
        $stmt->execute();
    }

    private static function insCacheTableReady(SQLite3 $db): bool
    {
        $stmt = $db->prepare("SELECT name FROM sqlite_master WHERE type = 'table' AND name = 'assure_paperless_cache'");
        return (bool) $stmt->execute()->fetchArray(SQLITE3_ASSOC);
    }

    /**
     * @param array<int, array<string, mixed>> $documents
     * @return array{success: bool, enabled: bool, documents: array<int, array<string, mixed>>, message: ?string}
     */
    private static function insResult(bool $success, bool $enabled, array $documents, ?string $message): array
    {
        return [
            'success' => $success,
            'enabled' => $enabled,
            'documents' => $documents,
            'message' => $message,
        ];
    }
}
