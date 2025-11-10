# Square Integration - Quick Start Guide

## For Developers

### 1. Run Migrations

```bash
php artisan migrate
```

This creates all Square-related columns:
- `groups.billing_mode`
- `users.square_*`
- `group_members.square_*`
- `payment_requests.square_*`

### 2. Configure Environment

Edit `.env`:

```env
SQUARE_ENABLED=false
SQUARE_MODE=sandbox
SQUARE_CLIENT_ID=your_id
SQUARE_CLIENT_SECRET=your_secret
SQUARE_OAUTH_CALLBACK_URL=http://localhost:8000/oauth/square/callback
SQUARE_WEBHOOK_SIGNING_KEY=your_signing_key
```

### 3. Create Test Group

```php
// Via tinker or test
$group = Group::factory()
    ->for(User::first(), 'owner')
    ->create([
        'billing_mode' => 'auto_square',
    ]);
```

### 4. Check Key Methods

User model:
```php
$user->hasSquareAccount()       // bool
$user->isSquareConfigured()     // bool
```

Group model:
```php
$group->isManualMode()          // bool
$group->isAutoSquareMode()      // bool
$group->ownerHasSquare()        // bool
```

GroupMember model:
```php
$member->hasSquareSubscription()    // bool
$member->isAutoSquareEnrolled()     // bool
```

PaymentRequest model:
```php
$request->isSquareVerified()    // bool
$request->confirmViaSquare($paymentId)
```

### 5. Test OAuth Flow

1. Start server: `php artisan serve`
2. Visit: `http://localhost:8000/oauth/square/authorize`
3. Redirect to Square (or sandbox login page)
4. After login, redirected back to callback
5. Token stored encrypted in `users.square_access_token`

### 6. Test Webhook

In terminal:

```bash
curl -X POST http://localhost:8000/webhooks/square \
  -H "Content-Type: application/json" \
  -H "X-Square-HmacSHA256-Signature: test_sig" \
  -H "X-Square-HmacSHA256-Timestamp: $(date +%s)" \
  -d '{
    "type": "invoice.payment_made",
    "data": {
      "object": {
        "invoice": {
          "id": "inv_123",
          "payment_requests": [{
            "payment_ids": ["pay_456"]
          }]
        }
      }
    }
  }'
```

(In production, Square sends actual signed webhooks)

### 7. Trigger Subscription Creation

```bash
# Simulate scheduler
php artisan queue:work

# In separate terminal, trigger the job manually
php artisan tinker
> Artisan::call('queue:work'); // Or dispatch job directly
> App\Jobs\GeneratePaymentRequestsJob::dispatch();
```

---

## Key Flow Checks

### Manual Mode (Default)
```
Group created with billing_mode='manual'
  ↓
generateRequestsForCycle() creates payment requests
  ↓
Manual two-step flow: member marks → owner confirms
  ↓
Status: owner_confirmed
```

### Auto-Square Mode
```
Group created with billing_mode='auto_square'
  ↓
generateRequestsForCycle() creates payment requests
  ↓
CreateAutoSquareSubscriptionsJob dispatched
  ↓
For each member:
  - Create Square Customer (if not exists)
  - Create Square Subscription
  ↓
Subscription created with monthly billing
  ↓
Square charges member on due date
  ↓
Webhook received: payment.updated/invoice.payment_made
  ↓
SquareWebhookService verifies signature
  ↓
PaymentRequest::confirmViaSquare() called
  ↓
Status: auto_verified
```

---

## Services Quick Reference

### SquareOAuthService
```php
use App\Services\Square\SquareOAuthService;

$oauth = new SquareOAuthService();

// Get authorization URL
$url = $oauth->getAuthorizationUrl($state);

// Exchange code for token
$token = $oauth->exchangeCodeForToken($code);

// Revoke token (disconnect)
$oauth->revokeAccessToken($accessToken);
```

### SquareCustomerService
```php
use App\Services\Square\SquareCustomerService;

$service = new SquareCustomerService();

// Create customer
$customerId = $service->createCustomerForMember($member, $owner);

// Get customer
$customer = $service->getCustomer($customerId, $owner);

// Delete customer
$service->deleteCustomer($customerId, $owner);
```

### SquareSubscriptionService
```php
use App\Services\Square\SquareSubscriptionService;

$service = new SquareSubscriptionService();

// Create subscription
$subscriptionId = $service->createSubscription($member, $owner, $cycle);

// Get subscription details
$sub = $service->getSubscription($subscriptionId, $owner);

// Cancel subscription
$service->cancelSubscription($subscriptionId, $owner);

// List subscriptions
$subs = $service->listCustomerSubscriptions($customerId, $owner);
```

### SquareWebhookService
```php
use App\Services\Square\SquareWebhookService;

$service = new SquareWebhookService();

// Verify webhook signature
$valid = $service->verifySignature($body, $signature, $timestamp);

// Route webhook to handlers
$service->handleWebhook($payload);
```

