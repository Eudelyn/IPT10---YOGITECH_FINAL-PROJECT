<?php
declare(strict_types=1);

namespace Database\seeds;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Services\CheckoutService;
use App\Services\OrderService;
use App\Services\ProductApiService;
use Core\Database;
use Throwable;

/**
 * Demo data: 1 store (seller account), 1 buyer, products from the DummyJSON API (or an offline copy), sample orders.
 */
final class DatabaseSeeder
{
    /** The store's seller account. */
    public const SELLER_EMAIL = 'lynsaldevar@gmail.com';
    public const SELLER_PASSWORD = 'LynSeller123';

    private $out;

    public function __construct(callable $out) { $this->out = $out; }
    private function say(string $m): void { ($this->out)($m); }

    public function run(): void
    {
        $official = $this->user([
            'role' => 'seller', 'name' => 'Yogi Admin', 'email' => self::SELLER_EMAIL, 'password' => self::SELLER_PASSWORD, 'store_name' => 'Yogitech Official',
            'store_description' => 'Authorized gadgets with local warranty.', 'phone' => '09171234567',
            'address_line' => 'MacArthur Highway', 'city' => 'Angeles City', 'province' => 'Pampanga', 'region' => 'Luzon', 'postal_code' => '2009',
        ]);
        $buyer = $this->user([
            'role' => 'buyer', 'name' => 'Juan Dela Cruz', 'email' => 'buyer@yogitech.test', 'phone' => '09991234567',
            'address_line' => '123 Sto. Rosario St.', 'city' => 'Angeles City', 'province' => 'Pampanga', 'region' => 'Luzon', 'postal_code' => '2009',
        ]);
        $this->say('Users: ' . self::SELLER_EMAIL . ' (store, password ' . self::SELLER_PASSWORD . '), buyer@yogitech.test (password123)');

        $plan = ['smartphones' => $official, 'accessories' => $official, 'laptops' => $official, 'tablets' => $official];
        $api = new ProductApiService();
        $fallback = json_decode((string) file_get_contents(__DIR__ . '/fallback_products.json'), true);
        foreach ($plan as $slug => $sellerId) {
            $category = Category::firstWhere('slug', $slug);
            try {
                $r = $api->importCategory($sellerId, $category);
                $this->say(sprintf('  ✓ %-12s %2d products from DummyJSON API (₱%.2f/USD)', $category['name'], $r['imported'] + $r['updated'], $r['rate']));
            } catch (Throwable $e) {
                $r = $api->importCategory($sellerId, $category, $fallback[$category['api_slug']] ?? []);
                $this->say(sprintf('  ! %-12s API offline, loaded %d products from fallback_products.json', $category['name'], $r['imported']));
            }
        }

        $this->sampleOrders($buyer);
    }

    private function user(array $data): int
    {
        $existing = User::firstWhere('email', $data['email']);
        if ($existing) {
            return (int) $existing['id'];
        }
        return User::register($data + ['password' => 'password123']); // demo buyer password
    }

