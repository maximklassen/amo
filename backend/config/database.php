<?php

declare(strict_types=1);

use App\Core\Env;

// Datenbank-Konfiguration. Zugriff: $config->get('database.host')
return [
    'driver'    => Env::string('DB_DRIVER', 'mysql'),
    'host'      => Env::string('DB_HOST', '127.0.0.1'),
    'port'      => Env::int('DB_PORT', 3306),
    // Bei sqlite: Dateipfad (relativ zu backend/) oder :memory:
    'database'  => Env::string('DB_DATABASE', 'kleingarten'),
    'username'  => Env::string('DB_USERNAME', 'root'),
    'password'  => Env::string('DB_PASSWORD', ''),
    'charset'   => Env::string('DB_CHARSET', 'utf8mb4'),
    'collation' => Env::string('DB_COLLATION', 'utf8mb4_unicode_ci'),
];
