<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'cashapp' => [
        'client_id' => env('CASHAPP_CLIENT_ID'),
        'client_secret' => env('CASHAPP_CLIENT_SECRET'),
        'api_url' => env('CASHAPP_API_URL', 'https://api.cash.app'),
        'webhook_secret' => env('CASHAPP_WEBHOOK_SECRET'),
    ],

    'venmo' => [
        'client_id' => env('VENMO_CLIENT_ID'),
        'client_secret' => env('VENMO_CLIENT_SECRET'),
        'api_url' => env('VENMO_API_URL', 'https://api.venmo.com'),
    ],

];
