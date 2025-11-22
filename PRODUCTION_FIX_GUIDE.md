# TogetherPay Production Fix Guide

## Current Issue: Login Returns "Server Error"

**Root Cause**: Production server is configured to use SQLite for caching, but the database file is read-only, causing all cache writes to fail (including rate limiting for login attempts).

**Error from logs**:
```
SQLSTATE[HY000]: General error: 8 attempt to write a readonly database
(Connection: sqlite, SQL: insert or ignore into "cache" ...)
```

---

## Quick Fix (Run on Production Server)

### Option 1: Automated Script (Recommended)

```bash
# SSH into production server
ssh root@ubuntu-16gb-hel1-1

# Navigate to project
cd /home/togetherpay/htdocs/www.togetherpay.app

# Upload and run the fix script
chmod +x fix-production-cache.sh
./fix-production-cache.sh
```

### Option 2: Manual Fix

```bash
# SSH into production server
ssh root@ubuntu-16gb-hel1-1

# Navigate to project
cd /home/togetherpay/htdocs/www.togetherpay.app

# Check if Redis is available
redis-cli ping

# If Redis is available (returns PONG):
sed -i 's/^CACHE_STORE=.*/CACHE_STORE=redis/' .env

# If Redis is NOT available:
sed -i 's/^CACHE_STORE=.*/CACHE_STORE=file/' .env

# Fix storage permissions
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# Clear all caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Rebuild production caches
php artisan config:cache
php artisan route:cache
```

### Verify the Fix

```bash
# Test cache write
php artisan tinker --execute="
Cache::put('test', 'works', 60);
echo Cache::get('test');
"

# Should output: works

# Test login API
curl -X POST https://www.togetherpay.app/api/v1/auth/login \
  -H 'Content-Type: application/json' \
  -d '{"email":"test@test.com","password":"password"}' \
  -v
```

**Expected Result**: Should return HTTP 422 (validation error) instead of 500 (server error)

---

## Test Mobile App Login

After running the fix:

1. Open TogetherPay mobile app on your iPhone/Android
2. Try to login with valid credentials
3. **Should now work!** ✅

---

## What Changed

### Before (Broken):
```env
CACHE_STORE=database  # ❌ SQLite cache is readonly
```

### After (Fixed):
```env
CACHE_STORE=redis     # ✅ Redis cache (if available)
# OR
CACHE_STORE=file      # ✅ File cache (fallback)
```

---

## Why This Happened

Laravel's rate limiting middleware (used on login endpoint) needs to write to the cache to track request counts. The production `.env` was configured with:

```env
CACHE_STORE=database
```

This tells Laravel to use the `cache` table in SQLite. However, the SQLite database file has read-only permissions, causing all write operations to fail.

**Solutions**:
1. **Use Redis** (recommended for production - faster, supports atomic operations)
2. **Use file cache** (fallback if Redis not available)
3. **Fix SQLite permissions** (not recommended - SQLite not ideal for cache)

---

## Next Steps After Fix

### Immediate (Required for Production):

1. ✅ **Fix cache issue** (this guide)
2. 🔐 **Critical Security Fixes** (see SECURITY_PRIORITIES.md)
   - Encrypt Square access tokens
   - Enforce webhook signature verification
   - Set token expiration to 30 days
   - Add webhook rate limiting
   - Strengthen password policy

### Short Term (This Week):

3. 📊 **Add Missing Database Indexes** (performance)
4. 🐛 **Fix N+1 Queries** (3 identified in review)
5. 🧪 **Add Critical Tests** (payment flow, webhooks)

### Medium Term (Next Sprint):

6. 📚 **Generate API Documentation**
7. 📱 **Mobile App Security Fixes**
8. 🎨 **Refactor God Widgets** (2000+ line files)

---

## Monitoring & Logging

After the fix, monitor these logs:

```bash
# Watch Laravel logs
tail -f storage/logs/laravel.log

# Watch mail logs
tail -f storage/logs/mail.log

# Check queue workers
php artisan queue:work --verbose
```

---

## Rollback Plan

If the fix causes issues:

```bash
# Revert cache to database
sed -i 's/^CACHE_STORE=.*/CACHE_STORE=database/' .env

# Fix SQLite permissions
chmod 666 database/database.sqlite
chmod 777 database/

# Clear caches
php artisan config:clear
php artisan cache:clear
```

---

## Support

If login still fails after this fix:

1. Check latest logs: `tail -100 storage/logs/laravel.log`
2. Verify `.env` cache setting: `grep CACHE_STORE .env`
3. Test cache manually: `php artisan tinker` → `Cache::put('test', 'value')`
4. Check Redis/file permissions

---

## Files Modified

- `.env` - Cache driver configuration
- `storage/` - Permissions fixed
- `bootstrap/cache/` - Permissions fixed

## Files Created

- `fix-production-cache.sh` - Automated fix script
- `PRODUCTION_FIX_GUIDE.md` - This guide
- `SECURITY_PRIORITIES.md` - Next steps (see below)
