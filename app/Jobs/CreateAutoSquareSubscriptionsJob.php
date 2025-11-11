<?php

namespace App\Jobs;

use App\Models\BillingCycle;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\PaymentRequest;
use App\Services\Square\SquareCustomerService;
use App\Services\Square\SquareSubscriptionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

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
                // Skip if this cycle already generated a payment request
                $existingRequest = PaymentRequest::where('cycle_id', $this->cycle->id)
                    ->where('member_id', $member->id)
                    ->first();

                if ($existingRequest) {
                    Log::info('Payment request already exists for member/cycle', [
                        'member_id' => $member->id,
                        'payment_request_id' => $existingRequest->id,
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

                // Create Cash App Pay invoice (legacy subscription service)
                $invoiceId = $subscriptionService->createSubscription($member, $owner, $this->cycle);

                $amountCents = $member->calculateAmount($this->cycle->total_amount_cents);

                $paymentRequest = PaymentRequest::create([
                    'group_id' => $group->id,
                    'cycle_id' => $this->cycle->id,
                    'member_id' => $member->id,
                    'amount_cents' => $amountCents,
                    'method_hint' => 'CASH_APP',
                    'pr_code' => 'PR-' . strtoupper(Str::random(8)),
                    'note' => "{$group->name} - {$this->cycle->period_start->format('M Y')}",
                    'status' => 'sent',
                    'square_invoice_id' => $invoiceId,
                    'initiated_at' => now(),
                    'sent_at' => now(),
                ]);

                $member->update([
                    'square_subscription_id' => $invoiceId,
                    'square_subscription_created_at' => now(),
                ]);

                Log::info('Created Square Cash App invoice for member', [
                    'member_id' => $member->id,
                    'invoice_id' => $invoiceId,
                    'cycle_id' => $this->cycle->id,
                    'payment_request_id' => $paymentRequest->id,
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to create Square Cash App invoice', [
                    'member_id' => $member->id,
                    'cycle_id' => $this->cycle->id,
                    'error' => $e->getMessage(),
                ]);
                // Continue with other members; don't fail entire job
            }
        }
    }
}
