<?php
declare(strict_types=1);

namespace App\Models;

use Core\Database;
use Core\Model;

final class Wishlist extends Model
{
    protected static string $table = 'wishlists';
    protected static bool $timestamps = false;

    public static function productIds(int $userId): array
    {
        return array_map('intval', array_column(Database::select('SELECT product_id FROM wishlists WHERE user_id = ?', [$userId]), 'product_id'));
    }

    /** @return bool true if now saved, false if removed */
    public static function toggle(int $userId, int $productId): bool
    {
        $deleted = Database::query('DELETE FROM wishlists WHERE user_id = ? AND product_id = ?', [$userId, $productId])->rowCount();
        if ($deleted) {
            return false;
        }
        Database::insert('wishlists', ['user_id' => $userId, 'product_id' => $productId, 'created_at' => date('Y-m-d H:i:s')]);
        return true;
    }
}
