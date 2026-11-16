<?php
return [
    // sandbox  = built-in GCash / card simulator (no account needed, works offline) — default
    // paymongo = PayMongo test mode (real hosted checkout API; needs a PayMongo account and sk_test_ key)
    'gateway' => env('PAYMENT_GATEWAY', 'sandbox'),

    'sandbox' => [
        // Fake GCash wallet balance, so "insufficient balance" can be demonstrated.
        'gcash_balance' => (float) env('SANDBOX_GCASH_BALANCE', 150000),
    ],

    'paymongo' => [
        'secret_key' => env('PAYMONGO_SECRET_KEY', ''),
        'base_url'   => rtrim((string) env('PAYMONGO_API_URL', 'https://api.paymongo.com/v2'), '/'),
    ],
];
