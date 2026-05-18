# AssureWallos — Docker-Anleitung für Endnutzer

Diese Anleitung gilt für den Betrieb mit [`docker-compose.hub.yaml`](../../docker-compose.hub.yaml) und dem Image von [Docker Hub](https://hub.docker.com/r/bkunzmann/assurewallos) — **ohne** lokalen Build.

Kurzinstallation (Englisch): [README — Installation (Docker Hub)](../../README.md#installation-docker-hub).

---

## Inhalt

- [Erste Schritte](#erste-schritte)
- [PUID und PGID (Dateirechte)](#puid-und-pgid-dateirechte)
- [Persistente Datenordner](#persistente-datenordner)
- [Upload-Ordner auf dem Host ändern](#upload-ordner-auf-dem-host-ändern)
- [Weitere Pfade ändern (DB, Logos)](#weitere-pfade-ändern-db-logos)
- [Port, Zeitzone und Image-Version](#port-zeitzone-und-image-version)
- [Healthcheck (optional)](#healthcheck-optional)
- [Update auf neue Version](#update-auf-neue-version)
- [Backup](#backup)
- [Fehlerbehebung](#fehlerbehebung)

---

## Erste Schritte

1. Verzeichnis anlegen und `docker-compose.hub.yaml` hineinlegen (oder dieses Repository klonen).
2. Datenordner erstellen:

   ```bash
   mkdir -p db logos images/uploads/insurance_docs
   ```

3. `PUID`/`PGID` anpassen (siehe unten) — die Vorlage nutzt `1000`/`1000`.
4. Starten:

   ```bash
   docker compose -f docker-compose.hub.yaml pull
   docker compose -f docker-compose.hub.yaml up -d
   ```

5. App öffnen: **http://localhost:8283/** (Standard-Port **8283**, damit parallel laufendes Wallos auf 8282 nicht kollidiert).

---

## PUID und PGID (Dateirechte)

Der Container startet intern als **root** und setzt beim Start den Benutzer `www-data` auf die UID/GID aus den Umgebungsvariablen `PUID` und `PGID`. So stimmen Besitzer und Schreibrechte auf den **gemounteten Host-Ordnern** (DB, Logos, Versicherungsdokumente) überein.

### Werte ermitteln

Auf dem Linux-Host, unter dem die Daten liegen sollen:

```bash
id -u   # → PUID
id -g   # → PGID
```

In `docker-compose.hub.yaml` eintragen:

```yaml
environment:
  PUID: "1000"
  PGID: "1000"
```

### Wenn PUID/PGID fehlen

Werden die Variablen **nicht** gesetzt, verwendet das Image standardmäßig **82:82** (Alpine/`www-data`). Die App läuft oft trotzdem; auf dem Host erscheinen Dateien dann als User **82**, was Backup und manuelles Bearbeiten erschwert.

### Wichtig: nicht `user:` in Compose verwenden

```yaml
# NICHT verwenden — bricht Startup (chown, nginx, Cron) in diesem Image:
user: "1000:1000"
```

Stattdessen immer **`PUID`/`PGID`** nutzen.

### NAS / Synology

Die UID/GID der Freigabe oder des Docker-Benutzers verwenden (in der NAS-Dokumentation oder per `id` auf der Shell).

---

## Persistente Datenordner

| Host-Pfad (links, **anpassbar**) | Container-Pfad (rechts, **nicht ändern**) | Inhalt |
|----------------------------------|-------------------------------------------|--------|
| `./db` | `/var/www/html/db` | SQLite-Datenbank |
| `./logos` | `/var/www/html/images/uploads/logos` | Abo-/Anbieter-Logos |
| `./images/uploads/insurance_docs` | `/var/www/html/images/uploads/insurance_docs` | Versicherungs-Uploads (PDF, Bilder) |

- **Links** = beliebiger Ordner auf Ihrem Rechner/Server.
- **Rechts** = fest im Image und in der App (`Ins_Repository`); eine Änderung erfordert Code-Anpassungen und ist für Endnutzer nicht vorgesehen.

In der Datenbank werden für Dokumente nur **Dateinamen** gespeichert, keine absoluten Host-Pfade. Beim Umzug des Host-Ordners reicht Kopieren der Dateien + Anpassen des Volume-Eintrags.

---

## Upload-Ordner auf dem Host ändern

Beispiel: von `./images/uploads/insurance_docs` nach `/opt/assurewallos/dokumente`.

### 1. Container stoppen

```bash
docker compose -f docker-compose.hub.yaml down
```

### 2. Dateien kopieren

Nicht nur verschieben, bis der neue Mount getestet ist:

```bash
mkdir -p /opt/assurewallos/dokumente
cp -a ./images/uploads/insurance_docs/. /opt/assurewallos/dokumente/
```

Den **Quellpfad** anpassen, wenn der alte Ordner woanders lag.

### 3. Compose-Datei anpassen (nur linke Seite)

```yaml
volumes:
  - /opt/assurewallos/dokumente:/var/www/html/images/uploads/insurance_docs
```

### 4. Rechte setzen

Der Ordner muss für `PUID`/`PGID` aus der Compose-Datei schreibbar sein:

```bash
sudo chown -R 1000:1000 /opt/assurewallos/dokumente
```

(`1000` durch Ihre Werte von `id -u` / `id -g` ersetzen.)

Beim nächsten Container-Start kann `startup.sh` die Besitzrechte der Mounts erneut an `www-data` (Ihre PUID/PGID) anpassen.

### 5. Container starten

```bash
docker compose -f docker-compose.hub.yaml up -d
```

### 6. Prüfen

- Bestehendes Versicherungsdokument in der App öffnen oder herunterladen.
- Optional einen neuen Upload testen.

### 7. Alten Ordner löschen

Erst wenn alles funktioniert.

`db` und `logos` bleiben unabhängig; nur der Eintrag für `insurance_docs` muss geändert werden.

---

## Weitere Pfade ändern (DB, Logos)

Gleiches Vorgehen wie beim Upload-Ordner:

1. `docker compose … down`
2. Daten mit `cp -a` in den neuen Host-Ordner kopieren
3. Nur den **linken** Pfad im `volumes`-Block ändern, z. B.:

   ```yaml
   - /opt/assurewallos/db:/var/www/html/db
   - /opt/assurewallos/logos:/var/www/html/images/uploads/logos
   ```

4. Rechte (`chown`) passend zu PUID/PGID
5. `up -d` und App testen

---

## Port, Zeitzone und Image-Version

### Port

Standard in der Vorlage: **8283** → Container-Port 80.

Nur AssureWallos, kein Wallos parallel? In der Compose-Datei z. B.:

```yaml
ports:
  - "8282:80/tcp"
```

### Zeitzone

```yaml
environment:
  TZ: Europe/Berlin
```

### Image-Version

Docker-Tag **ohne** `v` (Git-Release-Tag ist `v0.2.0`, Image-Tag ist `0.2.0`):

```yaml
image: bkunzmann/assurewallos:0.2.0
```

Ad-hoc-Image (manueller CI-Lauf): `bkunzmann/assurewallos:manual` oder `bkunzmann/assurewallos:sha-<commit>`. Release-Images nur über Git-Tag `vX.Y.Z`.

---

## Healthcheck (optional)

Das Image enthält einen Healthcheck (`health.php`). In den meisten Fällen kann er aktiv bleiben.

Zum **Deaktivieren** (z. B. ältere Docker-Engine, weniger „unhealthy“-Meldungen in `docker ps`) unter `services.wallos`:

```yaml
healthcheck:
  disable: true
```

Alternativ (wie im Wallos-README):

```yaml
healthcheck:
  test: ["NONE"]
```

---

## Update auf neue Version

1. In `docker-compose.hub.yaml` den `image:`-Tag anpassen (z. B. `0.1.3`).
2. Ausführen:

   ```bash
   docker compose -f docker-compose.hub.yaml pull
   docker compose -f docker-compose.hub.yaml up -d
   ```

3. Bei Aufforderung Migration im Browser:  
   `http://<ihr-host>:8283/endpoints/db/migrate.php`

---

## Backup

Vor größeren Änderungen (Pfad-Umzug, Update) sichern:

- Ordner **`db/`** (komplette SQLite-Datei)
- Ordner **`logos/`**
- Ordner **`images/uploads/insurance_docs/`** (bzw. Ihr angepasster Host-Pfad für Versicherungsdokumente)
- Ihre angepasste **`docker-compose.hub.yaml`**

---

## Fehlerbehebung

| Problem | Mögliche Lösung |
|--------|------------------|
| Upload schlägt fehl / „Permission denied“ | `PUID`/`PGID` mit `id -u`/`id -g` prüfen; Host-Ordner beschreibbar machen (`chown`) |
| Dokumente nach Umzug weg | Dateien kopiert? Nur **linken** Volume-Pfad geändert, rechten Pfad unverändert gelassen? |
| Container „unhealthy“ | Kurz warten (`start-period` im Image); ggf. Healthcheck deaktivieren (siehe oben) |
| Port belegt | Anderen Host-Port in `ports:` wählen (z. B. 8284) |
| Alte Dateien gehören User 82 | `PUID`/`PGID` setzen und Container neu starten; ggf. einmalig `chown` auf dem Host |

---

## Paperless-ngx (optional)

AssureWallos kann im Versicherungsformular **bereits archivierte** Dokumente aus [Paperless-ngx](https://docs.paperless-ngx.com/) anzeigen (nur Lesen, kein Upload aus AssureWallos).

**Ausführliche Anleitungen:**

- **[PAPERLESS-ADMIN.md](PAPERLESS-ADMIN.md)** — Einrichtung, Zuordnungsmodi (Custom Field, Tag, Volltext), Docker/SSRF, Fehlerbehebung
- **[PAPERLESS-BENUTZER.md](PAPERLESS-BENUTZER.md)** — Nutzung im Versicherungsformular (Liste/Vorschau, Vertrags-ID)

**Kurz:** API-Token in Paperless → AssureWallos **Einstellungen** → **Paperless-ngx** → speichern & testen → Dokumente in Paperless mit **Vertrags-ID** verknüpfen. Bei Docker/LAN: Hostname in der Wallos-**Administration** → **Webhook-Allowlist** eintragen.

---

## Siehe auch

- [PAPERLESS-ADMIN.md](PAPERLESS-ADMIN.md) — Paperless einrichten (Administrator)
- [PAPERLESS-BENUTZER.md](PAPERLESS-BENUTZER.md) — Paperless-Archiv nutzen (Benutzer)
- [SICHERHEIT.md](SICHERHEIT.md) — Reverse Proxy, HTTPS, Synology, Firewall, Backups
- [README (Installation)](../../README.md#installation-docker-hub)
- [CHANGELOG-ASSURE.md](../../CHANGELOG-ASSURE.md)
- Docker Hub: https://hub.docker.com/r/bkunzmann/assurewallos
