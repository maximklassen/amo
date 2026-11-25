<?php

declare(strict_types=1);

namespace App\Core;

use Dotenv\Dotenv;

/**
 * Zugriff auf Umgebungsvariablen (.env-Datei und echte Server-Umgebung).
 *
 * Bewusst ohne "Magie": Werte wie "true" oder "null" werden nicht automatisch
 * umgewandelt, sondern explizit über string(), bool() und int() gelesen.
 * So wird ein Passwort wie "null" nie versehentlich zu einem anderen Typ.
 */
final class Env
{
    public static function load(string $directory): void
    {
        // safeLoad(): eine fehlende .env-Datei ist kein Fehler (z. B. wenn der Server die Variablen setzt).
        // Der Immutable-Modus überschreibt bereits gesetzte echte Umgebungsvariablen nicht.
        Dotenv::createImmutable($directory)->safeLoad();
    }

    public static function string(string $key, ?string $default = null): ?string
    {
        // $_ENV ist mit der Standard-php.ini oft leer (variables_order), daher getenv() als Rückfall.
        $value = $_ENV[$key] ?? getenv($key);

        return $value === false || $value === null ? $default : (string) $value;
    }

    public static function bool(string $key, bool $default = false): bool
    {
        $value = self::string($key);

        if ($value === null || trim($value) === '') {
            return $default;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? $default;
    }

    public static function int(string $key, int $default = 0): int
    {
        $value = self::string($key);

        return $value !== null && is_numeric($value) ? (int) $value : $default;
    }
}
