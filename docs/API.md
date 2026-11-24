# ParzellenPlaner – API

Passt zu `database/schema.sql` (Tabellen: `users`, `users_data`, `parcels`, `leases`).
Neue Module (Arbeitsstunden, Buchungen, Zähler …) werden hier ergänzt, sobald ihre Tabellen existieren.

## Allgemein

| Regel | Wert |
|---|---|
| Basis-URL | `/api/` (Vite-Proxy → `http://localhost/kleingarten/api/`) |
| URLs | ohne `.php`, IDs im Pfad: `/api/users/5` |
| Format | Request und Response immer JSON, UTF-8 |
| Login | PHP-Session-Cookie (`credentials: 'include'` in `api.js`) |
| CSRF | Bei POST / PUT / DELETE Header `X-CSRF-Token: <token aus login/me>` |
| Datum | `YYYY-MM-DD` |
| Boolean | `true` / `false` (nicht `0` / `1`) |
| Fehler | `{ "error": "Text für den Benutzer" }` |

**Statuscodes:** `200` OK · `201` angelegt · `400` ungültige Eingabe · `401` nicht eingeloggt · `403` keine Berechtigung · `404` nicht gefunden / unbekannte Route · `405` Methode nicht erlaubt · `409` Konflikt · `429` zu viele Versuche

**Rollen:** `bewerber` · `paechter` · `vorstand`

---

## Routing (wie `/api/users/5` ohne `.php` funktioniert)

Alle Anfragen an `/api/...` landen per `.htaccess` in **einer** Datei `api/index.php` (Front Controller).
Die Handler-Dateien sind von außen nicht direkt aufrufbar.

```
kleingarten/
└── api/
    ├── .htaccess
    ├── index.php          ← Router
    ├── includes/          ← db.php, auth.php, csrf.php, response.php
    └── handlers/
        ├── auth/          login.php, logout.php, me.php, register.php
        ├── profile/       get.php, update.php, password.php
        ├── parcels/       list.php, get.php, create.php, update.php, delete.php, release.php
        ├── leases/        list.php, my.php, create.php, terminate.php
        └── users/         list.php, get.php, role.php, delete.php
```

`api/.htaccess`
```apache
RewriteEngine On
RewriteRule ^(?!index\.php$) index.php [QSA,L]
```
(XAMPP: `mod_rewrite` und `AllowOverride All` sind standardmäßig aktiv.)

`api/index.php`
```php
<?php
require __DIR__ . '/includes/response.php';

$path   = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path   = trim(preg_replace('#^.*?/api/#', '', $path), '/');   // z. B. "users/5"
$method = $_SERVER['REQUEST_METHOD'];

$routes = [
    // Methode, Muster,                          Handler
    ['POST',   'auth/register',                 'auth/register.php'],
    ['POST',   'auth/login',                    'auth/login.php'],
    ['POST',   'auth/logout',                   'auth/logout.php'],
    ['GET',    'auth/me',                       'auth/me.php'],

    ['GET',    'profile',                       'profile/get.php'],
    ['PUT',    'profile',                       'profile/update.php'],
    ['PUT',    'profile/password',              'profile/password.php'],

    ['GET',    'parcels',                       'parcels/list.php'],
    ['POST',   'parcels',                       'parcels/create.php'],
    ['GET',    'parcels/(\d+)',                 'parcels/get.php'],
    ['PUT',    'parcels/(\d+)',                 'parcels/update.php'],
    ['DELETE', 'parcels/(\d+)',                 'parcels/delete.php'],
    ['POST',   'parcels/(\d+)/release',         'parcels/release.php'],

    ['GET',    'leases',                        'leases/list.php'],
    ['POST',   'leases',                        'leases/create.php'],
    ['GET',    'leases/my',                     'leases/my.php'],
    ['PUT',    'leases/(\d+)/terminate',        'leases/terminate.php'],

    ['GET',    'users',                         'users/list.php'],
    ['GET',    'users/(\d+)',                   'users/get.php'],
    ['PUT',    'users/(\d+)/role',              'users/role.php'],
    ['DELETE', 'users/(\d+)',                   'users/delete.php'],
];

$pathFound = false;
foreach ($routes as [$m, $pattern, $handler]) {
    if (!preg_match('#^' . $pattern . '$#', $path, $match)) continue;
    $pathFound = true;
    if ($m !== $method) continue;

    $id   = isset($match[1]) ? (int)$match[1] : null;          // ID aus der URL
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    require __DIR__ . '/handlers/' . $handler;                  // Handler nutzt $id, $body
    exit;
}
$pathFound ? error('Methode nicht erlaubt', 405) : error('Route nicht gefunden', 404);
```

