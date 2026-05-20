<?php

require_once __DIR__ . '/ins_repository.php';
require_once __DIR__ . '/subscriptions_table_columns.php';

/**
 * Enriches subscription rows for table display and CSV export.
 */
class Ins_Subscriptions_Table_Data
{
    /**
     * @param list<array<string, mixed>> $subscriptions
     * @return array<int, array<string, mixed>>
     */
    public static function insInsuranceDetailsBySubscription(SQLite3 $db, array $subscriptions): array
    {
        if (!Ins_Repository::insSchemaReady($db) || $subscriptions === []) {
            return [];
        }

        $ids = [];
        foreach ($subscriptions as $row) {
            $id = (int) ($row['id'] ?? 0);
            if ($id > 0) {
                $ids[$id] = true;
            }
        }
        if ($ids === []) {
            return [];
        }

        $idList = implode(',', array_map('intval', array_keys($ids)));
        $result = $db->query(
            "SELECT * FROM assure_insurance_details WHERE subscription_id IN ({$idList})"
        );
        $map = [];
        while ($result && ($detail = $result->fetchArray(SQLITE3_ASSOC))) {
            $map[(int) $detail['subscription_id']] = $detail;
        }

        return $map;
    }

    /**
     * @return array<int, array{group_name: string, type_name: string, group_id: int}>
     */
    public static function insInsuranceTypeLookup(SQLite3 $db, int $userId): array
    {
        if (!Ins_Repository::insTaxonomyReady($db)) {
            return [];
        }

        $stmt = $db->prepare("
            SELECT t.id AS type_id, t.name AS type_name, t.group_id, g.name AS group_name
            FROM assure_insurance_types t
            INNER JOIN assure_insurance_groups g ON g.id = t.group_id
            WHERE t.active = 1 AND g.active = 1
              AND (t.user_id IS NULL OR t.user_id = :userId)
              AND (g.user_id IS NULL OR g.user_id = :userId)
        ");
        $stmt->bindValue(':userId', $userId, SQLITE3_INTEGER);
        $result = $stmt->execute();
        $lookup = [];
        while ($result && ($row = $result->fetchArray(SQLITE3_ASSOC))) {
            $lookup[(int) $row['type_id']] = [
                'group_name' => (string) ($row['group_name'] ?? ''),
                'type_name' => (string) ($row['type_name'] ?? ''),
                'group_id' => (int) ($row['group_id'] ?? 0),
            ];
        }

        return $lookup;
    }

    /**
     * @param list<array<string, mixed>> $subscriptions
     * @return list<array<string, mixed>>
     */
    public static function insBuildDisplayRows(
        SQLite3 $db,
        int $userId,
        array $subscriptions,
        array $categories,
        array $payment_methods,
        array $members,
        array $cycles,
        array $currencies,
        array $settings,
        int $mainCurrencyId
    ): array {
        require_once dirname(__DIR__) . '/list_subscriptions.php';

        $insuranceMap = self::insInsuranceDetailsBySubscription($db, $subscriptions);
        $typeLookup = self::insInsuranceTypeLookup($db, $userId);

        $formatter = new IntlDateFormatter(
            'de_DE',
            IntlDateFormatter::SHORT,
            IntlDateFormatter::NONE,
            null,
            null,
            'dd.MM.yyyy'
        );

        $rows = [];
        foreach ($subscriptions as $subscription) {
            if (!empty($subscription['inactive'])
                && ($settings['hideDisabledSubscriptions'] ?? '') === 'true'
                && !isset($_GET['state'])) {
                continue;
            }

            $id = (int) ($subscription['id'] ?? 0);
            $currencyId = (int) ($subscription['currency_id'] ?? 0);
            $cycle = (int) ($subscription['cycle'] ?? 0);
            $frequency = (int) ($subscription['frequency'] ?? 1);
            $rawPrice = (float) ($subscription['price'] ?? 0);
            $price = $rawPrice;
            $currencyCode = (string) ($currencies[$currencyId]['code'] ?? '');

            if (($settings['convertCurrency'] ?? '') === 'true' && $currencyId !== $mainCurrencyId) {
                $price = getPriceConverted($price, $currencyId, $db);
                $currencyCode = (string) ($currencies[$mainCurrencyId]['code'] ?? $currencyCode);
            }
            if (($settings['showMonthlyPrice'] ?? '') === 'true') {
                $price = getPricePerMonth($cycle, $frequency, $price);
            }

            $categoryId = (int) ($subscription['category_id'] ?? 0);
            $paymentId = (int) ($subscription['payment_method_id'] ?? 0);
            $payerId = $subscription['payer_user_id'] ?? null;
            $payerName = ($payerId !== null && $payerId !== '' && isset($members[$payerId]))
                ? (string) $members[$payerId]['name'] : '';

            $nextRaw = (string) ($subscription['next_payment'] ?? '');
            $nextDisplay = $nextRaw;
            $nextMonth = '';
            if ($nextRaw !== '') {
                $ts = strtotime($nextRaw);
                if ($ts !== false) {
                    $nextDisplay = $formatter->format($ts);
                    $nextMonth = date('Y-m', $ts);
                }
            }

            $isInsurance = !empty($subscription['is_insurance']);
            $details = $insuranceMap[$id] ?? null;
            $typeId = is_array($details) ? (int) ($details['insurance_type_id'] ?? 0) : 0;
            $groupName = '';
            $typeName = '';
            $groupId = 0;
            if ($typeId > 0 && isset($typeLookup[$typeId])) {
                $groupName = $typeLookup[$typeId]['group_name'];
                $typeName = $typeLookup[$typeId]['type_name'];
                $groupId = $typeLookup[$typeId]['group_id'];
            }

            $logoFile = (string) ($subscription['logo'] ?? '');
            $logoUrl = $logoFile !== '' ? 'images/uploads/logos/' . $logoFile : '';

            $notes = (string) ($subscription['notes'] ?? '');
            $notesShort = $notes;
            if (mb_strlen($notesShort) > 80) {
                $notesShort = mb_substr($notesShort, 0, 77) . '…';
            }

            $url = (string) ($subscription['url'] ?? '');

            $cells = [
                'logo' => $logoUrl,
                'name' => (string) ($subscription['name'] ?? ''),
                'price' => number_format($price, 2, ',', '.'),
                'currency' => $currencyCode,
                'cycle' => getBillingCycle($cycle, $frequency, $GLOBALS['i18n'] ?? []),
                'next_payment' => $nextDisplay,
                'category' => (string) ($categories[$categoryId]['name'] ?? ''),
                'payment_method' => (string) ($payment_methods[$paymentId]['name'] ?? ''),
                'payer' => $payerName,
                'inactive' => !empty($subscription['inactive']) ? 'Inaktiv' : 'Aktiv',
                'auto_renew' => !empty($subscription['auto_renew']) ? 'Automatisch' : 'Manuell',
                'notes' => $notesShort,
                'url' => $url !== '' ? $url : '',
                'is_insurance' => $isInsurance ? 'Ja' : 'Nein',
                'insurance_group' => $groupName,
                'insurance_type' => $typeName,
                'policy_number' => '',
                'insurer_name' => '',
                'contract_status' => '',
                'main_due_date' => '',
                'insurance_sum' => '',
                'deductible' => '',
                'claims_hotline' => '',
                'broker_name' => '',
                'tariff_name' => '',
                'policyholder' => '',
                'insured_persons' => '',
                'beneficiary' => '',
                'start_date' => '',
                'end_date' => '',
                'minimum_term_months' => '',
                'cancellation_period' => '',
                'cancellation_status' => '',
                'auto_contract_renewal' => '',
                'renewal_period_months' => '',
                'payment_method_text' => '',
                'bank_account_label' => '',
                'contact_name' => '',
                'contact_phone' => '',
                'contact_email' => '',
                'portal_url' => '',
            ];

            $insuranceSumNumeric = 0.0;
            $deductibleNumeric = 0.0;

            if ($isInsurance && is_array($details)) {
                $cells['policy_number'] = (string) ($details['policy_number'] ?? '');
                $cells['insurer_name'] = (string) ($details['insurer_name'] ?? '');
                $cells['contract_status'] = (string) ($details['contract_status'] ?? '');
                $cells['main_due_date'] = (string) ($details['main_due_date'] ?? '');
                $cells['claims_hotline'] = (string) ($details['claims_hotline'] ?? '');
                $cells['broker_name'] = (string) ($details['broker_name'] ?? '');
                $cells['tariff_name'] = (string) ($details['tariff_name'] ?? '');
                $cells['policyholder'] = (string) ($details['policyholder'] ?? '');
                $cells['insured_persons'] = (string) ($details['insured_persons'] ?? '');
                $cells['beneficiary'] = (string) ($details['beneficiary'] ?? '');
                $cells['start_date'] = (string) ($details['start_date'] ?? '');
                $cells['end_date'] = (string) ($details['end_date'] ?? '');
                $cells['minimum_term_months'] = isset($details['minimum_term_months'])
                    ? (string) (int) $details['minimum_term_months'] : '';
                $cells['cancellation_status'] = (string) ($details['cancellation_status'] ?? '');
                $cells['payment_method_text'] = (string) ($details['payment_method_text'] ?? '');
                $cells['bank_account_label'] = (string) ($details['bank_account_label'] ?? '');
                $cells['contact_name'] = (string) ($details['contact_name'] ?? '');
                $cells['contact_phone'] = (string) ($details['contact_phone'] ?? '');
                $cells['contact_email'] = (string) ($details['contact_email'] ?? '');
                $portal = (string) ($details['portal_url'] ?? '');
                $cells['portal_url'] = $portal;

                $cells['cancellation_period'] = self::insFormatCancellationPeriod($details);
                $cells['auto_contract_renewal'] = !empty($details['auto_contract_renewal']) ? 'Ja' : 'Nein';
                $cells['renewal_period_months'] = isset($details['renewal_period_months'])
                    ? (string) (int) $details['renewal_period_months'] : '';

                $insuranceSumNumeric = self::insParseMoney((string) ($details['insurance_sum'] ?? ''));
                $deductibleNumeric = self::insParseMoney((string) ($details['deductible'] ?? ''));
                if ($insuranceSumNumeric > 0) {
                    $cells['insurance_sum'] = number_format($insuranceSumNumeric, 2, ',', '.');
                }
                if ($deductibleNumeric > 0) {
                    $cells['deductible'] = number_format($deductibleNumeric, 2, ',', '.');
                }
            }

            $startYear = '';
            if ($cells['start_date'] !== '') {
                $y = substr($cells['start_date'], 0, 4);
                if (preg_match('/^\d{4}$/', $y)) {
                    $startYear = $y;
                }
            }
            $endYear = '';
            if ($cells['end_date'] !== '') {
                $y = substr($cells['end_date'], 0, 4);
                if (preg_match('/^\d{4}$/', $y)) {
                    $endYear = $y;
                }
            }

            $groupLabels = [
                'name' => self::insGroupLabel((string) $cells['name']),
                'cycle' => self::insGroupLabel((string) $cells['cycle']),
                'category' => self::insGroupLabel((string) $cells['category']),
                'payer' => self::insGroupLabel($payerName),
                'payment_method' => self::insGroupLabel((string) $cells['payment_method']),
                'inactive' => self::insGroupLabel((string) $cells['inactive']),
                'auto_renew' => self::insGroupLabel((string) $cells['auto_renew']),
                'currency' => self::insGroupLabel($currencyCode),
                'is_insurance' => self::insGroupLabel((string) $cells['is_insurance']),
                'insurance_group' => self::insGroupLabel($groupName),
                'insurance_type' => self::insGroupLabel($typeName),
                'insurer_name' => self::insGroupLabel((string) $cells['insurer_name']),
                'policy_number' => self::insGroupLabel((string) $cells['policy_number']),
                'contract_status' => self::insGroupLabel((string) $cells['contract_status']),
                'main_due_date' => self::insGroupLabel((string) $cells['main_due_date']),
                'claims_hotline' => self::insGroupLabel((string) $cells['claims_hotline']),
                'broker_name' => self::insGroupLabel((string) $cells['broker_name']),
                'tariff_name' => self::insGroupLabel((string) $cells['tariff_name']),
                'policyholder' => self::insGroupLabel((string) $cells['policyholder']),
                'insured_persons' => self::insGroupLabel((string) $cells['insured_persons']),
                'beneficiary' => self::insGroupLabel((string) $cells['beneficiary']),
                'start_year' => self::insGroupLabel($startYear),
                'end_year' => self::insGroupLabel($endYear),
                'minimum_term_months' => self::insGroupLabel((string) $cells['minimum_term_months']),
                'cancellation_period' => self::insGroupLabel((string) $cells['cancellation_period']),
                'cancellation_status' => self::insGroupLabel((string) $cells['cancellation_status']),
                'auto_contract_renewal' => self::insGroupLabel((string) $cells['auto_contract_renewal']),
                'renewal_period_months' => self::insGroupLabel((string) $cells['renewal_period_months']),
                'payment_method_text' => self::insGroupLabel((string) $cells['payment_method_text']),
                'bank_account_label' => self::insGroupLabel((string) $cells['bank_account_label']),
                'contact_name' => self::insGroupLabel((string) $cells['contact_name']),
                'contact_phone' => self::insGroupLabel((string) $cells['contact_phone']),
                'contact_email' => self::insGroupLabel((string) $cells['contact_email']),
                'next_payment_month' => self::insGroupLabel($nextMonth),
            ];

            $rows[] = [
                'id' => $id,
                'name' => $cells['name'],
                'logo_url' => $logoUrl,
                'policy_number' => $cells['policy_number'],
                'cells' => $cells,
                'group_labels' => $groupLabels,
                'category_id' => $categoryId,
                'payment_method_id' => $paymentId,
                'insurance_type_id' => $typeId,
                'insurance_group_id' => $groupId,
                'is_insurance' => $isInsurance ? 1 : 0,
                'price_numeric' => $price,
                'insurance_sum_numeric' => $insuranceSumNumeric,
                'deductible_numeric' => $deductibleNumeric,
                'currency_code' => $currencyCode,
            ];
        }

        return $rows;
    }

    /**
     * @param array<string, mixed> $details
     */
    private static function insGroupLabel(string $value): string
    {
        $value = trim($value);

        return $value !== '' ? $value : '(leer)';
    }

    /**
     * @param array<string, mixed> $details
     */
    private static function insFormatCancellationPeriod(array $details): string
    {
        $value = (int) ($details['cancellation_period_value'] ?? 0);
        $unit = (string) ($details['cancellation_period_unit'] ?? '');
        if ($value <= 0) {
            return '';
        }
        $unitLabel = match ($unit) {
            'days' => 'Tage',
            'weeks' => 'Wochen',
            'months' => 'Monate',
            'years' => 'Jahre',
            default => $unit,
        };

        return $value . ' ' . $unitLabel;
    }

    private static function insParseMoney(string $value): float
    {
        $value = trim($value);
        if ($value === '') {
            return 0.0;
        }
        $normalized = str_replace([' ', '.'], ['', ''], $value);
        $normalized = str_replace(',', '.', $normalized);

        return (float) $normalized;
    }

    /**
     * @param list<array<string, mixed>> $displayRows
     * @param list<array<string, mixed>>|null $blocks
     * @param list<string> $columnIds
     */
    public static function insRowsToCsv(array $displayRows, array $columnIds, ?array $blocks = null): string
    {
        $byId = Ins_Subscriptions_Table_Columns::insById();
        $headers = [];
        foreach ($columnIds as $cid) {
            if ($cid === 'logo') {
                continue;
            }
            $headers[] = $byId[$cid]['label'] ?? $cid;
        }
        $lines = [implode(';', array_map([self::class, 'insCsvEscape'], $headers))];

        $emitRow = static function (array $row) use ($columnIds, &$lines): void {
            $values = [];
            foreach ($columnIds as $cid) {
                if ($cid === 'logo') {
                    $values[] = self::insCsvEscape((string) ($row['logo_url'] ?? ''));
                    continue;
                }
                $values[] = self::insCsvEscape((string) ($row['cells'][$cid] ?? ''));
            }
            $lines[] = implode(';', $values);
        };

        if ($blocks !== null) {
            foreach ($blocks as $block) {
                $type = $block['type'] ?? '';
                if ($type === 'group_header') {
                    $lines[] = self::insCsvEscape('--- ' . ($block['label'] ?? '') . ' ---');
                    continue;
                }
                if ($type === 'group_sum') {
                    $count = (int) ($block['count'] ?? 0);
                    $sumParts = ['Summe', $count . ' Verträge'];
                    foreach ($block['sums'] ?? [] as $colId => $sum) {
                        $total = number_format((float) ($sum['total'] ?? 0), 2, ',', '.');
                        $cur = (string) ($sum['currency'] ?? '');
                        $hint = !empty($sum['mixed_currency']) ? ' (gemischte Währungen)' : ($cur !== '' ? ' ' . $cur : '');
                        $sumParts[] = ($byId[$colId]['label'] ?? $colId) . ': ' . $total . $hint;
                    }
                    $lines[] = self::insCsvEscape(implode(' | ', $sumParts));
                    continue;
                }
                if ($type === 'data' && isset($block['row'])) {
                    $emitRow($block['row']);
                }
            }
        } else {
            foreach ($displayRows as $row) {
                $emitRow($row);
            }
        }

        return "\xEF\xBB\xBF" . implode("\n", $lines) . "\n";
    }

    private static function insCsvEscape(string $value): string
    {
        if (strpbrk($value, "\";\n\r") !== false) {
            return '"' . str_replace('"', '""', $value) . '"';
        }

        return $value;
    }
}
