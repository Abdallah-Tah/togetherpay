#!/bin/bash

# Fix API routes not loading
# This fixes the issue where API requests return 302 redirect to homepage

set -e

cd /home/togetherpay/htdocs/www.togetherpay.app || exit 1

echo "🔧 Fixing API route loading issue..."
echo ""

echo "📝 Step 1: Verify routes/api.php exists..."
if [ ! -f routes/api.php ]; then
    echo "❌ ERROR: routes/api.php is missing!"
    echo "You need to restore this file from your repository"
    exit 1
fi
echo "✅ routes/api.php exists"
echo ""

echo "📝 Step 2: Clear ALL caches and compiled files..."
php artisan optimize:clear
rm -f bootstrap/cache/routes-v7.php
rm -f bootstrap/cache/config.php
rm -f bootstrap/cache/services.php
echo "✅ All caches cleared"
echo ""

echo "📝 Step 3: Clear Laravel cache manually..."
php artisan config:clear
php artisan route:clear
php artisan cache:clear
php artisan view:clear
echo "✅ Laravel caches cleared"
echo ""

echo "📝 Step 4: Check if API routes are now registered..."
echo "Checking for login route:"
if php artisan route:list --path=api/v1/auth/login 2>&1 | grep -q "login"; then
    echo "✅ API routes are registered!"
    php artisan route:list --path=api/v1/auth | head -10
else
    echo "❌ API routes NOT registered!"
    echo "Checking bootstrap/app.php configuration..."
    grep -A 10 "withRouting" bootstrap/app.php
fi
echo ""

echo "📝 Step 5: Test API without route cache..."
echo "Testing: POST /api/v1/auth/login"
HTTP_CODE=$(curl -X POST https://www.togetherpay.app/api/v1/auth/login \
  -H 'Content-Type: application/json' \
  -H 'Accept: application/json' \
  -d '{"email":"test@test.com","password":"pass"}' \
  -s -o /dev/null -w "%{http_code}")

echo "HTTP Status: $HTTP_CODE"

if [ "$HTTP_CODE" = "422" ] || [ "$HTTP_CODE" = "401" ]; then
    echo "✅ SUCCESS! API is working (got validation/auth error as expected)"
elif [ "$HTTP_CODE" = "302" ]; then
    echo "❌ STILL REDIRECTING"
    echo ""
    echo "Getting full response to debug..."
    curl -X POST https://www.togetherpay.app/api/v1/auth/login \
      -H 'Content-Type: application/json' \
      -H 'Accept: application/json' \
      -d '{"email":"test@test.com","password":"pass"}' \
      -s | head -20
else
    echo "⚠️  Got unexpected status: $HTTP_CODE"
fi
echo ""

echo "📝 Step 6: Only cache config (NOT routes - can cause issues)..."
php artisan config:cache
echo "✅ Config cached"
echo ""

echo "📝 Step 7: Final test..."
curl -X POST https://www.togetherpay.app/api/v1/auth/login \
  -H 'Content-Type: application/json' \
  -H 'Accept: application/json' \
  -d '{"email":"test@test.com","password":"pass"}' \
  -s -o /dev/null -w "Final HTTP Status: %{http_code}\n"

echo ""
echo "✅ Fix script completed!"
echo ""
echo "📱 Next steps:"
echo "1. If status is 422/401 → SUCCESS! Try mobile app login"
echo "2. If still 302 → Run debug-routes.sh to see what's wrong"
echo "3. Check storage/logs/laravel.log for any errors"
