<?php
declare(strict_types=1);

namespace App\Controllers\Seller;

use App\Models\Order;
use App\Services\OrderService;
use App\Services\ShippingService;
use Core\Auth;
use Core\Controller;
use Core\Database;
use Core\Request;
use Core\Response;
use Core\Session;
use DomainException;

final class OrderController extends Controller
{
    public function index(Request $request): Response
    {
        $status = (string) $request->query('status', '');
        if ($status !== '' && !isset(ShippingService::LABELS[$status])) {
            $status = '';
        }
        $counts = array_column(Database::select('SELECT status, COUNT(*) AS n FROM orders WHERE seller_id = ? GROUP BY status', [Auth::id()]), 'n', 'status');
        return $this->view('seller/orders/index', [
            'title'  => 'Orders',
            'pager'  => $pager = Order::forSeller((int) Auth::id(), $status ?: null, (string) $request->query('q', '') ?: null, (int) $request->query('page', 1)),
            'orders' => $pager->items,
            'status' => $status,
            'counts' => $counts,
            'q'      => (string) $request->query('q', ''),
        ], 'layouts/seller');
    }

    public function show(Request $request, string $id): Response
    {
        $order = Order::findForSeller((int) $id, (int) Auth::id());
        if (!$order) {
            abort(404);
        }
        return $this->view('seller/orders/show', [
            'title'    => 'Order ' . $order['order_number'],
            'order'    => $order,
            'items'    => OrderService::items((int) $id),
            'events'   => OrderService::events((int) $id),
            'next'     => ShippingService::nextStatuses($order['status']),
            'couriers' => ShippingService::couriers(),
        ], 'layouts/seller');
    }

    public function updateStatus(Request $request, string $id): Response
    {
        $order = Order::findForSeller((int) $id, (int) Auth::id());
        if (!$order) {
            abort(404);
        }
        $data = $this->validate($request, [
            'status'   => 'required|in:' . implode(',', array_keys(ShippingService::LABELS)),
            'courier'  => 'nullable|in:' . implode(',', ShippingService::couriers()),
            'location' => 'nullable|max:120',
            'note'     => 'nullable|max:150',
            'reason'   => 'nullable|max:150',
        ]);
        try {
            OrderService::transition($order, $data['status'], array_filter([
                'courier' => $data['courier'] ?? null, 'location' => $data['location'] ?? null,
                'note' => $data['note'] ?? null, 'reason' => $data['reason'] ? 'Seller cancelled — ' . $data['reason'] : 'Cancelled by seller',
            ]));
            \App\Services\NotificationService::statusChanged((int) $order['id'], 'seller');
            Session::flash('success', 'Order ' . $order['order_number'] . ' → ' . ShippingService::label($data['status']) . '. The buyer\'s tracking page is updated and they were emailed.');
        } catch (DomainException $e) {
            Session::flash('error', $e->getMessage());
        }
        return $this->redirect('/seller/orders/' . $id);
    }
}
