<?php

namespace App\Services\Square;

use App\Models\PaymentRequest;
use Illuminate\Support\Facades\Log;

/**
 * Handles Square webhook events for payment confirmations
 */
class SquareWebhookService
{
    /**
     * Verify webhook signature
     */
    public function verifySignature(string $body, string $signature, string $timestamp): bool
    {
        $signingKey = config('payment_requests.square.webhook.signing_key');

        if (! $signingKey) {
            Log::warning('Square webhook signing key not configured');

            return false;
        }

        // Construct the signed content
        $url = config('payment_requests.square.webhook.endpoint');
        $signedContent = $url.$body.$timestamp;

        // Compute HMAC
        $hash = hash_hmac('sha256', $signedContent, $signingKey, true);
        $computedSignature = base64_encode($hash);

        return hash_equals($computedSignature, $signature);
    }

    /**
     * Handle invoice.payment_made webhook
     */
    public function handleInvoicePaymentMade(array $data): void
    {
        $invoice = $data['data']['object']['invoice'] ?? null;

        if (! $invoice) {
            Log::warning('Invalid invoice payment made webhook data', $data);

            return;
        }

        $invoiceId = $invoice['id'];
        $paymentId = $invoice['payment_requests'][0]['payment_ids'][0] ?? null;

        if (! $paymentId) {
            Log::warning('No payment ID in invoice payment made', $data);

            return;
        }

        // Find payment request by Square invoice ID
        $paymentRequest = PaymentRequest::where('square_invoice_id', $invoiceId)->first();

        if (! $paymentRequest) {
            Log::warning('Payment request not found for Square invoice', ['invoice_id' => $invoiceId]);

            return;
        }

        // Mark as verified via Square
        try {
            $paymentRequest->confirmViaSquare($paymentId);
            Log::info('Payment confirmed via Square webhook', [
                'payment_request_id' => $paymentRequest->id,
                'invoice_id' => $invoiceId,
                'payment_id' => $paymentId,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to confirm payment via Square webhook', [
                'payment_request_id' => $paymentRequest->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Handle payment.updated webhook (alternative approach)
     */
    public function handlePaymentUpdated(array $data): void
    {
        $payment = $data['data']['object']['payment'] ?? null;

        if (! $payment) {
            Log::warning('Invalid payment updated webhook data', $data);

            return;
        }

        $squarePaymentId = $payment['id'];
        $status = $payment['status'] ?? null;

        // Only process completed payments
        if ($status !== 'COMPLETED') {
            Log::info('Payment not completed, skipping', ['status' => $status]);

            return;
        }

        // Find payment request by Square payment ID
        $paymentRequest = PaymentRequest::where('square_payment_id', $squarePaymentId)->first();

        if (! $paymentRequest) {
            Log::warning('Payment request not found for Square payment', ['payment_id' => $squarePaymentId]);

            return;
        }

        // Mark as verified via Square
        try {
            $paymentRequest->confirmViaSquare($squarePaymentId);
            Log::info('Payment confirmed via payment.updated webhook', [
                'payment_request_id' => $paymentRequest->id,
                'payment_id' => $squarePaymentId,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to confirm payment via payment.updated webhook', [
                'payment_request_id' => $paymentRequest->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Handle subscription.updated webhook
     */
    public function handleSubscriptionUpdated(array $data): void
    {
        $subscription = $data['data']['object']['subscription'] ?? null;

        if (! $subscription) {
            Log::warning('Invalid subscription updated webhook data', $data);

            return;
        }

        $subscriptionId = $subscription['id'];
        $status = $subscription['status'] ?? null;

        // Log subscription status changes for monitoring
        Log::info('Subscription status updated', [
            'subscription_id' => $subscriptionId,
            'status' => $status,
        ]);
    }

    /**
     * Route webhook to appropriate handler
     */
    public function handleWebhook(array $payload): void
    {
        $eventType = $payload['type'] ?? null;

        match ($eventType) {
            'invoice.payment_made' => $this->handleInvoicePaymentMade($payload),
            'payment.updated' => $this->handlePaymentUpdated($payload),
            'subscription.updated' => $this->handleSubscriptionUpdated($payload),
            default => Log::info('Unhandled Square webhook event', ['type' => $eventType]),
        };
    }
}
