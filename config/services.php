<?php
return [
    'verify_ssl' => (bool) env('HTTP_VERIFY_SSL', true),

    // DummyJSON — free mock product API (https://dummyjson.com/docs/products)
    'products_api' => [
        'base_url'   => rtrim((string) env('PRODUCTS_API_URL', 'https://dummyjson.com'), '/'),
        'cache_ttl'  => 3600,
        // Yogitech category slug => DummyJSON category slug
        'categories' => [
            'smartphones' => 'smartphones',
            'laptops'     => 'laptops',
            'tablets'     => 'tablets',
            'accessories' => 'mobile-accessories',
        ],
    ],

    // ExchangeRate-API open endpoint — converts USD prices to Philippine pesos
    'exchange_api' => [
        'url'       => env('EXCHANGE_API_URL', 'https://open.er-api.com/v6/latest/USD'),
        'fallback'  => (float) env('FALLBACK_USD_PHP', 58.50),
        'cache_ttl' => 43200, // 12 hours; the API updates daily
    ],
];
