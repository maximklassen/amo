<?php

declare(strict_types=1);

namespace App\Core;

use App\Auth\Auth;
use App\Database\Connection;
use App\Http\Csrf;
use App\Http\NativeSession;
use App\Http\Router;
use App\Http\Session;
use App\Support\Logger;
use App\Support\Path;
use ErrorException;

/**
 * Startet die Anwendung: lädt .env und config/, richtet PHP ein und registriert
 * die Kern-Dienste im Container. Wird von public/index.php genutzt.
 */
final class Application
{
    private function __construct(
        private readonly string $basePath,
        private readonly Container $container,
        private readonly Config $config,
    ) {
    }

    public static function boot(string $basePath): self
    {
        Env::load($basePath);

        $config = Config::fromDirectory($basePath . '/config');
        $app = new self($basePath, new Container(), $config);

        $app->configurePhp();
        $app->registerServices();

        return $app;
    }

    public function get(string $id): object
    {
        return $this->container->get($id);
    }

    public function config(): Config
    {
        return $this->config;
    }

    /** Absoluter Pfad innerhalb von backend/, z. B. path('storage'). */
    public function path(string $relative = ''): string
    {
        return $relative === '' ? $this->basePath : Path::resolve($this->basePath, $relative);
    }

    private function configurePhp(): void
    {
        date_default_timezone_set((string) $this->config->get('app.timezone', 'UTC'));

        error_reporting(E_ALL);
        // Im Web nie Fehler an den Browser ausgeben (Kernel liefert JSON), in der Konsole schon.
        ini_set('display_errors', PHP_SAPI === 'cli' ? '1' : '0');
        ini_set('log_errors', '1');

        // PHP-Warnungen und -Notices werden zu Ausnahmen, damit sie nicht unbemerkt bleiben.
        // Deprecations und mit @ unterdrückte Meldungen laufen weiter über den normalen PHP-Handler.
        set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
            if (($severity & (E_DEPRECATED | E_USER_DEPRECATED)) !== 0 || (error_reporting() & $severity) === 0) {
                return false;
            }

            throw new ErrorException($message, 0, $severity, $file, $line);
        });
    }

    private function registerServices(): void
    {
        $container = $this->container;
        $config = $this->config;

        $container->instance(self::class, $this);
        $container->instance(Container::class, $container);
        $container->instance(Config::class, $config);

        $container->singleton(Logger::class, fn (): Logger => new Logger(
            Path::resolve($this->basePath, (string) $config->get('app.log_path', 'storage/logs/app.log')),
            (string) $config->get('app.log_level', 'debug'),
        ));

        // Die Verbindung wird erst beim ersten Datenbankzugriff aufgebaut.
        $container->singleton(Connection::class, fn (): Connection => Connection::fromConfig(
            (array) $config->get('database', []),
            $this->basePath,
        ));

        // Pro Anfrage nur einmal: Middleware und Controller sehen denselben angemeldeten Benutzer
        $container->share(Csrf::class, Auth::class);

        // Session-Cookie: Name, Secure-Flag und SameSite kommen aus config/auth.php
        $container->singleton(Session::class, fn (): Session => new NativeSession(
            (string) $config->get('auth.session.name', 'PARZELLEN_SID'),
            $config->get('auth.session.secure'),
            (string) $config->get('auth.session.same_site', 'Lax'),
        ));

        $container->singleton(Router::class, function (): Router {
            $router = new Router();

            /** @var callable(Router): void $routes */
            $routes = require $this->path('routes/api.php');
            $routes($router);

            return $router;
        });
    }
}
