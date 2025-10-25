<?php

namespace App\Jobs;

use App\Models\PaymentRequest;
use App\Services\CashAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendCashAppPaymentRequestJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public PaymentRequest $paymentRequest
    ) {}

    public function handle(CashAppService $cashAppService): void
    {
        // Only send if payment method is CASH_APP and member has cash_app_customer_id
        if ($this->paymentRequest->payment_method !== 'CASH_APP') {
            Log::info('Skipping Cash App request - payment method not CASH_APP', [
                'payment_request_id' => $this->paymentRequest->id,
                'payment_method' => $this->paymentRequest->payment_method,
            ]);
            return;
        }

        $member = $this->paymentRequest->member;
        if (!$member->cash_app_customer_id) {
            Log::warning('Member missing Cash App customer ID', [
                'member_id' => $member->id,
                'payment_request_id' => $this->paymentRequest->id,
            ]);

            // Mark as failed or fall back to email notification
            $this->paymentRequest->update([
                'metadata_json->cashapp_error' => 'Missing Cash App customer ID',
            ]);
            return;
        }

        $group = $this->paymentRequest->billingCycle->group;
        $owner = $group->owner;

        // Create payment request via Cash App API
        $result = $cashAppService->createPaymentRequest([
            'amount' => (int) ($this->paymentRequest->amount_cents), // Already in cents
            'currency' => $group->currency,
            'scope_id' => $member->cash_app_customer_id, // Member's Cash App ID
            'reference_id' => "PR_{$this->paymentRequest->id}", // Your internal ID
            'requester_name' => $owner->name ?? config('app.name'),
            'requester_logo_url' => $owner->avatar_url ?? null,
            'redirect_url' => route('payment.callback', ['payment_request' => $this->paymentRequest->id]),
            'idempotency_key' => "PR_{$this->paymentRequest->id}_" . now()->timestamp,
        ]);

        if ($result) {
            // Store Cash App request details
            $this->paymentRequest->update([
                'metadata_json->cashapp_request_id' => $result['id'],
                'metadata_json->cashapp_status' => $result['status'],
                'metadata_json->cashapp_mobile_url' => $result['auth_flow_triggers']['mobile_url'] ?? null,
                'metadata_json->cashapp_desktop_url' => $result['auth_flow_triggers']['desktop_url'] ?? null,
                'metadata_json->cashapp_qr_code_url' => $result['auth_flow_triggers']['qr_code_image_url'] ?? null,
                'metadata_json->cashapp_expires_at' => $result['expires_at'] ?? null,
            ]);

            Log::info('Cash App payment request created successfully', [
                'payment_request_id' => $this->paymentRequest->id,
                'cashapp_request_id' => $result['id'],
                'member_id' => $member->id,
            ]);
        } else {
            Log::error('Failed to create Cash App payment request', [
                'payment_request_id' => $this->paymentRequest->id,
                'member_id' => $member->id,
            ]);

            // Update metadata with error
            $this->paymentRequest->update([
                'metadata_json->cashapp_error' => 'Failed to create Cash App request',
            ]);
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('SendCashAppPaymentRequestJob failed', [
            'payment_request_id' => $this->paymentRequest->id,
            'exception' => $exception->getMessage(),
        ]);

        $this->paymentRequest->update([
            'metadata_json->cashapp_error' => $exception->getMessage(),
        ]);
    }
}