Im Handler sind `$id` und `$body` schon da, z. B. `handlers/users/get.php`:
```php
<?php
require_once __DIR__ . '/../../includes/auth.php';
requireRole('vorstand');
$stmt = $pdo->prepare('SELECT ... FROM users JOIN users_data ON ... WHERE id = ?');
$stmt->execute([$id]);
$user = $stmt->fetch() ?: error('Benutzer nicht gefunden', 404);
json($user);
```

---

## 1. Auth

### POST `/api/auth/register`
Zugriff: öffentlich. Neuer Benutzer bekommt immer die Rolle `bewerber`.
```json
{ "email": "max@example.com", "password": "test1234",
  "first_name": "Max", "last_name": "Neumann",
  "phone": "0511 123", "street": "Celler Straße 70", "city": "Hannover", "zip": "30161" }
```
`phone`, `street`, `city`, `zip` optional.
→ `201`
```json
{ "id": 17, "email": "max@example.com", "role": "bewerber" }
```
Fehler: `400` Pflichtfeld fehlt / E-Mail ungültig / Passwort < 8 Zeichen / PLZ nicht 5 Ziffern · `409` E-Mail existiert

### POST `/api/auth/login`
Zugriff: öffentlich
```json
{ "email": "anna.schulz@example.com", "password": "test1234" }
```
→ `200`
```json
{ "user": { "id": 3, "email": "anna.schulz@example.com", "role": "paechter",
            "first_name": "Anna", "last_name": "Schulz" },
  "csrf_token": "a1b2c3..." }
```
Fehler: `400` Feld fehlt · `401` E-Mail oder Passwort falsch (immer dieselbe Meldung) · `429` 5 Fehlversuche in 15 Min.

### POST `/api/auth/logout`
Zugriff: eingeloggt → `200` `{ "ok": true }`

### GET `/api/auth/me`
Zugriff: eingeloggt. Wird beim Laden der App aufgerufen (`AuthContext`).
→ `200` gleiches Format wie Login · Fehler: `401`

---

## 2. Profil (eigene Daten)

### GET `/api/profile`
Zugriff: eingeloggt
→ `200`
```json
{ "id": 3, "email": "anna.schulz@example.com", "role": "paechter", "created_at": "2020-01-15 18:20:00",
  "first_name": "Anna", "last_name": "Schulz", "phone": "0511 5550103",
  "street": "Engelbosteler Damm 8", "city": "Hannover", "zip": "30167" }
```

### PUT `/api/profile`
Zugriff: eingeloggt. Ändert nur `users_data` des eingeloggten Benutzers.
```json
{ "first_name": "Anna", "last_name": "Schulz", "phone": "0511 999",
  "street": "Neue Str. 1", "city": "Hannover", "zip": "30167" }
```
→ `200` Profil wie bei GET · Fehler: `400`

### PUT `/api/profile/password`
Zugriff: eingeloggt
```json
{ "old_password": "test1234", "new_password": "neuesPasswort1" }
```
→ `200` `{ "ok": true }` · Fehler: `400` neues Passwort < 8 Zeichen · `401` altes Passwort falsch

---

## 3. Parzellen

