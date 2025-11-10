<?php

namespace App\Jobs;

use App\Models\BillingCycle;
use App\Models\Group;
use App\Models\GroupMember;
use App\Services\Square\SquareCustomerService;
use App\Services\Square\SquareSubscriptionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Create Square subscriptions for members in auto_square groups
 * Runs after a billing cycle is created or when group is switched to auto mode
 */
class CreateAutoSquareSubscriptionsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private BillingCycle $cycle,
        private ?Group $group = null,
    ) {
    }

    public function handle(
        SquareCustomerService $customerService,
        SquareSubscriptionService $subscriptionService,
    ): void {
        $group = $this->group ?? $this->cycle->group;

        // Only process auto_square groups
        if (!$group->isAutoSquareMode()) {
            return;
        }

        // Owner must have Square connected
        if (!$group->ownerHasSquare()) {
            Log::warning('Auto-square group has no Square account connected', [
                'group_id' => $group->id,
                'owner_id' => $group->owner_id,
            ]);
            return;
        }

        $owner = $group->owner;
        $activeMembers = $group->activeMembers()->get();

        foreach ($activeMembers as $member) {
            try {
                // Skip if already has subscription
                if ($member->isAutoSquareEnrolled()) {
                    Log::info('Member already has Square subscription', [
                        'member_id' => $member->id,
                        'subscription_id' => $member->square_subscription_id,
                    ]);
                    continue;
                }

                // Create Square customer if not exists
                if (!$member->square_customer_id) {
                    $customerId = $customerService->createCustomerForMember($member, $owner);
                    $member->update(['square_customer_id' => $customerId]);
                    Log::info('Created Square customer for member', [
                        'member_id' => $member->id,
                        'customer_id' => $customerId,
                    ]);
                }

                // Create subscription
                $subscriptionId = $subscriptionService->createSubscription($member, $owner, $this->cycle);
                $member->update([
                    'square_subscription_id' => $subscriptionId,
                    'square_subscription_created_at' => now(),
                ]);

                Log::info('Created Square subscription for member', [
                    'member_id' => $member->id,
                    'subscription_id' => $subscriptionId,
                    'cycle_id' => $this->cycle->id,
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to create Square subscription', [
                    'member_id' => $member->id,
                    'cycle_id' => $this->cycle->id,
                    'error' => $e->getMessage(),
                ]);
                // Continue with other members; don't fail entire job
            }
        }
    }
}
