<?php

declare(strict_types=1);

namespace Tests\Http;

use App\Http\HttpException;
use App\Http\Request;
use App\Http\Router;
use PHPUnit\Framework\TestCase;

final class RouterTest extends TestCase
{
    public function testMatchFindsRouteAndExtractsParams(): void
    {
        $router = new Router();
        $router->get('/api/items/{id:\d+}', static fn (Request $r): array => []);

        $match = $router->match('GET', '/api/items/42');

        self::assertSame(['id' => '42'], $match->params);
    }

    public function testUnknownPathAndFailingConstraintGive404(): void
    {
        $router = new Router();
        $router->get('/api/items/{id:\d+}', static fn (Request $r): array => []);

        foreach (['/api/nope', '/api/items/abc'] as $path) {
            try {
                $router->match('GET', $path);
                self::fail('HttpException erwartet für ' . $path);
            } catch (HttpException $e) {
                self::assertSame(404, $e->getStatus());
                self::assertSame('not_found', $e->getErrorCode());
            }
        }
    }

    public function testWrongMethodGives405WithAllowedMethods(): void
    {
        $router = new Router();
        $router->get('/api/x', static fn (Request $r): array => []);
        $router->post('/api/x', static fn (Request $r): array => []);

        try {
            $router->match('DELETE', '/api/x');
            self::fail('HttpException erwartet');
        } catch (HttpException $e) {
            self::assertSame(405, $e->getStatus());
            self::assertSame(['Allow' => 'GET, POST'], $e->getHeaders());
        }
    }

    public function testMethodShortcutsRegisterTheRightVerbs(): void
    {
        $router = new Router();
        $handler = static fn (Request $r): array => [];

        $router->put('/p', $handler);
        $router->patch('/q', $handler);
        $router->delete('/r', $handler);

        self::assertSame([], $router->match('PUT', '/p')->params);
        self::assertSame([], $router->match('PATCH', '/q')->params);
        self::assertSame([], $router->match('DELETE', '/r')->params);
    }

    public function testGroupsAddPrefixAndMergeMiddlewareOuterFirst(): void
    {
        $router = new Router();

        $router->group('/api', ['A'], function (Router $r): void {
            $r->group('/admin/', ['B'], function (Router $r): void {
                $r->get('/x', static fn (Request $q): array => [], ['C']);
            });

            $r->get('/open', static fn (Request $q): array => []);
        });

        $router->get('/outside', static fn (Request $q): array => []);

        self::assertSame(['A', 'B', 'C'], $router->match('GET', '/api/admin/x')->middleware);
        self::assertSame(['A'], $router->match('GET', '/api/open')->middleware);
        self::assertSame([], $router->match('GET', '/outside')->middleware);
    }

    public function testRootRouteAndRoutesAddedAfterFirstMatch(): void
    {
        $router = new Router();
        $router->get('/', static fn (Request $r): array => []);

        self::assertSame([], $router->match('GET', '/')->params);

        // Eine später registrierte Route muss ebenfalls gefunden werden
        $router->get('/late', static fn (Request $r): array => []);
        self::assertSame([], $router->match('GET', '/late')->params);
    }

    public function testHandlerIsReturnedUnchanged(): void
    {
        $router = new Router();
        $router->get('/c', ['Some\\Controller', 'index']);

        self::assertSame(['Some\\Controller', 'index'], $router->match('GET', '/c')->handler);
    }
}
