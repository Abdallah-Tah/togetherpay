<?php

namespace App\Services\Square;

use App\Models\BillingCycle;
use App\Models\GroupMember;
use App\Models\User;

/**
 * Manages Square subscriptions for recurring billing via REST API
 */
class SquareSubscriptionService
{
    /**
     * Create a subscription for a group member
     */
    public function createSubscription(GroupMember $member, User $owner, BillingCycle $cycle): string
    {
        if (!$member->square_customer_id) {
            throw new \Exception("Member does not have a Square customer ID");
        }

        if (!$owner->square_location_id) {
            throw new \Exception("Owner does not have a Square location ID");
        }

        $client = new SquareApiClient($owner);

        // Calculate the member's share
        $amountCents = $member->calculateAmount($cycle->total_amount_cents);

        return $client->createSubscription([
            'idempotency_key' => uniqid('sub_' . $member->id . '_'),
            'location_id' => $owner->square_location_id,
            'customer_id' => $member->square_customer_id,
            'start_date' => $cycle->due_at->toDateString(),
            'amount_cents' => $amountCents,
            'card_id' => null, // Optional: for card-based subscriptions
        ]);
    }

    /**
     * Cancel a subscription
     */
    public function cancelSubscription(string $subscriptionId, User $owner): void
    {
        $client = new SquareApiClient($owner);
        $client->cancelSubscription($subscriptionId);
    }

    /**
     * Get subscription details from Square
     */
    public function getSubscription(string $subscriptionId, User $owner): array
    {
        $client = new SquareApiClient($owner);
        return $client->getSubscription($subscriptionId);
    }
}
