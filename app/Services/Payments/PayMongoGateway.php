<?php
declare(strict_types=1);

namespace App\Services\Payments;

use Core\HttpClient;
use RuntimeException;

/**
 * PayMongo hosted checkout, test mode (https://docs.paymongo.com).
 *   POST {base}/checkout_sessions   Basic auth: secret key as username, empty password
 *   GET  {base}/checkout_sessions/{id}
 * Amounts are in centavos. Webhooks can't reach a localhost site, so the result is
 * checked when the buyer returns to the success URL.
 */
final class PayMongoGateway implements PaymentGateway
{
    private string $key;
    private string $base;

    public function __construct()
    {
        $this->key = (string) config('payments.paymongo.secret_key');
        $this->base = (string) config('payments.paymongo.base_url');
        if ($this->key === '') {
            throw new RuntimeException('PAYMONGO_SECRET_KEY is empty in .env (use your sk_test_ key), or set PAYMENT_GATEWAY=sandbox.');
        }
    }

    public function name(): string
    {
        return 'PayMongo (test mode)';
    }

    private function http(): HttpClient
    {
        return (new HttpClient(20, 1))->withHeaders(['Authorization' => 'Basic ' . base64_encode($this->key . ':')]);
    }

    public function createSession(array $payment, array $orders, string $returnUrl, string $cancelUrl): array
    {
        $lines = [];
        foreach ($orders as $o) {
            $lines[] = ['name' => 'Yogitech order ' . $o['order_number'], 'amount' => (int) round((float) $o['total'] * 100), 'currency' => 'PHP', 'quantity' => 1];
        }
        $res = $this->http()->post($this->base . '/checkout_sessions', ['data' => ['attributes' => [
            'line_items'           => $lines,
            'payment_method_types' => [$payment['method'] === 'gcash' ? 'gcash' : 'card'],
            'success_url'          => $returnUrl,
            'cancel_url'           => $cancelUrl,
            'reference_number'     => $payment['public_id'],
            'description'          => 'Yogitech checkout ' . $payment['checkout_ref'],
            'send_email_receipt'   => false,
        ]]]);
        $url = (string) ($res['json']['data']['attributes']['checkout_url'] ?? '');
        if (!$res['ok'] || $url === '') {
            throw new RuntimeException('PayMongo: ' . self::error($res));
        }
        return ['url' => $url, 'ref' => (string) $res['json']['data']['id']];
    }

    public function fetchStatus(array $payment): array
    {
        $res = $this->http()->get($this->base . '/checkout_sessions/' . rawurlencode((string) $payment['gateway_ref']));
        if (!$res['ok']) {
            throw new RuntimeException('PayMongo: ' . self::error($res));
        }
        $a = $res['json']['data']['attributes'] ?? [];
        $paid = in_array(strtolower((string) ($a['status'] ?? '')), ['paid', 'completed', 'succeeded'], true)
            || strtolower((string) ($a['payment_intent']['attributes']['status'] ?? '')) === 'succeeded';
        foreach ((array) ($a['payments'] ?? []) as $p) {
            if (strtolower((string) ($p['attributes']['status'] ?? '')) === 'paid') {
                $paid = true;
            }
        }
        return ['status' => $paid ? 'paid' : 'pending', 'payer' => null, 'failure' => null];
    }

    private static function error(array $res): string
    {
        $e = $res['json']['errors'][0] ?? null;
        if (is_array($e)) {
            return (string) ($e['detail'] ?? $e['code'] ?? 'request failed');
        }
        return (string) ($res['error'] ?? 'HTTP ' . $res['status']);
    }
}
