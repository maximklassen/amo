-- ============================================================
-- ParzellenPlaner – Testdaten
-- Import NACH schema.sql (einmalig; ein zweiter Import ohne schema.sql scheitert bzw. legt Einträge doppelt an).
-- Passwort für ALLE Testkonten: test1234
-- ============================================================

USE kleingarten;

-- ------------------------------------------------------------
-- Benutzer: 2 Vorstand, 11 Pächter, 3 Bewerber
-- ------------------------------------------------------------
INSERT INTO users (id, email, password_hash, role, created_at) VALUES
( 1, 'klaus.meier@example.com',    '$2y$10$IISICS67cGHt6Ka678f30O2r5q5YrNtbJNoBFX4oFtQ0nJt4yohfO', 'vorstand', '2015-02-10 10:00:00'),
( 2, 'sabine.krueger@example.com', '$2y$10$IISICS67cGHt6Ka678f30O2r5q5YrNtbJNoBFX4oFtQ0nJt4yohfO', 'vorstand', '2016-03-05 09:30:00'),
( 3, 'anna.schulz@example.com',    '$2y$10$IISICS67cGHt6Ka678f30O2r5q5YrNtbJNoBFX4oFtQ0nJt4yohfO', 'paechter', '2020-01-15 18:20:00'),
( 4, 'ali.yilmaz@example.com',     '$2y$10$IISICS67cGHt6Ka678f30O2r5q5YrNtbJNoBFX4oFtQ0nJt4yohfO', 'paechter', '2019-04-02 12:00:00'),
( 5, 'olga.petrova@example.com',   '$2y$10$IISICS67cGHt6Ka678f30O2r5q5YrNtbJNoBFX4oFtQ0nJt4yohfO', 'paechter', '2018-11-20 08:45:00'),
( 6, 'thomas.becker@example.com',  '$2y$10$IISICS67cGHt6Ka678f30O2r5q5YrNtbJNoBFX4oFtQ0nJt4yohfO', 'paechter', '2017-09-01 17:10:00'),
( 7, 'fatma.demir@example.com',    '$2y$10$IISICS67cGHt6Ka678f30O2r5q5YrNtbJNoBFX4oFtQ0nJt4yohfO', 'paechter', '2021-02-14 11:00:00'),
( 8, 'jan.wojcik@example.com',     '$2y$10$IISICS67cGHt6Ka678f30O2r5q5YrNtbJNoBFX4oFtQ0nJt4yohfO', 'paechter', '2016-06-30 15:30:00'),
( 9, 'lena.hoffmann@example.com',  '$2y$10$IISICS67cGHt6Ka678f30O2r5q5YrNtbJNoBFX4oFtQ0nJt4yohfO', 'paechter', '2022-03-12 10:15:00'),
(10, 'peter.wagner@example.com',   '$2y$10$IISICS67cGHt6Ka678f30O2r5q5YrNtbJNoBFX4oFtQ0nJt4yohfO', 'paechter', '2014-05-01 09:00:00'),
(11, 'mehmet.kaya@example.com',    '$2y$10$IISICS67cGHt6Ka678f30O2r5q5YrNtbJNoBFX4oFtQ0nJt4yohfO', 'paechter', '2023-01-20 14:40:00'),
(12, 'julia.fischer@example.com',  '$2y$10$IISICS67cGHt6Ka678f30O2r5q5YrNtbJNoBFX4oFtQ0nJt4yohfO', 'paechter', '2023-08-08 16:00:00'),
(13, 'ingrid.braun@example.com',   '$2y$10$IISICS67cGHt6Ka678f30O2r5q5YrNtbJNoBFX4oFtQ0nJt4yohfO', 'paechter', '2012-04-18 10:30:00'),
(14, 'max.neumann@example.com',    '$2y$10$IISICS67cGHt6Ka678f30O2r5q5YrNtbJNoBFX4oFtQ0nJt4yohfO', 'bewerber', '2025-02-03 19:00:00'),
(15, 'sofia.rossi@example.com',    '$2y$10$IISICS67cGHt6Ka678f30O2r5q5YrNtbJNoBFX4oFtQ0nJt4yohfO', 'bewerber', '2025-06-21 20:15:00'),
(16, 'daniel.schmidt@example.com', '$2y$10$IISICS67cGHt6Ka678f30O2r5q5YrNtbJNoBFX4oFtQ0nJt4yohfO', 'bewerber', '2026-03-09 08:05:00');

