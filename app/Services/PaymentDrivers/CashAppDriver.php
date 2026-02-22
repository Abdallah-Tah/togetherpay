<?php

namespace App\Services\PaymentDrivers;

use App\Contracts\PaymentRequestDriver;
use App\Models\GroupMember;

class CashAppDriver implements PaymentRequestDriver
{
    public function buildLink(
        string $ownerHandle,
        GroupMember $member,
        int $amountCents,
        string $prCode,
        string $note
    ): array {
        // Owner's handle is who receives the payment
        $amount = number_format($amountCents / 100, 2, '.', '');

        // Treat anything that is explicitly a cashtag (or stored as one) as a cashtag
        if (str_starts_with($ownerHandle, '$')) {
            $cashtag = ltrim($ownerHandle, '$');

            // Build recipient with $ prefix for deep link
            $recipient = '$'.$cashtag;

            // Smart deep link - works best on Android and some iOS versions
            $uri = $this->buildSmartUri($recipient, $amount, $note);

            // Web-friendly URL - always works as fallback, especially on iOS
            $webFallback = sprintf(
                'https://cash.app/$%s/%s',
                rawurlencode($cashtag),
                $amount
            );

            return [
                'uri' => $uri,
                'web_fallback' => $webFallback,
            ];
        }

        // Fallback for phone/email (less common)
        $recipient = $ownerHandle;
        $uri = $this->buildSmartUri($recipient, $amount, $note);

        $webFallback = sprintf(
            'https://cash.app/send?recipient=%s&amount=%s',
            rawurlencode($recipient),
            $amount
        );

        // Include note in web fallback when present
        if (! empty($note)) {
            $webFallback .= '&note='.rawurlencode($note);
        }

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

    /**
     * Build Cash App universal link
     *
     * IMPORTANT: Cash App does NOT support cashapp:// deep links with query parameters.
     * The ONLY format that works is: https://cash.app/$cashtag/amount
     *
     * Cash App ignores ALL query parameters (note, memo, etc.) for security reasons.
     * The amount is shown in the URL path, NOT prefilled in the app - user must enter it manually.
     *
     * This is a universal link that:
     * - Opens Cash App on mobile if installed
     * - Opens in browser if app not installed
     * - Works on both iOS and Android
     *
     * @param  string  $recipient  Recipient identifier (with $ for cashtag)
     * @param  string  $amount  Amount in decimal format (e.g., "5.00")
     * @param  string  $note  Payment note (NOT USED - Cash App ignores it)
     * @return string Universal link (HTTPS URL)
     */
    protected function buildSmartUri(string $recipient, string $amount, string $note): string
    {
        // Cash App only works with HTTPS universal links, not cashapp:// scheme
        // Format: https://cash.app/$cashtag/amount
        $cashtag = ltrim($recipient, '$');

        return "https://cash.app/\${$cashtag}/{$amount}";
    }
}
