#!/bin/bash

# TogetherPay Production Cache Fix Script
# Fixes SQLite readonly database error for cache

set -e  # Exit on error

echo "🔧 Fixing TogetherPay production cache configuration..."

# Navigate to project directory
cd /home/togetherpay/htdocs/www.togetherpay.app || {
    echo "❌ Error: Project directory not found"
    exit 1
}

echo "📝 Step 1: Updating cache driver from database to Redis..."

# Check if Redis is available
if command -v redis-cli &> /dev/null; then
    echo "✅ Redis is installed"

    # Test Redis connection
    if redis-cli ping > /dev/null 2>&1; then
        echo "✅ Redis is running"

        # Update .env to use Redis cache
        sed -i 's/^CACHE_STORE=.*/CACHE_STORE=redis/' .env
        echo "✅ Updated CACHE_STORE to redis"
    else
        echo "⚠️  Redis not running, falling back to file cache"
        sed -i 's/^CACHE_STORE=.*/CACHE_STORE=file/' .env
        echo "✅ Updated CACHE_STORE to file"
    fi
else
    echo "⚠️  Redis not installed, using file cache"
    sed -i 's/^CACHE_STORE=.*/CACHE_STORE=file/' .env
    echo "✅ Updated CACHE_STORE to file"
fi

echo ""
echo "📝 Step 2: Fixing storage permissions..."

# Fix permissions for storage and cache directories
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

echo "✅ Fixed storage permissions"

echo ""
echo "📝 Step 3: Clearing all caches..."

# Clear all Laravel caches
php artisan config:clear
echo "✅ Config cache cleared"

php artisan cache:clear
echo "✅ Application cache cleared"

php artisan route:clear
echo "✅ Route cache cleared"

php artisan view:clear
echo "✅ View cache cleared"

echo ""
echo "📝 Step 4: Optimizing for production..."

# Rebuild optimized caches
php artisan config:cache
echo "✅ Config cached"

php artisan route:cache
echo "✅ Routes cached"

php artisan view:cache
echo "✅ Views cached"

echo ""
echo "📝 Step 5: Verifying cache configuration..."

# Test cache write
php artisan tinker --execute="
try {
    Cache::put('test_key', 'test_value', 60);
    \$value = Cache::get('test_key');
    if (\$value === 'test_value') {
        echo '✅ Cache write test PASSED' . PHP_EOL;
    } else {
        echo '❌ Cache write test FAILED' . PHP_EOL;
    }
    Cache::forget('test_key');
} catch (\Exception \$e) {
    echo '❌ Cache error: ' . \$e->getMessage() . PHP_EOL;
}
"

echo ""
echo "📝 Step 6: Testing login API..."

# Test login endpoint
curl -X POST https://www.togetherpay.app/api/v1/auth/login \
  -H 'Content-Type: application/json' \
  -H 'Accept: application/json' \
  -d '{"email":"test@example.com","password":"password"}' \
  -s -o /dev/null -w "HTTP Status: %{http_code}\n"

echo ""
echo "✅ Production cache fix completed!"
echo ""
echo "📱 Next steps:"
echo "1. Test login from mobile app"
echo "2. If still failing, check: tail -100 storage/logs/laravel.log"
echo "3. Verify .env has correct CACHE_STORE setting"
