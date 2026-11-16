<?php
declare(strict_types=1);

namespace App\Services;

use Core\Cache;
use Core\Database;
use Core\HttpClient;
use Core\Logger;
use RuntimeException;

/**
 * External API #1 — DummyJSON mock product catalog (https://dummyjson.com).
 *   GET /products/category/{slug}?limit=0   -> { products: [...], total, skip, limit }
 *   GET /products/search?q=...              -> same shape
 * Sellers import gadgets from it; prices are converted to PHP with ExchangeRateService.
 */
final class ProductApiService
{
    private HttpClient $http;
    private string $baseUrl;

    public function __construct(?HttpClient $http = null)
    {
        $this->http = $http ?? new HttpClient(12, 1);
        $this->baseUrl = (string) config('services.products_api.base_url');
    }

    /** @return array list of raw API products */
    public function fetchCategory(string $apiSlug): array
    {
        $key = 'dummyjson:category:' . $apiSlug;
        $cached = Cache::get($key);
        if (is_array($cached)) {
            return $cached;
        }
        $res = $this->http->get($this->baseUrl . '/products/category/' . rawurlencode($apiSlug), ['limit' => 0]);
        if (!$res['ok'] || !isset($res['json']['products']) || !is_array($res['json']['products'])) {
            throw new RuntimeException('Product API unavailable (' . ($res['error'] ?? 'bad response') . ')');
        }
        $products = $res['json']['products'];
        Cache::put($key, $products, (int) config('services.products_api.cache_ttl', 3600));
        return $products;
    }

    /** Converts one DummyJSON product into a row for our products table. */
    public static function mapProduct(array $p, int $sellerId, int $categoryId, float $rate): array
    {
        $usd = (float) ($p['price'] ?? 0);
        $discount = max(0.0, min(90.0, (float) ($p['discountPercentage'] ?? 0)));
        $compare = ExchangeRateService::toPeso($usd, $rate);
        $price = ExchangeRateService::toPeso($usd * (1 - $discount / 100), $rate);

        $specs = array_filter([
            'Availability' => $p['availabilityStatus'] ?? null,
            'Minimum order' => isset($p['minimumOrderQuantity']) ? (int) $p['minimumOrderQuantity'] . ' pc(s)' : null,
            'Dimensions' => isset($p['dimensions']['width'])
                ? sprintf('%s × %s × %s cm', $p['dimensions']['width'], $p['dimensions']['height'], $p['dimensions']['depth'])
                : null,
            'Weight' => isset($p['weight']) ? $p['weight'] . ' kg' : null,
            'Tags' => !empty($p['tags']) ? implode(', ', (array) $p['tags']) : null,
        ]);

        return [
            'seller_id'     => $sellerId,
            'category_id'   => $categoryId,
            'external_id'   => (int) $p['id'],
            'source'        => 'dummyjson',
            'sku'           => $p['sku'] ?? null,
            'name'          => (string) ($p['title'] ?? 'Untitled gadget'),
            'brand'         => $p['brand'] ?? 'Generic',
            'description'   => (string) ($p['description'] ?? ''),
            'price'         => $price,
            'compare_price' => $compare > $price ? $compare : null,
            'api_price_usd' => $usd,
            'stock'         => max(1, (int) ($p['stock'] ?? 10)),
            'thumbnail'     => $p['thumbnail'] ?? null,
            'images'        => json_encode(array_values((array) ($p['images'] ?? [])), JSON_UNESCAPED_SLASHES),
            'warranty'      => $p['warrantyInformation'] ?? '1 year local warranty',
            'return_policy' => $p['returnPolicy'] ?? '7 days return policy',
            'specs'         => json_encode($specs, JSON_UNESCAPED_UNICODE),
            'status'        => 'active',
        ];
    }

    /**
     * Imports a category for a seller. Existing imports are updated (price, stock, images).
     * @param array|null $rawProducts pass a pre-fetched/fallback list to skip the HTTP call
     * @return array{imported:int, updated:int, rate:float}
     */
    public function importCategory(int $sellerId, array $category, ?array $rawProducts = null, int $limit = 0): array
    {
        $raw = $rawProducts ?? $this->fetchCategory((string) $category['api_slug']);
        if ($limit > 0) {
            $raw = array_slice($raw, 0, $limit);
        }
        $rate = ExchangeRateService::rate();
        $imported = 0;
        $updated = 0;

        Database::transaction(function () use ($raw, $sellerId, $category, $rate, &$imported, &$updated) {
            foreach ($raw as $p) {
                if (!isset($p['id'])) {
                    continue;
                }
                $row = self::mapProduct($p, $sellerId, (int) $category['id'], $rate);
                $existing = Database::first('SELECT id FROM products WHERE seller_id = ? AND external_id = ?', [$sellerId, $row['external_id']]);
                $now = date('Y-m-d H:i:s');
                if ($existing) {
                    Database::update('products', [
                        'price' => $row['price'], 'compare_price' => $row['compare_price'], 'api_price_usd' => $row['api_price_usd'],
                        'thumbnail' => $row['thumbnail'], 'images' => $row['images'], 'updated_at' => $now,
                    ], 'id = ?', [$existing['id']]);
                    $updated++;
                    continue;
                }
                $productId = Database::insert('products', $row + ['created_at' => $now, 'updated_at' => $now]);
                foreach ((array) ($p['reviews'] ?? []) as $rv) {
                    Database::insert('reviews', [
                        'product_id'    => $productId,
                        'reviewer_name' => (string) ($rv['reviewerName'] ?? 'Verified buyer'),
                        'rating'        => max(1, min(5, (int) ($rv['rating'] ?? 5))),
                        'comment'       => str_limit((string) ($rv['comment'] ?? ''), 1000),
                        'created_at'    => isset($rv['date']) ? date('Y-m-d H:i:s', strtotime((string) $rv['date'])) : $now,
                    ]);
                }
                \App\Models\Product::refreshRating($productId);
                $imported++;
            }
        });

        Logger::info('Imported products from DummyJSON', ['seller' => $sellerId, 'category' => $category['slug'], 'new' => $imported, 'updated' => $updated]);
        return ['imported' => $imported, 'updated' => $updated, 'rate' => $rate];
    }
}
