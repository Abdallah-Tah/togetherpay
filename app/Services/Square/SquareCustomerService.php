<?php

namespace App\Services\Square;

use App\Models\GroupMember;
use App\Models\User;

/**
 * Manages Square Customer creation and updates via REST API
 */
class SquareCustomerService
{
    /**
     * Create a Square customer for a group member
     */
    public function createCustomerForMember(GroupMember $member, User $owner): string
    {
        $client = new SquareApiClient($owner);

        return $client->createCustomer([
            'idempotency_key' => uniqid('gm_' . $member->id . '_'),
            'given_name' => $member->user->name ?? 'Member',
            'email_address' => $member->user->email,
            'phone_number' => $member->user->phone,
            'reference_id' => "member_{$member->id}",
        ]);
    }

    /**
     * Get customer details from Square
     */
    public function getCustomer(string $customerId, User $owner): array
    {
        $client = new SquareApiClient($owner);
        return $client->getCustomer($customerId);
    }
}
