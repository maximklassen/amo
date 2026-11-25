<?php

declare(strict_types=1);

namespace Tests\Database;

use App\Database\Connection;
use App\Repositories\SettingsRepository;
use PHPUnit\Framework\TestCase;

final class SettingsRepositoryTest extends TestCase
{
    private SettingsRepository $settings;

    protected function setUp(): void
    {
        $db = Connection::fromConfig(['driver' => 'sqlite', 'database' => ':memory:']);

        // Gleiche Struktur wie die Tabelle "settings" in schema.sql (hier in der SQLite-Variante für den Test)
        $db->execute('CREATE TABLE settings (
            name VARCHAR(100) NOT NULL PRIMARY KEY,
            value TEXT NOT NULL,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        )');

        $this->settings = new SettingsRepository($db);
    }

    public function testGetReturnsDefaultForUnknownName(): void
    {
        self::assertNull($this->settings->get('tarif.unbekannt'));
        self::assertSame('x', $this->settings->get('tarif.unbekannt', 'x'));
    }

    public function testSetCreatesAndOverwrites(): void
    {
        $this->settings->set('tarif.pacht_pro_m2', '0.25');
        self::assertSame('0.25', $this->settings->get('tarif.pacht_pro_m2'));

        $this->settings->set('tarif.pacht_pro_m2', 0.3);
        self::assertSame('0.3', $this->settings->get('tarif.pacht_pro_m2'));
        self::assertCount(1, $this->settings->all());
    }

    public function testTypedGetters(): void
    {
        $this->settings->set('arbeit.soll_stunden', 10);
        $this->settings->set('tarif.strom_pro_kwh', '0.35');
        $this->settings->set('text', 'abc');

        self::assertSame(10, $this->settings->getInt('arbeit.soll_stunden'));
        self::assertSame(0.35, $this->settings->getFloat('tarif.strom_pro_kwh'));

        self::assertSame(7, $this->settings->getInt('text', 7));
        self::assertSame(1.5, $this->settings->getFloat('fehlt', 1.5));
    }

    public function testAllIsSortedByName(): void
    {
        $this->settings->set('b', '2');
        $this->settings->set('a', '1');

        self::assertSame(['a' => '1', 'b' => '2'], $this->settings->all());
    }
}
