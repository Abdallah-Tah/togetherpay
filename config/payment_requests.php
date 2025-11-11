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

        // V2: Enhanced dunning schedule (days relative to due date)
        'retry1_days_before' => env('DUNNING_RETRY1_DAYS_BEFORE', 2),  // D-2 (friendly reminder)
        'retry2_days_after' => env('DUNNING_RETRY2_DAYS_AFTER', 2),    // D+2 (gentle nudge)
        'final_days_after' => env('DUNNING_FINAL_DAYS_AFTER', 5),      // D+5 (final notice)
    ],

    /*
    |--------------------------------------------------------------------------
    | Grace Period
    |--------------------------------------------------------------------------
    |
    | Number of days after the due date before marking a billing cycle
    | as closed and carrying forward unpaid requests.
    |
    */
    'grace_period_days' => env('CYCLE_GRACE_PERIOD_DAYS', 7),

    /*
    |--------------------------------------------------------------------------
    | Payment Request Statuses (V2)
    |--------------------------------------------------------------------------
    |
    | Valid status values for payment requests state machine
    |
    */
    'statuses' => [
        'pending',         // Created but not yet sent
        'sent',            // Notification sent to member
        'member_marked',   // Member marked as paid
        'owner_confirmed', // Owner confirmed receipt
        'auto_verified',   // Automatically verified (future feature)
        'failed',          // Payment failed
        'expired',         // Request expired without payment
    ],

    /*
    |--------------------------------------------------------------------------
    | Evidence Types (V2)
    |--------------------------------------------------------------------------
    |
    | Valid types of payment evidence members can submit
    |
    */
    'evidence_types' => [
        'photo',
        'screenshot',
        'confirmation_code',
    ],

    /*
    |--------------------------------------------------------------------------
    | Link Shortener (V2)
    |--------------------------------------------------------------------------
    */
    'shortener' => [
        'enabled' => env('LINK_SHORTENER_ENABLED', false),
        'base_url' => env('LINK_SHORTENER_BASE', 'https://tpay.app/p/'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Square Integration Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Square OAuth and payment processing
    |
    */
    'square' => [
        'enabled' => env('SQUARE_ENABLED', false),
        'mode' => env('SQUARE_ENVIRONMENT', env('SQUARE_MODE', 'sandbox')), // 'sandbox' or 'production'
        'access_token' => env('SQUARE_ACCESS_TOKEN'),
        'default_location_id' => env('SQUARE_LOCATION_ID'),

        // OAuth settings
        'oauth' => [
            'client_id' => env('SQUARE_CLIENT_ID', env('SQUARE_APPLICATION_ID')),
            'client_secret' => env('SQUARE_CLIENT_SECRET', env('SQUARE_APPLICATION_SECRET')),
            'redirect_uri' => env('SQUARE_OAUTH_CALLBACK_URL', env('SQUARE_OAUTH_REDIRECT_URI', 'http://localhost:8000/oauth/square/callback')),
            'scopes' => [
                'MERCHANT_PROFILE_READ',  // Required for /v2/me endpoint
                'CUSTOMERS_READ',
                'CUSTOMERS_WRITE',
                'INVOICES_READ',
                'INVOICES_WRITE',
                'ITEMS_READ',             // Required for catalog plans
                'ITEMS_WRITE',            // Required for creating catalog plans
                'ORDERS_WRITE',           // Required for subscription creation
                'PAYMENTS_READ',
                'PAYMENTS_WRITE',
                'SUBSCRIPTIONS_READ',     // For reading subscription details
                'SUBSCRIPTIONS_WRITE',    // For creating subscriptions
            ],
        ],

        // API settings
        'api' => [
            'base_url' => env('SQUARE_API_URL', 'https://connect.squareupsandbox.com'),
            'version' => 'v2',
        ],

        // Default subscription settings
        'subscription' => [
            'interval_unit' => 'MONTH', // MONTH or WEEK
            'interval_count' => 1, // Billing every 1 month
        ],

        // Webhook settings
        'webhook' => [
            'signing_key' => env('SQUARE_WEBHOOK_SIGNING_KEY'),
            'endpoint' => env('SQUARE_WEBHOOK_ENDPOINT', 'https://togetherpay.local/webhooks/square'),
        ],
    ],
];
