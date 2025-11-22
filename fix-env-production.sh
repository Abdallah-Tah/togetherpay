#!/bin/bash

# Fix APP_ENV from 'local' to 'production'
# This was causing API routes to not work properly

set -e

cd /home/togetherpay/htdocs/www.togetherpay.app || exit 1

echo "🔧 Fixing APP_ENV configuration..."
echo ""

echo "📝 Current APP_ENV:"
grep "^APP_ENV=" .env
echo ""

echo "📝 Changing APP_ENV from 'local' to 'production'..."
sed -i 's/^APP_ENV=local/APP_ENV=production/' .env
echo "✅ APP_ENV updated to production"
echo ""

echo "📝 Also setting APP_DEBUG to false for security..."
sed -i 's/^APP_DEBUG=true/APP_DEBUG=false/' .env
echo "✅ APP_DEBUG set to false"
echo ""

echo "📝 Updated configuration:"
grep "^APP_ENV=" .env
grep "^APP_DEBUG=" .env
echo ""

echo "📝 Clearing all caches to apply new environment..."
php artisan optimize:clear
echo "✅ Caches cleared"
echo ""

echo "📝 Rebuilding config cache..."
php artisan config:cache
echo "✅ Config cached"
echo ""

echo "📝 Testing API endpoint..."
HTTP_CODE=$(curl -X POST https://www.togetherpay.app/api/v1/auth/login \
  -H 'Content-Type: application/json' \
  -H 'Accept: application/json' \
  -d '{"email":"test@test.com","password":"pass"}' \
  -s -o /dev/null -w "%{http_code}")

echo "HTTP Status: $HTTP_CODE"

if [ "$HTTP_CODE" = "422" ] || [ "$HTTP_CODE" = "401" ]; then
    echo "✅ SUCCESS! API is now working!"
    echo ""
    echo "Getting JSON response to verify:"
    curl -X POST https://www.togetherpay.app/api/v1/auth/login \
      -H 'Content-Type: application/json' \
      -H 'Accept: application/json' \
      -d '{"email":"test@test.com","password":"pass"}' \
      -s | jq '.' || cat
elif [ "$HTTP_CODE" = "302" ]; then
    echo "❌ Still redirecting - there may be another issue"
    echo "Check Laravel logs: tail -50 storage/logs/laravel.log"
else
    echo "⚠️  Got unexpected status: $HTTP_CODE"
fi

echo ""
echo "✅ Environment fix completed!"
echo ""
echo "📱 Next steps:"
echo "1. Test mobile app login - should work now!"
echo "2. Monitor logs: tail -f storage/logs/laravel.log"
echo "3. IMPORTANT: Keep APP_ENV=production on production server!"
