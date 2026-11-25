<?php

declare(strict_types=1);

namespace App\Support;

/** Kleine Helfer für Dateipfade. */
final class Path
{
    public static function isAbsolute(string $path): bool
    {
        return str_starts_with($path, '/')
            || str_starts_with($path, '\\')
            || preg_match('~^[A-Za-z]:[\\\\/]~', $path) === 1;
    }

    /** Relative Pfade werden an $base angehängt, absolute bleiben unverändert. */
    public static function resolve(string $base, string $path): string
    {
        return self::isAbsolute($path) ? $path : rtrim($base, '/\\') . '/' . $path;
    }
}
