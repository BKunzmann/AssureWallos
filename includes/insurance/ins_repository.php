<?php

/**
 * AssureWallos repository for insurance-specific subscription data.
 *
 * Wallos core keeps owning subscriptions. This repository stores only the
 * insurance extension data in AssureWallos tables.
 */
class Ins_Repository
{
    private const DOCUMENT_DIR = 'images/uploads/insurance_docs';
    private const MAX_DOCUMENT_SIZE = 10485760; // 10 MiB
    private const ALLOWED_DOCUMENT_TYPES = [
        'pdf' => ['application/pdf', 'application/x-pdf', 'application/acrobat', 'application/octet-stream'],
        'png' => ['image/png'],
        'jpg' => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'webp' => ['image/webp'],
    ];

    public static function insSchemaReady(SQLite3 $db): bool
    {
        return self::insColumnExists($db, 'subscriptions', 'is_insurance')
            && self::insTableExists($db, 'assure_insurance_details')
            && self::insTableExists($db, 'assure_documents');
    }

    public static function insSaveFromPost(SQLite3 $db, int $subscriptionId, int $userId, array $post, array $files): void
    {
        if (!self::insSchemaReady($db)) {
            return;
        }

        $isInsurance = isset($post['is_insurance']) ? 1 : 0;
        self::insUpdateInsuranceFlag($db, $subscriptionId, $userId, $isInsurance);

        if ($isInsurance === 1) {
            self::insUpsertInsuranceDetails($db, $subscriptionId, $post);
            self::insStoreUploadedDocuments($db, $subscriptionId, $userId, $post, $files);
        }
    }

    public static function insLoadForSubscription(SQLite3 $db, int $subscriptionId, int $userId): array
    {
        if (!self::insSchemaReady($db)) {
            return [
                'is_insurance' => 0,
                'insurance_details' => null,
                'insurance_documents' => [],
            ];
        }

        return [
            'is_insurance' => self::insLoadInsuranceFlag($db, $subscriptionId, $userId),
            'insurance_details' => self::insLoadInsuranceDetails($db, $subscriptionId),
            'insurance_documents' => self::insListDocuments($db, $subscriptionId, $userId),
        ];
    }

    public static function insStoreSingleUploadedDocument(
        SQLite3 $db,
        int $subscriptionId,
        int $userId,
        string $docType,
        array $uploadedFile
    ): ?int {
        if (!self::insSchemaReady($db) || !self::insSubscriptionBelongsToUser($db, $subscriptionId, $userId)) {
            return null;
        }

        return self::insPersistUploadedDocument($db, $subscriptionId, $userId, $docType, $uploadedFile);
    }

