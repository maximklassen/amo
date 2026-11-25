<?php

declare(strict_types=1);

use App\Core\Env;

// Anmeldung und Sessions. Zugriff: $config->get('auth.session.idle_minutes')
$secure = Env::string('SESSION_SECURE');

return [
    // Aufwand für bcrypt: höher = sicherer, aber langsamer (12 ≈ 0,25 s pro Passwortprüfung)
    'bcrypt_cost'         => Env::int('AUTH_BCRYPT_COST', 12),
    'password_min_length' => 8,

    // Sperre nach zu vielen Fehlversuchen innerhalb von lockout_minutes
    'max_failed_per_email' => 5,
    'max_failed_per_ip'    => 20,
    'lockout_minutes'      => 15,

    'session' => [
        'name'         => Env::string('SESSION_NAME', 'PARZELLEN_SID'),
        // Automatisches Abmelden nach so vielen Minuten ohne Anfrage
        'idle_minutes' => Env::int('SESSION_IDLE_MINUTES', 120),
        // null = automatisch (Secure-Cookie, wenn die Seite per HTTPS aufgerufen wird)
        'secure'       => $secure === null || $secure === '' ? null : filter_var($secure, FILTER_VALIDATE_BOOLEAN),
        'same_site'    => 'Lax',
    ],
];
