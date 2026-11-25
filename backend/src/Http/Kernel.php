<?php

declare(strict_types=1);

namespace App\Http;

use App\Core\Config;
use App\Core\Container;
use App\Http\Middleware\Middleware;
use App\Support\Logger;
use Closure;
use JsonSerializable;
use LogicException;
use Throwable;

/**
 * Nimmt einen Request entgegen und liefert immer eine Response:
 * Route finden -> Middleware-Kette -> Handler -> Ergebnis in Response umwandeln.
 * Fehler werden als JSON ausgegeben, unerwartete Ausnahmen protokolliert.
 *
 * Alle Antworten haben dieselbe Hülle (siehe Response::success / Response::failure).
 * Handler dürfen zurückgeben: Response, Array/JsonSerializable (wird "response" mit Status 200) oder null
 * ("response": null, Status 200; ein Body mit Hülle ist wichtiger als 204).
 */
final class Kernel
{
    public function __construct(
        private readonly Container $container,
        private readonly Router $router,
        private readonly Logger $logger,
        private readonly Config $config,
    ) {
    }

    public function handle(Request $request): Response
    {
        try {
            $match = $this->router->match($request->method, $request->path);
            $request->setRouteParams($match->params);

            $core = fn (Request $request): Response => $this->callHandler($match->handler, $request);

            return $this->runMiddleware($match->middleware, $request, $core);
        } catch (HttpException $e) {
            return $this->errorResponse($e->getStatus(), $e->getMessage(), $e->getHeaders(), [], $e->getErrors());
        } catch (Throwable $e) {
            $this->logger->error('Unbehandelte Ausnahme: ' . $e->getMessage(), ['exception' => $e]);

            // Details nur im Debug-Modus nach außen geben
            $debug = $this->config->get('app.debug', false)
                ? [
                    'exception' => $e::class,
                    'message'   => $e->getMessage(),
                    'trace'     => explode("\n", $e->getTraceAsString()),
                ]
                : [];

            return $this->errorResponse(500, 'Interner Serverfehler', [], $debug);
        }
    }

    /**
     * @param array{0: class-string, 1: string}|Closure $handler
     */
    private function callHandler(array|Closure $handler, Request $request): Response
    {
        if (is_array($handler)) {
            [$class, $method] = $handler;
            $handler = [$this->container->get($class), $method];
        }

        return $this->toResponse($handler($request));
    }

    private function toResponse(mixed $result): Response
    {
        return match (true) {
            $result instanceof Response => $result,
            $result === null, is_array($result), $result instanceof JsonSerializable => Response::success($result),
            default => throw new LogicException('Ein Handler muss Response, Array, JsonSerializable oder null zurückgeben'),
        };
    }

    /**
     * Baut die Kette so, dass die erste Middleware in der Liste zuerst läuft.
     *
     * @param list<string> $middleware
     */
    private function runMiddleware(array $middleware, Request $request, Closure $core): Response
    {
        $pipeline = array_reduce(
            array_reverse($middleware),
            fn (Closure $next, string $class): Closure => fn (Request $request): Response => $this->resolveMiddleware($class)->handle($request, $next),
            $core,
        );

        return $pipeline($request);
    }

    private function resolveMiddleware(string $class): Middleware
    {
        $middleware = $this->container->get($class);

        if (!$middleware instanceof Middleware) {
            throw new LogicException(sprintf('%s implementiert %s nicht', $class, Middleware::class));
        }

        return $middleware;
    }

    /**
     * @param array<string, string> $headers
     * @param array<string, mixed>  $debug  Details der Ausnahme (nur im Debug-Modus)
     * @param array<string, string> $errors Fehlermeldungen je Eingabefeld (nur bei Validierungsfehlern)
     */
    private function errorResponse(int $status, string $message, array $headers = [], array $debug = [], array $errors = []): Response
    {
        // "response" bleibt null, außer bei Validierungsfehlern (Meldung je Feld) und im Debug-Modus
        $data = match (true) {
            $errors !== [] => ['errors' => $errors],
            $debug !== []  => ['debug' => $debug],
            default        => null,
        };

        return Response::failure($status, $message, $data, $headers);
    }
}
