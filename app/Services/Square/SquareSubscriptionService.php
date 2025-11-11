<?php

namespace App\Services\Square;

use App\Models\BillingCycle;
use App\Models\GroupMember;
use App\Models\User;

/**
 * Legacy "subscription" service now issues Square invoices with Cash App Pay
 * Keeps the same interface so the rest of the app can remain unchanged while
 * we mimic Claude's Cash App approval flow.
 */
class SquareSubscriptionService
{
    public function __construct(private SquareInvoiceService $invoiceService)
    {
    }

    /**
     * Create a Cash App Pay invoice for this cycle/member.
     * Returns the published invoice ID (stored where subscription ID used to be).
     */
    public function createSubscription(GroupMember $member, User $owner, BillingCycle $cycle): string
    {
        if (!$member->square_customer_id) {
            throw new \Exception("Member does not have a Square customer ID");
        }

        if (!$owner->square_location_id) {
            throw new \Exception("Owner does not have a Square location ID");
        }

        return $this->invoiceService->createInvoiceForMember($member, $owner, $cycle);
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
