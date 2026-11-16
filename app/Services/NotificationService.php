<?php
declare(strict_types=1);

namespace App\Services;

use Core\Database;
use Core\Mailer;
use Core\View;
use Throwable;

/**
 * Email notifications to buyers and sellers. Only registered users get emails
 * (orders always belong to a signed-up account). Failures never break the page.
 */
final class NotificationService
{
    /** Statuses the buyer is emailed about when the seller updates an order. */
    private const BUYER_STATUSES = ['confirmed', 'shipped', 'out_for_delivery', 'delivered', 'cancelled'];

    private const BUYER_COPY = [
        'pending'          => ['Order placed', 'We sent your order to {store}. You’ll get an email when they confirm it.'],
        'confirmed'        => ['Order confirmed', '{store} confirmed your order and is preparing it.'],
        'shipped'          => ['Your order has shipped', 'Your parcel is with {courier}. Track it anytime with the number below.'],
        'out_for_delivery' => ['Out for delivery today', 'A rider is bringing your parcel today. Keep your phone nearby.'],
        'delivered'        => ['Delivered', 'Your parcel was delivered. Enjoy your gadget, and leave a review if you can.'],
        'cancelled'        => ['Order cancelled', 'Your order was cancelled. {refund}'],
    ];

    public static function welcome(array $user): void
    {
        self::safely(function () use ($user) {
            $seller = $user['role'] === 'seller';
            self::mail($user, 'Welcome to Yogitech', 'emails/welcome', [
                'name' => $user['name'], 'seller' => $seller,
                'cta' => absolute_url($seller ? '/seller' : '/products'),
            ], ['type' => 'welcome']);
        });
    }

    /** After checkout: one email per order to the buyer and to the seller. */
    public static function orderPlaced(string $checkoutRef): void
    {
        self::safely(function () use ($checkoutRef) {
            foreach (Database::select('SELECT id FROM orders WHERE checkout_ref = ?', [$checkoutRef]) as $row) {
                [$order, $buyer, $seller, $items] = self::load((int) $row['id']);
                self::buyerUpdate($order, $buyer, $seller, $items, 'pending');
                self::sellerAlert($order, $buyer, $seller, $items, 'new');
            }
        });
    }

    /**
     * After a status change. $actor = 'seller' emails the buyer; 'buyer' (cancel or
     * confirm receipt) emails the seller, plus the buyer gets their own confirmation.
     */
    public static function statusChanged(int $orderId, string $actor): void
    {
        self::safely(function () use ($orderId, $actor) {
            [$order, $buyer, $seller, $items] = self::load($orderId);
            $status = $order['status'];
            if ($actor === 'seller' && in_array($status, self::BUYER_STATUSES, true)) {
                self::buyerUpdate($order, $buyer, $seller, $items, $status);
            }
            if ($actor === 'buyer') {
                self::sellerAlert($order, $buyer, $seller, $items, $status);
                self::buyerUpdate($order, $buyer, $seller, $items, $status);
            }
        });
    }

    private static function buyerUpdate(array $order, array $buyer, array $seller, array $items, string $status): void
    {
        [$title, $body] = self::BUYER_COPY[$status] ?? [ShippingService::label($status), ''];
        $body = strtr($body, [
            '{store}' => $seller['store_name'] ?: $seller['name'],
            '{courier}' => $order['courier'] ?: 'the courier',
            '{refund}' => $order['payment_status'] === 'refunded' ? 'Your payment will be refunded.' : 'You weren’t charged.',
        ]);
        self::mail($buyer, $title . ' — ' . $order['order_number'], 'emails/order', [
            'title' => $title, 'intro' => $body, 'order' => $order, 'items' => $items, 'name' => $buyer['name'],
            'cta' => absolute_url('/orders/' . $order['id']), 'ctaLabel' => 'Track your order',
            'eta' => ShippingService::estimateLabel($order['estimated_from'], $order['estimated_to']),
        ], ['type' => 'buyer_' . $status, 'order_id' => $order['id']]);
    }

