<?php

declare(strict_types=1);

namespace Tests\Auth;

final class AuthTest extends AuthTestCase
{
    // ---------- Registrierung ----------

    public function testRegisterCreatesBewerberAndLogsIn(): void
    {
        $user = $this->auth->register($this->registrationData());

        self::assertSame('anna@example.de', $user['email']);
        self::assertSame('bewerber', $user['role']);
        self::assertSame('Anna', $user['first_name']);
        self::assertSame('29664', $user['zip']);
        self::assertSame($user['id'], $this->session->get('user_id'));
        self::assertSame($user['id'], $this->makeAuth()->user()['id']); // nächste Anfrage erkennt den Benutzer
    }

    public function testRegisterStoresOnlyAHashNeverThePassword(): void
    {
        $this->auth->register($this->registrationData());
        $hash = (string) $this->db->scalar('SELECT password_hash FROM users');

        self::assertTrue(password_verify('geheim123', $hash));
        self::assertStringNotContainsString('geheim123', $hash);
    }

    public function testRegisterAlwaysGivesRoleBewerber(): void
    {
        $data = $this->registrationData() + ['role' => 'vorstand']; // eingeschleustes Feld wird ignoriert
        $user = $this->auth->register($data);

        self::assertSame('bewerber', $user['role']);
    }

    public function testRegisterRegeneratesSessionIdAndRotatesCsrfToken(): void
    {
        $before = $this->csrf->token();
        $this->auth->register($this->registrationData());

        self::assertSame(1, $this->session->regenerations);
        self::assertNotSame($before, $this->csrf->token());
    }

    public function testRegisterWithTakenEmailReturns409(): void
    {
        $this->auth->register($this->registrationData());

        $e = $this->catchHttp(fn () => $this->makeAuth()->register($this->registrationData()));

        self::assertSame(409, $e->getStatus());
        self::assertSame('email_taken', $e->getErrorCode());
        self::assertSame(1, (int) $this->db->scalar('SELECT COUNT(*) FROM users'));
    }

    public function testFailedProfileInsertRollsBackTheUser(): void
    {
        $data = $this->registrationData();
        $data['first_name'] = null; // verletzt NOT NULL in users_data

        try {
            $this->auth->register($data);
            self::fail('Ausnahme erwartet');
        } catch (\PDOException) {
            self::addToAssertionCount(1);
        }

        self::assertSame(0, (int) $this->db->scalar('SELECT COUNT(*) FROM users'));
    }

    // ---------- Login ----------

    public function testLoginWithCorrectCredentials(): void
    {
        $id = $this->insertUser('paul@example.de', 'passwort1', 'paechter');

        $user = $this->auth->login('paul@example.de', 'passwort1', '10.0.0.1');

        self::assertSame($id, $user['id']);
        self::assertSame('paechter', $user['role']);
        self::assertSame($id, $this->session->get('user_id'));
    }

    public function testLoginWorksForEveryRole(): void
    {
        foreach (['bewerber', 'paechter', 'vorstand'] as $i => $role) {
            $this->insertUser("u$i@example.de", 'passwort1', $role);
            $user = $this->makeAuth()->login("u$i@example.de", 'passwort1', '10.0.0.1');
            self::assertSame($role, $user['role']);
        }
    }

    public function testLoginRegeneratesSessionIdAndRotatesCsrfToken(): void
    {
        $this->insertUser('paul@example.de', 'passwort1', 'paechter');
        $before = $this->csrf->token();

        $this->auth->login('paul@example.de', 'passwort1', '10.0.0.1');

        self::assertSame(1, $this->session->regenerations); // gegen Session Fixation
        self::assertNotSame($before, $this->csrf->token());
    }

    public function testWrongPasswordAndUnknownEmailGiveIdenticalError(): void
    {
        $this->insertUser('paul@example.de', 'passwort1', 'paechter');

        $wrongPassword = $this->catchHttp(fn () => $this->auth->login('paul@example.de', 'falsch', '10.0.0.1'));
        $unknownEmail = $this->catchHttp(fn () => $this->auth->login('niemand@example.de', 'falsch', '10.0.0.1'));

        self::assertSame(401, $wrongPassword->getStatus());
        self::assertSame(401, $unknownEmail->getStatus());
        self::assertSame($wrongPassword->getMessage(), $unknownEmail->getMessage());
        self::assertSame($wrongPassword->getErrorCode(), $unknownEmail->getErrorCode());
        self::assertNull($this->session->get('user_id'));
    }

    public function testFailedLoginsAreRecorded(): void
    {
        $this->catchHttp(fn () => $this->auth->login('x@example.de', 'falsch', '10.0.0.1'));

        self::assertSame(1, (int) $this->db->scalar('SELECT COUNT(*) FROM login_attempts'));
    }

    public function testLockoutAfterFiveFailuresEvenWithCorrectPassword(): void
    {
        $this->insertUser('paul@example.de', 'passwort1', 'paechter');

        for ($i = 0; $i < 5; $i++) {
            $this->catchHttp(fn () => $this->auth->login('paul@example.de', 'falsch', '10.0.0.' . $i));
        }

        $e = $this->catchHttp(fn () => $this->auth->login('paul@example.de', 'passwort1', '10.0.0.99'));

        self::assertSame(429, $e->getStatus());
        self::assertSame('900', $e->getHeaders()['Retry-After']);
        self::assertNull($this->session->get('user_id'));
    }

