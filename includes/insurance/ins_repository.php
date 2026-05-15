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
    private const DETAIL_FIELD_TYPES = [
        'policy_number' => 'text',
        'insurance_sum' => 'money',
        'deductible' => 'money',
        'claims_hotline' => 'text',
        'insurance_type_id' => 'int',
        'insurer_name' => 'text',
        'broker_name' => 'text',
        'tariff_name' => 'text',
        'policyholder' => 'text',
        'insured_persons' => 'text',
        'beneficiary' => 'text',
        'document_url' => 'text',
        'portal_url' => 'text',
        'portal_notes' => 'text',
        'contract_status' => 'text',
        'start_date' => 'text',
        'end_date' => 'text',
        'main_due_date' => 'text',
        'minimum_term_months' => 'int',
        'cancellation_period_value' => 'int',
        'cancellation_period_unit' => 'text',
        'auto_contract_renewal' => 'bool',
        'renewal_period_months' => 'int',
        'cancellation_status' => 'text',
        'payment_method_text' => 'text',
        'bank_account_label' => 'text',
        'contact_name' => 'text',
        'contact_phone' => 'text',
        'contact_email' => 'text',
        'claim_reference_notes' => 'text',
    ];

    public static function insSchemaReady(SQLite3 $db): bool
    {
        return self::insColumnExists($db, 'subscriptions', 'is_insurance')
            && self::insTableExists($db, 'assure_insurance_details')
            && self::insTableExists($db, 'assure_documents');
    }

    public static function insTaxonomyReady(SQLite3 $db): bool
    {
        if (!self::insTableExists($db, 'assure_insurance_groups')
            || !self::insTableExists($db, 'assure_insurance_types')) {
            return false;
        }

        // Lazy-Schema: symbol/code kommen aus Migration 9999_99_06; bei älteren DBs ohne
        // Migrations-Eintrag sonst fataler Fehler auf der Einstellungsseite.
        self::insEnsureTaxonomySymbolCodeColumns($db);

        return true;
    }

    /**
     * Fügt symbol/code auf assure_insurance_* hinzu und füllt Defaults (gleiche Logik wie Migration 9999_99_06).
     */
    private static function insEnsureTaxonomySymbolCodeColumns(SQLite3 $db): void
    {
        $altered = false;

        if (!self::insColumnExists($db, 'assure_insurance_groups', 'symbol')) {
            $db->exec("ALTER TABLE assure_insurance_groups ADD COLUMN symbol TEXT NOT NULL DEFAULT ''");
            $altered = true;
        }
        if (!self::insColumnExists($db, 'assure_insurance_groups', 'code')) {
            $db->exec("ALTER TABLE assure_insurance_groups ADD COLUMN code TEXT NOT NULL DEFAULT ''");
            $altered = true;
        }
        if (!self::insColumnExists($db, 'assure_insurance_types', 'symbol')) {
            $db->exec("ALTER TABLE assure_insurance_types ADD COLUMN symbol TEXT NOT NULL DEFAULT ''");
            $altered = true;
        }
        if (!self::insColumnExists($db, 'assure_insurance_types', 'code')) {
            $db->exec("ALTER TABLE assure_insurance_types ADD COLUMN code TEXT NOT NULL DEFAULT ''");
            $altered = true;
        }

        if (!$altered) {
            return;
        }

        $db->exec("
            UPDATE assure_insurance_groups
            SET
                symbol = COALESCE(NULLIF(TRIM(symbol), ''), SUBSTR(name, 1, 1)),
                code = COALESCE(NULLIF(TRIM(code), ''), UPPER(SUBSTR(REPLACE(TRIM(name), ' ', ''), 1, 3)))
        ");

        $db->exec("
            UPDATE assure_insurance_types
            SET
                symbol = COALESCE(NULLIF(TRIM(symbol), ''), SUBSTR(name, 1, 1)),
                code = COALESCE(
                    NULLIF(TRIM(code), ''),
                    CASE
                        WHEN import_nr IS NOT NULL THEN CAST(import_nr AS TEXT)
                        ELSE UPPER(SUBSTR(REPLACE(TRIM(name), ' ', ''), 1, 3))
                    END
                )
        ");
    }

    public static function insSaveFromPost(SQLite3 $db, int $subscriptionId, int $userId, array $post, array $files): void
    {
        if (!self::insSchemaReady($db)) {
            return;
        }

        $isInsurance = isset($post['is_insurance']) ? 1 : 0;
        self::insUpdateInsuranceFlag($db, $subscriptionId, $userId, $isInsurance);

        if ($isInsurance === 1) {
            self::insUpsertInsuranceDetails($db, $subscriptionId, $userId, $post);
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
                'insurance_taxonomy' => [],
            ];
        }

        return [
            'is_insurance' => self::insLoadInsuranceFlag($db, $subscriptionId, $userId),
            'insurance_details' => self::insLoadInsuranceDetails($db, $subscriptionId),
            'insurance_documents' => self::insListDocuments($db, $subscriptionId, $userId),
            'insurance_taxonomy' => self::insLoadTaxonomy($db, $userId),
        ];
    }

    public static function insLoadTaxonomy(SQLite3 $db, int $userId): array
    {
        if (!self::insTaxonomyReady($db)) {
            return [];
        }

        $groupsStmt = $db->prepare("
            SELECT id, name, sort_order, is_system, symbol, code
            FROM assure_insurance_groups
            WHERE active = 1 AND (user_id IS NULL OR user_id = :userId)
            ORDER BY sort_order ASC, name ASC
        ");
        $groupsStmt->bindValue(':userId', $userId, SQLITE3_INTEGER);
        $groupsResult = $groupsStmt->execute();

        $groups = [];
        while ($groupsResult && ($group = $groupsResult->fetchArray(SQLITE3_ASSOC))) {
            $group['types'] = [];
            $groups[(int) $group['id']] = $group;
        }

        if (empty($groups)) {
            return [];
        }

        $typesStmt = $db->prepare("
            SELECT id, group_id, name, import_nr, sort_order, is_system, symbol, code
            FROM assure_insurance_types
            WHERE active = 1 AND (user_id IS NULL OR user_id = :userId)
            ORDER BY sort_order ASC, name ASC
        ");
        $typesStmt->bindValue(':userId', $userId, SQLITE3_INTEGER);
        $typesResult = $typesStmt->execute();

        while ($typesResult && ($type = $typesResult->fetchArray(SQLITE3_ASSOC))) {
            $groupId = (int) $type['group_id'];
            if (isset($groups[$groupId])) {
                $groups[$groupId]['types'][] = $type;
            }
        }

        foreach (array_keys($groups) as $gid) {
            if (!empty($groups[$gid]['types'])) {
                usort($groups[$gid]['types'], static function (array $a, array $b): int {
                    return strcasecmp((string) ($a['name'] ?? ''), (string) ($b['name'] ?? ''));
                });
            }
        }

        return array_values($groups);
    }

    public static function insAddInsuranceGroup(SQLite3 $db, int $userId, string $name, string $symbol = '', string $code = ''): ?array
    {
        if (!self::insTaxonomyReady($db)) {
            return null;
        }

        $name = self::insCleanString($name);
        if ($name === '') {
            return null;
        }

        $symbol = self::insCleanString($symbol);
        $code = self::insCleanString($code);
        self::insTaxonomyApplyDefaultDisplayFields($name, $symbol, $code, null);

        $existing = self::insFindGroupByName($db, $userId, $name);
        if ($existing !== null) {
            return $existing;
        }

        $sortOrder = (int) $db->querySingle("SELECT COALESCE(MAX(sort_order), 0) + 1 FROM assure_insurance_groups");
        $stmt = $db->prepare("
            INSERT INTO assure_insurance_groups (user_id, name, symbol, code, sort_order, active, is_system)
            VALUES (:userId, :name, :symbol, :code, :sortOrder, 1, 0)
        ");
        $stmt->bindValue(':userId', $userId, SQLITE3_INTEGER);
        $stmt->bindValue(':name', $name, SQLITE3_TEXT);
        $stmt->bindValue(':symbol', $symbol, SQLITE3_TEXT);
        $stmt->bindValue(':code', $code, SQLITE3_TEXT);
        $stmt->bindValue(':sortOrder', $sortOrder, SQLITE3_INTEGER);
        $stmt->execute();

        return self::insGetGroup($db, (int) $db->lastInsertRowID(), $userId);
    }

    public static function insAddInsuranceType(SQLite3 $db, int $userId, int $groupId, string $name, string $symbol = '', string $code = ''): ?array
    {
        if (!self::insTaxonomyReady($db) || !self::insGroupIsVisible($db, $groupId, $userId)) {
            return null;
        }

        $name = self::insCleanString($name);
        if ($name === '') {
            return null;
        }

        $symbol = self::insCleanString($symbol);
        $code = self::insCleanString($code);
        self::insTaxonomyApplyDefaultDisplayFields($name, $symbol, $code, null);

        $existing = self::insFindTypeByName($db, $userId, $groupId, $name);
        if ($existing !== null) {
            return $existing;
        }

        $sortStmt = $db->prepare("SELECT COALESCE(MAX(sort_order), 0) + 1 FROM assure_insurance_types WHERE group_id = :groupId");
        $sortStmt->bindValue(':groupId', $groupId, SQLITE3_INTEGER);
        $sortOrder = (int) $sortStmt->execute()->fetchArray(SQLITE3_NUM)[0];

        $stmt = $db->prepare("
            INSERT INTO assure_insurance_types (group_id, user_id, name, symbol, code, sort_order, active, is_system)
            VALUES (:groupId, :userId, :name, :symbol, :code, :sortOrder, 1, 0)
        ");
        $stmt->bindValue(':groupId', $groupId, SQLITE3_INTEGER);
        $stmt->bindValue(':userId', $userId, SQLITE3_INTEGER);
        $stmt->bindValue(':name', $name, SQLITE3_TEXT);
        $stmt->bindValue(':symbol', $symbol, SQLITE3_TEXT);
        $stmt->bindValue(':code', $code, SQLITE3_TEXT);
        $stmt->bindValue(':sortOrder', $sortOrder, SQLITE3_INTEGER);
        $stmt->execute();

        return self::insGetType($db, (int) $db->lastInsertRowID(), $userId);
    }

    /**
     * Deletes an insurance group visible to the user (including system seed groups) and its types.
     */
    public static function insDeleteInsuranceGroup(SQLite3 $db, int $userId, int $groupId): bool
    {
        if (!self::insTaxonomyReady($db) || $groupId <= 0) {
            return false;
        }

        if (self::insGetGroup($db, $groupId, $userId) === null) {
            return false;
        }

        if (self::insColumnExists($db, 'assure_insurance_details', 'insurance_type_id')) {
            $clean = $db->prepare("
                UPDATE assure_insurance_details
                SET insurance_type_id = NULL
                WHERE insurance_type_id IN (
                    SELECT id FROM assure_insurance_types WHERE group_id = :groupId
                )
            ");
            $clean->bindValue(':groupId', $groupId, SQLITE3_INTEGER);
            $clean->execute();
        }

        $delTypes = $db->prepare("DELETE FROM assure_insurance_types WHERE group_id = :groupId");
        $delTypes->bindValue(':groupId', $groupId, SQLITE3_INTEGER);
        $delTypes->execute();

        $delGroup = $db->prepare("DELETE FROM assure_insurance_groups WHERE id = :groupId");
        $delGroup->bindValue(':groupId', $groupId, SQLITE3_INTEGER);

        return (bool) $delGroup->execute() && $db->changes() > 0;
    }

    /**
     * Deletes an insurance type visible to the user (including system seed types).
     */
    public static function insDeleteInsuranceType(SQLite3 $db, int $userId, int $typeId): bool
    {
        if (!self::insTaxonomyReady($db) || $typeId <= 0) {
            return false;
        }

        if (self::insGetType($db, $typeId, $userId) === null) {
            return false;
        }

        if (self::insColumnExists($db, 'assure_insurance_details', 'insurance_type_id')) {
            $clean = $db->prepare("
                UPDATE assure_insurance_details
                SET insurance_type_id = NULL
                WHERE insurance_type_id = :typeId
            ");
            $clean->bindValue(':typeId', $typeId, SQLITE3_INTEGER);
            $clean->execute();
        }

        $del = $db->prepare("DELETE FROM assure_insurance_types WHERE id = :typeId");
        $del->bindValue(':typeId', $typeId, SQLITE3_INTEGER);

        return (bool) $del->execute() && $db->changes() > 0;
    }

    /**
     * Saves name, symbol and code for any taxonomy group visible to the user (including system seeds).
     */
    public static function insSaveInsuranceGroup(SQLite3 $db, int $userId, int $groupId, string $name, string $symbol, string $code): bool
    {
        if (!self::insTaxonomyReady($db) || $groupId <= 0) {
            return false;
        }

        $name = self::insCleanString($name);
        if ($name === '') {
            return false;
        }

        $symbol = self::insCleanString($symbol);
        $code = self::insCleanString($code);

        $row = self::insGetGroup($db, $groupId, $userId);
        if ($row === null) {
            return false;
        }

        self::insTaxonomyApplyDefaultDisplayFields($name, $symbol, $code, null);

        if ((string) ($row['name'] ?? '') === $name
            && (string) ($row['symbol'] ?? '') === $symbol
            && (string) ($row['code'] ?? '') === $code) {
            return true;
        }

        if ((string) ($row['name'] ?? '') !== $name) {
            $conflict = $db->prepare("
                SELECT COUNT(*) FROM assure_insurance_groups
                WHERE active = 1 AND name = :name AND (user_id IS NULL OR user_id = :userId) AND id != :groupId
            ");
            $conflict->bindValue(':name', $name, SQLITE3_TEXT);
            $conflict->bindValue(':userId', $userId, SQLITE3_INTEGER);
            $conflict->bindValue(':groupId', $groupId, SQLITE3_INTEGER);
            if ((int) $conflict->execute()->fetchArray(SQLITE3_NUM)[0] > 0) {
                return false;
            }
        }

        $upd = $db->prepare("
            UPDATE assure_insurance_groups
            SET name = :name, symbol = :symbol, code = :code, updated_at = CURRENT_TIMESTAMP
            WHERE id = :groupId AND active = 1
        ");
        $upd->bindValue(':name', $name, SQLITE3_TEXT);
        $upd->bindValue(':symbol', $symbol, SQLITE3_TEXT);
        $upd->bindValue(':code', $code, SQLITE3_TEXT);
        $upd->bindValue(':groupId', $groupId, SQLITE3_INTEGER);

        return (bool) $upd->execute();
    }

    /**
     * Saves type fields and optionally moves the type to another visible group (including system rows).
     */
    public static function insSaveInsuranceType(SQLite3 $db, int $userId, int $typeId, int $groupId, string $name, string $symbol, string $code): bool
    {
        if (!self::insTaxonomyReady($db) || $typeId <= 0 || $groupId <= 0) {
            return false;
        }

        $name = self::insCleanString($name);
        if ($name === '') {
            return false;
        }

        $symbol = self::insCleanString($symbol);
        $code = self::insCleanString($code);

        $row = self::insGetType($db, $typeId, $userId);
        if ($row === null) {
            return false;
        }

        self::insTaxonomyApplyDefaultDisplayFields($name, $symbol, $code, $row);

        if (!self::insGroupIsVisible($db, $groupId, $userId)) {
            return false;
        }

        $oldGroupId = (int) $row['group_id'];
        if ((string) ($row['name'] ?? '') === $name
            && (string) ($row['symbol'] ?? '') === $symbol
            && (string) ($row['code'] ?? '') === $code
            && $oldGroupId === $groupId) {
            return true;
        }

        if ($oldGroupId !== $groupId || (string) ($row['name'] ?? '') !== $name) {
            $conflict = $db->prepare("
                SELECT COUNT(*) FROM assure_insurance_types
                WHERE active = 1 AND group_id = :groupId AND name = :name
                    AND (user_id IS NULL OR user_id = :userId) AND id != :typeId
            ");
            $conflict->bindValue(':groupId', $groupId, SQLITE3_INTEGER);
            $conflict->bindValue(':name', $name, SQLITE3_TEXT);
            $conflict->bindValue(':userId', $userId, SQLITE3_INTEGER);
            $conflict->bindValue(':typeId', $typeId, SQLITE3_INTEGER);
            if ((int) $conflict->execute()->fetchArray(SQLITE3_NUM)[0] > 0) {
                return false;
            }
        }

        $upd = $db->prepare("
            UPDATE assure_insurance_types
            SET group_id = :groupId, name = :name, symbol = :symbol, code = :code, updated_at = CURRENT_TIMESTAMP
            WHERE id = :typeId AND active = 1
        ");
        $upd->bindValue(':groupId', $groupId, SQLITE3_INTEGER);
        $upd->bindValue(':name', $name, SQLITE3_TEXT);
        $upd->bindValue(':symbol', $symbol, SQLITE3_TEXT);
        $upd->bindValue(':code', $code, SQLITE3_TEXT);
        $upd->bindValue(':typeId', $typeId, SQLITE3_INTEGER);

        return (bool) $upd->execute();
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

    private static function insUpsertInsuranceDetails(SQLite3 $db, int $subscriptionId, int $userId, array $post): void
    {
        $availableFields = self::insAvailableDetailFields($db);
        $fieldNames = [];
        $placeholders = [];
        $updates = [];
        $values = [];

        foreach (self::DETAIL_FIELD_TYPES as $field => $type) {
            if (!in_array($field, $availableFields, true)) {
                continue;
            }

            $fieldNames[] = $field;
            $placeholders[] = ':' . $field;
            $updates[] = $field . ' = excluded.' . $field;
            $values[$field] = self::insValueForField($db, $userId, $field, $type, $post);
        }

        if (empty($fieldNames)) {
            return;
        }

        $sql = sprintf(
            "INSERT INTO assure_insurance_details (subscription_id, %s, updated_at) VALUES (:subscriptionId, %s, CURRENT_TIMESTAMP)
            ON CONFLICT(subscription_id) DO UPDATE SET %s, updated_at = CURRENT_TIMESTAMP",
            implode(', ', $fieldNames),
            implode(', ', $placeholders),
            implode(', ', $updates)
        );

        $stmt = $db->prepare($sql);
        $stmt->bindValue(':subscriptionId', $subscriptionId, SQLITE3_INTEGER);

        foreach ($values as $field => $value) {
            self::insBindValueByFieldType($stmt, ':' . $field, self::DETAIL_FIELD_TYPES[$field], $value);
        }

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
        $stmt = $db->prepare("SELECT * FROM assure_insurance_details WHERE subscription_id = :subscriptionId");
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

    private static function insAvailableDetailFields(SQLite3 $db): array
    {
        $columns = [];
        $result = $db->query("PRAGMA table_info(assure_insurance_details)");

        while ($result && ($row = $result->fetchArray(SQLITE3_ASSOC))) {
            $columns[] = $row['name'];
        }

        return $columns;
    }

    private static function insValueForField(SQLite3 $db, int $userId, string $field, string $type, array $post)
    {
        if ($type === 'bool') {
            if ($field === 'auto_contract_renewal') {
                return isset($post['auto_renew']) ? 1 : 0;
            }

            return isset($post[$field]) ? 1 : 0;
        }

        $value = $post[$field] ?? null;

        if ($type === 'money') {
            return self::insNullableNumber($value);
        }

        if ($type === 'int') {
            $intValue = self::insNullableInt($value);
            if ($field === 'insurance_type_id' && $intValue !== null) {
                return self::insGetType($db, $intValue, $userId) !== null ? $intValue : null;
            }

            return $intValue;
        }

        return self::insCleanString($value ?? '');
    }

    private static function insBindValueByFieldType(SQLite3Stmt $stmt, string $name, string $type, $value): void
    {
        if ($type === 'money') {
            self::insBindNullableFloat($stmt, $name, $value);
            return;
        }

        if ($type === 'int' || $type === 'bool') {
            if ($value === null) {
                $stmt->bindValue($name, null, SQLITE3_NULL);
                return;
            }

            $stmt->bindValue($name, (int) $value, SQLITE3_INTEGER);
            return;
        }

        $stmt->bindValue($name, $value, SQLITE3_TEXT);
    }

    /**
     * Setzt symbol/code aus dem Namen, wenn beide leer sind (vereinfachte Einstellungs-UI ohne Kürzel/Code-Felder).
     * Entspricht der Intention von Migration 9999_99_06; bei Arten mit import_nr wird dieser als Code genutzt.
     */
    private static function insTaxonomyApplyDefaultDisplayFields(string $name, string &$symbol, string &$code, ?array $typeRow): void
    {
        if ($symbol !== '' || $code !== '') {
            return;
        }

        $symbol = self::insTaxonomyDefaultSymbolForName($name);
        if ($typeRow !== null) {
            $imp = $typeRow['import_nr'] ?? null;
            if ($imp !== null && trim((string) $imp) !== '') {
                $code = (string) $imp;
            } else {
                $code = self::insTaxonomyDefaultCodeForName($name);
            }
        } else {
            $code = self::insTaxonomyDefaultCodeForName($name);
        }
    }

    private static function insTaxonomyDefaultSymbolForName(string $name): string
    {
        $trimmed = trim($name);
        if ($trimmed === '') {
            return '';
        }

        if (function_exists('mb_substr')) {
            return (string) mb_substr($trimmed, 0, 1, 'UTF-8');
        }

        return (string) substr($trimmed, 0, 1);
    }

    private static function insTaxonomyDefaultCodeForName(string $name): string
    {
        $collapsed = str_replace(' ', '', trim($name));
        if ($collapsed === '') {
            return '';
        }

        $chunk = function_exists('mb_substr')
            ? mb_substr($collapsed, 0, 3, 'UTF-8')
            : substr($collapsed, 0, 3);

        return strtoupper((string) $chunk);
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

    private static function insNullableInt($value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_numeric($value) ? (int) $value : null;
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

    private static function insFindGroupByName(SQLite3 $db, int $userId, string $name): ?array
    {
        $stmt = $db->prepare("
            SELECT id, name, sort_order, is_system
            FROM assure_insurance_groups
            WHERE active = 1 AND name = :name AND (user_id IS NULL OR user_id = :userId)
            ORDER BY is_system ASC
            LIMIT 1
        ");
        $stmt->bindValue(':name', $name, SQLITE3_TEXT);
        $stmt->bindValue(':userId', $userId, SQLITE3_INTEGER);

        $result = $stmt->execute();
        $row = $result ? $result->fetchArray(SQLITE3_ASSOC) : false;

        return $row ?: null;
    }

    private static function insFindTypeByName(SQLite3 $db, int $userId, int $groupId, string $name): ?array
    {
        $stmt = $db->prepare("
            SELECT id, group_id, name, import_nr, sort_order, is_system
            FROM assure_insurance_types
            WHERE active = 1 AND group_id = :groupId AND name = :name AND (user_id IS NULL OR user_id = :userId)
            ORDER BY is_system ASC
            LIMIT 1
        ");
        $stmt->bindValue(':groupId', $groupId, SQLITE3_INTEGER);
        $stmt->bindValue(':name', $name, SQLITE3_TEXT);
        $stmt->bindValue(':userId', $userId, SQLITE3_INTEGER);

        $result = $stmt->execute();
        $row = $result ? $result->fetchArray(SQLITE3_ASSOC) : false;

        return $row ?: null;
    }

    private static function insGetGroup(SQLite3 $db, int $groupId, int $userId): ?array
    {
        $stmt = $db->prepare("
            SELECT id, name, sort_order, is_system, symbol, code
            FROM assure_insurance_groups
            WHERE id = :groupId AND active = 1 AND (user_id IS NULL OR user_id = :userId)
        ");
        $stmt->bindValue(':groupId', $groupId, SQLITE3_INTEGER);
        $stmt->bindValue(':userId', $userId, SQLITE3_INTEGER);

        $result = $stmt->execute();
        $row = $result ? $result->fetchArray(SQLITE3_ASSOC) : false;

        return $row ?: null;
    }

    private static function insGetType(SQLite3 $db, int $typeId, int $userId): ?array
    {
        $stmt = $db->prepare("
            SELECT id, group_id, name, import_nr, sort_order, is_system, symbol, code
            FROM assure_insurance_types
            WHERE id = :typeId AND active = 1 AND (user_id IS NULL OR user_id = :userId)
        ");
        $stmt->bindValue(':typeId', $typeId, SQLITE3_INTEGER);
        $stmt->bindValue(':userId', $userId, SQLITE3_INTEGER);

        $result = $stmt->execute();
        $row = $result ? $result->fetchArray(SQLITE3_ASSOC) : false;

        return $row ?: null;
    }

    private static function insGroupIsVisible(SQLite3 $db, int $groupId, int $userId): bool
    {
        return self::insGetGroup($db, $groupId, $userId) !== null;
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
