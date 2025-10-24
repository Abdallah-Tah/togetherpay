<?php

namespace App\Services\PaymentDrivers;

use App\Contracts\PaymentRequestDriver;
use App\Models\GroupMember;

class CashAppDriver implements PaymentRequestDriver
{
    public function buildLink(GroupMember $member, int $amountCents, string $note): string
    {
        $handle = $member->cash_tag ?? $member->cash_phone ?? $member->cash_email;
        $amount = number_format($amountCents / 100, 2, '.', '');

        // Cash App deep-link format varies by recipient type
        if ($member->cash_tag) {
            // $cashtag format
            $params = http_build_query([
                'amount' => $amount,
                'note' => $note,
            ]);
            $cashtag = ltrim($handle, '$');
            return "https://cash.app/\${$cashtag}/{$amount}";
        }

        if ($member->cash_phone) {
            // Phone number format
            $params = http_build_query([
                'recipient' => $handle,
                'amount' => $amount,
                'note' => $note,
            ]);
            return "cashapp://send?{$params}";
        }

        // Email format
        $params = http_build_query([
            'recipient' => $handle,
            'amount' => $amount,
            'note' => $note,
        ]);
        return "cashapp://send?{$params}";
    }

    public function sendRequest(GroupMember $member, int $amountCents, string $note): array
    {
        // API-based requests not implemented in MVP
        // This will be added when FF_PAYMENT_API is enabled
        return [
            'success' => false,
            'error' => 'API requests not yet supported for Cash App',
        ];
    }

    public function verifyHandle(string $handle): bool
    {
        // Cash App $cashtag format
        if (str_starts_with($handle, '$')) {
            return preg_match('/^\$[a-zA-Z0-9]{1,20}$/', $handle) === 1;
        }

        // Email format
        if (filter_var($handle, FILTER_VALIDATE_EMAIL)) {
            return true;
        }

        // Phone format (basic validation)
        if (preg_match('/^\+?[1-9]\d{1,14}$/', $handle)) {
            return true;
        }

        return false;
    }

    public function getName(): string
    {
        return 'cash_app';
    }
}