    public function testLockoutIsPerEmail(): void
    {
        $this->insertUser('paul@example.de', 'passwort1', 'paechter');
        $this->insertUser('anna@example.de', 'passwort2', 'paechter');

        for ($i = 0; $i < 5; $i++) {
            $this->catchHttp(fn () => $this->auth->login('paul@example.de', 'falsch', '10.0.0.' . $i));
        }

        $user = $this->auth->login('anna@example.de', 'passwort2', '10.0.1.1');
        self::assertSame('anna@example.de', $user['email']);
    }

    public function testLockoutPerIpAfterTwentyFailuresForDifferentEmails(): void
    {
        for ($i = 0; $i < 20; $i++) {
            $this->catchHttp(fn () => $this->auth->login("x$i@example.de", 'falsch', '10.9.9.9'));
        }

        $e = $this->catchHttp(fn () => $this->auth->login('neu@example.de', 'falsch', '10.9.9.9'));

        self::assertSame(429, $e->getStatus());
    }

    public function testOldFailuresOutsideWindowDoNotCount(): void
    {
        $this->insertUser('paul@example.de', 'passwort1', 'paechter');
        $old = date('Y-m-d H:i:s', time() - 16 * 60);

        for ($i = 0; $i < 5; $i++) {
            $this->db->execute(
                'INSERT INTO login_attempts (email, ip, attempted_at) VALUES (:e, :ip, :t)',
                ['e' => 'paul@example.de', 'ip' => '10.0.0.1', 't' => $old],
            );
        }

        $user = $this->auth->login('paul@example.de', 'passwort1', '10.0.0.1');
        self::assertSame('paul@example.de', $user['email']);
    }

    public function testSuccessfulLoginClearsFailureCounter(): void
    {
        $this->insertUser('paul@example.de', 'passwort1', 'paechter');
        $this->catchHttp(fn () => $this->auth->login('paul@example.de', 'falsch', '10.0.0.1'));

        $this->auth->login('paul@example.de', 'passwort1', '10.0.0.1');

        self::assertSame(0, (int) $this->db->scalar('SELECT COUNT(*) FROM login_attempts'));
    }

    public function testHashIsUpgradedWhenCostChanges(): void
    {
        $id = $this->insertUser('paul@example.de', 'passwort1', 'paechter'); // Hash mit cost 4
        $old = (string) $this->db->scalar('SELECT password_hash FROM users WHERE id = :id', ['id' => $id]);

        $this->makeAuth(5)->login('paul@example.de', 'passwort1', '10.0.0.1');

        $new = (string) $this->db->scalar('SELECT password_hash FROM users WHERE id = :id', ['id' => $id]);
        self::assertNotSame($old, $new);
        self::assertTrue(password_verify('passwort1', $new));
        self::assertTrue(str_starts_with($new, '$2y$05$'));
    }

    // ---------- Logout ----------

    public function testLogoutEndsSessionAndRotatesCsrfToken(): void
    {
        $this->insertUser('paul@example.de', 'passwort1', 'paechter');
        $this->auth->login('paul@example.de', 'passwort1', '10.0.0.1');
        $token = $this->csrf->token();

        $this->auth->logout();

        self::assertNull($this->auth->user());
        self::assertNull($this->session->get('user_id'));
        self::assertNull($this->makeAuth()->user());
        self::assertNotSame($token, $this->csrf->token());
    }

    // ---------- user() ----------

    public function testGuestHasNoUser(): void
    {
        self::assertNull($this->auth->user());
    }

    public function testUserDoesNotExposePasswordHash(): void
    {
        $this->auth->register($this->registrationData());

        self::assertArrayNotHasKey('password_hash', $this->makeAuth()->user());
    }

    public function testIdleTimeoutEndsSession(): void
    {
        $this->auth->register($this->registrationData());
        $this->session->set('last_activity', time() - 121 * 60);

        self::assertNull($this->makeAuth()->user());
        self::assertNull($this->session->get('user_id'));
    }

    public function testActivityExtendsSession(): void
    {
        $this->auth->register($this->registrationData());
        $this->session->set('last_activity', time() - 60 * 60); // noch innerhalb von 120 Minuten

        self::assertTrue($this->makeAuth()->user() !== null);
        self::assertTrue(time() - (int) $this->session->get('last_activity') < 5);
    }

    public function testDeletedAccountLogsOutImmediately(): void
    {
        $user = $this->auth->register($this->registrationData());
        $this->db->execute('DELETE FROM users WHERE id = :id', ['id' => $user['id']]);

        self::assertNull($this->makeAuth()->user());
        self::assertNull($this->session->get('user_id'));
    }

    public function testRoleChangeInDatabaseAppliesOnNextRequest(): void
    {
        $user = $this->auth->register($this->registrationData());
        $this->db->execute("UPDATE users SET role = 'paechter' WHERE id = :id", ['id' => $user['id']]);

        self::assertSame('paechter', $this->makeAuth()->user()['role']);
    }
}
