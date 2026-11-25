<?php

declare(strict_types=1);

namespace App\Database;

use App\Support\Path;
use Closure;
use InvalidArgumentException;
use PDO;
use PDOStatement;
use Throwable;

/**
 * Dünne Hülle um PDO.
 *
 * - verbindet erst beim ersten Zugriff (lazy)
 * - nutzt ausschließlich Prepared Statements
 * - Bezeichner (Tabellen/Spalten) werden validiert und gequotet, Werte immer gebunden
 * - kleine Helfer für insert/update/delete/upsert und verschachtelte Transaktionen
 */
final class Connection
{
    private ?PDO $pdo = null;
    private int $transactionLevel = 0;

    public function __construct(private readonly Closure $connector)
    {
    }

    /** @param array<string, mixed> $config Werte aus config/database.php */
    public static function fromConfig(array $config, string $basePath = ''): self
    {
        $driver = (string) ($config['driver'] ?? 'mysql');

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        if ($driver === 'mysql') {
            $charset = self::assertSafeName((string) ($config['charset'] ?? 'utf8mb4'), 'charset');
            $collation = self::assertSafeName((string) ($config['collation'] ?? 'utf8mb4_unicode_ci'), 'collation');
            $username = (string) ($config['username'] ?? '');
            $password = (string) ($config['password'] ?? '');
            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                (string) ($config['host'] ?? '127.0.0.1'),
                (int) ($config['port'] ?? 3306),
                (string) ($config['database'] ?? ''),
                $charset,
            );

            return new self(static function () use ($dsn, $username, $password, $options, $charset, $collation): PDO {
                $pdo = new PDO($dsn, $username, $password, $options);
                $pdo->exec(sprintf("SET NAMES '%s' COLLATE '%s'", $charset, $collation));

                return $pdo;
            });
        }

        if ($driver === 'sqlite') {
            $database = (string) ($config['database'] ?? ':memory:');
            $file = $database === ':memory:' ? $database : Path::resolve($basePath, $database);

            return new self(static function () use ($file, $options): PDO {
                $pdo = new PDO('sqlite:' . $file, null, null, $options);
                $pdo->exec('PRAGMA foreign_keys = ON');

                return $pdo;
            });
        }

