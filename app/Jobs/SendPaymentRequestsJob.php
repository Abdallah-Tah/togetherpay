<?php

namespace App\Jobs;

use App\Models\PaymentRequest;
use App\Notifications\PaymentRequestEventNotification;
use App\Notifications\PaymentRequestNotification;
use App\Services\PaymentRequestService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SendPaymentRequestsJob implements ShouldQueue
{
    use Queueable;

    /**
     * Execute the job.
     */
    public function handle(PaymentRequestService $paymentService): void
    {
        Log::info('SendPaymentRequestsJob: Starting to send payment requests');

        // Find pending requests that haven't been sent yet
        $requests = PaymentRequest::where('status', 'pending')
            ->whereNull('sent_at')
            ->with(['member.user', 'cycle.group'])
            ->get();

        $sentCount = 0;

        foreach ($requests as $request) {
            try {
                // Send the request (marks as sent in DB)
                $paymentService->sendRequest($request);

                // Send notification to member
                $request->member->user->notify(
                    new PaymentRequestNotification($request)
                );

                // Notify group owner (sender) that request was sent
                $owner = $request->cycle?->group?->owner ?? $request->group?->owner;
                if ($owner) {
                    $owner->notify(new PaymentRequestEventNotification($request, 'created'));
                }

                // If payment method is Cash App and member has customer ID, send via Cash App API
                if ($request->payment_method === 'CASH_APP' && $request->member->cash_app_customer_id) {
                    dispatch(new SendCashAppPaymentRequestJob($request));
                }

                $sentCount++;

                Log::info("Sent payment request {$request->id} to user {$request->member->user_id}");
            } catch (\Exception $e) {
                Log::error("Failed to send payment request {$request->id}: {$e->getMessage()}");

                $request->markAsFailed($e->getMessage());
            }
        }

        Log::info("SendPaymentRequestsJob: Sent {$sentCount} payment requests");
    }
}
