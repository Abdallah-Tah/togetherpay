<?php

namespace App\Services\Square;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Direct HTTP client for Square REST API
 * Bypasses SDK dependency, uses REST API directly
 */
class SquareApiClient
{
    private string $accessToken;
    private string $baseUrl;
    private string $version = '2025-01-23';

    public function __construct(User $user)
    {
        $config = config('payment_requests.square');
        $mode = $config['mode'] ?? 'sandbox';

        // Decrypt the access token
        try {
            $this->accessToken = decrypt($user->square_access_token);
        } catch (\Exception $e) {
            Log::error('Failed to decrypt Square token', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            throw new \Exception('Failed to decrypt Square access token');
        }

        // Set base URL based on mode
        $this->baseUrl = $mode === 'production'
            ? 'https://connect.squareup.com/v2'
            : 'https://connect.squareupsandbox.com/v2';
    }

    /**
     * Create a customer via REST API
     */
    public function createCustomer(array $data): string
    {
        $payload = [
            'idempotency_key' => $data['idempotency_key'] ?? uniqid('cust_'),
            'given_name' => $data['given_name'] ?? '',
            'email_address' => $data['email_address'] ?? null,
            'phone_number' => $data['phone_number'] ?? null,
            'reference_id' => $data['reference_id'] ?? null,
        ];

        // Remove null values
        $payload = array_filter($payload, fn($v) => $v !== null);

        Log::info('Square API: Creating customer', [
            'email' => $payload['email_address'] ?? 'N/A',
            'phone' => $payload['phone_number'] ?? 'N/A',
        ]);

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->accessToken,
                'Square-Version' => $this->version,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/customers', $payload);

            if ($response->failed()) {
                Log::error('Square API: Create customer failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                throw new \Exception('Failed to create customer: ' . $response->body());
            }

            $customerId = $response->json('customer.id');

            Log::info('Square API: Customer created successfully', [
                'customer_id' => $customerId,
            ]);

            return $customerId;
        } catch (\Exception $e) {
            Log::error('Square API: Customer creation error', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Create a subscription via REST API
     */
    public function createSubscription(array $data): string
    {
        $payload = [
            'idempotency_key' => $data['idempotency_key'] ?? uniqid('sub_'),
            'location_id' => $data['location_id'],
            'customer_id' => $data['customer_id'],
            'start_date' => $data['start_date'], // ISO 8601 date format
            'price_override_money' => [
                'amount' => $data['amount_cents'],
                'currency' => 'USD',
            ],
            'billing_anchor_date' => $data['start_date'],
            'card_id' => $data['card_id'] ?? null,
        ];

        // Add phases for monthly recurring
        $payload['phases'] = [
            [
                'ordinal' => 1,
                'recurring_price_money' => [
                    'amount' => $data['amount_cents'],
                    'currency' => 'USD',
                ],
                'recurrence_type' => 'INFINITE',
                'recurrence_details' => [
                    'interval' => 'MONTHLY',
                    'interval_count' => 1,
                ],
            ],
        ];

        Log::info('Square API: Creating subscription', [
            'customer_id' => $data['customer_id'],
            'location_id' => $data['location_id'],
            'amount_cents' => $data['amount_cents'],
            'start_date' => $data['start_date'],
        ]);

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->accessToken,
                'Square-Version' => $this->version,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/subscriptions', $payload);

            if ($response->failed()) {
                Log::error('Square API: Create subscription failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                throw new \Exception('Failed to create subscription: ' . $response->body());
            }

            $subscriptionId = $response->json('subscription.id');

            Log::info('Square API: Subscription created successfully', [
                'subscription_id' => $subscriptionId,
            ]);

            return $subscriptionId;
        } catch (\Exception $e) {
            Log::error('Square API: Subscription creation error', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get subscription details
     */
    public function getSubscription(string $subscriptionId): array
    {
        Log::info('Square API: Fetching subscription', [
            'subscription_id' => $subscriptionId,
        ]);

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->accessToken,
                'Square-Version' => $this->version,
            ])->get($this->baseUrl . '/subscriptions/' . $subscriptionId);

            if ($response->failed()) {
                Log::error('Square API: Get subscription failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                throw new \Exception('Failed to get subscription');
            }

            return $response->json('subscription');
        } catch (\Exception $e) {
            Log::error('Square API: Get subscription error', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Cancel a subscription
     */
    public function cancelSubscription(string $subscriptionId): void
    {
        Log::info('Square API: Canceling subscription', [
            'subscription_id' => $subscriptionId,
        ]);

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->accessToken,
                'Square-Version' => $this->version,
            ])->post($this->baseUrl . '/subscriptions/' . $subscriptionId . '/cancel');

            if ($response->failed()) {
                Log::error('Square API: Cancel subscription failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                throw new \Exception('Failed to cancel subscription');
            }

            Log::info('Square API: Subscription canceled successfully', [
                'subscription_id' => $subscriptionId,
            ]);
        } catch (\Exception $e) {
            Log::error('Square API: Cancel subscription error', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get customer details
     */
    public function getCustomer(string $customerId): array
    {
        Log::info('Square API: Fetching customer', [
            'customer_id' => $customerId,
        ]);

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->accessToken,
                'Square-Version' => $this->version,
            ])->get($this->baseUrl . '/customers/' . $customerId);

            if ($response->failed()) {
                Log::error('Square API: Get customer failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                throw new \Exception('Failed to get customer');
            }

            return $response->json('customer');
        } catch (\Exception $e) {
            Log::error('Square API: Get customer error', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Create an order via REST API
     */
    public function createOrder(array $data): string
    {
        Log::info('Square API: Creating order', [
            'location_id' => $data['order']['location_id'] ?? null,
            'line_items_count' => count($data['order']['line_items'] ?? []),
        ]);

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->accessToken,
                'Square-Version' => $this->version,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/orders', $data);

            if ($response->failed()) {
                Log::error('Square API: Create order failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                throw new \Exception('Failed to create order: ' . $response->body());
            }

            $orderId = $response->json('order.id');

            Log::info('Square API: Order created successfully', [
                'order_id' => $orderId,
            ]);

            return $orderId;
        } catch (\Exception $e) {
            Log::error('Square API: Order creation error', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Create an invoice via REST API
     */
    public function createInvoice(array $data): array
    {
        Log::info('Square API: Creating invoice', [
            'location_id' => $data['invoice']['location_id'] ?? null,
            'customer_id' => $data['invoice']['primary_recipient']['customer_id'] ?? null,
        ]);

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->accessToken,
                'Square-Version' => $this->version,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/invoices', $data);

            if ($response->failed()) {
                Log::error('Square API: Create invoice failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                throw new \Exception('Failed to create invoice: ' . $response->body());
            }

            $invoice = $response->json('invoice');

            Log::info('Square API: Invoice created successfully', [
                'invoice_id' => $invoice['id'] ?? null,
            ]);

            return $invoice;
        } catch (\Exception $e) {
            Log::error('Square API: Invoice creation error', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Publish an invoice to make it live
     */
    public function publishInvoice(string $invoiceId, int $version): array
    {
        Log::info('Square API: Publishing invoice', [
            'invoice_id' => $invoiceId,
            'version' => $version,
        ]);

        try {
            $payload = [
                'version' => $version,
            ];

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->accessToken,
                'Square-Version' => $this->version,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/invoices/' . $invoiceId . '/publish', $payload);

            if ($response->failed()) {
                Log::error('Square API: Publish invoice failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                throw new \Exception('Failed to publish invoice: ' . $response->body());
            }

            $invoice = $response->json('invoice');

            Log::info('Square API: Invoice published successfully', [
                'invoice_id' => $invoice['id'] ?? null,
                'public_url' => $invoice['public_url'] ?? null,
            ]);

            return $invoice;
        } catch (\Exception $e) {
            Log::error('Square API: Invoice publish error', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get invoice details
     */
    public function getInvoice(string $invoiceId): array
    {
        Log::info('Square API: Fetching invoice', [
            'invoice_id' => $invoiceId,
        ]);

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->accessToken,
                'Square-Version' => $this->version,
            ])->get($this->baseUrl . '/invoices/' . $invoiceId);

            if ($response->failed()) {
                Log::error('Square API: Get invoice failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                throw new \Exception('Failed to get invoice');
            }

            return $response->json('invoice');
        } catch (\Exception $e) {
            Log::error('Square API: Get invoice error', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
