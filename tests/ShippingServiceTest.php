<?php
declare(strict_types=1);

namespace Tests;

use App\Services\ShippingService;
use DateTimeImmutable;

final class ShippingServiceTest extends TestCase
{
    public function testFeeByRegionAndFreeThreshold(): void
    {
        $this->assertEqualsFloat(99, ShippingService::fee('NCR', 2500));
        $this->assertEqualsFloat(189, ShippingService::fee('Mindanao', 2500));
        $this->assertEqualsFloat(0, ShippingService::fee('Visayas', (float) config('shipping.free_threshold')));
    }

    public function testEstimateSkipsSundays(): void
    {
        // Saturday, 2026-11-07: +1 business day lands on Monday the 9th
        $d = ShippingService::addBusinessDays(new DateTimeImmutable('2026-11-07'), 1);
        $this->assertSame('2026-11-09', $d->format('Y-m-d'));
        [$from, $to] = ShippingService::estimate('NCR', new DateTimeImmutable('2026-11-04'));
        $this->assertSame('2026-11-05', $from);
        $this->assertSame('2026-11-07', $to);
    }

    public function testStatusStateMachine(): void
    {
        $this->assertTrue(ShippingService::canTransition('pending', 'confirmed'));
        $this->assertTrue(ShippingService::canTransition('in_transit', 'in_transit'), 'multiple transit updates allowed');
        $this->assertFalse(ShippingService::canTransition('shipped', 'cancelled'), 'cannot cancel after shipping');
        $this->assertFalse(ShippingService::canTransition('delivered', 'pending'));
        $this->assertTrue(ShippingService::buyerCanCancel('confirmed'));
        $this->assertFalse(ShippingService::buyerCanCancel('packed'));
    }

    public function testProgress(): void
    {
        $this->assertSame(0, ShippingService::progressPercent('pending'));
        $this->assertSame(60, ShippingService::progressPercent('in_transit'));
        $this->assertSame(100, ShippingService::progressPercent('delivered'));
        $this->assertSame(-1, ShippingService::progressIndex('cancelled'));
    }

    public function testTrackingNumberFormat(): void
    {
        $this->assertTrue((bool) preg_match('/^YGT[0-9A-F]{10}$/', ShippingService::trackingNumber()));
    }
}
