<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Square Environment
    |--------------------------------------------------------------------------
    */
    'environment' => env('SQUARE_ENVIRONMENT', 'sandbox'), // 'sandbox' or 'production'

    /*
    |--------------------------------------------------------------------------
    | Square Application Credentials
    |--------------------------------------------------------------------------
    */
    'application_id' => env('SQUARE_APPLICATION_ID'),
    'access_token' => env('SQUARE_ACCESS_TOKEN'),
    'location_id' => env('SQUARE_LOCATION_ID'),

    /*
    |--------------------------------------------------------------------------
    | Square OAuth Configuration
    |--------------------------------------------------------------------------
    */
    'oauth' => [
        'redirect_uri' => env('SQUARE_OAUTH_REDIRECT_URI', env('APP_URL').'/square/callback'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Square Webhook Configuration
    |--------------------------------------------------------------------------
    */
    'webhook' => [
        'signature_key' => env('SQUARE_WEBHOOK_SIGNATURE_KEY'),
    ],
];
