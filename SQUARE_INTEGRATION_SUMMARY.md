# Square Integration Implementation Summary

## Overview

This document outlines the completed implementation of the Square integration for TogetherPay, enabling automatic recurring payment collection through Square + Cash App Pay alongside the existing manual deep-link payment system.

**Key Principle**: Two-mode approach where existing manual flow remains untouched, with all new Square logic isolated behind `billing_mode === 'auto_square'` checks.

---

## 1. Database Schema Changes

### New Migrations (Created)

#### 1. `add_billing_mode_to_groups_table`
- **Column**: `billing_mode` (ENUM: 'manual', 'auto_square')
- **Default**: 'manual' (backward compatible)
- **Purpose**: Determine which payment collection mode each group uses

#### 2. `add_square_fields_to_users_table`
- **Columns**:
  - `square_merchant_id` - OAuth merchant ID from Square
  - `square_access_token` - Encrypted API token (stored encrypted)
  - `square_location_id` - Primary Square location
  - `square_connected_at` - Timestamp of OAuth completion
- **Purpose**: Store owner's Square OAuth credentials

#### 3. `add_square_fields_to_group_members_table`
- **Columns**:
  - `square_customer_id` - Square customer record ID
  - `square_subscription_id` - Recurring subscription ID
  - `square_subscription_created_at` - When subscription was activated
- **Purpose**: Link group members to Square customer & subscription entities

#### 4. `add_square_fields_to_payment_requests_table`
- **Columns**:
  - `square_invoice_id` - Invoice ID from Square (if applicable)
  - `square_payment_id` - Payment ID when Square processes payment
  - `square_confirmed_at` - Webhook confirmation timestamp
- **Purpose**: Track Square-side payment details for reconciliation

---

## 2. Model Updates

### User Model (`app/Models/User.php`)

**Fillable Fields Added**:
```php
'square_merchant_id',
'square_access_token',
'square_location_id',
'square_connected_at',
```

**Casts**:
```php
'square_connected_at' => 'datetime',
```

**New Methods**:
- `hasSquareAccount(): bool` - Check if user has Square credentials
- `isSquareConfigured(): bool` - Check if properly connected
- `disconnectSquare(): void` - Clear all Square fields

### Group Model (`app/Models/Group.php`)

**Fillable**:
```php
'billing_mode',
```

**New Methods**:
- `isManualMode(): bool` - Check if using manual deep-links
- `isAutoSquareMode(): bool` - Check if using auto-square
- `ownerHasSquare(): bool` - Check if owner has Square connected

### GroupMember Model (`app/Models/GroupMember.php`)

**Fillable**:
```php
'square_customer_id',
'square_subscription_id',
'square_subscription_created_at',
```

**Casts**:
```php
'square_subscription_created_at' => 'datetime',
```

**New Methods**:
- `hasSquareSubscription(): bool` - Check for subscription
- `isAutoSquareEnrolled(): bool` - Check if active

### PaymentRequest Model (`app/Models/PaymentRequest.php`)

**Fillable**:
```php
'square_invoice_id',
'square_payment_id',
'square_confirmed_at',
```

**Casts**:
```php
'square_confirmed_at' => 'datetime',
```

**New Methods**:
- `isSquareVerified(): bool` - Check if webhook confirmed
- `confirmViaSquare(string $paymentId): void` - Mark as auto_verified and update cycle collected amount

---

## 3. Configuration

### `config/payment_requests.php` - Square Section Added

```php
'square' => [
    'enabled' => env('SQUARE_ENABLED', false),
    'mode' => env('SQUARE_MODE', 'sandbox'),

    'oauth' => [
        'client_id' => env('SQUARE_CLIENT_ID'),
        'client_secret' => env('SQUARE_CLIENT_SECRET'),
        'redirect_uri' => env('SQUARE_OAUTH_CALLBACK_URL', 'http://localhost:8000/oauth/square/callback'),
        'scopes' => [
            'CUSTOMERS_READ', 'CUSTOMERS_WRITE',
            'INVOICES_READ', 'INVOICES_WRITE',
            'PAYMENTS_READ', 'PAYMENTS_WRITE',
        ],
    ],

    'api' => [
        'base_url' => 'https://connect.squareupsandbox.com',
        'version' => 'v2',
    ],

    'subscription' => [
        'interval_unit' => 'MONTH',
        'interval_count' => 1,
    ],

    'webhook' => [
        'signing_key' => env('SQUARE_WEBHOOK_SIGNING_KEY'),
        'endpoint' => 'https://togetherpay.local/webhooks/square',
    ],
],
```

