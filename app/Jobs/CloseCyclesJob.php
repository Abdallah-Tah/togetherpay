<?php

namespace App\Jobs;

use App\Models\BillingCycle;
use App\Notifications\CycleClosedNotification;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class CloseCyclesJob implements ShouldQueue
{
    use Queueable;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('CloseCyclesJob: Starting cycle closure process');

        // Find collecting cycles that have passed their grace period
        $cycles = BillingCycle::where('status', 'collecting')
            ->with(['group', 'paymentRequests'])
            ->get();

        $closedCount = 0;

        foreach ($cycles as $cycle) {
            try {
                $gracePeriodEnd = $cycle->due_at
                    ->copy()
                    ->addDays($cycle->group->grace_days);

                if (Carbon::now()->greaterThan($gracePeriodEnd)) {
                    $this->closeCycle($cycle);
                    $closedCount++;
                }
            } catch (\Exception $e) {
                Log::error("Failed to close cycle {$cycle->id}: {$e->getMessage()}");
            }
        }

        Log::info("CloseCyclesJob: Closed {$closedCount} billing cycles");
    }

    protected function closeCycle(BillingCycle $cycle): void
    {
        // Update cycle status
        $cycle->update(['status' => 'closed']);

        // Mark unpaid requests as expired
        $cycle->paymentRequests()
            ->whereIn('status', ['pending', 'sent'])
            ->update([
                'status' => 'expired',
                'fail_reason' => 'Payment request expired after grace period',
            ]);

        // Calculate collection metrics
        $totalRequests = $cycle->paymentRequests()->count();
        $paidRequests = $cycle->paymentRequests()->where('status', 'paid')->count();
        $collectionRate = $totalRequests > 0 ? ($paidRequests / $totalRequests) * 100 : 0;

        // Notify owner
        $cycle->group->owner->notify(
            new CycleClosedNotification($cycle, [
                'total_requests' => $totalRequests,
                'paid_requests' => $paidRequests,
                'collection_rate' => round($collectionRate, 2),
                'total_amount' => $cycle->total_amount,
                'collected_amount' => $cycle->collected_amount,
                'outstanding_amount' => ($cycle->total_amount_cents - $cycle->collected_amount_cents) / 100,
            ])
        );

        Log::info("Closed cycle {$cycle->id} with {$collectionRate}% collection rate");
    }
}