    public static function insGetDocument(SQLite3 $db, int $documentId, int $userId): ?array
    {
        if (!self::insTableExists($db, 'assure_documents')) {
            return null;
        }

        $stmt = $db->prepare("
            SELECT id, subscription_id, user_id, doc_type, filename, original_name, mime_type, size_bytes
            FROM assure_documents
            WHERE id = :id AND user_id = :userId
        ");
        $stmt->bindValue(':id', $documentId, SQLITE3_INTEGER);
        $stmt->bindValue(':userId', $userId, SQLITE3_INTEGER);

        $result = $stmt->execute();
        $document = $result ? $result->fetchArray(SQLITE3_ASSOC) : false;

        if (!$document) {
            return null;
        }

        $document['path'] = self::insDocumentUploadDir() . '/' . $document['filename'];
        return $document;
    }

    public static function insDeleteDocument(SQLite3 $db, int $documentId, int $userId): bool
    {
        $document = self::insGetDocument($db, $documentId, $userId);
        if ($document === null) {
            return false;
        }

        $stmt = $db->prepare("DELETE FROM assure_documents WHERE id = :id AND user_id = :userId");
        $stmt->bindValue(':id', $documentId, SQLITE3_INTEGER);
        $stmt->bindValue(':userId', $userId, SQLITE3_INTEGER);
        $success = (bool) $stmt->execute();

        if ($success && is_file($document['path'])) {
            unlink($document['path']);
        }

        return $success;
    }

    private static function insUpdateInsuranceFlag(SQLite3 $db, int $subscriptionId, int $userId, int $isInsurance): void
    {
        $stmt = $db->prepare("
            UPDATE subscriptions
            SET is_insurance = :isInsurance
            WHERE id = :subscriptionId AND user_id = :userId
        ");
        $stmt->bindValue(':isInsurance', $isInsurance, SQLITE3_INTEGER);
        $stmt->bindValue(':subscriptionId', $subscriptionId, SQLITE3_INTEGER);
        $stmt->bindValue(':userId', $userId, SQLITE3_INTEGER);
        $stmt->execute();
    }

    private static function insUpsertInsuranceDetails(SQLite3 $db, int $subscriptionId, array $post): void
    {
        $policyNumber = self::insCleanString($post['policy_number'] ?? '');
        $insuranceSum = self::insNullableNumber($post['insurance_sum'] ?? null);
        $deductible = self::insNullableNumber($post['deductible'] ?? null);
        $claimsHotline = self::insCleanString($post['claims_hotline'] ?? '');

        $stmt = $db->prepare("
            INSERT INTO assure_insurance_details (
                subscription_id, policy_number, insurance_sum, deductible, claims_hotline, updated_at
            ) VALUES (
                :subscriptionId, :policyNumber, :insuranceSum, :deductible, :claimsHotline, CURRENT_TIMESTAMP
            )
            ON CONFLICT(subscription_id) DO UPDATE SET
                policy_number = excluded.policy_number,
                insurance_sum = excluded.insurance_sum,
                deductible = excluded.deductible,
                claims_hotline = excluded.claims_hotline,
                updated_at = CURRENT_TIMESTAMP
        ");
        $stmt->bindValue(':subscriptionId', $subscriptionId, SQLITE3_INTEGER);
        $stmt->bindValue(':policyNumber', $policyNumber, SQLITE3_TEXT);
        self::insBindNullableFloat($stmt, ':insuranceSum', $insuranceSum);
        self::insBindNullableFloat($stmt, ':deductible', $deductible);
        $stmt->bindValue(':claimsHotline', $claimsHotline, SQLITE3_TEXT);
        $stmt->execute();
    }

    private static function insLoadInsuranceFlag(SQLite3 $db, int $subscriptionId, int $userId): int
    {
        $stmt = $db->prepare("
            SELECT is_insurance
            FROM subscriptions
            WHERE id = :subscriptionId AND user_id = :userId
        ");
        $stmt->bindValue(':subscriptionId', $subscriptionId, SQLITE3_INTEGER);
        $stmt->bindValue(':userId', $userId, SQLITE3_INTEGER);

        $result = $stmt->execute();
        $row = $result ? $result->fetchArray(SQLITE3_ASSOC) : false;

        return $row ? (int) $row['is_insurance'] : 0;
    }

    private static function insLoadInsuranceDetails(SQLite3 $db, int $subscriptionId): ?array
    {
        $stmt = $db->prepare("
            SELECT policy_number, insurance_sum, deductible, claims_hotline
            FROM assure_insurance_details
            WHERE subscription_id = :subscriptionId
        ");
        $stmt->bindValue(':subscriptionId', $subscriptionId, SQLITE3_INTEGER);

        $result = $stmt->execute();
        $details = $result ? $result->fetchArray(SQLITE3_ASSOC) : false;

        return $details ?: null;
    }

    private static function insListDocuments(SQLite3 $db, int $subscriptionId, int $userId): array
    {
        $stmt = $db->prepare("
            SELECT id, doc_type, original_name, mime_type, size_bytes, uploaded_at
            FROM assure_documents
            WHERE subscription_id = :subscriptionId AND user_id = :userId
            ORDER BY uploaded_at DESC, id DESC
        ");
        $stmt->bindValue(':subscriptionId', $subscriptionId, SQLITE3_INTEGER);
        $stmt->bindValue(':userId', $userId, SQLITE3_INTEGER);

        $result = $stmt->execute();
        $documents = [];

        while ($result && ($row = $result->fetchArray(SQLITE3_ASSOC))) {
            $documents[] = $row;
        }

        return $documents;
    }

    private static function insStoreUploadedDocuments(SQLite3 $db, int $subscriptionId, int $userId, array $post, array $files): void
    {
        if (empty($files['ins_documents']['name'])) {
            return;
        }

        $documentType = self::insCleanDocumentType($post['ins_document_type'] ?? 'other');
        $documents = self::insNormalizeFilesArray($files['ins_documents']);

        foreach ($documents as $document) {
            self::insPersistUploadedDocument($db, $subscriptionId, $userId, $documentType, $document);
        }
    }

    private static function insPersistUploadedDocument(
        SQLite3 $db,
        int $subscriptionId,
        int $userId,
        string $docType,
        array $uploadedFile
    ): ?int {
        if (($uploadedFile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return null;
        }

        if (($uploadedFile['size'] ?? 0) > self::MAX_DOCUMENT_SIZE) {
            return null;
        }

        $originalName = basename((string) $uploadedFile['name']);
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        if (!array_key_exists($extension, self::ALLOWED_DOCUMENT_TYPES)) {
            return null;
        }

        $mimeType = mime_content_type($uploadedFile['tmp_name']);
        if (!self::insIsAllowedDocumentMime($extension, $mimeType, $uploadedFile['tmp_name'])) {
            return null;
        }

        $uploadDir = self::insDocumentUploadDir();
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }

        $storedName = sprintf(
            '%d-%d-%s.%s',
            $subscriptionId,
            time(),
            bin2hex(random_bytes(8)),
            $extension
        );
        $targetPath = $uploadDir . '/' . $storedName;

        if (!move_uploaded_file($uploadedFile['tmp_name'], $targetPath)) {
            return null;
        }

        $stmt = $db->prepare("
            INSERT INTO assure_documents (
                subscription_id, user_id, doc_type, filename, original_name, mime_type, size_bytes
            ) VALUES (
                :subscriptionId, :userId, :docType, :filename, :originalName, :mimeType, :sizeBytes
            )
        ");
        $stmt->bindValue(':subscriptionId', $subscriptionId, SQLITE3_INTEGER);
        $stmt->bindValue(':userId', $userId, SQLITE3_INTEGER);
        $stmt->bindValue(':docType', self::insCleanDocumentType($docType), SQLITE3_TEXT);
        $stmt->bindValue(':filename', $storedName, SQLITE3_TEXT);
        $stmt->bindValue(':originalName', self::insCleanString($originalName), SQLITE3_TEXT);
        $stmt->bindValue(':mimeType', $mimeType, SQLITE3_TEXT);
        $stmt->bindValue(':sizeBytes', (int) $uploadedFile['size'], SQLITE3_INTEGER);

        if (!$stmt->execute()) {
            unlink($targetPath);
            return null;
        }

        return (int) $db->lastInsertRowID();
    }

    private static function insSubscriptionBelongsToUser(SQLite3 $db, int $subscriptionId, int $userId): bool
    {
        $stmt = $db->prepare("SELECT COUNT(*) FROM subscriptions WHERE id = :subscriptionId AND user_id = :userId");
        $stmt->bindValue(':subscriptionId', $subscriptionId, SQLITE3_INTEGER);
        $stmt->bindValue(':userId', $userId, SQLITE3_INTEGER);

        return (int) $stmt->execute()->fetchArray(SQLITE3_NUM)[0] > 0;
    }

    private static function insDocumentUploadDir(): string
    {
        return dirname(__DIR__, 2) . '/' . self::DOCUMENT_DIR;
    }

    private static function insNormalizeFilesArray(array $fileInput): array
    {
        if (!is_array($fileInput['name'])) {
            return [$fileInput];
        }

        $files = [];
        foreach ($fileInput['name'] as $index => $name) {
            $files[] = [
                'name' => $name,
                'type' => $fileInput['type'][$index] ?? '',
                'tmp_name' => $fileInput['tmp_name'][$index] ?? '',
                'error' => $fileInput['error'][$index] ?? UPLOAD_ERR_NO_FILE,
                'size' => $fileInput['size'][$index] ?? 0,
            ];
        }

        return $files;
    }

    private static function insCleanString($value): string
    {
        return htmlspecialchars(stripslashes(trim((string) $value)), ENT_QUOTES, 'UTF-8');
    }

    private static function insNullableNumber($value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        $normalized = preg_replace('/\s+/', '', trim((string) $value));
        $hasComma = strpos($normalized, ',') !== false;
        $hasDot = strpos($normalized, '.') !== false;

        if ($hasComma && $hasDot) {
            $normalized = str_replace('.', '', $normalized);
            $normalized = str_replace(',', '.', $normalized);
        } elseif ($hasComma) {
            $normalized = str_replace(',', '.', $normalized);
        }

        return is_numeric($normalized) ? (float) $normalized : null;
    }

    private static function insBindNullableFloat(SQLite3Stmt $stmt, string $name, ?float $value): void
    {
        if ($value === null) {
            $stmt->bindValue($name, null, SQLITE3_NULL);
            return;
        }

        $stmt->bindValue($name, $value, SQLITE3_FLOAT);
    }

    private static function insCleanDocumentType($value): string
    {
        $allowedTypes = ['policy', 'claim', 'invoice', 'correspondence', 'cancellation', 'other'];
        $type = strtolower(trim((string) $value));

        return in_array($type, $allowedTypes, true) ? $type : 'other';
    }

    private static function insIsAllowedDocumentMime(string $extension, string $mimeType, string $path): bool
    {
        if (!in_array($mimeType, self::ALLOWED_DOCUMENT_TYPES[$extension], true)) {
            return false;
        }

        if ($extension === 'pdf') {
            return self::insFileStartsWith($path, '%PDF-');
        }

        return true;
    }

    private static function insFileStartsWith(string $path, string $signature): bool
    {
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            return false;
        }

        $bytes = fread($handle, strlen($signature));
        fclose($handle);

        return $bytes === $signature;
    }

    private static function insTableExists(SQLite3 $db, string $tableName): bool
    {
        $stmt = $db->prepare("SELECT name FROM sqlite_master WHERE type = 'table' AND name = :tableName");
        $stmt->bindValue(':tableName', $tableName, SQLITE3_TEXT);

        return (bool) $stmt->execute()->fetchArray(SQLITE3_ASSOC);
    }

    private static function insColumnExists(SQLite3 $db, string $tableName, string $columnName): bool
    {
        $result = $db->query("PRAGMA table_info(" . SQLite3::escapeString($tableName) . ")");

        while ($result && ($row = $result->fetchArray(SQLITE3_ASSOC))) {
            if ($row['name'] === $columnName) {
                return true;
            }
        }

        return false;
    }
}

?>
