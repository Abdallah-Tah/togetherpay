<?php

namespace App\Http\Controllers\Square;

use App\Http\Controllers\Controller;
use App\Models\PaymentRequest;
use App\Services\Square\SquareWebhookService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Handles incoming webhooks from Square
 * Processes payments from both invoices and subscriptions
 * Updates payment request status based on Square events
 *
 * Subscription payment flow:
 * 1. Owner connects their Square account (OAuth)
 * 2. App creates subscription for each member
 * 3. Square sends monthly Cash App notifications to members
 * 4. Members confirm payment in Cash App
 * 5. Square webhook: subscription.charge.succeeded
 * 6. App marks payment as auto_verified
 */
class SquareWebhookController extends Controller
{
    public function __construct(
        private SquareWebhookService $webhookService
    ) {}

    /**
     * Handle incoming Square webhook
     */
    public function handle(Request $request)
    {
        // Verify webhook signature if signing key is configured
        $signature = $request->header('X-Square-Signature');
        $timestamp = $request->header('X-Square-Hmacsha256-Timestamp');

        if ($signature && $timestamp) {
            $isValid = $this->webhookService->verifySignature(
                $request->getContent(),
                $signature,
                $timestamp
            );

            if (! $isValid) {
                Log::warning('Square webhook signature verification failed', [
                    'ip' => $request->ip(),
                ]);

                return response()->json(['error' => 'Invalid signature'], 401);
            }
        }

        $eventType = $request->input('type');
        $data = $request->input('data', []);

        Log::info('Square webhook received', [
            'event_type' => $eventType,
            'object_id' => $data['object']['id'] ?? null,
        ]);

        try {
            match ($eventType) {
                // Invoice-based payment flow
                'invoice.payment_made' => $this->handleInvoicePaymentMade($data),
                'invoice.scheduled_charge_failed' => $this->handleInvoiceChargeFailed($data),
                // Subscription-based payment flow (new - owner-as-merchant model)
                'subscription.charge.succeeded' => $this->handleSubscriptionChargeSucceeded($data),
                'subscription.charge.failed' => $this->handleSubscriptionChargeFailed($data),
                'payment.updated' => $this->handlePaymentUpdated($data),
                default => Log::info('Square webhook ignored', ['event_type' => $eventType]),
            };

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('Square webhook handling failed', [
                'event_type' => $eventType,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Return 200 to prevent retries, but don't expose error details
            return response()->json(['success' => false], 200);
        }
    }

    /**
     * Handle invoice.payment_made webhook
     * Fired when an invoice payment is completed
     */
    private function handleInvoicePaymentMade(array $data): void
    {
        $invoice = $data['object']['invoice'] ?? [];
        $invoiceId = $invoice['id'] ?? null;

        if (! $invoiceId) {
            Log::warning('Invoice payment webhook missing invoice ID', $data);

            return;
        }

        Log::info('Invoice payment received', [
            'invoice_id' => $invoiceId,
            'customer_id' => $invoice['primary_recipient']['customer_id'] ?? null,
            'total_amount_money' => $invoice['total_money']['amount'] ?? 0,
        ]);

        // Find the payment request linked to this invoice
        $paymentRequest = PaymentRequest::where('square_invoice_id', $invoiceId)->first();

        if ($paymentRequest) {
            // Get payment ID from invoice
            $paymentId = $invoice['payment_requests'][0]['computed_amount_money']['amount'] ?? $invoiceId;

            $paymentRequest->confirmViaSquare($paymentId);

            Log::info('Payment request marked as auto_verified', [
                'payment_request_id' => $paymentRequest->id,
                'invoice_id' => $invoiceId,
            ]);
        } else {
            Log::warning('No payment request found for invoice', [
                'invoice_id' => $invoiceId,
            ]);
        }
    }

    /**
     * Handle invoice.scheduled_charge_failed webhook
     * Fired when a scheduled charge on an invoice fails
     */
    private function handleInvoiceChargeFailed(array $data): void
    {
        $invoice = $data['object']['invoice'] ?? [];
        $invoiceId = $invoice['id'] ?? null;

        if (! $invoiceId) {
            Log::warning('Invoice charge failed webhook missing invoice ID', $data);

            return;
        }

        Log::warning('Invoice charge failed', [
            'invoice_id' => $invoiceId,
            'error_code' => $data['object']['error_code'] ?? null,
        ]);

        $paymentRequest = PaymentRequest::where('square_invoice_id', $invoiceId)->first();

        if ($paymentRequest) {
            $paymentRequest->update([
                'status' => 'failed',
            ]);

            Log::info('Payment request marked as failed', [
                'payment_request_id' => $paymentRequest->id,
                'invoice_id' => $invoiceId,
            ]);
        }
    }

    /**
     * Handle subscription.charge.succeeded webhook
     * Fired when a subscription charge succeeds (member paid via Cash App)
     */
    private function handleSubscriptionChargeSucceeded(array $data): void
    {
        $subscription = $data['object']['subscription'] ?? [];
        $subscriptionId = $subscription['id'] ?? null;

        if (! $subscriptionId) {
            Log::warning('Subscription charge succeeded webhook missing subscription ID', $data);

            return;
        }

        Log::info('Subscription charge succeeded', [
            'subscription_id' => $subscriptionId,
            'customer_id' => $subscription['customer_id'] ?? null,
            'billing_anchor_date' => $subscription['billing_anchor_date'] ?? null,
        ]);

        // Find the payment request linked to this subscription
        $paymentRequest = PaymentRequest::where('square_subscription_id', $subscriptionId)->first();

        if ($paymentRequest) {
            // Use subscription ID as payment identifier
            $paymentRequest->confirmViaSquare($subscriptionId);

            Log::info('Payment request marked as auto_verified', [
                'payment_request_id' => $paymentRequest->id,
                'subscription_id' => $subscriptionId,
            ]);
        } else {
            Log::warning('No payment request found for subscription', [
                'subscription_id' => $subscriptionId,
            ]);
        }
    }

    /**
     * Handle subscription.charge.failed webhook
     * Fired when a subscription charge fails (member didn't pay)
     */
    private function handleSubscriptionChargeFailed(array $data): void
    {
        $subscription = $data['object']['subscription'] ?? [];
        $subscriptionId = $subscription['id'] ?? null;

        if (! $subscriptionId) {
            Log::warning('Subscription charge failed webhook missing subscription ID', $data);

            return;
        }

        Log::warning('Subscription charge failed', [
            'subscription_id' => $subscriptionId,
            'customer_id' => $subscription['customer_id'] ?? null,
            'error_code' => $data['object']['error_code'] ?? null,
        ]);

        $paymentRequest = PaymentRequest::where('square_subscription_id', $subscriptionId)->first();

        if ($paymentRequest) {
            $paymentRequest->update([
                'status' => 'failed',
            ]);

            Log::info('Payment request marked as failed', [
                'payment_request_id' => $paymentRequest->id,
                'subscription_id' => $subscriptionId,
            ]);
        }
    }

    /**
     * Handle payment.updated webhook
     * Extra confirmation for successful payments
     */
    private function handlePaymentUpdated(array $data): void
    {
        $payment = $data['object']['payment'] ?? [];
        $paymentId = $payment['id'] ?? null;
        $status = $payment['status'] ?? null;

        Log::info('Payment updated', [
            'payment_id' => $paymentId,
            'status' => $status,
        ]);

        // This is optional - the invoice.payment_made or subscription.charge.succeeded events are the primary indicators
        // Only use this if we need extra confirmation
    }
}
