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
use DomainException;
use Throwable;

/** Buyer side of online payments: start (Pay now), come back from the gateway, or cancel. */
final class PaymentController extends Controller
{
    /** "Pay now" on an unpaid GCash/card order (also used right after checkout). */
    public function pay(Request $request, string $ref): Response
    {
        try {
            return $this->redirect(PaymentService::start($ref, (int) Auth::id()));
        } catch (DomainException $e) {
            Session::flash('info', $e->getMessage());
        } catch (Throwable $e) {
            Session::flash('error', 'Couldn’t open the payment page: ' . $e->getMessage());
        }
        return $this->redirect($this->orderUrl($ref));
    }

    public function return(Request $request, string $id): Response
    {
        $payment = $this->owned($id);
        try {
            $status = PaymentService::finish($payment);
        } catch (Throwable $e) {
            Session::flash('error', 'We couldn’t confirm the payment yet: ' . $e->getMessage() . ' Refresh this page in a moment.');
            return $this->redirect($this->orderUrl($payment['checkout_ref']));
        }
        if ($status === 'paid') {
            Session::flash('success', 'Payment received. Thank you!');
            return $this->redirect('/checkout/success?ref=' . urlencode($payment['checkout_ref']));
        }
        if ($status === 'pending') {
            Session::flash('info', 'The payment is still being confirmed. Refresh in a moment, or use Pay now to try again.');
        } else {
            $fresh = PaymentService::find($id);
            Session::flash('error', 'Payment ' . ($status === 'failed' ? 'failed' : 'cancelled')
                . (!empty($fresh['failure']) ? ': ' . $fresh['failure'] : '.') . ' Your order is saved — use Pay now to try again.');
        }
        return $this->redirect($this->orderUrl($payment['checkout_ref']));
    }

    public function cancel(Request $request, string $id): Response
    {
        $payment = $this->owned($id);
        PaymentService::cancel($payment);
        Session::flash('info', 'Payment cancelled. Your order is saved — use Pay now when you’re ready, or cancel the order.');
        return $this->redirect($this->orderUrl($payment['checkout_ref']));
    }

    private function owned(string $id): array
    {
        $payment = PaymentService::find($id);
        if (!$payment || (int) $payment['buyer_id'] !== (int) Auth::id()) {
            throw new HttpException(404);
        }
        return $payment;
    }

    /** First order of the checkout, or the orders list. */
    private function orderUrl(string $ref): string
    {
        $id = \Core\Database::scalar('SELECT id FROM orders WHERE checkout_ref = ? AND buyer_id = ? ORDER BY id LIMIT 1', [$ref, Auth::id()]);
        return $id ? '/orders/' . $id : '/orders';
    }
}
