#!/bin/bash

# Debug route registration issue
# Find out why API routes are redirecting

set -e

cd /home/togetherpay/htdocs/www.togetherpay.app || exit 1

echo "🔍 Debugging API route registration..."
echo ""

echo "📝 Step 1: Clear route cache completely..."
php artisan route:clear
rm -f bootstrap/cache/routes-v7.php
echo "✅ Route cache cleared and deleted"
echo ""

echo "📝 Step 2: Check if routes/api.php exists..."
if [ -f routes/api.php ]; then
    echo "✅ routes/api.php exists"
    echo "First 20 lines:"
    head -20 routes/api.php
else
    echo "❌ routes/api.php is MISSING!"
fi
echo ""

echo "📝 Step 3: Check bootstrap/app.php for API route registration..."
grep -A 5 "withRouting" bootstrap/app.php
echo ""

echo "📝 Step 4: List all registered routes (without cache)..."
echo "Looking for /api/v1/auth/login route:"
php artisan route:list --path=api/v1/auth/login
echo ""

echo "📝 Step 5: Check if APP_URL is set correctly..."
grep "^APP_URL=" .env || echo "APP_URL not set in .env"
echo ""

echo "📝 Step 6: Test API endpoint with verbose curl..."
echo "Testing: POST /api/v1/auth/login"
curl -X POST https://www.togetherpay.app/api/v1/auth/login \
  -H 'Content-Type: application/json' \
  -H 'Accept: application/json' \
  -d '{"email":"test@test.com","password":"pass"}' \
  -v 2>&1 | grep -E "(HTTP|Location|Redirecting|< )"
echo ""

echo "📝 Step 7: Check Laravel log for errors..."
echo "Last 20 lines of Laravel log:"
tail -20 storage/logs/laravel.log || echo "No Laravel log found"
echo ""

echo "✅ Debug complete!"
echo ""
echo "🔍 What to look for:"
echo "1. If routes/api.php is missing → restore from git"
echo "2. If route:list shows no API routes → bootstrap/app.php config issue"
echo "3. If curl shows HTML redirect → middleware or APP_URL issue"
echo "4. If log shows errors → fix those errors"
