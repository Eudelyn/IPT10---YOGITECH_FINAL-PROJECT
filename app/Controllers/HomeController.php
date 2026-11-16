<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\Category;
use App\Models\Product;
use Core\Controller;
use Core\Request;
use Core\Response;
use Core\Session;

final class HomeController extends Controller
{
    public function index(Request $request): Response
    {
        return $this->view('home/index', [
            'title'      => 'Gadgets, wired for you',
            'categories' => Category::withCounts(),
            'deals'      => Product::collection(Product::SORTS['discount'][1], 8, 'p.compare_price IS NOT NULL AND p.stock > 0'),
            'topRated'   => Product::collection('p.rating DESC, p.rating_count DESC', 4),
            'newest'     => Product::collection('p.created_at DESC, p.id DESC', 8),
            'recent'     => Product::byIds(array_slice((array) Session::get('recently_viewed', []), 0, 4)),
        ]);
    }
}
