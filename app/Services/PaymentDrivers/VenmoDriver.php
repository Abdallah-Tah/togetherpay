<?php

namespace App\Services\PaymentDrivers;

use App\Contracts\PaymentRequestDriver;
use App\Models\GroupMember;

class VenmoDriver implements PaymentRequestDriver
{
    public function buildLink(
        string $ownerHandle,
        GroupMember $member,
        int $amountCents,
        string $prCode,
        string $note
    ): array {
        // Owner's handle is who receives the payment
        $recipientHandle = ltrim($ownerHandle, '@');
        $amount = number_format($amountCents / 100, 2, '.', '');

        $query = http_build_query([
            'txn' => 'pay',
            'recipients' => "@{$recipientHandle}",
            'amount' => $amount,
            'note' => $note,
        ]);

        $deepLink = "venmo://paycharge?{$query}";
        $webFallback = sprintf(
            'https://venmo.com/u/%s?txn=pay&amount=%s&note=%s&pr=%s',
            $recipientHandle,
            $amount,
            urlencode($note),
            urlencode($prCode)
        );

        return [
            'uri' => $deepLink,
            'web_fallback' => $webFallback,
        ];
    }

    public function validateAccount(string $account): bool
    {
        return preg_match('/^@?[a-zA-Z0-9_-]{5,30}$/', $account) === 1;
    }

    public function getName(): string
    {
        return 'venmo';
    }
}
