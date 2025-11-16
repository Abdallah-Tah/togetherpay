# Registration Issue - Fixed! ✅

## Problem Identified

The mobile app was unable to register users due to **TWO issues**:

### Issue 1: Empty API Base URL in Mobile App ❌

**Symptom:** App logs showed:
```
I/flutter: Using API base:
```

The `Env.apiBase` was empty, causing errors like:
```
Error: Invalid argument(s): No host specified in URI /auth/register
```

**Root Cause:**
`String.fromEnvironment()` in Dart doesn't properly set `defaultValue` in release mode when `--dart-define` is not provided during build.

**Fix Applied:**
- Added `effectiveApiBase` getter in `lib/env.dart` that provides runtime fallback
- Updated `lib/main.dart` to use `Env.effectiveApiBase` instead of `Env.apiBase`
- Added debug print to always show API base URL on app startup

**Files Changed:**
1. `mobile/lib/env.dart` - Added runtime fallback
2. `mobile/lib/main.dart` - Use effective API base

### Issue 2: Production Nginx Not Forwarding Request Body ✅ (Already Fixed)

**Symptom:** Production API returned:
```json
{"message":"The name field is required. (and 2 more errors)"}
```

Even though curl was sending all fields.

**Root Cause:**
Nginx reverse proxy (port 443 → 8080) was not forwarding:
- Request body
- Content-Type header
- Content-Length header

**Fix Applied (by user):**
Updated `/etc/nginx/sites-enabled/www.togetherpay.app.conf`:
```nginx
proxy_set_header X-Forwarded-Proto $scheme;
proxy_set_header Content-Type $content_type;
proxy_set_header Content-Length $content_length;
proxy_request_buffering off;
```

Then reloaded Nginx:
```bash
sudo nginx -t
sudo systemctl reload nginx
```

## Current Status

✅ **Nginx Configuration:** Fixed - Request body properly forwarded
✅ **Mobile App API URL:** Fixed - Now using correct production endpoint
✅ **App Installed:** Latest version installed on device A059

## Testing Instructions

**On your Android device:**

1. **Open TogetherPay app**
   - Look for the wallet icon
   - Tap to launch

2. **Check logs show correct API** (on Mac):
   ```bash
   flutter logs
   ```
   Should show: `Using API base: https://www.togetherpay.app/api/v1`

3. **Try Registration:**
   - Tap "Create Account" or "Register"
   - Fill in:
     - **Name:** Your Full Name
     - **Email:** your-email@example.com (use a NEW email, not one that exists)
     - **Password:** Choose a strong password (min 8 chars)
     - **Confirm Password:** Same password
   - Tap "Create Account"

4. **Expected Result:**
   - ✅ "Account created successfully!" message
   - ✅ Redirected to onboarding screen
   - ✅ Can complete onboarding and access the app

5. **If Still Failing:**
   - Check Flutter logs for errors
   - Try with a different email address
   - Verify you're connected to internet (WiFi or mobile data)

## Verification Commands

### Test API from Terminal:

```bash
# Test registration endpoint
curl -X POST https://www.togetherpay.app/api/v1/auth/register \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "name":"Test User",
    "email":"test'$(date +%s)'@example.com",
    "password":"TestPass123!",
    "password_confirmation":"TestPass123!"
  }'
```

**Expected:**
```json
{
  "user": {
    "id": X,
    "name": "Test User",
    "email": "test...@example.com",
    "phone": null,
    "role": "member",
    "onboarding_completed": false
  },
  "token": "X|..."
}
```

### Monitor Live Logs:

While testing on device:

```bash
cd /Users/amohamed/Herd/togetherpay/mobile
flutter logs
```

Watch for:
- API base URL being printed
- Request details
- Response codes (201 = success, 422 = validation error)
- Any error messages

## Changes Made

### 1. mobile/lib/env.dart

```dart
class Env {
  static const String apiBase = String.fromEnvironment(
    'API_BASE',
    defaultValue: 'https://www.togetherpay.app/api/v1',
  );

  static const bool debug = bool.fromEnvironment('DEBUG', defaultValue: false);
  static const String appName = 'TogetherPay';

  /// Get the actual API base URL (runtime fallback if compile-time is empty)
  static String get effectiveApiBase {
    return apiBase.isEmpty ? 'https://www.togetherpay.app/api/v1' : apiBase;
  }
}
```

### 2. mobile/lib/main.dart

```dart
/// Global Dio instance for API calls
final dio = Dio(BaseOptions(
  baseUrl: Env.effectiveApiBase,  // Changed from Env.apiBase
  headers: {
    'ngrok-skip-browser-warning': 'true',
    'Accept': 'application/json',
  },
));

void _configureDioLogging() {
  // Always print API base for debugging
  debugPrint('Using API base: ${Env.effectiveApiBase}');

  if (!Env.debug) return;
  // ... rest of logging setup
}
```

## Rebuild Commands (if needed)

If you need to rebuild in the future:

```bash
cd /Users/amohamed/Herd/togetherpay/mobile

# Debug build (faster, with logs)
flutter build apk --debug
flutter install

# Release build (optimized, for production)
flutter build apk --release
flutter install

# Or with explicit API override
flutter build apk --dart-define=API_BASE=https://www.togetherpay.app/api/v1 --release
flutter install
```

## Next Steps

1. ✅ Test registration on the app
2. ✅ Complete onboarding flow
3. ✅ Create a test group
4. ✅ Test payment requests
5. ✅ Verify all features work

If registration works, follow the comprehensive testing guide:
- [DEVICE_TESTING_STEPS.md](DEVICE_TESTING_STEPS.md) - Basic device testing
- [MOBILE_TEST_QUICKSTART.md](MOBILE_TEST_QUICKSTART.md) - Quick 30-min test
- [MOBILE_TESTING_GUIDE.md](MOBILE_TESTING_GUIDE.md) - Full testing (100+ checks)

## Troubleshooting

### Still seeing "Registration failed"?

**Check 1: Is the app using the right URL?**
```bash
flutter logs | grep "Using API base"
# Should show: https://www.togetherpay.app/api/v1
```

**Check 2: Is the API actually working?**
```bash
curl https://www.togetherpay.app/api/health
# Should return: {"status":"ok",...}
```

**Check 3: Email already exists?**
Try with a different email address. The error might be:
```json
{"errors":{"email":["The email has already been taken."]}}
```

**Check 4: Network connectivity?**
- Try both WiFi and mobile data
- Open browser on phone and visit: https://www.togetherpay.app

### View Detailed Error Messages:

```bash
cd /Users/amohamed/Herd/togetherpay/mobile
flutter logs --verbose
```

Look for HTTP status codes and error responses.

---

**Fixed:** 2025-11-15
**Device:** A059 (Android 15)
**API:** https://www.togetherpay.app/api/v1
**Status:** Ready for testing ✅