INSERT INTO users_data (user_id, first_name, last_name, phone, street, city, zip) VALUES
( 1, 'Klaus',  'Meier',    '0511 5550101', 'Limmerstraße 12',       'Hannover', '30451'),
( 2, 'Sabine', 'Krüger',   '0511 5550102', 'Lister Meile 45',       'Hannover', '30161'),
( 3, 'Anna',   'Schulz',   '0511 5550103', 'Engelbosteler Damm 8',  'Hannover', '30167'),
( 4, 'Ali',    'Yilmaz',   '0511 5550104', 'Davenstedter Str. 77',  'Hannover', '30453'),
( 5, 'Olga',   'Petrova',  '0511 5550105', 'Podbielskistraße 210',  'Hannover', '30177'),
( 6, 'Thomas', 'Becker',   '0511 5550106', 'Hildesheimer Str. 99',  'Hannover', '30173'),
( 7, 'Fatma',  'Demir',    '0511 5550107', 'Stöckener Straße 31',   'Hannover', '30419'),
( 8, 'Jan',    'Wójcik',   '0511 5550108', 'Vahrenwalder Str. 140', 'Hannover', '30165'),
( 9, 'Lena',   'Hoffmann', '0511 5550109', 'Kirchröder Straße 5',   'Hannover', '30625'),
(10, 'Peter',  'Wagner',   '0511 5550110', 'Ricklinger Stadtweg 3', 'Hannover', '30459'),
(11, 'Mehmet', 'Kaya',     '0511 5550111', 'Badenstedter Str. 60',  'Hannover', '30455'),
(12, 'Julia',  'Fischer',  '0511 5550112', 'Sallstraße 22',         'Hannover', '30171'),
(13, 'Ingrid', 'Braun',    '0511 5550113', 'Wunstorfer Straße 18',  'Hannover', '30449'),
(14, 'Max',    'Neumann',  '0511 5550114', 'Celler Straße 70',      'Hannover', '30161'),
(15, 'Sofia',  'Rossi',    '0511 5550115', 'Göttinger Chaussee 11', 'Hannover', '30459'),
(16, 'Daniel', 'Schmidt',  NULL,           NULL,                    'Garbsen',  '30823');

-- ------------------------------------------------------------
-- Parzellen: 16 Stück, 2 Reihen à 8 (passt zur Karte im Wireframe)
-- Reihe 1: y = 10, Reihe 2: y = 135, Breite 50, Höhe 90, Abstand 53
-- ------------------------------------------------------------
INSERT INTO parcels (id, number, size_m2, has_water, has_power, has_hut, svg_path, status) VALUES
( 1, '1',  300, TRUE,  TRUE,  TRUE,  'M5 10 h50 v90 h-50 z',    'verpachtet'),
( 2, '2',  280, TRUE,  TRUE,  TRUE,  'M58 10 h50 v90 h-50 z',   'verpachtet'),
( 3, '3',  250, TRUE,  FALSE, TRUE,  'M111 10 h50 v90 h-50 z',  'verpachtet'),
( 4, '4',  320, TRUE,  TRUE,  TRUE,  'M164 10 h50 v90 h-50 z',  'verpachtet'),
( 5, '5',  270, TRUE,  FALSE, FALSE, 'M217 10 h50 v90 h-50 z',  'verpachtet'),
( 6, '6',  240, TRUE,  FALSE, FALSE, 'M270 10 h50 v90 h-50 z',  'frei'),
( 7, '7',  310, TRUE,  TRUE,  TRUE,  'M323 10 h50 v90 h-50 z',  'verpachtet'),
( 8, '8',  290, TRUE,  TRUE,  FALSE, 'M376 10 h50 v90 h-50 z',  'verpachtet'),
( 9, '9',  350, TRUE,  TRUE,  TRUE,  'M5 135 h50 v90 h-50 z',   'verpachtet'),
(10, '10', 220, FALSE, FALSE, FALSE, 'M58 135 h50 v90 h-50 z',  'frei'),
(11, '11', 260, TRUE,  FALSE, TRUE,  'M111 135 h50 v90 h-50 z', 'verpachtet'),
(12, '12', 300, TRUE,  TRUE,  TRUE,  'M164 135 h50 v90 h-50 z', 'gekündigt'),
(13, '13', 280, TRUE,  TRUE,  FALSE, 'M217 135 h50 v90 h-50 z', 'verpachtet'),
(14, '14', 330, TRUE,  TRUE,  TRUE,  'M270 135 h50 v90 h-50 z', 'verpachtet'),
(15, '15', 250, TRUE,  FALSE, TRUE,  'M323 135 h50 v90 h-50 z', 'frei'),
(16, '16', 400, TRUE,  TRUE,  TRUE,  'M376 135 h50 v90 h-50 z', 'verpachtet');

