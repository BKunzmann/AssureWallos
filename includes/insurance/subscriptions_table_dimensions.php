<?php

/**
 * Filter, sort and group dimensions for subscriptions table (AssureWallos).
 */
class Ins_Subscriptions_Table_Dimensions
{
    /**
     * @return array<string, array{
     *   id: string,
     *   label: string,
     *   capabilities: list<string>,
     *   column_id: ?string,
     *   sort_sql: ?string,
     *   filter_type: ?string
     * }>
     */
    public static function insCatalog(): array
    {
        static $catalog = null;
        if ($catalog !== null) {
            return $catalog;
        }

        $items = [
            ['id' => 'name', 'label' => 'Name', 'capabilities' => ['sort', 'group', 'filter'], 'column_id' => 'name', 'sort_sql' => 's.name', 'filter_type' => 'q'],
            ['id' => 'price', 'label' => 'Preis', 'capabilities' => ['sort'], 'column_id' => 'price', 'sort_sql' => 's.price', 'filter_type' => null],
            ['id' => 'cycle', 'label' => 'Zyklus', 'capabilities' => ['sort', 'group'], 'column_id' => 'cycle', 'sort_sql' => 's.cycle', 'filter_type' => null],
            ['id' => 'next_payment', 'label' => 'Nächste Zahlung', 'capabilities' => ['sort'], 'column_id' => 'next_payment', 'sort_sql' => 's.next_payment', 'filter_type' => null],
            ['id' => 'next_payment_month', 'label' => 'Fälligkeitsmonat', 'capabilities' => ['group', 'filter'], 'column_id' => 'next_payment', 'sort_sql' => null, 'filter_type' => 'next_payment_month'],
            ['id' => 'category', 'label' => 'Kategorie', 'capabilities' => ['sort', 'group', 'filter'], 'column_id' => 'category', 'sort_sql' => 's.category_id', 'filter_type' => 'categories'],
            ['id' => 'payer', 'label' => 'Zahler', 'capabilities' => ['sort', 'group', 'filter'], 'column_id' => 'payer', 'sort_sql' => 's.payer_user_id', 'filter_type' => 'members'],
            ['id' => 'payment_method', 'label' => 'Zahlungsart', 'capabilities' => ['sort', 'group', 'filter'], 'column_id' => 'payment_method', 'sort_sql' => 's.payment_method_id', 'filter_type' => 'payments'],
            ['id' => 'inactive', 'label' => 'Status', 'capabilities' => ['sort', 'group', 'filter'], 'column_id' => 'inactive', 'sort_sql' => 's.inactive', 'filter_type' => 'state'],
            ['id' => 'auto_renew', 'label' => 'Verlängerung', 'capabilities' => ['sort', 'group', 'filter'], 'column_id' => 'auto_renew', 'sort_sql' => 's.auto_renew', 'filter_type' => 'renewalType'],
            ['id' => 'currency', 'label' => 'Währung', 'capabilities' => ['group'], 'column_id' => 'currency', 'sort_sql' => null, 'filter_type' => null],
            ['id' => 'is_insurance', 'label' => 'Versicherung', 'capabilities' => ['sort', 'group', 'filter'], 'column_id' => 'is_insurance', 'sort_sql' => 's.is_insurance', 'filter_type' => 'is_insurance'],
            ['id' => 'insurance_group', 'label' => 'Versicherungsgruppe', 'capabilities' => ['sort', 'group', 'filter'], 'column_id' => 'insurance_group', 'sort_sql' => 'd.insurance_type_id', 'filter_type' => 'insurance_groups'],
            ['id' => 'insurance_type', 'label' => 'Versicherungsart', 'capabilities' => ['sort', 'group', 'filter'], 'column_id' => 'insurance_type', 'sort_sql' => 'd.insurance_type_id', 'filter_type' => 'insurance_types'],
            ['id' => 'insurer_name', 'label' => 'Versicherer', 'capabilities' => ['sort', 'group', 'filter'], 'column_id' => 'insurer_name', 'sort_sql' => 'd.insurer_name', 'filter_type' => 'q'],
            ['id' => 'policy_number', 'label' => 'Versicherungsnr.', 'capabilities' => ['sort', 'group', 'filter'], 'column_id' => 'policy_number', 'sort_sql' => 'd.policy_number', 'filter_type' => 'q'],
            ['id' => 'contract_status', 'label' => 'Vertragsstatus', 'capabilities' => ['sort', 'group', 'filter'], 'column_id' => 'contract_status', 'sort_sql' => 'd.contract_status', 'filter_type' => 'contract_status'],
            ['id' => 'main_due_date', 'label' => 'Hauptfälligkeit', 'capabilities' => ['sort', 'group'], 'column_id' => 'main_due_date', 'sort_sql' => 'd.main_due_date', 'filter_type' => null],
            ['id' => 'insurance_sum', 'label' => 'Versicherungssumme', 'capabilities' => ['sort'], 'column_id' => 'insurance_sum', 'sort_sql' => 'd.insurance_sum', 'filter_type' => null],
            ['id' => 'deductible', 'label' => 'Selbstbehalt', 'capabilities' => ['sort'], 'column_id' => 'deductible', 'sort_sql' => 'd.deductible', 'filter_type' => null],
            ['id' => 'claims_hotline', 'label' => 'Schaden-Hotline', 'capabilities' => ['sort', 'group'], 'column_id' => 'claims_hotline', 'sort_sql' => 'd.claims_hotline', 'filter_type' => null],
            ['id' => 'broker_name', 'label' => 'Vermittler', 'capabilities' => ['sort', 'group', 'filter'], 'column_id' => 'broker_name', 'sort_sql' => 'd.broker_name', 'filter_type' => 'q'],
            ['id' => 'tariff_name', 'label' => 'Tarif', 'capabilities' => ['sort', 'group', 'filter'], 'column_id' => 'tariff_name', 'sort_sql' => 'd.tariff_name', 'filter_type' => 'q'],
            ['id' => 'policyholder', 'label' => 'Versicherungsnehmer', 'capabilities' => ['sort', 'group'], 'column_id' => 'policyholder', 'sort_sql' => 'd.policyholder', 'filter_type' => null],
            ['id' => 'insured_persons', 'label' => 'Versicherte Personen', 'capabilities' => ['sort', 'group'], 'column_id' => 'insured_persons', 'sort_sql' => 'd.insured_persons', 'filter_type' => null],
            ['id' => 'beneficiary', 'label' => 'Begünstigte', 'capabilities' => ['sort', 'group'], 'column_id' => 'beneficiary', 'sort_sql' => 'd.beneficiary', 'filter_type' => null],
            ['id' => 'start_date', 'label' => 'Vertragsbeginn', 'capabilities' => ['sort', 'group'], 'column_id' => 'start_date', 'sort_sql' => 'd.start_date', 'filter_type' => null],
            ['id' => 'start_year', 'label' => 'Beginn-Jahr', 'capabilities' => ['group'], 'column_id' => 'start_date', 'sort_sql' => null, 'filter_type' => null],
            ['id' => 'end_date', 'label' => 'Vertragsende', 'capabilities' => ['sort', 'group'], 'column_id' => 'end_date', 'sort_sql' => 'd.end_date', 'filter_type' => null],
            ['id' => 'end_year', 'label' => 'Ende-Jahr', 'capabilities' => ['group'], 'column_id' => 'end_date', 'sort_sql' => null, 'filter_type' => null],
            ['id' => 'minimum_term_months', 'label' => 'Mindestlaufzeit', 'capabilities' => ['sort', 'group'], 'column_id' => 'minimum_term_months', 'sort_sql' => 'd.minimum_term_months', 'filter_type' => null],
            ['id' => 'cancellation_period', 'label' => 'Kündigungsfrist', 'capabilities' => ['group'], 'column_id' => 'cancellation_period', 'sort_sql' => null, 'filter_type' => null],
            ['id' => 'cancellation_status', 'label' => 'Kündigungsstatus', 'capabilities' => ['sort', 'group', 'filter'], 'column_id' => 'cancellation_status', 'sort_sql' => 'd.cancellation_status', 'filter_type' => 'cancellation_status'],
            ['id' => 'auto_contract_renewal', 'label' => 'Vertrags-Verlängerung', 'capabilities' => ['sort', 'group'], 'column_id' => 'auto_contract_renewal', 'sort_sql' => 'd.auto_contract_renewal', 'filter_type' => null],
            ['id' => 'renewal_period_months', 'label' => 'Verlängerungszeitraum', 'capabilities' => ['sort', 'group'], 'column_id' => 'renewal_period_months', 'sort_sql' => 'd.renewal_period_months', 'filter_type' => null],
            ['id' => 'payment_method_text', 'label' => 'Zahlungsweg', 'capabilities' => ['sort', 'group'], 'column_id' => 'payment_method_text', 'sort_sql' => 'd.payment_method_text', 'filter_type' => null],
            ['id' => 'bank_account_label', 'label' => 'Konto', 'capabilities' => ['sort', 'group'], 'column_id' => 'bank_account_label', 'sort_sql' => 'd.bank_account_label', 'filter_type' => null],
            ['id' => 'contact_name', 'label' => 'Ansprechpartner', 'capabilities' => ['sort', 'group'], 'column_id' => 'contact_name', 'sort_sql' => 'd.contact_name', 'filter_type' => null],
            ['id' => 'contact_phone', 'label' => 'Telefon', 'capabilities' => ['sort', 'group'], 'column_id' => 'contact_phone', 'sort_sql' => 'd.contact_phone', 'filter_type' => null],
            ['id' => 'contact_email', 'label' => 'E-Mail', 'capabilities' => ['sort', 'group'], 'column_id' => 'contact_email', 'sort_sql' => 'd.contact_email', 'filter_type' => null],
            ['id' => 'none', 'label' => 'Keine Gruppierung', 'capabilities' => ['group'], 'column_id' => null, 'sort_sql' => null, 'filter_type' => null],
        ];

        $catalog = [];
        foreach ($items as $item) {
            $catalog[$item['id']] = $item;
        }

        return $catalog;
    }

