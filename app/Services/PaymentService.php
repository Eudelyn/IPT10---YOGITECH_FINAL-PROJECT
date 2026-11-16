<?php
declare(strict_types=1);

namespace App\Services;

use App\Services\Payments\PaymentGateway;
use App\Services\Payments\PayMongoGateway;
use App\Services\Payments\SandboxGateway;
use Core\Database;
use Core\Logger;
use DomainException;

/**
 * Online payments for a checkout (all orders placed together are paid at once).
 * Flow: start() → buyer pays on the gateway page → back to /payments/{id}/return → finish().
 */
final class PaymentService
{
    public static function gateway(?string $name = null): PaymentGateway
    {
        return ($name ?? (string) config('payments.gateway', 'sandbox')) === 'paymongo'
            ? new PayMongoGateway()
            : new SandboxGateway();
    }

    public static function find(string $publicId): ?array
    {
        return Database::first('SELECT * FROM payments WHERE public_id = ?', [$publicId]);
    }

    /** Orders of the checkout that still need paying (not cancelled, unpaid, online). */
    public static function payableOrders(string $checkoutRef, int $buyerId): array
    {
        return Database::select(
            "SELECT * FROM orders WHERE checkout_ref = ? AND buyer_id = ? AND payment_method <> 'cod'
               AND payment_status = 'unpaid' AND status <> 'cancelled' ORDER BY id",
            [$checkoutRef, $buyerId]
        );
    }

    /** Creates a payment and returns the URL of the gateway page to send the buyer to. */
    public static function start(string $checkoutRef, int $buyerId): string
    {
        $orders = self::payableOrders($checkoutRef, $buyerId);
        if (!$orders) {
            throw new DomainException('There is nothing left to pay for this order.');
        }
        $amount = round(array_sum(array_map(static fn($o) => (float) $o['total'], $orders)), 2);
        $gatewayName = (string) config('payments.gateway', 'sandbox');
        $gateway = self::gateway($gatewayName);

        // Older unfinished attempts for this checkout stop counting.
        Database::query("UPDATE payments SET status = 'cancelled' WHERE checkout_ref = ? AND status IN ('pending','authorized')", [$checkoutRef]);
        $publicId = bin2hex(random_bytes(12));
        $id = Database::insert('payments', [
            'public_id' => $publicId, 'checkout_ref' => $checkoutRef, 'buyer_id' => $buyerId,
            'gateway' => $gatewayName, 'method' => $orders[0]['payment_method'], 'amount' => $amount,
            'status' => 'pending', 'created_at' => date('Y-m-d H:i:s'),
        ]);
        $payment = Database::first('SELECT * FROM payments WHERE id = ?', [$id]);

        $session = $gateway->createSession(
            $payment, $orders,
            absolute_url('/payments/' . $publicId . '/return'),
            absolute_url('/payments/' . $publicId . '/cancel')
        );
        Database::update('payments', ['gateway_ref' => $session['ref']], 'id = ?', [$id]);
        Logger::info('Payment started', ['payment' => $publicId, 'checkout' => $checkoutRef, 'amount' => $amount, 'gateway' => $gatewayName]);
        return $session['url'];
    }

    /** Called by the sandbox gateway pages: $status = authorized | failed | cancelled. */
    public static function recordSandboxResult(array $payment, string $status, ?string $payer, ?string $failure): void
    {
        Database::query(
            "UPDATE payments SET status = ?, payer = ?, failure = ? WHERE id = ? AND status = 'pending'",
            [$status, $payer, $failure, $payment['id']]
        );
    }

    /**
     * Checks the result with the gateway and, when paid, marks every order of the checkout paid.
     * Safe to call twice (refresh, back button): the second call changes nothing.
     * @return string paid | pending | failed | cancelled
     */
    public static function finish(array $payment, bool $notify = true): string
    {
        if (in_array($payment['status'], ['pending', 'authorized'], true)) {
            $result = self::gateway($payment['gateway'])->fetchStatus($payment);
        } else {
            $result = ['status' => $payment['status'], 'payer' => $payment['payer'], 'failure' => $payment['failure']];
        }
        if ($result['status'] !== 'paid') {
            if (in_array($result['status'], ['failed', 'cancelled'], true) && $payment['status'] === 'pending') {
                Database::query("UPDATE payments SET status = ?, failure = ? WHERE id = ? AND status = 'pending'",
                    [$result['status'], $result['failure'], $payment['id']]);
            }
            return $result['status'];
        }

        $newlyPaid = Database::transaction(function () use ($payment, $result) {
            $now = date('Y-m-d H:i:s');
            $changed = Database::query(
                "UPDATE payments SET status = 'paid', paid_at = ?, payer = COALESCE(?, payer) WHERE id = ? AND status <> 'paid'",
                [$now, $result['payer'], $payment['id']]
            )->rowCount();
            if ($changed === 0) {
                return false; // already recorded
            }
            $label = $payment['method'] === 'gcash' ? 'GCash' : 'card';
            foreach (self::payableOrders($payment['checkout_ref'], (int) $payment['buyer_id']) as $o) {
                Database::update('orders', ['payment_status' => 'paid', 'updated_at' => $now], 'id = ?', [$o['id']]);
                Database::insert('shipment_events', [
                    'order_id' => $o['id'], 'status' => 'payment', 'title' => 'Payment received',
                    'description' => 'Paid ' . peso($o['total']) . ' via ' . $label
                        . ($payment['gateway'] === 'paymongo' ? ' (PayMongo test mode)' : ' (sandbox)')
                        . '. Ref ' . ($payment['gateway_ref'] ?: $payment['public_id']) . '.',
                    'location' => null, 'created_at' => $now,
                ]);
            }
            return true;
        });
        if ($newlyPaid && $notify) {
            NotificationService::orderPlaced($payment['checkout_ref']); // confirmation emails go out once paid
        }
        if ($newlyPaid) {
            Logger::info('Payment paid', ['payment' => $payment['public_id'], 'checkout' => $payment['checkout_ref']]);
        }
        return 'paid';
    }

    /** Buyer pressed Cancel on the gateway page. */
    public static function cancel(array $payment): void
    {
        Database::query("UPDATE payments SET status = 'cancelled' WHERE id = ? AND status = 'pending'", [$payment['id']]);
    }
}