-- ------------------------------------------------------------
-- Pachtverträge
-- Aktuell (end_date NULL) für alle "verpachtet"-Parzellen,
-- Parzelle 12 gekündigt zum 31.12.2026,
-- plus beendete Verträge als Historie (Umzüge innerhalb der Anlage).
-- ------------------------------------------------------------
INSERT INTO leases (parcel_id, user_id, start_date, end_date) VALUES
-- aktuelle Verträge
( 1,  1, '2015-03-01', NULL),
( 2,  2, '2016-04-01', NULL),
( 3,  3, '2020-03-01', NULL),
( 4,  4, '2019-05-01', NULL),
( 5,  5, '2021-03-01', NULL),
( 7,  6, '2023-01-01', NULL),
( 8,  7, '2021-04-01', NULL),
( 9,  8, '2020-04-01', NULL),
(11,  9, '2022-04-01', NULL),
(13, 11, '2023-03-01', NULL),
(14, 12, '2023-09-01', NULL),
(16, 13, '2012-05-01', NULL),
-- gekündigt, läuft noch bis Jahresende
(12, 10, '2014-06-01', '2026-12-31'),
-- Historie: beendete Verträge
( 6,  6, '2017-10-01', '2022-12-31'),  -- Thomas: Parzelle 6 → 7
(15,  5, '2019-01-01', '2021-02-28'),  -- Olga:   Parzelle 15 → 5
(10,  8, '2016-07-01', '2020-03-31');  -- Jan:    Parzelle 10 → 9

-- ------------------------------------------------------------
-- Tarife und Regeln
-- Beispielwerte, der Vorstand ändert sie später im Admin-Bereich.
-- ------------------------------------------------------------
INSERT INTO settings (name, value) VALUES
  ('tarif.pacht_pro_m2',        '0.30'),
  ('tarif.mitgliedsbeitrag',    '30.00'),
  ('tarif.wasser_pro_m3',       '2.50'),
  ('tarif.strom_pro_kwh',       '0.35'),
  ('arbeit.pflichtstunden',     '10'),
  ('arbeit.strafe_pro_stunde',  '15.00')
ON DUPLICATE KEY UPDATE value = VALUES(value);

-- ------------------------------------------------------------
-- Zähler: Wasser für alle Parzellen mit has_water, Strom für alle mit has_power.
-- Stände jährlich zum Saisonende (Oktober), eingetragen vom Vorstand.
-- Freie Parzellen (6, 10, 15): Stand bleibt unverändert.
-- ------------------------------------------------------------
INSERT INTO meters (id, parcel_id, type, number, removed_at) VALUES
( 1,  1, 'wasser', 'W-1001', NULL),
( 2,  2, 'wasser', 'W-1002', NULL),
( 3,  3, 'wasser', 'W-1003', NULL),
( 4,  4, 'wasser', 'W-1004', NULL),
( 5,  5, 'wasser', 'W-1005', NULL),
( 6,  6, 'wasser', 'W-1006', NULL),
( 7,  7, 'wasser', 'W-1007', NULL),
( 8,  8, 'wasser', 'W-1008', NULL),
( 9,  9, 'wasser', 'W-1009', NULL),
(10, 11, 'wasser', 'W-1011', NULL),
(11, 12, 'wasser', 'W-1012', NULL),
(12, 13, 'wasser', 'W-1013', NULL),
(13, 14, 'wasser', 'W-1014', NULL),
(14, 15, 'wasser', 'W-1015', NULL),
(15, 16, 'wasser', 'W-1016', NULL),
(16,  1, 'strom' , 'S-2001', NULL),
(17,  2, 'strom' , 'S-2002', NULL),
(18,  4, 'strom' , 'S-2004', NULL),
(19,  7, 'strom' , 'S-2007', NULL),
(20,  8, 'strom' , 'S-2008', NULL),
(21,  9, 'strom' , 'S-2009', NULL),
(22, 12, 'strom' , 'S-2012', NULL),
(23, 13, 'strom' , 'S-2013', NULL),
(24, 14, 'strom' , 'S-2014', NULL),
(25, 16, 'strom' , 'S-2016', NULL);

