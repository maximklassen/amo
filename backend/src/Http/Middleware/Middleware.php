<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Http\Request;
use App\Http\Response;

/**
 * Code, der vor und/oder nach dem eigentlichen Handler läuft (Anmeldung, Rollen-Prüfung, Logging ...).
 *
 * - $next($request) ruft die nächste Middleware bzw. den Handler auf und liefert dessen Response.
 * - Ohne $next-Aufruf wird abgebrochen, z. B. mit: throw HttpException::unauthorized();
 */
interface Middleware
{
    public function handle(Request $request, callable $next): Response;
}
