<?php

namespace App\Services\PaymentDrivers;

use App\Contracts\PaymentRequestDriver;
use App\Models\GroupMember;

class VenmoDriver implements PaymentRequestDriver
{
    public function buildLink(GroupMember $member, int $amountCents, string $note): string
    {
        $handle = $member->venmo_username;
        $amount = number_format($amountCents / 100, 2, '.', '');

        // Venmo deep-link format: venmo://paycharge?txn=pay&recipients=@handle&amount=XX&note=...
        $params = http_build_query([
            'txn' => 'pay',
            'recipients' => $handle,
            'amount' => $amount,
            'note' => $note,
        ]);

        return "venmo://paycharge?{$params}";
    }

    public function sendRequest(GroupMember $member, int $amountCents, string $note): array
    {
        // API-based requests not implemented in MVP
        // This will be added when FF_PAYMENT_API is enabled
        return [
            'success' => false,
            'error' => 'API requests not yet supported for Venmo',
        ];
    }

    public function verifyHandle(string $handle): bool
    {
        // Venmo usernames start with @ and contain alphanumeric + hyphen/underscore
        return preg_match('/^@[a-zA-Z0-9_-]{5,30}$/', $handle) === 1;
    }

    public function getName(): string
    {
        return 'venmo';
    }
}
