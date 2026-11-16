<?php
declare(strict_types=1);

namespace App\Services\Payments;

/**
 * Built-in sandbox: Yogitech's own fake GCash / card gateway (routes under /sandbox-pay).
 * It behaves like a hosted checkout: the buyer leaves the store, logs in / enters a card,
 * types a one-time code, approves, and is sent back. No real money, no account needed.
 * The simulator pages record the outcome on the payment row, which fetchStatus() reads.
 */
final class SandboxGateway implements PaymentGateway
{
    public function name(): string
    {
        return 'sandbox gateway';
    }

    public function createSession(array $payment, array $orders, string $returnUrl, string $cancelUrl): array
    {
        return ['url' => '/sandbox-pay/' . $payment['public_id'], 'ref' => 'SBX-' . strtoupper(substr($payment['public_id'], 0, 10))];
    }

    public function fetchStatus(array $payment): array
    {
        $status = $payment['status'] === 'authorized' ? 'paid' : $payment['status'];
        return ['status' => $status, 'payer' => $payment['payer'] ?? null, 'failure' => $payment['failure'] ?? null];
    }
}
