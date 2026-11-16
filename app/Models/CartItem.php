<?php
declare(strict_types=1);

namespace App\Models;

use Core\Database;
use Core\Model;

final class CartItem extends Model
{
    protected static string $table = 'cart_items';
    protected static array $fillable = ['user_id', 'product_id', 'quantity'];

    public static function addOrIncrement(int $userId, int $productId, int $qty, int $maxStock): int
    {
        $existing = Database::first('SELECT * FROM cart_items WHERE user_id = ? AND product_id = ?', [$userId, $productId]);
        $newQty = min($maxStock, ($existing['quantity'] ?? 0) + $qty);
        if ($existing) {
            self::updateById((int) $existing['id'], ['quantity' => $newQty]);
        } else {
            self::create(['user_id' => $userId, 'product_id' => $productId, 'quantity' => $newQty]);
        }
        return $newQty;
    }

    public static function findOwned(int $id, int $userId): ?array
    {
        return Database::first(
            'SELECT c.*, p.stock, p.name FROM cart_items c JOIN products p ON p.id = c.product_id WHERE c.id = ? AND c.user_id = ?',
            [$id, $userId]
        );
    }
}
