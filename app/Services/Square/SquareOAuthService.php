<?php

namespace App\Services\Square;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Handles Square OAuth flow for connecting accounts
 */
class SquareOAuthService
{
    public function getAuthorizationUrl(?string $state = null): string
    {
        $state = $state ?? Str::random(40);
        $config = config('payment_requests.square');
        $oauthConfig = $config['oauth'];
        $mode = $config['mode'] ?? 'sandbox';

        $params = [
            'client_id' => $oauthConfig['client_id'],
            'response_type' => 'code',
            'scope' => implode(' ', $oauthConfig['scopes']),
            'redirect_uri' => $oauthConfig['redirect_uri'],
            'state' => $state,
        ];

        // Use sandbox URL for sandbox mode, production URL otherwise
        $baseUrl = $mode === 'production'
            ? 'https://connect.squareup.com/oauth2/authorize'
            : 'https://connect.squareupsandbox.com/oauth2/authorize';

        $authUrl = $baseUrl.'?'.http_build_query($params);

        Log::info('Square OAuth: Generating authorization URL', [
            'mode' => $mode,
            'base_url' => $baseUrl,
            'client_id' => $oauthConfig['client_id'],
            'redirect_uri' => $oauthConfig['redirect_uri'],
            'scopes' => $oauthConfig['scopes'],
            'state' => $state,
        ]);

        return $authUrl;
    }

    /**
     * Exchange authorization code for access token
     */
    public function exchangeCodeForToken(string $code): array
    {
        $config = config('payment_requests.square');
        $oauthConfig = $config['oauth'];
        $mode = $config['mode'] ?? 'sandbox';

        $tokenUrl = $mode === 'production'
            ? 'https://connect.squareup.com/oauth2/token'
            : 'https://connect.squareupsandbox.com/oauth2/token';

        $payload = [
            'client_id' => $oauthConfig['client_id'],
            'client_secret' => $oauthConfig['client_secret'],
            'code' => $code,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $oauthConfig['redirect_uri'],
        ];

        Log::info('Square OAuth: Exchanging authorization code for token', [
            'mode' => $mode,
            'token_url' => $tokenUrl,
            'client_id' => $oauthConfig['client_id'],
            'code' => substr($code, 0, 10).'...', // Log first 10 chars for security
            'redirect_uri' => $oauthConfig['redirect_uri'],
        ]);

        $response = Http::post($tokenUrl, $payload);

        if ($response->failed()) {
            Log::error('Square OAuth: Token exchange failed', [
                'status' => $response->status(),
                'response_body' => $response->body(),
                'request_payload' => [
                    'client_id' => $oauthConfig['client_id'],
                    'code' => substr($code, 0, 10).'...',
                    'redirect_uri' => $oauthConfig['redirect_uri'],
                ],
            ]);
            throw new \Exception('Failed to exchange code for token: '.$response->body());
        }

        $responseData = $response->json();
        Log::info('Square OAuth: Token exchange successful', [
            'merchant_id' => $responseData['merchant_id'] ?? null,
            'access_token' => substr($responseData['access_token'] ?? '', 0, 10).'...', // Log first 10 chars
            'token_type' => $responseData['token_type'] ?? null,
            'expires_in' => $responseData['expires_in'] ?? null,
            'refresh_token' => isset($responseData['refresh_token']) ? substr($responseData['refresh_token'], 0, 10).'...' : null,
        ]);

        return $responseData;
    }

    /**
     * Refresh an access token
     */
    public function refreshAccessToken(string $refreshToken): array
    {
        $config = config('payment_requests.square');
        $oauthConfig = $config['oauth'];
        $mode = $config['mode'] ?? 'sandbox';

        $tokenUrl = $mode === 'production'
            ? 'https://connect.squareup.com/oauth2/token'
            : 'https://connect.squareupsandbox.com/oauth2/token';

        $payload = [
            'client_id' => $oauthConfig['client_id'],
            'client_secret' => $oauthConfig['client_secret'],
            'refresh_token' => $refreshToken,
            'grant_type' => 'refresh_token',
        ];

        Log::info('Square OAuth: Refreshing access token', [
            'mode' => $mode,
            'token_url' => $tokenUrl,
            'client_id' => $oauthConfig['client_id'],
            'refresh_token' => substr($refreshToken, 0, 10).'...', // Log first 10 chars for security
        ]);

        $response = Http::post($tokenUrl, $payload);

        if ($response->failed()) {
            Log::error('Square OAuth: Token refresh failed', [
                'status' => $response->status(),
                'response_body' => $response->body(),
            ]);
            throw new \Exception('Failed to refresh token: '.$response->body());
        }

        $responseData = $response->json();
        Log::info('Square OAuth: Token refresh successful', [
            'access_token' => substr($responseData['access_token'] ?? '', 0, 10).'...',
            'token_type' => $responseData['token_type'] ?? null,
            'expires_in' => $responseData['expires_in'] ?? null,
        ]);

        return $responseData;
    }

