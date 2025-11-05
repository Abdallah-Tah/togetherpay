<?php

namespace App\Contracts;

use App\Models\GroupMember;

interface PaymentRequestDriver
{
    /**
     * Build deep link and web fallback for payment request
     *
     * @param  GroupMember  $member  The member who will pay
     * @param  int  $amountCents  Amount in cents (e.g., 5000 = $50.00)
     * @param  string  $prCode  Payment Request Code for reconciliation
     * @param  string  $note  Payment note/description
     * @return array{uri: string, web_fallback: string} Deep link and web fallback URL
     */
    public function buildLink(
        GroupMember $member,
        int $amountCents,
        string $prCode,
        string $note
    ): array;

    /**
     * Validate payment account format
     *
     * @param  string  $account  Account identifier (e.g., $cashtag or @username)
     * @return bool True if account format is valid
     */
    public function validateAccount(string $account): bool;

    /**
     * Get the driver name
     */
    public function getName(): string;
}
