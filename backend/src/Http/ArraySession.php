<?php

declare(strict_types=1);

namespace App\Http;

/** Session nur im Arbeitsspeicher, für Tests. Zählt mit, wie oft die ID erneuert wurde. */
final class ArraySession implements Session
{
    /** @var array<string, mixed> */
    private array $data = [];

    public int $regenerations = 0;

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    public function set(string $key, mixed $value): void
    {
        $this->data[$key] = $value;
    }

    public function forget(string $key): void
    {
        unset($this->data[$key]);
    }

    public function regenerate(): void
    {
        $this->regenerations++;
    }

    public function invalidate(): void
    {
        $this->data = [];
        $this->regenerations++;
    }
}
