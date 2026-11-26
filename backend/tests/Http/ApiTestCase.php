<?php

declare(strict_types=1);

namespace Tests\Http;

use App\Auth\Auth;
use App\Core\Config;
use App\Core\Container;
use App\Database\Connection;
use App\Http\ArraySession;
use App\Http\Csrf;
use App\Http\Kernel;
use App\Http\Request;
use App\Http\Router;
use App\Http\Session;
use App\Support\Logger;
use Tests\Auth\AuthTestCase;

/**
 * Basis für Tests über die ganze Kette (Routen aus routes/api.php, Middleware, Controller, Auth, SQLite).
 * Jede Anfrage bekommt einen neuen Container (wie bei PHP pro Request), nur Session und Datenbank bleiben
 * erhalten (Session = das Cookie des Browsers).
 */
abstract class ApiTestCase extends AuthTestCase
{
    private string $logFile;

    protected function setUp(): void
    {
        parent::setUp();
        $this->logFile = sys_get_temp_dir() . '/api_' . bin2hex(random_bytes(4)) . '.log';
    }

    protected function tearDown(): void
    {
        if (is_file($this->logFile)) {
            unlink($this->logFile);
        }
    }

    /** Aktueller CSRF-Token der Session (wie GET /api/auth/me beim Laden der App). */
    protected function csrfToken(): string
    {
        return $this->call('GET', '/api/auth/me')[1]['response']['csrf_token'];
    }

    /** Legt ein Konto an und meldet über die API an. @return string CSRF-Token nach dem Login */
    protected function loginAs(string $role, string $email = 'u@example.de', string $password = 'geheim123'): string
    {
        $this->insertUser($email, $password, $role);
        [, $body] = $this->call('POST', '/api/auth/login', ['email' => $email, 'password' => $password], $this->csrfToken());

        return $body['response']['csrf_token'];
    }

    /**
     * Eine Anfrage wie im Browser.
     *
     * @param array<string, mixed>|null $json
     * @return array{0: int, 1: array<string, mixed>} [HTTP-Status, dekodierter Body]
     */
    protected function call(string $method, string $path, ?array $json = null, ?string $csrfToken = null): array
    {
        $headers = [];
        $raw = '';

        if ($json !== null) {
            $headers['Content-Type'] = 'application/json';
            $raw = json_encode($json, JSON_THROW_ON_ERROR);
        }

        if ($csrfToken !== null) {
            $headers['X-CSRF-Token'] = $csrfToken;
        }

        $container = new Container();
        $container->instance(Container::class, $container);
        $container->instance(Connection::class, $this->db);
        $container->instance(Session::class, $this->session);
        $container->instance(ArraySession::class, $this->session);
        $config = new Config(['auth' => [
            'bcrypt_cost' => 4, 'password_min_length' => 8,
            'max_failed_per_email' => 5, 'max_failed_per_ip' => 20, 'lockout_minutes' => 15,
            'session' => ['idle_minutes' => 120],
        ], 'app' => ['debug' => true]]);
        $container->instance(Config::class, $config);
        $container->share(Csrf::class, Auth::class);

        $router = new Router();
        (require dirname(__DIR__, 2) . '/routes/api.php')($router);

        $kernel = new Kernel($container, $router, new Logger($this->logFile), $config);
        $response = $kernel->handle(new Request($method, $path, headers: $headers, rawBody: $raw, ip: '10.0.0.1'));

        return [$response->status(), json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR)];
    }
}
