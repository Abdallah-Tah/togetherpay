<?php

namespace App\Support;

class PaymentLinks
{
    /**
     * Generate Cash App universal link (HTTPS)
     * Format: https://cash.app/$<cashtag>/<amount>?note=<encoded_note>
     *
     * @param string $recipient $cashtag (e.g., $IkramBarkad)
     * @param int $amountCents Amount in cents
     * @param string $note Payment note/memo
     * @return string Universal link URL
     */
    public static function cashApp(string $recipient, int $amountCents, string $note = ''): string
    {
        // Convert cents to dollars with 2 decimals
        $amount = number_format($amountCents / 100, 2, '.', '');

        // Remove $ prefix if present for the URL path
        $cashtag = ltrim($recipient, '$');

        // Build URL with amount in path
        $url = "https://cash.app/\${$cashtag}/{$amount}";

        // Add note as query parameter if provided
        if (!empty($note)) {
            $url .= '?note=' . urlencode($note);
        }

        return $url;
    }
    /**
     * Generate Venmo universal link (HTTPS)
     * Format: https://venmo.com/u/username?amount=X.XX&note=text
     *
     * @param string $username Venmo username (with or without @)
     * @param int $amountCents Amount in cents
     * @param string $note Payment note/memo
     * @return string Universal link URL
     */
    public static function venmo(string $username, int $amountCents, string $note = ''): string
    {
        // Convert cents to dollars with 2 decimals
        $amount = number_format($amountCents / 100, 2, '.', '');

        // Remove @ prefix if present
        $user = ltrim($username, '@');

        // Build query parameters
        $params = [
            'txn' => 'pay',
            'amount' => $amount,
        ];

        if (!empty($note)) {
            $params['note'] = $note;
        }

        $query = http_build_query($params);

        // Universal link format
        return "https://venmo.com/{$user}?{$query}";
    }

    /**
     * Generate web fallback URL for Smart Pay page
     *
     * @param int $paymentRequestId
     * @return string
     */
    public static function smartPay(int $paymentRequestId): string
    {
        return url("/pay/{$paymentRequestId}");
    }
}