**Required Environment Variables**:
```
SQUARE_ENABLED=false
SQUARE_MODE=sandbox
SQUARE_CLIENT_ID=your_client_id
SQUARE_CLIENT_SECRET=your_secret
SQUARE_OAUTH_CALLBACK_URL=http://localhost:8000/oauth/square/callback
SQUARE_WEBHOOK_SIGNING_KEY=your_signing_key
```

---

## 4. Services

### `app/Services/Square/SquareClientBuilder.php`

Factory for creating authenticated Square API clients:
- `build()` - Create client with default credentials
- `buildForUser($user)` - Create client with user's access token

### `app/Services/Square/SquareOAuthService.php`

Handles OAuth flow:
- `getAuthorizationUrl($state): string` - Generate OAuth URL
- `exchangeCodeForToken($code): array` - Exchange auth code for token
- `refreshAccessToken($refreshToken): array` - Refresh token
- `revokeAccessToken($accessToken): bool` - Disconnect
- `getMerchantInfo($accessToken): array` - Get merchant details

### `app/Services/Square/SquareCustomerService.php`

Manages Square customers:
- `createCustomerForMember($member, $owner): string` - Create customer, return ID
- `updateCustomer($customerId, $owner, $data): void` - Update customer info
- `deleteCustomer($customerId, $owner): void` - Delete customer
- `getCustomer($customerId, $owner): array` - Retrieve customer details

### `app/Services/Square/SquareSubscriptionService.php`

Manages recurring subscriptions:
- `createSubscription($member, $owner, $cycle): string` - Create subscription, return ID
- `cancelSubscription($subscriptionId, $owner): void` - Cancel subscription
- `updateSubscriptionAmount($subscriptionId, $owner, $amountCents): void` - Update price
- `getSubscription($subscriptionId, $owner): array` - Get subscription details
- `listCustomerSubscriptions($customerId, $owner): array` - List customer's subscriptions

### `app/Services/Square/SquareWebhookService.php`

Handles webhook events:
- `verifySignature($body, $signature, $timestamp): bool` - Validate webhook signature
- `handleInvoicePaymentMade($data): void` - Process payment webhook
- `handlePaymentUpdated($data): void` - Process payment status updates
- `handleSubscriptionUpdated($data): void` - Process subscription changes
- `handleWebhook($payload): void` - Router for all webhook types

---

## 5. Controllers

### `app/Http/Controllers/Square/SquareOAuthController.php`

Handles OAuth flow:
- `authorize()` - Redirect to Square OAuth
- `callback()` - Handle OAuth callback, store encrypted token
- `disconnect()` - Revoke token and clear Square fields

### `app/Http/Controllers/Square/SquareWebhookController.php`

Handles webhook callbacks:
- `handle(Request $request)` - Process and verify webhook

---

## 6. Routes

### Web Routes Added (in `routes/web.php`)

```php
// Square OAuth (authenticated)
Route::middleware('auth')->prefix('oauth/square')->name('oauth.square.')->group(function () {
    Route::get('authorize', '...SquareOAuthController@authorize')->name('authorize');
    Route::get('callback', '...SquareOAuthController@callback')->name('callback');
    Route::post('disconnect', '...SquareOAuthController@disconnect')->name('disconnect');
});

// Square webhooks (public, CSRF exempt)
Route::post('/webhooks/square', '...SquareWebhookController@handle')
    ->name('webhooks.square')
    ->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);
```

---

## 7. Scheduler Jobs

### `app/Jobs/CreateAutoSquareSubscriptionsJob.php`

Triggered by `GeneratePaymentRequestsJob` when cycle is for auto_square group:
- Creates Square Customer for each member (if not exists)
- Creates Square Subscription with member's calculated share amount
- Sets start date to billing cycle due date
- Handles errors gracefully (continues with other members)

### Integration Point

Updated `GeneratePaymentRequestsJob`:
```php
// After generating requests for a cycle:
if ($cycle->group->isAutoSquareMode()) {
    CreateAutoSquareSubscriptionsJob::dispatch($cycle);
}
```

