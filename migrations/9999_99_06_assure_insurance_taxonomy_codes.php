<?php

// AssureWallos: Kurzzeichen und Codes für Gruppen/Arten (Anzeige wie Wallos-Währungen).

$insTaxAddCol = static function (SQLite3 $db, string $table, string $column, string $ddlSuffix): void {
    $res = $db->query('PRAGMA table_info(' . $table . ')');
    while ($res && ($row = $res->fetchArray(SQLITE3_ASSOC))) {
        if (($row['name'] ?? '') === $column) {
            return;
        }
    }
    $db->exec('ALTER TABLE ' . $table . ' ADD COLUMN ' . $column . ' ' . $ddlSuffix);
};

$insTaxAddCol($db, 'assure_insurance_groups', 'symbol', "TEXT NOT NULL DEFAULT ''");
$insTaxAddCol($db, 'assure_insurance_groups', 'code', "TEXT NOT NULL DEFAULT ''");
$insTaxAddCol($db, 'assure_insurance_types', 'symbol', "TEXT NOT NULL DEFAULT ''");
$insTaxAddCol($db, 'assure_insurance_types', 'code', "TEXT NOT NULL DEFAULT ''");

$db->exec("
    UPDATE assure_insurance_groups
    SET
        symbol = COALESCE(NULLIF(TRIM(symbol), ''), SUBSTR(name, 1, 1)),
        code = COALESCE(NULLIF(TRIM(code), ''), UPPER(SUBSTR(REPLACE(TRIM(name), ' ', ''), 1, 3)))
");

$db->exec("
    UPDATE assure_insurance_types
    SET
        symbol = COALESCE(NULLIF(TRIM(symbol), ''), SUBSTR(name, 1, 1)),
        code = COALESCE(
            NULLIF(TRIM(code), ''),
            CASE
                WHEN import_nr IS NOT NULL THEN CAST(import_nr AS TEXT)
                ELSE UPPER(SUBSTR(REPLACE(TRIM(name), ' ', ''), 1, 3))
            END
        )
");

?>
