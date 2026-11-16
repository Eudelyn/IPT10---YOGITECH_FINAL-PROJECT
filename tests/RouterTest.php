<?php
declare(strict_types=1);

namespace Tests;

use Core\Request;
use Core\Response;
use Core\Router;

final class RouterTest extends TestCase
{
    public function testCompilesParametersWithConstraints(): void
    {
        $this->assertSame('#^/orders/(?P<id>\d+)$#', Router::compile('/orders/{id:\d+}'));
        $this->assertSame('#^/category/(?P<slug>[^/]+)$#', Router::compile('/category/{slug}'));
    }

    public function testMatchesRouteAndExtractsParams(): void
    {
        $r = new Router();
        $r->get('/products/{id:\d+}', fn() => 'x');
        $m = $r->match('GET', '/products/42');
        $this->assertSame(['id' => '42'], $m['params']);
        $this->assertSame(null, $r->match('GET', '/products/abc'), 'constraint rejects non-digits');
    }

    public function testMethodNotAllowed(): void
    {
        $r = new Router();
        $r->post('/cart/add', fn() => 'x');
        $this->assertTrue(!empty($r->match('GET', '/cart/add')['method_not_allowed']));
    }

    public function testGroupPrefixAndMiddlewareStack(): void
    {
        $r = new Router();
        $r->group(['prefix' => '/seller', 'middleware' => ['auth']], function ($g) {
            $g->get('/orders', fn() => 'x', ['role:seller']);
        });
        $m = $r->match('GET', '/seller/orders');
        $this->assertSame('/seller/orders', $m['path']);
        $this->assertSame(['auth', 'role:seller'], $m['middleware']);
    }

    public function testDispatchRunsClosureAndConvertsArrayToJson(): void
    {
        $r = new Router();
        $r->get('/ping/{n}', fn(Request $req, string $n) => ['pong' => $n]);
        $res = $r->dispatch(new Request('GET', '/ping/7'));
        $this->assertSame(200, $res->status());
        $this->assertSame(['pong' => '7'], json_decode($res->content(), true));
    }

    public function testMiddlewareCanShortCircuit(): void
    {
        $r = new Router(['block' => BlockingMiddleware::class]);
        $r->get('/secret', fn() => 'should not run', ['block']);
        $res = $r->dispatch(new Request('GET', '/secret'));
        $this->assertSame(403, $res->status());
    }
}

final class BlockingMiddleware implements \Core\Middleware\MiddlewareInterface
{
    public function handle(Request $request, callable $next, string ...$args): Response
    {
        return Response::html('blocked', 403);
    }
}
