<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Http\Csrf;
use App\Http\HttpException;
use App\Http\Request;
use App\Http\Response;

/**
 * Verlangt bei allen ändernden Anfragen (POST, PUT, PATCH, DELETE) den CSRF-Token im Header "X-CSRF-Token".
 * Lesende Anfragen (GET) bleiben frei. Siehe App\Http\Csrf.
 */
final class CsrfMiddleware implements Middleware
{
    private const SAFE_METHODS = ['GET', 'HEAD', 'OPTIONS'];

    public function __construct(private readonly Csrf $csrf)
    {
    }

    public function handle(Request $request, callable $next): Response
    {
        if (!in_array($request->method, self::SAFE_METHODS, true) && !$this->csrf->isValid($request->header('x-csrf-token'))) {
            throw new HttpException(403, 'csrf_invalid', 'Ungültiges oder fehlendes CSRF-Token');
        }

        return $next($request);
    }
}
