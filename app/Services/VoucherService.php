<?php
declare(strict_types=1);

namespace App\Services;

use Core\Database;

/**
 * Platform vouchers: percent off, fixed amount off, or shipping discount.
 */
final class VoucherService
{
    public static function find(string $code): ?array
    {
        return Database::first('SELECT * FROM vouchers WHERE code = ? LIMIT 1', [strtoupper(trim($code))]);
    }

    /**
     * Usage limits (needs the database). Returns an error message or null.
     * Uses are counted per checkout and given back when the whole checkout is cancelled.
     */
    public static function limitError(array $voucher, int $userId, bool $lock = false): ?string
    {
        $suffix = $lock ? ' FOR UPDATE' : '';
        if ($voucher['usage_limit'] ?? null) {
            $used = (int) Database::scalar('SELECT COUNT(*) FROM voucher_redemptions WHERE voucher_id = ? AND released_at IS NULL' . $suffix, [$voucher['id']]);
            if ($used >= (int) $voucher['usage_limit']) {
                return $voucher['code'] . ' has been fully claimed.';
            }
        }
        if ($voucher['per_user_limit'] ?? null) {
            $mine = (int) Database::scalar(
                'SELECT COUNT(*) FROM voucher_redemptions WHERE voucher_id = ? AND user_id = ? AND released_at IS NULL' . $suffix,
                [$voucher['id'], $userId]
            );
            if ($mine >= (int) $voucher['per_user_limit']) {
                $n = (int) $voucher['per_user_limit'];
                return 'You already used ' . $voucher['code'] . ($n === 1 ? '.' : ' ' . $n . ' times.');
            }
        }
        return null;
    }

    public static function redeem(array $voucher, int $userId, string $checkoutRef, float $discount): void
    {
        Database::insert('voucher_redemptions', [
            'voucher_id' => $voucher['id'], 'user_id' => $userId, 'checkout_ref' => $checkoutRef,
            'discount' => $discount, 'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /** Gives the use back once every order of that checkout is cancelled. */
    public static function releaseIfFullyCancelled(string $checkoutRef): void
    {
        $active = (int) Database::scalar("SELECT COUNT(*) FROM orders WHERE checkout_ref = ? AND status <> 'cancelled'", [$checkoutRef]);
        if ($active === 0) {
            Database::query('UPDATE voucher_redemptions SET released_at = ? WHERE checkout_ref = ? AND released_at IS NULL', [date('Y-m-d H:i:s'), $checkoutRef]);
        }
    }

    /**
     * Blocks a buyer from cancelling part of a voucher checkout when the orders left
     * would no longer reach the voucher's minimum spend (otherwise min spend is bypassed).
     * Pure function (unit-tested). Returns an error message or null.
     */
    public static function partialCancelError(?array $voucher, float $remainingSubtotal): ?string
    {
        if ($voucher === null || $remainingSubtotal <= 0) {
            return null; // no voucher, or this is the last active order of the checkout
        }
        $min = (float) $voucher['min_spend'];
        if ($min > 0 && $remainingSubtotal < $min) {
            return 'This order used voucher ' . $voucher['code'] . ' (min. spend ' . peso($min) . '). Cancelling it leaves '
                . peso($remainingSubtotal) . ' in the other orders from the same checkout, below the minimum. '
                . 'Cancel those orders too, or keep this one.';
        }
        return null;
    }

    /**
     * Pure calculation (unit-tested).
     * @return array{merch:float, shipping:float, error:?string}
     */
    public static function evaluate(?array $voucher, float $merchSubtotal, float $shippingTotal, ?int $now = null): array
    {
        $none = ['merch' => 0.0, 'shipping' => 0.0, 'error' => null];
        if ($voucher === null) {
            return $none;
        }
        $now = $now ?? time();
        if (!(int) $voucher['is_active'] || (!empty($voucher['expires_at']) && strtotime($voucher['expires_at']) < $now)) {
            return ['error' => 'This voucher has expired.'] + $none;
        }
        if ($merchSubtotal < (float) $voucher['min_spend']) {
            return ['error' => 'Spend at least ' . peso($voucher['min_spend']) . ' to use ' . $voucher['code'] . '.'] + $none;
        }
        $value = (float) $voucher['value'];
        switch ($voucher['type']) {
            case 'percent':
                $off = $merchSubtotal * $value / 100;
                if ($voucher['max_discount'] !== null) {
                    $off = min($off, (float) $voucher['max_discount']);
                }
                return ['merch' => round($off, 2), 'shipping' => 0.0, 'error' => null];
            case 'fixed':
                return ['merch' => round(min($value, $merchSubtotal), 2), 'shipping' => 0.0, 'error' => null];
            case 'shipping':
                if ($shippingTotal <= 0) {
                    return ['error' => 'Your order already ships free.'] + $none;
                }
                return ['merch' => 0.0, 'shipping' => round(min($value, $shippingTotal), 2), 'error' => null];
        }
        return ['error' => 'Invalid voucher.'] + $none;
    }
}
