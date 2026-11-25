<?php

declare(strict_types=1);

namespace App\Http;

/**
 * CSRF-Schutz: Ein fremde Webseite könnte den Browser dazu bringen, Anfragen mit dem
 * Session-Cookie des Benutzers abzuschicken. Deshalb muss jede ändernde Anfrage zusätzlich
 * einen geheimen Token im Header "X-CSRF-Token" mitschicken, den nur unsere eigene Seite kennt
 * (sie bekommt ihn von GET /api/auth/me). Der Token liegt in der Session.
 */
final class Csrf
{
    private const KEY = '_csrf_token';

    public function __construct(private readonly Session $session)
    {
    }

    public function token(): string
    {
        $token = $this->session->get(self::KEY);

        if (!is_string($token) || $token === '') {
            $token = $this->rotate();
        }

        return $token;
    }

    /** Neuen Token erzeugen, z. B. nach Login und Logout. */
    public function rotate(): string
    {
        $token = bin2hex(random_bytes(32));
        $this->session->set(self::KEY, $token);

        return $token;
    }

    public function isValid(?string $given): bool
    {
        // hash_equals vergleicht in konstanter Zeit (kein Rückschluss über Zeitmessung)
        return $given !== null && $given !== '' && hash_equals($this->token(), $given);
    }
}
