<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\Order;
use App\Services\OrderService;
use Core\Auth;
use Core\Controller;
use Core\Request;
use Core\Response;

/** Public "Track my parcel" page: no sign-in needed, personal details masked. */
final class TrackingController extends Controller
{
    public function index(Request $request): Response
    {
        $no = strtoupper((string) $request->query('no', ''));
        $order = $no !== '' ? Order::byTracking($no, Auth::is('buyer') ? Auth::id() : null) : null;
        return $this->view('tracking/index', [
            'title'  => 'Track a parcel',
            'no'     => $no,
            'order'  => $order,
            'events' => $order ? OrderService::events((int) $order['id']) : [],
            'items'  => $order ? OrderService::items((int) $order['id']) : [],
            'isOwner'=> $order && (int) $order['buyer_id'] === Auth::id(),
        ]);
    }
}