INSERT INTO meter_readings (meter_id, read_at, value) VALUES
( 1, '2024-10-15',   163.70),
( 1, '2025-10-15',   190.00),
( 1, '2026-10-15',   217.60),
( 2, '2024-10-15',   177.40),
( 2, '2025-10-15',   212.00),
( 2, '2026-10-15',   249.20),
( 3, '2024-10-15',   191.10),
( 3, '2025-10-15',   234.00),
( 3, '2026-10-15',   274.30),
( 4, '2024-10-15',   204.80),
( 4, '2025-10-15',   231.00),
( 4, '2026-10-15',   255.90),
( 5, '2024-10-15',   218.50),
( 5, '2025-10-15',   246.50),
( 5, '2026-10-15',   274.50),
( 6, '2024-10-15',   232.20),
( 6, '2025-10-15',   232.20),
( 6, '2026-10-15',   232.20),
( 7, '2024-10-15',   245.90),
( 7, '2025-10-15',   290.50),
( 7, '2026-10-15',   337.70),
( 8, '2024-10-15',   259.60),
( 8, '2025-10-15',   287.50),
( 8, '2026-10-15',   312.80),
( 9, '2024-10-15',   273.30),
( 9, '2025-10-15',   309.50),
( 9, '2026-10-15',   344.40),
(10, '2024-10-15',   300.70),
(10, '2025-10-15',   322.00),
(10, '2026-10-15',   344.60),
(11, '2024-10-15',   314.40),
(11, '2025-10-15',   344.00),
(11, '2026-10-15',   376.20),
(12, '2024-10-15',   328.10),
(12, '2025-10-15',   366.00),
(12, '2026-10-15',   401.30),
(13, '2024-10-15',   341.80),
(13, '2025-10-15',   388.00),
(13, '2026-10-15',   432.90),
(14, '2024-10-15',   355.50),
(14, '2025-10-15',   355.50),
(14, '2026-10-15',   355.50),
(15, '2024-10-15',   369.20),
(15, '2025-10-15',   400.50),
(15, '2026-10-15',   433.10),
(16, '2024-10-15',  2811.00),
(16, '2025-10-15',  3061.00),
(16, '2026-10-15',  3328.00),
(17, '2024-10-15',  3122.00),
(17, '2025-10-15',  3442.00),
(17, '2026-10-15',  3796.00),
(18, '2024-10-15',  3744.00),
(18, '2025-10-15',  4204.00),
(18, '2026-10-15',  4647.00),
(19, '2024-10-15',  4677.00),
(19, '2025-10-15',  5002.00),
(19, '2026-10-15',  5361.00),
(20, '2024-10-15',  4988.00),
(20, '2025-10-15',  5383.00),
(20, '2026-10-15',  5744.00),
(21, '2024-10-15',  5299.00),
(21, '2025-10-15',  5764.00),
(21, '2026-10-15',  6212.00),
(22, '2024-10-15',  6232.00),
(22, '2025-10-15',  6562.00),
(22, '2026-10-15',  6926.00),
(23, '2024-10-15',  6543.00),
(23, '2025-10-15',  6943.00),
(23, '2026-10-15',  7309.00),
(24, '2024-10-15',  6854.00),
(24, '2025-10-15',  7324.00),
(24, '2026-10-15',  7777.00),
(25, '2024-10-15',  7476.00),
(25, '2025-10-15',  7741.00),
(25, '2026-10-15',  8023.00);

-- ------------------------------------------------------------
-- Rechnungen: Anna Schulz (Parzelle 3, 250 m², nur Wasser) für 2025 und 2026
-- ------------------------------------------------------------
INSERT INTO invoices (id, number, user_id, parcel_id, year, issued_at, due_at, status) VALUES
(1, '2025-003', 3, 3, 2025, '2025-11-01', '2025-12-01', 'bezahlt'),
(2, '2026-003', 3, 3, 2026, '2026-11-01', '2026-12-01', 'offen');

