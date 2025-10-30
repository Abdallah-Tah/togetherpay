# ⚠️ Laravel Backend Fix Required

## Critical Issue: Payment Request Endpoint Authentication

### The Problem

Your mobile app's `PayScreen` (lib/pay_screen.dart:69) calls:
```dart
final response = await dio.get('/requests/by-code/${widget.prCode}');
```

But this endpoint requires authentication in your Laravel API:
```php
// Currently in protected routes ❌
Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    Route::get('/requests/by-code/{prCode}', [PaymentRequestController::class, 'showByCode']);
});
```

### Why This Breaks

When members receive a push notification and tap "Pay Now", they are NOT logged in. The request fails with 401 Unauthorized.

---

## ✅ The Fix

### In your Laravel `routes/api.php`:

**MOVE** this route from the protected section to the public section:

```php
// Public routes (no auth required)
Route::prefix('v1')->group(function () {
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login', [AuthController::class, 'login']);
    Route::post('/devices/register', [DeviceController::class, 'register']);
    
    // ✅ ADD THIS LINE (move from protected section)
    Route::get('/requests/by-code/{prCode}', [PaymentRequestController::class, 'showByCode']);
    
    Route::post('/requests/by-code/{prCode}/mark', [PaymentRequestController::class, 'markByCode']);
});

// Protected routes
Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    // ❌ REMOVE from here
    // Route::get('/requests/by-code/{prCode}', [PaymentRequestController::class, 'showByCode']);
    
    // ... rest of your protected routes
});
```

---

## 🔒 Security Note

**Is this safe?**

✅ **Yes!** The PR code acts as the authentication mechanism:
- PR codes are unique, unguessable strings (e.g., `PR_2025-10_G42_M17_a3f9c2`)
- Only the member who receives the push notification has the code
- Similar to how password reset links work - the token IS the auth

This is the **standard pattern** for "magic link" or "secure code" based access.

---

## 🧪 Test After Fix

```bash
# Test without auth token (should work)
curl https://georgiann-semiacademic-never.ngrok-free.dev/api/v1/requests/by-code/PR_TEST

# Should return 200 OK with payment request details
```

---

## 📋 Complete Updated routes/api.php Structure

```php
<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\DeviceController;
use App\Http\Controllers\API\GroupController;
use App\Http\Controllers\API\PaymentRequestController;
use App\Http\Controllers\CashAppWebhookController;
use App\Http\Controllers\SmartPayController;
use Illuminate\Support\Facades\Route;

// Webhooks (no auth required)
Route::post('/webhooks/cashapp', [CashAppWebhookController::class, 'handle'])
    ->name('webhooks.cashapp');

// Public routes
Route::prefix('v1')->group(function () {
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login', [AuthController::class, 'login']);
    
    // Device registration (allows both authenticated and guest users)
    Route::post('/devices/register', [DeviceController::class, 'register']);
    
    // ✅ MOVED - Public payment request lookup by code
    Route::get('/requests/by-code/{prCode}', [PaymentRequestController::class, 'showByCode']);
    
    // Member marks payment as sent (public endpoint - auth by PR code)
    Route::post('/requests/by-code/{prCode}/mark', [PaymentRequestController::class, 'markByCode']);
});

// Protected routes
Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);
    
    // Groups
    Route::apiResource('groups', GroupController::class);
    Route::post('/groups/{group}/members', [GroupController::class, 'addMember']);
    Route::put('/groups/{group}/members/{member}', [GroupController::class, 'updateMember']);
    Route::delete('/groups/{group}/members/{member}', [GroupController::class, 'removeMember']);
    
    // Payment Requests (Existing endpoints)
    Route::get('/payment-requests', [PaymentRequestController::class, 'index']);
    Route::get('/payment-requests/stats', [PaymentRequestController::class, 'stats']);
    Route::get('/payment-requests/{paymentRequest}', [PaymentRequestController::class, 'show']);
    Route::post('/payment-requests/{paymentRequest}/mark-paid', [PaymentRequestController::class, 'markPaid']);
    Route::post('/payment-requests/{paymentRequest}/retry', [PaymentRequestController::class, 'retry']);
    Route::get('/cycles/{cycle}/payment-requests', [PaymentRequestController::class, 'byCycle']);
    
    // Payment Requests V2 (PR Code based endpoints - authenticated)
    Route::post('/requests/by-code/{prCode}/confirm', [PaymentRequestController::class, 'confirmPayment']);
    Route::post('/requests/by-code/{prCode}/nudge', [PaymentRequestController::class, 'nudge']);
    Route::post('/cycles/{cycleId}/bulk-confirm', [PaymentRequestController::class, 'bulkConfirm']);
});

// Mark payment as paid (public endpoint with optional auth)
Route::post('/requests/{id}/mark-paid', [SmartPayController::class, 'markPaid'])
    ->name('api.mark-paid');
```

---

## ✅ After Making This Change

Your mobile app will work correctly with your API!

Run the Flutter app:
```bash
flutter run --dart-define=API_BASE=https://georgiann-semiacademic-never.ngrok-free.dev/api/v1
```

