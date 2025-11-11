<?php

namespace App\Http\Controllers\Square;

use App\Http\Controllers\Controller;
use App\Models\PaymentRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Handles incoming webhooks from Square
 * Processes invoice payments and updates payment request status
 */
class SquareWebhookController extends Controller
{
    /**
     * Handle incoming Square webhook
     */
    public function handle(Request $request)
    {
        $eventType = $request->input('type');
        $data = $request->input('data', []);

        Log::info('Square webhook received', [
            'event_type' => $eventType,
            'object_id' => $data['object']['id'] ?? null,
        ]);

        try {
            match ($eventType) {
                'invoice.payment_made' => $this->handleInvoicePaymentMade($data),
                'invoice.scheduled_charge_failed' => $this->handleInvoiceChargeFailed($data),
                'payment.updated' => $this->handlePaymentUpdated($data),
                default => Log::info('Square webhook ignored', ['event_type' => $eventType]),
            };

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('Square webhook handling failed', [
                'event_type' => $eventType,
                'error' => $e->getMessage(),
            ]);

            // Return 200 anyway - we logged the error
            return response()->json(['success' => false, 'error' => $e->getMessage()], 200);
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

        if (!$invoiceId) {
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
            $paymentRequest->update([
                'status' => 'auto_verified',
                'verified_at' => now(),
            ]);

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

        if (!$invoiceId) {
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

        // This is optional - the invoice.payment_made event is the primary indicator
        // Only use this if we need extra confirmation
    }
}
