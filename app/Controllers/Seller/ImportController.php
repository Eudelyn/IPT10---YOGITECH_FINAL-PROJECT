<?php
declare(strict_types=1);

namespace App\Controllers\Seller;

use App\Models\Category;
use App\Services\ExchangeRateService;
use App\Services\ProductApiService;
use Core\Auth;
use Core\Controller;
use Core\Database;
use Core\Request;
use Core\Response;
use Core\Session;
use Throwable;

/** Seller imports mock gadgets from the DummyJSON API, priced in PHP. */
final class ImportController extends Controller
{
    public function index(Request $request): Response
    {
        $counts = array_column(Database::select(
            "SELECT category_id, COUNT(*) AS n FROM products WHERE seller_id = ? AND source = 'dummyjson' GROUP BY category_id",
            [Auth::id()]
        ), 'n', 'category_id');
        return $this->view('seller/import', [
            'title' => 'Import products', 'categories' => Category::ordered(),
            'counts' => $counts, 'fx' => ExchangeRateService::info(),
        ], 'layouts/seller');
    }

    public function store(Request $request): Response
    {
        $data = $this->validate($request, ['category_id' => 'required|integer|exists:categories,id', 'limit' => 'nullable|integer|min:1|max:50']);
        $category = Category::find((int) $data['category_id']);
        try {
            $result = (new ProductApiService())->importCategory((int) Auth::id(), $category, null, (int) ($data['limit'] ?? 0));
            Session::flash('success', sprintf(
                '%s: %d new product(s) imported, %d updated. Prices converted at $1 = ₱%s.',
                $category['name'], $result['imported'], $result['updated'], number_format($result['rate'], 2)
            ));
        } catch (Throwable $e) {
            Session::flash('error', 'Couldn\'t reach the catalog API: ' . $e->getMessage() . '. Check your internet connection and try again.');
        }
        return $this->redirect('/seller/import');
    }
}