### GET `/api/parcels`
Zugriff: öffentlich (Karte). Optional `?status=frei`, `?full=true`.
`full=true` wird **serverseitig** gegen die Rolle in der Session geprüft – der Parameter allein gibt keine Rechte.

| Aufruf | Wer | Ergebnis |
|---|---|---|
| ohne `full` | Gast | Basisfelder |
| ohne `full` | eingeloggt | Basisfelder + `is_mine` |
| `?full=true` | `vorstand` | Basisfelder + `is_mine` + `tenant` |
| `?full=true` | Gast | `401` |
| `?full=true` | `bewerber` / `paechter` | `403` (**Demo in Präsentation**) |

→ `200` (Beispiel Gast)
```json
[
  { "id": 1, "number": "1", "size_m2": 300, "has_water": true, "has_power": true, "has_hut": true,
    "svg_path": "M5 10 h50 v90 h-50 z", "status": "verpachtet" },
  ...
]
```
Eingeloggt zusätzlich: `"is_mine": true` (eigene Parzelle auf der Karte hervorheben).
Mit `full=true` zusätzlich: `"tenant": { "user_id": 1, "name": "Klaus Meier" }` (oder `null`).
Namen anderer Pächter sieht ein Pächter nicht (DSGVO, Datensparsamkeit).

PHP-Skizze (`handlers/parcels/list.php`):
```php
$user = currentUser();               // null für Gäste
$full = ($_GET['full'] ?? '') === 'true';
if ($full) requireRole('vorstand');  // 401 / 403, Skript endet hier

foreach ($rows as &$p) {
    if ($user) $p['is_mine'] = $p['tenant_id'] == $user['id'];
    if ($full) $p['tenant'] = $p['tenant_id']
        ? ['user_id' => $p['tenant_id'], 'name' => $p['tenant_name']] : null;
    unset($p['tenant_id'], $p['tenant_name']);
}
```

### GET `/api/parcels/12`
Zugriff: öffentlich; mit `?full=true` nur `vorstand` (gleiche Regel wie oben)
→ `200`
```json
{ "id": 12, "number": "12", "size_m2": 300, "has_water": true, "has_power": true, "has_hut": true,
  "svg_path": "M164 135 h50 v90 h-50 z", "status": "gekündigt",
  "current_lease": { "id": 13, "user_id": 10, "name": "Peter Wagner",
                     "start_date": "2014-06-01", "end_date": "2026-12-31" },
  "history": [ ... ] }
```
`current_lease` und `history` nur mit `full=true`. Fehler: `401` · `403` · `404`

### POST `/api/parcels`
Zugriff: `vorstand`
```json
{ "number": "17", "size_m2": 280, "has_water": true, "has_power": false, "has_hut": false,
  "svg_path": "M429 10 h50 v90 h-50 z" }
```
Status ist immer `frei`. → `201` neue Parzelle · Fehler: `400` · `403` · `409` Nummer existiert

### PUT `/api/parcels/6`
Zugriff: `vorstand`. Status wird **nicht** hier geändert (nur über Pachtverträge).
```json
{ "size_m2": 250, "has_water": true, "has_power": true, "has_hut": false, "svg_path": "..." }
```
→ `200` Parzelle · Fehler: `400` · `403` · `404`

### DELETE `/api/parcels/17`
Zugriff: `vorstand` → `200` `{ "ok": true }`
Fehler: `403` · `404` · `409` Parzelle hat Pachtverträge (FK `RESTRICT`)

### POST `/api/parcels/12/release`
Zugriff: `vorstand`. Gekündigte Parzelle nach Vertragsende wieder freigeben. Kein Body.
`parcels.status = 'frei'`; hat der Ex-Pächter keinen anderen aktiven Vertrag → Rolle `bewerber`.
→ `200` Parzelle · Fehler: `403` · `404` · `409` Vertrag läuft noch (`end_date` > heute)

---

## 4. Pachtverträge

