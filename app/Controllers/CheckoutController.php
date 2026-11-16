<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\User;
use App\Services\CartService;
use App\Services\CheckoutService;
use App\Services\ShippingService;
use App\Services\VoucherService;
use Core\Auth;
use Core\Controller;
use Core\Database;
use Core\Request;
use Core\Response;
use Core\Session;
use DomainException;

final class CheckoutController extends Controller
{
    private function buildQuote(string $region): ?array
    {
        $items = CartService::items((int) Auth::id());
        if (!$items) {
            return null;
        }
        $code = Session::get('voucher_code');
        $voucher = $code ? VoucherService::find($code) : null;
        $limitError = $voucher ? VoucherService::limitError($voucher, (int) Auth::id()) : null;
        $quote = CheckoutService::quote(CartService::groupBySeller($items), $region, $limitError ? null : $voucher);
        if ($code && !$voucher) {
            $quote['voucher_error'] = 'Voucher ' . $code . ' no longer exists.';
        } elseif ($limitError) {
            $quote['voucher_error'] = $limitError;
        }
        return $quote;
    }

    private function region(Request $request): string
    {
        $regions = array_keys(ShippingService::regions());
        $r = (string) ($request->query('region') ?: old('region') ?: (Auth::user()['region'] ?? 'NCR'));
        return in_array($r, $regions, true) ? $r : 'NCR';
    }

    public function index(Request $request): Response
    {
        $region = $this->region($request);
        $quote = $this->buildQuote($region);
        if ($quote === null) {
            Session::flash('info', 'Your cart is empty. Add a gadget first.');
            return $this->redirect('/cart');
        }
        if ($quote['voucher_error'] && Session::get('voucher_code')) {
            Session::flash('info', $quote['voucher_error']);
        }
        return $this->view('checkout/index', [
            'title'    => 'Checkout',
            'user'     => Auth::user(),
            'quote'    => $quote,
            'region'   => $region,
            'regions'  => ShippingService::regions(),
            'vouchers' => Database::select('SELECT * FROM vouchers WHERE is_active = 1 AND (expires_at IS NULL OR expires_at > NOW()) ORDER BY min_spend'),
            'code'     => Session::get('voucher_code'),
        ]);
    }

    /** JSON: recalculates totals when the buyer changes region (called by app.js). */
    public function quoteJson(Request $request): Response
    {
        $quote = $this->buildQuote($this->region($request));
        if ($quote === null) {
            return $this->json(['message' => 'Cart is empty'], 404);
        }
        return $this->json([
            'subtotal' => peso($quote['subtotal']), 'shipping' => $quote['shipping'] > 0 ? peso($quote['shipping']) : 'Free',
            'discount' => '−' . peso($quote['discount']), 'has_discount' => $quote['discount'] > 0,
            'total' => peso($quote['total']), 'total_raw' => number_format((float) $quote['total'], 2, '.', ''), 'eta' => ShippingService::estimateLabel(...$quote['estimate']),
            'groups' => array_map(static fn($g) => ['seller_id' => $g['seller_id'], 'shipping' => $g['shipping_fee'] > 0 ? peso($g['shipping_fee']) : 'Free'], $quote['groups']),
        ]);
    }

    public function applyVoucher(Request $request): Response
    {
        $data = $this->validate($request, ['code' => 'required|max:30']);
        $voucher = VoucherService::find($data['code']);
        if (!$voucher) {
            Session::flash('errors', ['code' => 'No voucher matches "' . strtoupper($data['code']) . '".']);
            return $this->back();
        }
        if ($limit = VoucherService::limitError($voucher, (int) Auth::id())) {
            Session::flash('errors', ['code' => $limit]);
            return $this->back();
        }
        $quote = $this->buildQuote($this->region($request));
        $eval = VoucherService::evaluate($voucher, $quote['subtotal'] ?? 0, $quote['shipping'] ?? 0);
        if ($eval['error']) {
            Session::flash('errors', ['code' => $eval['error']]);
            return $this->back();
        }
        Session::put('voucher_code', $voucher['code']);
        Session::flash('success', 'Voucher ' . $voucher['code'] . ' applied: ' . $voucher['description'] . '.');
        return $this->back();
    }

    public function removeVoucher(Request $request): Response
    {
        Session::forget('voucher_code');
        Session::flash('info', 'Voucher removed.');
        return $this->back();
    }

    public function place(Request $request): Response
    {
        // Double submit: the first click already placed the order and emptied the cart.
        $last = Session::get('last_checkout');
        if (is_array($last) && $last['at'] > time() - 120 && \App\Services\CartService::count((int) Auth::id()) === 0) {
            return $this->redirect('/checkout/success?ref=' . urlencode($last['ref']));
        }

        $regions = implode(',', array_keys(ShippingService::regions()));
        $rules = [
            'recipient_name' => 'required|min:2|max:120',
            'phone'          => 'required|phone_ph',
            'address_line'   => 'required|min:5|max:255',
            'city'           => 'required|max:100',
            'province'       => 'required|max:100',
            'region'         => "required|in:$regions",
            'postal_code'    => 'nullable|regex:/^\d{4}$/',
            'notes'          => 'nullable|max:255',
            'payment_method' => 'required|in:cod,gcash,card',
        ];
        $method = (string) $request->input('payment_method');
        // GCash and card details are entered on the payment gateway page, not here.
        $data = $this->validate($request, $rules, [
            'address_line' => 'Street address', 'recipient_name' => 'Recipient name',
        ]);

        $user = Auth::user();
        $code = Session::get('voucher_code');
        $expected = $request->input('expected_total');
        $expected = is_numeric($expected) ? (float) $expected : null;

        try {
            $ref = CheckoutService::place($user, $data, $method, $code ?: null, $data['notes'] ?? null, $expected);
        } catch (DomainException $e) {
            Session::flash('error', $e->getMessage());
            Session::flash('old', array_diff_key($request->only(array_keys($rules)), array_flip(SENSITIVE_FIELDS)));
            return $this->redirect('/checkout');
        }
        Session::put('last_checkout', ['ref' => $ref, 'at' => time()]);

        if ($request->input('save_address')) {
            User::updateById((int) $user['id'], array_intersect_key($data, array_flip(['phone', 'address_line', 'city', 'province', 'region', 'postal_code'])));
        }
        Session::forget('voucher_code');
        if ($method === 'cod') {
            \App\Services\NotificationService::orderPlaced($ref);
            return $this->redirect('/checkout/success?ref=' . urlencode($ref));
        }
        // GCash / card: the order is saved (stock reserved); now pay on the gateway page.
        // Confirmation emails go out once the payment is confirmed.
        try {
            return $this->redirect(\App\Services\PaymentService::start($ref, (int) $user['id']));
        } catch (\Throwable $e) {
            Session::flash('error', 'Your order is saved, but the payment page couldn’t open: ' . $e->getMessage() . ' Use Pay now to try again.');
            $first = Database::scalar('SELECT id FROM orders WHERE checkout_ref = ? ORDER BY id LIMIT 1', [$ref]);
            return $this->redirect('/orders/' . $first);
        }
    }

    public function success(Request $request): Response
    {
        $orders = Database::select(
            'SELECT o.*, u.store_name FROM orders o JOIN users u ON u.id = o.seller_id WHERE o.checkout_ref = ? AND o.buyer_id = ? ORDER BY o.id',
            [(string) $request->query('ref'), Auth::id()]
        );
        if (!$orders) {
            return $this->redirect('/orders');
        }
        return $this->view('checkout/success', ['title' => 'Order placed', 'orders' => $orders]);
    }
}
