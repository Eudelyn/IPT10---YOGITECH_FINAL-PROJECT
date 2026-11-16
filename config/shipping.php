<?php
return [
    'free_threshold' => (float) env('FREE_SHIPPING_THRESHOLD', 10000),

    // Flat fee per seller order and delivery window in days, by island group.
    'regions' => [
        'NCR'      => ['label' => 'Metro Manila', 'fee' => 99,  'min_days' => 1, 'max_days' => 3],
        'Luzon'    => ['label' => 'Luzon',        'fee' => 129, 'min_days' => 2, 'max_days' => 5],
        'Visayas'  => ['label' => 'Visayas',      'fee' => 169, 'min_days' => 3, 'max_days' => 7],
        'Mindanao' => ['label' => 'Mindanao',     'fee' => 189, 'min_days' => 4, 'max_days' => 8],
    ],

    'couriers' => ['J&T Express', 'LBC Express', 'Ninja Van', 'Flash Express', 'GoGo Xpress'],
];
