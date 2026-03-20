# Schützenwettbewerb - Wettkampf-Verwaltung

Webanwendung zur Organisation von Schützenwettkämpfen mit Vue.js Frontend, PHP Backend und MariaDB Datenbank.

## Funktionen

- **Events verwalten** – Wettkämpfe anlegen, bearbeiten und löschen. Events können aus mehreren Terminen bestehen.
- **Schützen-Datenbank** – Schützen mit Vor-/Nachname, Jahrgang, Geschlecht, Vereinszugehörigkeit, E-Mail und Telefon verwalten.
- **Teilnehmer zuordnen** – Schützen zu Events hinzufügen und entfernen.
- **Ergebnisse erfassen** – Punkte pro Schütze, Event und Termin erfassen und bearbeiten.
- **Anmeldung & Zeitslots** – Öffentliche Registrierung für Events mit 15-Minuten-Zeitslots. Anmeldung als Einzelschütze oder Vereinsgruppe möglich.
- **Auswertung** – Reports nach Event (Rangliste), nach Schütze (alle Ergebnisse) und nach Altersklasse.
- **Altersklassen** – Konfigurierbare Gruppen nach Geburtsjahrgängen und Geschlecht (m/w/alle).

## Technologie-Stack

| Komponente | Technologie |
|------------|-------------|
| Frontend | Vue.js 3 + Vite + Vue Router + Axios |
| Backend | PHP 8.x REST API |
| Datenbank | MariaDB / MySQL |
| Server | PHP Built-in Server mit kombiniertem Router |

---

## Voraussetzungen

Folgende Software muss auf dem System installiert sein:

- **PHP 8.0** oder höher (mit PDO und MySQL-Extension)
- **MariaDB 10.x** oder **MySQL 5.7+**
- **Node.js 18+** und **npm**
- **Git**

### PHP-Extensions prüfen

```bash
php -m | grep -i pdo
```

Es sollten `pdo_mysql` und `PDO` in der Ausgabe erscheinen. Falls nicht:

```bash
# Ubuntu/Debian
sudo apt install php-mysql php-pdo

# CentOS/RHEL
sudo yum install php-mysqlnd php-pdo
```

---

## Installation Schritt für Schritt

### 1. Repository klonen

```bash
git clone https://github.com/caemmerer82-cloud/Sch-tzenverein.git
cd Sch-tzenverein
```

### 2. MariaDB / MySQL installieren (falls noch nicht vorhanden)

#### Ubuntu / Debian

```bash
sudo apt update
sudo apt install mariadb-server mariadb-client
sudo systemctl start mariadb
sudo systemctl enable mariadb
```

#### CentOS / RHEL

```bash
sudo yum install mariadb-server mariadb
sudo systemctl start mariadb
sudo systemctl enable mariadb
```

#### macOS (Homebrew)

```bash
brew install mariadb
brew services start mariadb
```

Nach der Installation die Sicherheitskonfiguration durchführen:

```bash
sudo mysql_secure_installation
```

### 3. Datenbank initialisieren

Die Datei `backend/database.sql` enthält das komplette Datenbankschema inklusive Standard-Altersklassen.

```bash
# Als root-Benutzer anmelden und SQL-Datei importieren
mysql -u root -p < backend/database.sql
```

Dies erstellt automatisch:

- Die Datenbank `schuetzenwettbewerb` (UTF-8 / utf8mb4)
- **8 Tabellen:**
  - `events` – Wettkampf-Events
  - `event_dates` – Termine pro Event
  - `shooters` – Schützendatenbank
  - `event_participants` – Teilnehmer pro Event
  - `scores` – Ergebnisse / Punkte
  - `time_slots` – 15-Minuten-Zeitslots (automatisch generiert)
  - `slot_reservations` – Zeitslot-Reservierungen
  - `age_groups` – Altersklassen-Konfiguration
- **Standard-Altersklassen:** Schüler, Jugend, Junioren, Herren, Damen, Senioren, Seniorinnen, Altersklasse

#### Datenbank-Benutzer anlegen (empfohlen)

Für den Produktionsbetrieb sollte ein eigener Datenbank-Benutzer angelegt werden:

```bash
mysql -u root -p
```

```sql
CREATE USER 'schuetzen'@'localhost' IDENTIFIED BY 'DEIN_SICHERES_PASSWORT';
GRANT ALL PRIVILEGES ON schuetzenwettbewerb.* TO 'schuetzen'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

#### Prüfen ob die Datenbank korrekt angelegt wurde

```bash
mysql -u root -p -e "USE schuetzenwettbewerb; SHOW TABLES;"
```

Erwartete Ausgabe:

```
+----------------------------------+
| Tables_in_schuetzenwettbewerb    |
+----------------------------------+
| age_groups                       |
| event_dates                      |
| event_participants               |
| events                           |
| scores                           |
| shooters                         |
| slot_reservations                |
| time_slots                       |
+----------------------------------+
```

### 4. Backend konfigurieren

Die Datenbank-Zugangsdaten werden über **Umgebungsvariablen** konfiguriert. Setze diese vor dem Starten des Servers:

```bash
export DB_HOST=localhost
export DB_NAME=schuetzenwettbewerb
export DB_USER=schuetzen
export DB_PASS=DEIN_SICHERES_PASSWORT
```

Alternativ kannst du eine `.env`-Datei im Projektverzeichnis anlegen (wird nicht ins Repository committed):

```bash
cat > .env << 'EOF'
export DB_HOST=localhost
export DB_NAME=schuetzenwettbewerb
export DB_USER=schuetzen
export DB_PASS=DEIN_SICHERES_PASSWORT
EOF

