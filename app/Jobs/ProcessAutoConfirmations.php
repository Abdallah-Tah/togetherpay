<?php

namespace App\Jobs;

use App\Models\PaymentRequest;
use App\Notifications\PaymentRequestEventNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessAutoConfirmations implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('ProcessAutoConfirmations: Starting auto-confirmation process');

        // Find all payment requests ready for auto-confirmation
        $readyForAutoConfirm = PaymentRequest::query()
            ->where('status', 'member_marked')
            ->whereNotNull('auto_confirm_at')
            ->where('auto_confirm_at', '<=', now())
            ->with(['member.user', 'cycle.group.owner'])
            ->get();

        $confirmedCount = 0;

        foreach ($readyForAutoConfirm as $pr) {
            try {
                // Auto-confirm the payment
                $pr->autoConfirmPayment();
                $confirmedCount++;

                Log::info('Auto-confirmed payment', [
                    'pr_id' => $pr->id,
                    'pr_code' => $pr->pr_code,
                    'amount_cents' => $pr->amount_cents,
                    'member_id' => $pr->member_id,
                ]);

                // Notify owner about auto-confirmation
                $owner = $pr->cycle?->group?->owner ?? $pr->group?->owner;
                if ($owner) {
                    $owner->notify(new PaymentRequestEventNotification(
                        $pr->fresh(['member.user', 'cycle.group']),
                        'auto_confirmed'
                    ));
                }

                // Notify member their payment was confirmed
                $memberUser = $pr->member?->user;
                if ($memberUser) {
                    $memberUser->notify(new PaymentRequestEventNotification(
                        $pr->fresh(['member.user', 'cycle.group']),
                        'owner_confirmed'
                    ));
                }
            } catch (\Exception $e) {
                Log::error('Failed to auto-confirm payment', [
                    'pr_id' => $pr->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('ProcessAutoConfirmations: Completed', [
            'confirmed_count' => $confirmedCount,
        ]);
    }
}
