<?php

declare(strict_types=1);

namespace App\Http\Middleware;

/** Nur für den Vorstand (Admin-Bereich). */
final class VorstandMiddleware extends RoleMiddleware
{
    protected function allowedRoles(): array
    {
        return ['vorstand'];
    }
}
