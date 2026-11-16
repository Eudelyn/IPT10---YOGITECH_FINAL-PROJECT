<?php
declare(strict_types=1);

namespace App\Controllers\Seller;

use App\Models\Order;
use Core\Auth;
use Core\Controller;
use Core\Database;
use Core\Request;
use Core\Response;

final class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $sid = (int) Auth::id();
        $stats = Database::first(
            "SELECT COALESCE(SUM(CASE WHEN status = 'delivered' THEN total END), 0) AS revenue,
                    COALESCE(SUM(CASE WHEN status NOT IN ('delivered','cancelled') THEN total END), 0) AS pipeline,
                    SUM(status IN ('pending','confirmed','packed')) AS to_ship,
                    SUM(status IN ('shipped','in_transit','out_for_delivery')) AS in_transit,
                    SUM(status = 'delivered') AS delivered,
                    SUM(DATE_FORMAT(created_at, '%Y-%m') = DATE_FORMAT(NOW(), '%Y-%m') AND status <> 'cancelled') AS this_month
               FROM orders WHERE seller_id = ?",
            [$sid]
        );
        $low = (int) config('app.low_stock', 5);
        $products = Database::first(
            "SELECT COUNT(*) AS total, SUM(status = 'active') AS active, SUM(stock <= ?) AS low, SUM(stock = 0) AS out_of_stock FROM products WHERE seller_id = ?",
            [$low, $sid]
        );

        // Sales for the last 7 days (excluding cancelled orders)
        $rows = Database::select(
            "SELECT DATE(created_at) AS d, SUM(total) AS amount, COUNT(*) AS n FROM orders
              WHERE seller_id = ? AND status <> 'cancelled' AND created_at >= CURDATE() - INTERVAL 6 DAY
              GROUP BY DATE(created_at)",
            [$sid]
        );
        $byDay = array_column($rows, null, 'd');
        $chart = [];
        for ($i = 6; $i >= 0; $i--) {
            $d = date('Y-m-d', strtotime("-$i days"));
            $chart[] = ['date' => $d, 'label' => date('D', strtotime($d)), 'amount' => (float) ($byDay[$d]['amount'] ?? 0), 'orders' => (int) ($byDay[$d]['n'] ?? 0)];
        }

        return $this->view('seller/dashboard', [
            'title'    => 'Seller center',
            'user'     => Auth::user(),
            'stats'    => $stats,
            'products' => $products,
            'chart'    => $chart,
            'recent'   => Order::forSeller($sid, null, null, 1, 6)->items,
            'lowStock' => Database::select('SELECT id, name, stock, thumbnail FROM products WHERE seller_id = ? AND stock <= ? ORDER BY stock ASC, name LIMIT 6', [$sid, $low]),
            'top'      => Database::select('SELECT id, name, sold_count, price, thumbnail, rating FROM products WHERE seller_id = ? AND sold_count > 0 ORDER BY sold_count DESC LIMIT 5', [$sid]),
        ], 'layouts/seller');
    }
}
