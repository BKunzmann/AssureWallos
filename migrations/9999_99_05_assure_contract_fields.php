<?php

// AssureWallos: optional contract-management fields for insurance subscriptions.
$columns = [];
$result = $db->query("PRAGMA table_info(assure_insurance_details)");

while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $columns[] = $row['name'];
}

$fields = [
    'insurance_type_id' => 'INTEGER',
    'insurer_name' => 'TEXT',
    'broker_name' => 'TEXT',
    'tariff_name' => 'TEXT',
    'policyholder' => 'TEXT',
    'insured_persons' => 'TEXT',
    'beneficiary' => 'TEXT',
    'document_url' => 'TEXT',
    'portal_url' => 'TEXT',
    'portal_notes' => 'TEXT',
    'contract_status' => 'TEXT',
    'start_date' => 'TEXT',
    'end_date' => 'TEXT',
    'main_due_date' => 'TEXT',
    'minimum_term_months' => 'INTEGER',
    'cancellation_period_value' => 'INTEGER',
    'cancellation_period_unit' => 'TEXT',
    'auto_contract_renewal' => 'INTEGER NOT NULL DEFAULT 0',
    'renewal_period_months' => 'INTEGER',
    'cancellation_status' => 'TEXT',
    'payment_method_text' => 'TEXT',
    'bank_account_label' => 'TEXT',
    'contact_name' => 'TEXT',
    'contact_phone' => 'TEXT',
    'contact_email' => 'TEXT',
    'claim_reference_notes' => 'TEXT'
];

foreach ($fields as $name => $definition) {
    if (!in_array($name, $columns, true)) {
        $db->exec("ALTER TABLE assure_insurance_details ADD COLUMN {$name} {$definition}");
    }
}

?>
