<?php

declare(strict_types=1);

namespace App\Http;

use Closure;
use FastRoute\Dispatcher;
use FastRoute\RouteCollector;

/**
 * Routen registrieren und Anfragen zuordnen (Matching übernimmt nikic/fast-route).
 *
 *   $router->group('/api', [], function (Router $r) {
 *       $r->get('/parzellen/{id:\d+}', [ParzellenController::class, 'show']);
 *   });
 *
 * Handler: [Controller::class, 'methode'] oder Closure; beide bekommen den Request.
 * Middleware: Liste von Klassennamen (siehe App\Http\Middleware\Middleware).
 */
final class Router
{
    /** @var list<array{method: string, pattern: string, handler: array{0: class-string, 1: string}|Closure, middleware: list<string>}> */
    private array $routes = [];

    /** @var list<array{prefix: string, middleware: list<string>}> */
    private array $groups = [];

    private ?Dispatcher $dispatcher = null;

    /**
     * @param array{0: class-string, 1: string}|Closure $handler
     * @param list<string>                              $middleware
     */
    public function get(string $path, array|Closure $handler, array $middleware = []): void
    {
        $this->add('GET', $path, $handler, $middleware);
    }

    /**
     * @param array{0: class-string, 1: string}|Closure $handler
     * @param list<string>                              $middleware
     */
    public function post(string $path, array|Closure $handler, array $middleware = []): void
    {
        $this->add('POST', $path, $handler, $middleware);
    }

    /**
     * @param array{0: class-string, 1: string}|Closure $handler
     * @param list<string>                              $middleware
     */
    public function put(string $path, array|Closure $handler, array $middleware = []): void
    {
        $this->add('PUT', $path, $handler, $middleware);
    }

    /**
     * @param array{0: class-string, 1: string}|Closure $handler
     * @param list<string>                              $middleware
     */
    public function patch(string $path, array|Closure $handler, array $middleware = []): void
    {
        $this->add('PATCH', $path, $handler, $middleware);
    }

    /**
     * @param array{0: class-string, 1: string}|Closure $handler
     * @param list<string>                              $middleware
     */
    public function delete(string $path, array|Closure $handler, array $middleware = []): void
    {
        $this->add('DELETE', $path, $handler, $middleware);
    }

    /**
     * @param array{0: class-string, 1: string}|Closure $handler
     * @param list<string>                              $middleware
     */
    public function add(string $method, string $path, array|Closure $handler, array $middleware = []): void
    {
        $prefix = '';
        $allMiddleware = [];

        foreach ($this->groups as $group) {
            $prefix .= '/' . trim($group['prefix'], '/');
            $allMiddleware = [...$allMiddleware, ...$group['middleware']];
        }

        $this->routes[] = [
            'method'     => strtoupper($method),
            'pattern'    => '/' . trim($prefix . '/' . trim($path, '/'), '/'),
            'handler'    => $handler,
            'middleware' => [...$allMiddleware, ...$middleware],
        ];

        // Dispatcher wird beim nächsten match() neu aufgebaut
        $this->dispatcher = null;
    }

    /**
     * Gemeinsames Präfix und gemeinsame Middleware für alle Routen im Callback.
     * Gruppen dürfen verschachtelt werden.
     *
     * @param list<string> $middleware
     */
    public function group(string $prefix, array $middleware, Closure $callback): void
    {
        $this->groups[] = ['prefix' => $prefix, 'middleware' => $middleware];

        try {
            $callback($this);
        } finally {
            array_pop($this->groups);
        }
    }

    /**
     * @throws HttpException 404, wenn keine Route passt; 405 (mit Allow-Header), wenn nur die Methode nicht passt
     */
    public function match(string $method, string $path): RouteMatch
    {
        $result = $this->dispatcher()->dispatch(strtoupper($method), $path);

        return match ($result[0]) {
            Dispatcher::FOUND              => new RouteMatch(
                $this->routes[$result[1]]['handler'],
                $result[2],
                $this->routes[$result[1]]['middleware'],
            ),
            Dispatcher::METHOD_NOT_ALLOWED => throw HttpException::methodNotAllowed($result[1]),
            default                        => throw HttpException::notFound(),
        };
    }

    private function dispatcher(): Dispatcher
    {
        return $this->dispatcher ??= \FastRoute\simpleDispatcher(function (RouteCollector $collector): void {
            foreach ($this->routes as $index => $route) {
                // Als Handler wird nur der Index gespeichert; Handler und Middleware liegen in $this->routes
                $collector->addRoute($route['method'], $route['pattern'], $index);
            }
        });
    }
}
