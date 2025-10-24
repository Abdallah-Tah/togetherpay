<?php

namespace App\Jobs;

use App\Models\DunningEvent;
use App\Models\PaymentRequest;
use App\Notifications\PaymentReminderNotification;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProcessDunningJob implements ShouldQueue
{
    use Queueable;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('ProcessDunningJob: Starting dunning process');

        $dunningConfig = config('payment_requests.dunning.reminders');
        $processedCount = 0;

        // Find pending/sent payment requests
        $requests = PaymentRequest::whereIn('status', ['pending', 'sent'])
            ->with(['member.user', 'cycle.group', 'dunningEvents'])
            ->get();

        foreach ($requests as $request) {
            try {
                $daysSinceDue = $this->getDaysSinceDue($request);

                if ($daysSinceDue < 0) {
                    continue; // Not due yet
                }

                $nextReminder = $this->getNextReminder($daysSinceDue, $dunningConfig, $request);

                if ($nextReminder) {
                    $this->sendReminder($request, $nextReminder, $daysSinceDue);
                    $processedCount++;
                }
            } catch (\Exception $e) {
                Log::error("Failed to process dunning for request {$request->id}: {$e->getMessage()}");
            }
        }

        Log::info("ProcessDunningJob: Processed {$processedCount} reminders");
    }

    protected function getDaysSinceDue(PaymentRequest $request): int
    {
        return Carbon::today()->diffInDays($request->cycle->due_at, false);
    }

    protected function getNextReminder(int $daysSinceDue, array $config, PaymentRequest $request): ?array
    {
        foreach ($config as $reminder) {
            // Check if we should send this reminder
            if ($daysSinceDue >= $reminder['days']) {
                // Check if we've already sent this type of reminder at this attempt
                $attemptNo = (int) floor($daysSinceDue / $reminder['days']) + 1;

                $existingEvent = $request->dunningEvents()
                    ->where('type', $reminder['type'])
                    ->where('attempt_no', $attemptNo)
                    ->exists();

                if (!$existingEvent) {
                    return array_merge($reminder, ['attempt_no' => $attemptNo]);
                }
            }
        }

        return null;
    }

    protected function sendReminder(PaymentRequest $request, array $reminder, int $daysSinceDue): void
    {
        // Create dunning event
        DunningEvent::create([
            'payment_request_id' => $request->id,
            'attempt_no' => $reminder['attempt_no'],
            'type' => $reminder['type'],
            'meta' => [
                'days_since_due' => $daysSinceDue,
                'amount_cents' => $request->amount_cents,
            ],
            'created_at' => now(),
        ]);

        // Send notification to member
        $request->member->user->notify(
            new PaymentReminderNotification($request, $reminder['type'], $daysSinceDue)
        );

        // If escalation, notify owner too
        if ($reminder['type'] === 'escalation') {
            $request->cycle->group->owner->notify(
                new PaymentReminderNotification($request, 'owner_escalation', $daysSinceDue)
            );
        }

        Log::info("Sent {$reminder['type']} reminder for payment request {$request->id}");
    }
}
