<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use App\Models\Wishlist;
use App\Services\ShippingService;
use Core\Auth;
use Core\Controller;
use Core\Request;
use Core\Response;
use Core\Session;

final class ProductController extends Controller
{
    public function index(Request $request, array $extra = []): Response
    {
        $filters = array_merge($request->only(['q', 'category', 'brand', 'min', 'max', 'rating', 'in_stock', 'sort']), $extra);
        if (!isset(Product::SORTS[$filters['sort'] ?? ''])) {
            $filters['sort'] = 'newest';
        }
        $pager = Product::search($filters, (int) $request->query('page', 1), (int) config('app.per_page', 12));
        $category = !empty($filters['category']) ? Category::firstWhere('slug', $filters['category']) : null;

        return $this->view('products/index', [
            'title'      => $filters['q'] ? 'Results for "' . $filters['q'] . '"' : ($category['name'] ?? 'All gadgets'),
            'filters'    => $filters,
            'pager'      => $pager,
            'category'   => $category,
            'categories' => Category::withCounts(),
            'brands'     => Product::brands($filters['category'] ?? null),
            'store'      => $extra['store'] ?? null,
            'wishlist'   => Auth::is('buyer') ? Wishlist::productIds((int) Auth::id()) : [],
        ]);
    }

    public function category(Request $request, string $slug): Response
    {
        if (!Category::firstWhere('slug', $slug)) {
            abort(404);
        }
        return $this->index($request, ['category' => $slug]);
    }

    public function store(Request $request, string $id): Response
    {
        $seller = User::find((int) $id);
        if (!$seller || $seller['role'] !== 'seller') {
            abort(404);
        }
        return $this->index($request, ['seller' => (int) $id, 'store' => $seller]);
    }

    public function show(Request $request, string $id): Response
    {
        $product = Product::detail((int) $id);
        $user = Auth::user();
        if (!$product || ($product['status'] !== 'active' && (int) ($user['id'] ?? 0) !== (int) $product['seller_id'])) {
            abort(404);
        }

        // Recently viewed (session, most recent first, max 8)
        $recent = array_values(array_diff((array) Session::get('recently_viewed', []), [(int) $id]));
        array_unshift($recent, (int) $id);
        Session::put('recently_viewed', array_slice($recent, 0, 8));

        $region = $user['region'] ?? 'NCR';
        [$etaFrom, $etaTo] = ShippingService::estimate($region);
        $isBuyer = Auth::is('buyer');

        return $this->view('products/show', [
            'title'      => $product['name'],
            'product'    => $product,
            'images'     => Product::images($product),
            'specs'      => Product::specs($product),
            'reviews'    => Review::forProduct((int) $id),
            'breakdown'  => Review::breakdown((int) $id),
            'related'    => Product::related($product),
            'region'     => $region,
            'eta'        => ShippingService::estimateLabel($etaFrom, $etaTo),
            'fee'        => ShippingService::fee($region, (float) $product['price']),
            'saved'      => $isBuyer && in_array((int) $id, Wishlist::productIds((int) Auth::id()), true),
            'canReview'  => $isBuyer && Review::eligibleOrderId((int) Auth::id(), (int) $id) && !Review::existsFor((int) Auth::id(), (int) $id),
        ]);
    }
}