INSERT INTO invoice_items (invoice_id, description, quantity, unit, unit_price, amount) VALUES
(1, 'Pacht',            250.00, 'm²',  0.30,  75.00),
(1, 'Mitgliedsbeitrag',   1.00, NULL, 30.00,  30.00),
(1, 'Wasser',            42.90, 'm³',  2.50, 107.25),
(2, 'Pacht',            250.00, 'm²',  0.30,  75.00),
(2, 'Mitgliedsbeitrag',   1.00, NULL, 30.00,  30.00),
(2, 'Wasser',            40.30, 'm³',  2.50, 100.75);

-- ------------------------------------------------------------
-- Gemeinschaftsarbeit: Aufgaben (angelegt vom ersten Vorstand)
-- Termine relativ zum Importdatum, damit sie immer in der Zukunft liegen.
-- ------------------------------------------------------------
INSERT INTO work_tasks (title, description, starts_at, duration_h, slots, created_by)
SELECT 'Hecke schneiden', 'Gemeinschaftshecke am Haupteingang, Schere und Handschuhe mitbringen.',
       TIMESTAMP(DATE_ADD(CURDATE(), INTERVAL 5 DAY), '10:00:00'), 3.00, 5,
       (SELECT id FROM users WHERE role = 'vorstand' ORDER BY id LIMIT 1);

INSERT INTO work_tasks (title, description, starts_at, duration_h, slots, created_by)
SELECT 'Vereinsheim streichen', 'Außenanstrich der Nordseite, Farbe wird gestellt.',
       TIMESTAMP(DATE_ADD(CURDATE(), INTERVAL 12 DAY), '09:00:00'), 4.00, 4,
       (SELECT id FROM users WHERE role = 'vorstand' ORDER BY id LIMIT 1);

INSERT INTO work_tasks (title, description, starts_at, duration_h, slots, created_by)
SELECT 'Wege reinigen', 'Hauptwege vom Laub befreien.',
       TIMESTAMP(DATE_ADD(CURDATE(), INTERVAL 19 DAY), '10:00:00'), 2.00, 6,
       (SELECT id FROM users WHERE role = 'vorstand' ORDER BY id LIMIT 1);

-- Anmeldungen: die ersten zwei Pächter für "Hecke schneiden"
INSERT INTO work_signups (task_id, user_id, status)
SELECT t.id, u.id, 'angemeldet'
FROM work_tasks t
JOIN users u ON u.role = 'paechter'
WHERE t.title = 'Hecke schneiden'
ORDER BY u.id
LIMIT 2;

-- ------------------------------------------------------------
-- Warteliste: die drei Bewerber mit unterschiedlichen Wünschen
-- ------------------------------------------------------------
INSERT INTO waitlist (user_id, pref_min_size, pref_max_size, wants_water, wants_power, wants_hut)
SELECT u.id, 250, 400, TRUE, FALSE, FALSE
FROM users u WHERE u.role = 'bewerber' ORDER BY u.id LIMIT 1;

INSERT INTO waitlist (user_id, pref_min_size, pref_max_size, wants_water, wants_power, wants_hut)
SELECT u.id, 300, NULL, TRUE, TRUE, TRUE
FROM users u WHERE u.role = 'bewerber' ORDER BY u.id LIMIT 1 OFFSET 1;

INSERT INTO waitlist (user_id, pref_min_size, pref_max_size, wants_water, wants_power, wants_hut)
SELECT u.id, NULL, 350, FALSE, FALSE, TRUE
FROM users u WHERE u.role = 'bewerber' ORDER BY u.id LIMIT 1 OFFSET 2;

-- ------------------------------------------------------------
-- Pinnwand: zwei Beiträge vom ersten Pächter
-- ------------------------------------------------------------
INSERT INTO posts (user_id, title, body, category)
SELECT u.id, 'Tomatenpflanzen abzugeben',
       'Ich habe zu viele Jungpflanzen gezogen, ca. 20 Stück, Sorte Harzfeuer. Bitte melden!', 'verschenken'
FROM users u WHERE u.role = 'paechter' ORDER BY u.id LIMIT 1;

INSERT INTO posts (user_id, title, body, category)
SELECT u.id, 'Suche Schubkarre',
       'Wer hat eine gebrauchte Schubkarre übrig? Gern gegen Zucchini-Tausch.', 'suche'
FROM users u WHERE u.role = 'paechter' ORDER BY u.id LIMIT 1;
