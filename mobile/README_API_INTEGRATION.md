# 🎯 TogetherPay Mobile App - API Integration Summary

## ✅ Your Mobile App is Correctly Implemented!

Your Flutter app uses proper environment-based configuration and follows best practices.

---

## 🔴 **ONE CRITICAL FIX REQUIRED IN YOUR LARAVEL BACKEND**

### The Issue

The endpoint `GET /api/v1/requests/by-code/{prCode}` is currently **protected** (requires authentication), but your mobile app needs it to be **public**.

**Why?** Members receive push notifications with payment links. When they tap "Pay Now", they are NOT logged in. The request fails with 401 Unauthorized.

### The Fix (2 minutes)

In your Laravel `routes/api.php`, **move this one line**:

**FROM** (protected section):
```php
Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    Route::get('/requests/by-code/{prCode}', [PaymentRequestController::class, 'showByCode']);
    // ...
});
```

**TO** (public section):
```php
Route::prefix('v1')->group(function () {
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login', [AuthController::class, 'login']);
    Route::post('/devices/register', [DeviceController::class, 'register']);
    
    // ✅ ADD THIS LINE HERE
    Route::get('/requests/by-code/{prCode}', [PaymentRequestController::class, 'showByCode']);
    
    Route::post('/requests/by-code/{prCode}/mark', [PaymentRequestController::class, 'markByCode']);
});
```

**Security:** This is safe because the PR code acts as the authentication mechanism (like a magic link).

---

## 📋 Complete API Compatibility Checklist

| Endpoint | Method | Your API | Status |
|----------|--------|----------|--------|
| `/auth/register` | POST | ✅ Public | Perfect |
| `/auth/login` | POST | ✅ Public | Perfect |
| `/auth/logout` | POST | ✅ Protected | Perfect |
| `/auth/me` | GET | ✅ Protected | Perfect |
| `/groups` | GET | ✅ Protected | Perfect |
| `/groups/{id}` | GET | ✅ Protected | Perfect |
| `/groups/{group}/members/{member}/payment-requests` | POST | ✅ Protected | **New – manual send** |
| `/payment-requests` | GET | ✅ Protected | Perfect |
| `/devices/register` | POST | ✅ Public | Perfect |
| `/requests/by-code/{prCode}` | GET | ⚠️ Protected | **Needs Fix** |
| `/requests/by-code/{prCode}/mark` | POST | ✅ Public | Perfect |

---

## 🚀 How to Run Your App with ngrok

### 1. Start ngrok (if not running)
```bash
ngrok http 8000 --host-header=rewrite
```

### 2. Run your Flutter app
```bash
flutter run --dart-define=API_BASE=https://georgiann-semiacademic-never.ngrok-free.dev/api/v1
```

### 3. Test the API (optional)
```bash
# Make test_api.sh executable first
chmod +x test_api.sh

# Run tests
./test_api.sh https://georgiann-semiacademic-never.ngrok-free.dev/api/v1
```

---

## 🔧 Laravel Configuration for ngrok

### CORS Configuration (`config/cors.php`)
```php
'allowed_origins' => [
    'http://localhost:8000',
    'https://georgiann-semiacademic-never.ngrok-free.dev',
    'https://*.ngrok-free.dev', // Allow any ngrok domain
],

'allowed_origins_patterns' => [
    '/^https:\/\/.*\.ngrok-free\.dev$/',
],
```

### Trust Proxies (`app/Http/Middleware/TrustProxies.php`)
```php
protected $proxies = '*';
```

### ngrok Command
```bash
# Use host-header rewrite to avoid Laravel routing issues
ngrok http 8000 --host-header=rewrite
```

---

## 📱 Expected API Response Formats

Your `PaymentRequestController@showByCode` should return:

```json
{
  "id": 123,
  "pr_code": "PR_2025-10_G42_M17_abc123",
  "code": "PR_2025-10_G42_M17_abc123",
  "group": {
    "id": 42,
    "name": "T-Mobile Family Plan",
    "owner": {
      "id": 1,
      "name": "John Doe",
      "cash_tag": "$JohnDoe"
    }
  },
  "amount_cents": 5000,
  "amount_display": "$50.00",
  "status": "sent",
  "method_hint": "cashapp",
  "note": "T-Mobile Oct 2025 cycle",
  "deep_link": "https://cash.app/$JohnDoe/50.00?note=PR_2025-10_G42_M17_abc123",
  "web_fallback_link": "https://cash.app/$JohnDoe"
}
```

**Important fields the mobile app uses:**
- `code` or `pr_code` - Payment request code
- `deep_link` - Opens payment app directly
- `web_fallback_link` - Backup if deep link fails
- `amount_display` - Formatted amount for display
- `group.name` - Group name
- `group.owner.name` - Owner name
- `group.owner.cash_tag` - Owner's payment handle

---

### New: Manual Payment Request Endpoint

Owners can now send an ad-hoc payment link to any verified member without waiting for the nightly cycle job.

`POST /api/v1/groups/{group}/members/{member}/payment-requests`

```bash
curl -X POST https://georgiann-semiacademic-never.ngrok-free.dev/api/v1/groups/42/members/17/payment-requests \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d '{
    "amount": 45.50,
    "note": "T-Mobile add-on – Nov 12",
    "send_now": true
  }'
```

- Supply either `amount` (USD) or `amount_cents`  
- Optional `cycle_id` ties the request back to an existing billing cycle  
- `send_now` defaults to `true`; set to `false` to create the record without sending notifications yet  
- Response mirrors `GET /requests/by-code/{prCode}` so the mobile app can present the payment screen immediately.

---

## 🧪 Testing Flow

1. **Backend Fix**
   - Move route to public section ✓
   - Restart Laravel server ✓

2. **Test API Directly**
   ```bash
   # Should return 200 OK (not 401)
   curl https://georgiann-semiacademic-never.ngrok-free.dev/api/v1/requests/by-code/PR_TEST
   ```

3. **Test Mobile App**
   ```bash
   flutter run --dart-define=API_BASE=https://georgiann-semiacademic-never.ngrok-free.dev/api/v1
   ```

4. **Test Payment Flow**
   - Tap a payment request in the app
   - Should navigate to PayScreen
   - Should load payment details
   - Should auto-launch payment app

---

## 📂 Files Created for Reference

1. **`LARAVEL_FIX_REQUIRED.md`** - Detailed fix instructions
2. **`API_Analysis_And_Fixes.md`** - Complete API analysis
3. **`test_api.sh`** - Automated API testing script

---

## ✅ Quick Action Checklist

- [ ] Fix Laravel route (move to public section)
- [ ] Restart Laravel server
- [ ] Configure CORS for ngrok domain
- [ ] Test with curl: `curl https://your-ngrok.dev/api/v1/requests/by-code/PR_TEST`
- [ ] Run Flutter app with ngrok URL
- [ ] Test login flow in app
- [ ] Test payment request flow in app

---

## 🎉 After These Changes

Your mobile app will work perfectly with your Laravel API! The only change needed is moving one route from protected to public.

---

## 💡 Additional Notes

### Why This Architecture is Correct

1. **Environment-based config** - Allows different API URLs for dev/staging/prod
2. **Sanctum auth** - Proper token-based authentication
3. **Secure storage** - Auth tokens stored securely
4. **PR code auth** - Members can pay without accounts (like Venmo/CashApp request links)

### Production Deployment

When deploying to production:

```bash
# Android
flutter build apk --release --dart-define=API_BASE=https://api.togetherpay.app/api/v1

# iOS
flutter build ios --release --dart-define=API_BASE=https://api.togetherpay.app/api/v1
```

Replace with your actual production API URL.
