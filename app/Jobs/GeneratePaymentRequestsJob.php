<?php

namespace App\Jobs;

use App\Models\BillingCycle;
use App\Services\PaymentRequestService;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class GeneratePaymentRequestsJob implements ShouldQueue
{
    use Queueable;

    /**
     * Execute the job.
     */
    public function handle(PaymentRequestService $paymentService): void
    {
        Log::info('GeneratePaymentRequestsJob: Starting request generation');

        // Find draft cycles that are ready to start collecting
        $cycles = BillingCycle::where('status', 'draft')
            ->where('period_start', '<=', Carbon::today())
            ->with(['group.members'])
            ->get();

        $requestsGenerated = 0;

        foreach ($cycles as $cycle) {
            try {
                $count = $paymentService->generateRequestsForCycle($cycle);

                // Update cycle status to collecting
                $cycle->update(['status' => 'collecting']);

                $requestsGenerated += $count;

                Log::info("Generated {$count} payment requests for cycle {$cycle->id}");

                // Create Square subscriptions for auto_square groups
                if ($cycle->group->isAutoSquareMode()) {
                    CreateAutoSquareSubscriptionsJob::dispatch($cycle);
                }
            } catch (\Exception $e) {
                Log::error("Failed to generate requests for cycle {$cycle->id}: {$e->getMessage()}");
            }
        }

        Log::info("GeneratePaymentRequestsJob: Generated {$requestsGenerated} payment requests");

        // Dispatch job to send the requests
        if ($requestsGenerated > 0) {
            SendPaymentRequestsJob::dispatch();
        }
    }
}
