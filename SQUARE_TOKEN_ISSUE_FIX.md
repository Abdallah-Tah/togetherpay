# Square Token Issue - Root Cause & Fix

## Problem Summary
Your Square API token works in terminal `curl` commands but fails in the Laravel test class.

## Root Causes Identified

### 1. **Wrong Base URL Configuration**
**Issue:** Line 65 was using:
```php
config('payment_requests.square.base_url')
```

**Problem:** This config key doesn't exist! The config only has:
- `payment_requests.square.mode` (sandbox/production)
- `payment_requests.square.api.base_url`

**Result:** It was falling back to production URL (`https://connect.squareup.com`) but your token is for **sandbox**.

### 2. **URL Mismatch**
- **Terminal curl**: Uses `https://connect.squareupsandbox.com` ✅
- **Test class**: Was trying `https://connect.squareup.com` ❌
- **Your token**: Valid for **sandbox** only ✅

## The Fix Applied

### Changed from:
```php
->get(config('payment_requests.square.base_url') ?: 'https://connect.squareup.com/v2/locations');
```

### Changed to:
```php
// Use sandbox URL since this is a sandbox token
$baseUrl = config('payment_requests.square.mode') === 'production'
    ? 'https://connect.squareup.com'
    : 'https://connect.squareupsandbox.com';

$response = \Illuminate\Support\Facades\Http::withHeaders([
    'Square-Version' => '2025-01-23',
    'Authorization' => 'Bearer ' . $squareToken,
    'Content-Type' => 'application/json',
])->get($baseUrl . '/v2/locations');
```

## Why Your Terminal Command Worked

```bash
curl https://connect.squareupsandbox.com/v2/customers \
  -H 'Authorization: Bearer EAAAl4YRs9djONdfEAkmOourp_y5tP1ncA4Ls_FviBHCl_ExAlqGAfvDvDbt72l8'
```

✅ Correct sandbox URL
✅ Correct token format
✅ Direct API call (no config lookup)

## Why Your Test Class Failed

❌ Wrong URL (production instead of sandbox)
❌ Config lookup returning wrong value
❌ Token doesn't work on production endpoints

## Verification

Your `.env` has:
```env
SQUARE_ENVIRONMENT=sandbox
SQUARE_ACCESS_TOKEN=EAAAl4YRs9djONdfEAkmOourp_y5tP1ncA4Ls_FviBHCl_ExAlqGAfvDvDbt72l8
```

Now the test class will:
1. Read `SQUARE_ENVIRONMENT=sandbox` from config
2. Build the correct sandbox URL: `https://connect.squareupsandbox.com`
3. Use your sandbox token
4. Successfully authenticate ✅

## Additional Improvements Made

Added debug output to show:
```php
$this->line('Environment: ' . config('payment_requests.square.mode'));
$this->line('Config Token: ' . (config('payment_requests.square.access_token') ? 'SET' : 'NOT SET'));
```

And on success:
```php
$this->line('  Environment: ' . config('payment_requests.square.mode'));
$this->line('  Base URL: ' . $baseUrl);
```

## Test Now

Run your command again:
```bash
php artisan test:real-square-invoice test@example.com +12065559999 --force
```

You should see:
```
✓ Square API reachable and token looks valid.
  Environment: sandbox
  Base URL: https://connect.squareupsandbox.com
```

## Summary

The issue was **NOT** with your token or Square credentials. It was a simple configuration lookup bug that was using the wrong URL (production vs sandbox). Your terminal curl worked because it explicitly specified the sandbox URL.
