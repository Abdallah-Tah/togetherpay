<?php

namespace App\Jobs;

use App\Models\User;
use App\Notifications\WeeklyMemberPaymentSummary;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SendWeeklyMemberSummaries implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job - Send weekly payment summaries to all members
     */
    public function handle(): void
    {
        Log::info('SendWeeklyMemberSummaries: Starting weekly summary emails');

        $oneWeekAgo = now()->subWeek();
        $sentCount = 0;

        // Get all users who are members of at least one group
        $users = User::whereHas('groupMemberships')->get();

        foreach ($users as $user) {
            try {
                $groupSummaries = $this->buildMemberGroupSummaries($user, $oneWeekAgo);

                // Only send if there's activity to report
                if (! empty($groupSummaries)) {
                    $user->notify(new WeeklyMemberPaymentSummary($groupSummaries));
                    $sentCount++;

                    Log::info('Sent weekly summary', [
                        'user_id' => $user->id,
                        'group_count' => count($groupSummaries),
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Failed to send weekly summary', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('SendWeeklyMemberSummaries: Completed', [
            'sent_count' => $sentCount,
        ]);
    }

    /**
     * Build payment summary for each group the user is a member of
     */
    protected function buildMemberGroupSummaries(User $user, \DateTime $since): array
    {
        $summaries = [];

        // Get all groups where user is a member
        $memberships = $user->groupMemberships()
            ->with('group')
            ->get();

        foreach ($memberships as $membership) {
            $group = $membership->group;

            if (! $group) {
                continue;
            }

            // Get payment requests for this member in this group from the past week
            $paymentRequests = DB::table('payment_requests')
                ->where('member_id', $membership->id)
                ->where('created_at', '>=', $since)
                ->get();

            if ($paymentRequests->isEmpty()) {
                continue; // Skip groups with no activity
            }

            // Calculate statistics
            $confirmed = $paymentRequests->whereIn('status', ['paid', 'owner_confirmed', 'auto_verified']);
            $awaitingOwnerConfirmation = $paymentRequests->where('status', 'member_marked');
            $disputed = $paymentRequests->where('status', 'disputed');
            $unpaid = $paymentRequests->whereIn('status', ['pending', 'sent']);

            $confirmedAmount = $confirmed->sum('amount_cents') / 100;
            $awaitingOwnerConfirmationAmount = $awaitingOwnerConfirmation->sum('amount_cents') / 100;
            $unpaidAmount = $unpaid->sum('amount_cents') / 100;

            // Count payments that will auto-confirm soon (within 24 hours)
            $autoConfirmSoon = $paymentRequests
                ->where('status', 'member_marked')
                ->filter(function ($pr) {
                    return $pr->auto_confirm_at &&
                           now()->diffInHours($pr->auto_confirm_at) <= 24;
                })
                ->count();

            // Determine if needs attention
            $needsAttention = false;
            $attentionMessage = '';

            if ($disputed->count() > 0) {
                $needsAttention = true;
                $attentionMessage = "Owner reported an issue with {$disputed->count()} payment(s). Please check your messages.";
            } elseif ($unpaid->count() > 0) {
                $needsAttention = true;
                $overdueCount = $paymentRequests
                    ->whereIn('status', ['pending', 'sent'])
                    ->filter(function ($pr) {
                        return $pr->created_at && now()->diffInDays($pr->created_at) > 3;
                    })
                    ->count();

                if ($overdueCount > 0) {
                    $attentionMessage = "{$overdueCount} payment(s) overdue. Please send your payment soon.";
                } else {
                    $attentionMessage = "{$unpaid->count()} payment(s) due. Don't forget to pay!";
                }
            }

            $summaries[] = [
                'group_name' => $group->name,
                'requests_created_count' => $paymentRequests->count(),
                'confirmed_count' => $confirmed->count(),
                'confirmed_amount' => number_format($confirmedAmount, 2),
                'awaiting_owner_confirmation_count' => $awaitingOwnerConfirmation->count(),
                'awaiting_owner_confirmation_amount' => number_format($awaitingOwnerConfirmationAmount, 2),
                'unpaid_count' => $unpaid->count(),
                'unpaid_amount' => number_format($unpaidAmount, 2),
                'auto_confirm_soon' => $autoConfirmSoon,
                'needs_attention' => $needsAttention,
                'attention_message' => $attentionMessage,
            ];
        }

        return $summaries;
    }
}
