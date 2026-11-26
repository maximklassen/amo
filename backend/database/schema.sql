-- ============================================================
-- ParzellenPlaner – Datenbankschema
-- Import: phpMyAdmin → Importieren → diese Datei, danach seed.sql
-- ACHTUNG: Beim Import werden alle Tabellen gelöscht und neu angelegt.
-- ============================================================
CREATE DATABASE IF NOT EXISTS kleingarten CHARACTER
SET
  utf8mb4 COLLATE utf8mb4_unicode_ci;

USE kleingarten;

-- Reihenfolge wegen Fremdschlüsseln: abhängige Tabellen zuerst löschen
DROP TABLE IF EXISTS meter_readings;

DROP TABLE IF EXISTS meters;

DROP TABLE IF EXISTS posts;

DROP TABLE IF EXISTS work_signups;

DROP TABLE IF EXISTS work_tasks;

DROP TABLE IF EXISTS waitlist;

DROP TABLE IF EXISTS leases;

DROP TABLE IF EXISTS users_data;

DROP TABLE IF EXISTS login_attempts;

DROP TABLE IF EXISTS settings;

DROP TABLE IF EXISTS parcels;

DROP TABLE IF EXISTS users;

-- ------------------------------------------------------------
-- Benutzerkonto (Login-Daten + Rolle)
-- ------------------------------------------------------------
CREATE TABLE
  users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM ('bewerber', 'paechter', 'vorstand') NOT NULL DEFAULT 'bewerber',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
  ) ENGINE = InnoDB;

-- ------------------------------------------------------------
-- Persönliche Daten (1:1 zu users, getrennt wegen DSGVO)
-- ------------------------------------------------------------
CREATE TABLE
  users_data (
    user_id INT UNSIGNED PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    phone VARCHAR(30) NULL,
    street VARCHAR(150) NULL,
    city VARCHAR(100) NULL,
    zip CHAR(5) NULL,
    CONSTRAINT fk_users_data_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
  ) ENGINE = InnoDB;

-- ------------------------------------------------------------
-- Parzellen
-- svg_path: Form der Parzelle auf der Karte (SVG-Pfad)
-- ------------------------------------------------------------
CREATE TABLE
  parcels (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    number VARCHAR(10) NOT NULL UNIQUE,
    size_m2 SMALLINT UNSIGNED NOT NULL,
    has_water BOOLEAN NOT NULL DEFAULT FALSE,
    has_power BOOLEAN NOT NULL DEFAULT FALSE,
    has_hut BOOLEAN NOT NULL DEFAULT FALSE,
    svg_path VARCHAR(500) NULL,
    status ENUM ('frei', 'verpachtet', 'gekündigt') NOT NULL DEFAULT 'frei'
  ) ENGINE = InnoDB;

-- ------------------------------------------------------------
-- Pachtverträge (Historie)
-- end_date NULL  = läuft aktuell
-- end_date in der Zukunft = gekündigt, läuft noch bis zum Datum
-- ------------------------------------------------------------
CREATE TABLE
  leases (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    parcel_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NULL,
    CONSTRAINT fk_leases_parcel FOREIGN KEY (parcel_id) REFERENCES parcels (id) ON DELETE RESTRICT,
    CONSTRAINT fk_leases_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE RESTRICT,
    CONSTRAINT chk_leases_dates CHECK (
      end_date IS NULL
      OR end_date > start_date
    ),
    INDEX idx_leases_parcel (parcel_id),
    INDEX idx_leases_user (user_id)
  ) ENGINE = InnoDB;

-- ------------------------------------------------------------
-- Warteliste
-- Die Position in der Warteschlange wird nicht gespeichert, sondern berechnet:
-- Einträge mit status = 'wartend', sortiert nach applied_at und id.
-- ------------------------------------------------------------
CREATE TABLE
  waitlist (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    pref_min_size SMALLINT UNSIGNED NULL,
    pref_max_size SMALLINT UNSIGNED NULL,
    wants_water BOOLEAN NOT NULL DEFAULT FALSE,
    wants_power BOOLEAN NOT NULL DEFAULT FALSE,
    wants_hut BOOLEAN NOT NULL DEFAULT FALSE,
    status ENUM (
      'wartend',
      'angeboten',
      'angenommen',
      'abgelehnt',
      'zurueckgezogen'
    ) NOT NULL DEFAULT 'wartend',
    offered_parcel_id INT UNSIGNED NULL,
    CONSTRAINT fk_waitlist_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_waitlist_parcel FOREIGN KEY (offered_parcel_id) REFERENCES parcels (id) ON DELETE SET NULL,
    CONSTRAINT chk_waitlist_size CHECK (
      pref_min_size IS NULL
      OR pref_max_size IS NULL
      OR pref_max_size >= pref_min_size
    ),
    INDEX idx_waitlist_queue (status, applied_at),
    INDEX idx_waitlist_user (user_id)
  ) ENGINE = InnoDB;

