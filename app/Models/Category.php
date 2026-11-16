<?php
declare(strict_types=1);

namespace App\Models;

use Core\Database;
use Core\Model;

final class Category extends Model
{
    protected static string $table = 'categories';
    protected static bool $timestamps = false;

    public static function withCounts(): array
    {
        return Database::select(
            "SELECT c.*, COUNT(p.id) AS product_count
               FROM categories c
               LEFT JOIN products p ON p.category_id = c.id AND p.status = 'active'
              GROUP BY c.id ORDER BY c.sort_order"
        );
    }

    public static function ordered(): array
    {
        return self::all('sort_order ASC');
    }
}