        throw new InvalidArgumentException(sprintf('Nicht unterstützter DB-Treiber "%s" (erlaubt: mysql, sqlite)', $driver));
    }

    public function pdo(): PDO
    {
        return $this->pdo ??= ($this->connector)();
    }

    /** "mysql" oder "sqlite" */
    public function driver(): string
    {
        return (string) $this->pdo()->getAttribute(PDO::ATTR_DRIVER_NAME);
    }

    /** @param array<int|string, mixed> $params */
    public function statement(string $sql, array $params = []): PDOStatement
    {
        $statement = $this->pdo()->prepare($sql);
        // Booleans als 0/1 binden, sonst wird false zu '' und scheitert in strikten MySQL-Spalten.
        $statement->execute(array_map(static fn (mixed $v): mixed => is_bool($v) ? (int) $v : $v, $params));

        return $statement;
    }

    /**
     * @param array<int|string, mixed> $params
     * @return list<array<string, mixed>>
     */
    public function select(string $sql, array $params = []): array
    {
        return $this->statement($sql, $params)->fetchAll();
    }

    /**
     * @param array<int|string, mixed> $params
     * @return array<string, mixed>|null
     */
    public function first(string $sql, array $params = []): ?array
    {
        $row = $this->statement($sql, $params)->fetch();

        return $row === false ? null : $row;
    }

    /** @param array<int|string, mixed> $params */
    public function scalar(string $sql, array $params = []): mixed
    {
        $value = $this->statement($sql, $params)->fetchColumn();

        return $value === false ? null : $value;
    }

    /**
     * Führt ein Statement aus und liefert die Zahl der betroffenen Zeilen.
     *
     * @param array<int|string, mixed> $params
     */
    public function execute(string $sql, array $params = []): int
    {
        return $this->statement($sql, $params)->rowCount();
    }

    /**
     * @param array<string, mixed> $data Spalte => Wert
     * @return string ID der neuen Zeile (bei Tabellen mit Auto-Increment)
     */
    public function insert(string $table, array $data): string
    {
        if ($data === []) {
            throw new InvalidArgumentException('Keine Daten zum Einfügen übergeben');
        }

        $columns = [];
        $placeholders = [];

        foreach (array_keys($data) as $column) {
            $columns[] = $this->quoteIdentifier((string) $column);
            $placeholders[] = ':' . $column;
        }

        $this->statement(sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $this->quoteIdentifier($table),
            implode(', ', $columns),
            implode(', ', $placeholders),
        ), $data);

        return $this->pdo()->lastInsertId();
    }

    /**
     * @param array<string, mixed> $data  Spalte => neuer Wert
     * @param array<string, mixed> $where Spalte => Wert (UND-verknüpft, null ergibt IS NULL)
     * @return int Zahl der betroffenen Zeilen
     */
    public function update(string $table, array $data, array $where): int
    {
        if ($data === []) {
            throw new InvalidArgumentException('Keine Daten zum Aktualisieren übergeben');
        }

        $params = [];
        $assignments = [];

        foreach ($data as $column => $value) {
            $assignments[] = $this->quoteIdentifier((string) $column) . ' = :s_' . $column;
            $params['s_' . $column] = $value;
        }

        $sql = sprintf(
            'UPDATE %s SET %s WHERE %s',
            $this->quoteIdentifier($table),
            implode(', ', $assignments),
            $this->buildWhere($where, $params),
        );

        return $this->statement($sql, $params)->rowCount();
    }

    /**
     * @param array<string, mixed> $where Spalte => Wert (UND-verknüpft, null ergibt IS NULL)
     * @return int Zahl der gelöschten Zeilen
     */
    public function delete(string $table, array $where): int
    {
        $params = [];

        $sql = sprintf(
            'DELETE FROM %s WHERE %s',
            $this->quoteIdentifier($table),
            $this->buildWhere($where, $params),
        );

        return $this->statement($sql, $params)->rowCount();
    }

    /**
     * Fügt eine Zeile ein oder aktualisiert sie, falls der Schlüssel schon existiert.
     * Die Spalten in $keyColumns müssen in der Tabelle UNIQUE bzw. PRIMARY KEY sein.
     *
     * @param array<string, mixed> $data
     * @param list<string>         $keyColumns
     */
    public function upsert(string $table, array $data, array $keyColumns): void
    {
        if ($keyColumns === []) {
            throw new InvalidArgumentException('Für upsert() wird mindestens eine Schlüsselspalte benötigt');
        }

        foreach ($keyColumns as $key) {
            if (!array_key_exists($key, $data)) {
                throw new InvalidArgumentException(sprintf('Schlüsselspalte "%s" fehlt in den Daten', $key));
            }
        }

        $quote = fn (string $name): string => $this->quoteIdentifier($name);
        $columns = array_map('strval', array_keys($data));
        $updateColumns = array_values(array_diff($columns, $keyColumns));

        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $quote($table),
            implode(', ', array_map($quote, $columns)),
            implode(', ', array_map(static fn (string $c): string => ':' . $c, $columns)),
        );

        if ($this->driver() === 'mysql') {
            $assignments = $updateColumns === []
                ? [$quote($keyColumns[0]) . ' = ' . $quote($keyColumns[0])]
                : array_map(static fn (string $c): string => $quote($c) . ' = VALUES(' . $quote($c) . ')', $updateColumns);

            $sql .= ' ON DUPLICATE KEY UPDATE ' . implode(', ', $assignments);
        } else {
            $conflict = implode(', ', array_map($quote, $keyColumns));

            $sql .= $updateColumns === []
                ? sprintf(' ON CONFLICT (%s) DO NOTHING', $conflict)
                : sprintf(
                    ' ON CONFLICT (%s) DO UPDATE SET %s',
                    $conflict,
                    implode(', ', array_map(static fn (string $c): string => $quote($c) . ' = excluded.' . $quote($c), $updateColumns)),
                );
        }

        $this->statement($sql, $data);
    }

    /**
     * Führt $callback in einer Transaktion aus. Bei einer Ausnahme wird zurückgerollt.
     * Verschachtelte Aufrufe nutzen Savepoints, ein Fehler im inneren Block rollt nur diesen zurück.
     *
     * Hinweis MySQL: DDL-Anweisungen (CREATE/ALTER/DROP TABLE) committen implizit
     * und sind daher nicht zurückrollbar.
     *
     * @template T
     * @param callable(self): T $callback
     * @return T
     */
    public function transaction(callable $callback): mixed
    {
        $pdo = $this->pdo();
        $level = $this->transactionLevel;

        if ($level === 0) {
            $pdo->beginTransaction();
        } else {
            $pdo->exec('SAVEPOINT sp' . $level);
        }

        $this->transactionLevel++;

        try {
            $result = $callback($this);
        } catch (Throwable $e) {
            $this->transactionLevel--;

            if ($level === 0) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
            } else {
                $pdo->exec('ROLLBACK TO SAVEPOINT sp' . $level);
            }

            throw $e;
        }

        $this->transactionLevel--;

        if ($level === 0) {
            // inTransaction() prüfen: nach einem DDL-Statement hat MySQL die Transaktion bereits beendet
            if ($pdo->inTransaction()) {
                $pdo->commit();
            }
        } else {
            $pdo->exec('RELEASE SAVEPOINT sp' . $level);
        }

        return $result;
    }

    /** true, wenn die Datenbank erreichbar ist und antwortet. */
    public function ping(): bool
    {
        try {
            return (int) $this->scalar('SELECT 1') === 1;
        } catch (Throwable) {
            return false;
        }
    }

    /** Validiert und quotet einen Tabellen- oder Spaltennamen (nur Buchstaben, Ziffern, Unterstrich). */
    public function quoteIdentifier(string $name): string
    {
        if (preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $name) !== 1) {
            throw new InvalidArgumentException(sprintf('Ungültiger Bezeichner: "%s"', $name));
        }

        $quote = $this->driver() === 'mysql' ? '`' : '"';

        return $quote . $name . $quote;
    }

    /**
     * @param array<string, mixed> $where
     * @param array<string, mixed> $params wird um die gebundenen Werte ergänzt
     */
    private function buildWhere(array $where, array &$params): string
    {
        if ($where === []) {
            throw new InvalidArgumentException('Eine leere WHERE-Bedingung ist nicht erlaubt');
        }

        $conditions = [];

        foreach ($where as $column => $value) {
            $quoted = $this->quoteIdentifier((string) $column);

            if ($value === null) {
                $conditions[] = $quoted . ' IS NULL';
                continue;
            }

            $conditions[] = $quoted . ' = :w_' . $column;
            $params['w_' . $column] = $value;
        }

        return implode(' AND ', $conditions);
    }

    private static function assertSafeName(string $value, string $label): string
    {
        if (preg_match('/^[A-Za-z0-9_]+$/', $value) !== 1) {
            throw new InvalidArgumentException(sprintf('Ungültiger Wert für %s: "%s"', $label, $value));
        }

        return $value;
    }
}
