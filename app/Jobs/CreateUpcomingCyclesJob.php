<?php

namespace App\Jobs;

use App\Models\BillingCycle;
use App\Models\Group;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class CreateUpcomingCyclesJob implements ShouldQueue
{
    use Queueable;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('CreateUpcomingCyclesJob: Starting cycle creation');

        $groups = Group::where('status', 'active')
            ->with('cycles')
            ->get();

        $cyclesCreated = 0;

        foreach ($groups as $group) {
            // Check if there's already a cycle for the next period
            $nextCycleStart = $this->calculateNextCycleStart($group);

            $existingCycle = $group->cycles()
                ->where('period_start', $nextCycleStart)
                ->first();

            if ($existingCycle) {
                continue;
            }

            // Create cycle if it's within 7 days
            $daysUntilStart = Carbon::today()->diffInDays($nextCycleStart, false);

            if ($daysUntilStart >= 0 && $daysUntilStart <= 7) {
                $this->createCycle($group, $nextCycleStart);
                $cyclesCreated++;
            }
        }

        Log::info("CreateUpcomingCyclesJob: Created {$cyclesCreated} billing cycles");
    }

    protected function calculateNextCycleStart(Group $group): Carbon
    {
        $lastCycle = $group->cycles()
            ->orderBy('period_end', 'desc')
            ->first();

        if ($lastCycle) {
            return $lastCycle->period_end->addDay();
        }

        // No previous cycle, start based on cadence
        $now = Carbon::now($group->timezone);

        if ($group->cadence === 'MONTHLY') {
            // Start on the due day of current or next month
            $start = $now->copy()->day($group->due_day);
            if ($start->isPast()) {
                $start->addMonth();
            }

            return $start;
        }

        if ($group->cadence === 'EVERY_4_WEEKS') {
            // Start next Monday
            return $now->copy()->next(Carbon::MONDAY);
        }

        // Default to start of next month
        return $now->copy()->startOfMonth()->addMonth();
    }

    protected function createCycle(Group $group, Carbon $periodStart): BillingCycle
    {
        $periodEnd = $this->calculatePeriodEnd($group, $periodStart);
        $dueAt = $this->calculateDueDate($group, $periodStart);

        return BillingCycle::create([
            'group_id' => $group->id,
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'due_at' => $dueAt,
            'status' => 'draft',
            'total_amount_cents' => $group->total_amount_cents ?? 0,
            'collected_amount_cents' => 0,
        ]);
    }

    protected function calculatePeriodEnd(Group $group, Carbon $periodStart): Carbon
    {
        if ($group->cadence === 'MONTHLY') {
            return $periodStart->copy()->addMonth()->subDay();
        }

        if ($group->cadence === 'EVERY_4_WEEKS') {
            return $periodStart->copy()->addWeeks(4)->subDay();
        }

        // Default to end of month
        return $periodStart->copy()->endOfMonth();
    }

    protected function calculateDueDate(Group $group, Carbon $periodStart): Carbon
    {
        $dueDate = $periodStart->copy();

        if ($group->cadence === 'MONTHLY') {
            $dueDate->day($group->due_day);
        } else {
            // For EVERY_4_WEEKS, due on the first day of the period
            $dueDate = $periodStart->copy();
        }

        return $dueDate->setTime(9, 0); // 9 AM in group timezone
    }
}
