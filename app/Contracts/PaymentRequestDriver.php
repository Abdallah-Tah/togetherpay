<?php

namespace App\Contracts;

use App\Models\GroupMember;

interface PaymentRequestDriver
{
    /**
     * Build a payment link for the member
     *
     * @param GroupMember $member
     * @param int $amountCents
     * @param string $note
     * @return string
     */
    public function buildLink(GroupMember $member, int $amountCents, string $note): string;

    /**
     * Send a payment request (for API-based drivers)
     *
     * @param GroupMember $member
     * @param int $amountCents
     * @param string $note
     * @return array{success: bool, external_id?: string, error?: string}
     */
    public function sendRequest(GroupMember $member, int $amountCents, string $note): array;

    /**
     * Verify if a payment handle is valid
     *
     * @param string $handle
     * @return bool
     */
    public function verifyHandle(string $handle): bool;

    /**
     * Get the driver name
     *
     * @return string
     */
    public function getName(): string;
}
