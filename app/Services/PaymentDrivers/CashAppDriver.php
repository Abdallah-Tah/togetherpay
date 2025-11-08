<?php

namespace App\Services\PaymentDrivers;

use App\Contracts\PaymentRequestDriver;
use App\Models\GroupMember;

class CashAppDriver implements PaymentRequestDriver
{
    public function buildLink(
        GroupMember $member,
        int $amountCents,
        string $prCode,
        string $note
    ): array {
        $handle = $member->cash_tag ?? $member->cash_phone ?? $member->cash_email ?? '';
        $amount = number_format($amountCents / 100, 2, '.', '');

        if ($member->cash_tag) {
            $cashtag = ltrim($member->cash_tag, '$');
            $uri = sprintf('cashapp://cash/%s?amount=%s&note=%s', $cashtag, $amount, urlencode($note));
            $webFallback = sprintf('https://cash.app/$%s/%s?note=%s&pr=%s', $cashtag, $amount, urlencode($note), urlencode($prCode));

            return [
                'uri' => $uri,
                'web_fallback' => $webFallback,
            ];
        }

        $recipient = $handle;
        $query = http_build_query([
            'recipient' => $recipient,
            'amount' => $amount,
            'note' => $note,
        ]);

        $uri = "cashapp://send?{$query}";
        $webFallback = sprintf(
            'https://cash.app/send?recipient=%s&amount=%s&note=%s&pr=%s',
            urlencode($recipient),
            $amount,
            urlencode($note),
            urlencode($prCode)
        );

        return [
            'uri' => $uri,
            'web_fallback' => $webFallback,
        ];
    }

    public function validateAccount(string $account): bool
    {
        if (str_starts_with($account, '$')) {
            return preg_match('/^\$[a-zA-Z0-9]{1,20}$/', $account) === 1;
        }

        if (filter_var($account, FILTER_VALIDATE_EMAIL)) {
            return true;
        }

        return preg_match('/^\+?[1-9]\d{1,14}$/', $account) === 1;
    }

    public function getName(): string
    {
        return 'cash_app';
    }
}
