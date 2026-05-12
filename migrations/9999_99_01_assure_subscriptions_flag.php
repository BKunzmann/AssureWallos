<?php

// AssureWallos: mark subscriptions that represent insurance contracts.
$columns = [];
$result = $db->query("PRAGMA table_info(subscriptions)");

while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $columns[] = $row['name'];
}

if (!in_array('is_insurance', $columns, true)) {
    $db->exec("ALTER TABLE subscriptions ADD COLUMN is_insurance INTEGER NOT NULL DEFAULT 0");
}

?>
