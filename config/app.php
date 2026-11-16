<?php
return [
    'name'           => env('APP_NAME', 'Yogitech'),
    'env'            => env('APP_ENV', 'production'),
    'debug'          => (bool) env('APP_DEBUG', false),
    'timezone'       => env('APP_TIMEZONE', 'Asia/Manila'),
    'currency'       => 'PHP',
    'currency_symbol'=> '₱',
    'session_name'   => 'yogitech_session',
    'log_requests'   => (bool) env('LOG_REQUESTS', true),
    'per_page'       => 12,
    'low_stock'      => 5,
    // false = one store (the seller account from the demo data); sign-up creates shoppers only.
    // true  = marketplace: anyone can sign up as a seller and open a store.
    'seller_signup'  => filter_var(env('ALLOW_SELLER_SIGNUP', false), FILTER_VALIDATE_BOOLEAN),
];
