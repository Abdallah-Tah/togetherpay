<?php

namespace App\Services\Square;

use Square\Client;

/**
 * Factory for creating and configuring Square API clients
 */
class SquareClientBuilder
{
    public static function build(): Client
    {
        $config = config('payment_requests.square');

        $client = new Client();
        $client->setAccessToken(self::getAccessToken());
        $client->setEnvironment($config['mode'] === 'production' ? 'production' : 'sandbox');

        return $client;
    }

    public static function buildForUser($user): Client
    {
        $client = new Client();
        $client->setAccessToken($user->square_access_token);

        $config = config('payment_requests.square');
        $client->setEnvironment($config['mode'] === 'production' ? 'production' : 'sandbox');

        return $client;
    }

    private static function getAccessToken(): ?string
    {
        return config('payment_requests.square.oauth.client_secret');
    }
}
