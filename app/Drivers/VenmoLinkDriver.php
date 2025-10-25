<?php

namespace App\Drivers;

use App\Contracts\PaymentRequestDriver;
use App\Models\GroupMember;

final class VenmoLinkDriver implements PaymentRequestDriver
{
    /**
     * Build Venmo deep link and web fallback
     *
     * Deep link format: venmo://pay?recipients={username}&amount={amount}&note={note}
     * Web fallback: https://venmo.com/{username}?txn=pay&amount={amount}&note={note}
     */
    public function buildLink(
        GroupMember $member,
        int $amountCents,
        string $prCode,
        string $note
    ): array {
        $amount = number_format($amountCents / 100, 2, '.', '');
        $owner = $member->group->owner;
        $recipient = ltrim($owner->venmo_username ?? '', '@');

        if (empty($recipient)) {
            throw new \InvalidArgumentException('Owner Venmo username is not configured');
        }

        // Include PR code in note for reconciliation
        $fullNote = $note.' '.$prCode;
        $encodedNote = rawurlencode($fullNote);

        $uri = sprintf(
            'venmo://pay?recipients=%s&amount=%s&note=%s',
            $recipient,
            $amount,
            $encodedNote
        );

        $webFallback = sprintf(
            'https://venmo.com/%s?txn=pay&amount=%s&note=%s',
            $recipient,
            $amount,
            $encodedNote
        );

        return [
            'uri' => $uri,
            'web_fallback' => $webFallback,
        ];
    }

    /**
     * Validate Venmo username format
     *
     * Valid: @username, username (alphanumeric, hyphen, underscore, 1-30 chars)
     */
    public function validateAccount(string $account): bool
    {
        // Remove @ prefix if present
        $account = ltrim($account, '@');

        // Venmo username: alphanumeric, hyphen, underscore, 1-30 characters
        return preg_match('/^[a-zA-Z0-9_-]{1,30}$/', $account) === 1;
    }

    /**
     * Get driver name
     */
    public function getName(): string
    {
        return 'venmo';
    }
}
