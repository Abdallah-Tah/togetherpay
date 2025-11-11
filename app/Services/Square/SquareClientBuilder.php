<?php

namespace App\Services\Square;

use Square\SquareClient;

/**
 * Factory for creating and configuring Square API clients
 * Using Square SDK v43+ (modern API)
 */
class SquareClientBuilder
{
    public static function build(): SquareClient
    {
        $config = config('payment_requests.square');
        $environment = $config['mode'] === 'production' ? 'production' : 'sandbox';

        $options = [
            'environment' => $environment,
        ];

        // Modern SquareClient constructor: __construct(?string $token, ?string $version, ?array $options)
        return new SquareClient(
            self::getAccessToken(),
            '2025-01-23',  // API version
            $options
        );
    }

    public static function buildForUser($user): SquareClient
    {
        $config = config('payment_requests.square');
        $environment = $config['mode'] === 'production' ? 'production' : 'sandbox';

        // Decrypt the access token
        try {
            $accessToken = decrypt($user->square_access_token);

            // Log for debugging (remove in production)
            logger()->info('SquareClientBuilder::buildForUser', [
                'user_id' => $user->id,
                'encrypted_length' => strlen($user->square_access_token),
                'decrypted_length' => strlen($accessToken),
                'environment' => $environment,
                'token_preview' => substr($accessToken, 0, 20) . '...',
                'token_last_4' => substr($accessToken, -4),
                'has_whitespace' => $accessToken !== trim($accessToken),
            ]);

            // Trim whitespace just in case
            $accessToken = trim($accessToken);
        } catch (\Exception $e) {
            logger()->error('Failed to decrypt Square access token', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            throw new \Exception('Failed to decrypt Square access token: ' . $e->getMessage());
        }

        $options = [
            'environment' => $environment,
        ];

        // Modern SquareClient constructor
        return new SquareClient(
            $accessToken,
            '2025-01-23',  // API version
            $options
        );
    }

    private static function getAccessToken(): ?string
    {
        return config('payment_requests.square.access_token');
    }
}
