<?php

declare(strict_types=1);

namespace Tests\Http;

use App\Http\HttpException;
use App\Http\Request;
use PHPUnit\Framework\TestCase;

final class RequestTest extends TestCase
{
    public function testMethodAndPathAreNormalized(): void
    {
        $request = new Request('get', '/api/x/');

        self::assertSame('GET', $request->method);
        self::assertSame('/api/x', $request->path);
        self::assertSame('/', (new Request('GET', ''))->path);
        self::assertSame('/', (new Request('GET', '/'))->path);
    }

    public function testHeadersAreCaseInsensitive(): void
    {
        $request = new Request('GET', '/', headers: ['Content-Type' => 'text/plain']);

        self::assertSame('text/plain', $request->header('content-type'));
        self::assertSame('text/plain', $request->header('CONTENT-TYPE'));
        self::assertNull($request->header('x-missing'));
        self::assertSame('d', $request->header('x-missing', 'd'));
    }

    public function testBearerToken(): void
    {
        self::assertSame('abc123', (new Request('GET', '/', headers: ['Authorization' => 'Bearer abc123']))->bearerToken());
        self::assertSame('abc123', (new Request('GET', '/', headers: ['authorization' => 'bearer abc123']))->bearerToken());
        self::assertNull((new Request('GET', '/', headers: ['Authorization' => 'Basic abc']))->bearerToken());
        self::assertNull((new Request('GET', '/'))->bearerToken());
    }

    public function testJsonBodyIsParsed(): void
    {
        $request = new Request('POST', '/', headers: ['Content-Type' => 'application/json; charset=utf-8'], rawBody: '{"name":"Müller","n":3}');

        self::assertSame(['name' => 'Müller', 'n' => 3], $request->body());
    }

    public function testFormDataIsUsedWhenBodyIsNotJson(): void
    {
        $request = new Request('POST', '/', rawBody: 'a=1', form: ['a' => '1']);

        self::assertSame(['a' => '1'], $request->body());
    }

    public function testInvalidJsonRaisesBadRequest(): void
    {
        $request = new Request('POST', '/', headers: ['Content-Type' => 'application/json'], rawBody: '{kaputt');

        try {
            $request->body();
            self::fail('HttpException erwartet');
        } catch (HttpException $e) {
            self::assertSame(400, $e->getStatus());
            self::assertSame('invalid_json', $e->getErrorCode());
        }
    }

    public function testJsonScalarBodyIsRejected(): void
    {
        $request = new Request('POST', '/', headers: ['Content-Type' => 'application/json'], rawBody: '"text"');

        $this->expectException(HttpException::class);

        $request->body();
    }

    public function testInputPrefersBodyOverQuery(): void
    {
        $request = new Request(
            'POST',
            '/',
            query: ['a' => 'query', 'b' => 'query'],
            headers: ['Content-Type' => 'application/json'],
            rawBody: '{"a":"body"}',
        );

        self::assertSame('body', $request->input('a'));
        self::assertSame('query', $request->input('b'));
        self::assertSame('d', $request->input('c', 'd'));
        self::assertSame(['a' => 'body', 'b' => 'query'], $request->all());
    }

    public function testRouteParamsAndAttributes(): void
    {
        $request = new Request('GET', '/');
        $request->setRouteParams(['id' => '42']);
        $request->setAttribute('user', ['id' => 1]);

        self::assertSame('42', $request->param('id'));
        self::assertNull($request->param('other'));
        self::assertSame(['id' => 1], $request->attribute('user'));
        self::assertSame('guest', $request->attribute('role', 'guest'));
    }
}