### GET `/api/leases/my`
Zugriff: eingeloggt. Eigene Verträge, aktuelle zuerst.
→ `200`
```json
[ { "id": 5, "parcel_id": 5, "parcel_number": "5", "start_date": "2021-03-01", "end_date": null },
  { "id": 15, "parcel_id": 15, "parcel_number": "15", "start_date": "2019-01-01", "end_date": "2021-02-28" } ]
```
Bewerber bekommt `[]`.

### GET `/api/leases`
Zugriff: `vorstand`. Filter optional: `?parcel_id=12`, `?user_id=5`, `?active=true`.
→ `200` Liste wie oben + `user_id`, `name`

### POST `/api/leases`
Zugriff: `vorstand`. Parzelle an Bewerber/Pächter vergeben.
```json
{ "parcel_id": 6, "user_id": 14, "start_date": "2027-01-01" }
```
In **einer Transaktion**: Vertrag anlegen → `parcels.status = 'verpachtet'` → falls `bewerber`, Rolle auf `paechter`.
→ `201` Vertrag
Fehler: `400` · `403` · `404` Parzelle/Benutzer fehlt · `409` Parzelle nicht `frei` · `409` Benutzer hat schon einen aktiven Vertrag

### PUT `/api/leases/13/terminate`
Zugriff: `vorstand` für jeden Vertrag, `paechter` **nur für den eigenen**.
```json
{ "end_date": "2026-12-31" }
```
Setzt `end_date` und `parcels.status = 'gekündigt'`.
→ `200` Vertrag
Fehler: `400` `end_date` ≤ `start_date` · `403` fremder Vertrag (**Demo in Präsentation**) · `404` · `409` schon gekündigt

---

## 5. Mitglieder (Verwaltung)

### GET `/api/users`
Zugriff: `vorstand`. Optional `?role=bewerber`.
→ `200`
```json
[ { "id": 14, "email": "max.neumann@example.com", "role": "bewerber", "created_at": "2025-02-03 19:00:00",
    "first_name": "Max", "last_name": "Neumann", "parcel_number": null } ]
```

### GET `/api/users/5`
Zugriff: `vorstand` → `200` Profil + `leases` · Fehler: `403` · `404`

### PUT `/api/users/2/role`
Zugriff: `vorstand`
```json
{ "role": "paechter" }
```
→ `200` Benutzer · Fehler: `400` ungültige Rolle / eigene Rolle ändern · `403` · `404`

### DELETE `/api/users/16`
Zugriff: `vorstand`. `users_data` wird per `ON DELETE CASCADE` mitgelöscht.
→ `200` `{ "ok": true }`
Fehler: `400` sich selbst löschen · `403` · `404` · `409` Benutzer hat Pachtverträge (FK `RESTRICT`)

---

## Übersicht

| Methode | URL | Zugriff |
|---|---|---|
| POST | /api/auth/register | öffentlich |
| POST | /api/auth/login | öffentlich |
| POST | /api/auth/logout | eingeloggt |
| GET | /api/auth/me | eingeloggt |
| GET | /api/profile | eingeloggt |
| PUT | /api/profile | eingeloggt |
| PUT | /api/profile/password | eingeloggt |
| GET | /api/parcels | öffentlich; `?full=true` nur vorstand |
| GET | /api/parcels/{id} | öffentlich; `?full=true` nur vorstand |
| POST | /api/parcels | vorstand |
| PUT | /api/parcels/{id} | vorstand |
| DELETE | /api/parcels/{id} | vorstand |
| POST | /api/parcels/{id}/release | vorstand |
| GET | /api/leases/my | eingeloggt |
| GET | /api/leases | vorstand |
| POST | /api/leases | vorstand |
| PUT | /api/leases/{id}/terminate | vorstand / eigener Pächter |
| GET | /api/users | vorstand |
| GET | /api/users/{id} | vorstand |
| PUT | /api/users/{id}/role | vorstand |
| DELETE | /api/users/{id} | vorstand |
