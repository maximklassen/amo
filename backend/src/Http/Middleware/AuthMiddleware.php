<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Auth\Auth;
use App\Http\HttpException;
use App\Http\Request;
use App\Http\Response;

/**
 * Lässt nur angemeldete Benutzer durch (sonst 401).
 * Der Benutzer steht danach im Request bereit: $request->attribute('user').
 */
class AuthMiddleware implements Middleware
{
    public function __construct(protected readonly Auth $auth)
    {
    }

    public function handle(Request $request, callable $next): Response
    {
        $user = $this->auth->user();

        if ($user === null) {
            throw HttpException::unauthorized();
        }

        $request->setAttribute('user', $user);

        return $next($request);
    }
}
