<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CashAppService
{
    protected string $apiUrl;

    protected string $clientId;

    protected string $clientSecret;

    public function __construct()
    {
        $this->apiUrl = config('services.cashapp.api_url', 'https://api.cash.app');
        $this->clientId = config('services.cashapp.client_id');
        $this->clientSecret = config('services.cashapp.client_secret');
    }

    /**
     * Create a payment request via Cash App Customer Request API
     */
    public function createPaymentRequest(array $params): ?array
    {
        // Generate idempotency key for safe retries
        $idempotencyKey = $params['idempotency_key'] ?? Str::uuid()->toString();

        $payload = [
            'idempotency_key' => $idempotencyKey,
            'request' => [
                'actions' => [
                    [
                        'amount' => $params['amount'], // Amount in cents (e.g., 2500 = $25.00)
                        'currency' => $params['currency'] ?? 'USD',
                        'scope_id' => $params['scope_id'], // Member's Cash App customer ID
                        'type' => 'ONE_TIME_PAYMENT',
                    ],
                ],
                'channel' => $params['channel'] ?? 'ONLINE',
                'reference_id' => $params['reference_id'], // Your internal payment request ID
                'requester_profile' => [
                    'name' => $params['requester_name'] ?? config('app.name'),
                    'logo_url' => $params['requester_logo_url'] ?? null,
                ],
                'redirect_url' => $params['redirect_url'] ?? route('payment.callback'),
            ],
        ];

        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'User-Agent' => config('app.name').'/1.0',
                'Authorization' => 'Bearer '.$this->getAccessToken(),
            ])->post("{$this->apiUrl}/customer-request/v1/requests", $payload);

            if ($response->successful()) {
                $data = $response->json();
                Log::info('Cash App payment request created', [
                    'request_id' => $data['request']['id'] ?? null,
                    'reference_id' => $params['reference_id'],
                ]);

                return $data['request'] ?? null;
            }

            Log::error('Cash App payment request failed', [
                'status' => $response->status(),
                'body' => $response->body(),
                'reference_id' => $params['reference_id'],
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Cash App API exception', [
                'message' => $e->getMessage(),
                'reference_id' => $params['reference_id'],
            ]);

            return null;
        }
    }

    /**
     * Retrieve a payment request status
     */
    public function retrievePaymentRequest(string $requestId): ?array
    {
        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'User-Agent' => config('app.name').'/1.0',
                'Authorization' => 'Bearer '.$this->getAccessToken(),
            ])->get("{$this->apiUrl}/customer-request/v1/requests/{$requestId}");

            if ($response->successful()) {
                return $response->json('request');
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Cash App retrieve request failed', [
                'message' => $e->getMessage(),
                'request_id' => $requestId,
            ]);

            return null;
        }
    }

    /**
     * Get OAuth access token for Cash App API
     * (You'll need to implement OAuth2 flow or use a stored token)
     */
    protected function getAccessToken(): string
    {
        // TODO: Implement proper OAuth2 token management
        // For now, return the stored token or fetch a new one
        return cache()->remember('cashapp_access_token', 3500, function () {
            return $this->fetchAccessToken();
        });
    }

    /**
     * Fetch a new access token from Cash App OAuth2
     */
    protected function fetchAccessToken(): string
    {
        try {
            $response = Http::asForm()->post("{$this->apiUrl}/oauth/authorize", [
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'grant_type' => 'client_credentials',
                'scope' => 'customer_request',
            ]);

            if ($response->successful()) {
                return $response->json('access_token');
            }

            Log::error('Cash App OAuth token fetch failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new \Exception('Failed to fetch Cash App access token');
        } catch (\Exception $e) {
            Log::error('Cash App OAuth exception', [
                'message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Generate OAuth authorization URL for member onboarding
     */
    public function getAuthorizationUrl(string $redirectUri, string $state): string
    {
        $params = http_build_query([
            'client_id' => $this->clientId,
            'scope' => 'customer_profile payments',
            'redirect_uri' => $redirectUri,
            'state' => $state,
            'response_type' => 'code',
        ]);

        return "{$this->apiUrl}/oauth/authorize?{$params}";
    }

    /**
     * Exchange authorization code for access token and customer ID
     *
     * @return array|null Returns ['access_token' => ..., 'customer_id' => ...]
     */
    public function exchangeCodeForTokens(string $code, string $redirectUri): ?array
    {
        try {
            $response = Http::asForm()->post("{$this->apiUrl}/oauth/token", [
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'code' => $code,
                'grant_type' => 'authorization_code',
                'redirect_uri' => $redirectUri,
            ]);

            if ($response->successful()) {
                $data = $response->json();

                Log::info('Cash App OAuth token exchange successful', [
                    'customer_id' => $data['customer_id'] ?? null,
                ]);

                return [
                    'access_token' => $data['access_token'],
                    'refresh_token' => $data['refresh_token'] ?? null,
                    'customer_id' => $data['customer_id'] ?? null,
                    'expires_in' => $data['expires_in'] ?? null,
                ];
            }

            Log::error('Cash App OAuth token exchange failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Cash App OAuth token exchange exception', [
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Resolve Cash App $cashtag to customer ID
     *
     * @param  string  $cashtag  e.g., "$JohnDoe" or "JohnDoe"
     */
    public function resolveCustomerId(string $cashtag): ?string
    {
        // Ensure cashtag starts with $
        if (! str_starts_with($cashtag, '$')) {
            $cashtag = '$'.$cashtag;
        }

        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'User-Agent' => config('app.name').'/1.0',
                'Authorization' => 'Bearer '.$this->getAccessToken(),
            ])->get("{$this->apiUrl}/v1/customers/cashtag/{$cashtag}");

            if ($response->successful()) {
                $data = $response->json();
                $customerId = $data['customer']['id'] ?? null;

                if ($customerId) {
                    Log::info('Cash App customer ID resolved', [
                        'cashtag' => $cashtag,
                        'customer_id' => $customerId,
                    ]);

                    return $customerId;
                }
            }

            Log::warning('Cash App customer ID resolution failed', [
                'cashtag' => $cashtag,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Cash App customer ID resolution exception', [
                'cashtag' => $cashtag,
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Refresh an expired access token
     */
    public function refreshAccessToken(string $refreshToken): ?array
    {
        try {
            $response = Http::asForm()->post("{$this->apiUrl}/oauth/token", [
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'refresh_token' => $refreshToken,
                'grant_type' => 'refresh_token',
            ]);

            if ($response->successful()) {
                $data = $response->json();

                Log::info('Cash App token refreshed successfully');

                return [
                    'access_token' => $data['access_token'],
                    'refresh_token' => $data['refresh_token'] ?? null,
                    'expires_in' => $data['expires_in'] ?? null,
                ];
            }

            Log::error('Cash App token refresh failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Cash App token refresh exception', [
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
