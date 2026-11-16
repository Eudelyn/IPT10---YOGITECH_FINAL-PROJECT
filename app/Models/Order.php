<?php
declare(strict_types=1);

namespace App\Models;

use Core\Database;
use Core\Model;
use Core\Paginator;

final class Order extends Model
{
    protected static string $table = 'orders';

    public static function forBuyer(int $buyerId, ?string $status = null, int $page = 1, int $perPage = 10): Paginator
    {
        $sql = 'SELECT o.*, u.store_name, u.name AS seller_name,
                       (SELECT COUNT(*) FROM order_items i WHERE i.order_id = o.id) AS item_count,
                       (SELECT i.thumbnail FROM order_items i WHERE i.order_id = o.id ORDER BY i.id LIMIT 1) AS first_thumb,
                       (SELECT i.product_name FROM order_items i WHERE i.order_id = o.id ORDER BY i.id LIMIT 1) AS first_name
                  FROM orders o JOIN users u ON u.id = o.seller_id WHERE o.buyer_id = ?';
        $params = [$buyerId];
        if ($status === 'active') {
            $sql .= " AND o.status NOT IN ('delivered','cancelled')";
        } elseif ($status) {
            $sql .= ' AND o.status = ?';
            $params[] = $status;
        }
        return Paginator::query($sql . ' ORDER BY o.created_at DESC, o.id DESC', $params, $page, $perPage);
    }

    public static function forSeller(int $sellerId, ?string $status = null, ?string $q = null, int $page = 1, int $perPage = 20): Paginator
    {
        $sql = 'SELECT o.*, b.name AS buyer_name, (SELECT SUM(quantity) FROM order_items i WHERE i.order_id = o.id) AS qty
                  FROM orders o JOIN users b ON b.id = o.buyer_id WHERE o.seller_id = ?';
        $params = [$sellerId];
        if ($status) {
            $sql .= ' AND o.status = ?';
            $params[] = $status;
        }
        if ($q) {
            $sql .= ' AND (o.order_number LIKE ? OR o.tracking_number LIKE ? OR o.recipient_name LIKE ?)';
            $like = '%' . like_escape($q) . '%';
            array_push($params, $like, $like, $like);
        }
        return Paginator::query($sql . ' ORDER BY o.created_at DESC, o.id DESC', $params, $page, $perPage);
    }

    public static function findForBuyer(int $id, int $buyerId): ?array
    {
        return Database::first(
            'SELECT o.*, u.store_name, u.name AS seller_name, u.city AS seller_city
               FROM orders o JOIN users u ON u.id = o.seller_id WHERE o.id = ? AND o.buyer_id = ?',
            [$id, $buyerId]
        );
    }

    public static function findForSeller(int $id, int $sellerId): ?array
    {
        return Database::first(
            'SELECT o.*, b.name AS buyer_name, b.email AS buyer_email
               FROM orders o JOIN users b ON b.id = o.buyer_id WHERE o.id = ? AND o.seller_id = ?',
            [$id, $sellerId]
        );
    }

    /**
     * Public lookup by tracking number (YGT + 10 random hex characters, not guessable).
     * Order numbers (YT…) are guessable, so they only work for the buyer who owns the order.
     */
    public static function byTracking(string $number, ?int $buyerId = null): ?array
    {
        $number = strtoupper(trim($number));
        $sql = 'SELECT o.*, u.store_name, u.name AS seller_name FROM orders o JOIN users u ON u.id = o.seller_id';
        if ($buyerId) {
            return Database::first($sql . ' WHERE o.tracking_number = ? OR (o.order_number = ? AND o.buyer_id = ?) LIMIT 1', [$number, $number, $buyerId]);
        }
        return Database::first($sql . ' WHERE o.tracking_number = ? LIMIT 1', [$number]);
    }
}
