<?php

declare(strict_types=1);

namespace Tests\Auth;

use App\Auth\Auth;
use App\Core\Config;
use App\Database\Connection;
use App\Http\ArraySession;
use App\Http\Csrf;
use App\Http\HttpException;
use App\Repositories\LoginAttemptRepository;
use App\Repositories\UserRepository;
use PHPUnit\Framework\TestCase;

/**
 * Gemeinsame Testumgebung für Auth-Tests: SQLite im Arbeitsspeicher (gleiche Spalten wie schema.sql),
 * Session im Arbeitsspeicher und ein niedriger bcrypt-Aufwand, damit die Tests schnell laufen.
 */
abstract class AuthTestCase extends TestCase
{
    protected Connection $db;
    protected ArraySession $session;
    protected Csrf $csrf;
    protected Auth $auth;

    protected function setUp(): void
    {
        $this->db = Connection::fromConfig(['driver' => 'sqlite', 'database' => ':memory:']);

        $this->db->execute('CREATE TABLE users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            email VARCHAR(255) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            role VARCHAR(20) NOT NULL DEFAULT \'bewerber\',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        )');
        $this->db->execute('CREATE TABLE users_data (
            user_id INTEGER PRIMARY KEY,
            first_name VARCHAR(100) NOT NULL,
            last_name VARCHAR(100) NOT NULL,
            phone VARCHAR(30) NULL,
            street VARCHAR(150) NULL,
            city VARCHAR(100) NULL,
            zip CHAR(5) NULL
        )');
        $this->db->execute('CREATE TABLE login_attempts (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            email VARCHAR(255) NOT NULL,
            ip VARCHAR(45) NOT NULL,
            attempted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        )');

        $this->session = new ArraySession();
        $this->csrf = new Csrf($this->session);
        $this->auth = $this->makeAuth();
    }

    /** Neues Auth-Objekt auf derselben Session und Datenbank (entspricht einer neuen Anfrage). */
    protected function makeAuth(int $cost = 4): Auth
    {
        return new Auth(
            $this->session,
            $this->csrf,
            $this->db,
            new UserRepository($this->db),
            new LoginAttemptRepository($this->db),
            new Config(['auth' => [
                'bcrypt_cost'          => $cost,
                'max_failed_per_email' => 5,
                'max_failed_per_ip'    => 20,
                'lockout_minutes'      => 15,
                'session'              => ['idle_minutes' => 120],
            ]]),
        );
    }

    /** @return array<string, mixed> */
    protected function registrationData(string $email = 'anna@example.de', string $password = 'geheim123'): array
    {
        return [
            'email' => $email, 'password' => $password,
            'first_name' => 'Anna', 'last_name' => 'Beispiel',
            'phone' => null, 'street' => 'Gartenweg 1', 'city' => 'Walsrode', 'zip' => '29664',
        ];
    }

    /** Legt direkt in der Datenbank ein Konto an (ohne Auth), liefert die ID. */
    protected function insertUser(string $email, string $password, string $role): int
    {
        $this->db->execute(
            'INSERT INTO users (email, password_hash, role) VALUES (:e, :h, :r)',
            ['e' => $email, 'h' => password_hash($password, PASSWORD_BCRYPT, ['cost' => 4]), 'r' => $role],
        );
        $id = (int) $this->db->pdo()->lastInsertId();
        $this->db->execute(
            'INSERT INTO users_data (user_id, first_name, last_name) VALUES (:id, :f, :l)',
            ['id' => $id, 'f' => 'Test', 'l' => ucfirst($role)],
        );

        return $id;
    }

    /** Führt $action aus und liefert die geworfene HttpException (oder lässt den Test scheitern). */
    protected function catchHttp(callable $action): HttpException
    {
        try {
            $action();
        } catch (HttpException $e) {
            return $e;
        }

        self::fail('HttpException erwartet, es wurde keine geworfen');
    }
}
