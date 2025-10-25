<?php

namespace App\Drivers;

use App\Contracts\PaymentRequestDriver;
use App\Models\GroupMember;

final class CashAppLinkDriver implements PaymentRequestDriver
{
    /**
     * Build Cash App deep link and web fallback
     *
     * Format: https://cash.app/$cashtag/{amount}?note={note}
     * Works as both deep link and web link
     */
    public function buildLink(
        GroupMember $member,
        int $amountCents,
        string $prCode,
        string $note
    ): array {
        $amount = number_format($amountCents / 100, 2, '.', '');
        $owner = $member->group->owner;
        // Try both column names for backward compatibility
        $cashTag = ltrim($owner->cashapp_tag ?? $owner->cash_tag ?? '', '$');

        if (empty($cashTag)) {
            throw new \InvalidArgumentException('Owner Cash App tag is not configured');
        }

        // Include PR code in note for reconciliation
        $fullNote = $note.' '.$prCode;
        $encodedNote = rawurlencode($fullNote);

        $uri = sprintf(
            'https://cash.app/$%s/%s?note=%s',
            $cashTag,
            $amount,
            $encodedNote
        );

        return [
            'uri' => $uri,
            'web_fallback' => $uri, // Same URL works for both
        ];
    }

    /**
     * Validate Cash App tag format
     *
     * Valid: $cashtag (alphanumeric only, 3-20 chars)
     */
    public function validateAccount(string $account): bool
    {
        // Remove $ prefix if present
        $account = ltrim($account, '$');

        // Cash tag: alphanumeric only, 3-20 characters
        return preg_match('/^[a-zA-Z0-9]{3,20}$/', $account) === 1;
    }

    /**
     * Get driver name
     */
    public function getName(): string
    {
        return 'cashapp';
    }
}
