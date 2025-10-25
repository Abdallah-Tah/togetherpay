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
     *
     * @param array $params
     * @return array|null
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
                    ]
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
                'User-Agent' => config('app.name') . '/1.0',
                'Authorization' => 'Bearer ' . $this->getAccessToken(),
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
     *
     * @param string $requestId
     * @return array|null
     */
    public function retrievePaymentRequest(string $requestId): ?array
    {
        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'User-Agent' => config('app.name') . '/1.0',
                'Authorization' => 'Bearer ' . $this->getAccessToken(),
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
     *
     * @return string
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
     *
     * @return string
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
}
