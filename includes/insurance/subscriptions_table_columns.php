<?php

/**
 * Column registry for subscriptions table view (AssureWallos).
 */

class Ins_Subscriptions_Table_Columns
{
    /**
     * @return list<array{id: string, label: string, group: string, default: bool}>
     */
    public static function insAll(): array
    {
        return [
            ['id' => 'logo', 'label' => 'Logo', 'group' => 'abo', 'default' => true],
            ['id' => 'name', 'label' => 'Name', 'group' => 'abo', 'default' => true],
            ['id' => 'price', 'label' => 'Preis', 'group' => 'abo', 'default' => true],
            ['id' => 'currency', 'label' => 'Währung', 'group' => 'abo', 'default' => true],
            ['id' => 'cycle', 'label' => 'Zyklus', 'group' => 'abo', 'default' => true],
            ['id' => 'next_payment', 'label' => 'Nächste Zahlung', 'group' => 'abo', 'default' => true],
            ['id' => 'category', 'label' => 'Kategorie', 'group' => 'abo', 'default' => true],
            ['id' => 'payment_method', 'label' => 'Zahlungsart', 'group' => 'abo', 'default' => true],
            ['id' => 'payer', 'label' => 'Zahler', 'group' => 'abo', 'default' => true],
            ['id' => 'inactive', 'label' => 'Status', 'group' => 'abo', 'default' => true],
            ['id' => 'auto_renew', 'label' => 'Verlängerung', 'group' => 'abo', 'default' => false],
            ['id' => 'is_insurance', 'label' => 'Versicherung', 'group' => 'versicherung', 'default' => false],
            ['id' => 'insurance_group', 'label' => 'Versicherungsgruppe', 'group' => 'versicherung', 'default' => false],
            ['id' => 'insurance_type', 'label' => 'Versicherungsart', 'group' => 'versicherung', 'default' => false],
            ['id' => 'policy_number', 'label' => 'Versicherungsnr.', 'group' => 'versicherung', 'default' => false],
            ['id' => 'insurer_name', 'label' => 'Versicherer', 'group' => 'versicherung', 'default' => false],
            ['id' => 'contract_status', 'label' => 'Vertragsstatus', 'group' => 'versicherung', 'default' => false],
            ['id' => 'main_due_date', 'label' => 'Hauptfälligkeit', 'group' => 'versicherung', 'default' => false],
        ];
    }

    /**
     * @return array<string, array{id: string, label: string, group: string, default: bool}>
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
}
