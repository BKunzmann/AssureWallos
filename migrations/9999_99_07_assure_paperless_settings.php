<?php

// AssureWallos: instance-wide Paperless-ngx read-only archive settings.
$db->exec("
    CREATE TABLE IF NOT EXISTS assure_paperless_settings (
        id INTEGER PRIMARY KEY CHECK (id = 1),
        enabled INTEGER NOT NULL DEFAULT 0,
        base_url TEXT NOT NULL DEFAULT '',
        api_token TEXT NOT NULL DEFAULT '',
        match_mode TEXT NOT NULL DEFAULT 'custom_field',
        custom_field_name TEXT NOT NULL DEFAULT 'assure_subscription_id',
        tag_prefix TEXT NOT NULL DEFAULT 'aw-sub-',
        use_policy_number_fallback INTEGER NOT NULL DEFAULT 0,
        cache_ttl_seconds INTEGER NOT NULL DEFAULT 300
    )
");

$db->exec("
    INSERT OR IGNORE INTO assure_paperless_settings (id) VALUES (1)
");

$db->exec("
    CREATE TABLE IF NOT EXISTS assure_paperless_cache (
        subscription_id INTEGER NOT NULL,
        user_id INTEGER NOT NULL,
        payload_json TEXT NOT NULL,
        fetched_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (subscription_id, user_id),
        FOREIGN KEY (subscription_id) REFERENCES subscriptions(id) ON DELETE CASCADE
    )
");

$db->exec("
    CREATE INDEX IF NOT EXISTS idx_assure_paperless_cache_fetched
    ON assure_paperless_cache(fetched_at)
");

?>
