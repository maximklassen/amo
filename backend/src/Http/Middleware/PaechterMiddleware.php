<?php

declare(strict_types=1);

namespace App\Http\Middleware;

/** Für Pächter und Vorstand (Bereich "Mein Garten", Gemeinschaftsarbeit, Pinnwand). */
final class PaechterMiddleware extends RoleMiddleware
{
    protected function allowedRoles(): array
    {
        return ['paechter', 'vorstand'];
    }
}