    public static function insHasCapability(string $id, string $capability): bool
    {
        $entry = self::insCatalog()[$id] ?? null;
        if ($entry === null) {
            return false;
        }

        return in_array($capability, $entry['capabilities'], true);
    }

    public static function insValidateSortKey(string $key): ?string
    {
        if ($key === 'alphanumeric') {
            return 'alphanumeric';
        }
        if ($key === 'renewal_type') {
            return 'auto_renew';
        }
        if ($key === 'id') {
            return 'id';
        }
        if (self::insHasCapability($key, 'sort')) {
            return $key;
        }
        foreach (self::insCatalog() as $id => $entry) {
            if (($entry['column_id'] ?? null) === $key && self::insHasCapability($id, 'sort')) {
                return $id;
            }
        }

        return null;
    }

    public static function insValidateGroupKey(string $key): ?string
    {
        if ($key === '' || $key === 'none') {
            return 'none';
        }
        if (self::insHasCapability($key, 'group')) {
            return $key;
        }

        return null;
    }

    /**
     * Kompakte Liste (Toolbar-Sort-Menü).
     *
     * @return list<array{id: string, label: string}>
     */
    public static function insSortOptionsForUi(): array
    {
        return self::insOptionsForCapability('sort', ['name', 'price', 'next_payment', 'category', 'payer']);
    }

