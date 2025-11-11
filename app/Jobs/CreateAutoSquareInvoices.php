<?php

namespace App\Jobs;

use App\Models\BillingCycle;
use App\Models\PaymentRequest;
use App\Services\Square\SquareCustomerService;
use App\Services\Square\SquareSubscriptionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Creates Square subscriptions for each member in an auto_square billing cycle
 * Dispatched when a billing cycle is created for auto_square groups
 *
 * Payment flow:
 * 1. Owner connects their Square account (OAuth)
 * 2. App creates subscription for each member
 * 3. Square sends monthly Cash App notifications to members
 * 4. Members confirm payment in Cash App
 * 5. Money flows directly to owner's Square merchant account
 * 6. Webhooks confirm payment and update status
 */
class CreateAutoSquareInvoices implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private BillingCycle $cycle)
    {
    }

    public function handle(): void
    {
        $group = $this->cycle->group;

        // Only process auto_square groups
        if ($group->billing_mode !== 'auto_square') {
            Log::info('Skipping non-auto_square group', ['group_id' => $group->id]);
            return;
        }

        $owner = $group->owner;

        // Verify owner has Square connection
        if (!$owner->hasSquareAccount()) {
            Log::error('Owner missing Square connection', [
                'owner_id' => $owner->id,
                'group_id' => $group->id,
            ]);
            return;
        }

        $customerService = new SquareCustomerService();
        $subscriptionService = new SquareSubscriptionService();

        Log::info('Starting subscription creation for cycle', [
            'cycle_id' => $this->cycle->id,
            'group_id' => $group->id,
            'group_name' => $group->name,
            'total_members' => $group->members()->count(),
        ]);

        foreach ($group->members()->where('status', 'active')->get() as $member) {
            try {
                // Step 1: Ensure member has Square customer ID
                if (!$member->square_customer_id) {
                    Log::info('Creating Square customer for member', [
                        'member_id' => $member->id,
                        'group_id' => $group->id,
                    ]);

                    $customerId = $customerService->createCustomerForMember($member, $owner);
                    $member->update(['square_customer_id' => $customerId]);

                    Log::info('Created Square customer for member', [
                        'member_id' => $member->id,
                        'customer_id' => $customerId,
                    ]);
                }

                // Step 2: Create subscription for recurring monthly billing
                Log::info('Creating subscription for member', [
                    'member_id' => $member->id,
                    'cycle_id' => $this->cycle->id,
                ]);

                $subscriptionId = $subscriptionService->createSubscription($member, $owner, $this->cycle);

                // Step 3: Create payment request record linked to subscription
                $amountCents = $member->calculateAmount($this->cycle->total_amount_cents);

                $paymentRequest = PaymentRequest::create([
                    'group_id' => $group->id,
                    'cycle_id' => $this->cycle->id,
                    'member_id' => $member->id,
                    'amount_cents' => $amountCents,
                    'method_hint' => 'SQUARE_SUBSCRIPTION',
                    'status' => 'sent',
                    'sent_at' => now(),
                    'square_subscription_id' => $subscriptionId,
                ]);

                Log::info('Created payment request for subscription', [
                    'payment_request_id' => $paymentRequest->id,
                    'subscription_id' => $subscriptionId,
                    'member_id' => $member->id,
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to create subscription for member', [
                    'member_id' => $member->id,
                    'cycle_id' => $this->cycle->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('Finished subscription creation for cycle', [
            'cycle_id' => $this->cycle->id,
            'group_id' => $group->id,
        ]);
    }
}
