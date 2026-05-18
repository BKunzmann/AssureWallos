<?php

require_once __DIR__ . '/ins_repository.php';

/**
 * CSV import for Wallos subscriptions + AssureWallos insurance details (preview + commit).
 */
class Ins_Csv_Import
{
    public const MAX_FILE_BYTES = 2097152;
    public const MAX_ROWS = 500;
    public const SESSION_TTL_SECONDS = 1800;

    /** @var array<string, string> */
    private const HEADER_ALIASES = [
        'name' => 'name',
        'bezeichnung' => 'name',
        'titel' => 'name',
        'price' => 'price',
        'preis' => 'price',
        'betrag' => 'price',
        'currency' => 'currency',
        'waehrung' => 'currency',
        'währung' => 'currency',
        'cycle' => 'cycle',
        'zyklus' => 'cycle',
        'zahlungszyklus' => 'cycle',
        'frequency' => 'frequency',
        'frequenz' => 'frequency',
        'next_payment' => 'next_payment',
        'naechste_zahlung' => 'next_payment',
        'nächste_zahlung' => 'next_payment',
        'category' => 'category',
        'kategorie' => 'category',
        'payment_method' => 'payment_method',
        'zahlungsmethode' => 'payment_method',
        'payer' => 'payer',
        'zahler' => 'payer',
        'bezahlt_von' => 'payer',
        'notes' => 'notes',
        'notizen' => 'notes',
        'url' => 'url',
        'inactive' => 'inactive',
        'inaktiv' => 'inactive',
        'notify' => 'notify',
        'benachrichtigung' => 'notify',
        'auto_renew' => 'auto_renew',
        'automatische_verlaengerung' => 'auto_renew',
        'start_date' => 'start_date',
        'startdatum' => 'start_date',
        'cancellation_date' => 'cancellation_date',
        'kuendigungsdatum' => 'cancellation_date',
        'kündigungsdatum' => 'cancellation_date',
        'is_insurance' => 'is_insurance',
        'versicherung' => 'is_insurance',
        'insurance_group' => 'insurance_group',
        'versicherungsgruppe' => 'insurance_group',
        'gruppe' => 'insurance_group',
        'insurance_type' => 'insurance_type',
        'versicherungsart' => 'insurance_type',
        'art' => 'insurance_type',
        'policy_number' => 'policy_number',
        'versicherungsnummer' => 'policy_number',
        'policennummer' => 'policy_number',
        'insurer_name' => 'insurer_name',
        'versicherer' => 'insurer_name',
        'broker_name' => 'broker_name',
        'makler' => 'broker_name',
        'tariff_name' => 'tariff_name',
        'tarif' => 'tariff_name',
        'policyholder' => 'policyholder',
        'versicherungsnehmer' => 'policyholder',
        'insured_persons' => 'insured_persons',
        'versicherte_personen' => 'insured_persons',
        'beneficiary' => 'beneficiary',
        'beguenstigte' => 'beneficiary',
        'begünstigte' => 'beneficiary',
        'document_url' => 'document_url',
        'portal_url' => 'portal_url',
        'portal_notes' => 'portal_notes',
        'contract_status' => 'contract_status',
        'vertragsstatus' => 'contract_status',
        'end_date' => 'end_date',
        'vertragsende' => 'end_date',
        'main_due_date' => 'main_due_date',
        'hauptfaelligkeit' => 'main_due_date',
        'insurance_sum' => 'insurance_sum',
        'versicherungssumme' => 'insurance_sum',
        'deductible' => 'deductible',
        'selbstbeteiligung' => 'deductible',
        'claims_hotline' => 'claims_hotline',
        'minimum_term_months' => 'minimum_term_months',
        'cancellation_period_value' => 'cancellation_period_value',
        'cancellation_period_unit' => 'cancellation_period_unit',
        'auto_contract_renewal' => 'auto_contract_renewal',
        'renewal_period_months' => 'renewal_period_months',
        'cancellation_status' => 'cancellation_status',
        'payment_method_text' => 'payment_method_text',
        'bank_account_label' => 'bank_account_label',
        'contact_name' => 'contact_name',
        'contact_phone' => 'contact_phone',
        'contact_email' => 'contact_email',
        'claim_reference_notes' => 'claim_reference_notes',
    ];

    /** @var array<string, string> */
    private const CYCLE_ALIASES = [
        'daily' => 'Daily',
        'täglich' => 'Daily',
        'taeglich' => 'Daily',
        'weekly' => 'Weekly',
        'wöchentlich' => 'Weekly',
        'woechentlich' => 'Weekly',
        'monthly' => 'Monthly',
        'monatlich' => 'Monthly',
        'yearly' => 'Yearly',
        'jährlich' => 'Yearly',
        'jaehrlich' => 'Yearly',
        'annual' => 'Yearly',
    ];

