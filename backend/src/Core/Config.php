<?php

declare(strict_types=1);

namespace App\Core;

use stdClass;

/**
 * Konfigurationsspeicher mit Punkt-Notation, z. B. $config->get('database.host').
 * Jede Datei in config/ wird unter ihrem Dateinamen (ohne .php) eingelesen.
 */
final class Config
{
    /** @param array<string, mixed> $items */
    public function __construct(private array $items = [])
    {
    }

    public static function fromDirectory(string $directory): self
    {
        $items = [];

        foreach (glob(rtrim($directory, '/\\') . '/*.php') ?: [] as $file) {
            $items[basename($file, '.php')] = require $file;
        }

        return new self($items);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $value = $this->items;

        foreach (explode('.', $key) as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }

        return $value;
    }

    public function has(string $key): bool
    {
        $missing = new stdClass();

        return $this->get($key, $missing) !== $missing;
    }

    public function set(string $key, mixed $value): void
    {
        $target = &$this->items;

        foreach (explode('.', $key) as $segment) {
            if (!isset($target[$segment]) || !is_array($target[$segment])) {
                $target[$segment] = [];
            }
            $target = &$target[$segment];
        }

        $target = $value;
    }

    /** @return array<string, mixed> */
    public function all(): array
    {
        return $this->items;
    }
}