    /**
     * Revoke an access token
     */
    public function revokeAccessToken(string $accessToken): bool
    {
        $config = config('payment_requests.square');
        $oauthConfig = $config['oauth'];
        $mode = $config['mode'] ?? 'sandbox';

        $revokeUrl = $mode === 'production'
            ? 'https://connect.squareup.com/oauth2/revoke'
            : 'https://connect.squareupsandbox.com/oauth2/revoke';

        $payload = [
            'client_id' => $oauthConfig['client_id'],
            'access_token' => $accessToken,
        ];

        Log::info('Square OAuth: Revoking access token', [
            'mode' => $mode,
            'revoke_url' => $revokeUrl,
            'client_id' => $oauthConfig['client_id'],
            'access_token' => substr($accessToken, 0, 10).'...', // Log first 10 chars for security
        ]);

        $response = Http::post($revokeUrl, $payload);

        if ($response->successful()) {
            Log::info('Square OAuth: Token revocation successful', [
                'status' => $response->status(),
            ]);
        } else {
            Log::warning('Square OAuth: Token revocation may have failed', [
                'status' => $response->status(),
                'response_body' => $response->body(),
            ]);
        }

        return $response->successful();
    }

    /**
     * Get merchant details from access token
     * Uses the Square Merchants API to fetch merchant information
     */
    public function getMerchantInfo(string $accessToken): array
    {
        $config = config('payment_requests.square');
        $mode = $config['mode'] ?? 'sandbox';
        $apiBaseUrl = $mode === 'production'
            ? 'https://connect.squareup.com/v2'
            : 'https://connect.squareupsandbox.com/v2';

        // First, try the /me endpoint (requires MERCHANT_PROFILE_READ scope)
        $meUrl = "{$apiBaseUrl}/me";

        Log::info('Square OAuth: Fetching merchant info via /me endpoint', [
            'mode' => $mode,
            'endpoint' => $meUrl,
            'access_token' => substr($accessToken, 0, 10).'...', // Log first 10 chars for security
        ]);

        $response = Http::withToken($accessToken)->get($meUrl);

        if ($response->successful()) {
            $merchantData = $response->json();
            Log::info('Square OAuth: Merchant info fetched successfully', [
                'merchant_id' => $merchantData['merchant']['id'] ?? null,
                'merchant_name' => $merchantData['merchant']['business_name'] ?? null,
                'location_count' => count($merchantData['merchant']['locations'] ?? []),
                'locations' => array_map(fn ($loc) => [
                    'id' => $loc['id'] ?? null,
                    'name' => $loc['name'] ?? null,
                ], $merchantData['merchant']['locations'] ?? []),
            ]);

            return $merchantData;
        }

        Log::warning('Square OAuth: /me endpoint failed, attempting fallback', [
            'status' => $response->status(),
            'response_body' => $response->body(),
        ]);

        // Fallback: Try the legacy /merchants endpoint
        $merchantsUrl = "{$apiBaseUrl}/merchants";

        Log::info('Square OAuth: Fetching merchant list via /merchants endpoint', [
            'endpoint' => $merchantsUrl,
        ]);

        $response = Http::withToken($accessToken)->get($merchantsUrl);

        if ($response->successful()) {
            $merchantsData = $response->json();
            Log::info('Square OAuth: Merchant list fetched via fallback endpoint', [
                'merchant_count' => count($merchantsData['merchants'] ?? []),
            ]);

            return $merchantsData;
        }

        Log::error('Square OAuth: All merchant info endpoints failed', [
            'me_endpoint_status' => $response->status(),
            'merchants_endpoint_status' => $response->status(),
            'response_body' => $response->body(),
        ]);

        // If both endpoints fail, return an empty structure with a warning
        // This allows OAuth to continue without merchant info
        Log::warning('Square OAuth: Unable to fetch merchant info, continuing without it', [
            'access_token' => substr($accessToken, 0, 10).'...',
        ]);

        return [
            'merchant' => [
                'id' => null,
                'business_name' => 'Unknown',
                'locations' => [],
            ],
        ];
    }
}
