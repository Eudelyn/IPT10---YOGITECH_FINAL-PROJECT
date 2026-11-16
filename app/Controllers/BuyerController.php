<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Core\Auth;
use Core\Controller;
use Core\Database;
use Core\Request;
use Core\Response;
use Core\Session;

final class BuyerController extends Controller
{
    public function dashboard(Request $request): Response
    {
        $user = Auth::user();
        $uid = (int) $user['id'];
        $stats = Database::first(
            "SELECT COUNT(*) AS orders,
                    COALESCE(SUM(CASE WHEN status <> 'cancelled' THEN total END), 0) AS spent,
                    SUM(status NOT IN ('delivered','cancelled')) AS active,
                    SUM(status = 'delivered') AS delivered
               FROM orders WHERE buyer_id = ?",
            [$uid]
        );
        $wishCount = (int) Database::scalar('SELECT COUNT(*) FROM wishlists WHERE user_id = ?', [$uid]);

        // Recommend from categories they've bought from; otherwise top rated.
        $catIds = array_column(Database::select(
            'SELECT DISTINCT p.category_id FROM order_items i JOIN orders o ON o.id = i.order_id JOIN products p ON p.id = i.product_id WHERE o.buyer_id = ?',
            [$uid]
        ), 'category_id');
        $recommended = $catIds
            ? Product::collection('p.rating DESC, p.sold_count DESC', 4,
                'p.category_id IN (' . implode(',', array_map('intval', $catIds)) . ') AND p.id NOT IN (SELECT i.product_id FROM order_items i JOIN orders o ON o.id = i.order_id WHERE o.buyer_id = ? AND i.product_id IS NOT NULL)',
                [$uid])
            : Product::collection('p.rating DESC', 4);

        return $this->view('buyer/dashboard', [
            'title'       => 'Dashboard',
            'user'        => $user,
            'stats'       => $stats,
            'wishCount'   => $wishCount,
            'active'      => Order::forBuyer($uid, 'active', 1, 4)->items,
            'recentOrders'=> Order::forBuyer($uid, null, 1, 5)->items,
            'recommended' => $recommended,
            'recent'      => Product::byIds(array_slice((array) Session::get('recently_viewed', []), 0, 4)),
            'hasAddress'  => User::hasAddress($user),
        ]);
    }
}