    private function sampleOrders(int $buyerId): void
    {
        if ((int) Database::scalar('SELECT COUNT(*) FROM orders WHERE buyer_id = ?', [$buyerId]) > 0) {
            return;
        }
        $buyer = User::find($buyerId);
        $address = [
            'recipient_name' => $buyer['name'], 'phone' => $buyer['phone'], 'address_line' => $buyer['address_line'],
            'city' => $buyer['city'], 'province' => $buyer['province'], 'region' => $buyer['region'], 'postal_code' => $buyer['postal_code'],
        ];
        $pick = static fn(string $cat, int $offset = 0): ?array => Database::first(
            "SELECT p.* FROM products p JOIN categories c ON c.id = p.category_id
              WHERE c.slug = ? AND p.stock > 2 ORDER BY p.rating DESC, p.id LIMIT 1 OFFSET $offset",
            [$cat]
        );

        $checkout = function (array $products, string $method, string $daysAgo) use ($buyerId, $buyer, $address): array {
            foreach ($products as $p) {
                Database::insert('cart_items', ['user_id' => $buyerId, 'product_id' => $p['id'], 'quantity' => 1]);
            }
            $ref = CheckoutService::place($buyer, $address, $method, null, null);
            $when = date('Y-m-d H:i:s', strtotime($daysAgo));
            Database::query(
                "UPDATE orders SET created_at = ?, updated_at = ?, order_number = CONCAT('YT', DATE_FORMAT(?, '%y%m%d'), SUBSTRING(order_number, 9)) WHERE checkout_ref = ?",
                [$when, $when, $when, $ref]
            );
            if ($method !== 'cod') {
                // Paid through the sandbox gateway, recorded the same way the gateway pages do it.
                $orders = Database::select('SELECT * FROM orders WHERE checkout_ref = ?', [$ref]);
                $total = array_sum(array_map(static fn($o) => (float) $o['total'], $orders));
                $pid = bin2hex(random_bytes(12));
                Database::insert('payments', [
                    'public_id' => $pid, 'checkout_ref' => $ref, 'buyer_id' => $buyerId, 'gateway' => 'sandbox',
                    'method' => $method, 'amount' => $total, 'status' => 'pending', 'gateway_ref' => 'SBX-' . strtoupper(substr($pid, 0, 10)),
                    'created_at' => $when,
                ]);
                $payment = Database::first('SELECT * FROM payments WHERE public_id = ?', [$pid]);
                \App\Services\PaymentService::recordSandboxResult($payment, 'authorized', $method === 'gcash' ? 'GCash 0999•••4567' : 'Visa •••• 4242', null);
                \App\Services\PaymentService::finish(Database::first('SELECT * FROM payments WHERE public_id = ?', [$pid]), false);
                Database::query('UPDATE payments SET created_at = ?, paid_at = ? WHERE public_id = ?', [$when, $when, $pid]);
            }
            Database::query('UPDATE shipment_events e JOIN orders o ON o.id = e.order_id SET e.created_at = ? WHERE o.checkout_ref = ?', [$when, $ref]);
            return Database::select('SELECT * FROM orders WHERE checkout_ref = ? ORDER BY id', [$ref]);
        };
        $advance = function (array $order, array $steps): void {
            foreach ($steps as [$status, $when, $ctx]) {
                $fresh = Database::first('SELECT * FROM orders WHERE id = ?', [$order['id']]);
                $lastId = (int) Database::scalar('SELECT COALESCE(MAX(id), 0) FROM shipment_events WHERE order_id = ?', [$order['id']]);
                OrderService::transition($fresh, $status, $ctx);
                $at = date('Y-m-d H:i:s', strtotime($when));
                Database::query('UPDATE shipment_events SET created_at = ? WHERE order_id = ? AND id > ?', [$at, $order['id'], $lastId]);
                if ($status === 'shipped') {
                    Database::query('UPDATE orders SET shipped_at = ? WHERE id = ?', [$at, $order['id']]);
                }
                Database::query('UPDATE orders SET updated_at = ? WHERE id = ?', [$at, $order['id']]);
            }
        };

        // 1) Delivered order (COD) with a review
        $phone = $pick('smartphones', 1);
        if ($phone) {
            [$o] = $checkout([$phone], 'cod', '-6 days 10:15');
            $advance($o, [
                ['confirmed', '-6 days 13:40', []], ['packed', '-5 days 09:10', []],
                ['shipped', '-5 days 16:30', ['courier' => 'J&T Express']],
                ['in_transit', '-4 days 06:20', ['location' => 'J&T Express — San Fernando, Pampanga hub']],
                ['out_for_delivery', '-3 days 08:05', []], ['delivered', '-3 days 14:22', []],
            ]);
            Database::query('UPDATE orders SET delivered_at = ? WHERE id = ?', [date('Y-m-d H:i:s', strtotime('-3 days 14:22')), $o['id']]);
            Database::insert('reviews', [
                'product_id' => $phone['id'], 'user_id' => $buyerId, 'order_id' => $o['id'], 'reviewer_name' => $buyer['name'],
                'rating' => 5, 'comment' => 'Arrived in 3 days, sealed box and complete inclusions. Battery is great.',
                'created_at' => date('Y-m-d H:i:s', strtotime('-2 days')),
            ]);
            Product::refreshRating((int) $phone['id']);
        }

        // 2) Checkout paid with GCash: laptop + accessory, in transit
        $laptop = $pick('laptops');
        $acc = $pick('accessories');
        if ($laptop && $acc) {
            $orders = $checkout([$laptop, $acc], 'gcash', '-2 days 19:05');
            foreach ($orders as $o) {
                if ((int) $o['seller_id'] === (int) $laptop['seller_id']) {
                    $advance($o, [
                        ['confirmed', '-2 days 20:00', []], ['packed', '-1 days 09:30', []],
                        ['shipped', '-1 days 15:10', ['courier' => 'LBC Express']],
                        ['in_transit', '-2 hours', ['location' => 'LBC Express — Mexico, Pampanga hub']],
                    ]);
                }
            }
        }

        // 3) Fresh order today, still pending
        $tablet = $pick('tablets');
        if ($tablet) {
            $checkout([$tablet], 'cod', '-40 minutes');
        }
        $this->say('Sample orders created for buyer@yogitech.test (delivered, in transit, pending).');
    }
}