# Variablen laden
source .env
```

| Variable | Beschreibung | Standardwert |
|----------|-------------|--------------|
| `DB_HOST` | Datenbank-Host | `localhost` |
| `DB_NAME` | Datenbankname | `schuetzenwettbewerb` |
| `DB_USER` | Datenbank-Benutzer | `root` |
| `DB_PASS` | Datenbank-Passwort | *(leer)* |

### 5. Frontend bauen

```bash
cd frontend
npm install
npm run build
cd ..
```

Der Build-Prozess erstellt das Verzeichnis `frontend/dist/` mit den optimierten, statischen Dateien.

### 6. Server starten

```bash
cd backend
php -S 0.0.0.0:8080 router.php
```

Die Anwendung ist jetzt erreichbar unter: **http://localhost:8080**

Der PHP-Router (`router.php`) übernimmt zwei Aufgaben:
- **API-Anfragen** (`/api/*`) werden an die PHP REST API weitergeleitet
- **Alle anderen Anfragen** liefern die Vue.js Frontend-Dateien aus `frontend/dist/`

> **Hinweis:** Für den Produktionsbetrieb empfiehlt sich ein Webserver wie **Apache** oder **Nginx** anstelle des PHP Built-in Servers.

---

## Schnellstart (Zusammenfassung)

```bash
# 1. Repository klonen
git clone https://github.com/caemmerer82-cloud/Sch-tzenverein.git
cd Sch-tzenverein

# 2. Datenbank einrichten
mysql -u root -p < backend/database.sql

# 3. Datenbank-Benutzer anlegen (optional, im MySQL-Prompt)
# CREATE USER 'schuetzen'@'localhost' IDENTIFIED BY 'DEIN_PASSWORT';
# GRANT ALL PRIVILEGES ON schuetzenwettbewerb.* TO 'schuetzen'@'localhost';
# FLUSH PRIVILEGES;

# 4. Frontend bauen
cd frontend && npm install && npm run build && cd ..

# 5. Umgebungsvariablen setzen
export DB_USER=root
export DB_PASS=dein_passwort

# 6. Server starten
cd backend && php -S 0.0.0.0:8080 router.php

# App öffnen: http://localhost:8080
```

---

## Entwicklung

Für die Frontend-Entwicklung mit Hot-Reload:

```bash
# Terminal 1: Backend starten
cd backend
php -S 0.0.0.0:8080 router.php

# Terminal 2: Frontend Dev-Server mit Hot-Reload
cd frontend
npm run dev
```

Der Vite Dev-Server startet unter `http://localhost:5173` und leitet API-Anfragen automatisch an das Backend weiter.

---

## Projektstruktur

```
Sch-tzenverein/
├── backend/
│   ├── api/
│   │   ├── index.php          # API-Router (leitet Anfragen weiter)
│   │   ├── events.php         # Event-Endpunkte (CRUD + Termine + Teilnehmer)
│   │   ├── shooters.php       # Schützen-Endpunkte (CRUD)
│   │   ├── scores.php         # Ergebnis-Endpunkte (CRUD)
│   │   ├── timeslots.php      # Zeitslot-Endpunkte (automatische Generierung)
│   │   ├── reservations.php   # Reservierungs-Endpunkte (CRUD)
│   │   ├── age_groups.php     # Altersklassen-Endpunkte (CRUD)
│   │   └── reports.php        # Auswertungs-Endpunkte
│   ├── config.php             # DB-Verbindung & Hilfsfunktionen
│   ├── database.sql           # Komplettes Datenbankschema
│   └── router.php             # Kombinierter Router (API + Frontend)
├── frontend/
│   ├── src/
│   │   ├── views/
│   │   │   ├── EventsView.vue       # Event-Übersicht & Erstellung
│   │   │   ├── EventDetailView.vue   # Event-Details, Teilnehmer & Ergebnisse
│   │   │   ├── ShootersView.vue      # Schützen-Verwaltung
│   │   │   ├── RegistrationView.vue  # Anmeldung & Zeitslot-Buchung
│   │   │   ├── ReportsView.vue       # Auswertungen
│   │   │   └── AgeGroupsView.vue     # Altersklassen-Konfiguration
│   │   ├── services/
│   │   │   └── api.js               # API-Service (Axios)
│   │   ├── router/
│   │   │   └── index.js             # Vue Router Konfiguration
│   │   ├── App.vue                  # Haupt-Komponente mit Navigation
│   │   ├── main.js                  # Vue.js Einstiegspunkt
│   │   └── style.css                # Globale Styles
│   ├── index.html                   # HTML-Einstiegspunkt
│   ├── package.json                 # npm Dependencies
│   └── vite.config.js               # Vite Konfiguration
├── .gitignore
└── README.md
```

---

## API-Referenz

### Events

| Methode | Pfad | Beschreibung |
|---------|------|--------------|
| `GET` | `/api/events` | Alle Events auflisten |
| `POST` | `/api/events` | Neues Event erstellen |
| `GET` | `/api/events/{id}` | Event-Details abrufen |
| `PUT` | `/api/events/{id}` | Event bearbeiten |
| `DELETE` | `/api/events/{id}` | Event löschen |
| `POST` | `/api/events/{id}/dates` | Termin hinzufügen |
| `DELETE` | `/api/events/{id}/dates/{dateId}` | Termin entfernen |
| `POST` | `/api/events/{id}/participants/{shooterId}` | Teilnehmer hinzufügen |
| `DELETE` | `/api/events/{id}/participants/{shooterId}` | Teilnehmer entfernen |

### Schützen

| Methode | Pfad | Beschreibung |
|---------|------|--------------|
| `GET` | `/api/shooters` | Alle Schützen auflisten |
| `POST` | `/api/shooters` | Neuen Schützen erstellen |
| `PUT` | `/api/shooters/{id}` | Schützen bearbeiten |
| `DELETE` | `/api/shooters/{id}` | Schützen löschen |

### Ergebnisse

| Methode | Pfad | Beschreibung |
|---------|------|--------------|
| `GET` | `/api/scores?event_id={id}` | Ergebnisse nach Event filtern |
| `GET` | `/api/scores?shooter_id={id}` | Ergebnisse nach Schütze filtern |
| `POST` | `/api/scores` | Ergebnis erfassen |
| `PUT` | `/api/scores/{id}` | Ergebnis bearbeiten |
| `DELETE` | `/api/scores/{id}` | Ergebnis löschen |

### Zeitslots & Reservierungen

| Methode | Pfad | Beschreibung |
|---------|------|--------------|
| `GET` | `/api/timeslots?event_date_id={id}` | Zeitslots für einen Termin abrufen |
| `GET` | `/api/reservations` | Alle Reservierungen auflisten |
| `POST` | `/api/reservations` | Neue Reservierung erstellen |
| `DELETE` | `/api/reservations/{id}` | Reservierung stornieren |

### Altersklassen

| Methode | Pfad | Beschreibung |
|---------|------|--------------|
| `GET` | `/api/age-groups` | Alle Altersklassen auflisten |
| `POST` | `/api/age-groups` | Neue Altersklasse erstellen |
| `PUT` | `/api/age-groups/{id}` | Altersklasse bearbeiten |
| `DELETE` | `/api/age-groups/{id}` | Altersklasse löschen |

### Auswertungen

| Methode | Pfad | Beschreibung |
|---------|------|--------------|
| `GET` | `/api/reports/event?event_id={id}` | Event-Auswertung (Rangliste nach Punkten) |
| `GET` | `/api/reports/shooter?shooter_id={id}` | Schützen-Auswertung (alle Events & Ergebnisse) |
| `GET` | `/api/reports/age-group?event_id={id}` | Altersklassen-Auswertung |

---

## Fehlerbehebung

### Datenbank-Verbindung schlägt fehl

```
Datenbankverbindung fehlgeschlagen: SQLSTATE[HY000] [1045] Access denied
```

**Lösung:** Prüfe die Umgebungsvariablen:
```bash
echo "DB_USER=$DB_USER"
echo "DB_PASS ist gesetzt: $([ -n "$DB_PASS" ] && echo 'ja' || echo 'nein')"
```

### Port bereits belegt

```
Failed to listen on 0.0.0.0:8080
```

**Lösung:** Verwende einen anderen Port:
```bash
php -S 0.0.0.0:3000 router.php
```

### Frontend-Dateien nicht gefunden (Seite zeigt "Not Found")

**Lösung:** Das Frontend wurde noch nicht gebaut:
```bash
cd frontend && npm install && npm run build
```

### SQL-Import schlägt fehl

```
ERROR 1007 (HY000): Can't create database 'schuetzenwettbewerb'; database exists
```

**Lösung:** Die Datenbank existiert bereits. Das ist kein Problem – das Schema nutzt `CREATE TABLE IF NOT EXISTS` und überspringt vorhandene Tabellen. Die Standard-Altersklassen werden jedoch erneut eingefügt. Um die Datenbank komplett neu aufzusetzen:

```bash
mysql -u root -p -e "DROP DATABASE schuetzenwettbewerb;"
mysql -u root -p < backend/database.sql
```
