<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Database\Connection;

/**
 * Zugriff auf die Tabelle "settings" (Schlüssel/Wert-Paare, z. B. Tarife).
 * Werte werden als Text gespeichert und beim Lesen typisiert.
 */
final class SettingsRepository
{
    private const TABLE = 'settings';

    public function __construct(private readonly Connection $db)
    {
    }

    public function get(string $name, ?string $default = null): ?string
    {
        $value = $this->db->scalar('SELECT value FROM settings WHERE name = :name', ['name' => $name]);

        return $value === null ? $default : (string) $value;
    }

    public function getInt(string $name, int $default = 0): int
    {
        $value = $this->get($name);

        return $value !== null && is_numeric($value) ? (int) $value : $default;
    }

    public function getFloat(string $name, float $default = 0.0): float
    {
        $value = $this->get($name);

        return $value !== null && is_numeric($value) ? (float) $value : $default;
    }

    /** Legt die Einstellung an oder überschreibt sie. */
    public function set(string $name, string|int|float $value): void
    {
        $this->db->upsert(self::TABLE, [
            'name'       => $name,
            'value'      => (string) $value,
            'updated_at' => date('Y-m-d H:i:s'),
        ], ['name']);
    }

    /** @return array<string, string> Name => Wert, alphabetisch sortiert */
    public function all(): array
    {
        $settings = [];

        foreach ($this->db->select('SELECT name, value FROM settings ORDER BY name') as $row) {
            $settings[(string) $row['name']] = (string) $row['value'];
        }

        return $settings;
    }
}
