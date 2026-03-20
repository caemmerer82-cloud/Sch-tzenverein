# Schützenwettbewerb - Wettkampf-Verwaltung

Webanwendung zur Organisation von Schützenwettkämpfen.

## Funktionen

- **Events verwalten** - Wettkämpfe anlegen, Termine verwalten, Teilnehmer hinzufügen
- **Schützen-Datenbank** - Schützen mit Jahrgang, Geschlecht und Vereinszugehörigkeit
- **Ergebnisse erfassen** - Punkte pro Schütze und Event erfassen
- **Anmeldung & Zeitslots** - Registrierung für Events mit 15-Minuten-Zeitslots (Einzelschützen/Vereinsgruppen)
- **Auswertung** - Reports nach Event, Schütze und Altersklasse
- **Altersklassen** - Konfigurierbare Gruppen nach Geburtsjahrgängen und Geschlecht

## Technologie

- **Frontend:** Vue.js 3 + Vite + Vue Router
- **Backend:** PHP REST API
- **Datenbank:** MariaDB

## Installation

### Voraussetzungen

- PHP 8.x
- MariaDB / MySQL
- Node.js 18+
- npm

### Datenbank einrichten

```bash
mysql -u root -p < backend/database.sql
```

Dies erstellt die Datenbank `schuetzenwettbewerb` mit dem Benutzer `schuetzen`.

### Frontend installieren

```bash
cd frontend
npm install
npm run build
```

### Server starten

```bash
cd backend
php -S 0.0.0.0:8080 router.php
```

Die Anwendung ist dann unter `http://localhost:8080` erreichbar.

Der PHP-Router (`router.php`) dient sowohl die API (`/api/*`) als auch die Frontend-Dateien aus `frontend/dist/`.

### Entwicklung

Für die Frontend-Entwicklung mit Hot-Reload:

```bash
cd frontend
npm run dev
```

## API-Endpunkte

| Methode | Pfad | Beschreibung |
|---------|------|--------------|
| GET/POST | `/api/events` | Events auflisten/erstellen |
| GET/PUT/DELETE | `/api/events/{id}` | Event bearbeiten/löschen |
| POST/DELETE | `/api/events/{id}/dates/{dateId}` | Termine verwalten |
| POST/DELETE | `/api/events/{id}/participants/{shooterId}` | Teilnehmer verwalten |
| GET/POST | `/api/shooters` | Schützen auflisten/erstellen |
| PUT/DELETE | `/api/shooters/{id}` | Schützen bearbeiten/löschen |
| GET/POST | `/api/scores` | Ergebnisse auflisten/erstellen |
| PUT/DELETE | `/api/scores/{id}` | Ergebnisse bearbeiten/löschen |
| GET | `/api/timeslots` | Zeitslots abrufen |
| GET/POST/DELETE | `/api/reservations` | Reservierungen verwalten |
| GET/POST | `/api/age-groups` | Altersklassen verwalten |
| PUT/DELETE | `/api/age-groups/{id}` | Altersklassen bearbeiten/löschen |
| GET | `/api/reports/event` | Event-Auswertung |
| GET | `/api/reports/shooter` | Schützen-Auswertung |
| GET | `/api/reports/age-group` | Altersklassen-Auswertung |

## Datenbankschema

- `events` - Wettkampf-Events
- `event_dates` - Termine pro Event
- `shooters` - Schützendatenbank
- `event_participants` - Teilnehmer pro Event
- `scores` - Ergebnisse
- `time_slots` - Automatisch generierte 15-Min-Zeitslots
- `slot_reservations` - Zeitslot-Reservierungen
- `age_groups` - Konfigurierbare Altersklassen
