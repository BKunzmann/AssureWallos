<?php

// AssureWallos: 1:1 details table for insurance-specific subscription data.
$db->exec("
    CREATE TABLE IF NOT EXISTS assure_insurance_details (
        subscription_id INTEGER PRIMARY KEY,
        policy_number TEXT,
        insurance_sum REAL,
        deductible REAL,
        claims_hotline TEXT,
        created_at TEXT DEFAULT CURRENT_TIMESTAMP,
        updated_at TEXT DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (subscription_id) REFERENCES subscriptions(id) ON DELETE CASCADE
    )
");

?>
