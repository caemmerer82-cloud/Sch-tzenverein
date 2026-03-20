CREATE DATABASE IF NOT EXISTS schuetzenwettbewerb CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE schuetzenwettbewerb;

-- Events (Wettkämpfe)
CREATE TABLE IF NOT EXISTS events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    location VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Event-Termine (ein Event kann mehrere Termine haben)
CREATE TABLE IF NOT EXISTS event_dates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    event_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Schützen
CREATE TABLE IF NOT EXISTS shooters (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    birth_year INT NOT NULL,
    gender ENUM('m', 'w', 'd') NOT NULL DEFAULT 'm',
    club_name VARCHAR(255) DEFAULT NULL,
    email VARCHAR(255) DEFAULT NULL,
    phone VARCHAR(50) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Event-Teilnehmer (Verknüpfung Event <-> Schütze)
CREATE TABLE IF NOT EXISTS event_participants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    shooter_id INT NOT NULL,
    registered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    FOREIGN KEY (shooter_id) REFERENCES shooters(id) ON DELETE CASCADE,
    UNIQUE KEY unique_participant (event_id, shooter_id)
) ENGINE=InnoDB;

-- Ergebnisse / Punkte
CREATE TABLE IF NOT EXISTS scores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    event_date_id INT DEFAULT NULL,
    shooter_id INT NOT NULL,
    points DECIMAL(10,2) NOT NULL DEFAULT 0,
    notes TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    FOREIGN KEY (event_date_id) REFERENCES event_dates(id) ON DELETE SET NULL,
    FOREIGN KEY (shooter_id) REFERENCES shooters(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Zeitslots (15-Minuten-Blöcke pro Event-Termin)
CREATE TABLE IF NOT EXISTS time_slots (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_date_id INT NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    max_participants INT NOT NULL DEFAULT 4,
    FOREIGN KEY (event_date_id) REFERENCES event_dates(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Zeitslot-Reservierungen
CREATE TABLE IF NOT EXISTS slot_reservations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    time_slot_id INT NOT NULL,
    shooter_id INT DEFAULT NULL,
    group_name VARCHAR(255) DEFAULT NULL,
    is_group TINYINT(1) NOT NULL DEFAULT 0,
    participant_count INT NOT NULL DEFAULT 1,
    contact_name VARCHAR(255) DEFAULT NULL,
    contact_email VARCHAR(255) DEFAULT NULL,
    contact_phone VARCHAR(50) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (time_slot_id) REFERENCES time_slots(id) ON DELETE CASCADE,
    FOREIGN KEY (shooter_id) REFERENCES shooters(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Altersklassen-Konfiguration
CREATE TABLE IF NOT EXISTS age_groups (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    min_birth_year INT NOT NULL,
    max_birth_year INT NOT NULL,
    gender ENUM('m', 'w', 'all') NOT NULL DEFAULT 'all',
    sort_order INT NOT NULL DEFAULT 0
) ENGINE=InnoDB;

-- Standard-Altersklassen einfügen
INSERT INTO age_groups (name, min_birth_year, max_birth_year, gender, sort_order) VALUES
('Schüler', 2012, 2020, 'all', 1),
('Jugend', 2008, 2011, 'all', 2),
('Junioren', 2004, 2007, 'all', 3),
('Herren', 1970, 2003, 'm', 4),
('Damen', 1970, 2003, 'w', 5),
('Senioren', 1950, 1969, 'm', 6),
('Seniorinnen', 1950, 1969, 'w', 7),
('Altersklasse', 1900, 1949, 'all', 8);
