<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "\n🧪 Testing Cash App Deep Link Format\n";
echo str_repeat('=', 50) . "\n\n";

$cashtag = 'IkramBarkad';
$amountCents = 500; // $5.00
$note = 'Test payment note';

$link = \App\Support\PaymentLinks::cashApp($cashtag, $amountCents, $note);

echo "✅ Generated Cash App Link:\n";
echo $link . "\n\n";

echo "📋 Expected Format:\n";
echo "https://cash.app/\$IkramBarkad/5.00\n\n";

if ($link === 'https://cash.app/$IkramBarkad/5.00') {
    echo "✅ FORMAT MATCHES EXACTLY! 🎉\n\n";
} else {
    echo "❌ Format mismatch!\n";
    echo "Expected: https://cash.app/\$IkramBarkad/5.00\n";
    echo "Got: $link\n\n";
}

if (strpos($link, '?') === false) {
    echo "✅ No query parameters (correct!)\n";
} else {
    echo "❌ Contains query parameters (should not!)\n";
}

echo "\n" . str_repeat('=', 50) . "\n\n";

echo "🧪 Testing with different amounts:\n";
echo str_repeat('-', 50) . "\n";

$tests = [
    ['IkramBarkad', 500, 'https://cash.app/$IkramBarkad/5.00'],
    ['AbdallahTM', 1000, 'https://cash.app/$AbdallahTM/10.00'],
    ['$TestUser', 1250, 'https://cash.app/$TestUser/12.50'],
    ['JohnDoe', 100, 'https://cash.app/$JohnDoe/1.00'],
];

foreach ($tests as [$tag, $cents, $expected]) {
    $result = \App\Support\PaymentLinks::cashApp($tag, $cents);
    $match = $result === $expected ? '✅' : '❌';
    echo "$match $tag (\$$cents cents): $result\n";
    if ($result !== $expected) {
        echo "   Expected: $expected\n";
    }
}

echo "\n" . str_repeat('=', 50) . "\n";
echo "✅ Test Complete!\n\n";
