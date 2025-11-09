<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations - Clean up Cash App links to remove query parameters
     */
    public function up(): void
    {
        // Get all payment requests with Cash App links
        $paymentRequests = DB::table('payment_requests')
            ->where('method_hint', 'CASH_APP')
            ->whereNotNull('deep_link')
            ->get();

        foreach ($paymentRequests as $pr) {
            $updates = [];

            // If deep_link is cashapp:// scheme OR has query params, clean it
            if ($pr->deep_link && (
                str_starts_with($pr->deep_link, 'cashapp://') ||
                str_contains($pr->deep_link, '?')
            )) {
                // If we have a clean web_fallback, use that as the deep_link
                if (
                    $pr->web_fallback_link &&
                    str_starts_with($pr->web_fallback_link, 'https://cash.app/') &&
                    ! str_contains($pr->web_fallback_link, '?')
                ) {
                    $updates['deep_link'] = $pr->web_fallback_link;
                } else {
                    // Try to extract and clean the link
                    $cleanLink = $this->cleanCashAppLink($pr->deep_link);
                    if ($cleanLink !== $pr->deep_link) {
                        $updates['deep_link'] = $cleanLink;
                    }
                }
            }

            // Clean web_fallback_link if it has query params
            if ($pr->web_fallback_link && str_contains($pr->web_fallback_link, '?')) {
                $cleanLink = $this->cleanCashAppLink($pr->web_fallback_link);
                if ($cleanLink !== $pr->web_fallback_link) {
                    $updates['web_fallback_link'] = $cleanLink;
                }
            }

            // Update if we have changes
            if (! empty($updates)) {
                DB::table('payment_requests')
                    ->where('id', $pr->id)
                    ->update($updates);
            }
        }
    }

    /**
     * Reverse the migrations
     */
    public function down(): void
    {
        // No need to reverse - we're just cleaning up URLs
    }

    /**
     * Remove query parameters from Cash App links
     */
    private function cleanCashAppLink(string $link): string
    {
        // Handle cashapp:// scheme links - extract cashtag and amount
        if (preg_match('#^cashapp://send\?.*cashtag=([^&]+).*amount=([0-9.]+)#i', $link, $matches)) {
            $cashtag = urldecode($matches[1]);
            $amount = $matches[2];

            return "https://cash.app/\${$cashtag}/{$amount}";
        }

        if (preg_match('#^cashapp://cash/([^?]+)\?.*amount=([0-9.]+)#i', $link, $matches)) {
            $cashtag = urldecode($matches[1]);
            $amount = $matches[2];

            return "https://cash.app/\${$cashtag}/{$amount}";
        }

        // Parse the URL for https links
        $parsed = parse_url($link);

        if (! isset($parsed['host']) || $parsed['host'] !== 'cash.app') {
            return $link; // Not a Cash App link, leave it
        }

        // Extract cashtag and amount from path
        // Format: https://cash.app/$cashtag/amount
        if (isset($parsed['path']) && preg_match('#^/\$([^/]+)/([0-9.]+)#', $parsed['path'], $matches)) {
            $cashtag = $matches[1];
            $amount = $matches[2];

            // Return clean format without query parameters
            return "https://cash.app/\${$cashtag}/{$amount}";
        }

        // If we can't parse it properly, return as-is
        return $link;
    }
};
