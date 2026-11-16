<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Services\PaymentService;
use Core\Auth;
use Core\Controller;
use Core\Exceptions\HttpException;
use Core\Request;
use Core\Response;
use Core\Session;
use Core\Validator;

/**
 * The built-in sandbox payment gateway ("pretend GCash / bank" pages).
 * GCash: mobile number + 4-digit MPIN → 6-digit SMS code → review → Pay.
 * Card:  card details → 3-D Secure code from the bank → paid.
 * Test cases are listed on each page (wrong MPIN 0000, declined cards, low balance).
 */
final class SandboxPayController extends Controller
{
    private const MAX_TRIES = 3;
    public const CARD_DECLINED = '4000000000000002';
    public const CARD_NO_FUNDS = '4000000000009995';

    public function show(Request $request, string $id): Response
    {
        [$payment, $st] = $this->load($id);
        if ($payment['status'] !== 'pending') {
            return $this->redirect('/payments/' . $id . '/return');
        }
        $step = $st['step'] ?? ($payment['method'] === 'gcash' ? 'login' : 'card');
        return $this->view('sandbox/pay', [
            'title' => $payment['method'] === 'gcash' ? 'GCash payment' : 'Card payment',
            'payment' => $payment, 'step' => $step, 'st' => $st,
            'buyer' => Auth::user(), 'balance' => (float) config('payments.sandbox.gcash_balance'),
        ], 'layouts/gateway');
    }

    /** GCash step 1: number + MPIN. */
    public function login(Request $request, string $id): Response
    {
        [$payment, $st] = $this->load($id, 'gcash');
        $v = new Validator($request->only(['number', 'mpin']), ['number' => 'required|phone_ph', 'mpin' => 'required|regex:/^\d{4}$/'], ['mpin' => 'MPIN', 'number' => 'GCash number']);
        if ($v->fails()) {
            return $this->fail($id, $v->errors(), ['number' => $request->input('number')]);
        }
        $number = preg_replace('/\D/', '', (string) $request->input('number'));
        if ($request->input('mpin') === '0000') {
            return $this->wrongTry($payment, $st, 'Incorrect MPIN.', 'mpin', ['number' => $number]);
        }
        $this->save($id, ['step' => 'otp', 'number' => $number, 'otp' => self::code(), 'tries' => 0]);
        return $this->redirect('/sandbox-pay/' . $id);
    }

    /** GCash step 2 / card step 2: one-time code. */
    public function otp(Request $request, string $id): Response
    {
        [$payment, $st] = $this->load($id);
        if (!in_array($st['step'] ?? '', ['otp', 'card_otp'], true)) {
            return $this->redirect('/sandbox-pay/' . $id);
        }
        if (!hash_equals((string) $st['otp'], preg_replace('/\D/', '', (string) $request->input('code')))) {
            return $this->wrongTry($payment, $st, 'That code doesn’t match. Check the SMS and try again.', 'code');
        }
        if ($st['step'] === 'card_otp') {
            return $this->authorize($payment, $st['card']);
        }
        $this->save($id, ['step' => 'review', 'tries' => 0, 'otp' => null] + $st);
        return $this->redirect('/sandbox-pay/' . $id);
    }

    /** Resend a new code. */
    public function resend(Request $request, string $id): Response
    {
        [, $st] = $this->load($id);
        if (in_array($st['step'] ?? '', ['otp', 'card_otp'], true)) {
            $this->save($id, ['otp' => self::code()] + $st);
            Session::flash('success', 'We sent a new code.');
        }
        return $this->redirect('/sandbox-pay/' . $id);
    }

    /** GCash step 3: approve. */
    public function approve(Request $request, string $id): Response
    {
        [$payment, $st] = $this->load($id, 'gcash');
        if (($st['step'] ?? '') !== 'review') {
            return $this->redirect('/sandbox-pay/' . $id);
        }
        if ((float) $payment['amount'] > (float) config('payments.sandbox.gcash_balance')) {
            PaymentService::recordSandboxResult($payment, 'failed', self::maskNumber($st['number']), 'Insufficient GCash balance');
            Session::forget('sbx_' . $id);
            return $this->redirect('/payments/' . $id . '/return');
        }
        return $this->authorize($payment, 'GCash ' . self::maskNumber($st['number']));
    }

