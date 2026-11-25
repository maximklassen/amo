<?php

declare(strict_types=1);

namespace App\Http;

use Closure;

/** Ergebnis von Router::match(): Handler, Routen-Parameter und Middleware der gefundenen Route. */
final class RouteMatch
{
    /**
     * @param array{0: class-string, 1: string}|Closure $handler
     * @param array<string, string>                     $params
     * @param list<string>                              $middleware Klassennamen, äußerste zuerst
     */
    public function __construct(
        public readonly array|Closure $handler,
        public readonly array $params,
        public readonly array $middleware,
    ) {
    }
}
