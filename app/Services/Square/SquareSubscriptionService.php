<?php

namespace App\Services\Square;

use App\Models\BillingCycle;
use App\Models\GroupMember;
use App\Models\User;
use Square\Models\CreateSubscriptionRequest;
use Square\Models\Money;
use Square\Models\SubscriptionPhase;

/**
 * Manages Square subscriptions for recurring billing
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

        $client = SquareClientBuilder::buildForUser($owner);
        $subscriptionsApi = $client->getSubscriptionsApi();

        // Calculate the member's share
        $amountCents = $member->calculateAmount($cycle->total_amount_cents);

        // Create price list item for the subscription
        $priceListItem = new \Square\Models\SubscriptionPhase();

        // Set subscription amount
        $price = new Money();
        $price->setAmount($amountCents);
        $price->setCurrency('USD');
        $priceListItem->setPrice($price);

        // Set billing period
        $config = config('payment_requests.square.subscription');
        $priceListItem->setOrderTemplateId(null);

        // Create subscription request
        $request = new CreateSubscriptionRequest();
        $request->setCustomerId($member->square_customer_id);
        $request->setCardId('{NONCE}'); // Will be populated by Cash App Pay
        $request->setIdempotencyKey(uniqid('sub_' . $member->id . '_'));

        // Set subscription phases
        $phases = [];
        $phase = new SubscriptionPhase();
        $phase->setUid('phase_' . $member->id);
        $phase->setOrdinal(1);
        $phase->setPrice($price);
        $phase->setRecurrenceType('MONTHLY');
        $phase->setRecurrenceDetails([
            'interval' => $config['interval_unit'],
            'interval_count' => $config['interval_count'],
        ]);

        $phases[] = $phase;
        $request->setPhases($phases);

        // Set start date to next billing cycle due date
        $request->setStartDate($cycle->due_at->toDateString());

        try {
            $response = $subscriptionsApi->createSubscription($request);
            $subscription = $response->getResult()->getSubscription();

            return $subscription->getId();
        } catch (\Exception $e) {
            logger()->error('Failed to create Square subscription', [
                'member_id' => $member->id,
                'customer_id' => $member->square_customer_id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Cancel a subscription
     */
    public function cancelSubscription(string $subscriptionId, User $owner): void
    {
        $client = SquareClientBuilder::buildForUser($owner);
        $subscriptionsApi = $client->getSubscriptionsApi();

        try {
            $subscriptionsApi->cancelSubscription($subscriptionId);
        } catch (\Exception $e) {
            logger()->error('Failed to cancel Square subscription', [
                'subscription_id' => $subscriptionId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Update subscription amount (when member share changes)
     */
    public function updateSubscriptionAmount(string $subscriptionId, User $owner, int $amountCents): void
    {
        $client = SquareClientBuilder::buildForUser($owner);
        $subscriptionsApi = $client->getSubscriptionsApi();

        $price = new Money();
        $price->setAmount($amountCents);
        $price->setCurrency('USD');

        $phase = new SubscriptionPhase();
        $phase->setPrice($price);

        try {
            $subscriptionsApi->updateSubscription($subscriptionId, $phase);
        } catch (\Exception $e) {
            logger()->error('Failed to update Square subscription amount', [
                'subscription_id' => $subscriptionId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get subscription details from Square
     */
    public function getSubscription(string $subscriptionId, User $owner): array
    {
        $client = SquareClientBuilder::buildForUser($owner);
        $subscriptionsApi = $client->getSubscriptionsApi();

        try {
            $response = $subscriptionsApi->retrieveSubscription($subscriptionId);
            $subscription = $response->getResult()->getSubscription();

            return [
                'id' => $subscription->getId(),
                'customer_id' => $subscription->getCustomerId(),
                'status' => $subscription->getStatus(),
                'start_date' => $subscription->getStartDate(),
                'timezone' => $subscription->getTimezone(),
            ];
        } catch (\Exception $e) {
            logger()->error('Failed to retrieve Square subscription', [
                'subscription_id' => $subscriptionId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * List subscriptions for a customer
     */
    public function listCustomerSubscriptions(string $customerId, User $owner): array
    {
        $client = SquareClientBuilder::buildForUser($owner);
        $subscriptionsApi = $client->getSubscriptionsApi();

        try {
            $response = $subscriptionsApi->searchSubscriptions([
                'query' => [
                    'filter' => [
                        'customer_ids' => [$customerId],
                    ],
                ],
            ]);

            $subscriptions = [];
            foreach ($response->getResult()->getSubscriptions() ?? [] as $sub) {
                $subscriptions[] = [
                    'id' => $sub->getId(),
                    'status' => $sub->getStatus(),
                    'start_date' => $sub->getStartDate(),
                ];
            }

            return $subscriptions;
        } catch (\Exception $e) {
            logger()->error('Failed to list subscriptions', [
                'customer_id' => $customerId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