    /**
     * Voller Katalog für Presets / Erweitert-Dialog.
     *
     * @return list<array{id: string, label: string}>
     */
    public static function insSortOptionsAll(): array
    {
        return self::insOptionsForCapability('sort');
    }

    /**
     * @return list<array{id: string, label: string}>
     */
    public static function insGroupOptionsForUi(string $level = 'primary'): array
    {
        $preferred = $level === 'secondary'
            ? ['contract_status', 'insurance_type', 'inactive', 'payer', 'cancellation_status', 'next_payment_month', 'auto_renew']
            : ['none', 'category', 'insurance_group', 'insurance_type', 'insurer_name', 'contract_status', 'payer'];

        return self::insOptionsForCapability('group', $preferred, $level !== 'secondary');
    }

    /**
     * @return list<array{id: string, label: string}>
     */
    public static function insGroupOptionsAll(string $level = 'primary'): array
    {
        return self::insOptionsForCapability('group', [], $level !== 'secondary');
    }

    /**
     * @param list<string> $preferredFirst
     * @return list<array{id: string, label: string}>
     */
    private static function insOptionsForCapability(string $capability, array $preferredFirst = [], bool $includeNoneFirst = false): array
    {
        $catalog = self::insCatalog();
        $byId = [];
        foreach ($catalog as $id => $entry) {
            if ($id === 'none' || !self::insHasCapability($id, $capability)) {
                continue;
            }
            $byId[$id] = ['id' => $id, 'label' => $entry['label']];
        }

        $ordered = [];
        if ($includeNoneFirst && isset($byId['none'])) {
            $ordered[] = $byId['none'];
            unset($byId['none']);
        }
        foreach ($preferredFirst as $id) {
            if (isset($byId[$id])) {
                $ordered[] = $byId[$id];
                unset($byId[$id]);
            }
        }
        $rest = array_values($byId);
        usort($rest, static fn ($a, $b) => strcasecmp($a['label'], $b['label']));
        foreach ($rest as $item) {
            $ordered[] = $item;
        }

        return $ordered;
    }

