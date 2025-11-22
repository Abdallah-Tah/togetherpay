#!/bin/bash

# TogetherPay API Routes Fix
# Fixes API returning 302 redirect without touching Nginx/vhost config

set -e  # Exit on error

echo "🔧 Fixing TogetherPay API routes (no vhost changes)..."
echo ""

# Navigate to project directory
cd /home/togetherpay/htdocs/www.togetherpay.app || {
    echo "❌ Error: Project directory not found"
    exit 1
}

echo "📝 Root Cause: Route cache or middleware issue (vhost was working yesterday)"
echo ""

echo "📝 Step 1: Check current APP_URL in .env..."
APP_URL=$(grep "^APP_URL=" .env | cut -d'=' -f2)
echo "Current APP_URL: $APP_URL"
echo ""

echo "📝 Step 2: Clear ALL Laravel caches..."
php artisan config:clear
echo "✅ Config cache cleared"

php artisan cache:clear
echo "✅ Application cache cleared"

php artisan route:clear
echo "✅ Route cache cleared"

php artisan view:clear
echo "✅ View cache cleared"

echo ""
echo "📝 Step 3: Check if API routes exist in routes/api.php..."
if grep -q "api/v1/auth/login" routes/api.php; then
    echo "✅ API routes found in routes/api.php"
else
    echo "❌ WARNING: API routes not found in routes/api.php"
fi
echo ""

echo "📝 Step 4: Verify API routes are registered..."
php artisan route:list --path=api/v1/auth | head -10
echo ""

echo "📝 Step 5: Test API endpoint (should return 422, not 302)..."
HTTP_CODE=$(curl -X POST https://www.togetherpay.app/api/v1/auth/login \
  -H 'Content-Type: application/json' \
  -H 'Accept: application/json' \
  -d '{"email":"test@example.com","password":"password"}' \
  -s -o /dev/null -w "%{http_code}")

echo "HTTP Status Code: $HTTP_CODE"

if [ "$HTTP_CODE" = "422" ] || [ "$HTTP_CODE" = "401" ]; then
    echo "✅ SUCCESS! API is working (validation error as expected)"
elif [ "$HTTP_CODE" = "302" ]; then
    echo "❌ STILL REDIRECTING - Need to check middleware"
    echo ""
    echo "Checking bootstrap/app.php middleware configuration..."
    grep -A 20 "withMiddleware" bootstrap/app.php || echo "No middleware configuration found"
else
    echo "⚠️  Unexpected status: $HTTP_CODE"
fi

echo ""
echo "📝 Step 6: Rebuild production caches..."
php artisan config:cache
echo "✅ Config cached"

php artisan route:cache
echo "✅ Routes cached"

echo ""
echo "📝 Step 7: Final test..."
curl -X POST https://www.togetherpay.app/api/v1/auth/login \
  -H 'Content-Type: application/json' \
  -H 'Accept: application/json' \
  -d '{"email":"test@example.com","password":"password"}' \
  -s -o /dev/null -w "Final HTTP Status: %{http_code}\n"

echo ""
echo "✅ API routes fix completed!"
echo ""
echo "📱 If still getting 302:"
echo "1. Check if TrustProxies middleware is forcing HTTPS redirect"
echo "2. Verify APP_URL matches actual domain (should be: https://www.togetherpay.app)"
echo "3. Check routes/api.php file exists and is loaded"
echo "4. Review bootstrap/app.php for global middleware that might redirect"
