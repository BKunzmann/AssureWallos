<?php

// AssureWallos: categorized document metadata for insurance subscriptions.
$db->exec("
    CREATE TABLE IF NOT EXISTS assure_documents (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        subscription_id INTEGER NOT NULL,
        user_id INTEGER NOT NULL,
        doc_type TEXT NOT NULL,
        filename TEXT NOT NULL,
        original_name TEXT NOT NULL,
        mime_type TEXT,
        size_bytes INTEGER,
        uploaded_at TEXT DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (subscription_id) REFERENCES subscriptions(id) ON DELETE CASCADE
    )
");

$db->exec("
    CREATE INDEX IF NOT EXISTS idx_assure_documents_subscription
    ON assure_documents(subscription_id)
");

$db->exec("
    CREATE INDEX IF NOT EXISTS idx_assure_documents_user
    ON assure_documents(user_id)
");

?>
