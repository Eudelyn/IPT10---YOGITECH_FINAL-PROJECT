<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\Product;
use App\Models\Wishlist;
use Core\Auth;
use Core\Controller;
use Core\Request;
use Core\Response;
use Core\Session;

final class WishlistController extends Controller
{
    public function index(Request $request): Response
    {
        $ids = array_reverse(Wishlist::productIds((int) Auth::id()));
        return $this->view('wishlist/index', ['title' => 'Wishlist', 'products' => Product::byIds($ids), 'wishlist' => $ids]);
    }

    public function toggle(Request $request, string $id): Response
    {
        if (!Product::find((int) $id)) {
            abort(404);
        }
        $saved = Wishlist::toggle((int) Auth::id(), (int) $id);
        if ($request->wantsJson()) {
            return $this->json(['saved' => $saved]);
        }
        Session::flash('success', $saved ? 'Saved to your wishlist.' : 'Removed from your wishlist.');
        return $this->back();
    }
}
