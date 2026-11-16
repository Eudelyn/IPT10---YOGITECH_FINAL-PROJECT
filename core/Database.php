<?php
declare(strict_types=1);

namespace Core;

use PDO;
use PDOStatement;
use Throwable;

/**
 * PDO wrapper (singleton connection) with small query helpers.
 * All queries use prepared statements to prevent SQL injection.
 */
final class Database
{
    private static ?PDO $pdo = null;

    public static function connection(bool $withDatabase = true): PDO
    {
        if ($withDatabase && self::$pdo !== null) {
            return self::$pdo;
        }
        $c = Config::get('database');
        $dsn = sprintf('mysql:host=%s;port=%s;charset=%s', $c['host'], $c['port'], $c['charset']);
        if ($withDatabase) {
            $dsn .= ';dbname=' . $c['database'];
        }
        $pdo = new PDO($dsn, $c['username'], $c['password'], [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
        $pdo->exec("SET time_zone = '+08:00'");
        if ($withDatabase) {
            self::$pdo = $pdo;
        }
        return $pdo;
    }

    public static function query(string $sql, array $params = []): PDOStatement
    {
        $stmt = self::connection()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public static function select(string $sql, array $params = []): array
    {
        return self::query($sql, $params)->fetchAll();
    }

    public static function first(string $sql, array $params = []): ?array
    {
        $row = self::query($sql, $params)->fetch();
        return $row === false ? null : $row;
    }

    /** @return mixed */
    public static function scalar(string $sql, array $params = [])
    {
        $value = self::query($sql, $params)->fetchColumn();
        return $value === false ? null : $value;
    }

    public static function insert(string $table, array $data): int
    {
        $cols = array_keys($data);
        $sql = sprintf(
            'INSERT INTO `%s` (%s) VALUES (%s)',
            $table,
            implode(', ', array_map(static fn($c) => "`$c`", $cols)),
            implode(', ', array_fill(0, count($cols), '?'))
        );
        self::query($sql, array_values($data));
        return (int) self::connection()->lastInsertId();
    }

    public static function update(string $table, array $data, string $where, array $whereParams = []): int
    {
        $set = implode(', ', array_map(static fn($c) => "`$c` = ?", array_keys($data)));
        $stmt = self::query("UPDATE `$table` SET $set WHERE $where", array_merge(array_values($data), $whereParams));
        return $stmt->rowCount();
    }

    /**
     * Runs the callback in a transaction; rolls back on any exception.
     * @return mixed
     */
    public static function transaction(callable $callback)
    {
        $pdo = self::connection();
        $pdo->beginTransaction();
        try {
            $result = $callback($pdo);
            $pdo->commit();
            return $result;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }
}
