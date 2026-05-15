<?php

// AssureWallos: user-extensible insurance groups and types seeded from Vertragssparte.csv.
$db->exec("
    CREATE TABLE IF NOT EXISTS assure_insurance_groups (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER,
        name TEXT NOT NULL,
        sort_order INTEGER NOT NULL DEFAULT 0,
        active INTEGER NOT NULL DEFAULT 1,
        is_system INTEGER NOT NULL DEFAULT 0,
        created_at TEXT DEFAULT CURRENT_TIMESTAMP,
        updated_at TEXT DEFAULT CURRENT_TIMESTAMP
    )
");

$db->exec("
    CREATE TABLE IF NOT EXISTS assure_insurance_types (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        group_id INTEGER NOT NULL,
        user_id INTEGER,
        name TEXT NOT NULL,
        import_nr INTEGER,
        sort_order INTEGER NOT NULL DEFAULT 0,
        active INTEGER NOT NULL DEFAULT 1,
        is_system INTEGER NOT NULL DEFAULT 0,
        created_at TEXT DEFAULT CURRENT_TIMESTAMP,
        updated_at TEXT DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (group_id) REFERENCES assure_insurance_groups(id) ON DELETE CASCADE
    )
");

$db->exec("
    CREATE INDEX IF NOT EXISTS idx_assure_insurance_groups_user
    ON assure_insurance_groups(user_id)
");

$db->exec("
    CREATE INDEX IF NOT EXISTS idx_assure_insurance_types_group
    ON assure_insurance_types(group_id)
");

$db->exec("
    CREATE INDEX IF NOT EXISTS idx_assure_insurance_types_user
    ON assure_insurance_types(user_id)
");

$seed = [
    ['Gesundheit', 'Auslandsreisekranken', 1],
    ['Gesundheit', 'Betr. Krankenversicherung', 2],
    ['Gesundheit', 'Gesetzliche Krankenversicherung', 3],
    ['Gesundheit', 'Krankenhaustagegeld', 4],
    ['Gesundheit', 'Krankenzusatzversicherung', 5],
    ['Gesundheit', 'Pflegepflicht', 6],
    ['Gesundheit', 'Pflegetagegeldversicherung', 7],
    ['Gesundheit', 'Pflegezusatz', 8],
    ['Gesundheit', 'Private Krankenversicherung', 9],
    ['Gesundheit', 'Reisekranken', 10],
    ['Gesundheit', 'Zahnzusatz', 11],
    ['Vorsorge', 'Berufsunfähigkeitsversicherung', 12],
    ['Vorsorge', 'Dread-Disease-Versicherung', 13],
    ['Vorsorge', 'Grundfähigkeitsversicherung', 14],
    ['Vorsorge', 'Risikolebensversicherung', 15],
    ['Vorsorge', 'Sterbekasse', 16],
    ['Vorsorge', 'Vollmachten/Verfügungen', 17],
    ['Vorsorge', 'BasisRente', 18],
    ['Vorsorge', 'Bausparen', 19],
    ['Vorsorge', 'Betriebliche Altersvorsorge', 20],
    ['Vorsorge', 'Darlehensschutzversicherung', 21],
    ['Vorsorge', 'Fondsgebundene Lebensversicherung', 22],
    ['Vorsorge', 'Fondsgebundene Rentenversicherung', 23],
    ['Vorsorge', 'Hypothekendarlehen', 24],
    ['Vorsorge', 'Invaliditätsversicherung', 25],
    ['Vorsorge', 'Kapitalbildende Lebensversicherung', 26],
    ['Vorsorge', 'Pflegerente', 27],
    ['Vorsorge', 'Private Rentenversicherung', 28],
    ['Vorsorge', 'Riesterrente', 29],
    ['Vorsorge', 'Tagesgeldkonto', 30],
    ['Sachversicherungen', 'Bauleistungsversicherung', 31],
    ['Sachversicherungen', 'Bootsversicherung', 32],
    ['Sachversicherungen', 'Campingversicherung', 33],
    ['Sachversicherungen', 'Elektronik', 34],
    ['Sachversicherungen', 'Fahrradversicherung', 35],
    ['Sachversicherungen', 'Gebäudeversicherung', 36],
    ['Sachversicherungen', 'Glasversicherung', 37],
    ['Sachversicherungen', 'Haftpflichtversicherung', 38],
    ['Sachversicherungen', 'Hausratversicherung', 39],
    ['Sachversicherungen', 'Instrumentenversicherung', 40],
    ['Sachversicherungen', 'Kautionsversicherung', 41],
    ['Sachversicherungen', 'KFZ-Versicherung', 42],
    ['Sachversicherungen', 'Krebsversicherung', 43],
    ['Sachversicherungen', 'Privatpolice', 44],
    ['Sachversicherungen', 'Rechtsschutz', 45],
    ['Sachversicherungen', 'Reiseversicherung', 46],
    ['Sachversicherungen', 'Sonstige Versicherung', 47],
    ['Sachversicherungen', 'Tierhalterhaftpflicht', 48],
    ['Sachversicherungen', 'Tierversicherung', 49],
    ['Sachversicherungen', 'Unfallversicherung', 50],
    ['Betrieblich', 'Agrarpolice', 51],
    ['Betrieblich', 'Betr. Elektronik', 52],
    ['Betrieblich', 'Betr. Haftpflicht', 53],
    ['Betrieblich', 'Betr. Inventar', 54],
    ['Betrieblich', 'Betr. Rechtsschutz', 55],
    ['Betrieblich', 'Betriebsschließungsversicherung', 56],
    ['Betrieblich', 'Betriebsunterbrechungsversicherung', 57],
    ['Betrieblich', 'Cyberversicherung', 58],
    ['Betrieblich', 'D&O', 59],
    ['Betrieblich', 'Firmenpolice', 60],
    ['Betrieblich', 'Hagelversicherung', 61],
    ['Betrieblich', 'Maschinenversicherung', 62],
    ['Betrieblich', 'Mehrkosten- und Ertragsausfallversicherung', 63],
    ['Betrieblich', 'Montageversicherung', 64],
    ['Betrieblich', 'Transportversicherung', 65],
    ['Betrieblich', 'Verkehrshaftungsversicherung', 66],
    ['Betrieblich', 'Vermögensschadenhaftpflichtversicherung', 67],
    ['Betrieblich', 'Warenversicherung', 68],
    ['Vorsorge', 'Finanzprodukt', 71],
];

$groupOrder = [];
$groupStmt = $db->prepare("
    INSERT INTO assure_insurance_groups (user_id, name, sort_order, active, is_system)
    SELECT NULL, :name, :sortOrder, 1, 1
    WHERE NOT EXISTS (
        SELECT 1 FROM assure_insurance_groups
        WHERE user_id IS NULL AND name = :name
    )
");
$typeStmt = $db->prepare("
    INSERT INTO assure_insurance_types (group_id, user_id, name, import_nr, sort_order, active, is_system)
    SELECT :groupId, NULL, :name, :importNr, :sortOrder, 1, 1
    WHERE NOT EXISTS (
        SELECT 1 FROM assure_insurance_types
        WHERE user_id IS NULL AND name = :name AND group_id = :groupId
    )
");

foreach ($seed as $entry) {
    [$groupName, $typeName, $importNr] = $entry;

    if (!isset($groupOrder[$groupName])) {
        $groupOrder[$groupName] = count($groupOrder) + 1;
        $groupStmt->bindValue(':name', $groupName, SQLITE3_TEXT);
        $groupStmt->bindValue(':sortOrder', $groupOrder[$groupName], SQLITE3_INTEGER);
        $groupStmt->execute();
    }

    $groupSelect = $db->prepare("SELECT id FROM assure_insurance_groups WHERE user_id IS NULL AND name = :name");
    $groupSelect->bindValue(':name', $groupName, SQLITE3_TEXT);
    $groupId = (int) $groupSelect->execute()->fetchArray(SQLITE3_ASSOC)['id'];

    $typeStmt->bindValue(':groupId', $groupId, SQLITE3_INTEGER);
    $typeStmt->bindValue(':name', $typeName, SQLITE3_TEXT);
    $typeStmt->bindValue(':importNr', $importNr, SQLITE3_INTEGER);
    $typeStmt->bindValue(':sortOrder', $importNr, SQLITE3_INTEGER);
    $typeStmt->execute();
}

?>
