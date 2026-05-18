# Paperless-ngx einrichten (Administrator)

Diese Anleitung richtet sich an den **Instanz-Administrator** von AssureWallos (in der Regel der erste Benutzer, User-ID 1). Sie beschreibt die **read-only**-Anbindung an [Paperless-ngx](https://docs.paperless-ngx.com/): Archivierte Dokumente aus Paperless werden im Versicherungsformular angezeigt und können in Paperless geöffnet werden.

**Was AssureWallos nicht tut:** keine Dateien nach Paperless hochladen, keine Dokumente in Paperless anlegen oder bearbeiten, keine bidirektionale Synchronisation.

**Endnutzer-Anleitung (ohne Admin-Rechte):** [PAPERLESS-BENUTZER.md](PAPERLESS-BENUTZER.md)

---

## Inhaltsverzeichnis

1. [Überblick](#überblick)
2. [Voraussetzungen](#voraussetzungen)
3. [Kurz-Checkliste](#kurz-checkliste)
4. [Schritt 1: Paperless vorbereiten](#schritt-1-paperless-vorbereiten)
5. [Schritt 2: AssureWallos verbinden](#schritt-2-assurewallos-verbinden)
6. [Schritt 3: Zuordnungsmodus wählen](#schritt-3-zuordnungsmodus-wählen)
7. [Zuordnung in Paperless pflegen](#zuordnung-in-paperless-pflegen)
8. [Leistung (Cache)](#leistung-cache)
9. [Netzwerk, Docker und SSRF](#netzwerk-docker-und-ssrf)
10. [Test und Fehlerbehebung](#test-und-fehlerbehebung)
11. [Sicherheit](#sicherheit)

---

## Überblick

| Thema | Verhalten |
|--------|-----------|
| **Wo konfigurieren?** | AssureWallos → **Einstellungen** → Abschnitt **Paperless-ngx** (nur sichtbar für User-ID 1) |
| **Wo sehen Nutzer Dokumente?** | Beim Bearbeiten eines Vertrags mit **„Dies ist eine Versicherung“** → Bereich **Versicherungsdokumente** → **Paperless-Archiv** |
| **Verknüpfung** | Jedes Paperless-Dokument muss zur **Vertrags-ID** (interne Abonnement-ID) oder — je nach Modus — zur **Versicherungsnummer** passen |
| **Ansicht** | Liste oder Vorschau (Thumbnails); Umschalter im Archiv-Block, Einstellung wird im Browser gespeichert |
| **Speichern** | Button **„Paperless-Einstellungen speichern“** — **nicht** der globale Wallos-Speichern-Button der Einstellungsseite |

### Vertrags-ID vs. Versicherungsnummer

- **Vertrags-ID:** Interne Nummer des Abonnements in AssureWallos (`subscriptions.id`). Sie erscheint im Formular unter **„Dies ist eine Versicherung“** nach dem ersten Speichern (z. B. `Vertrags-ID: 42`). Für Custom Field und Tag-Modus ist **diese** ID maßgeblich.
- **Versicherungsnummer:** Freitext im Versicherungsformular (`policy_number`). Nur relevant für den Modus **Volltext — Versicherungsnummer** oder die optionale **Fallback-Suche**.

---

## Voraussetzungen

- Paperless-ngx läuft und ist per Browser erreichbar (mindestens für Sie als Admin).
- AssureWallos-Migration `9999_99_07_assure_paperless_settings` ist ausgeführt (passiert beim normalen DB-Migrate nach Update).
- Sie sind als **Administrator (User-ID 1)** in AssureWallos angemeldet.
- In Paperless existiert ein Benutzer mit Recht, die REST-API zu nutzen (Standard bei Paperless-Benutzern).

---

## Kurz-Checkliste

1. [ ] API-Token in Paperless erzeugen (**Mein Profil**)
2. [ ] In AssureWallos: Basis-URL + Token eintragen, Archiv aktivieren, **Paperless-Einstellungen speichern**
3. [ ] **Verbindung testen** — Meldung „Verbindung zu Paperless erfolgreich“
4. [ ] Bei Docker/LAN: Hostname der Paperless-URL in der **Webhook-Allowlist** (Wallos-**Administration** → Sicherheit)
5. [ ] Zuordnungsmodus wählen und in Paperless Dokumente entsprechend markieren
6. [ ] Test-Vertrag als Versicherung speichern, Vertrags-ID notieren, Paperless-Dokument zuordnen
7. [ ] Im Versicherungsformular prüfen, ob **Paperless-Archiv** Treffer zeigt

---

## Schritt 1: Paperless vorbereiten

### API-Token erzeugen

1. In Paperless-ngx anmelden.
2. **Einstellungen / Mein Profil** (je nach Paperless-Version).
3. Unter **API-Token** einen neuen Token erzeugen und **sicher kopieren** (wird nur einmal vollständig angezeigt).
4. Den Token nur in AssureWallos eintragen — nicht in Chats oder Tickets posten.

### Basis-URL festlegen (zwei URLs möglich)

| Verwendung | Welche URL |
|------------|------------|
| **AssureWallos → Paperless (API)** | Die URL, die der **AssureWallos-Server** per HTTP erreicht (Container-intern, LAN-IP, interner DNS-Name) |
| **Nutzer → Paperless (Browser)** | Die URL, die **Nutzer im Browser** öffnen (oft öffentliche Domain mit HTTPS) |

In den AssureWallos-Einstellungen tragen Sie die **API-Basis-URL** ein (ohne abschließenden Schrägstrich), z. B.:

- `https://paperless.example.de` (Reverse Proxy, von überall erreichbar)
- `http://paperless:8000` (nur wenn AssureWallos im **gleichen Docker-Netzwerk** wie Paperless läuft)

Die Links **„In Paperless“** im Versicherungsformular nutzen dieselbe konfigurierte Basis-URL. Wenn Nutzer Paperless nur unter einer anderen Domain erreichen, sollten Sie dieselbe öffentliche URL als Basis-URL eintragen (sofern AssureWallos sie per API ebenfalls erreichen kann).

---

## Schritt 2: AssureWallos verbinden

1. In AssureWallos **Einstellungen** öffnen.
2. Abschnitt **Paperless-ngx** (ganz unten bei Admin-Installationen).
3. **Paperless-Archiv in Versicherungen anzeigen** aktivieren.
4. **Basis-URL** eintragen (siehe oben).
5. **API-Token** eintragen (aus Paperless).
6. Auf **Paperless-Einstellungen speichern** klicken.

**Hinweis zum Token-Feld:** Nach dem Speichern bleibt das Passwortfeld **absichtlich leer**. Die Meldung *„API-Token ist gespeichert …“* bestätigt, dass ein Token hinterlegt ist. Nur zum **Ersetzen** erneut eintragen.

7. **Verbindung testen** — bei Erfolg erscheint eine Bestätigung.

---

## Schritt 3: Zuordnungsmodus wählen

Unter **Dokumente zuordnen** legen Sie fest, wie AssureWallos Paperless-Dokumente einem Vertrag zuordnet. **Eine Hauptvariante** wählen; optional kommt die Fallback-Suche hinzu.

### Modus A: Custom Field — Vertrags-ID (empfohlen)

**Geeignet für:** Saubere, eindeutige Zuordnung; skaliert gut bei vielen Verträgen.

**In AssureWallos:**

- Zuordnung: **Custom Field — Vertrags-ID**
- Feldname: Standard `assure_subscription_id` (oder Ihr Name, muss in Paperless identisch sein)

**In Paperless:**

1. **Einstellungen → Custom Fields** (Bezeichnung je nach Version).
2. Neues Feld anlegen, z. B. Name `assure_subscription_id`, Typ **Integer** (oder vergleichbar).
3. Pro Versicherungsdokument den Wert = **Vertrags-ID** aus AssureWallos setzen (z. B. `42`).

**Vorteile:** Exakter Match, keine Verwechslung ähnlicher Versicherungsnummern.  
**Nachteile:** Einmalige Einrichtung des Custom Fields; jedes Dokument muss den Wert erhalten (manuell, Regelwerk oder externe Automatisierung).

---

### Modus B: Tag — Vertrags-ID

**Geeignet für:** Workflows, die ohnehin stark mit Tags arbeiten.

**In AssureWallos:**

- Zuordnung: **Tag — Vertrags-ID**
- Tag-Präfix: Standard `aw-sub-` (anpassbar)

**In Paperless:**

- Jedes Dokument erhält einen Tag **`{Präfix}{Vertrags-ID}`**, z. B. `aw-sub-42` bei Vertrags-ID 42.
- Der Tag-Name muss **exakt** passen (Groß-/Kleinschreibung wird bei der API-Suche nicht unterschieden, aber der Name muss vollständig stimmen — kein „enthält nur Teile der Nummer“).

**Vorteile:** In der Paperless-UI gut sichtbar; Bulk-Tagging möglich.  
**Nachteile:** Tag-Disziplin nötig; Tippfehler im Tag führen zu fehlenden Treffern.

---

### Modus C: Volltext — Versicherungsnummer

**Geeignet für:** Bestandsarchive, in denen die Versicherungsnummer bereits im Titel oder OCR-Text vorkommt.

**In AssureWallos:**

- Zuordnung: **Volltext — Versicherungsnummer**

**In Paperless:**

- AssureWallos sucht in **Titel und Dokumentinhalt (OCR)** nach dem exakten Inhalt des Feldes **Versicherungsnummer** im Versicherungsformular.

**Wichtige Grenzen:**

- **Keine Toleranz** für Leerzeichen, Bindestriche oder Schreibvarianten — der Text muss in Paperless so vorkommen wie in AssureWallos.
- Mehrere Verträge mit gleicher Nummer in AssureWallos können theoretisch dieselben Paperless-Treffer sehen.
- OCR-Qualität beeinflusst Treffer.

**Vorteile:** Kein Custom Field / kein Tag nötig, wenn Nummern bereits im Archiv stehen.  
**Nachteile:** Unschärfere Treffer; falsch-positive oder fehlende Treffer möglich.

---

### Optionale Fallback-Suche

Checkbox: **„Zusätzlich nach Versicherungsnummer suchen, wenn obige Zuordnung keine Treffer liefert“**

- Verfügbar bei Modus **Custom Field** oder **Tag**, nicht bei reiner Volltext-Zuordnung.
- Ablauf: Zuerst Hauptmodus (ID/Tag); nur bei **0 Treffern** zusätzlich Volltext nach Versicherungsnummer.
- Sinnvoll für Übergangsphasen (alte Dokumente nur per Nummer, neue per Custom Field).

---

## Zuordnung in Paperless pflegen

### Vertrags-ID in AssureWallos finden

1. Vertrag öffnen oder anlegen.
2. **„Dies ist eine Versicherung“** aktivieren.
3. Vertrag **speichern**.
4. Unter dem Häkchen erscheint z. B. **Vertrags-ID: 42** (Hinweis zu Tag `aw-sub-42` / Custom Field).

Ohne gespeicherten Vertrag gibt es noch keine ID — das Paperless-Archiv zeigt dann den Hinweis, zuerst zu speichern.

### Workflow-Beispiel (Custom Field)

1. Kunde/Versicherung in AssureWallos anlegen → Vertrags-ID **58** notieren.
2. Police in Paperless importieren.
3. In Paperless am Dokument Custom Field `assure_subscription_id` = **58** setzen.
4. In AssureWallos Vertrag **58** öffnen → **Paperless-Archiv** sollte das Dokument listen.

### Mehrere Dokumente pro Vertrag

Beliebig viele Paperless-Dokumente können dieselbe Vertrags-ID (Custom Field), denselben Tag oder dieselbe Versicherungsnummer tragen — alle erscheinen im Archiv.

---

## Leistung (Cache)

**Cache (Sekunden, 0 = aus)** — Standard **300** (5 Minuten).

- Zwischenspeichert die **Liste** der Paperless-Treffer pro Vertrag und Benutzer.
- Reduziert wiederholte API-Aufrufe beim erneuten Öffnen desselben Vertrags.
- **0** = kein Cache (immer live von Paperless; höhere Last, aktuellste Liste).
- Nach Änderungen in Paperless kann es bis zur Cache-Dauer dauern, bis AssureWallos neue Dokumente zeigt (Vertrag schließen und später erneut öffnen, oder Cache-Zeit verkürzen).

Vorschau-Thumbnails nutzen einen separaten, kurzen Browser-Cache über den AssureWallos-Proxy.

---

## Netzwerk, Docker und SSRF

AssureWallos ruft Paperless **serverseitig** auf. Der Server muss die konfigurierte Basis-URL per HTTP/HTTPS erreichen können.

### Docker

| Szenario | Empfehlung |
|----------|------------|
| AssureWallos und Paperless im **gleichen** Docker-Netz | Basis-URL z. B. `http://paperless:8000` (Service-Name aus Compose) |
| Paperless nur in anderem Netz / anderer Host | Host-IP oder interner DNS; ggf. Netzwerk verbinden (`docker network connect`) |
| Nur Reverse Proxy öffentlich | Basis-URL = URL, die **vom AssureWallos-Container** aus erreichbar ist (oft interne Proxy-URL oder öffentliche URL) |

Ausführlicher Docker-Kontext: [DOCKER-ENDNUTZER.md](DOCKER-ENDNUTZER.md#paperless-ngx-optional).

### Webhook-Allowlist (SSRF-Schutz)

Wallos blockiert Server-zu-Server-Aufrufe auf private Adressen, sofern der Ziel-Host nicht freigegeben ist.

1. Wallos **Administration** (nicht „Einstellungen“) öffnen — nur für Admins.
2. Abschnitt **Sicherheit** / Sicherheitseinstellungen.
3. Feld **Webhook-Allowlist** (lokale Webhook-Benachrichtigungen): Hostname, IP oder `host:port` eintragen, z. B.  
   `paperless`, `paperless:8000`, `192.168.1.50`, `paperless.home.lan`
4. Sicherheitseinstellungen **speichern**.

Ohne Eintrag erscheinen Fehler wie *„Paperless-URL ist nicht erlaubt (SSRF-Schutz)“* trotz korrekter URL.

---

## Test und Fehlerbehebung

| Symptom | Mögliche Ursache | Maßnahme |
|--------|------------------|----------|
| Abschnitt Paperless fehlt in Einstellungen | Nicht User-ID 1 | Mit Admin-Konto anmelden |
| Speichern ohne Wirkung | Falscher Button | **Paperless-Einstellungen speichern** verwenden |
| Token „verschwindet“ | Normal | Feld leer, Hinweis „Token ist gespeichert“ |
| Verbindungstest schlägt fehl | URL, Token, Netz, Allowlist | URL ohne `/` am Ende; Token neu; Allowlist; Container-Netz |
| Archiv leer, Paperless hat Dokumente | Falsche ID/Tag/Nummer | Vertrags-ID im Formular mit Paperless abgleichen |
| Archiv erscheint nicht | Paperless deaktiviert / kein Versicherungsvertrag | Schalter in Einstellungen; „Dies ist eine Versicherung“ |
| „Bitte speichern …“ | Neuer Vertrag | Einmal speichern, dann ID in Paperless setzen |
| Alte Treffer nach Paperless-Änderung | Cache | Warten (Cache-Zeit) oder Cache auf 0 setzen |
| Vorschau grau / leer | Thumb-API, Rechte | Verbindungstest; Dokument in Paperless öffnen; Logs prüfen |
| Link „In Paperless“ funktioniert nicht | Basis-URL für Browser falsch | Öffentliche URL eintragen, wenn Nutzer nicht auf interne Hosts zugreifen |

### Logs

Bei Verbindungsproblemen Server-Logs von AssureWallos prüfen (Einträge mit `AssureWallos Paperless`).

### API manuell prüfen (optional)

Vom gleichen Host wie AssureWallos:

```bash
curl -s -H "Authorization: Token IHR_TOKEN" "https://paperless.example.de/api/documents/?page_size=1"
```

Antwort sollte JSON mit `results` sein.

---

## Sicherheit

- API-Token nur serverseitig in der Datenbank; wird nicht an normale Endnutzer ausgeliefert.
- Thumbnails laufen über einen AssureWallos-Proxy: Es werden nur Bilder für Dokumente ausgeliefert, die zum angefragten Vertrag gehören.
- Paperless-URL unterliegt dem gleichen **SSRF-Schutz** wie Webhooks (Allowlist).
- AssureWallos lädt **keine** Dokumente in Paperless hoch — Angriffsfläche Upload entfällt.
- Token regelmäßig rotieren (in Paperless neu erzeugen, in AssureWallos ersetzen und speichern).

---

## Siehe auch

- [PAPERLESS-BENUTZER.md](PAPERLESS-BENUTZER.md) — Nutzung im Versicherungsformular
- [DOCKER-ENDNUTZER.md](DOCKER-ENDNUTZER.md) — Docker, Volumes, Netzwerk
- [Paperless-ngx API-Dokumentation](https://docs.paperless-ngx.com/api/)
