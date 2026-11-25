<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Http\HttpException;
use App\Http\Request;
use App\Http\Response;

/**
 * Basis für Rollen-Prüfungen: Anmeldung (401) und dann passende Rolle (403).
 * Die konkrete Middleware legt nur fest, welche Rollen erlaubt sind (siehe VorstandMiddleware).
 */
abstract class RoleMiddleware extends AuthMiddleware
{
    /** @return list<string> erlaubte Rollen */
    abstract protected function allowedRoles(): array;

    public function handle(Request $request, callable $next): Response
    {
        return parent::handle($request, function (Request $request) use ($next): Response {
            $role = (string) ($request->attribute('user')['role'] ?? '');

            if (!in_array($role, $this->allowedRoles(), true)) {
                throw HttpException::forbidden();
            }

            return $next($request);
        });
    }
}
