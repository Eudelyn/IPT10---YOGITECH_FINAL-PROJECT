<?php
declare(strict_types=1);

namespace Core;

/**
 * Lightweight table gateway. Child classes set $table and $fillable.
 * Rows are returned as associative arrays.
 */
abstract class Model
{
    protected static string $table = '';
    protected static array $fillable = [];
    protected static bool $timestamps = true;

    public static function table(): string { return static::$table; }

    public static function find(int $id): ?array
    {
        return Database::first('SELECT * FROM `' . static::$table . '` WHERE id = ?', [$id]);
    }

    public static function findOrFail(int $id): array
    {
        $row = static::find($id);
        if ($row === null) {
            abort(404);
        }
        return $row;
    }

    public static function firstWhere(string $column, $value): ?array
    {
        self::assertColumn($column);
        return Database::first('SELECT * FROM `' . static::$table . "` WHERE `$column` = ? LIMIT 1", [$value]);
    }

    public static function where(string $column, $value, string $orderBy = 'id DESC'): array
    {
        self::assertColumn($column);
        return Database::select('SELECT * FROM `' . static::$table . "` WHERE `$column` = ? ORDER BY $orderBy", [$value]);
    }

    public static function all(string $orderBy = 'id DESC'): array
    {
        return Database::select('SELECT * FROM `' . static::$table . "` ORDER BY $orderBy");
    }

    public static function count(string $where = '1=1', array $params = []): int
    {
        return (int) Database::scalar('SELECT COUNT(*) FROM `' . static::$table . "` WHERE $where", $params);
    }

    public static function create(array $data): int
    {
        $data = static::filterFillable($data);
        if (static::$timestamps) {
            $now = date('Y-m-d H:i:s');
            $data += ['created_at' => $now, 'updated_at' => $now];
        }
        return Database::insert(static::$table, $data);
    }

    public static function updateById(int $id, array $data): int
    {
        $data = static::filterFillable($data);
        if (static::$timestamps) {
            $data['updated_at'] = date('Y-m-d H:i:s');
        }
        return Database::update(static::$table, $data, 'id = ?', [$id]);
    }

    public static function deleteById(int $id): int
    {
        return Database::query('DELETE FROM `' . static::$table . '` WHERE id = ?', [$id])->rowCount();
    }

    protected static function filterFillable(array $data): array
    {
        if (static::$fillable === []) {
            return $data;
        }
        return array_intersect_key($data, array_flip(static::$fillable));
    }

    private static function assertColumn(string $column): void
    {
        if (!preg_match('/^[a-z_][a-z0-9_]*$/i', $column)) {
            throw new \InvalidArgumentException("Invalid column name [$column]");
        }
    }
}
