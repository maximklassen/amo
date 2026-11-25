<?php

declare(strict_types=1);

namespace App\Http;

use RuntimeException;

/**
 * Session auf Basis der PHP-Sessions ($_SESSION). Wird erst beim ersten Zugriff gestartet.
 *
 * Cookie-Schutz:
 * - HttpOnly:   JavaScript kann das Cookie nicht lesen (schützt vor Diebstahl per XSS)
 * - SameSite:   Das Cookie wird bei Anfragen von fremden Seiten nicht mitgeschickt (CSRF-Schutz)
 * - Secure:     Das Cookie wird nur über HTTPS gesendet (automatisch, wenn die Seite per HTTPS läuft)
 * - Strict Mode: PHP akzeptiert keine selbst ausgedachten Session-IDs
 */
final class NativeSession implements Session
{
    public function __construct(
        private readonly string $name = 'PARZELLEN_SID',
        private readonly ?bool $secure = null,
        private readonly string $sameSite = 'Lax',
    ) {
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $this->start();

        return $_SESSION[$key] ?? $default;
    }

    public function set(string $key, mixed $value): void
    {
        $this->start();
        $_SESSION[$key] = $value;
    }

    public function forget(string $key): void
    {
        $this->start();
        unset($_SESSION[$key]);
    }

    public function regenerate(): void
    {
        $this->start();
        // true: die alte Session-Datei wird gelöscht
        session_regenerate_id(true);
    }

    public function invalidate(): void
    {
        $this->start();
        $_SESSION = [];
        session_regenerate_id(true);
    }

    private function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        if (headers_sent()) {
            throw new RuntimeException('Die Session kann nicht gestartet werden, weil schon Ausgaben gesendet wurden');
        }

        $https = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== '' && $_SERVER['HTTPS'] !== 'off';

        session_start([
            'name'              => $this->name,
            'use_strict_mode'   => 1,
            'use_only_cookies'  => 1,
            'use_trans_sid'     => 0,
            'cookie_httponly'   => 1,
            'cookie_secure'     => ($this->secure ?? $https) ? 1 : 0,
            'cookie_samesite'   => $this->sameSite,
            'cookie_lifetime'   => 0, // Cookie endet mit dem Schließen des Browsers
        ]);
    }
}