-- ------------------------------------------------------------
-- Zähler (Strom und Wasser) – gehören zur Parzelle, nicht zum Pächter
-- removed_at NULL = Zähler ist aktuell eingebaut;
-- bei Zählertausch wird der alte mit removed_at abgeschlossen und ein neuer angelegt.
-- ------------------------------------------------------------
CREATE TABLE
  meters (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    parcel_id INT UNSIGNED NOT NULL,
    type ENUM ('strom', 'wasser') NOT NULL,
    number VARCHAR(30) NOT NULL UNIQUE,
    installed_at DATE NOT NULL,
    removed_at DATE NULL,
    CONSTRAINT fk_meters_parcel FOREIGN KEY (parcel_id) REFERENCES parcels (id) ON DELETE RESTRICT,
    CONSTRAINT chk_meters_dates CHECK (
      removed_at IS NULL
      OR removed_at >= installed_at
    ),
    INDEX idx_meters_parcel (parcel_id, type)
  ) ENGINE = InnoDB;

-- ------------------------------------------------------------
-- Zählerstände
-- value = absoluter Stand (Strom in kWh, Wasser in m³). Der Verbrauch wird nicht
-- gespeichert, sondern berechnet: Differenz zum vorherigen Stand desselben Zählers.
-- entered_by = wer den Stand eingetragen hat (Pächter oder Vorstand)
-- ------------------------------------------------------------
CREATE TABLE
  meter_readings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    meter_id INT UNSIGNED NOT NULL,
    read_at DATE NOT NULL,
    value DECIMAL(10, 2) NOT NULL,
    entered_by INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_meter_readings_meter FOREIGN KEY (meter_id) REFERENCES meters (id) ON DELETE CASCADE,
    CONSTRAINT fk_meter_readings_user FOREIGN KEY (entered_by) REFERENCES users (id) ON DELETE SET NULL,
    CONSTRAINT chk_meter_readings_value CHECK (value >= 0),
    -- Pro Zähler höchstens ein Stand pro Tag
    CONSTRAINT uq_meter_readings_meter_date UNIQUE (meter_id, read_at)
  ) ENGINE = InnoDB;

-- ------------------------------------------------------------
-- Gemeinschaftsarbeit: Aufgaben, die der Vorstand anlegt
-- slots = Zahl der benötigten Helfer, duration_h = angerechnete Stunden
-- ------------------------------------------------------------
CREATE TABLE
  work_tasks (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    description TEXT NULL,
    starts_at DATETIME NOT NULL,
    duration_h DECIMAL(4, 2) NOT NULL,
    slots SMALLINT UNSIGNED NOT NULL,
    created_by INT UNSIGNED NULL,
    CONSTRAINT fk_work_tasks_creator FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE SET NULL,
    CONSTRAINT chk_work_tasks_duration CHECK (duration_h > 0),
    CONSTRAINT chk_work_tasks_slots CHECK (slots > 0),
    INDEX idx_work_tasks_starts (starts_at)
  ) ENGINE = InnoDB;

-- ------------------------------------------------------------
-- Anmeldungen zu Aufgaben; hours_credited wird erst bei Bestätigung durch den Vorstand gesetzt
-- ------------------------------------------------------------
CREATE TABLE
  work_signups (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    task_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    status ENUM ('angemeldet', 'bestaetigt', 'abwesend') NOT NULL DEFAULT 'angemeldet',
    hours_credited DECIMAL(4, 2) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_work_signups_task FOREIGN KEY (task_id) REFERENCES work_tasks (id) ON DELETE CASCADE,
    CONSTRAINT fk_work_signups_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    -- Eine Person kann sich pro Aufgabe nur einmal anmelden
    CONSTRAINT uq_work_signups_task_user UNIQUE (task_id, user_id),
    INDEX idx_work_signups_user (user_id)
  ) ENGINE = InnoDB;

-- ------------------------------------------------------------
-- Pinnwand (Tausch, Verschenken, Gesuche)
-- is_visible = 0: vom Vorstand ausgeblendet (Moderation)
-- ------------------------------------------------------------
CREATE TABLE
  posts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    title VARCHAR(150) NOT NULL,
    body TEXT NOT NULL,
    category ENUM ('tausch', 'verschenken', 'suche', 'sonstiges') NOT NULL DEFAULT 'sonstiges',
    image_path VARCHAR(255) NULL,
    is_visible BOOLEAN NOT NULL DEFAULT TRUE,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_posts_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    INDEX idx_posts_category (category, created_at)
  ) ENGINE = InnoDB;

-- ------------------------------------------------------------
-- Einstellungen und Tarife (Schlüssel/Wert), vom Vorstand ohne Codeänderung anpassbar
-- Beispiele: tarif.pacht_pro_m2, tarif.wasser_pro_m3, arbeit.pflichtstunden
-- ------------------------------------------------------------
CREATE TABLE
  settings (
    name VARCHAR(100) NOT NULL PRIMARY KEY,
    value TEXT NOT NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
  ) ENGINE = InnoDB;

-- ------------------------------------------------------------
-- Fehlgeschlagene Anmeldeversuche (Schutz vor Passwort-Raten, siehe App\Auth\Auth)
-- ------------------------------------------------------------
CREATE TABLE
  login_attempts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    ip VARCHAR(45) NOT NULL,
    attempted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_login_attempts_email (email, attempted_at),
    INDEX idx_login_attempts_ip (ip, attempted_at)
  ) ENGINE = InnoDB;

CREATE TABLE
  IF NOT EXISTS login_attempts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    ip VARCHAR(45) NOT NULL,
    attempted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_login_attempts_email (email, attempted_at),
    INDEX idx_login_attempts_ip (ip, attempted_at)
  ) ENGINE = InnoDB;