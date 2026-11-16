<?php
/**
 * Web routes. $router is a Core\Router instance.
 * @var Core\Router $router
 */

use App\Controllers\AccountController;
use App\Controllers\AuthController;
use App\Controllers\BuyerController;
use App\Controllers\CartController;
use App\Controllers\CheckoutController;
use App\Controllers\HomeController;
use App\Controllers\OrderController;
use App\Controllers\PasswordResetController;
use App\Controllers\PaymentController;
use App\Controllers\SandboxPayController;
use App\Controllers\SetupController;
use App\Controllers\DevMailController;
use App\Controllers\ProductController;
use App\Controllers\ReviewController;
use App\Controllers\TrackingController;
use App\Controllers\WishlistController;
use App\Controllers\Seller\DashboardController as SellerDashboard;
use App\Controllers\Seller\ImportController as SellerImport;
use App\Controllers\Seller\OrderController as SellerOrders;
use App\Controllers\Seller\ProductController as SellerProducts;

// ── Public storefront ───────────────────────────────
$router->get('/', [HomeController::class, 'index']);
$router->get('/products', [ProductController::class, 'index']);
$router->get('/products/{id:\d+}', [ProductController::class, 'show']);
$router->get('/category/{slug:[a-z0-9-]+}', [ProductController::class, 'category']);
$router->get('/store/{id:\d+}', [ProductController::class, 'store']);
$router->get('/track', [TrackingController::class, 'index'], ['throttle:30,1,track']);

// ── Authentication ──────────────────────────────────
$router->group(['middleware' => ['guest']], function ($r) {
    $r->get('/login', [AuthController::class, 'showLogin']);
    $r->post('/login', [AuthController::class, 'login'], ['throttle:8,1']);
    $r->get('/register', [AuthController::class, 'showRegister']);
    $r->post('/register', [AuthController::class, 'register'], ['throttle:5,1']);
    $r->get('/forgot-password', [PasswordResetController::class, 'showForgot']);
    $r->post('/forgot-password', [PasswordResetController::class, 'sendLink'], ['throttle:5,10']);
});
// Reset links work even if someone is signed in on this browser (they get signed out after).
$router->get('/reset-password/{token:[a-f0-9]{64}}', [PasswordResetController::class, 'showReset'], ['throttle:20,10,reset']);
$router->post('/reset-password', [PasswordResetController::class, 'reset'], ['throttle:10,10,reset-post']);
$router->post('/logout', [AuthController::class, 'logout'], ['auth']);

// ── Any signed-in user ──────────────────────────────
$router->group(['middleware' => ['auth']], function ($r) {
    $r->get('/account', [AccountController::class, 'edit']);
    $r->post('/account', [AccountController::class, 'update']);
    $r->post('/account/password', [AccountController::class, 'password']);
});

// ── Buyer area ──────────────────────────────────────
$router->group(['middleware' => ['auth', 'role:buyer']], function ($r) {
    $r->get('/dashboard', [BuyerController::class, 'dashboard']);

    $r->get('/cart', [CartController::class, 'index']);
    $r->post('/cart/add', [CartController::class, 'add']);
    $r->post('/cart/{id:\d+}/update', [CartController::class, 'update']);
    $r->post('/cart/{id:\d+}/remove', [CartController::class, 'remove']);

    $r->get('/checkout', [CheckoutController::class, 'index']);
    $r->get('/checkout/quote', [CheckoutController::class, 'quoteJson']);
    $r->post('/checkout/voucher', [CheckoutController::class, 'applyVoucher']);
    $r->post('/checkout/voucher/remove', [CheckoutController::class, 'removeVoucher']);
    $r->post('/checkout', [CheckoutController::class, 'place']);
    $r->get('/checkout/success', [CheckoutController::class, 'success']);

    $r->get('/orders', [OrderController::class, 'index']);
    $r->get('/orders/{id:\d+}', [OrderController::class, 'show']);
    $r->post('/orders/{id:\d+}/cancel', [OrderController::class, 'cancel']);
    $r->post('/orders/{id:\d+}/received', [OrderController::class, 'received']);

    // Online payments (GCash / card) — see PaymentService
    $r->post('/payments/checkout/{ref:[A-Z0-9]+}/pay', [PaymentController::class, 'pay'], ['throttle:10,1,pay']);
    $r->get('/payments/{id:[a-f0-9]{24}}/return', [PaymentController::class, 'return']);
    $r->get('/payments/{id:[a-f0-9]{24}}/cancel', [PaymentController::class, 'cancel']);

    // Built-in sandbox payment gateway (pretend GCash / bank pages)
    $r->get('/sandbox-pay/{id:[a-f0-9]{24}}', [SandboxPayController::class, 'show']);
    $r->post('/sandbox-pay/{id:[a-f0-9]{24}}/login', [SandboxPayController::class, 'login'], ['throttle:10,1,sbx']);
    $r->post('/sandbox-pay/{id:[a-f0-9]{24}}/otp', [SandboxPayController::class, 'otp'], ['throttle:10,1,sbx']);
    $r->post('/sandbox-pay/{id:[a-f0-9]{24}}/resend', [SandboxPayController::class, 'resend'], ['throttle:5,1,sbx-resend']);
    $r->post('/sandbox-pay/{id:[a-f0-9]{24}}/approve', [SandboxPayController::class, 'approve']);
    $r->post('/sandbox-pay/{id:[a-f0-9]{24}}/card', [SandboxPayController::class, 'card'], ['throttle:10,1,sbx']);
    $r->post('/sandbox-pay/{id:[a-f0-9]{24}}/cancel', [SandboxPayController::class, 'cancel']);

    $r->get('/wishlist', [WishlistController::class, 'index']);
    $r->post('/wishlist/{id:\d+}/toggle', [WishlistController::class, 'toggle']);

    $r->post('/products/{id:\d+}/reviews', [ReviewController::class, 'store']);
});

// ── Seller center ───────────────────────────────────
$router->group(['prefix' => '/seller', 'middleware' => ['auth', 'role:seller']], function ($r) {
    $r->get('/', [SellerDashboard::class, 'index']);

    $r->get('/products', [SellerProducts::class, 'index']);
    $r->get('/products/create', [SellerProducts::class, 'create']);
    $r->post('/products', [SellerProducts::class, 'store']);
    $r->get('/products/{id:\d+}/edit', [SellerProducts::class, 'edit']);
    $r->post('/products/{id:\d+}', [SellerProducts::class, 'update']);
    $r->post('/products/{id:\d+}/delete', [SellerProducts::class, 'destroy']);

    $r->get('/import', [SellerImport::class, 'index']);
    $r->post('/import', [SellerImport::class, 'store']);

    $r->get('/orders', [SellerOrders::class, 'index']);
    $r->get('/orders/{id:\d+}', [SellerOrders::class, 'show']);
    $r->post('/orders/{id:\d+}/status', [SellerOrders::class, 'updateStatus']);
});

// Developer tool: shown on the "database is incomplete" error page (local + APP_DEBUG only).
$router->post('/setup/repair-database', [SetupController::class, 'repair'], ['throttle:5,1']);
$router->get('/dev/mail', [DevMailController::class, 'index']);
$router->get('/dev/mail/{file:[\w.-]+\.html}', [DevMailController::class, 'show']);
