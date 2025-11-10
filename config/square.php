<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Square Application ID
    |--------------------------------------------------------------------------
    |
    | Your Square application ID from the Square Developer Dashboard.
    | https://developer.squareup.com/apps
    |
    */

    'application_id' => env('SQUARE_APPLICATION_ID'),

    /*
    |--------------------------------------------------------------------------
    | Square Access Token
    |--------------------------------------------------------------------------
    |
    | Your Square access token. For production, this is obtained via OAuth.
    | For sandbox testing, use your sandbox access token.
    |
    */

    'access_token' => env('SQUARE_ACCESS_TOKEN'),

    /*
    |--------------------------------------------------------------------------
    | Square Environment
    |--------------------------------------------------------------------------
    |
    | The Square environment to use: 'sandbox' or 'production'
    |
    */

    'environment' => env('SQUARE_ENVIRONMENT', 'sandbox'),

    /*
    |--------------------------------------------------------------------------
    | Square Location ID
    |--------------------------------------------------------------------------
    |
    | Default location ID for your Square account.
    |
    */

    'location_id' => env('SQUARE_LOCATION_ID'),

    /*
    |--------------------------------------------------------------------------
    | Square OAuth Settings
    |--------------------------------------------------------------------------
    |
    | OAuth redirect URI and scopes for Square OAuth flow.
    |
    */

    'oauth' => [
        'redirect_uri' => env('SQUARE_OAUTH_REDIRECT_URI', env('APP_URL').'/square/callback'),
        'scopes' => [
            'MERCHANT_PROFILE_READ',
            'PAYMENTS_READ',
            'PAYMENTS_WRITE',
            'CUSTOMERS_READ',
            'CUSTOMERS_WRITE',
            'SUBSCRIPTIONS_READ',
            'SUBSCRIPTIONS_WRITE',
            'INVOICES_READ',
            'INVOICES_WRITE',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhook Signature Key
    |--------------------------------------------------------------------------
    |
    | The signature key for verifying Square webhooks.
    |
    */

    'webhook_signature_key' => env('SQUARE_WEBHOOK_SIGNATURE_KEY'),

];
