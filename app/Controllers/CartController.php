<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\CartItem;
use App\Models\Product;
use App\Services\CartService;
use App\Services\ShippingService;
use Core\Auth;
use Core\Controller;
use Core\Request;
use Core\Response;
use Core\Session;

final class CartController extends Controller
{
    public function index(Request $request): Response
    {
        $user = Auth::user();
        $groups = CartService::groupBySeller(CartService::items((int) $user['id']));
        $region = $user['region'] ?: 'NCR';
        $subtotal = 0.0;
        $shipping = 0.0;
        foreach ($groups as &$g) {
            $g['shipping_fee'] = ShippingService::fee($region, $g['subtotal']);
            $subtotal += $g['subtotal'];
            $shipping += $g['shipping_fee'];
        }
        unset($g);
        return $this->view('cart/index', [
            'title' => 'Cart', 'groups' => $groups, 'subtotal' => $subtotal, 'shipping' => $shipping,
            'region' => $region, 'threshold' => (float) config('shipping.free_threshold'),
        ]);
    }

    public function add(Request $request): Response
    {
        $data = $this->validate($request, ['product_id' => 'required|integer', 'quantity' => 'nullable|integer|min:1|max:99']);
        $product = Product::find((int) $data['product_id']);
        if (!$product || $product['status'] !== 'active') {
            Session::flash('error', 'That product is no longer available.');
            return $this->back();
        }
        if ((int) $product['stock'] < 1) {
            Session::flash('error', $product['name'] . ' is out of stock.');
            return $this->back();
        }
        $qty = CartItem::addOrIncrement((int) Auth::id(), (int) $product['id'], (int) ($data['quantity'] ?: 1), (int) $product['stock']);

        if ($request->input('buy_now')) {
            return $this->redirect('/checkout');
        }
        Session::flash('success', 'Added to cart: ' . $product['name'] . ' (×' . $qty . ').');
        return $this->back();
    }

    public function update(Request $request, string $id): Response
    {
        $item = CartItem::findOwned((int) $id, (int) Auth::id());
        if (!$item) {
            abort(404);
        }
        $stock = (int) $item['stock'];
        if ($stock < 1) {
            Session::flash('error', $item['name'] . ' is out of stock. Remove it from your cart to check out.');
            return $this->redirect('/cart');
        }
        $wanted = (int) $request->input('quantity', 1);
        $qty = max(1, min($stock, $wanted, 99));
        CartItem::updateById((int) $id, ['quantity' => $qty]);
        if ($wanted > $stock) {
            Session::flash('info', 'Only ' . $stock . ' of ' . $item['name'] . ' in stock.');
        }
        return $this->redirect('/cart');
    }

    public function remove(Request $request, string $id): Response
    {
        $item = CartItem::findOwned((int) $id, (int) Auth::id());
        if ($item) {
            CartItem::deleteById((int) $id);
            Session::flash('success', 'Removed ' . $item['name'] . ' from your cart.');
        }
        return $this->redirect('/cart');
    }
}
