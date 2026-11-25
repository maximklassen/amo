<?php

declare(strict_types=1);

namespace App\Http;

/**
 * Serverseitiger Speicher pro Besucher (hinter einem Session-Cookie).
 * Ein Interface, damit Tests ohne echte Cookies auskommen (siehe ArraySession).
 */
interface Session
{
    public function get(string $key, mixed $default = null): mixed;

    public function set(string $key, mixed $value): void;

    public function forget(string $key): void;

    /** Neue Session-ID, Daten bleiben erhalten. Pflicht beim Login, sonst droht Session Fixation. */
    public function regenerate(): void;

    /** Alle Daten löschen und eine neue Session-ID vergeben (Logout). */
    public function invalidate(): void;
}
