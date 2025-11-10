<?php

namespace App\Http\Controllers;

use App\Models\GroupMember;
use App\Models\PaymentRequest;
use App\Services\SquareService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SquareWebhookController extends Controller
{
    protected SquareService $squareService;

    public function __construct()
    {
        $this->squareService = new SquareService;
    }

    /**
     * Handle incoming Square webhooks.
     */
    public function handle(Request $request): JsonResponse
    {
        // Verify webhook signature
        $signature = $request->header('X-Square-Hmacsha256-Signature');
        $body = $request->getContent();
        $url = $request->fullUrl();

        if (! $this->squareService->verifyWebhookSignature($body, $signature, $url)) {
            Log::warning('Square webhook signature verification failed', [
                'signature' => $signature,
                'url' => $url,
            ]);

            return response()->json(['error' => 'Invalid signature'], 401);
        }

        // Parse webhook payload
        $payload = $request->all();
        $eventType = $payload['type'] ?? null;
        $data = $payload['data'] ?? [];

        Log::info('Square webhook received', [
            'type' => $eventType,
            'merchant_id' => $payload['merchant_id'] ?? null,
        ]);

        try {
            match ($eventType) {
                'subscription.created' => $this->handleSubscriptionCreated($data),
                'subscription.updated' => $this->handleSubscriptionUpdated($data),
                'subscription.canceled' => $this->handleSubscriptionCanceled($data),
                'payment.created' => $this->handlePaymentCreated($data),
                'payment.updated' => $this->handlePaymentUpdated($data),
                'invoice.payment_made' => $this->handleInvoicePaymentMade($data),
                'invoice.published' => $this->handleInvoicePublished($data),
                'invoice.payment_failed' => $this->handleInvoicePaymentFailed($data),
                default => Log::info('Unhandled Square webhook type', ['type' => $eventType]),
            };

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('Square webhook processing failed', [
                'type' => $eventType,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['error' => 'Processing failed'], 500);
        }
    }

    /**
     * Handle subscription.created event.
     */
    protected function handleSubscriptionCreated(array $data): void
    {
        $subscriptionId = $data['object']['subscription']['id'] ?? null;
        $customerId = $data['object']['subscription']['customer_id'] ?? null;

        if (! $subscriptionId || ! $customerId) {
            return;
        }

        Log::info('Subscription created', [
            'subscription_id' => $subscriptionId,
            'customer_id' => $customerId,
        ]);

        // Note: Subscription ID is already stored when we create it via API
        // This webhook just confirms it was created successfully
    }

    /**
     * Handle subscription.updated event.
     */
    protected function handleSubscriptionUpdated(array $data): void
    {
        $subscriptionId = $data['object']['subscription']['id'] ?? null;
        $status = $data['object']['subscription']['status'] ?? null;

        if (! $subscriptionId) {
            return;
        }

        Log::info('Subscription updated', [
            'subscription_id' => $subscriptionId,
            'status' => $status,
        ]);

        // Find member with this subscription
        $member = GroupMember::where('square_subscription_id', $subscriptionId)->first();

        if (! $member) {
            Log::warning('Subscription not found in database', [
                'subscription_id' => $subscriptionId,
            ]);

            return;
        }

        // Update member status based on subscription status
        $newStatus = match ($status) {
            'ACTIVE' => 'ACTIVE',
            'CANCELED' => 'INACTIVE',
            'PAUSED' => 'INACTIVE',
            default => $member->status,
        };

        if ($newStatus !== $member->status) {
            $member->update(['status' => $newStatus]);
            Log::info('Member status updated from subscription', [
                'member_id' => $member->id,
                'old_status' => $member->status,
                'new_status' => $newStatus,
            ]);
        }
    }

    /**
     * Handle subscription.canceled event.
     */
    protected function handleSubscriptionCanceled(array $data): void
    {
        $subscriptionId = $data['object']['subscription']['id'] ?? null;

        if (! $subscriptionId) {
            return;
        }

        Log::info('Subscription canceled', [
            'subscription_id' => $subscriptionId,
        ]);

        // Find member and mark as inactive
        $member = GroupMember::where('square_subscription_id', $subscriptionId)->first();

        if ($member) {
            $member->update(['status' => 'INACTIVE']);
            Log::info('Member marked inactive from subscription cancellation', [
                'member_id' => $member->id,
            ]);
        }
    }

    /**
     * Handle payment.created event.
     */
    protected function handlePaymentCreated(array $data): void
    {
        $paymentId = $data['object']['payment']['id'] ?? null;
        $orderId = $data['object']['payment']['order_id'] ?? null;
        $amountMoney = $data['object']['payment']['amount_money'] ?? [];

        Log::info('Payment created', [
            'payment_id' => $paymentId,
            'order_id' => $orderId,
            'amount_cents' => $amountMoney['amount'] ?? 0,
        ]);

        // Payment tracking happens via invoice events
    }

    /**
     * Handle payment.updated event.
     */
    protected function handlePaymentUpdated(array $data): void
    {
        $paymentId = $data['object']['payment']['id'] ?? null;
        $status = $data['object']['payment']['status'] ?? null;

        Log::info('Payment updated', [
            'payment_id' => $paymentId,
            'status' => $status,
        ]);

        // Payment tracking happens via invoice events
    }

    /**
     * Handle invoice.payment_made event - MAIN PAYMENT TRACKING.
     */
    protected function handleInvoicePaymentMade(array $data): void
    {
        $invoiceId = $data['object']['invoice']['id'] ?? null;
        $subscriptionId = $data['object']['invoice']['subscription_id'] ?? null;
        $paymentId = $data['object']['invoice']['payment_requests'][0]['uid'] ?? null;

        if (! $subscriptionId) {
            return;
        }

        Log::info('Invoice payment made', [
            'invoice_id' => $invoiceId,
            'subscription_id' => $subscriptionId,
            'payment_id' => $paymentId,
        ]);

        // Find member by subscription
        $member = GroupMember::where('square_subscription_id', $subscriptionId)->first();

        if (! $member) {
            Log::warning('Member not found for subscription', [
                'subscription_id' => $subscriptionId,
            ]);

            return;
        }

        // Find the latest pending payment request for this member
        $paymentRequest = PaymentRequest::where('member_id', $member->id)
            ->whereIn('status', ['pending', 'sent'])
            ->orderBy('created_at', 'desc')
            ->first();

        if ($paymentRequest) {
            // Mark as paid via Square
            $paymentRequest->update([
                'status' => 'paid',
                'paid_at' => now(),
            ]);

            Log::info('Payment request marked as paid from Square webhook', [
                'payment_request_id' => $paymentRequest->id,
                'member_id' => $member->id,
                'invoice_id' => $invoiceId,
            ]);
        } else {
            Log::warning('No pending payment request found for paid invoice', [
                'member_id' => $member->id,
                'subscription_id' => $subscriptionId,
            ]);
        }
    }

    /**
     * Handle invoice.published event.
     */
    protected function handleInvoicePublished(array $data): void
    {
        $invoiceId = $data['object']['invoice']['id'] ?? null;
        $subscriptionId = $data['object']['invoice']['subscription_id'] ?? null;

        Log::info('Invoice published', [
            'invoice_id' => $invoiceId,
            'subscription_id' => $subscriptionId,
        ]);

        // Invoice published means Square created a charge
        // We track actual payment in invoice.payment_made event
    }

    /**
     * Handle invoice.payment_failed event.
     */
    protected function handleInvoicePaymentFailed(array $data): void
    {
        $invoiceId = $data['object']['invoice']['id'] ?? null;
        $subscriptionId = $data['object']['invoice']['subscription_id'] ?? null;

        Log::info('Invoice payment failed', [
            'invoice_id' => $invoiceId,
            'subscription_id' => $subscriptionId,
        ]);

        // Find member by subscription
        $member = GroupMember::where('square_subscription_id', $subscriptionId)->first();

        if (! $member) {
            return;
        }

        // Find the latest pending payment request and mark as failed
        $paymentRequest = PaymentRequest::where('member_id', $member->id)
            ->whereIn('status', ['pending', 'sent'])
            ->orderBy('created_at', 'desc')
            ->first();

        if ($paymentRequest) {
            $paymentRequest->update([
                'status' => 'failed',
            ]);

            Log::warning('Payment request marked as failed from Square webhook', [
                'payment_request_id' => $paymentRequest->id,
                'member_id' => $member->id,
                'invoice_id' => $invoiceId,
            ]);
        }
    }
}
