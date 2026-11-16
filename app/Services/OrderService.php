<?php
declare(strict_types=1);

namespace App\Services;

use Core\Database;
use DomainException;

/**
 * Status changes for orders (seller fulfilment, buyer cancel / confirm receipt).
 */
final class OrderService
{
    public static function events(int $orderId): array
    {
        return Database::select('SELECT * FROM shipment_events WHERE order_id = ? ORDER BY created_at DESC, id DESC', [$orderId]);
    }

    public static function items(int $orderId): array
    {
        return Database::select('SELECT * FROM order_items WHERE order_id = ? ORDER BY id', [$orderId]);
    }

    private static function allowed(string $from, string $to, string $actor): bool
    {
        return $actor === 'seller'
            ? ShippingService::canTransition($from, $to)
            : (($to === 'cancelled' && ShippingService::buyerCanCancel($from))
                || ($to === 'delivered' && ShippingService::buyerCanConfirmReceipt($from)));
    }

    /**
     * @param array $ctx courier, location, note, reason
     */
    public static function transition(array $order, string $to, array $ctx = [], string $actor = 'seller'): void
    {
        if (!self::allowed($order['status'], $to, $actor)) {
            throw new DomainException('Can\'t change this order from "' . ShippingService::label($order['status']) . '" to "' . ShippingService::label($to) . '".');
        }
        if ($to === 'shipped' && empty($ctx['courier'])) {
            throw new DomainException('Choose a courier before marking the order as shipped.');
        }

        $seller = Database::first('SELECT store_name, name, city FROM users WHERE id = ?', [$order['seller_id']]);
        $ctx += ['store' => $seller['store_name'] ?: $seller['name'], 'origin' => $seller['city'] ?: 'Seller warehouse'];

        Database::transaction(function () use ($order, $to, $ctx, $actor) {
            // Lock the row and re-check: the buyer and the seller may click at the same moment.
            $current = Database::first('SELECT * FROM orders WHERE id = ? FOR UPDATE', [$order['id']]);
            if (!$current || $current['status'] !== $order['status'] || !self::allowed($current['status'], $to, $actor)) {
                throw new DomainException('This order was just updated' . ($current ? ' to "' . ShippingService::label($current['status']) . '"' : '') . '. Refresh the page and try again.');
            }
            $order = $current;

            // GCash / card orders are prepared only after the payment is confirmed.
            if ($actor === 'seller' && $to !== 'cancelled' && $order['payment_method'] !== 'cod' && $order['payment_status'] !== 'paid') {
                throw new DomainException('Waiting for the buyer’s ' . payment_label($order['payment_method']) . ' payment. You can confirm the order once it’s paid.');
            }

            if ($to === 'cancelled' && $actor === 'buyer' && $order['voucher_code']) {
                $voucher = VoucherService::find($order['voucher_code']);
                $remaining = (float) Database::scalar(
                    "SELECT COALESCE(SUM(subtotal), 0) FROM orders WHERE checkout_ref = ? AND id <> ? AND status <> 'cancelled'",
                    [$order['checkout_ref'], $order['id']]
                );
                $error = VoucherService::partialCancelError($voucher, $remaining);
                if ($error) {
                    throw new DomainException($error);
                }
            }

            $now = date('Y-m-d H:i:s');
            $data = ['status' => $to, 'updated_at' => $now];
            if ($to === 'shipped') {
                $data['courier'] = $ctx['courier'];
                $data['shipped_at'] = $now;
                $order['courier'] = $ctx['courier'];
                [$from, $until] = ShippingService::estimate($order['region']);
                $data['estimated_from'] = $from;
                $data['estimated_to'] = $until;
            }
            $codPaid = $to === 'delivered' && $order['payment_method'] === 'cod' && $order['payment_status'] === 'unpaid';
            if ($to === 'delivered') {
                $data['delivered_at'] = $now;
                if ($codPaid) {
                    $data['payment_status'] = 'paid';
                }
            }
            if ($to === 'cancelled') {
                $data['cancelled_at'] = $now;
                $data['cancel_reason'] = $ctx['reason'] ?? 'Cancelled';
                if ($order['payment_status'] === 'paid') {
                    $data['payment_status'] = 'refunded';
                }
            }
            Database::update('orders', $data, 'id = ?', [$order['id']]);

            if ($to === 'cancelled') {
                foreach (self::items((int) $order['id']) as $item) {
                    if ($item['product_id']) {
                        Database::query(
                            'UPDATE products SET stock = stock + ?, sold_count = GREATEST(sold_count - ?, 0) WHERE id = ?',
                            [$item['quantity'], $item['quantity'], $item['product_id']]
                        );
                    }
                }
                VoucherService::releaseIfFullyCancelled((string) $order['checkout_ref']);
            }

            $event = ShippingService::eventFor($to, $order, $ctx + ['actor' => $actor]);
            Database::insert('shipment_events', ['order_id' => $order['id'], 'status' => $to] + $event + ['created_at' => $now]);
            if ($codPaid) {
                Database::insert('shipment_events', [
                    'order_id' => $order['id'], 'status' => 'payment',
                    'title' => $actor === 'buyer' ? 'Cash on delivery paid' : 'Cash on delivery collected',
                    'description' => $actor === 'buyer'
                        ? 'Buyer confirmed receiving the parcel and paying ' . peso($order['total']) . ' on delivery.'
                        : 'Collected ' . peso($order['total']) . ' from the recipient.',
                    'location' => null, 'created_at' => $now,
                ]);
            }
        });
    }
}
