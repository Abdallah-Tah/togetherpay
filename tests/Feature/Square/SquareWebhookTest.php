<?php

namespace Tests\Feature\Square;

use App\Models\BillingCycle;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\PaymentRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SquareWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function getWebhookSignature(string $body, string $timestamp): string
    {
        $signingKey = config('payment_requests.square.webhook.signing_key', 'test-key');
        $url = config('payment_requests.square.webhook.endpoint');
        $signedContent = $url.$body.$timestamp;
        $hash = hash_hmac('sha256', $signedContent, $signingKey, true);

        return base64_encode($hash);
    }

    public function test_webhook_rejects_invalid_signature()
    {
        $payload = json_encode([
            'type' => 'invoice.payment_made',
            'data' => ['object' => ['invoice' => ['id' => 'inv_123']]],
        ]);

        $timestamp = (string) time();

        $response = $this->postJson(route('webhooks.square'), json_decode($payload, true), [
            'X-Square-Signature' => 'invalid-signature',
            'X-Square-Hmacsha256-Timestamp' => $timestamp,
        ]);

        $response->assertStatus(401);
        $response->assertJson(['error' => 'Invalid signature']);
    }

    public function test_webhook_accepts_valid_signature()
    {
        config(['payment_requests.square.webhook.signing_key' => 'test-key']);

        $payload = json_encode([
            'type' => 'invoice.payment_made',
            'data' => ['object' => ['invoice' => ['id' => 'inv_nonexistent']]],
        ]);

        $timestamp = (string) time();
        $signature = $this->getWebhookSignature($payload, $timestamp);

        $response = $this->postJson(route('webhooks.square'), json_decode($payload, true), [
            'X-Square-Signature' => $signature,
            'X-Square-Hmacsha256-Timestamp' => $timestamp,
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);
    }

    public function test_invoice_payment_made_confirms_payment_request()
    {
        // Create test data
        $owner = User::factory()->create();
        $group = Group::factory()->create(['owner_id' => $owner->id]);
        $cycle = BillingCycle::factory()->create(['group_id' => $group->id]);
        $member = GroupMember::factory()->create([
            'group_id' => $group->id,
            'name' => 'Test Member',
        ]);

        $paymentRequest = PaymentRequest::create([
            'group_id' => $group->id,
            'cycle_id' => $cycle->id,
            'member_id' => $member->id,
            'amount_cents' => 5000,
            'status' => 'sent',
            'method_hint' => 'CASH_APP',
            'square_invoice_id' => 'inv_123',
        ]);

        // Create webhook payload
        $payload = [
            'type' => 'invoice.payment_made',
            'data' => [
                'object' => [
                    'invoice' => [
                        'id' => 'inv_123',
                        'payment_requests' => [[
                            'computed_amount_money' => ['amount' => 5000],
                        ]],
                    ],
                ],
            ],
        ];

        // Send webhook (skip signature check in testing)
        $response = $this->postJson(route('square.webhook'), $payload);

        $response->assertOk();
        $response->assertJson(['success' => true]);

        // Assert payment request was confirmed
        $paymentRequest->refresh();
        $this->assertEquals('auto_verified', $paymentRequest->status);
        $this->assertNotNull($paymentRequest->square_confirmed_at);
        $this->assertNotNull($paymentRequest->square_payment_id);
    }

    public function test_subscription_charge_succeeded_confirms_payment_request()
    {
        // Create test data
        $owner = User::factory()->create();
        $group = Group::factory()->create(['owner_id' => $owner->id]);
        $cycle = BillingCycle::factory()->create(['group_id' => $group->id]);
        $member = GroupMember::factory()->create([
            'group_id' => $group->id,
            'name' => 'Test Member',
        ]);

        $paymentRequest = PaymentRequest::create([
            'group_id' => $group->id,
            'cycle_id' => $cycle->id,
            'member_id' => $member->id,
            'amount_cents' => 5000,
            'status' => 'sent',
            'method_hint' => 'CASH_APP',
            'square_subscription_id' => 'sub_123',
        ]);

        // Create webhook payload
        $payload = [
            'type' => 'subscription.charge.succeeded',
            'data' => [
                'object' => [
                    'subscription' => [
                        'id' => 'sub_123',
                        'customer_id' => 'cust_123',
                    ],
                ],
            ],
        ];

        // Send webhook
        $response = $this->postJson(route('square.webhook'), $payload);

        $response->assertOk();

        // Assert payment request was confirmed
        $paymentRequest->refresh();
        $this->assertEquals('auto_verified', $paymentRequest->status);
        $this->assertNotNull($paymentRequest->square_confirmed_at);
    }

    public function test_subscription_charge_failed_marks_payment_as_failed()
    {
        $owner = User::factory()->create();
        $group = Group::factory()->create(['owner_id' => $owner->id]);
        $cycle = BillingCycle::factory()->create(['group_id' => $group->id]);
        $member = GroupMember::factory()->create([
            'group_id' => $group->id,
            'name' => 'Test Member',
        ]);

        $paymentRequest = PaymentRequest::create([
            'group_id' => $group->id,
            'cycle_id' => $cycle->id,
            'member_id' => $member->id,
            'amount_cents' => 5000,
            'status' => 'sent',
            'method_hint' => 'CASH_APP',
            'square_subscription_id' => 'sub_123',
        ]);

        $payload = [
            'type' => 'subscription.charge.failed',
            'data' => [
                'object' => [
                    'subscription' => [
                        'id' => 'sub_123',
                        'customer_id' => 'cust_123',
                    ],
                    'error_code' => 'PAYMENT_METHOD_DECLINED',
                ],
            ],
        ];

        $response = $this->postJson(route('square.webhook'), $payload);

        $response->assertOk();

        $paymentRequest->refresh();
        $this->assertEquals('failed', $paymentRequest->status);
    }

    public function test_webhook_handles_unknown_event_types_gracefully()
    {
        $payload = [
            'type' => 'unknown.event.type',
            'data' => ['object' => ['id' => '123']],
        ];

        $response = $this->postJson(route('square.webhook'), $payload);

        $response->assertOk();
        $response->assertJson(['success' => true]);
    }

    public function test_webhook_returns_200_on_error_to_prevent_retries()
    {
        // Create invalid payload that will cause an error
        $payload = [
            'type' => 'invoice.payment_made',
            'data' => [], // Missing required fields
        ];

        $response = $this->postJson(route('square.webhook'), $payload);

        // Should return 200 even on error to prevent Square from retrying
        $response->assertOk();
        $response->assertJson(['success' => false]);
    }
}
