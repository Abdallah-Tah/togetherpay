#!/bin/bash

# TogetherPay Nginx API Redirect Fix
# Fixes 302 redirect on /api routes when Varnish is disabled

set -e  # Exit on error

echo "🔧 Fixing TogetherPay API redirect issue..."
echo ""

# Navigate to project directory
cd /home/togetherpay/htdocs/www.togetherpay.app || {
    echo "❌ Error: Project directory not found"
    exit 1
}

echo "📝 Current Issue:"
echo "- API requests to /api/v1/* return 302 redirect to homepage"
echo "- Expected: JSON responses from Laravel API"
echo ""

echo "📝 Root Cause Analysis:"
echo "Since Varnish is disabled, the redirect is likely from:"
echo "1. Laravel route configuration forcing HTTPS redirect"
echo "2. Nginx configuration with try_files catching API routes"
echo "3. Missing API routes in route cache"
echo ""

echo "📝 Step 1: Clear Laravel route cache..."
php artisan route:clear
echo "✅ Route cache cleared"
echo ""

echo "📝 Step 2: List registered API routes..."
echo "Checking if API routes are registered:"
php artisan route:list --path=api/v1/auth/login
echo ""

echo "📝 Step 3: Check if APP_URL forces redirect..."
echo "Current APP_URL:"
grep "^APP_URL=" .env || echo "APP_URL not set"
echo ""

echo "📝 Step 4: Test API endpoint directly..."
echo "Testing login endpoint:"
curl -X POST https://www.togetherpay.app/api/v1/auth/login \
  -H 'Content-Type: application/json' \
  -H 'Accept: application/json' \
  -d '{"email":"test@example.com","password":"password"}' \
  -s -i | head -20

echo ""
echo "📝 Step 5: Rebuild route cache for production..."
php artisan route:cache
echo "✅ Routes cached"
echo ""

echo "📝 Step 6: Test again after cache rebuild..."
curl -X POST https://www.togetherpay.app/api/v1/auth/login \
  -H 'Content-Type: application/json' \
  -H 'Accept: application/json' \
  -d '{"email":"test@example.com","password":"password"}' \
  -s -o /dev/null -w "HTTP Status: %{http_code}\n"

echo ""
echo "✅ Diagnostic script completed!"
echo ""
echo "📱 Next steps:"
echo "1. If still getting 302, check Nginx config for rewrite rules"
echo "2. Verify APP_URL in .env matches actual domain"
echo "3. Check if TrustProxies middleware is configured"
echo "4. Review middleware applied to API routes"
