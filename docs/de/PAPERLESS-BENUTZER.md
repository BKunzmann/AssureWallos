# Paperless-Archiv im Versicherungsformular (Benutzer)

Diese Anleitung richtet sich an **alle angemeldeten Nutzer** von AssureWallos, die Versicherungsverträge pflegen. Sie beschreibt, wie Sie archivierte Dokumente aus **Paperless-ngx** im Vertrag sehen — **ohne** Paperless oder die Server-Einstellungen konfigurieren zu müssen.

**Einrichtung durch den Administrator:** [PAPERLESS-ADMIN.md](PAPERLESS-ADMIN.md)

---

## Was Sie sehen (und was nicht)

| Ja | Nein |
|----|------|
| Liste oder Vorschau von Dokumenten, die in Paperless **bereits** existieren und Ihrem Vertrag zugeordnet sind | Dokumente aus AssureWallos **nach** Paperless hochladen |
| Link **„In Paperless“** — öffnet das Dokument in der Paperless-Oberfläche | Paperless-Einstellungen ändern (nur Admin) |
| Umschalten zwischen **Liste** und **Vorschau** (Vorschaubilder) | Dokumente in AssureWallos bearbeiten, die nur in Paperless liegen |

AssureWallos ist hier eine **Lesefläche** auf Ihr bestehendes Paperless-Archiv. Scannen, Import und Ablage bleiben in Paperless (oder Ihren gewohnten Workflows dort).

---

## Wann erscheint das Paperless-Archiv?

Der Bereich **Paperless-Archiv** erscheint unter **Versicherungsdokumente**, wenn **alle** Punkte zutreffen:

1. Ihr Administrator hat die Paperless-Anbindung **aktiviert** und getestet.
2. Sie bearbeiten einen Vertrag mit aktiviertem Häkchen **„Dies ist eine Versicherung“**.
3. Der Vertrag wurde **mindestens einmal gespeichert** (damit eine interne **Vertrags-ID** existiert).

Bei einem **neuen**, noch nicht gespeicherten Vertrag steht dort der Hinweis, zuerst zu speichern — danach kann das Archiv geladen werden.

Wenn Paperless für Ihre Installation nicht eingerichtet ist, sehen Sie den Block gar nicht.

---

## Vertrags-ID — wofür ist die?

Unter **„Dies ist eine Versicherung“** sehen Sie nach dem Speichern z. B.:

**Vertrags-ID: 42** — für Paperless z. B. Tag `aw-sub-42` oder Custom Field mit diesem Wert.

- Die **Vertrags-ID** ist die interne Nummer Ihres Vertrags in AssureWallos (nicht die Versicherungsnummer aus dem Formular).
- Ihr Administrator (oder Ihr Ablage-Prozess in Paperless) verknüpft Dokumente in Paperless mit genau dieser Nummer — per Custom Field, Tag oder (je nach Einstellung) über die Versicherungsnummer im Dokumententext.
- **Sie müssen die ID normalerweise nicht selbst in Paperless eintragen**, wenn ein zentraler Ablage-Prozess das erledigt. Die Anzeige hilft beim Abgleich („Welche Nummer gehört zu diesem Vertrag?“).

---

## Paperless-Archiv bedienen

### Öffnen

1. Startseite → Vertrag öffnen (oder neu anlegen).
2. **„Dies ist eine Versicherung“** aktivieren (falls noch nicht gesetzt).
3. Nach unten zu **Versicherungsdokumente** scrollen.
4. Abschnitt **Paperless-Archiv** — die Liste wird automatisch geladen.

### Ansicht: Liste oder Vorschau

Rechts neben der Überschrift **Paperless-Archiv**:

| Schaltfläche | Bedeutung |
|--------------|-----------|
| **Liste** | Datum, Titel, Button **In Paperless** (Standard) |
| **Vorschau** | Kacheln mit Vorschaubild, Titel, Datum, **In Paperless** |