    /**
     * @return list<string>
     */
    public static function insTemplateHeaders(): array
    {
        return [
            'name',
            'price',
            'currency',
            'cycle',
            'frequency',
            'next_payment',
            'category',
            'payment_method',
            'payer',
            'notes',
            'url',
            'inactive',
            'notify',
            'auto_renew',
            'start_date',
            'cancellation_date',
            'is_insurance',
            'insurance_group',
            'insurance_type',
            'policy_number',
            'insurer_name',
            'broker_name',
            'tariff_name',
            'policyholder',
            'insured_persons',
            'beneficiary',
            'contract_status',
            'end_date',
            'main_due_date',
            'insurance_sum',
            'deductible',
            'claims_hotline',
            'minimum_term_months',
            'cancellation_period_value',
            'cancellation_period_unit',
            'renewal_period_months',
            'cancellation_status',
            'payment_method_text',
            'bank_account_label',
            'contact_name',
            'contact_phone',
            'contact_email',
            'claim_reference_notes',
            'document_url',
            'portal_url',
            'portal_notes',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function insTemplateExampleRow(): array
    {
        return [
            'name' => 'Haftpflicht Muster',
            'price' => '8.50',
            'currency' => 'EUR',
            'cycle' => 'Monthly',
            'frequency' => '1',
            'next_payment' => date('Y-m-d', strtotime('+1 month')),
            'category' => 'Versicherung',
            'payment_method' => '',
            'payer' => '',
            'notes' => '',
            'url' => '',
            'inactive' => '0',
            'notify' => '1',
            'auto_renew' => '1',
            'start_date' => date('Y-m-d'),
            'cancellation_date' => '',
            'is_insurance' => '1',
            'insurance_group' => 'Privat',
            'insurance_type' => 'Privathaftpflichtversicherung',
            'policy_number' => 'HP-123456',
            'insurer_name' => 'Beispiel Versicherung AG',
            'broker_name' => '',
            'tariff_name' => 'Komfort',
            'policyholder' => 'Max Mustermann',
            'insured_persons' => 'Max Mustermann',
            'beneficiary' => '',
            'contract_status' => 'active',
            'end_date' => '',
            'main_due_date' => '01.01.',
            'insurance_sum' => '3000000',
            'deductible' => '150',
            'claims_hotline' => '0800 123456',
            'minimum_term_months' => '12',
            'cancellation_period_value' => '3',
            'cancellation_period_unit' => 'months',
            'renewal_period_months' => '12',
            'cancellation_status' => 'none',
            'payment_method_text' => 'Lastschrift',
            'bank_account_label' => '',
            'contact_name' => '',
            'contact_phone' => '',
            'contact_email' => '',
            'claim_reference_notes' => '',
            'document_url' => '',
            'portal_url' => '',
            'portal_notes' => '',
        ];
    }

    public static function insTemplateCsv(): string
    {
        $headers = self::insTemplateHeaders();
        $example = self::insTemplateExampleRow();
        $lines = [implode(';', $headers)];
        $values = [];
        foreach ($headers as $header) {
            $values[] = self::insCsvEscape((string) ($example[$header] ?? ''));
        }
        $lines[] = implode(';', $values);

        return implode("\n", $lines) . "\n";
    }

    /**
     * @return array{success: bool, rows: array<int, array<string, string>>, message: ?string}
     */
    public static function insParseCsv(string $raw): array
    {
        $raw = self::insStripBom($raw);
        $raw = trim($raw);
        if ($raw === '') {
            return ['success' => false, 'rows' => [], 'message' => 'Die CSV-Datei ist leer.'];
        }

        $lines = preg_split('/\r\n|\r|\n/', $raw);
        if (!is_array($lines) || count($lines) < 2) {
            return ['success' => false, 'rows' => [], 'message' => 'Die CSV-Datei braucht eine Kopfzeile und mindestens eine Datenzeile.'];
        }

        $firstLine = (string) $lines[0];
        $delimiter = self::insDetectDelimiter($firstLine);
        $headerRow = str_getcsv($firstLine, $delimiter);
        if (!is_array($headerRow) || empty($headerRow)) {
            return ['success' => false, 'rows' => [], 'message' => 'Kopfzeile konnte nicht gelesen werden.'];
        }

        $canonical = [];
        foreach ($headerRow as $index => $header) {
            $key = self::insNormalizeHeader((string) $header);
            if ($key === '') {
                continue;
            }
            $canonical[$index] = $key;
        }

        if (!in_array('name', $canonical, true)) {
            return ['success' => false, 'rows' => [], 'message' => 'Pflichtspalte „name“ fehlt in der Kopfzeile.'];
        }

        $rows = [];
        $lineNo = 1;
        for ($i = 1, $c = count($lines); $i < $c; $i++) {
            $lineNo++;
            $line = trim((string) $lines[$i]);
            if ($line === '') {
                continue;
            }

            $cells = str_getcsv($line, $delimiter);
            if (!is_array($cells)) {
                continue;
            }

            $assoc = [];
            $hasValue = false;
            foreach ($canonical as $index => $key) {
                $value = trim((string) ($cells[$index] ?? ''));
                if ($value !== '') {
                    $hasValue = true;
                }
                $assoc[$key] = $value;
            }

            if ($hasValue) {
                $rows[] = $assoc;
            }

            if (count($rows) > self::MAX_ROWS) {
                return [
                    'success' => false,
                    'rows' => [],
                    'message' => 'Maximal ' . self::MAX_ROWS . ' Datenzeilen pro Import.',
                ];
            }
        }

        if (empty($rows)) {
            return ['success' => false, 'rows' => [], 'message' => 'Keine Datenzeilen gefunden.'];
        }

        return ['success' => true, 'rows' => $rows, 'message' => null];
    }

    /**
     * @param array<int, array<string, string>> $rawRows
     * @param array<string, mixed> $uiDefaults category_id, etc. from preview form
     * @return array{success: bool, summary: array<string, int>, rows: array<int, array<string, mixed>>, batch_token: ?string, message: ?string}
     */
    public static function insPreview(SQLite3 $db, int $userId, array $rawRows, array $uiDefaults = []): array
    {
        if (!Ins_Repository::insSchemaReady($db)) {
            return self::insPreviewError('Versicherungsmodul ist noch nicht migriert.');
        }

        $context = self::insBuildContext($db, $userId);
        $previewRows = [];
        $summary = ['total' => 0, 'ok' => 0, 'warning' => 0, 'error' => 0, 'importable' => 0];

        foreach ($rawRows as $index => $raw) {
            $line = $index + 2;
            $validated = self::insValidateRow($db, $userId, $raw, $context, $uiDefaults, $line);
            $previewRows[] = $validated;
            $summary['total']++;
            $summary[$validated['status']]++;
            if ($validated['importable']) {
                $summary['importable']++;
            }
        }

        $token = self::insStagePreview($userId, $previewRows, [
            'created_at' => time(),
            'ui_defaults' => $uiDefaults,
        ]);

        return [
            'success' => true,
            'summary' => $summary,
            'rows' => array_map(static function (array $row): array {
                return [
                    'line' => $row['line'],
                    'status' => $row['status'],
                    'importable' => $row['importable'],
                    'messages' => $row['messages'],
                    'name' => $row['display']['name'] ?? '',
                    'policy_number' => $row['display']['policy_number'] ?? '',
                ];
            }, $previewRows),
            'batch_token' => $token,
            'message' => null,
        ];
    }

    /**
     * @param array<string, mixed> $options include_duplicates, default_category_id
     * @return array{success: bool, created: int, skipped: int, errors: int, messages: array<int, string>, message: ?string}
     */
    public static function insCommitBatch(SQLite3 $db, int $userId, string $token, array $options = []): array
    {
        $staged = self::insGetStaged($token, $userId);
        if ($staged === null) {
            return [
                'success' => false,
                'created' => 0,
                'skipped' => 0,
                'errors' => 0,
                'messages' => [],
                'message' => 'Import-Vorschau abgelaufen oder ungültig. Bitte CSV erneut hochladen.',
            ];
        }

        $includeDuplicates = !empty($options['include_duplicates']);
        $created = 0;
        $skipped = 0;
        $errors = 0;
        $messages = [];

        foreach ($staged['rows'] as $row) {
            if (!$row['importable']) {
                $skipped++;
                continue;
            }

            if ($row['status'] === 'warning' && !empty($row['duplicate']) && !$includeDuplicates) {
                $skipped++;
                continue;
            }

            if ($row['status'] === 'error' || empty($row['subscription'])) {
                $errors++;
                $messages[$row['line']] = 'Zeile ' . $row['line'] . ': Import fehlgeschlagen.';
                continue;
            }

            try {
                $subscriptionId = self::insInsertSubscription($db, $userId, $row['subscription']);
                if ($subscriptionId <= 0) {
                    throw new RuntimeException('INSERT fehlgeschlagen');
                }

                if (!empty($row['post'])) {
                    Ins_Repository::insSaveFromPost($db, $subscriptionId, $userId, $row['post'], []);
                }

                $created++;
            } catch (Throwable $e) {
                $errors++;
                $messages[$row['line']] = 'Zeile ' . $row['line'] . ': ' . $e->getMessage();
                error_log('AssureWallos CSV import line ' . $row['line'] . ': ' . $e->getMessage());
            }
        }

        self::insClearStaged($token, $userId);

        return [
            'success' => $errors === 0,
            'created' => $created,
            'skipped' => $skipped,
            'errors' => $errors,
            'messages' => $messages,
            'message' => $created > 0
                ? $created . ' Vertrag/Verträge importiert.'
                : 'Es wurde kein Vertrag importiert.',
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $previewRows
     * @param array<string, mixed> $meta
     */
    public static function insStagePreview(int $userId, array $previewRows, array $meta): string
    {
        $token = bin2hex(random_bytes(16));
        if (!isset($_SESSION['assure_csv_import']) || !is_array($_SESSION['assure_csv_import'])) {
            $_SESSION['assure_csv_import'] = [];
        }

        $_SESSION['assure_csv_import'][$token] = [
            'user_id' => $userId,
            'expires' => time() + self::SESSION_TTL_SECONDS,
            'meta' => $meta,
            'rows' => $previewRows,
        ];

        return $token;
    }

    /**
     * @return array{rows: array<int, array<string, mixed>>, meta: array<string, mixed>}|null
     */
    public static function insGetStaged(string $token, int $userId): ?array
    {
        $token = trim($token);
        if ($token === '') {
            return null;
        }

        $bucket = $_SESSION['assure_csv_import'][$token] ?? null;
        if (!is_array($bucket)) {
            return null;
        }

        if ((int) ($bucket['user_id'] ?? 0) !== $userId) {
            return null;
        }

        if ((int) ($bucket['expires'] ?? 0) < time()) {
            unset($_SESSION['assure_csv_import'][$token]);
            return null;
        }

        return [
            'rows' => is_array($bucket['rows'] ?? null) ? $bucket['rows'] : [],
            'meta' => is_array($bucket['meta'] ?? null) ? $bucket['meta'] : [],
        ];
    }

    public static function insClearStaged(string $token, int $userId): void
    {
        $bucket = $_SESSION['assure_csv_import'][$token] ?? null;
        if (is_array($bucket) && (int) ($bucket['user_id'] ?? 0) === $userId) {
            unset($_SESSION['assure_csv_import'][$token]);
        }
    }

    /**
     * @param array<string, string> $raw
     * @param array<string, mixed> $context
     * @param array<string, mixed> $uiDefaults
     * @return array<string, mixed>
     */
    private static function insValidateRow(
        SQLite3 $db,
        int $userId,
        array $raw,
        array $context,
        array $uiDefaults,
        int $line
    ): array {
        $messages = [];
        $status = 'ok';
        $duplicate = false;

        $name = trim((string) ($raw['name'] ?? ''));
        if ($name === '') {
            $messages[] = 'Name fehlt.';
            $status = 'error';
        }

        $priceRaw = trim((string) ($raw['price'] ?? ''));
        if ($priceRaw === '' || !is_numeric(str_replace(',', '.', $priceRaw))) {
            $messages[] = 'Preis fehlt oder ist ungültig.';
            $status = 'error';
        }
        $price = (float) str_replace(',', '.', $priceRaw);

        $currencyId = self::insResolveCurrency($raw['currency'] ?? '', $context, $messages, $status);
        if ($currencyId === null && $status !== 'error') {
            $currencyId = (int) ($uiDefaults['default_currency_id'] ?? $context['defaults']['currency_id']);
            $messages[] = 'Währung nicht gefunden — Standard verwendet.';
            $status = self::insEscalateStatus($status, 'warning');
        }

        $cycleId = self::insResolveCycle($raw['cycle'] ?? '', $context, $messages, $status);
        if ($cycleId === null) {
            $messages[] = 'Zyklus fehlt oder unbekannt (z. B. Monthly, Yearly).';
            $status = 'error';
        }

        $nextPayment = trim((string) ($raw['next_payment'] ?? ''));
        if ($nextPayment === '' || !self::insIsIsoDate($nextPayment)) {
            $messages[] = 'next_payment fehlt oder ungültig (YYYY-MM-DD).';
            $status = 'error';
        }

        $frequency = (int) ($raw['frequency'] ?? '1');
        if ($frequency < 1) {
            $frequency = 1;
        }

        $categoryId = self::insResolveCategory($raw['category'] ?? '', $context);
        if ($categoryId === null) {
            $categoryId = (int) ($uiDefaults['default_category_id'] ?? $context['defaults']['category_id']);
            if ($categoryId > 0) {
                $messages[] = 'Kategorie nicht gefunden — Standard verwendet.';
                $status = self::insEscalateStatus($status, 'warning');
            } else {
                $messages[] = 'Kategorie fehlt und kein Standard verfügbar.';
                $status = 'error';
            }
        }

        $paymentMethodId = self::insResolvePaymentMethod($raw['payment_method'] ?? '', $context);
        if ($paymentMethodId === null) {
            $paymentMethodId = (int) $context['defaults']['payment_method_id'];
            if (trim((string) ($raw['payment_method'] ?? '')) !== '') {
                $messages[] = 'Zahlungsmethode nicht gefunden — Standard verwendet.';
                $status = self::insEscalateStatus($status, 'warning');
            }
        }

        $payerId = self::insResolvePayer($raw['payer'] ?? '', $context);
        if ($payerId === null) {
            $payerId = (int) $context['defaults']['payer_id'];
            if (trim((string) ($raw['payer'] ?? '')) !== '') {
                $messages[] = 'Zahler nicht gefunden — Standard verwendet.';
                $status = self::insEscalateStatus($status, 'warning');
            }
        }

        $isInsurance = self::insParseBool($raw['is_insurance'] ?? '1', true);

        $policyNumber = trim((string) ($raw['policy_number'] ?? ''));
        if ($isInsurance && $policyNumber !== '' && self::insPolicyExists($db, $userId, $policyNumber)) {
            $messages[] = 'Versicherungsnummer existiert bereits.';
            $duplicate = true;
            $status = self::insEscalateStatus($status, 'warning');
        }

        if ($name !== '' && self::insNamePriceExists($db, $userId, $name, $price)) {
            $messages[] = 'Name und Preis existieren bereits als Abonnement.';
            $duplicate = true;
            $status = self::insEscalateStatus($status, 'warning');
        }

        $subscription = null;
        $post = [];

        if ($status !== 'error' && $cycleId !== null && $currencyId !== null && $categoryId !== null) {
            $subscription = [
                'name' => $name,
                'logo' => '',
                'price' => $price,
                'currency_id' => $currencyId,
                'next_payment' => $nextPayment,
                'cycle' => $cycleId,
                'frequency' => $frequency,
                'notes' => trim((string) ($raw['notes'] ?? '')),
                'payment_method_id' => $paymentMethodId,
                'payer_user_id' => $payerId,
                'category_id' => $categoryId,
                'notify' => self::insParseBool($raw['notify'] ?? '1', true) ? 1 : 0,
                'inactive' => self::insParseBool($raw['inactive'] ?? '0', false) ? 1 : 0,
                'url' => trim((string) ($raw['url'] ?? '')),
                'notify_days_before' => 1,
                'cancellation_date' => self::insNullableDate($raw['cancellation_date'] ?? ''),
                'replacement_subscription_id' => null,
                'auto_renew' => self::insParseBool($raw['auto_renew'] ?? '1', true) ? 1 : 0,
                'start_date' => self::insNullableDate($raw['start_date'] ?? '') ?? $nextPayment,
            ];

            if ($isInsurance) {
                $post['is_insurance'] = '1';
                $typeId = self::insResolveInsuranceType(
                    $db,
                    $userId,
                    (string) ($raw['insurance_group'] ?? ''),
                    (string) ($raw['insurance_type'] ?? ''),
                    $messages,
                    $status
                );
                if ($typeId !== null) {
                    $post['insurance_type_id'] = (string) $typeId;
                }

                foreach (Ins_Repository::insDetailFieldsForImport() as $field) {
                    if (!array_key_exists($field, $raw)) {
                        continue;
                    }
                    $value = trim((string) $raw[$field]);
                    if ($value === '') {
                        continue;
                    }
                    if ($field === 'auto_contract_renewal') {
                        $post[$field] = self::insParseBool($value, false) ? '1' : '';
                        continue;
                    }
                    $post[$field] = $value;
                }

                if (!empty($raw['auto_renew']) && !isset($post['auto_contract_renewal'])) {
                    $post['auto_contract_renewal'] = self::insParseBool($raw['auto_renew'], true) ? '1' : '';
                }
            }
        }

        $importable = $status === 'ok' || ($status === 'warning' && !$duplicate);

        return [
            'line' => $line,
            'status' => $status,
            'importable' => $importable,
            'duplicate' => $duplicate,
            'messages' => $messages,
            'display' => [
                'name' => $name,
                'policy_number' => $policyNumber,
            ],
            'subscription' => $subscription,
            'post' => $post,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function insBuildContext(SQLite3 $db, int $userId): array
    {
        require_once dirname(__DIR__) . '/getdbkeys.php';

        $currencyMap = [];
        foreach ($currencies as $id => $row) {
            $code = strtolower(trim((string) ($row['code'] ?? '')));
            $name = strtolower(trim((string) ($row['name'] ?? '')));
            if ($code !== '') {
                $currencyMap[$code] = (int) $id;
            }
            if ($name !== '') {
                $currencyMap[$name] = (int) $id;
            }
        }

        $categoryMap = [];
        $firstCategoryId = 0;
        foreach ($categories as $id => $row) {
            $key = strtolower(trim((string) ($row['name'] ?? '')));
            if ($key !== '') {
                $categoryMap[$key] = (int) $id;
            }
            if ($firstCategoryId === 0) {
                $firstCategoryId = (int) $id;
            }
        }

        $paymentMap = [];
        $firstPaymentId = 0;
        foreach ($payment_methods as $id => $row) {
            $key = strtolower(trim((string) ($row['name'] ?? '')));
            if ($key !== '') {
                $paymentMap[$key] = (int) $id;
            }
            if ($firstPaymentId === 0) {
                $firstPaymentId = (int) $id;
            }
        }

        $payerMap = [];
        $firstPayerId = 0;
        foreach ($members as $id => $row) {
            $key = strtolower(trim((string) ($row['name'] ?? '')));
            if ($key !== '') {
                $payerMap[$key] = (int) $id;
            }
            if ($firstPayerId === 0) {
                $firstPayerId = (int) $id;
            }
        }

        $cycleMap = [];
        foreach ($cycles as $id => $row) {
            $key = strtolower(trim((string) ($row['name'] ?? '')));
            if ($key !== '') {
                $cycleMap[$key] = (int) $id;
            }
        }

        $mainCurrencyStmt = $db->prepare('SELECT main_currency FROM user WHERE id = :userId');
        $mainCurrencyStmt->bindValue(':userId', $userId, SQLITE3_INTEGER);
        $mainRow = $mainCurrencyStmt->execute()->fetchArray(SQLITE3_ASSOC);
        $mainCurrencyId = (int) ($mainRow['main_currency'] ?? 0);
        if ($mainCurrencyId <= 0 && !empty($currencies)) {
            $mainCurrencyId = (int) array_key_first($currencies);
        }

        return [
            'currency_map' => $currencyMap,
            'category_map' => $categoryMap,
            'payment_map' => $paymentMap,
            'payer_map' => $payerMap,
            'cycle_map' => $cycleMap,
            'defaults' => [
                'currency_id' => $mainCurrencyId,
                'category_id' => $firstCategoryId,
                'payment_method_id' => $firstPaymentId,
                'payer_id' => $firstPayerId,
            ],
        ];
    }

    /**
     * @param array<string, mixed> $context
     */
    private static function insResolveCurrency(string $value, array $context, array &$messages, string &$status): ?int
    {
        $value = strtolower(trim($value));
        if ($value === '') {
            return null;
        }

        $id = $context['currency_map'][$value] ?? null;
        if ($id === null) {
            $messages[] = 'Währung „' . $value . '“ unbekannt.';
            $status = self::insEscalateStatus($status, 'error');
        }

        return $id !== null ? (int) $id : null;
    }

    /**
     * @param array<string, mixed> $context
     */
    private static function insResolveCycle(string $value, array $context, array &$messages, string &$status): ?int
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        $normalized = strtolower($value);
        if (isset(self::CYCLE_ALIASES[$normalized])) {
            $canonical = strtolower(self::CYCLE_ALIASES[$normalized]);
            if (isset($context['cycle_map'][$canonical])) {
                return (int) $context['cycle_map'][$canonical];
            }
        }

        if (isset($context['cycle_map'][$normalized])) {
            return (int) $context['cycle_map'][$normalized];
        }

        if (is_numeric($value) && isset($context['cycle_map'][(int) $value])) {
            return (int) $value;
        }

        $messages[] = 'Zyklus „' . $value . '“ unbekannt.';
        $status = self::insEscalateStatus($status, 'error');

        return null;
    }

    /**
     * @param array<string, mixed> $context
     */
    private static function insResolveCategory(string $value, array $context): ?int
    {
        $key = strtolower(trim($value));
        if ($key === '') {
            return null;
        }

        return isset($context['category_map'][$key]) ? (int) $context['category_map'][$key] : null;
    }

    /**
     * @param array<string, mixed> $context
     */
    private static function insResolvePaymentMethod(string $value, array $context): ?int
    {
        $key = strtolower(trim($value));
        if ($key === '') {
            return null;
        }

        return isset($context['payment_map'][$key]) ? (int) $context['payment_map'][$key] : null;
    }

    /**
     * @param array<string, mixed> $context
     */
    private static function insResolvePayer(string $value, array $context): ?int
    {
        $key = strtolower(trim($value));
        if ($key === '') {
            return null;
        }

        return isset($context['payer_map'][$key]) ? (int) $context['payer_map'][$key] : null;
    }

    private static function insResolveInsuranceType(
        SQLite3 $db,
        int $userId,
        string $groupName,
        string $typeName,
        array &$messages,
        string &$status
    ): ?int {
        $typeName = trim($typeName);
        if ($typeName === '') {
            return null;
        }

        $groupName = trim($groupName);
        $taxonomy = Ins_Repository::insLoadTaxonomy($db, $userId);

        foreach ($taxonomy as $group) {
            $gName = strtolower(trim((string) ($group['name'] ?? '')));
            if ($groupName !== '' && $gName !== strtolower($groupName)) {
                continue;
            }
            foreach ($group['types'] ?? [] as $type) {
                if (strtolower(trim((string) ($type['name'] ?? ''))) === strtolower($typeName)) {
                    return (int) $type['id'];
                }
            }
        }

        if ($groupName !== '') {
            foreach ($taxonomy as $group) {
                foreach ($group['types'] ?? [] as $type) {
                    if (strtolower(trim((string) ($type['name'] ?? ''))) === strtolower($typeName)) {
                        $messages[] = 'Versicherungsart gefunden, Gruppe weicht ab.';
                        $status = self::insEscalateStatus($status, 'warning');

                        return (int) $type['id'];
                    }
                }
            }
        }

        $messages[] = 'Versicherungsart „' . $typeName . '“ nicht gefunden.';
        $status = self::insEscalateStatus($status, 'warning');

        return null;
    }

    private static function insPolicyExists(SQLite3 $db, int $userId, string $policyNumber): bool
    {
        $stmt = $db->prepare("
            SELECT COUNT(*) FROM assure_insurance_details d
            INNER JOIN subscriptions s ON s.id = d.subscription_id
            WHERE s.user_id = :userId AND d.policy_number = :policy
        ");
        $stmt->bindValue(':userId', $userId, SQLITE3_INTEGER);
        $stmt->bindValue(':policy', $policyNumber, SQLITE3_TEXT);

        return (int) $stmt->execute()->fetchArray(SQLITE3_NUM)[0] > 0;
    }

    private static function insNamePriceExists(SQLite3 $db, int $userId, string $name, float $price): bool
    {
        $stmt = $db->prepare("
            SELECT COUNT(*) FROM subscriptions
            WHERE user_id = :userId AND name = :name AND ABS(price - :price) < 0.001
        ");
        $stmt->bindValue(':userId', $userId, SQLITE3_INTEGER);
        $stmt->bindValue(':name', $name, SQLITE3_TEXT);
        $stmt->bindValue(':price', $price, SQLITE3_FLOAT);

        return (int) $stmt->execute()->fetchArray(SQLITE3_NUM)[0] > 0;
    }

    /**
     * @param array<string, mixed> $subscription
     */
    private static function insInsertSubscription(SQLite3 $db, int $userId, array $subscription): int
    {
        $stmt = $db->prepare("
            INSERT INTO subscriptions (
                name, logo, price, currency_id, next_payment, cycle, frequency, notes,
                payment_method_id, payer_user_id, category_id, notify, inactive, url,
                notify_days_before, user_id, cancellation_date, replacement_subscription_id,
                auto_renew, start_date
            ) VALUES (
                :name, :logo, :price, :currencyId, :nextPayment, :cycle, :frequency, :notes,
                :paymentMethodId, :payerUserId, :categoryId, :notify, :inactive, :url,
                :notifyDaysBefore, :userId, :cancellationDate, :replacement_subscription_id,
                :autoRenew, :startDate
            )
        ");

        $stmt->bindValue(':name', (string) $subscription['name'], SQLITE3_TEXT);
        $stmt->bindValue(':logo', (string) ($subscription['logo'] ?? ''), SQLITE3_TEXT);
        $stmt->bindValue(':price', (float) $subscription['price'], SQLITE3_FLOAT);
        $stmt->bindValue(':currencyId', (int) $subscription['currency_id'], SQLITE3_INTEGER);
        $stmt->bindValue(':nextPayment', (string) $subscription['next_payment'], SQLITE3_TEXT);
        $stmt->bindValue(':cycle', (int) $subscription['cycle'], SQLITE3_INTEGER);
        $stmt->bindValue(':frequency', (int) $subscription['frequency'], SQLITE3_INTEGER);
        $stmt->bindValue(':notes', (string) ($subscription['notes'] ?? ''), SQLITE3_TEXT);
        $stmt->bindValue(':paymentMethodId', (int) $subscription['payment_method_id'], SQLITE3_INTEGER);
        $stmt->bindValue(':payerUserId', (int) $subscription['payer_user_id'], SQLITE3_INTEGER);
        $stmt->bindValue(':categoryId', (int) $subscription['category_id'], SQLITE3_INTEGER);
        $stmt->bindValue(':notify', (int) $subscription['notify'], SQLITE3_INTEGER);
        $stmt->bindValue(':inactive', (int) $subscription['inactive'], SQLITE3_INTEGER);
        $stmt->bindValue(':url', (string) ($subscription['url'] ?? ''), SQLITE3_TEXT);
        $stmt->bindValue(':notifyDaysBefore', (int) ($subscription['notify_days_before'] ?? 1), SQLITE3_INTEGER);
        $stmt->bindValue(':userId', $userId, SQLITE3_INTEGER);
        $cancellation = $subscription['cancellation_date'] ?? null;
        if ($cancellation === null || $cancellation === '') {
            $stmt->bindValue(':cancellationDate', null, SQLITE3_NULL);
        } else {
            $stmt->bindValue(':cancellationDate', (string) $cancellation, SQLITE3_TEXT);
        }
        $replacement = $subscription['replacement_subscription_id'] ?? null;
        if ($replacement === null) {
            $stmt->bindValue(':replacement_subscription_id', null, SQLITE3_NULL);
        } else {
            $stmt->bindValue(':replacement_subscription_id', (int) $replacement, SQLITE3_INTEGER);
        }
        $stmt->bindValue(':autoRenew', (int) ($subscription['auto_renew'] ?? 0), SQLITE3_INTEGER);
        $stmt->bindValue(':startDate', (string) ($subscription['start_date'] ?? ''), SQLITE3_TEXT);

        if (!$stmt->execute()) {
            return 0;
        }

        return (int) $db->lastInsertRowID();
    }

    private static function insNormalizeHeader(string $header): string
    {
        $header = trim($header);
        $header = preg_replace('/^\xEF\xBB\xBF/', '', $header) ?? $header;
        $header = strtolower($header);
        $header = str_replace([' ', '-', '.'], '_', $header);
        $header = preg_replace('/_+/', '_', $header) ?? $header;

        return self::HEADER_ALIASES[$header] ?? $header;
    }

    private static function insStripBom(string $raw): string
    {
        if (str_starts_with($raw, "\xEF\xBB\xBF")) {
            return substr($raw, 3);
        }

        return $raw;
    }

    private static function insDetectDelimiter(string $firstLine): string
    {
        return substr_count($firstLine, ';') > substr_count($firstLine, ',') ? ';' : ',';
    }

    private static function insCsvEscape(string $value): string
    {
        if (strpbrk($value, "\";\n\r") !== false) {
            return '"' . str_replace('"', '""', $value) . '"';
        }

        return $value;
    }

    private static function insParseBool(string $value, bool $default): bool
    {
        $value = strtolower(trim($value));
        if ($value === '') {
            return $default;
        }

        return in_array($value, ['1', 'true', 'yes', 'ja', 'y', 'on'], true);
    }

    private static function insIsIsoDate(string $value): bool
    {
        $value = trim($value);
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return false;
        }

        $parts = explode('-', $value);

        return checkdate((int) $parts[1], (int) $parts[2], (int) $parts[0]);
    }

    private static function insNullableDate(string $value): ?string
    {
        $value = trim($value);

        return $value !== '' && self::insIsIsoDate($value) ? $value : null;
    }

    private static function insEscalateStatus(string $current, string $next): string
    {
        if ($current === 'error' || $next === 'error') {
            return 'error';
        }

        if ($current === 'warning' || $next === 'warning') {
            return 'warning';
        }

        return 'ok';
    }

    /**
     * @return array{success: bool, summary: array<string, int>, rows: array<int, array<string, mixed>>, batch_token: ?string, message: string}
     */
    private static function insPreviewError(string $message): array
    {
        return [
            'success' => false,
            'summary' => ['total' => 0, 'ok' => 0, 'warning' => 0, 'error' => 0, 'importable' => 0],
            'rows' => [],
            'batch_token' => null,
            'message' => $message,
        ];
    }
}
