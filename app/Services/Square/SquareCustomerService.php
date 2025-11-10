<?php

namespace App\Services\Square;

use App\Models\GroupMember;
use App\Models\User;
use Square\Models\CreateCustomerRequest;
use Square\Models\UpdateCustomerRequest;

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
        $customersApi = $client->getCustomersApi();

        $customer = new \Square\Models\Customer();
        $customer->setGivenName($member->user->name ?? 'Member');
        $customer->setEmailAddress($member->user->email);

        // Add phone if available
        if ($member->user->phone) {
            $customer->setPhoneNumber($member->user->phone);
        }

        // Add reference ID to link back to our system
        $customer->setReferenceId("member_{$member->id}");

        $request = new CreateCustomerRequest();
        $request->setCustomer($customer);
        $request->setIdempotencyKey(uniqid('gm_' . $member->id . '_'));

        try {
            $response = $customersApi->createCustomer($request);
            $squareCustomer = $response->getResult()->getCustomer();

            return $squareCustomer->getId();
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
        $customersApi = $client->getCustomersApi();

        $customer = new \Square\Models\Customer();

        if (isset($data['name'])) {
            $customer->setGivenName($data['name']);
        }
        if (isset($data['email'])) {
            $customer->setEmailAddress($data['email']);
        }
        if (isset($data['phone'])) {
            $customer->setPhoneNumber($data['phone']);
        }

        $request = new UpdateCustomerRequest();
        $request->setCustomer($customer);

        try {
            $customersApi->updateCustomer($customerId, $request);
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
        $customersApi = $client->getCustomersApi();

        try {
            $customersApi->deleteCustomer($customerId);
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
        $customersApi = $client->getCustomersApi();

        try {
            $response = $customersApi->retrieveCustomer($customerId);
            $customer = $response->getResult()->getCustomer();

            return [
                'id' => $customer->getId(),
                'name' => $customer->getGivenName(),
                'email' => $customer->getEmailAddress(),
                'phone' => $customer->getPhoneNumber(),
                'created_at' => $customer->getCreatedAt(),
                'updated_at' => $customer->getUpdatedAt(),
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
