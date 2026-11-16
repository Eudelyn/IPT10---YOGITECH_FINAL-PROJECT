<?php
declare(strict_types=1);

namespace App\Services;

use Core\Database;
use Core\Logger;
use DomainException;

/**
 * Builds the checkout quote (per-seller shipping + voucher allocation) and places orders.
 */
final class CheckoutService
{
    /**
     * Splits $amount across parts proportionally to $weights, to the centavo.
     * The last part absorbs rounding so the sum always equals $amount.
     */
    public static function allocate(array $weights, float $amount): array
    {
        $total = array_sum($weights);
        $out = [];
        $running = 0.0;
        $n = count($weights);
        $i = 0;
        foreach ($weights as $k => $w) {
            $i++;
            if ($i === $n) {
                $out[$k] = round($amount - $running, 2);
            } else {
                $share = $total > 0 ? round($amount * $w / $total, 2) : 0.0;
                $out[$k] = $share;
                $running += $share;
            }
        }
        return $out;
    }

    /** @return array{groups:array, subtotal:float, shipping:float, discount:float, total:float, voucher:?array, voucher_error:?string, estimate:array} */
    public static function quote(array $groups, string $region, ?array $voucher): array
    {
        $subtotals = [];
        $shipping = [];
        foreach ($groups as $i => $g) {
            $subtotals[$i] = round($g['subtotal'], 2);
            $shipping[$i] = ShippingService::fee($region, $g['subtotal']);
        }
        $sumSub = array_sum($subtotals);
        $sumShip = array_sum($shipping);

        $eval = VoucherService::evaluate($voucher, $sumSub, $sumShip);
        $merchShares = $eval['merch'] > 0 ? self::allocate($subtotals, $eval['merch']) : array_fill_keys(array_keys($groups), 0.0);
        $shipShares = $eval['shipping'] > 0 ? self::allocate($shipping, $eval['shipping']) : array_fill_keys(array_keys($groups), 0.0);

        foreach ($groups as $i => &$g) {
            $g['subtotal'] = $subtotals[$i];
            $g['shipping_fee'] = $shipping[$i];
            $g['discount'] = round($merchShares[$i] + $shipShares[$i], 2);
            $g['total'] = round($g['subtotal'] + $g['shipping_fee'] - $g['discount'], 2);
        }
        unset($g);

        $discount = $eval['merch'] + $eval['shipping'];
        return [
            'groups'        => $groups,
            'subtotal'      => $sumSub,
            'shipping'      => $sumShip,
            'discount'      => $discount,
            'total'         => round($sumSub + $sumShip - $discount, 2),
            'voucher'       => $eval['error'] ? null : $voucher,
            'voucher_error' => $eval['error'],
            'estimate'      => ShippingService::estimate($region),
        ];
    }

