# CSV-Import (Abos & Versicherung)

AssureWallos kann **Verträge und Versicherungen** aus einer CSV-Datei importieren. Der Ablauf ist zweistufig: **Vorschau** mit Validierung, danach **bestätigter Import**. Es werden **nur neue Datensätze** angelegt — bestehende Verträge werden nicht aktualisiert.

**Ort in der App:** Profil → Abschnitt **Konto** → **CSV-Import (Abos & Versicherung)** (unter Export).

---

## Ablauf

1. **Vorlage herunterladen** (Link im Import-Block) oder eigene CSV mit gleichen Spaltennamen erstellen.
2. CSV-Datei auswählen, optional **Standard-Kategorie** wählen (wenn die Spalte `category` leer ist).
3. **Vorschau laden** — Tabelle mit Status pro Zeile (OK / Warnung / Fehler).
4. Ergebnis prüfen; bei Duplikat-Warnungen optional **„Zeilen mit Duplikat-Warnung trotzdem importieren“** aktivieren.
5. **Import starten** bestätigen.

Die Vorschau bleibt ca. **30 Minuten** in der Sitzung gespeichert; danach CSV erneut hochladen.

---

## Dateiformat

| Eigenschaft | Wert |
|-------------|------|
| Encoding | UTF-8 (BOM wird erkannt) |
| Trennzeichen | `;` oder `,` (automatisch anhand der Kopfzeile) |
| Kopfzeile | Pflicht, Spaltennamen siehe Vorlage |
| Max. Zeilen | 500 Datenzeilen |
| Max. Dateigröße | 2 MB |

### Pflichtspalten (Abo)

| Spalte | Beschreibung |
|--------|----------------|
| `name` | Bezeichnung des Vertrags |
| `price` | Preis (Dezimalzahl, Komma oder Punkt) |
| `currency` | Währungscode oder -name (z. B. `EUR`) — bei Leerfeld: Hauptwährung |
| `cycle` | `Monthly`, `Yearly`, `Weekly`, `Daily` (auch DE: `Monatlich`, `Jährlich`, …) |
| `next_payment` | Nächste Zahlung, Format **YYYY-MM-DD** |

### Wichtige optionale Spalten (Abo)

| Spalte | Beschreibung |
|--------|----------------|
| `frequency` | Standard `1` |
| `category` | Kategoriename — bei Leerfeld: Standard-Kategorie aus UI |
| `payment_method` | Name der Zahlungsmethode |
| `payer` | Name aus „Bezahlt von“ (Haushalt) |
| `notes`, `url` | Text |
| `inactive`, `notify`, `auto_renew` | `1`/`0`, `ja`/`nein` |
| `start_date`, `cancellation_date` | YYYY-MM-DD |

### Versicherung

| Spalte | Beschreibung |
|--------|----------------|
| `is_insurance` | `1` = Versicherung (Standard in Vorlage: `1`) |
| `insurance_group` | Gruppenname (z. B. `Privat`) |
| `insurance_type` | Art-Name (muss in Einstellungen → AssureWallos existieren) |
| `policy_number` | Versicherungsnummer |
| Weitere Spalten | Wie im Formular: `insurer_name`, `tariff_name`, `insurance_sum`, `deductible`, `contract_status`, `end_date`, … |

**Vertragsbeginn** für Versicherungen: Spalte `start_date` (Wallos-Startdatum).  
**Nächste Beitragsfälligkeit:** Spalte `next_payment`.

---

## Duplikate und Warnungen

| Situation | Status | Standard beim Import |
|-----------|--------|----------------------|
| Pflichtfeld fehlt / ungültiges Datum | Fehler | Zeile wird **nicht** importiert |
| Unbekannte Währung | Fehler | — |
| Kategorie/Zahlungsmethode unbekannt | Warnung | Standardwert der Instanz |
| Gleiche **Versicherungsnummer** existiert schon | Warnung | Zeile wird **übersprungen** (optional einschließen) |
| Gleicher **Name + Preis** existiert schon | Warnung | Zeile wird **übersprungen** (optional einschließen) |
| Versicherungsart nicht gefunden | Warnung | Vertrag wird ohne `insurance_type_id` angelegt |

---

## Was nicht importiert wird

- Logos / Bilder
- Hochgeladene Versicherungsdokumente (`ins_documents`)
- Paperless-Verknüpfungen
- Bestehende Verträge aktualisieren (kein Upsert)

---

## Unterschied zum Wallos-CSV-Export

Der **Export** unter Profil nutzt andere Spaltenbezeichnungen (englische Anzeigenamen). Für den Import die **AssureWallos-Vorlage** verwenden (`endpoints/insurance/csv_import_template.php`).

---

## Fehlerbehebung

| Problem | Lösung |
|---------|--------|
| „Pflichtspalte name fehlt“ | Kopfzeile `name` prüfen (Kleinbuchstaben) |
| „next_payment ungültig“ | Datum als `2026-05-18` |
| „Zyklus unbekannt“ | `Monthly` oder `Monatlich` |
| „Versicherungsart nicht gefunden“ | Art in **Einstellungen → AssureWallos** anlegen oder Namen exakt übernehmen |
| Import-Block fehlt | Datenbank migriert? Versicherungsmodul aktiv? |
| Demo-Modus | Import deaktiviert |

---

## Siehe auch

- [PAPERLESS-ADMIN.md](PAPERLESS-ADMIN.md) — Paperless (separat vom CSV-Import)
- [DOCKER-ENDNUTZER.md](DOCKER-ENDNUTZER.md) — Installation
