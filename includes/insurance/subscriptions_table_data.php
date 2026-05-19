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
     * @return array<int, array{group_name: string, type_name: string}>
     */
    public static function insInsuranceTypeLookup(SQLite3 $db, int $userId): array
    {
        if (!Ins_Repository::insTaxonomyReady($db)) {
            return [];
        }

        $stmt = $db->prepare("
            SELECT t.id AS type_id, t.name AS type_name, g.name AS group_name
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
            'en',
            IntlDateFormatter::SHORT,
            IntlDateFormatter::NONE,
            null,
            null,
            'MMM d, yyyy'
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
            $price = (float) ($subscription['price'] ?? 0);

            if (($settings['convertCurrency'] ?? '') === 'true' && $currencyId !== $mainCurrencyId) {
                $price = getPriceConverted($price, $currencyId, $db);
            }
            if (($settings['showMonthlyPrice'] ?? '') === 'true') {
                $price = getPricePerMonth($cycle, $frequency, $price);
            }

            $categoryId = (int) ($subscription['category_id'] ?? 0);
            $paymentId = (int) ($subscription['payment_method_id'] ?? 0);
            $payerId = $subscription['payer_user_id'] ?? null;

            $nextRaw = (string) ($subscription['next_payment'] ?? '');
            $nextDisplay = $nextRaw;
            if ($nextRaw !== '') {
                $ts = strtotime($nextRaw);
                if ($ts !== false) {
                    $nextDisplay = $formatter->format($ts);
                }
            }

            $isInsurance = !empty($subscription['is_insurance']);
            $details = $insuranceMap[$id] ?? null;
            $typeId = is_array($details) ? (int) ($details['insurance_type_id'] ?? 0) : 0;

            $logoFile = (string) ($subscription['logo'] ?? '');
            $logoUrl = $logoFile !== '' ? 'images/uploads/logos/' . $logoFile : '';

            $cells = [
                'logo' => $logoUrl,
                'name' => (string) ($subscription['name'] ?? ''),
                'price' => number_format($price, 2, ',', '.'),
                'currency' => (string) ($currencies[$currencyId]['code'] ?? ''),
                'cycle' => getBillingCycle($cycle, $frequency, $GLOBALS['i18n'] ?? []),
                'next_payment' => $nextDisplay,
                'category' => (string) ($categories[$categoryId]['name'] ?? ''),
                'payment_method' => (string) ($payment_methods[$paymentId]['name'] ?? ''),
                'payer' => ($payerId !== null && $payerId !== '' && isset($members[$payerId]))
                    ? (string) $members[$payerId]['name'] : '',
                'inactive' => !empty($subscription['inactive']) ? 'Inaktiv' : 'Aktiv',
                'auto_renew' => !empty($subscription['auto_renew']) ? 'Automatisch' : 'Manuell',
                'is_insurance' => $isInsurance ? 'Ja' : 'Nein',
                'insurance_group' => '',
                'insurance_type' => '',
                'policy_number' => '',
                'insurer_name' => '',
                'contract_status' => '',
                'main_due_date' => '',
            ];

            if ($isInsurance && is_array($details)) {
                $cells['policy_number'] = (string) ($details['policy_number'] ?? '');
                $cells['insurer_name'] = (string) ($details['insurer_name'] ?? '');
                $cells['contract_status'] = (string) ($details['contract_status'] ?? '');
                $cells['main_due_date'] = (string) ($details['main_due_date'] ?? '');
                if ($typeId > 0 && isset($typeLookup[$typeId])) {
                    $cells['insurance_group'] = $typeLookup[$typeId]['group_name'];
                    $cells['insurance_type'] = $typeLookup[$typeId]['type_name'];
                }
            }

            $rows[] = [
                'id' => $id,
                'name' => $cells['name'],
                'logo_url' => $logoUrl,
                'policy_number' => $cells['policy_number'],
                'cells' => $cells,
                'category_id' => $categoryId,
                'payment_method_id' => $paymentId,
                'insurance_type_id' => $typeId,
                'is_insurance' => $isInsurance ? 1 : 0,
            ];
        }

        return $rows;
    }

    /**
     * @param list<array<string, mixed>> $displayRows
     * @param list<string> $columnIds
     */
    public static function insRowsToCsv(array $displayRows, array $columnIds): string
    {
        $byId = Ins_Subscriptions_Table_Columns::insById();
        $headers = [];
        foreach ($columnIds as $cid) {
            $headers[] = $byId[$cid]['label'] ?? $cid;
        }
        $lines = [implode(';', array_map([self::class, 'insCsvEscape'], $headers))];

        foreach ($displayRows as $row) {
            $values = [];
            foreach ($columnIds as $cid) {
                $values[] = self::insCsvEscape((string) ($row['cells'][$cid] ?? ''));
            }
            $lines[] = implode(';', $values);
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