    /** Card step 1: card details. */
    public function card(Request $request, string $id): Response
    {
        [$payment] = $this->load($id, 'card');
        $data = $request->only(['card_name', 'card_number', 'card_expiry', 'card_cvc']);
        $v = new Validator($data, [
            'card_name'   => 'required|min:2|max:80',
            'card_number' => 'required|regex:/^[\d ]{13,23}$/|luhn',
            'card_expiry' => ['required', 'regex:/^(0[1-9]|1[0-2])\/\d{2}$/', 'card_expiry'],
            'card_cvc'    => 'required|regex:/^\d{3,4}$/',
        ], ['card_name' => 'Name on card', 'card_number' => 'Card number', 'card_expiry' => 'Expiry (MM/YY)', 'card_cvc' => 'CVC']);
        if ($v->fails()) {
            return $this->fail($id, $v->errors(), ['card_name' => $data['card_name']]);
        }
        $digits = preg_replace('/\D/', '', (string) $data['card_number']);
        $label = self::brand($digits) . ' •••• ' . substr($digits, -4);
        if ($digits === self::CARD_DECLINED || $digits === self::CARD_NO_FUNDS) {
            PaymentService::recordSandboxResult($payment, 'failed', $label, $digits === self::CARD_DECLINED ? 'Card declined by the bank' : 'Insufficient funds');
            Session::forget('sbx_' . $id);
            return $this->redirect('/payments/' . $id . '/return');
        }
        // Card number, expiry and CVC are never stored: only the brand + last 4 digits.
        $this->save($id, ['step' => 'card_otp', 'card' => $label, 'otp' => self::code(), 'tries' => 0]);
        return $this->redirect('/sandbox-pay/' . $id);
    }

    public function cancel(Request $request, string $id): Response
    {
        [$payment] = $this->load($id);
        PaymentService::recordSandboxResult($payment, 'cancelled', null, 'Cancelled by the buyer');
        Session::forget('sbx_' . $id);
        return $this->redirect('/payments/' . $id . '/cancel');
    }

    // ── helpers ────────────────────────────────────────────────────────

    private function authorize(array $payment, string $payer): Response
    {
        PaymentService::recordSandboxResult($payment, 'authorized', $payer, null);
        Session::forget('sbx_' . $payment['public_id']);
        return $this->redirect('/payments/' . $payment['public_id'] . '/return');
    }

    private function wrongTry(array $payment, array $st, string $message, string $field, array $old = []): Response
    {
        $tries = (int) ($st['tries'] ?? 0) + 1;
        if ($tries >= self::MAX_TRIES) {
            PaymentService::recordSandboxResult($payment, 'failed', null, 'Too many wrong attempts');
            Session::forget('sbx_' . $payment['public_id']);
            return $this->redirect('/payments/' . $payment['public_id'] . '/return');
        }
        $this->save($payment['public_id'], ['tries' => $tries] + $st);
        $left = self::MAX_TRIES - $tries;
        return $this->fail($payment['public_id'], [$field => $message . ' ' . $left . ' attempt' . ($left === 1 ? '' : 's') . ' left.'], $old);
    }

    private function fail(string $id, array $errors, array $old = []): Response
    {
        Session::flash('errors', $errors);
        Session::flash('old', $old);
        return $this->redirect('/sandbox-pay/' . $id);
    }

    /** @return array{0: array, 1: array} the payment (must be the buyer's, still pending) and the step state */
    private function load(string $id, ?string $method = null): array
    {
        $payment = PaymentService::find($id);
        if (!$payment || $payment['gateway'] !== 'sandbox' || (int) $payment['buyer_id'] !== (int) Auth::id()
            || ($method !== null && $payment['method'] !== $method)) {
            throw new HttpException(404);
        }
        if ($payment['status'] !== 'pending' && $method !== null) {
            throw new HttpException(409, 'This payment is already finished.');
        }
        $st = Session::get('sbx_' . $id);
        return [$payment, is_array($st) ? $st : []];
    }

    private function save(string $id, array $state): void
    {
        Session::put('sbx_' . $id, $state);
    }

    private static function code(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    public static function maskNumber(string $n): string
    {
        return substr($n, 0, 4) . '•••' . substr($n, -4);
    }

    private static function brand(string $digits): string
    {
        if ($digits[0] === '4') {
            return 'Visa';
        }
        if (preg_match('/^(5[1-5]|2[2-7])/', $digits)) {
            return 'Mastercard';
        }
        if (strpos($digits, '35') === 0) {
            return 'JCB';
        }
        return 'Card';
    }
}
