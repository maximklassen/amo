-- ============================================================
-- Zusätzliche Testdaten für die Tabellen waitlist, work_tasks, work_signups, posts, settings
-- Import NACH schema.sql und seed.sql (einmalig; ein zweiter Import legt Einträge doppelt an).
-- Die Zeilen suchen sich vorhandene Benutzer aus seed.sql nach Rolle; gibt es keine, bleiben sie leer.
-- Die Tarife sind Beispielwerte, der Vorstand ändert sie später im Admin-Bereich.
-- ============================================================

USE kleingarten;

-- Tarife und Regeln
INSERT INTO settings (name, value) VALUES
  ('tarif.pacht_pro_m2',        '0.30'),
  ('tarif.mitgliedsbeitrag',    '30.00'),
  ('tarif.wasser_pro_m3',       '2.50'),
  ('tarif.strom_pro_kwh',       '0.35'),
  ('arbeit.pflichtstunden',     '10'),
  ('arbeit.strafe_pro_stunde',  '15.00')
ON DUPLICATE KEY UPDATE value = VALUES(value);

-- Aufgaben (angelegt vom ersten Vorstand)
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

-- Warteliste: die ersten drei Bewerber mit unterschiedlichen Wünschen
INSERT INTO waitlist (user_id, pref_min_size, pref_max_size, wants_water, wants_power, wants_hut)
SELECT u.id, 250, 400, TRUE, FALSE, FALSE
FROM users u WHERE u.role = 'bewerber' ORDER BY u.id LIMIT 1;

INSERT INTO waitlist (user_id, pref_min_size, pref_max_size, wants_water, wants_power, wants_hut)
SELECT u.id, 300, NULL, TRUE, TRUE, TRUE
FROM users u WHERE u.role = 'bewerber' ORDER BY u.id LIMIT 1 OFFSET 1;

INSERT INTO waitlist (user_id, pref_min_size, pref_max_size, wants_water, wants_power, wants_hut)
SELECT u.id, NULL, 350, FALSE, FALSE, TRUE
FROM users u WHERE u.role = 'bewerber' ORDER BY u.id LIMIT 1 OFFSET 2;

-- Pinnwand: zwei Beiträge vom ersten Pächter
INSERT INTO posts (user_id, title, body, category)
SELECT u.id, 'Tomatenpflanzen abzugeben',
       'Ich habe zu viele Jungpflanzen gezogen, ca. 20 Stück, Sorte Harzfeuer. Bitte melden!', 'verschenken'
FROM users u WHERE u.role = 'paechter' ORDER BY u.id LIMIT 1;

INSERT INTO posts (user_id, title, body, category)
SELECT u.id, 'Suche Schubkarre',
       'Wer hat eine gebrauchte Schubkarre übrig? Gern gegen Zucchini-Tausch.', 'suche'
FROM users u WHERE u.role = 'paechter' ORDER BY u.id LIMIT 1;
