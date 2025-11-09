<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "\n📋 Latest Cash App Payment Requests:\n";
echo str_repeat('=', 70)."\n\n";

$prs = \App\Models\PaymentRequest::where('method_hint', 'CASH_APP')
    ->whereNotNull('deep_link')
    ->latest()
    ->take(5)
    ->get(['id', 'pr_code', 'deep_link', 'web_fallback_link']);

if ($prs->isEmpty()) {
    echo "No Cash App payment requests found.\n\n";
    exit;
}

foreach ($prs as $pr) {
    echo "PR #{$pr->id} - {$pr->pr_code}\n";
    echo "Deep Link: {$pr->deep_link}\n";

    if ($pr->web_fallback_link) {
        echo "Fallback: {$pr->web_fallback_link}\n";
    }

    // Check for query params
    if (strpos($pr->deep_link, '?') !== false) {
        echo "❌ Still has query params!\n";
    } else {
        echo "✅ Clean format (no query params)\n";
    }

    // Validate format
    if (preg_match('#^https://cash\.app/\$[^/]+/[0-9.]+$#', $pr->deep_link)) {
        echo "✅ Matches format: https://cash.app/\$cashtag/amount\n";
    } else {
        echo "⚠️  Format doesn't match expected pattern\n";
    }

    echo str_repeat('-', 70)."\n";
}

echo "\n✅ Verification Complete!\n\n";
