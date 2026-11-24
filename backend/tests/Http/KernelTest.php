<?php

declare(strict_types=1);

namespace Tests\Http;

use App\Core\Config;
use App\Core\Container;
use App\Http\HttpException;
use App\Http\Kernel;
use App\Http\Middleware\Middleware;
use App\Http\Request;
use App\Http\Response;
use App\Http\Router;
use App\Support\Logger;
use PHPUnit\Framework\TestCase;

final class KernelTest extends TestCase
{
    private string $logFile;

    protected function setUp(): void
    {
        $this->logFile = sys_get_temp_dir() . '/kernel_' . bin2hex(random_bytes(4)) . '.log';
    }

    protected function tearDown(): void
    {
        if (is_file($this->logFile)) {
            unlink($this->logFile);
        }
    }

    public function testArrayResultBecomesJson200(): void
    {
        $router = new Router();
        $router->get('/api/ping', static fn (): array => ['pong' => true, 'text' => 'Grüße']);

        $response = $this->kernel($router)->handle(new Request('GET', '/api/ping'));

        self::assertSame(200, $response->status());
        self::assertSame('application/json; charset=utf-8', $response->header('Content-Type'));
        self::assertSame(['pong' => true, 'text' => 'Grüße'], $this->decode($response));
        // Umlaute dürfen nicht als ü-Escapes ausgegeben werden
        self::assertStringContainsString('Grüße', $response->body());
    }

    public function testControllerIsResolvedFromContainerWithAutowiringAndRouteParams(): void
    {
        $router = new Router();
        $router->get('/api/hello/{name}', [KernelTestController::class, 'hello']);

        $response = $this->kernel($router)->handle(new Request('GET', '/api/hello/Anna'));

        self::assertSame(['message' => 'Hallo Anna'], $this->decode($response));
    }

    public function testNullResultBecomesNoContent(): void
    {
        $router = new Router();
        $router->delete('/api/x', static fn (): null => null);

        $response = $this->kernel($router)->handle(new Request('DELETE', '/api/x'));

        self::assertSame(204, $response->status());
        self::assertSame('', $response->body());
    }

    public function testResponseObjectIsPassedThrough(): void
    {
        $router = new Router();
        $router->post('/api/x', static fn (): Response => Response::json(['id' => 1], 201));

        self::assertSame(201, $this->kernel($router)->handle(new Request('POST', '/api/x'))->status());
    }

    public function testUnknownRouteReturnsJson404(): void
    {
        $response = $this->kernel(new Router())->handle(new Request('GET', '/api/nope'));

        self::assertSame(404, $response->status());
        self::assertSame('not_found', $this->decode($response)['error']['code']);
    }

    public function testWrongMethodReturns405WithAllowHeader(): void
    {
        $router = new Router();
        $router->get('/api/x', static fn (): array => []);

        $response = $this->kernel($router)->handle(new Request('POST', '/api/x'));

        self::assertSame(405, $response->status());
        self::assertSame('GET', $response->header('Allow'));
        self::assertSame('method_not_allowed', $this->decode($response)['error']['code']);
    }

    public function testMiddlewareRunsInOrderAroundHandler(): void
    {
        $recorder = new KernelTestRecorder();
        $container = new Container();
        $container->instance(KernelTestRecorder::class, $recorder);

        $router = new Router();
        $router->group('/api', [KernelTestMiddlewareA::class, KernelTestMiddlewareB::class], function (Router $r) use ($recorder): void {
            $r->get('/x', static function () use ($recorder): array {
                $recorder->events[] = 'handler';

                return [];
            });
        });

        $this->kernel($router, container: $container)->handle(new Request('GET', '/api/x'));

        self::assertSame(['A:before', 'B:before', 'handler', 'B:after', 'A:after'], $recorder->events);
    }

    public function testMiddlewareCanShortCircuit(): void
    {
        $recorder = new KernelTestRecorder();
        $container = new Container();
        $container->instance(KernelTestRecorder::class, $recorder);

        $router = new Router();
        $router->get('/api/secret', static function () use ($recorder): array {
            $recorder->events[] = 'handler';

            return [];
        }, [KernelTestDenyMiddleware::class]);

        $response = $this->kernel($router, container: $container)->handle(new Request('GET', '/api/secret'));

        self::assertSame(401, $response->status());
        self::assertSame('unauthorized', $this->decode($response)['error']['code']);
        self::assertSame([], $recorder->events);
    }

    public function testMiddlewareCanModifyRequestAttributes(): void
    {
        $router = new Router();
        $router->get('/api/me', static fn (Request $r): array => ['user' => $r->attribute('user')], [KernelTestUserMiddleware::class]);

        $response = $this->kernel($router)->handle(new Request('GET', '/api/me'));

        self::assertSame(['user' => 'anna'], $this->decode($response));
    }