    private static function sellerAlert(array $order, array $buyer, array $seller, array $items, string $event): void
    {
        $copy = [
            'new'       => ['New order to fulfil', $buyer['name'] . ' placed an order. Confirm it and pack the items below.'],
            'cancelled' => ['Order cancelled by buyer', $buyer['name'] . ' cancelled this order. The stock was returned to your inventory. ' . ($order['cancel_reason'] ?? '')],
            'delivered' => ['Buyer received the order', $buyer['name'] . ' confirmed the parcel arrived. This sale is now complete.'],
        ];
        if (!isset($copy[$event])) {
            return;
        }
        [$title, $body] = $copy[$event];
        self::mail($seller, $title . ' — ' . $order['order_number'], 'emails/order', [
            'title' => $title, 'intro' => $body, 'order' => $order, 'items' => $items, 'name' => $seller['name'],
            'cta' => absolute_url('/seller/orders/' . $order['id']), 'ctaLabel' => 'Open in seller center',
            'eta' => ShippingService::estimateLabel($order['estimated_from'], $order['estimated_to']),
        ], ['type' => 'seller_' . $event, 'order_id' => $order['id']]);
    }

    private static function load(int $orderId): array
    {
        $order = Database::first('SELECT * FROM orders WHERE id = ?', [$orderId]);
        $buyer = Database::first('SELECT * FROM users WHERE id = ?', [$order['buyer_id']]);
        $seller = Database::first('SELECT * FROM users WHERE id = ?', [$order['seller_id']]);
        return [$order, $buyer, $seller, OrderService::items($orderId)];
    }

    private static function mail(array $user, string $subject, string $view, array $data, array $meta): void
    {
        $html = View::render($view, $data + ['subject' => $subject], 'emails/layout');
        Mailer::send($user['email'], $user['name'], $subject, $html, '', $meta + ['user_id' => $user['id']]);
    }

    /** Forgot password: emails a one-time reset link. */
    public static function passwordResetLink(array $user, string $link, int $minutes): void
    {
        self::safely(function () use ($user, $link, $minutes) {
            self::mail($user, 'Reset your Yogitech password', 'emails/password-reset', [
                'name' => $user['name'], 'cta' => $link, 'minutes' => $minutes,
            ], ['type' => 'password_reset']);
        });
    }

    /** Security notice after a password reset or change. $how = 'reset' | 'changed'. */
    public static function passwordChanged(array $user, string $how, string $ip): void
    {
        self::safely(function () use ($user, $how, $ip) {
            self::mail($user, 'Your Yogitech password was ' . ($how === 'reset' ? 'reset' : 'changed'), 'emails/password-changed', [
                'name' => $user['name'], 'how' => $how, 'ip' => $ip, 'when' => date('M j, Y g:i A'),
                'cta' => absolute_url('/forgot-password'),
            ], ['type' => 'password_' . $how]);
        });
    }

    /** Tells the OLD address that the account email was changed. */
    public static function emailChanged(array $user, string $oldEmail, string $ip): void
    {
        self::safely(function () use ($user, $oldEmail, $ip) {
            $html = View::render('emails/email-changed', [
                'name' => $user['name'], 'newEmail' => $user['email'], 'ip' => $ip, 'when' => date('M j, Y g:i A'),
                'subject' => 'Your Yogitech email was changed',
            ], 'emails/layout');
            Mailer::send($oldEmail, $user['name'], 'Your Yogitech email was changed', $html, '', ['type' => 'email_changed', 'user_id' => $user['id']]);
        });
    }

    /** Runs after the page is sent (see after_response) and never breaks the request. */
    private static function safely(callable $fn): void
    {
        after_response(static function () use ($fn) {
            try {
                $fn();
            } catch (Throwable $e) {
                \Core\Logger::error('Notification failed: ' . $e->getMessage());
            }
        });
    }
}
