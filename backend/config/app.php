<?php

declare(strict_types=1);

use App\Core\Env;

// Allgemeine Anwendungskonfiguration. Zugriff: $config->get('app.name')
return [
    'name'         => Env::string('APP_NAME', 'ParzellenPlaner'),
    'env'          => Env::string('APP_ENV', 'production'),
    // Sicherer Standard: ohne Angabe ist der Debug-Modus aus
    'debug'        => Env::bool('APP_DEBUG', false),
    'url'          => Env::string('APP_URL', 'http://localhost:8000'),
    'frontend_url' => Env::string('FRONTEND_URL', 'http://localhost:5173'),
    'key'          => Env::string('APP_KEY', ''),
    'timezone'     => Env::string('APP_TIMEZONE', 'Europe/Berlin'),
    'log_path'     => Env::string('LOG_PATH', 'storage/logs/app.log'),
    'log_level'    => Env::string('LOG_LEVEL', 'debug'),
];
