<?php

namespace App\Http\Controllers;

use App\Models\PaymentRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CashAppWebhookController extends Controller
{
    /**
     * Handle incoming Cash App webhooks for payment status updates
     */
    public function handle(Request $request)
    {
        // Verify webhook signature (implement Cash App webhook verification)
        // if (!$this->verifySignature($request)) {
        //     return response()->json(['error' => 'Invalid signature'], 401);
        // }

        $event = $request->input('event_type');
        $data = $request->input('data');

        Log::info('Cash App webhook received', [
            'event' => $event,
            'request_id' => $data['request']['id'] ?? null,
        ]);

        switch ($event) {
            case 'PAYMENT_COMPLETED':
                $this->handlePaymentCompleted($data);
                break;

            case 'PAYMENT_FAILED':
                $this->handlePaymentFailed($data);
                break;

            case 'REQUEST_EXPIRED':
                $this->handleRequestExpired($data);
                break;

            default:
                Log::info('Unhandled Cash App webhook event', ['event' => $event]);
        }

        return response()->json(['status' => 'success']);
    }

    /**
     * Handle payment completed event
     */
    protected function handlePaymentCompleted(array $data): void
    {
        $cashAppRequestId = $data['request']['id'] ?? null;
        $referenceId = $data['request']['reference_id'] ?? null; // e.g., "PR_123"

        if (!$referenceId) {
            Log::warning('Cash App webhook missing reference_id', ['data' => $data]);
            return;
        }

        // Extract payment request ID from reference_id (e.g., "PR_123" -> 123)
        $paymentRequestId = str_replace('PR_', '', $referenceId);

        $paymentRequest = PaymentRequest::find($paymentRequestId);
        if (!$paymentRequest) {
            Log::error('Payment request not found for Cash App webhook', [
                'reference_id' => $referenceId,
                'cashapp_request_id' => $cashAppRequestId,
            ]);
            return;
        }

        // Update payment request status
        $paymentRequest->update([
            'status' => 'PAID',
            'paid_at' => now(),
            'metadata_json->cashapp_payment_id' => $data['payment']['id'] ?? null,
            'metadata_json->cashapp_status' => 'COMPLETED',
        ]);

        Log::info('Payment marked as paid via Cash App webhook', [
            'payment_request_id' => $paymentRequest->id,
            'cashapp_request_id' => $cashAppRequestId,
        ]);

        // Optionally send confirmation notification to member and owner
        // dispatch(new SendPaymentConfirmationNotification($paymentRequest));
    }

    /**
     * Handle payment failed event
     */
    protected function handlePaymentFailed(array $data): void
    {
        $referenceId = $data['request']['reference_id'] ?? null;
        if (!$referenceId) return;

        $paymentRequestId = str_replace('PR_', '', $referenceId);
        $paymentRequest = PaymentRequest::find($paymentRequestId);

        if ($paymentRequest) {
            $paymentRequest->update([
                'metadata_json->cashapp_status' => 'FAILED',
                'metadata_json->cashapp_failure_reason' => $data['failure_reason'] ?? 'Unknown',
            ]);

            Log::warning('Cash App payment failed', [
                'payment_request_id' => $paymentRequest->id,
                'reason' => $data['failure_reason'] ?? 'Unknown',
            ]);
        }
    }

    /**
     * Handle request expired event
     */
    protected function handleRequestExpired(array $data): void
    {
        $referenceId = $data['request']['reference_id'] ?? null;
        if (!$referenceId) return;

        $paymentRequestId = str_replace('PR_', '', $referenceId);
        $paymentRequest = PaymentRequest::find($paymentRequestId);

        if ($paymentRequest) {
            $paymentRequest->update([
                'metadata_json->cashapp_status' => 'EXPIRED',
            ]);

            Log::info('Cash App payment request expired', [
                'payment_request_id' => $paymentRequest->id,
            ]);

            // Optionally resend request or send reminder
        }
    }

    /**
     * Verify Cash App webhook signature
     */
    protected function verifySignature(Request $request): bool
    {
        // TODO: Implement Cash App webhook signature verification
        // See Cash App documentation for webhook security
        return true;
    }
}
