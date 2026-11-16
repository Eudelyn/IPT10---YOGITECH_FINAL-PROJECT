<?php
use App\Middleware\AuthMiddleware;
use App\Middleware\CsrfMiddleware;
use App\Middleware\GuestMiddleware;
use App\Middleware\RequestLogMiddleware;
use App\Middleware\RoleMiddleware;
use App\Middleware\ThrottleMiddleware;

return [
    // Run on every request, in this order.
    'global' => [RequestLogMiddleware::class, CsrfMiddleware::class],

    // Short names usable in routes: ['auth', 'role:seller', 'throttle:5,1']
    'aliases' => [
        'auth'     => AuthMiddleware::class,
        'guest'    => GuestMiddleware::class,
        'role'     => RoleMiddleware::class,
        'throttle' => ThrottleMiddleware::class,
    ],
];