    /**
     * @return list<string>
     */
    public static function insSummableColumnIds(): array
    {
        return ['price', 'insurance_sum', 'deductible'];
    }

    public static function insSortSqlForKey(string $sortKey): ?string
    {
        if ($sortKey === 'alphanumeric' || $sortKey === 'id') {
            return $sortKey === 'id' ? 's.id' : 's.name';
        }
        $entry = self::insCatalog()[$sortKey] ?? null;

        return $entry['sort_sql'] ?? null;
    }

    public static function insColumnToSortKey(string $columnId): ?string
    {
        foreach (self::insCatalog() as $id => $entry) {
            if (($entry['column_id'] ?? null) === $columnId && self::insHasCapability($id, 'sort')) {
                return $id;
            }
        }

        return null;
    }

    public static function insColumnToGroupKey(string $columnId): ?string
    {
        foreach (self::insCatalog() as $id => $entry) {
            if (($entry['column_id'] ?? null) === $columnId && self::insHasCapability($id, 'group')) {
                return $id;
            }
            if ($id === $columnId && self::insHasCapability($id, 'group')) {
                return $id;
            }
        }

        return null;
    }

    public static function insNeedsInsuranceJoin(array $getParams, ?string $sortKey = null, ?string $group = null, ?string $group2 = null): bool
    {
        $keys = ['is_insurance', 'insurance_groups', 'insurance_types', 'contract_status', 'cancellation_status', 'q'];
        foreach ($keys as $k) {
            if (!empty($getParams[$k])) {
                return true;
            }
        }
        foreach ([$sortKey, $group, $group2] as $dim) {
            if ($dim === null || $dim === '' || $dim === 'none') {
                continue;
            }
            $entry = self::insCatalog()[$dim] ?? null;
            if ($entry !== null && str_starts_with((string) ($entry['sort_sql'] ?? ''), 'd.')) {
                return true;
            }
        }

        return false;
    }
}
