<?php
declare(strict_types=1);

namespace App\Models;

use Core\Database;
use Core\Model;
use Core\Paginator;

final class Product extends Model
{
    protected static string $table = 'products';
    protected static array $fillable = [
        'seller_id', 'category_id', 'external_id', 'source', 'sku', 'name', 'brand', 'description',
        'price', 'compare_price', 'api_price_usd', 'stock', 'thumbnail', 'images', 'video_url', 'warranty',
        'return_policy', 'specs', 'status',
    ];

    public const SORTS = [
        'newest'     => ['Newest', 'p.created_at DESC, p.id DESC'],
        'popular'    => ['Best selling', 'p.sold_count DESC, p.rating DESC'],
        'rating'     => ['Top rated', 'p.rating DESC, p.rating_count DESC'],
        'price_asc'  => ['Price: low to high', 'p.price ASC'],
        'price_desc' => ['Price: high to low', 'p.price DESC'],
        'discount'   => ['Biggest discount', '(COALESCE(p.compare_price, p.price) - p.price) / COALESCE(p.compare_price, p.price) DESC'],
    ];

    private const CARD_SELECT = 'SELECT p.*, c.name AS category_name, c.slug AS category_slug, u.store_name, u.name AS seller_name
                                   FROM products p
                                   JOIN categories c ON c.id = p.category_id
                                   JOIN users u ON u.id = p.seller_id';

    /** Storefront search with filters + sort + pagination. */
    public static function search(array $f, int $page = 1, int $perPage = 12): Paginator
    {
        [$where, $params] = self::filters($f);
        $sql = ' FROM products p JOIN categories c ON c.id = p.category_id JOIN users u ON u.id = p.seller_id WHERE ' . implode(' AND ', $where);

        $total = (int) Database::scalar('SELECT COUNT(*)' . $sql, $params);
        $pager = new Paginator($total, $perPage, $page);
        $order = self::SORTS[$f['sort'] ?? 'newest'][1] ?? self::SORTS['newest'][1];
        $pager->items = Database::select(
            'SELECT p.*, c.name AS category_name, c.slug AS category_slug, u.store_name, u.name AS seller_name' . $sql
            . " ORDER BY $order LIMIT " . (int) $pager->perPage . ' OFFSET ' . (int) $pager->offset(),
            $params
        );
        return $pager;
    }

    /** @return array{0:array,1:array} */
    private static function filters(array $f): array
    {
        $where = ["p.status = 'active'"];
        $params = [];
        if (!empty($f['q'])) {
            $like = '%' . like_escape((string) $f['q']) . '%';
            $where[] = '(p.name LIKE ? OR p.brand LIKE ? OR p.description LIKE ? OR c.name LIKE ?)';
            array_push($params, $like, $like, $like, $like);
        }
        if (!empty($f['category'])) {
            $where[] = 'c.slug = ?';
            $params[] = $f['category'];
        }
        if (!empty($f['brand'])) {
            $where[] = 'p.brand = ?';
            $params[] = $f['brand'];
        }
        if (isset($f['min']) && is_numeric($f['min'])) {
            $where[] = 'p.price >= ?';
            $params[] = (float) $f['min'];
        }
        if (isset($f['max']) && is_numeric($f['max']) && (float) $f['max'] > 0) {
            $where[] = 'p.price <= ?';
            $params[] = (float) $f['max'];
        }
        if (!empty($f['rating']) && is_numeric($f['rating'])) {
            $where[] = 'p.rating >= ?';
            $params[] = (float) $f['rating'];
        }
        if (!empty($f['in_stock'])) {
            $where[] = 'p.stock > 0';
        }
        if (!empty($f['seller'])) {
            $where[] = 'p.seller_id = ?';
            $params[] = (int) $f['seller'];
        }
        return [$where, $params];
    }

    public static function brands(?string $categorySlug = null): array
    {
        $sql = "SELECT p.brand, COUNT(*) AS n FROM products p JOIN categories c ON c.id = p.category_id
                 WHERE p.status = 'active' AND p.brand IS NOT NULL AND p.brand <> ''";
        $params = [];
        if ($categorySlug) {
            $sql .= ' AND c.slug = ?';
            $params[] = $categorySlug;
        }
        return Database::select($sql . ' GROUP BY p.brand ORDER BY n DESC, p.brand LIMIT 20', $params);
    }

    public static function detail(int $id): ?array
    {
        return Database::first(
            'SELECT p.*, c.name AS category_name, c.slug AS category_slug, u.store_name, u.name AS seller_name, u.city AS seller_city
               FROM products p JOIN categories c ON c.id = p.category_id JOIN users u ON u.id = p.seller_id
              WHERE p.id = ?',
            [$id]
        );
    }

    public static function collection(string $orderBy, int $limit = 8, string $extraWhere = '1=1', array $params = []): array
    {
        return Database::select(
            self::CARD_SELECT . " WHERE p.status = 'active' AND $extraWhere ORDER BY $orderBy LIMIT " . (int) $limit,
            $params
        );
    }

    public static function byIds(array $ids): array
    {
        $ids = array_values(array_filter(array_map('intval', $ids)));
        if (!$ids) {
            return [];
        }
        $rows = Database::select(
            self::CARD_SELECT . " WHERE p.status = 'active' AND p.id IN (" . implode(',', array_fill(0, count($ids), '?')) . ')',
            $ids
        );
        $byId = array_column($rows, null, 'id');
        return array_values(array_filter(array_map(static fn($id) => $byId[$id] ?? null, $ids)));
    }

    public static function related(array $product, int $limit = 4): array
    {
        return self::collection('p.rating DESC', $limit, 'p.category_id = ? AND p.id <> ?', [$product['category_id'], $product['id']]);
    }

    public static function refreshRating(int $productId): void
    {
        Database::query(
            'UPDATE products SET
                rating = COALESCE((SELECT ROUND(AVG(rating), 2) FROM reviews WHERE product_id = ?), 0),
                rating_count = (SELECT COUNT(*) FROM reviews WHERE product_id = ?)
             WHERE id = ?',
            [$productId, $productId, $productId]
        );
    }

    public static function images(array $product): array
    {
        $images = json_decode((string) ($product['images'] ?? '[]'), true) ?: [];
        if ($product['thumbnail'] && !in_array($product['thumbnail'], $images, true)) {
            array_unshift($images, $product['thumbnail']);
        }
        return $images ?: [null];
    }

    public static function specs(array $product): array
    {
        return json_decode((string) ($product['specs'] ?? '{}'), true) ?: [];
    }

    public static function discountPercent(array $product): int
    {
        $compare = (float) ($product['compare_price'] ?? 0);
        $price = (float) $product['price'];
        return $compare > $price ? (int) round(($compare - $price) / $compare * 100) : 0;
    }
}