---

## Common Patterns

### Check if Group Needs Square Setup

```php
if ($group->isAutoSquareMode() && !$group->ownerHasSquare()) {
    return redirect()->route('oauth.square.authorize')
        ->with('message', 'Please connect your Square account');
}
```

### Create Subscription After Member Added

```php
$member = GroupMember::create([...]);

if ($member->group->isAutoSquareMode()) {
    CreateAutoSquareSubscriptionsJob::dispatch($member->group->currentCycle());
}
```

### Handle Payment Confirmation

```php
// From webhook handler or manual confirmation
$paymentRequest->confirmViaSquare($squarePaymentId);

// Internally:
// 1. Sets status to 'auto_verified'
// 2. Updates cycle.total_collected_cents
// 3. Records square_payment_id and square_confirmed_at
```

### Disconnect Square Account

```php
$user->disconnectSquare();
// Clears all Square fields and revokes token
```

---

## Debugging Tips

### Check if User Has Square
```php
$user->square_merchant_id;        // null or merchant_id
$user->square_access_token;       // null or encrypted token
$user->square_connected_at;       // null or timestamp
```

### Check Member Subscription Status
```php
$member->square_customer_id;      // null or cust_123
$member->square_subscription_id;  // null or sub_456
$member->isAutoSquareEnrolled();  // true/false
```

### Check Payment Request Status
```php
$request->status;                 // pending, sent, auto_verified, owner_confirmed, etc.
$request->square_payment_id;      // null or pay_789
$request->square_confirmed_at;    // null or timestamp
```

### Check Webhook Logs
```bash
tail -f storage/logs/laravel.log | grep -i square
```

### Simulate Webhook
```php
// app/Console/Commands/TestSquareWebhook.php
use App\Services\Square\SquareWebhookService;

$service = app(SquareWebhookService::class);

$payload = [
    'type' => 'payment.updated',
    'data' => [
        'object' => [
            'payment' => [
                'id' => 'pay_xyz',
                'status' => 'COMPLETED',
            ],
        ],
    ],
];

$service->handleWebhook($payload);
```

---

## Testing Checklist

- [ ] Migrations run successfully
- [ ] User model: `hasSquareAccount()`, `isSquareConfigured()` work
- [ ] Group model: `isAutoSquareMode()`, `ownerHasSquare()` work
- [ ] OAuth flow: authorize → callback → token stored
- [ ] Create subscription: customer created, subscription created
- [ ] Webhook received: signature verified, payment confirmed
- [ ] Status changed: from `pending` → `auto_verified`
- [ ] Cycle collected: `total_collected_cents` incremented
- [ ] Manual mode: unchanged behavior

---

## Troubleshooting

### "Invalid Square Credentials"
- Check `.env` has `SQUARE_CLIENT_ID` and `SQUARE_CLIENT_SECRET`
- Verify credentials from Square dashboard
- Check `SQUARE_MODE` matches (sandbox vs production)

### "Webhook Signature Invalid"
- Verify `SQUARE_WEBHOOK_SIGNING_KEY` is correct
- Check webhook body/signature/timestamp order
- Ensure clock sync with Square servers

### "Cannot Create Subscription"
- Verify member has `square_customer_id`
- Check owner has `square_access_token` not null
- Verify cycle has `total_amount_cents > 0`
- Check logs: `storage/logs/laravel.log`

### "Payment Not Confirmed"
- Check `payment_requests` table: is `square_payment_id` filled?
- Verify `PaymentRequest::confirmViaSquare()` was called
- Check webhook was received: grep logs for "Square webhook"

---

## Security Reminders

- ✅ Square tokens are encrypted: `Crypt::encryptString()`
- ✅ Webhook signatures verified: `verifySignature()`
- ✅ Webhook endpoint CSRF-exempt: Required for Square calls
- ✅ OAuth state validated: Prevents CSRF attacks
- ✅ Sensitive logs filtered: Tokens never logged in plain text

---

## Next Phase: UI Components

Once backend is tested, implement UI:

1. **Settings Page** (`resources/views/livewire/settings/square-account.blade.php`)
   - Show Square connection status
   - "Connect" / "Disconnect" buttons

2. **Group Creation** (update existing component)
   - Add "Billing Mode" step
   - Manual vs Auto-Square choice
   - Conditional Square connection check

3. **Group Dashboard** (update existing component)
   - Show billing mode badge
   - List member subscription statuses

4. **Member Payment View**
   - Different messaging for auto-square vs manual

See `SQUARE_INTEGRATION_SUMMARY.md` Phase 1-4 for UI details.

---

## Support

For issues or questions:
- Check `SQUARE_INTEGRATION_SUMMARY.md` for architecture details
- Review service code in `app/Services/Square/`
- Check webhook logs in `storage/logs/laravel.log`
- Test with Square sandbox first
