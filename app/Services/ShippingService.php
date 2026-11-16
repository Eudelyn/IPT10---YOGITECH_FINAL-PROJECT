<?php
declare(strict_types=1);

namespace App\Services;

use DateTimeImmutable;

/**
 * Shipping fees, delivery estimates and the order status workflow (state machine).
 */
final class ShippingService
{
    public const FLOW = ['pending', 'confirmed', 'packed', 'shipped', 'in_transit', 'out_for_delivery', 'delivered'];

    public const LABELS = [
        'pending'          => 'Order placed',
        'confirmed'        => 'Confirmed',
        'packed'           => 'Packed',
        'shipped'          => 'Shipped',
        'in_transit'       => 'In transit',
        'out_for_delivery' => 'Out for delivery',
        'delivered'        => 'Delivered',
        'cancelled'        => 'Cancelled',
    ];

    /** Steps shown in the progress tracker (in_transit is part of "Shipped"). */
    public const MILESTONES = ['pending', 'confirmed', 'packed', 'shipped', 'out_for_delivery', 'delivered'];

    /** What the seller may move an order to from each status. */
    private const TRANSITIONS = [
        'pending'          => ['confirmed', 'cancelled'],
        'confirmed'        => ['packed', 'cancelled'],
        'packed'           => ['shipped', 'cancelled'],
        'shipped'          => ['in_transit', 'out_for_delivery'],
        'in_transit'       => ['in_transit', 'out_for_delivery'],
        'out_for_delivery' => ['delivered'],
        'delivered'        => [],
        'cancelled'        => [],
    ];

    public static function regions(): array { return (array) config('shipping.regions', []); }
    public static function couriers(): array { return (array) config('shipping.couriers', []); }
    public static function label(string $status): string { return self::LABELS[$status] ?? ucfirst(str_replace('_', ' ', $status)); }

    public static function fee(string $region, float $subtotal): float
    {
        if ($subtotal >= (float) config('shipping.free_threshold', 10000)) {
            return 0.0;
        }
        return (float) (self::regions()[$region]['fee'] ?? 149);
    }

    /** Delivery window counting business days (Sundays skipped). @return array{0:string,1:string} Y-m-d */
    public static function estimate(string $region, ?DateTimeImmutable $from = null): array
    {
        $cfg = self::regions()[$region] ?? ['min_days' => 3, 'max_days' => 7];
        $from = $from ?? new DateTimeImmutable('today');
        return [
            self::addBusinessDays($from, (int) $cfg['min_days'])->format('Y-m-d'),
            self::addBusinessDays($from, (int) $cfg['max_days'])->format('Y-m-d'),
        ];
    }

    public static function addBusinessDays(DateTimeImmutable $date, int $days): DateTimeImmutable
    {
        while ($days > 0) {
            $date = $date->modify('+1 day');
            if ($date->format('N') !== '7') {
                $days--;
            }
        }
        return $date;
    }

    public static function estimateLabel(?string $from, ?string $to): string
    {
        if (!$from || !$to) {
            return '—';
        }
        $f = strtotime($from);
        $t = strtotime($to);
        return date('M', $f) === date('M', $t)
            ? date('M j', $f) . '–' . date('j', $t)
            : date('M j', $f) . ' – ' . date('M j', $t);
    }

    public static function nextStatuses(string $current): array { return self::TRANSITIONS[$current] ?? []; }

    public static function canTransition(string $from, string $to): bool
    {
        return in_array($to, self::nextStatuses($from), true);
    }

    public static function buyerCanCancel(string $status): bool { return in_array($status, ['pending', 'confirmed'], true); }
    public static function buyerCanConfirmReceipt(string $status): bool { return in_array($status, ['shipped', 'in_transit', 'out_for_delivery'], true); }

    /** Index in MILESTONES for the progress bar; -1 when cancelled. */
    public static function progressIndex(string $status): int
    {
        if ($status === 'cancelled') {
            return -1;
        }
        if ($status === 'in_transit') {
            $status = 'shipped';
        }
        $i = array_search($status, self::MILESTONES, true);
        return $i === false ? 0 : (int) $i;
    }

    public static function progressPercent(string $status): int
    {
        $i = self::progressIndex($status);
        return $i < 0 ? 0 : (int) round($i / (count(self::MILESTONES) - 1) * 100);
    }

    public static function trackingNumber(): string
    {
        return 'YGT' . strtoupper(bin2hex(random_bytes(5)));
    }

    /**
     * Default timeline text for a status change.
     * @return array{title:string, description:string, location:?string}
     */
    public static function eventFor(string $status, array $order, array $ctx = []): array
    {
        $store = $ctx['store'] ?? 'The seller';
        $origin = $ctx['origin'] ?? 'Seller warehouse';
        $courier = $ctx['courier'] ?? $order['courier'] ?? 'Courier';
        $dest = trim(($order['city'] ?? '') . ', ' . ($order['province'] ?? ''), ', ');
        $note = trim((string) ($ctx['note'] ?? ''));

        switch ($status) {
            case 'pending':
                $e = ['Order placed', 'Waiting for ' . $store . ' to confirm your order.', null];
                break;
            case 'confirmed':
                $e = ['Order confirmed', $store . ' confirmed your order and is preparing it.', $origin];
                break;
            case 'packed':
                $e = ['Parcel packed', 'Your parcel is packed and waiting for courier pickup.', $origin];
                break;
            case 'shipped':
                $e = ['Picked up by ' . $courier, 'Your parcel is on its way. Tracking no. ' . ($order['tracking_number'] ?? ''), $courier . ' — ' . $origin . ' hub'];
                break;
            case 'in_transit':
                $e = ['In transit', 'Parcel arrived at a sorting facility.', $ctx['location'] ?? ($courier . ' sorting center')];
                break;
            case 'out_for_delivery':
                $e = ['Out for delivery', 'A rider is bringing your parcel today. Keep your phone nearby.', $dest];
                break;
            case 'delivered':
                // No recipient name here: tracking timelines are public.
                $e = ['Delivered', ($ctx['actor'] ?? 'seller') === 'buyer'
                    ? 'Buyer confirmed the parcel arrived.'
                    : 'Parcel delivered to the recipient.', $dest];
                break;
            case 'cancelled':
                $e = ['Order cancelled', 'Reason: ' . ($ctx['reason'] ?? 'Cancelled'), null];
                break;
            default:
                $e = [self::label($status), '', null];
        }
        if ($note !== '') {
            $e[1] .= ' Note: ' . $note;
        }
        if (!empty($ctx['location'])) {
            $e[2] = $ctx['location'];
        }
        return ['title' => $e[0], 'description' => $e[1], 'location' => $e[2]];
    }
}
