<?php

namespace App\Services\Square;

use App\Models\GroupMember;
use App\Models\User;
use Square\Customers\Requests\CreateCustomerRequest;
use Square\Customers\Requests\UpdateCustomerRequest;

/**
 * Manages Square Customer creation and updates
 */
class SquareCustomerService
{
    /**
     * Create a Square customer for a group member
     */
    public function createCustomerForMember(GroupMember $member, User $owner): string
    {
        $client = SquareClientBuilder::buildForUser($owner);
        $customersApi = $client->customers;

        $customerData = [
            'idempotencyKey' => uniqid('gm_' . $member->id . '_'),
            'givenName' => $member->user->name ?? 'Member',
            'emailAddress' => $member->user->email,
            'referenceId' => "member_{$member->id}",
        ];

        // Add phone if available
        if ($member->user->phone) {
            $customerData['phoneNumber'] = $member->user->phone;
        }

        $request = new CreateCustomerRequest($customerData);

        try {
            $response = $customersApi->create($request);

            return $response->customer->id;
        } catch (\Exception $e) {
            logger()->error('Failed to create Square customer', [
                'member_id' => $member->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Update existing Square customer
     */
    public function updateCustomer(string $customerId, User $owner, array $data): void
    {
        $client = SquareClientBuilder::buildForUser($owner);
        $customersApi = $client->customers;

        $customerData = [];

        if (isset($data['name'])) {
            $customerData['givenName'] = $data['name'];
        }
        if (isset($data['email'])) {
            $customerData['emailAddress'] = $data['email'];
        }
        if (isset($data['phone'])) {
            $customerData['phoneNumber'] = $data['phone'];
        }

        $request = new UpdateCustomerRequest($customerData);

        try {
            $customersApi->update($customerId, $request);
        } catch (\Exception $e) {
            logger()->error('Failed to update Square customer', [
                'customer_id' => $customerId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Delete a Square customer
     */
    public function deleteCustomer(string $customerId, User $owner): void
    {
        $client = SquareClientBuilder::buildForUser($owner);
        $customersApi = $client->customers;

        try {
            $customersApi->delete($customerId);
        } catch (\Exception $e) {
            logger()->error('Failed to delete Square customer', [
                'customer_id' => $customerId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get customer details from Square
     */
    public function getCustomer(string $customerId, User $owner): array
    {
        $client = SquareClientBuilder::buildForUser($owner);
        $customersApi = $client->customers;

        try {
            $response = $customersApi->get($customerId);
            $customer = $response->customer;

            return [
                'id' => $customer->id,
                'name' => $customer->givenName,
                'email' => $customer->emailAddress,
                'phone' => $customer->phoneNumber ?? null,
                'created_at' => $customer->createdAt,
                'updated_at' => $customer->updatedAt,
            ];
        } catch (\Exception $e) {
            logger()->error('Failed to retrieve Square customer', [
                'customer_id' => $customerId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
