<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Payment Request Driver
    |--------------------------------------------------------------------------
    |
    | This value determines which payment request driver will be used by default.
    | Options: 'link' for deep-link based requests (MVP), 'api' for future API integration
    |
    */

    'default' => env('PR_DRIVER', 'link'),

    /*
    |--------------------------------------------------------------------------
    | Payment Request Drivers
    |--------------------------------------------------------------------------
    |
    | Configuration for each payment channel driver
    |
    */

    'drivers' => [
        'venmo' => [
            'mode' => env('VENMO_MODE', 'link'), // 'link' or 'api'
            'api_key' => env('VENMO_API_KEY'),
            'api_url' => env('VENMO_API_URL'),
        ],

        'cash_app' => [
            'mode' => env('CASH_APP_MODE', 'link'), // 'link' or 'api'
            'api_key' => env('CASH_APP_API_KEY'),
            'api_url' => env('CASH_APP_API_URL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Feature Flags
    |--------------------------------------------------------------------------
    */

    'features' => [
        'payment_api' => env('FF_PAYMENT_API', false),
        'sms' => env('FF_SMS', false),
        'pdf_exports' => env('FF_PDF_EXPORTS', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Dunning Configuration
    |--------------------------------------------------------------------------
    |
    | Configure reminder and retry cadence for pending payment requests
    |
    */

    'dunning' => [
        'reminders' => [
            ['days' => 0, 'type' => 'initial'],
            ['days' => 3, 'type' => 'reminder'],
            ['days' => 7, 'type' => 'reminder'],
            ['days' => 10, 'type' => 'escalation'],
        ],
    ],
];
