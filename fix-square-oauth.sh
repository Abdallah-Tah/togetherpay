#!/bin/bash

# Fix Square OAuth Configuration
# Fixes environment/mode mismatch and redirect URI

set -e

cd /home/togetherpay/htdocs/www.togetherpay.app || exit 1

echo "🔧 Fixing Square OAuth configuration..."
echo ""

echo "📝 Current Square configuration:"
grep "^SQUARE_" .env | grep -v "PASSWORD\|SECRET\|TOKEN"
echo ""

echo "📝 Issue detected:"
echo "- SQUARE_ENVIRONMENT=production"
echo "- But SQUARE_APPLICATION_ID starts with 'sandbox-'"
echo "  → This is a MISMATCH!"
echo ""

echo "📝 Fix: Change environment to 'sandbox' to match credentials..."
sed -i 's/^SQUARE_ENVIRONMENT=production/SQUARE_ENVIRONMENT=sandbox/' .env
echo "✅ Updated SQUARE_ENVIRONMENT to sandbox"
echo ""

echo "📝 Also need to check redirect URI..."
CURRENT_REDIRECT=$(grep "^SQUARE_OAUTH_REDIRECT_URI=" .env | cut -d'=' -f2)
echo "Current redirect URI: $CURRENT_REDIRECT"
echo ""

if [[ "$CURRENT_REDIRECT" == *"togetherpay.app"* ]]; then
    echo "✅ Redirect URI looks correct for production domain"
elif [[ "$CURRENT_REDIRECT" == "" ]]; then
    echo "⚠️  Redirect URI is not set!"
    echo "Adding default redirect URI..."
    echo "" >> .env
    echo "# Square OAuth callback URL for mobile" >> .env
    echo "SQUARE_OAUTH_REDIRECT_URI=https://www.togetherpay.app/oauth/square/callback" >> .env
    echo "✅ Added SQUARE_OAUTH_REDIRECT_URI"
else
    echo "⚠️  Redirect URI might be wrong: $CURRENT_REDIRECT"
    echo "Expected: https://www.togetherpay.app/oauth/square/callback"
fi
echo ""

echo "📝 Updated Square configuration:"
grep "^SQUARE_" .env | grep -v "PASSWORD\|SECRET\|TOKEN"
echo ""

echo "📝 Clearing config cache to apply changes..."
php artisan config:clear
php artisan config:cache
echo "✅ Config cache refreshed"
echo ""

echo "📝 Testing Square OAuth URL generation..."
php artisan tinker --execute="
try {
    \$service = app('App\\Services\\Square\\SquareOAuthService');
    \$url = \$service->getAuthorizationUrl('test_state_123');
    echo 'Generated OAuth URL: ' . \$url . PHP_EOL;

    if (str_contains(\$url, 'squareupsandbox.com')) {
        echo '✅ Using sandbox URL (correct for sandbox credentials)' . PHP_EOL;
    } else {
        echo '⚠️  Using production URL' . PHP_EOL;
    }
} catch (Exception \$e) {
    echo '❌ Error: ' . \$e->getMessage() . PHP_EOL;
}
"
echo ""

echo "✅ Square OAuth configuration fixed!"
echo ""
echo "📱 IMPORTANT - Next steps:"
echo ""
echo "1. Go to Square Developer Portal:"
echo "   https://developer.squareup.com/apps"
echo ""
echo "2. Select your SANDBOX application"
echo ""
echo "3. Go to OAuth → Redirect URLs"
echo ""
echo "4. Make sure this URL is added:"
echo "   https://www.togetherpay.app/oauth/square/callback"
echo ""
echo "5. Try connecting Square from mobile app again!"
echo ""
echo "⚠️  Note: You're using SANDBOX credentials"
echo "   - For testing only"
echo "   - Switch to production credentials when ready to go live"
