<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Identity verification
    |--------------------------------------------------------------------------
    */
    'identity' => [
        // The legacy application treats users.is_active = 0 as login-enabled.
        'active_user_value' => 0,
        'normalization' => [
            'trim_surrounding_whitespace' => true,
            'preserve_case' => true,
            'preserve_leading_zero' => true,
            'accepted_terminators' => ['ENTER', 'TAB'],
        ],
    ],

    'transaction' => [
        'require_storage_location_for_in' => true,
        'duplicate_submission_ttl_seconds' => 30,
    ],

    'storage_locations' => [
        'DS8',
        'Deltamas',
    ],

    'scanner' => [
        'duplicate_scan_window_ms' => 1500,
        'auto_focus' => true,
    ],

    'dashboard' => [
        'default_period_days' => 30,
        'low_stock_inclusive' => true,
    ],

    'authorization' => [
        'administrator_role_ids' => [1],
        'authorized_department_names' => [
            'Logistic & Warehouse',
            'Production',
            'PDCA, Inventory, Procurement & IT',
        ],
    ],

    'export' => [
        'max_rows' => 10000,
    ],

    'rate_limits' => [
        'scan_per_minute' => 120,
        'mutation_per_minute' => 30,
    ],
];