**Execution Timeline**:
1. `CreateUpcomingCyclesJob` - Daily 01:10 - Creates draft cycles
2. `GeneratePaymentRequestsJob` - Daily 01:30 - Generates requests + dispatches auto-square subscriptions
3. `CreateAutoSquareSubscriptionsJob` - After generation - Creates/updates Square subscriptions

---

## 8. Flow Diagrams

### Group Creation Flow (Two Modes)

```
Owner creates group
    ↓
[CHOICE] Billing mode?
    ├─ Manual (existing) → billing_mode = 'manual'
    └─ Auto-Square → billing_mode = 'auto_square' + prompt to connect Square

Owner adds members
    ├─ If manual: Just store member info
    └─ If auto_square: Generate payment requests, trigger subscription creation
```

### Auto-Square Payment Flow

```
1. Billing cycle created (status='draft')
   ↓
2. GeneratePaymentRequestsJob runs
   ├─ Generates payment requests (same as manual)
   └─ If auto_square: Dispatches CreateAutoSquareSubscriptionsJob
   ↓
3. CreateAutoSquareSubscriptionsJob
   ├─ For each member:
   │  ├─ Create Square Customer (if not exists)
   │  └─ Create Square Subscription
   └─ Member has recurring billing set up
   ↓
4. Square charges member on due date (automatic)
   ↓
5. Square sends webhook payment.updated/invoice.payment_made
   ↓
6. SquareWebhookController receives webhook
   ├─ Verifies signature
   ├─ Routes to SquareWebhookService
   └─ PaymentRequest::confirmViaSquare() called
   ↓
7. Payment marked 'auto_verified'
   └─ Cycle.total_collected_cents incremented
```

### Manual Mode (Unchanged)

```
Existing flow remains identical:
1. GeneratePaymentRequestsJob → creates payment requests
2. SendPaymentRequestsJob → notifies members
3. Member marks as paid
4. Owner confirms receipt
5. Status: owner_confirmed
```

---

## 9. Status Values

Payment requests now support additional status for auto-square:

```php
'pending'           // Created but not sent
'sent'              // Notification sent (manual only)
'member_marked'     // Member marked as paid (manual only)
'owner_confirmed'   // Owner confirmed (manual flow completed)
'auto_verified'     // Square webhook confirmed (auto_square flow completed) ← NEW
'failed'            // Payment failed
'expired'           // Request expired
```

**Key**: `auto_verified` replaces the manual two-step confirmation for Square payments.

---

## 10. Security Considerations

### Token Encryption

Square access tokens are encrypted in database:
```php
// Storage
'square_access_token' => Crypt::encryptString($tokenResponse['access_token']),

// Usage
SquareClientBuilder::buildForUser($owner)
// Internally decrypts token for API calls
```

### Webhook Verification

All Square webhooks must pass signature verification:
```php
$valid = $this->webhookService->verifySignature(
    $body,
    $header['X-Square-HmacSHA256-Signature'],
    $header['X-Square-HmacSHA256-Timestamp']
);
```

### CSRF Exemption

Webhook endpoint is CSRF-exempt (required for webhook handling):
```php
Route::post('/webhooks/square', ...)
    ->withoutMiddleware(VerifyCsrfToken::class);
```

### Rate Limiting

Consider adding rate limiting to OAuth endpoint:
```php
Route::middleware('throttle:10,1')->prefix('oauth/square')...
```

---

## 11. What's NOT Changed

**Existing Manual Flow**:
- ✅ `PaymentRequestService` - untouched
- ✅ `GeneratePaymentRequestsJob` - only added conditional dispatch
- ✅ `SendPaymentRequestsJob` - untouched
- ✅ All payment drivers (Venmo, Cash App) - untouched
- ✅ Deep-link generation - untouched
- ✅ Manual confirmation flow - untouched
- ✅ All existing UI components - untouched

**Backward Compatibility**:
- All groups default to `billing_mode = 'manual'`
- Square fields are nullable
- No breaking changes to existing APIs

---

## 12. Next Steps (UI Implementation)

The backend is now ready. UI implementation still needed:

