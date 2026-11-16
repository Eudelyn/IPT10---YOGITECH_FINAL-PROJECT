<?php
declare(strict_types=1);

namespace App\Services;

use Core\Database;

final class CartService
{
    public static function items(int $userId): array
    {
        return Database::select(
            'SELECT c.id AS cart_id, c.quantity, p.id, p.name, p.brand, p.price, p.compare_price, p.stock, p.thumbnail,
                    p.status, p.seller_id, u.store_name, u.name AS seller_name, u.city AS seller_city
               FROM cart_items c
               JOIN products p ON p.id = c.product_id
               JOIN users u ON u.id = p.seller_id
              WHERE c.user_id = ?
              ORDER BY u.id, c.created_at',
            [$userId]
        );
    }

    /** Groups cart lines per seller, since each seller ships its own parcel. */
    public static function groupBySeller(array $items): array
    {
        $groups = [];
        foreach ($items as $item) {
            $sid = (int) $item['seller_id'];
            if (!isset($groups[$sid])) {
                $groups[$sid] = [
                    'seller_id' => $sid,
                    'store_name' => $item['store_name'] ?: $item['seller_name'],
                    'seller_city' => $item['seller_city'],
                    'items' => [],
                    'subtotal' => 0.0,
                ];
            }
            $groups[$sid]['items'][] = $item;
            $groups[$sid]['subtotal'] += (float) $item['price'] * (int) $item['quantity'];
        }
        return array_values($groups);
    }

    public static function count(int $userId): int
    {
        return (int) Database::scalar('SELECT COALESCE(SUM(quantity),0) FROM cart_items WHERE user_id = ?', [$userId]);
    }
}
