<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Models\Order;
use App\Models\Product;
use App\Services\ExchangeRateService;
use App\Services\OrderService;
use App\Services\ShippingService;
use Core\Controller;
use Core\Request;
use Core\Response;

/**
 * Yogitech's own read-only JSON API.
 */
final class ApiController extends Controller
{
    private static function card(array $p): array
    {
        return [
            'id'          => (int) $p['id'],
            'name'        => $p['name'],
            'brand'       => $p['brand'],
            'category'    => $p['category_name'] ?? null,
            'price'       => (float) $p['price'],
            'price_label' => peso($p['price']),
            'discount'    => discount_pct($p),
            'rating'      => (float) $p['rating'],
            'in_stock'    => (int) $p['stock'] > 0,
            'thumbnail'   => img_url($p['thumbnail']),
            'url'         => url('/products/' . $p['id']),
        ];
    }

    /** GET /api/products?q=iphone&category=smartphones&limit=6 */
    public function products(Request $request): Response
    {
        $limit = max(1, min(24, (int) $request->query('limit', 12)));
        $pager = Product::search($request->only(['q', 'category', 'brand', 'min', 'max', 'sort']), (int) $request->query('page', 1), $limit);
        return $this->json([
            'data' => array_map([self::class, 'card'], $pager->items),
            'meta' => ['total' => $pager->total, 'page' => $pager->page, 'last_page' => $pager->lastPage, 'currency' => 'PHP'],
        ]);
    }

    /** GET /api/products/{id} */
    public function product(Request $request, string $id): Response
    {
        $p = Product::detail((int) $id);
        if (!$p || $p['status'] !== 'active') {
            return $this->json(['error' => true, 'message' => 'Product not found'], 404);
        }
        return $this->json(['data' => self::card($p) + [
            'description' => $p['description'], 'stock' => (int) $p['stock'], 'warranty' => $p['warranty'],
            'return_policy' => $p['return_policy'], 'images' => array_map('img_url', Product::images($p)),
            'specs' => Product::specs($p), 'store' => $p['store_name'] ?: $p['seller_name'],
        ]]);
    }

    /** GET /api/track/{tracking} — public tracking feed */
    public function track(Request $request, string $tracking): Response
    {
        $o = Order::byTracking($tracking); // tracking numbers only (no order numbers) on the public API
        if (!$o) {
            return $this->json(['error' => true, 'message' => 'No parcel found for ' . strtoupper($tracking)], 404);
        }
        return $this->json(['data' => [
            'tracking_number' => $o['tracking_number'],
            'status'          => $o['status'],
            'status_label'    => ShippingService::label($o['status']),
            'progress'        => ShippingService::progressPercent($o['status']),
            'courier'         => $o['courier'],
            'destination'     => $o['city'] . ', ' . $o['province'],
            'estimated'       => ['from' => $o['estimated_from'], 'to' => $o['estimated_to']],
            'events'          => array_map(static fn($e) => [
                'status' => $e['status'], 'title' => $e['title'], 'description' => $e['description'],
                'location' => $e['location'], 'at' => date(DATE_ATOM, strtotime($e['created_at'])),
            ], OrderService::events((int) $o['id'])),
        ]]);
    }

    /** GET /api/exchange-rate */
    public function exchangeRate(Request $request): Response
    {
        $info = ExchangeRateService::info();
        unset($info['error']);
        return $this->json(['data' => ['base' => 'USD', 'quote' => 'PHP'] + $info]);
    }

    /** GET /api/shipping-quote?region=Visayas&subtotal=4999 */
    public function shippingQuote(Request $request): Response
    {
        $region = (string) $request->query('region', 'NCR');
        if (!isset(ShippingService::regions()[$region])) {
            return $this->json(['error' => true, 'message' => 'Unknown region'], 422);
        }
        $fee = ShippingService::fee($region, (float) $request->query('subtotal', 0));
        [$from, $to] = ShippingService::estimate($region);
        return $this->json(['data' => [
            'region' => $region, 'fee' => $fee, 'fee_label' => $fee > 0 ? peso($fee) : 'Free',
            'eta_label' => ShippingService::estimateLabel($from, $to), 'eta' => ['from' => $from, 'to' => $to],
        ]]);
    }
}