### Phase 1: Settings Page
- [ ] Show "Square Account Status" on settings.profile
- [ ] "Connect Square" button → redirects to `oauth.square.authorize`
- [ ] "Disconnect Square" button → POST to `oauth.square.disconnect`
- [ ] Display connected merchant ID & location

### Phase 2: Group Creation
- [ ] Add step in group creation wizard: "How do you want to collect money?"
- [ ] Radio choice: Manual vs Auto-Square
- [ ] If Auto-Square: Check if owner has Square connected
- [ ] If not: Show "Connect Square first" message with link

### Phase 3: Group Dashboard
- [ ] Add "Billing Mode" badge (Manual / Auto-Square)
- [ ] For auto-square members, show subscription status
- [ ] Status indicators:
  - 🟢 Active (subscription created)
  - 🟡 Pending (subscription creation in progress)
  - 🔴 Failed (subscription creation failed)

### Phase 4: Member View
- [ ] Show payment status differently:
  - Manual: "Pending member confirmation" → "Awaiting owner review" → "Confirmed"
  - Auto: "Auto-charge pending" → "Auto-charged (auto_verified)"

---

## 13. Testing

### Unit Tests to Write
- [ ] SquareOAuthService::exchangeCodeForToken
- [ ] SquareCustomerService::createCustomerForMember
- [ ] SquareSubscriptionService::createSubscription
- [ ] SquareWebhookService::verifySignature
- [ ] PaymentRequest::confirmViaSquare

### Feature Tests to Write
- [ ] OAuth flow: authorize → callback
- [ ] Webhook handling with signature verification
- [ ] Subscription creation for members
- [ ] Payment confirmation via webhook
- [ ] Group mode enforcement (manual vs auto_square)

### Integration Tests
- [ ] End-to-end: Group creation → Member add → Subscription creation → Webhook → Payment confirmed
- [ ] Manual mode still works when auto_square mode disabled
- [ ] Webhook rejection with invalid signature

---

## 14. Environment Setup

### .env Configuration

```bash
# Square Integration
SQUARE_ENABLED=false              # Set to true when ready
SQUARE_MODE=sandbox               # 'sandbox' or 'production'
SQUARE_CLIENT_ID=sq_sandbox_...
SQUARE_CLIENT_SECRET=sq_...
SQUARE_OAUTH_CALLBACK_URL=http://localhost:8000/oauth/square/callback
SQUARE_WEBHOOK_SIGNING_KEY=sig_...
```

### Local Testing

1. Create Square Developer Account: https://developer.squareup.com
2. Create test application
3. Get credentials from: https://developer.squareup.com/apps
4. Copy credentials to `.env`
5. Test OAuth flow with sandbox account

---

## 15. File Structure

```
app/
├── Services/Square/
│   ├── SquareClientBuilder.php
│   ├── SquareOAuthService.php
│   ├── SquareCustomerService.php
│   ├── SquareSubscriptionService.php
│   └── SquareWebhookService.php
│
├── Http/Controllers/Square/
│   ├── SquareOAuthController.php
│   └── SquareWebhookController.php
│
├── Jobs/
│   └── CreateAutoSquareSubscriptionsJob.php
│
└── Models/
    ├── User.php (updated)
    ├── Group.php (updated)
    ├── GroupMember.php (updated)
    └── PaymentRequest.php (updated)

database/migrations/
├── 2025_11_10_115656_add_billing_mode_to_groups_table.php
├── 2025_11_10_115657_add_square_fields_to_users_table.php
├── 2025_11_10_115658_add_square_fields_to_group_members_table.php
└── 2025_11_10_115659_add_square_fields_to_payment_requests_table.php

config/
└── payment_requests.php (updated with square config)

routes/
└── web.php (updated with oauth & webhook routes)
```

---

## Summary

✅ **Completed**:
- Database schema for all Square entities
- Model updates with helper methods
- OAuth service and controller
- Customer and subscription services
- Webhook handling and verification
- Scheduler integration
- Routes and CSRF exemption
- Configuration system
- Full separation from manual flow

⏳ **Remaining** (UI/Frontend):
- Settings page Square account management
- Group creation wizard mode selection
- Dashboard badges and status displays
- Member view enhancements

The implementation follows the two-mode principle: manual mode is completely unchanged, and all Square logic is isolated behind `billing_mode === 'auto_square'` checks. The existing codebase remains stable and backward compatible.
