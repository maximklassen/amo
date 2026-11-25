<?php

declare(strict_types=1);

use App\Controllers\HealthController;
use App\Controllers\ParcelController;
use App\Http\Router;

// Alle API-Routen. Die Datei gibt eine Funktion zurück, die den Router befüllt.
// Beispiel für eine Gruppe mit Middleware (kommt mit der Anmeldung):
//   $router->group('/api/admin', [AuthMiddleware::class, VorstandMiddleware::class], function (Router $r): void {
//       $r->get('/parzellen/{id:\d+}', [ParzellenController::class, 'show']);
//   });
return static function (Router $router): void {
    [
        $router->get('/api/health', [HealthController::class, 'show']),
        $router->get('/api/parcells', [ParcelController::class, 'all']),
    ];
};