    public function testUnexpectedExceptionBecomes500WithoutDetailsAndIsLogged(): void
    {
        $router = new Router();
        $router->get('/api/boom', static fn () => throw new \RuntimeException('geheimes DB-Passwort'));

        $response = $this->kernel($router, debug: false)->handle(new Request('GET', '/api/boom'));
        $body = $this->decode($response);

        self::assertSame(500, $response->status());
        self::assertSame('server_error', $body['error']['code']);
        self::assertArrayNotHasKey('debug', $body);
        self::assertStringNotContainsString('geheimes', $response->body());

        self::assertFileExists($this->logFile);
        self::assertStringContainsString('geheimes DB-Passwort', (string) file_get_contents($this->logFile));
    }

    public function testDebugModeAddsExceptionDetails(): void
    {
        $router = new Router();
        $router->get('/api/boom', static fn () => throw new \RuntimeException('Detail'));

        $body = $this->decode($this->kernel($router, debug: true)->handle(new Request('GET', '/api/boom')));

        self::assertSame('Detail', $body['debug']['message']);
        self::assertSame(\RuntimeException::class, $body['debug']['exception']);
    }

    public function testInvalidJsonBodyReturns400(): void
    {
        $router = new Router();
        $router->post('/api/echo', static fn (Request $r): array => $r->body());

        $request = new Request('POST', '/api/echo', headers: ['Content-Type' => 'application/json'], rawBody: '{kaputt');
        $response = $this->kernel($router)->handle($request);

        self::assertSame(400, $response->status());
        self::assertSame('invalid_json', $this->decode($response)['error']['code']);
    }

    public function testJsonBodyReachesHandler(): void
    {
        $router = new Router();
        $router->post('/api/echo', static fn (Request $r): array => $r->body());

        $request = new Request('POST', '/api/echo', headers: ['Content-Type' => 'application/json'], rawBody: '{"a":1}');

        self::assertSame(['a' => 1], $this->decode($this->kernel($router)->handle($request)));
    }

    public function testUnsupportedHandlerResultBecomes500(): void
    {
        $router = new Router();
        $router->get('/api/x', static fn (): string => 'Text');

        self::assertSame(500, $this->kernel($router)->handle(new Request('GET', '/api/x'))->status());
    }

    public function testHttpExceptionFromHandlerKeepsStatusAndHeaders(): void
    {
        $router = new Router();
        $router->get('/api/x', static fn () => throw new HttpException(429, 'rate_limited', 'Zu viele Anfragen', ['Retry-After' => '30']));

        $response = $this->kernel($router)->handle(new Request('GET', '/api/x'));

        self::assertSame(429, $response->status());
        self::assertSame('30', $response->header('Retry-After'));
        self::assertSame('Zu viele Anfragen', $this->decode($response)['error']['message']);
    }

    private function kernel(Router $router, bool $debug = false, ?Container $container = null): Kernel
    {
        return new Kernel(
            $container ?? new Container(),
            $router,
            new Logger($this->logFile),
            new Config(['app' => ['debug' => $debug]]),
        );
    }

    /** @return array<string, mixed> */
    private function decode(Response $response): array
    {
        return json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);
    }
}

final class KernelTestRecorder
{
    /** @var list<string> */
    public array $events = [];
}

final class KernelTestGreeter
{
    public function hello(string $name): string
    {
        return 'Hallo ' . $name;
    }
}

final class KernelTestController
{
    public function __construct(private readonly KernelTestGreeter $greeter)
    {
    }

    public function hello(Request $request): array
    {
        return ['message' => $this->greeter->hello((string) $request->param('name'))];
    }
}

final class KernelTestMiddlewareA implements Middleware
{
    public function __construct(private readonly KernelTestRecorder $recorder)
    {
    }

    public function handle(Request $request, callable $next): Response
    {
        $this->recorder->events[] = 'A:before';
        $response = $next($request);
        $this->recorder->events[] = 'A:after';

        return $response;
    }
}

final class KernelTestMiddlewareB implements Middleware
{
    public function __construct(private readonly KernelTestRecorder $recorder)
    {
    }

    public function handle(Request $request, callable $next): Response
    {
        $this->recorder->events[] = 'B:before';
        $response = $next($request);
        $this->recorder->events[] = 'B:after';

        return $response;
    }
}

final class KernelTestDenyMiddleware implements Middleware
{
    public function handle(Request $request, callable $next): Response
    {
        throw HttpException::unauthorized();
    }
}

final class KernelTestUserMiddleware implements Middleware
{
    public function handle(Request $request, callable $next): Response
    {
        $request->setAttribute('user', 'anna');

        return $next($request);
    }
}
