<?php

/**
 * Column registry for subscriptions table view (AssureWallos).
 */
class Ins_Subscriptions_Table_Columns
{
    /**
     * @return list<array{
     *   id: string,
     *   label: string,
     *   group: string,
     *   default: bool,
     *   export: bool,
     *   sortable: bool,
     *   groupable: bool,
     *   summable: bool,
     *   sort_key: ?string,
     *   group_key: ?string
     * }>
     */
    public static function insAll(): array
    {
        $cols = [
            ['id' => 'logo', 'label' => 'Logo', 'group' => 'abo', 'default' => true, 'export' => false, 'sortable' => false, 'groupable' => false, 'summable' => false],
            ['id' => 'name', 'label' => 'Name', 'group' => 'abo', 'default' => true, 'export' => true, 'sortable' => true, 'groupable' => true, 'summable' => false, 'sort_key' => 'name', 'group_key' => 'name'],
            ['id' => 'price', 'label' => 'Preis', 'group' => 'abo', 'default' => true, 'export' => true, 'sortable' => true, 'groupable' => false, 'summable' => true, 'sort_key' => 'price'],
            ['id' => 'currency', 'label' => 'Währung', 'group' => 'abo', 'default' => true, 'export' => true, 'sortable' => false, 'groupable' => true, 'summable' => false, 'group_key' => 'currency'],
            ['id' => 'cycle', 'label' => 'Zyklus', 'group' => 'abo', 'default' => true, 'export' => true, 'sortable' => true, 'groupable' => true, 'summable' => false, 'sort_key' => 'cycle', 'group_key' => 'cycle'],
            ['id' => 'next_payment', 'label' => 'Nächste Zahlung', 'group' => 'abo', 'default' => true, 'export' => true, 'sortable' => true, 'groupable' => true, 'summable' => false, 'sort_key' => 'next_payment', 'group_key' => 'next_payment_month'],
            ['id' => 'category', 'label' => 'Kategorie', 'group' => 'abo', 'default' => true, 'export' => true, 'sortable' => true, 'groupable' => true, 'summable' => false, 'sort_key' => 'category', 'group_key' => 'category'],
            ['id' => 'payment_method', 'label' => 'Zahlungsart', 'group' => 'abo', 'default' => true, 'export' => true, 'sortable' => true, 'groupable' => true, 'summable' => false, 'sort_key' => 'payment_method', 'group_key' => 'payment_method'],
            ['id' => 'payer', 'label' => 'Zahler', 'group' => 'abo', 'default' => true, 'export' => true, 'sortable' => true, 'groupable' => true, 'summable' => false, 'sort_key' => 'payer', 'group_key' => 'payer'],
            ['id' => 'inactive', 'label' => 'Status', 'group' => 'abo', 'default' => true, 'export' => true, 'sortable' => true, 'groupable' => true, 'summable' => false, 'sort_key' => 'inactive', 'group_key' => 'inactive'],
            ['id' => 'auto_renew', 'label' => 'Verlängerung', 'group' => 'abo', 'default' => false, 'export' => true, 'sortable' => true, 'groupable' => true, 'summable' => false, 'sort_key' => 'auto_renew', 'group_key' => 'auto_renew'],
            ['id' => 'notes', 'label' => 'Notiz', 'group' => 'meta', 'default' => false, 'export' => true, 'sortable' => false, 'groupable' => false, 'summable' => false],
            ['id' => 'url', 'label' => 'Link', 'group' => 'meta', 'default' => false, 'export' => true, 'sortable' => false, 'groupable' => false, 'summable' => false],
            ['id' => 'is_insurance', 'label' => 'Versicherung', 'group' => 'versicherung', 'default' => false, 'export' => true, 'sortable' => true, 'groupable' => true, 'summable' => false, 'sort_key' => 'is_insurance', 'group_key' => 'is_insurance'],
            ['id' => 'insurance_group', 'label' => 'Versicherungsgruppe', 'group' => 'versicherung', 'default' => false, 'export' => true, 'sortable' => true, 'groupable' => true, 'summable' => false, 'sort_key' => 'insurance_group', 'group_key' => 'insurance_group'],
            ['id' => 'insurance_type', 'label' => 'Versicherungsart', 'group' => 'versicherung', 'default' => false, 'export' => true, 'sortable' => true, 'groupable' => true, 'summable' => false, 'sort_key' => 'insurance_type', 'group_key' => 'insurance_type'],
            ['id' => 'policy_number', 'label' => 'Versicherungsnr.', 'group' => 'versicherung', 'default' => false, 'export' => true, 'sortable' => true, 'groupable' => true, 'summable' => false, 'sort_key' => 'policy_number', 'group_key' => 'policy_number'],
            ['id' => 'insurer_name', 'label' => 'Versicherer', 'group' => 'versicherung', 'default' => false, 'export' => true, 'sortable' => true, 'groupable' => true, 'summable' => false, 'sort_key' => 'insurer_name', 'group_key' => 'insurer_name'],
            ['id' => 'contract_status', 'label' => 'Vertragsstatus', 'group' => 'versicherung', 'default' => false, 'export' => true, 'sortable' => true, 'groupable' => true, 'summable' => false, 'sort_key' => 'contract_status', 'group_key' => 'contract_status'],
            ['id' => 'main_due_date', 'label' => 'Hauptfälligkeit', 'group' => 'versicherung', 'default' => false, 'export' => true, 'sortable' => true, 'groupable' => true, 'summable' => false, 'sort_key' => 'main_due_date', 'group_key' => 'main_due_date'],
            ['id' => 'insurance_sum', 'label' => 'Versicherungssumme', 'group' => 'versicherung', 'default' => false, 'export' => true, 'sortable' => true, 'groupable' => false, 'summable' => true, 'sort_key' => 'insurance_sum'],
            ['id' => 'deductible', 'label' => 'Selbstbehalt', 'group' => 'versicherung', 'default' => false, 'export' => true, 'sortable' => true, 'groupable' => false, 'summable' => true, 'sort_key' => 'deductible'],
            ['id' => 'claims_hotline', 'label' => 'Schaden-Hotline', 'group' => 'versicherung', 'default' => false, 'export' => true, 'sortable' => false, 'groupable' => false, 'summable' => false],
            ['id' => 'broker_name', 'label' => 'Vermittler', 'group' => 'vertrag', 'default' => false, 'export' => true, 'sortable' => true, 'groupable' => true, 'summable' => false, 'sort_key' => 'broker_name', 'group_key' => 'broker_name'],
            ['id' => 'tariff_name', 'label' => 'Tarif', 'group' => 'vertrag', 'default' => false, 'export' => true, 'sortable' => true, 'groupable' => true, 'summable' => false, 'sort_key' => 'tariff_name', 'group_key' => 'tariff_name'],
            ['id' => 'policyholder', 'label' => 'Versicherungsnehmer', 'group' => 'vertrag', 'default' => false, 'export' => true, 'sortable' => true, 'groupable' => true, 'summable' => false, 'sort_key' => 'policyholder', 'group_key' => 'policyholder'],
            ['id' => 'insured_persons', 'label' => 'Versicherte Personen', 'group' => 'vertrag', 'default' => false, 'export' => true, 'sortable' => true, 'groupable' => true, 'summable' => false, 'sort_key' => 'insured_persons', 'group_key' => 'insured_persons'],
            ['id' => 'beneficiary', 'label' => 'Begünstigte', 'group' => 'vertrag', 'default' => false, 'export' => true, 'sortable' => true, 'groupable' => true, 'summable' => false, 'sort_key' => 'beneficiary', 'group_key' => 'beneficiary'],
            ['id' => 'start_date', 'label' => 'Vertragsbeginn', 'group' => 'vertrag', 'default' => false, 'export' => true, 'sortable' => true, 'groupable' => true, 'summable' => false, 'sort_key' => 'start_date', 'group_key' => 'start_year'],
            ['id' => 'end_date', 'label' => 'Vertragsende', 'group' => 'vertrag', 'default' => false, 'export' => true, 'sortable' => true, 'groupable' => true, 'summable' => false, 'sort_key' => 'end_date', 'group_key' => 'end_year'],
            ['id' => 'minimum_term_months', 'label' => 'Mindestlaufzeit (Mon.)', 'group' => 'vertrag', 'default' => false, 'export' => true, 'sortable' => true, 'groupable' => true, 'summable' => false, 'sort_key' => 'minimum_term_months', 'group_key' => 'minimum_term_months'],
            ['id' => 'cancellation_period', 'label' => 'Kündigungsfrist', 'group' => 'vertrag', 'default' => false, 'export' => true, 'sortable' => false, 'groupable' => true, 'summable' => false, 'group_key' => 'cancellation_period'],
            ['id' => 'cancellation_status', 'label' => 'Kündigungsstatus', 'group' => 'vertrag', 'default' => false, 'export' => true, 'sortable' => true, 'groupable' => true, 'summable' => false, 'sort_key' => 'cancellation_status', 'group_key' => 'cancellation_status'],
            ['id' => 'auto_contract_renewal', 'label' => 'Vertrags-Verlängerung', 'group' => 'vertrag', 'default' => false, 'export' => true, 'sortable' => true, 'groupable' => true, 'summable' => false, 'sort_key' => 'auto_contract_renewal', 'group_key' => 'auto_contract_renewal'],
            ['id' => 'renewal_period_months', 'label' => 'Verlängerungszeitraum', 'group' => 'vertrag', 'default' => false, 'export' => true, 'sortable' => true, 'groupable' => true, 'summable' => false, 'sort_key' => 'renewal_period_months', 'group_key' => 'renewal_period_months'],
            ['id' => 'payment_method_text', 'label' => 'Zahlungsweg', 'group' => 'vertrag', 'default' => false, 'export' => true, 'sortable' => true, 'groupable' => true, 'summable' => false, 'sort_key' => 'payment_method_text', 'group_key' => 'payment_method_text'],
            ['id' => 'bank_account_label', 'label' => 'Konto', 'group' => 'vertrag', 'default' => false, 'export' => true, 'sortable' => true, 'groupable' => true, 'summable' => false, 'sort_key' => 'bank_account_label', 'group_key' => 'bank_account_label'],
            ['id' => 'contact_name', 'label' => 'Ansprechpartner', 'group' => 'vertrag', 'default' => false, 'export' => true, 'sortable' => true, 'groupable' => true, 'summable' => false, 'sort_key' => 'contact_name', 'group_key' => 'contact_name'],
            ['id' => 'contact_phone', 'label' => 'Telefon', 'group' => 'vertrag', 'default' => false, 'export' => true, 'sortable' => true, 'groupable' => true, 'summable' => false, 'sort_key' => 'contact_phone', 'group_key' => 'contact_phone'],
            ['id' => 'contact_email', 'label' => 'E-Mail', 'group' => 'vertrag', 'default' => false, 'export' => true, 'sortable' => true, 'groupable' => true, 'summable' => false, 'sort_key' => 'contact_email', 'group_key' => 'contact_email'],
            ['id' => 'portal_url', 'label' => 'Portal', 'group' => 'vertrag', 'default' => false, 'export' => true, 'sortable' => false, 'groupable' => false, 'summable' => false],
        ];

        return $cols;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function insById(): array
    {
        $map = [];
        foreach (self::insAll() as $col) {
            $map[$col['id']] = $col;
        }

        return $map;
    }

    /**
     * @param list<string>|null $requested
     * @return list<string> valid column ids
     */
    public static function insNormalizeVisible(?array $requested): array
    {
        $byId = self::insById();
        if ($requested === null || $requested === []) {
            $visible = [];
            foreach (self::insAll() as $col) {
                if ($col['default']) {
                    $visible[] = $col['id'];
                }
            }

            return $visible;
        }

        $visible = [];
        foreach ($requested as $id) {
            $id = trim((string) $id);
            if ($id !== '' && isset($byId[$id])) {
                $visible[] = $id;
            }
        }

        return $visible !== [] ? $visible : self::insNormalizeVisible(null);
    }

    public static function insGroupLabel(string $group): string
    {
        return match ($group) {
            'versicherung' => 'Versicherung',
            'vertrag' => 'Vertrag',
            'meta' => 'Meta',
            default => 'Abo',
        };
    }
}
