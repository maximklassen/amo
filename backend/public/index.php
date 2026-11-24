<?php

declare(strict_types=1);

use App\Core\Application;
use App\Http\Kernel;
use App\Http\Request;

require __DIR__ . '/../vendor/autoload.php';

try {
    $app = Application::boot(dirname(__DIR__));

    /** @var Kernel $kernel */
    $kernel = $app->get(Kernel::class);
    $kernel->handle(Request::capture())->send();
} catch (Throwable $e) {
    // Fehler beim Starten (z. B. kaputte .env): der Kernel läuft noch nicht, daher minimale JSON-Antwort
    error_log((string) $e);

    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo '{"error":{"code":"server_error","message":"Interner Serverfehler"}}';
}
