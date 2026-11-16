<?php
/**
 * JSON API routes (read-only, public). Used by live search and for demoing Response::json().
 * @var Core\Router $router
 */

use App\Controllers\Api\ApiController;

$router->group(['prefix' => '/api', 'middleware' => ['throttle:120,1,api']], function ($r) {
    $r->get('/products', [ApiController::class, 'products']);
    $r->get('/products/{id:\d+}', [ApiController::class, 'product']);
    $r->get('/track/{tracking:[A-Za-z0-9-]+}', [ApiController::class, 'track'], ['throttle:30,1,track']);
    $r->get('/exchange-rate', [ApiController::class, 'exchangeRate']);
    $r->get('/shipping-quote', [ApiController::class, 'shippingQuote']);
});
