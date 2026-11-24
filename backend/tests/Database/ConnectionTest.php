<?php

declare(strict_types=1);

namespace Tests\Database;

use App\Database\Connection;
use InvalidArgumentException;
use PDOException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ConnectionTest extends TestCase
{
    private Connection $db;

    protected function setUp(): void
    {
        $this->db = Connection::fromConfig(['driver' => 'sqlite', 'database' => ':memory:']);
        $this->db->execute('CREATE TABLE items (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL, qty INTEGER NOT NULL DEFAULT 0, note TEXT)');
        $this->db->execute('CREATE TABLE kv (k TEXT PRIMARY KEY, v TEXT NOT NULL)');
    }

    public function testInsertReturnsIdAndRowCanBeRead(): void
    {
        $id = $this->db->insert('items', ['name' => 'Spaten', 'qty' => 3]);

        self::assertSame('1', $id);
        self::assertSame(
            ['id' => 1, 'name' => 'Spaten', 'qty' => 3, 'note' => null],
            $this->db->first('SELECT * FROM items WHERE id = :id', ['id' => $id]),
        );
    }

    public function testSelectFirstAndScalar(): void
    {
        $this->db->insert('items', ['name' => 'A']);
        $this->db->insert('items', ['name' => 'B']);

        self::assertSame(['A', 'B'], array_column($this->db->select('SELECT name FROM items ORDER BY id'), 'name'));
        self::assertNull($this->db->first('SELECT * FROM items WHERE id = 99'));
        self::assertSame(2, $this->db->scalar('SELECT COUNT(*) FROM items'));
        self::assertNull($this->db->scalar('SELECT name FROM items WHERE id = 99'));
    }

    public function testUpdateChangesOnlyMatchingRows(): void
    {
        $this->db->insert('items', ['name' => 'A', 'qty' => 1]);
        $this->db->insert('items', ['name' => 'B', 'qty' => 1]);

        $affected = $this->db->update('items', ['qty' => 9], ['name' => 'B']);

        self::assertSame(1, $affected);
        self::assertSame([1, 9], array_map('intval', array_column($this->db->select('SELECT qty FROM items ORDER BY id'), 'qty')));
    }

    public function testUpdateAllowsSameColumnInSetAndWhere(): void
    {
        $this->db->insert('items', ['name' => 'A', 'qty' => 1]);

        self::assertSame(1, $this->db->update('items', ['qty' => 2], ['qty' => 1]));
        self::assertSame(2, $this->db->scalar('SELECT qty FROM items'));
    }

    public function testDeleteRemovesMatchingRows(): void
    {
        $this->db->insert('items', ['name' => 'A']);
        $this->db->insert('items', ['name' => 'B']);

        self::assertSame(1, $this->db->delete('items', ['name' => 'A']));
        self::assertSame(1, $this->db->scalar('SELECT COUNT(*) FROM items'));
    }

    public function testNullInWhereBecomesIsNull(): void
    {
        $this->db->insert('items', ['name' => 'A', 'note' => null]);
        $this->db->insert('items', ['name' => 'B', 'note' => 'x']);

        self::assertSame(1, $this->db->update('items', ['qty' => 5], ['note' => null]));
        self::assertSame('A', $this->db->scalar('SELECT name FROM items WHERE qty = 5'));
    }

    public function testEmptyWhereIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->db->delete('items', []);
    }

    public function testEmptyInsertDataIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->db->insert('items', []);
    }

    public function testInvalidIdentifiersAreRejected(): void
    {
        $this->db->insert('items', ['name' => 'A']);

        foreach (['name; DROP TABLE items', 'na me', '1abc', ''] as $bad) {
            try {
                $this->db->insert('items', [$bad => 'x']);
                self::fail('Ausnahme erwartet für: ' . $bad);
            } catch (InvalidArgumentException) {
                self::addToAssertionCount(1);
            }
        }

        $this->expectException(InvalidArgumentException::class);
        $this->db->update('items; DROP TABLE items', ['qty' => 1], ['id' => 1]);
    }

    public function testQuoteIdentifierUsesDriverSpecificQuotes(): void
    {
        self::assertSame('"items"', $this->db->quoteIdentifier('items'));
    }

    public function testBooleansAreBoundAsIntegers(): void
    {
        $this->db->insert('items', ['name' => 'A', 'qty' => true]);
        $this->db->insert('items', ['name' => 'B', 'qty' => false]);

        self::assertSame([1, 0], array_map('intval', array_column($this->db->select('SELECT qty FROM items ORDER BY id'), 'qty')));
    }

    public function testUpsertInsertsAndThenUpdates(): void
    {
        $this->db->upsert('kv', ['k' => 'a', 'v' => '1'], ['k']);
        $this->db->upsert('kv', ['k' => 'a', 'v' => '2'], ['k']);
        $this->db->upsert('kv', ['k' => 'b', 'v' => '3'], ['k']);

        self::assertSame(
            [['k' => 'a', 'v' => '2'], ['k' => 'b', 'v' => '3']],
            $this->db->select('SELECT k, v FROM kv ORDER BY k'),
        );
    }

    public function testUpsertWithOnlyKeyColumnsKeepsExistingRow(): void
    {
        $this->db->execute('CREATE TABLE tags (name TEXT PRIMARY KEY)');

        $this->db->upsert('tags', ['name' => 'x'], ['name']);
        $this->db->upsert('tags', ['name' => 'x'], ['name']);

        self::assertSame(1, $this->db->scalar('SELECT COUNT(*) FROM tags'));
    }

    public function testUpsertRequiresKeyColumnsInData(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->db->upsert('kv', ['v' => '1'], ['k']);
    }

    public function testTransactionCommitsAndReturnsCallbackResult(): void
    {
        $result = $this->db->transaction(function (Connection $db): string {
            $db->insert('items', ['name' => 'A']);

            return 'done';
        });

        self::assertSame('done', $result);
        self::assertSame(1, $this->db->scalar('SELECT COUNT(*) FROM items'));
    }

    public function testTransactionRollsBackOnException(): void
    {
        try {
            $this->db->transaction(function (Connection $db): void {
                $db->insert('items', ['name' => 'A']);
                throw new RuntimeException('boom');
            });
            self::fail('Ausnahme erwartet');
        } catch (RuntimeException $e) {
            self::assertSame('boom', $e->getMessage());
        }

        self::assertSame(0, $this->db->scalar('SELECT COUNT(*) FROM items'));
        self::assertFalse($this->db->pdo()->inTransaction());
    }

    public function testNestedTransactionRollsBackOnlyInnerBlock(): void
    {
        $this->db->transaction(function (Connection $db): void {
            $db->insert('items', ['name' => 'outer']);

            try {
                $db->transaction(function (Connection $db): void {
                    $db->insert('items', ['name' => 'inner']);
                    throw new RuntimeException('boom');
                });
            } catch (RuntimeException) {
                // erwartet: nur der innere Block wird zurückgerollt
            }

            $db->insert('items', ['name' => 'after']);
        });

        self::assertSame(['outer', 'after'], array_column($this->db->select('SELECT name FROM items ORDER BY id'), 'name'));
    }

    public function testNestedTransactionsThatSucceedAreCommittedTogether(): void
    {
        $this->db->transaction(function (Connection $db): void {
            $db->transaction(fn (Connection $db) => $db->insert('items', ['name' => 'inner']));
            $db->insert('items', ['name' => 'outer']);
        });

        self::assertSame(2, $this->db->scalar('SELECT COUNT(*) FROM items'));
        self::assertFalse($this->db->pdo()->inTransaction());
    }

    public function testPingReflectsAvailability(): void
    {
        self::assertTrue($this->db->ping());

        $broken = new Connection(static fn () => throw new PDOException('keine Verbindung'));
        self::assertFalse($broken->ping());
    }

    public function testConnectionIsLazy(): void
    {
        $calls = 0;
        $db = new Connection(function () use (&$calls) {
            $calls++;

            return Connection::fromConfig(['driver' => 'sqlite', 'database' => ':memory:'])->pdo();
        });

        self::assertSame(0, $calls);

        $db->scalar('SELECT 1');
        $db->scalar('SELECT 1');

        self::assertSame(1, $calls);
    }

    public function testUnsupportedDriverIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Connection::fromConfig(['driver' => 'oracle']);
    }

    public function testUnsafeMysqlCharsetIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Connection::fromConfig(['driver' => 'mysql', 'charset' => "utf8mb4'; DROP TABLE x; --"]);
    }
}
