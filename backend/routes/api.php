<?php

declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\HealthController;
use App\Controllers\ProfileController;
use App\Http\Middleware\AuthMiddleware;
use App\Http\Middleware\CsrfMiddleware;
use App\Http\Router;

// Alle API-Routen. Die Datei gibt eine Funktion zurück, die den Router befüllt.
// Alles unter /api ist durch CsrfMiddleware geschützt (gilt nur für POST, PUT, PATCH, DELETE).
//
// Beispiele für geschützte Gruppen (kommen mit den nächsten Funktionen):
//   $r->group('/mein-garten', [PaechterMiddleware::class], function (Router $r): void { ... });
//   $r->group('/admin',       [VorstandMiddleware::class], function (Router $r): void { ... });
return static function (Router $router): void {
    $router->group('/api', [CsrfMiddleware::class], function (Router $r): void {
        $r->get('/health', [HealthController::class, 'show']);

        $r->group('/auth', [], function (Router $r): void {
            $r->get('/me', [AuthController::class, 'me']);
            $r->post('/register', [AuthController::class, 'register']);
            $r->post('/login', [AuthController::class, 'login']);
            $r->post('/logout', [AuthController::class, 'logout'], [AuthMiddleware::class]);
        });

        // Eigene Daten: nur angemeldet
        $r->group('/profile', [AuthMiddleware::class], function (Router $r): void {
            $r->get('', [ProfileController::class, 'show']);
            $r->put('', [ProfileController::class, 'update']);
            $r->put('/password', [ProfileController::class, 'changePassword']);
        });
    });
};
