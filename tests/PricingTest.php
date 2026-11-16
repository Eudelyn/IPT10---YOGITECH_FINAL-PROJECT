<?php
declare(strict_types=1);

namespace Tests;

use App\Services\CheckoutService;
use App\Services\ExchangeRateService;
use App\Services\ProductApiService;
use App\Services\VoucherService;

final class PricingTest extends TestCase
{
    public function testUsdToPesoRounding(): void
    {
        $this->assertEqualsFloat(11599, ExchangeRateService::toPeso(199.99, 58.0));
        $this->assertEqualsFloat(58, ExchangeRateService::toPeso(1.0, 58.0));
    }

    public function testMapsDummyJsonProduct(): void
    {
        $row = ProductApiService::mapProduct([
            'id' => 1, 'title' => 'iPhone 9', 'price' => 549, 'discountPercentage' => 10, 'stock' => 0,
            'brand' => 'Apple', 'thumbnail' => 'https://x/t.jpg', 'images' => ['https://x/1.jpg'],
        ], 3, 1, 58.0);
        $this->assertSame('iPhone 9', $row['name']);
        $this->assertEqualsFloat(31839, $row['compare_price']);   // 549 × 58 = 31,842 → 31,839
        $this->assertEqualsFloat(28659, $row['price']);           // 494.10 × 58 = 28,657.8 → 28,659
        $this->assertSame(1, $row['stock'], 'stock floor of 1');
        $this->assertSame('dummyjson', $row['source']);
    }

    public function testAllocationSumsExactly(): void
    {
        $parts = CheckoutService::allocate([1000, 2000, 333.33], 100);
        $this->assertEqualsFloat(100, array_sum($parts), 'parts add up to the total');
        $this->assertEqualsFloat(30.0, $parts[0]);
    }

    public function testVoucherRules(): void
    {
        $pct = ['code' => 'YOGI10', 'type' => 'percent', 'value' => 10, 'min_spend' => 3000, 'max_discount' => 1000, 'is_active' => 1, 'expires_at' => null];
        $this->assertEqualsFloat(500, VoucherService::evaluate($pct, 5000, 0)['merch']);
        $this->assertEqualsFloat(1000, VoucherService::evaluate($pct, 50000, 0)['merch'], 'capped');
        $this->assertTrue(VoucherService::evaluate($pct, 1000, 0)['error'] !== null, 'min spend enforced');

        $ship = ['code' => 'FREESHIP', 'type' => 'shipping', 'value' => 200, 'min_spend' => 0, 'max_discount' => null, 'is_active' => 1, 'expires_at' => null];
        $this->assertEqualsFloat(129, VoucherService::evaluate($ship, 500, 129)['shipping']);

        $expired = ['expires_at' => '2020-01-01 00:00:00'] + $pct;
        $this->assertSame('This voucher has expired.', VoucherService::evaluate($expired, 5000, 0)['error']);
    }

    public function testPesoFormatting(): void
    {
        $this->assertSame('₱12,345.50', peso(12345.5));
        $this->assertSame('₱10,000', peso(10000, false));
    }
}
