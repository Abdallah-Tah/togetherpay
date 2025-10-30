# API Flow Diagram - Before & After Fix

## ❌ CURRENT STATE (Broken)

```
Member receives push notification
         |
         | Taps "Pay Now"
         |
         v
Mobile App (PayScreen)
         |
         | GET /requests/by-code/PR_XXX
         | (No auth token - user not logged in)
         |
         v
Laravel API - Protected Route 🔒
         |
         | 401 Unauthorized ❌
         |
         v
App shows error: "Unable to load payment request"
```

**Problem:** Members can't view payment requests without logging in first.

---

## ✅ AFTER FIX (Working)

```
Member receives push notification
         |
         | Taps "Pay Now"
         |
         v
Mobile App (PayScreen)
         |
         | GET /requests/by-code/PR_XXX
         | (No auth token needed)
         |
         v
Laravel API - Public Route 🌐
         |
         | 200 OK ✅
         | Returns payment details
         |
         v
App shows payment details
         |
         | Auto-launches Cash App
         |
         v
Member completes payment
         |
         | Returns to app
         |
         v
App shows confirmation dialog
         |
         | Member confirms payment
         |
         v
App sends: POST /requests/by-code/PR_XXX/mark
         |
         v
Payment marked as paid ✅
```

**Result:** Seamless payment flow without login friction!

---

## 🔄 Complete App Flow

### Scenario 1: Group Owner (Authenticated User)

```
Owner logs in
     |
     | POST /auth/login
     | Returns: { token: "..." }
     |
     v
Token stored securely
     |
     v
All API calls include:
Authorization: Bearer <token>
     |
     |-----> GET /groups (list groups)
     |-----> GET /payment-requests (view all requests)
     |-----> POST /groups (create group)
     |-----> POST /groups/{id}/members (add members)
```

### Scenario 2: Group Member (Guest User via Push Notification)

```
Member receives push notification
     |
     | Opens app via deep link
     | togetherpay://pay/PR_2025-10_G1_M1_abc
     |
     v
PayScreen loads
     |
     | GET /requests/by-code/PR_2025-10_G1_M1_abc
     | (No auth required - PR code IS the auth)
     |
     v
Shows payment details
     |
     | Opens Cash App
     |
     v
Member pays $50
     |
     | Returns to app
     |
     v
Confirmation dialog
     |
     | POST /requests/by-code/PR_2025-10_G1_M1_abc/mark
     | (No auth required - PR code IS the auth)
     |
     v
Payment confirmed ✅
```

**Key Point:** Members never need to login! The PR code acts as authentication.

---

## 🔐 Security Model

### Traditional Auth (Login Required)
```
Request → Check JWT token → Verify user → Allow/Deny
```

### PR Code Auth (Magic Link Pattern)
```
Request → Check PR code exists → Verify not expired → Allow/Deny
```

**Examples of PR Code Auth in the wild:**
- Password reset links
- Email verification links
- Venmo/CashApp payment requests
- Meeting invite links (Zoom, Google Meet)

**Why it's secure:**
- PR codes are unique, unguessable strings
- Only the intended recipient has the code
- Codes can expire
- Actions are limited (view/mark payment only)

---

## 📊 API Endpoint Categories

### 1. Public Endpoints (No Auth)
- `POST /auth/register` - Anyone can create account
- `POST /auth/login` - Get auth token
- `POST /devices/register` - Register for push notifications
- `GET /requests/by-code/{prCode}` - View payment request ✅ **Need to fix**
- `POST /requests/by-code/{prCode}/mark` - Mark as paid

### 2. Protected Endpoints (Auth Required)
- `POST /auth/logout` - Logout current user
- `GET /auth/me` - Get current user profile
- `GET /groups` - List user's groups
- `POST /groups` - Create new group
- `GET /payment-requests` - List user's requests
- All other group/payment management endpoints

---

## 🎯 The One-Line Fix

In `routes/api.php`, move this line:

**From:**
```php
// Protected routes ❌
Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    Route::get('/requests/by-code/{prCode}', [...]);
```

**To:**
```php
// Public routes ✅
Route::prefix('v1')->group(function () {
    Route::get('/requests/by-code/{prCode}', [...]);
```

That's it! One line moved = entire payment flow works perfectly.

---

## 🧪 How to Verify the Fix

### Before Fix:
```bash
$ curl https://your-api.dev/api/v1/requests/by-code/PR_TEST
{"message":"Unauthenticated."}  # ❌ 401 Error
```

### After Fix:
```bash
$ curl https://your-api.dev/api/v1/requests/by-code/PR_TEST
{
  "id": 123,
  "pr_code": "PR_TEST",
  "amount_display": "$50.00",
  ...
}  # ✅ 200 OK
```

---

## 📱 Mobile App Code (Already Correct!)

**lib/pay_screen.dart:69**
```dart
// Fetch payment request details from API
final response = await dio.get('/requests/by-code/${widget.prCode}');
```

**lib/pay_screen.dart:205**
```dart
// Confirm payment
await dio.post(
  '/requests/by-code/${widget.prCode}/mark',
  data: {
    'evidence_url': null,
    'evidence_type': null,
  },
);
```

**No changes needed in mobile app!** Just fix the Laravel route.

---

## ✅ Success Checklist

- [ ] Move route to public section in `routes/api.php`
- [ ] Restart Laravel server
- [ ] Test with curl (should return 200, not 401)
- [ ] Run mobile app with ngrok URL
- [ ] Test payment flow in app
- [ ] Celebrate! 🎉

