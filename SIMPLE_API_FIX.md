# Simple API Fix (No CloudPanel/Nginx Changes)

## Problem
API was working yesterday, now returns 302 redirect. CloudPanel vhost config hasn't changed.

## Root Cause
Most likely: **Corrupted route cache** from running `php artisan config:cache` or `php artisan optimize`.

## Solution (Run on Production Server)

### Option 1: Quick Fix (30 seconds)

```bash
# SSH into server
ssh root@ubuntu-16gb-hel1-1

# Navigate to project
cd /home/togetherpay/htdocs/www.togetherpay.app

# Clear ALL caches (this fixes 90% of cases)
php artisan optimize:clear

# Rebuild only what's needed
php artisan config:cache

# Test API
curl -X POST https://www.togetherpay.app/api/v1/auth/login \
  -H 'Content-Type: application/json' \
  -d '{"email":"test@test.com","password":"pass"}' \
  -s -o /dev/null -w "HTTP Status: %{http_code}\n"
```

**Expected result**: `HTTP Status: 422` (validation error - this is GOOD!)

---

### Option 2: Detailed Fix (if Option 1 doesn't work)

```bash
# Upload and run the diagnostic script
chmod +x fix-api-routes.sh
./fix-api-routes.sh
```

This will:
1. Check APP_URL configuration
2. Clear all caches individually
3. Verify routes are registered
4. Test the API
5. Rebuild caches
6. Show you exactly what's wrong

---

## Why This Happens

When you run `php artisan optimize` or cache configuration, Laravel sometimes:
- Caches routes that don't exist yet
- Applies middleware in wrong order
- Uses old APP_URL from cached config

**The fix**: Clear everything and rebuild fresh.

---

## After Running the Fix

### Test from mobile app:
1. Open TogetherPay app
2. Try to login
3. Should work! ✅

### If STILL getting 302:

Check these in order:

1. **APP_URL in .env**:
   ```bash
   grep "^APP_URL=" .env
   ```
   Should be: `APP_URL=https://www.togetherpay.app`

2. **Routes are registered**:
   ```bash
   php artisan route:list --path=api/v1/auth
   ```
   Should show login/register routes

3. **No global redirect middleware**:
   ```bash
   grep -r "ForceHttpsMiddleware\|RedirectToHttps" app/Http/Middleware/
   ```
   Should return nothing (you don't have this)

4. **Check Laravel logs**:
   ```bash
   tail -50 storage/logs/laravel.log
   ```
   Look for middleware errors

---

## What Changed Yesterday?

Most likely one of these happened:
- Someone ran `php artisan optimize` on production
- Config cache got corrupted during deployment
- Cache directory permissions changed
- `.env` file was modified

**The good news**: All of these are fixed by clearing caches!

---

## Prevention

To prevent this in the future, always run these commands in order when deploying:

```bash
php artisan optimize:clear  # Clear everything first
php artisan migrate --force  # Run migrations
php artisan config:cache    # Only cache config
# DON'T run: php artisan route:cache (can cause issues)
# DON'T run: php artisan optimize (does too much)
```
