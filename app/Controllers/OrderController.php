<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\Order;
use App\Models\Review;
use App\Services\OrderService;
use App\Services\ShippingService;
use Core\Auth;
use Core\Controller;
use Core\Request;
use Core\Response;
use Core\Session;
use DomainException;

final class OrderController extends Controller
{
    public const TABS = [
        ''          => 'All',
        'active'    => 'In progress',
        'delivered' => 'Delivered',
        'cancelled' => 'Cancelled',
    ];

    public function index(Request $request): Response
    {
        $tab = (string) $request->query('status', '');
        if (!array_key_exists($tab, self::TABS)) {
            $tab = '';
        }
        return $this->view('orders/index', [
            'title'  => 'My orders',
            'pager'  => $pager = Order::forBuyer((int) Auth::id(), $tab ?: null, (int) $request->query('page', 1)),
            'orders' => $pager->items,
            'tab'    => $tab,
            'tabs'   => self::TABS,
        ]);
    }

    public function show(Request $request, string $id): Response
    {
        $order = Order::findForBuyer((int) $id, (int) Auth::id());
        if (!$order) {
            abort(404);
        }
        $items = OrderService::items((int) $id);
        $reviewable = [];
        if ($order['status'] === 'delivered') {
            foreach ($items as $it) {
                if ($it['product_id'] && !Review::existsFor((int) Auth::id(), (int) $it['product_id'])) {
                    $reviewable[] = (int) $it['product_id'];
                }
            }
        }
        return $this->view('orders/show', [
            'title'      => 'Order ' . $order['order_number'],
            'order'      => $order,
            'items'      => $items,
            'events'     => OrderService::events((int) $id),
            'reviewable' => $reviewable,
        ]);
    }

    public function cancel(Request $request, string $id): Response
    {
        $order = Order::findForBuyer((int) $id, (int) Auth::id());
        if (!$order) {
            abort(404);
        }
        $data = $this->validate($request, ['reason' => 'required|in:Changed my mind,Found a better price,Ordered by mistake,Need to change address,Other']);
        try {
            OrderService::transition($order, 'cancelled', ['reason' => 'Buyer cancelled — ' . $data['reason']], 'buyer');
            \App\Services\NotificationService::statusChanged((int) $order['id'], 'buyer');
            Session::flash('success', 'Order ' . $order['order_number'] . ' cancelled.' . ($order['payment_status'] === 'paid' ? ' Your payment will be refunded.' : ''));
        } catch (DomainException $e) {
            Session::flash('error', $e->getMessage());
        }
        return $this->redirect('/orders/' . $id);
    }

    public function received(Request $request, string $id): Response
    {
        $order = Order::findForBuyer((int) $id, (int) Auth::id());
        if (!$order) {
            abort(404);
        }
        try {
            OrderService::transition($order, 'delivered', [], 'buyer');
            \App\Services\NotificationService::statusChanged((int) $order['id'], 'buyer');
            Session::flash('success', 'Thanks for confirming. Rate your gadgets below.');
        } catch (DomainException $e) {
            Session::flash('error', $e->getMessage());
        }
        return $this->redirect('/orders/' . $id);
    }
}
