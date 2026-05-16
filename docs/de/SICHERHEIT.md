# AssureWallos — Sicherheitshinweise (Betrieb)

Empfehlungen für den **öffentlichen oder halböffentlichen Betrieb** von AssureWallos (Docker, Reverse Proxy, Synology/NAS, VPS). Die App enthält Finanz- und Versicherungsdaten — behandeln Sie den Host entsprechend.

**Basis-Installation:** [DOCKER-ENDNUTZER.md](DOCKER-ENDNUTZER.md)

---

## Inhalt

- [Kurz-Checkliste](#kurz-checkliste)
- [Reverse Proxy und HTTPS](#reverse-proxy-und-https)
- [Synology DSM (Beispiel)](#synology-dsm-beispiel)
- [Netzwerk und Ports](#netzwerk-und-ports)
- [Zugang zur Anwendung](#zugang-zur-anwendung)
- [App-Einstellungen](#app-einstellungen)
- [Was AssureWallos bereits absichert](#was-assurewallos-bereits-absichert)
- [Docker und Daten](#docker-und-daten)
- [Host und NAS](#host-und-nas)
- [Updates und Monitoring](#updates-und-monitoring)
- [Optional / erweitert](#optional--erweitert)
- [Kurz testen nach dem Setup](#kurz-testen-nach-dem-setup)

---

## Kurz-Checkliste

- [ ] Nur **HTTPS (443)** von außen; Container-Port (**8283**) nicht öffentlich
- [ ] Reverse Proxy mit **X-Forwarded-Proto: https** (und sinnvollen Forward-Headern)
- [ ] **TLS** aktuell (Let’s Encrypt o. Ä.), HSTS wenn Domain dauerhaft per HTTPS
- [ ] **Offene Registrierung** deaktiviert (nach Anlage des ersten Accounts)
- [ ] **Starkes Passwort**, optional **TOTP/2FA** in der App
- [ ] Zugriff eingeschränkt: **VPN**, **IP-Allowlist** oder zusätzliche Auth vor der App
- [ ] **Backups** von `db/`, `logos/`, Versicherungsdokumenten — ideal verschlüsselt
- [ ] **DSM/Host** mit 2FA, Firewall, keine unnötig offenen Dienste
- [ ] Image-Version **gepinnt**, Updates geplant

---

## Reverse Proxy und HTTPS

Typisches Setup (wie bei Synology):

| Öffentlich | Intern |
|------------|--------|
| `https://assurewallos.example.com` (Port 443) | `http://127.0.0.1:8283` (Docker/Compose) |

Der Proxy beendet TLS; der Container spricht intern HTTP.

### Proxy-Header setzen

In den erweiterten Einstellungen des Reverse Proxy (Custom Header), falls nicht automatisch gesetzt:

| Header | Beispielwert | Zweck |
|--------|--------------|--------|
| `X-Forwarded-Proto` | `https` | App/Redirects erkennen HTTPS |
| `X-Forwarded-Host` | `assurewallos.example.com` | Korrekte Hostnamen |
| `X-Real-IP` | Client-IP | Logging, ggf. Rate-Limits |

Ohne `X-Forwarded-Proto` kann die Anwendung intern „HTTP“ sehen (Redirects, Cookie-Verhalten).

### TLS und HSTS

- Zertifikat für die Subdomain (z. B. **Let’s Encrypt** über DSM)
- Alte Protokolle abschalten (**TLS 1.2+**, bevorzugt **1.3**)
- **HSTS** nur aktivieren, wenn die Seite dauerhaft unter HTTPS erreichbar ist

### Security-Header am Proxy (optional)

Vorsichtig testen — manche Header können UI/Scripts stören:

- `Strict-Transport-Security: max-age=31536000; includeSubDomains`
- `X-Content-Type-Options: nosniff`
- `X-Frame-Options: SAMEORIGIN`
- `Referrer-Policy: strict-origin-when-cross-origin`
- `Content-Security-Policy` nur mit Test (Wallos/AssureWallos nutzt viele eigene Scripts/Styles)

---

## Synology DSM (Beispiel)

Beispiel-Konfiguration, die in der Praxis funktioniert:

- **Quelle:** `assurewallos.myurl.com`, Port **443**, HTTPS
- **Ziel:** `http://localhost:8283` (oder IP des Docker-Hosts)

Zusätzlich empfohlen:

1. **Systemsteuerung → Sicherheit → Firewall:** Port **8283** nicht von außen freigeben; nur **443** (und ggf. SSH/VPN).
2. **DSM:** **2FA** für Administrator-Accounts.
3. **Container Manager:** Compose mit gepinntem Image-Tag, Daten auf Freigabe mit passender **PUID/PGID** ([DOCKER-ENDNUTZER.md](DOCKER-ENDNUTZER.md)).
4. **QuickConnect:** nur wenn bewusst gewünscht; sonst Zugriff lieber über VPN + eigene Domain.
5. **Hyper Backup** (oder äquivalent) für die persistenten Datenordner.

---

## Netzwerk und Ports

### Port 8283 nicht exposen

- Von **außen** sollte nur der Reverse Proxy (**443**) erreichbar sein.
- **8283** nur auf `localhost` / internes Docker-Netz.
- Sonst kann der Dienst am Proxy vorbei aufgerufen werden (andere Header, kein TLS).

### Docker-Compose-Standard

In [`docker-compose.hub.yaml`](../../docker-compose.hub.yaml) ist **8283** der Host-Port (Abstand zu Wallos auf **8282**). Das ist kein Sicherheitsfeature — nur Port-Trennung bei Parallelbetrieb.

---

## Zugang zur Anwendung

Die App hat Login und CSRF; für sensible Daten reicht das im Internet oft **nicht** allein.

| Maßnahme | Nutzen |
|----------|--------|
| **Registrierung geschlossen** | Keine fremden Benutzerkonten |
| **Starkes Admin-Passwort** | Basis-Schutz |
| **TOTP/2FA** (in Wallos/AssureWallos, falls aktiviert) | Schutz bei Passwort-Leak |
| **VPN** (WireGuard/OpenVPN auf der NAS) | App nur aus vertrauenswürdigem Netz |
| **IP-Allowlist** (Firewall/Proxy) | Nur bekannte Netze (z. B. Heim-IP) |
| **Zusätzliche Auth vor dem Proxy** (Authelia, Authentik, Synology SSO) | Zweite Schicht vor PHP |

Für eine **private Familien-Instanz** sind oft am effektivsten: **VPN + geschlossene Registrierung + starkes Passwort**.

### `user:` in Compose

**Nicht** `user: "1000:1000"` statt `PUID`/`PGID` verwenden — das bricht Startup in diesem Image. Siehe [DOCKER-ENDNUTZER.md — PUID/PGID](DOCKER-ENDNUTZER.md#puid-und-pgid-dateirechte).

---

## App-Einstellungen

Nach der Ersteinrichtung im **Admin-Bereich** prüfen:

- **Benutzerregistrierung:** deaktivieren (`registrations_open`), sobald alle Konten angelegt sind (Standard in frischer DB oft geschlossen — trotzdem prüfen).
- **`DEMO_MODE`:** in der Compose-Datei **nicht** setzen.
- **E-Mail-Verifizierung / Passwort-Reset:** nur aktivieren, wenn SMTP korrekt und Domain/URLs stimmen.
- **OIDC:** sinnvoll, wenn ein zentraler Identity Provider (Keycloak, Entra ID, …) vorhanden ist.
- **API-Keys** (Fixer, KI, Benachrichtigungen): minimal vergeben, nicht in Screenshots/Logs teilen.

### Cookies und HTTPS

Session-Cookies nutzen u. a. `httponly` und `SameSite=Lax`. Nach Login in den **Browser-Entwicklertools** prüfen, ob Cookies wie erwartet gesetzt sind. Bei reinem HTTP-Zugriff auf die App (ohne Proxy) fehlt ggf. das `Secure`-Flag — deshalb **nur HTTPS** von außen nutzen.

---

## Was AssureWallos bereits absichert

### Versicherungsdokumente

- Direkter Webzugriff auf `/images/uploads/insurance_docs/` ist in **nginx blockiert** (`deny all`).
- Download nur über **`serve_document.php`** nach **Login** und Berechtigungsprüfung.
- Metadaten in der DB; Dateinamen ohne öffentliche Verzeichnisliste.

### CSRF

Viele schreibende Endpoints erwarten ein gültiges CSRF-Token (Session).

### Upload-Limits

Im Image u. a. begrenzte Upload-Größe (PHP/nginx, z. B. 25 MB) und erlaubte Dokumenttypen im `Ins_Repository`.

---

## Docker und Daten

- **Image-Tag pinnen** (`bkunzmann/assurewallos:0.1.2`), nicht blind `:latest` in Produktion.
- **PUID/PGID** an den Besitzer der Host-Ordner anpassen ([DOCKER-ENDNUTZER.md](DOCKER-ENDNUTZER.md)).
- **Backups** regelmäßig:
  - `db/` (SQLite)
  - `logos/`
  - Versicherungsdokumente (`insurance_docs` bzw. angepasster Host-Pfad)
- Backups **verschlüsselt** und **offsite** speichern.
- Compose-Datei und Secrets (SMTP, API-Keys in der App-DB) nicht unverschlüsselt in öffentliche Repos legen.

---

## Host und NAS

- Betriebssystem/DSM **aktuell halten**
- **Automatische Sperre** / Fail2ban-ähnliche Funktionen, wo verfügbar
- **SSH:** Key statt Passwort, kein Root-Login von außen
- Nur benötigte **Pakete/Dienste** installiert
- Container-Logs bei Auffälligkeiten: `docker compose -f docker-compose.hub.yaml logs`

---

## Updates und Monitoring

1. `image:`-Tag in Compose anheben.
2. `docker compose -f docker-compose.hub.yaml pull && up -d`
3. Migration: `https://<ihre-domain>/endpoints/db/migrate.php`
4. Kurz Login, Upload, ein Versicherungsdokument testen.

Optional:

- **Uptime-Monitoring** (Uptime Kuma, Synology-eigen, …)
- **Zertifikatsablauf** überwachen (Let’s Encrypt erneuert meist automatisch)

---

## Optional / erweitert

- **Rate Limiting** am Proxy gegen Login-Bruteforce (Synology begrenzt; ggf. Traefik/Caddy/nginx davor).
- **Eigene Subdomain** nur für AssureWallos, getrennt von anderen Diensten.
- **WAF** / Geo-Blocking nur bei realem Bedarf (Komplexität vs. Nutzen).
- **Verschlüsselung der SQLite-DB** auf Dateisystem-Ebene (NAS-Verschlüsselung, LUKS) statt App-intern.

---

## Kurz testen nach dem Setup

1. Von **außen:** `https://assurewallos.example.com` — Zertifikat gültig, Login ok.
2. Von **außen:** `http://nas-ip:8283` — sollte **nicht** erreichbar sein (Timeout/verweigert).
3. **Registrierungsseite:** nur erreichbar, wenn bewusst erlaubt.
4. Versicherungsdokument: Download nur eingeloggt; direkte URL auf `/images/uploads/insurance_docs/...` → **403**.
5. Nach Login: Cookies in DevTools prüfen (Session gesetzt).

---

## Siehe auch

- [DOCKER-ENDNUTZER.md](DOCKER-ENDNUTZER.md) — Installation, PUID/PGID, Datenpfade
- [README — Installation](../../README.md#installation-docker-hub)
- [CHANGELOG-ASSURE.md](../../CHANGELOG-ASSURE.md)