Ihre letzte Wahl wird im Browser gemerkt und beim nächsten Mal wieder verwendet.

### Dokument in Paperless öffnen

- In der **Liste:** auf den **Titel** klicken oder **In Paperless**.
- In der **Vorschau:** auf das **Bild** oder den **Titel** klicken oder **In Paperless**.

Es öffnet sich ein neuer Tab mit der Paperless-Oberfläche (sofern Sie dort Zugriff haben). AssureWallos ersetzt Paperless nicht.

### Keine Treffer

Meldung: *„Keine passenden Dokumente in Paperless gefunden.“*

Das bedeutet: Für diesen Vertrag hat Paperless (laut Admin-Einstellung) **kein** passend verknüpftes Dokument. Typische Gründe:

- Dokument in Paperless hat noch **kein** passendes Custom Field / Tag / keine passende Versicherungsnummer im Text.
- **Falsche Vertrags-ID** in Paperless (Tippfehler im Tag, andere Nummer).
- Dokument liegt in Paperless, wurde aber **noch nicht** zugeordnet.
- **Cache** beim Administrator (kurze Verzögerung bis neue Dokumente erscheinen).

**Was Sie tun können:** Vertrags-ID notieren und an die Person wenden, die Paperless pflegt (Admin oder Ablage). Sie selbst können in AssureWallos das Archiv nicht „reparieren“, nur anzeigen lassen.

---

## Versicherungsdokumente in AssureWallos vs. Paperless

Im gleichen Formularbereich gibt es zwei getrennte Welten:

| **Versicherungsdokumente (AssureWallos)** | **Paperless-Archiv** |
|-------------------------------------------|----------------------|
| Dateien, die Sie **hier hochladen** (PDF, Bilder) | Dateien, die **in Paperless** archiviert sind |
| Speicherung auf dem AssureWallos-Server | Speicherung in Paperless |
| Löschen/Hochladen in AssureWallos möglich | Nur anzeigen und in Paperless öffnen |

Ein Upload in AssureWallos erscheint **nicht automatisch** in Paperless und umgekehrt.

---

## Häufige Fragen

**Muss ich Paperless installieren?**  
Nein. Sie brauchen nur Zugriff auf Paperless, wenn Sie Links dort öffnen wollen. Die Anzeige in AssureWallos funktioniert, sobald der Admin die Verbindung eingerichtet hat.

**Warum sehe ich keine Vorschauen?**  
Auf **Vorschau** umschalten. Wenn Bilder fehlen, Paperless oder die Verbindung prüfen lassen (Admin).

**Kann ich als normaler Nutzer Paperless konfigurieren?**  
Nein. Nur der Instanz-Administrator (Einstellungen → Paperless-ngx).

**Ändert sich das Archiv, wenn ich den Vertrag speichere?**  
Beim erneuten Öffnen wird die Liste neu geladen (ggf. nach kurzer Cache-Zeit, die der Admin festlegt).

**Gilt das auch für Nicht-Versicherungen?**  
Nein. Nur bei **„Dies ist eine Versicherung“**.

---

## Kurzreferenz

1. Versicherung aktivieren → speichern → **Vertrags-ID** merken (falls Sie Paperless mitpflegen).
2. **Versicherungsdokumente** → **Paperless-Archiv**.
3. **Liste** / **Vorschau** nach Bedarf.
4. **In Paperless** zum vollständigen Dokument.

Bei Problemen: Administrator mit [PAPERLESS-ADMIN.md](PAPERLESS-ADMIN.md) oder Vertrags-ID und Screenshots kontaktieren.

---

## Siehe auch

- [PAPERLESS-ADMIN.md](PAPERLESS-ADMIN.md) — Einrichtung (Administrator)
- [DOCKER-ENDNUTZER.md](DOCKER-ENDNUTZER.md) — Installation AssureWallos per Docker