    /**
     * Creates one order per seller inside a transaction. Stock is reserved atomically.
     *
     * @param ?string $voucherCode   the code the buyer applied (null = none)
     * @param ?float  $expectedTotal the total shown on the checkout page; if prices,
     *                               stock or the voucher changed since, nothing is charged
     * @return string checkout reference
     */
    public static function place(array $buyer, array $address, string $paymentMethod, ?string $voucherCode, ?string $notes, ?float $expectedTotal = null): string
    {
        return Database::transaction(function () use ($buyer, $address, $paymentMethod, $voucherCode, $notes, $expectedTotal) {
            $items = CartService::items((int) $buyer['id']);
            if (!$items) {
                throw new DomainException('Your cart is empty.');
            }
            foreach ($items as $it) {
                if ($it['status'] !== 'active') {
                    throw new DomainException($it['name'] . ' is no longer available. Remove it from your cart.');
                }
            }

            $voucher = null;
            if ($voucherCode) {
                // Lock the voucher so two buyers can't claim its last use at the same time.
                $voucher = Database::first('SELECT * FROM vouchers WHERE code = ? FOR UPDATE', [strtoupper($voucherCode)]);
                if (!$voucher) {
                    throw new DomainException('Voucher ' . strtoupper($voucherCode) . ' no longer exists. Remove it to continue.');
                }
                if ($limit = VoucherService::limitError($voucher, (int) $buyer['id'])) {
                    throw new DomainException($limit . ' Remove it to continue.');
                }
            }

            $quote = self::quote(CartService::groupBySeller($items), $address['region'], $voucher);
            if ($voucher && $quote['voucher_error']) {
                throw new DomainException($quote['voucher_error'] . ' Remove the voucher to continue.');
            }
            if ($expectedTotal !== null && abs($quote['total'] - $expectedTotal) >= 0.01) {
                throw new DomainException('Your total changed from ' . peso($expectedTotal) . ' to ' . peso($quote['total'])
                    . ' (a price, shipping fee or voucher was updated). Check the summary and place the order again.');
            }
            [$etaFrom, $etaTo] = $quote['estimate'];
            $checkoutRef = 'CK' . date('ymd') . strtoupper(bin2hex(random_bytes(3)));
            $paid = false; // GCash/card orders become paid when the payment gateway confirms (PaymentService)
            $now = date('Y-m-d H:i:s');

            foreach ($quote['groups'] as $g) {
                $orderId = Database::insert('orders', [
                    'order_number'    => self::orderNumber(),
                    'tracking_number' => self::uniqueTracking(),
                    'checkout_ref'    => $checkoutRef,
                    'buyer_id'        => $buyer['id'],
                    'seller_id'       => $g['seller_id'],
                    'status'          => 'pending',
                    'payment_method'  => $paymentMethod,
                    'payment_status'  => $paid ? 'paid' : 'unpaid',
                    'subtotal'        => $g['subtotal'],
                    'shipping_fee'    => $g['shipping_fee'],
                    'discount'        => $g['discount'],
                    'total'           => $g['total'],
                    'voucher_code'    => $quote['voucher']['code'] ?? null,
                    'recipient_name'  => $address['recipient_name'],
                    'phone'           => $address['phone'],
                    'address_line'    => $address['address_line'],
                    'city'            => $address['city'],
                    'province'        => $address['province'],
                    'region'          => $address['region'],
                    'postal_code'     => $address['postal_code'] ?? null,
                    'notes'           => $notes ?: null,
                    'estimated_from'  => $etaFrom,
                    'estimated_to'    => $etaTo,
                    'created_at'      => $now,
                    'updated_at'      => $now,
                ]);

                foreach ($g['items'] as $it) {
                    $qty = (int) $it['quantity'];
                    $reserved = Database::query(
                        "UPDATE products SET stock = stock - ?, sold_count = sold_count + ? WHERE id = ? AND stock >= ? AND status = 'active'",
                        [$qty, $qty, $it['id'], $qty]
                    )->rowCount();
                    if ($reserved === 0) {
                        throw new DomainException('Only ' . (int) $it['stock'] . ' left of ' . $it['name'] . '. Update the quantity in your cart.');
                    }
                    Database::insert('order_items', [
                        'order_id' => $orderId, 'product_id' => $it['id'], 'product_name' => $it['name'],
                        'thumbnail' => $it['thumbnail'], 'unit_price' => $it['price'], 'quantity' => $qty,
                        'line_total' => round((float) $it['price'] * $qty, 2),
                    ]);
                }

                $event = ShippingService::eventFor('pending', [], ['store' => $g['store_name']]);
                Database::insert('shipment_events', ['order_id' => $orderId, 'status' => 'pending'] + $event + ['created_at' => $now]);
            }

            if ($voucher && $quote['discount'] > 0) {
                VoucherService::redeem($voucher, (int) $buyer['id'], $checkoutRef, (float) $quote['discount']);
            }
            Database::query('DELETE FROM cart_items WHERE user_id = ?', [$buyer['id']]);
            Logger::info('Checkout placed', ['ref' => $checkoutRef, 'buyer' => $buyer['id'], 'total' => $quote['total']]);
            return $checkoutRef;
        });
    }

    private static function orderNumber(): string
    {
        do {
            $n = 'YT' . date('ymd') . '-' . random_int(10000, 99999);
        } while (Database::scalar('SELECT 1 FROM orders WHERE order_number = ?', [$n]));
        return $n;
    }

    private static function uniqueTracking(): string
    {
        do {
            $t = ShippingService::trackingNumber();
        } while (Database::scalar('SELECT 1 FROM orders WHERE tracking_number = ?', [$t]));
        return $t;
    }
}
