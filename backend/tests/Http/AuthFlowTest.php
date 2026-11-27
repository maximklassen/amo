<?php

declare(strict_types=1);

namespace Tests\Http;

/**
 * Ablauf wie im Browser: Routen aus routes/api.php, echte Middleware, Controller, Auth und Datenbank (SQLite).
 * Jede Anfrage bekommt einen neuen Container (wie bei PHP pro Request), nur Session und Datenbank bleiben
 * erhalten (Session = das Cookie des Browsers).
 */
final class AuthFlowTest extends ApiTestCase
{
    public function testMeForGuestReturnsNullUserAndCsrfToken(): void
    {
        [$status, $body] = $this->call('GET', '/api/auth/me');

        self::assertSame(200, $status);
        self::assertSame(200, $body['statuscode']);
        self::assertFalse($body['error']);
        self::assertNull($body['response']['user']);
        self::assertTrue(strlen($body['response']['csrf_token']) >= 32);
    }

    public function testFullFlowRegisterMeLogoutLogin(): void
    {
        // 1) Gast holt den CSRF-Token
        $token = $this->call('GET', '/api/auth/me')[1]['response']['csrf_token'];

        // 2) Registrieren (eingeloggt, 201)
        [$status, $body] = $this->call('POST', '/api/auth/register', [
            'email' => 'Anna@Example.de', 'password' => 'geheim123',
            'first_name' => 'Anna', 'last_name' => 'Beispiel',
        ], $token);
        self::assertSame(201, $status);
        self::assertSame('anna@example.de', $body['response']['user']['email']);
        self::assertSame('bewerber', $body['response']['user']['role']);
        self::assertArrayNotHasKey('password_hash', $body['response']['user']);
        $token = $body['response']['csrf_token']; // nach Registrierung neuer Token
        self::assertTrue(strlen($token) >= 32);

        // 3) me liefert jetzt den Benutzer
        [, $body] = $this->call('GET', '/api/auth/me');
        self::assertSame('anna@example.de', $body['response']['user']['email']);

        // 4) Logout
        [$status, $body] = $this->call('POST', '/api/auth/logout', [], $token);
        self::assertSame(200, $status);
        self::assertSame(['ok' => true], $body['response']);

        // 5) Danach wieder Gast, mit neuem Token
        [, $body] = $this->call('GET', '/api/auth/me');
        self::assertNull($body['response']['user']);
        $token = $body['response']['csrf_token'];

        // 6) Login
        [$status, $body] = $this->call('POST', '/api/auth/login', ['email' => 'anna@example.de', 'password' => 'geheim123'], $token);
        self::assertSame(200, $status);
        self::assertSame('anna@example.de', $body['response']['user']['email']);
    }

    public function testChangingRequestsWithoutCsrfTokenAreRejected(): void
    {
        foreach (['/api/auth/register', '/api/auth/login'] as $path) {
            [$status, $body] = $this->call('POST', $path, ['email' => 'a@b.de', 'password' => 'geheim123']);

            self::assertSame(403, $status);
            self::assertTrue($body['error']);
            self::assertNull($body['response']);
        }
    }

    public function testLogoutRequiresLogin(): void
    {
        $token = $this->call('GET', '/api/auth/me')[1]['response']['csrf_token'];

        [$status] = $this->call('POST', '/api/auth/logout', [], $token);

        self::assertSame(401, $status);
    }

    public function testRegisterValidationErrorsAreListedPerField(): void
    {
        $token = $this->call('GET', '/api/auth/me')[1]['response']['csrf_token'];

        [$status, $body] = $this->call('POST', '/api/auth/register', ['email' => 'kaputt', 'password' => 'kurz'], $token);

        self::assertSame(400, $status);
        self::assertTrue($body['error']);
        $errors = $body['response']['errors'];
        self::assertArrayHasKey('email', $errors);
        self::assertArrayHasKey('password', $errors);
        self::assertArrayHasKey('first_name', $errors);
        self::assertArrayHasKey('last_name', $errors);
    }

    public function testDuplicateRegistrationReturns409(): void
    {
        $token = $this->call('GET', '/api/auth/me')[1]['response']['csrf_token'];
        $data = ['email' => 'anna@example.de', 'password' => 'geheim123', 'first_name' => 'Anna', 'last_name' => 'B'];
        $this->call('POST', '/api/auth/register', $data, $token);
        $this->call('POST', '/api/auth/logout', [], $this->call('GET', '/api/auth/me')[1]['response']['csrf_token']);

        $token = $this->call('GET', '/api/auth/me')[1]['response']['csrf_token'];
        [$status, $body] = $this->call('POST', '/api/auth/register', $data, $token);

        self::assertSame(409, $status);
        self::assertSame('Diese E-Mail-Adresse ist bereits registriert', $body['message']);
    }

    public function testWrongLoginReturns401WithEnvelope(): void
    {
        $token = $this->call('GET', '/api/auth/me')[1]['response']['csrf_token'];

        [$status, $body] = $this->call('POST', '/api/auth/login', ['email' => 'x@example.de', 'password' => 'falsch123'], $token);

        self::assertSame(401, $status);
        self::assertSame(['statuscode' => 401, 'error' => true, 'message' => 'E-Mail oder Passwort falsch', 'response' => null], $body);
    }

    public function testRoleFromDatabaseIsUsedForExistingSession(): void
    {
        $token = $this->call('GET', '/api/auth/me')[1]['response']['csrf_token'];
        $this->call('POST', '/api/auth/register', ['email' => 'a@example.de', 'password' => 'geheim123', 'first_name' => 'A', 'last_name' => 'B'], $token);

        $this->db->execute("UPDATE users SET role = 'vorstand'");

        self::assertSame('vorstand', $this->call('GET', '/api/auth/me')[1]['response']['user']['role']);
    }
}
