<?php

declare(strict_types=1);

namespace Tests\Http;

final class ProfileTest extends ApiTestCase
{
    public function testGuestGets401OnAllProfileRoutes(): void
    {
        $token = $this->csrfToken();

        self::assertSame(401, $this->call('GET', '/api/profile')[0]);
        self::assertSame(401, $this->call('PUT', '/api/profile', ['first_name' => 'A', 'last_name' => 'B'], $token)[0]);
        self::assertSame(401, $this->call('PUT', '/api/profile/password', ['old_password' => 'x', 'new_password' => 'geheim1234'], $token)[0]);
    }

    public function testShowReturnsOwnProfileWithoutPasswordHash(): void
    {
        $this->loginAs('paechter');

        [$status, $body] = $this->call('GET', '/api/profile');

        self::assertSame(200, $status);
        self::assertSame('u@example.de', $body['response']['email']);
        self::assertSame('paechter', $body['response']['role']);
        self::assertSame('Test', $body['response']['first_name']);
        self::assertArrayHasKey('created_at', $body['response']);
        self::assertArrayNotHasKey('password_hash', $body['response']);
    }

    public function testUpdateChangesOnlyOwnPersonalData(): void
    {
        $token = $this->loginAs('paechter');
        $otherId = $this->insertUser('andere@example.de', 'geheim123', 'paechter');

        [$status, $body] = $this->call('PUT', '/api/profile', [
            'first_name' => 'Anna', 'last_name' => 'Schulz', 'phone' => '0511 999',
            'street' => 'Neue Str. 1', 'city' => 'Hannover', 'zip' => '30167',
        ], $token);

        self::assertSame(200, $status);
        self::assertSame('Anna', $body['response']['first_name']);
        self::assertSame('30167', $body['response']['zip']);
        self::assertSame('Anna', $this->call('GET', '/api/profile')[1]['response']['first_name']);
        // fremdes Profil unverändert
        self::assertSame('Test', $this->db->scalar('SELECT first_name FROM users_data WHERE user_id = :id', ['id' => $otherId]));
    }

    public function testUpdateCannotChangeEmailOrRole(): void
    {
        $token = $this->loginAs('bewerber');

        [, $body] = $this->call('PUT', '/api/profile', [
            'first_name' => 'A', 'last_name' => 'B', 'email' => 'hacker@example.de', 'role' => 'vorstand',
        ], $token);

        self::assertSame('u@example.de', $body['response']['email']);
        self::assertSame('bewerber', $body['response']['role']);
    }

    public function testUpdateClearsOptionalFieldsThatAreOmitted(): void
    {
        $token = $this->loginAs('paechter');
        $this->call('PUT', '/api/profile', ['first_name' => 'A', 'last_name' => 'B', 'phone' => '0511 1', 'city' => 'Hannover'], $token);

        [, $body] = $this->call('PUT', '/api/profile', ['first_name' => 'A', 'last_name' => 'B'], $token);

        self::assertNull($body['response']['phone']);
        self::assertNull($body['response']['city']);
    }

    public function testUpdateCreatesMissingUsersDataRow(): void
    {
        $token = $this->loginAs('vorstand');
        $this->db->execute('DELETE FROM users_data'); // Konto ohne persönliche Daten (z. B. aus dem Seed)

        [$status, $body] = $this->call('PUT', '/api/profile', ['first_name' => 'Neu', 'last_name' => 'Angelegt'], $token);

        self::assertSame(200, $status);
        self::assertSame('Neu', $body['response']['first_name']);
    }

    public function testUpdateValidationErrorsReturn400(): void
    {
        $token = $this->loginAs('paechter');

        [$status, $body] = $this->call('PUT', '/api/profile', ['first_name' => '', 'last_name' => 'B', 'zip' => '12'], $token);

        self::assertSame(400, $status);
        self::assertSame(['first_name', 'zip'], array_keys($body['response']['errors']));
    }

    public function testUpdateRequiresCsrfToken(): void
    {
        $this->loginAs('paechter');

        self::assertSame(403, $this->call('PUT', '/api/profile', ['first_name' => 'A', 'last_name' => 'B'])[0]);
    }

    // ---------- Passwort ----------

    public function testChangePasswordSucceedsAndNewPasswordWorks(): void
    {
        $token = $this->loginAs('paechter');

        [$status, $body] = $this->call('PUT', '/api/profile/password', ['old_password' => 'geheim123', 'new_password' => 'neuesPasswort1'], $token);

        self::assertSame(200, $status);
        self::assertSame(['ok' => true], $body['response']);
        // weiterhin angemeldet, CSRF-Token bleibt gültig
        self::assertSame(200, $this->call('GET', '/api/profile')[0]);
        self::assertSame(200, $this->call('PUT', '/api/profile', ['first_name' => 'A', 'last_name' => 'B'], $token)[0]);

        // Abmelden, mit dem alten Passwort geht es nicht mehr, mit dem neuen schon
        $this->call('POST', '/api/auth/logout', [], $token);
        $guestToken = $this->csrfToken();
        self::assertSame(401, $this->call('POST', '/api/auth/login', ['email' => 'u@example.de', 'password' => 'geheim123'], $guestToken)[0]);
        self::assertSame(200, $this->call('POST', '/api/auth/login', ['email' => 'u@example.de', 'password' => 'neuesPasswort1'], $guestToken)[0]);
    }

    public function testWrongOldPasswordReturns400NotUnauthorized(): void
    {
        $token = $this->loginAs('paechter');

        [$status, $body] = $this->call('PUT', '/api/profile/password', ['old_password' => 'falsch', 'new_password' => 'neuesPasswort1'], $token);

        self::assertSame(400, $status); // nicht 401: das Frontend würde sonst abmelden
        self::assertSame('Das alte Passwort ist falsch', $body['message']);
        self::assertSame(200, $this->call('GET', '/api/profile')[0]); // weiterhin angemeldet
    }

    public function testNewPasswordTooShortReturns400(): void
    {
        $token = $this->loginAs('paechter');

        [$status, $body] = $this->call('PUT', '/api/profile/password', ['old_password' => 'geheim123', 'new_password' => 'kurz'], $token);

        self::assertSame(400, $status);
        self::assertArrayHasKey('new_password', $body['response']['errors']);
    }

    public function testNewPasswordMustDifferFromOld(): void
    {
        $token = $this->loginAs('paechter');

        [$status, $body] = $this->call('PUT', '/api/profile/password', ['old_password' => 'geheim123', 'new_password' => 'geheim123'], $token);

        self::assertSame(400, $status);
        self::assertArrayHasKey('new_password', $body['response']['errors']);
    }

    public function testFailedPasswordChangeLeavesHashUntouched(): void
    {
        $token = $this->loginAs('paechter');
        $before = $this->db->scalar('SELECT password_hash FROM users');

        $this->call('PUT', '/api/profile/password', ['old_password' => 'falsch', 'new_password' => 'neuesPasswort1'], $token);

        self::assertSame($before, $this->db->scalar('SELECT password_hash FROM users'));
    }
}
