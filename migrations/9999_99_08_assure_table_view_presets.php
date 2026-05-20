<?php

// AssureWallos: saved table view presets per user (sort, group, columns).
$db->exec("
    CREATE TABLE IF NOT EXISTS assure_user_table_presets (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        name TEXT NOT NULL,
        is_default INTEGER NOT NULL DEFAULT 0,
        payload_json TEXT NOT NULL,
        created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE (user_id, name)
    )
");

$db->exec("
    CREATE INDEX IF NOT EXISTS idx_assure_user_table_presets_user
    ON assure_user_table_presets(user_id)
");

?>
