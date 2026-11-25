<?php

declare(strict_types=1);

namespace Tests\Http;

use App\Http\Csrf;
use App\Http\ArraySession;
use App\Http\HttpException;
use App\Http\Middleware\AuthMiddleware;
use App\Http\Middleware\CsrfMiddleware;
use App\Http\Middleware\PaechterMiddleware;
use App\Http\Middleware\VorstandMiddleware;
use App\Http\Request;
use App\Http\Response;
use Tests\Auth\AuthTestCase;

final class MiddlewareTest extends AuthTestCase
{
    // ---------- CSRF ----------

    public function testCsrfLetsReadingRequestsPassWithoutToken(): void
    {
        foreach (['GET', 'HEAD', 'OPTIONS'] as $method) {
            self::assertSame(200, $this->runCsrf(new Request($method, '/api/x'))->status());
        }
    }

    public function testCsrfBlocksChangingRequestsWithoutToken(): void
    {
        foreach (['POST', 'PUT', 'PATCH', 'DELETE'] as $method) {
            $e = $this->catchHttp(fn () => $this->runCsrf(new Request($method, '/api/x')));
            self::assertSame(403, $e->getStatus());
            self::assertSame('csrf_invalid', $e->getErrorCode());
        }
    }

    public function testCsrfBlocksWrongToken(): void
    {
        $this->csrf->token();
        $e = $this->catchHttp(fn () => $this->runCsrf(new Request('POST', '/api/x', headers: ['X-CSRF-Token' => 'falsch'])));

        self::assertSame(403, $e->getStatus());
    }

    public function testCsrfAcceptsCorrectTokenCaseInsensitiveHeaderName(): void
    {
        $token = $this->csrf->token();

        $response = $this->runCsrf(new Request('POST', '/api/x', headers: ['x-csrf-token' => $token]));

        self::assertSame(200, $response->status());
    }

    public function testCsrfTokenFromOtherSessionIsRejected(): void
    {
        $foreign = (new Csrf(new ArraySession()))->token();
        $this->csrf->token();

        $e = $this->catchHttp(fn () => $this->runCsrf(new Request('POST', '/api/x', headers: ['X-CSRF-Token' => $foreign])));

        self::assertSame(403, $e->getStatus());
    }

    public function testCsrfTokenIsStableUntilRotated(): void
    {
        $first = $this->csrf->token();

        self::assertSame($first, $this->csrf->token());
        self::assertNotSame($first, $this->csrf->rotate());
        self::assertFalse($this->csrf->isValid($first));
        self::assertFalse($this->csrf->isValid(null));
        self::assertFalse($this->csrf->isValid(''));
    }

    // ---------- Anmeldung ----------

    public function testAuthMiddlewareRejectsGuestWith401(): void
    {
        $e = $this->catchHttp(fn () => $this->through(new AuthMiddleware($this->auth)));

        self::assertSame(401, $e->getStatus());
    }

    public function testAuthMiddlewarePassesUserToRequest(): void
    {
        $this->insertUser('paul@example.de', 'passwort1', 'bewerber');
        $this->auth->login('paul@example.de', 'passwort1', '10.0.0.1');

        $request = new Request('GET', '/api/x');
        $this->through(new AuthMiddleware($this->auth), $request);

        self::assertSame('paul@example.de', $request->attribute('user')['email']);
    }

    // ---------- Rollen ----------

    public function testPaechterMiddlewareAllowsPaechterAndVorstandButNotBewerber(): void
    {
        self::assertSame(403, $this->statusFor('bewerber', fn () => new PaechterMiddleware($this->auth)));
        self::assertSame(200, $this->statusFor('paechter', fn () => new PaechterMiddleware($this->auth)));
        self::assertSame(200, $this->statusFor('vorstand', fn () => new PaechterMiddleware($this->auth)));
    }

    public function testVorstandMiddlewareAllowsOnlyVorstand(): void
    {
        self::assertSame(403, $this->statusFor('bewerber', fn () => new VorstandMiddleware($this->auth)));
        self::assertSame(403, $this->statusFor('paechter', fn () => new VorstandMiddleware($this->auth)));
        self::assertSame(200, $this->statusFor('vorstand', fn () => new VorstandMiddleware($this->auth)));
    }

    public function testRoleMiddlewareGivesGuest401NotForbidden(): void
    {
        $e = $this->catchHttp(fn () => $this->through(new VorstandMiddleware($this->auth)));

        self::assertSame(401, $e->getStatus());
    }

    // ---------- Hilfen ----------

    private function runCsrf(Request $request): Response
    {
        return $this->through(new CsrfMiddleware($this->csrf), $request);
    }

    private function through(\App\Http\Middleware\Middleware $middleware, ?Request $request = null): Response
    {
        return $middleware->handle(
            $request ?? new Request('GET', '/api/x'),
            static fn (Request $r): Response => Response::json(['ok' => true]),
        );
    }

    /** @param callable(): \App\Http\Middleware\Middleware $factory */
    private function statusFor(string $role, callable $factory): int
    {
        $this->setUp(); // frische Datenbank und Session je Fall
        $this->insertUser('u@example.de', 'passwort1', $role);
        $this->auth->login('u@example.de', 'passwort1', '10.0.0.1');

        try {
            return $this->through($factory())->status();
        } catch (HttpException $e) {
            return $e->getStatus();
        }
    }
}
