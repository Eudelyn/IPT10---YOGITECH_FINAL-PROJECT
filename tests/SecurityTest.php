<?php
declare(strict_types=1);

namespace Tests;

use App\Services\VoucherService;
use Core\Request;
use Core\Router;
use Core\Validator;

/** Regression tests for the QA fixes (v1.1). */
final class SecurityTest extends TestCase
{
    public function testArrayInputIsIgnoredNotCrashing(): void
    {
        $r = new Request('GET', '/products', ['q' => ['x'], 'sort' => 'newest']);
        $this->assertSame(null, $r->query('q'), 'q[]=x is treated as missing');
        $this->assertSame('fallback', $r->input('q', 'fallback'));
        $this->assertSame(['q' => null, 'sort' => 'newest'], $r->only(['q', 'sort']));
    }

    public function testValidatorRejectsArrays(): void
    {
        $v = new Validator(['name' => ['a', 'b']], ['name' => 'required|min:2']);
        $this->assertSame('Name is invalid.', $v->errors()['name']);
    }

    public function testPasswordStrengthAndDifferent(): void
    {
        $v = new Validator(['password' => 'abcdefgh'], ['password' => 'password']);
        $this->assertTrue($v->fails(), 'letters only fails');
        $v = new Validator(['password' => 'abcd1234'], ['password' => 'password']);
        $this->assertTrue($v->passes(), 'letter + number passes');
        $v = new Validator(['current_password' => 'old12345', 'password' => 'old12345'], ['password' => 'different:current_password']);
        $this->assertTrue($v->fails(), 'same as current fails');
    }

    public function testCardChecks(): void
    {
        $this->assertTrue(Validator::luhn('4242 4242 4242 4242'));
        $this->assertFalse(Validator::luhn('4242 4242 4242 4241'));
        $this->assertFalse(Validator::luhn('1234'));
        $now = mktime(12, 0, 0, 11, 11, 2026);
        $this->assertTrue(Validator::expiryInFuture('11/26', $now), 'this month is still valid');
        $this->assertTrue(Validator::expiryInFuture('01/30', $now));
        $this->assertFalse(Validator::expiryInFuture('10/26', $now), 'last month is expired');
        $this->assertFalse(Validator::expiryInFuture('13/30', $now));
    }

    public function testPartialCancelKeepsMinSpend(): void
    {
        $v = ['code' => 'YOGI10', 'min_spend' => 3000];
        $this->assertTrue(VoucherService::partialCancelError($v, 2000) !== null, 'leaving ₱2,000 under ₱3,000 min is blocked');
        $this->assertSame(null, VoucherService::partialCancelError($v, 3500), 'still above min spend');
        $this->assertSame(null, VoucherService::partialCancelError($v, 0), 'last order of the checkout can be cancelled');
        $this->assertSame(null, VoucherService::partialCancelError(null, 100), 'no voucher');
    }

    public function testLikeEscape(): void
    {
        $this->assertSame('50\\% off\\_x', like_escape('50% off_x'));
    }

    public function testEmailLinksIgnoreForgedHost(): void
    {
        $backup = $_SERVER['HTTP_HOST'] ?? null;
        $_SERVER['HTTP_HOST'] = 'evil.example';
        $this->assertTrue(strpos(absolute_url('/orders/1'), 'evil.example') === false, 'forged host is not used');
        $_SERVER['HTTP_HOST'] = 'localhost';
        $this->assertTrue(strpos(absolute_url('/orders/1'), 'http://localhost/') === 0, 'trusted host is used');
        if ($backup === null) { unset($_SERVER['HTTP_HOST']); } else { $_SERVER['HTTP_HOST'] = $backup; }
    }

    public function testResetRouteMatchesOnly64HexTokens(): void
    {
        $r = new Router();
        $r->get('/reset-password/{token:[a-f0-9]{64}}', fn() => 'x');
        $token = str_repeat('ab', 32);
        $m = $r->match('GET', '/reset-password/' . $token);
        $this->assertSame($token, $m['params']['token'] ?? null);
        $this->assertSame(null, $r->match('GET', '/reset-password/xyz'));
    }
}
