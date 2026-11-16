<?php
declare(strict_types=1);

namespace App\Models;

use Core\Database;
use Core\Model;

final class Review extends Model
{
    protected static string $table = 'reviews';
    protected static bool $timestamps = false;

    public static function forProduct(int $productId, int $limit = 20): array
    {
        return Database::select('SELECT * FROM reviews WHERE product_id = ? ORDER BY created_at DESC LIMIT ' . (int) $limit, [$productId]);
    }

    public static function breakdown(int $productId): array
    {
        $rows = Database::select('SELECT rating, COUNT(*) AS n FROM reviews WHERE product_id = ? GROUP BY rating', [$productId]);
        $out = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
        foreach ($rows as $r) {
            $out[(int) $r['rating']] = (int) $r['n'];
        }
        return $out;
    }

    /** A buyer may review a product once they have a delivered order containing it. */
    public static function eligibleOrderId(int $userId, int $productId): ?int
    {
        $id = Database::scalar(
            "SELECT o.id FROM orders o JOIN order_items i ON i.order_id = o.id
              WHERE o.buyer_id = ? AND i.product_id = ? AND o.status = 'delivered' ORDER BY o.delivered_at DESC LIMIT 1",
            [$userId, $productId]
        );
        return $id ? (int) $id : null;
    }

    public static function existsFor(int $userId, int $productId): bool
    {
        return (bool) Database::scalar('SELECT 1 FROM reviews WHERE user_id = ? AND product_id = ?', [$userId, $productId]);
    }
}
