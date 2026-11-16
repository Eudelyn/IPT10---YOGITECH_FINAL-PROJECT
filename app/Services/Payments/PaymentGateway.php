<?php
declare(strict_types=1);

namespace App\Services\Payments;

/**
 * A payment provider. Yogitech creates a payment session, sends the buyer to the
 * provider's page, and checks the result when the buyer comes back.
 */
interface PaymentGateway
{
    /** Human-readable name shown to buyers, e.g. "GCash sandbox". */
    public function name(): string;

    /**
     * Creates the session at the provider.
     * @return array{url: string, ref: ?string} where to send the buyer, and the provider's id for the session
     */
    public function createSession(array $payment, array $orders, string $returnUrl, string $cancelUrl): array;

    /**
     * Asks the provider what happened.
     * @return array{status: string, payer: ?string, failure: ?string} status = paid | pending | failed | cancelled
     */
    public function fetchStatus(array $payment): array;
}
