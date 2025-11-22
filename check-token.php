<?php

// Quick diagnostic script to check Square token
// Run: php check-token.php

$tokenInCode = 'EAAAl4YRs9djONdfEAkmOourp_y5tP1ncA4Ls_FviBHCl_ExAlqGAfvDvDbt72l8';
$tokenFromEnv = env('SQUARE_ACCESS_TOKEN');

echo "=== Square Token Diagnostic ===\n\n";
echo 'Token in code: '.$tokenInCode."\n";
echo 'Length: '.strlen($tokenInCode)." characters\n\n";

echo 'Token from .env: '.($tokenFromEnv ?: 'NOT SET')."\n";
echo 'Length: '.strlen($tokenFromEnv)." characters\n\n";

echo "Expected format: Starts with 'EAAA' and is typically 84 characters for sandbox tokens\n\n";

echo "Test API call with token from code:\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://connect.squareupsandbox.com/v2/locations');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Square-Version: 2025-01-23',
    'Authorization: Bearer '.$tokenInCode,
    'Content-Type: application/json',
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo 'HTTP Status: '.$httpCode."\n";
if ($httpCode === 200) {
    echo "✓ Token works!\n";
} else {
    echo "✗ Token failed\n";
    echo 'Response: '.substr($response, 0, 200)."...\n";
}

echo "\n\n";
echo "INSTRUCTIONS:\n";
echo "1. Copy the FULL token from your working curl command\n";
echo "2. Replace the token on line 37 of TestRealSquareInvoice.php\n";
echo "3. Update SQUARE_ACCESS_TOKEN in .env with the complete token\n";
