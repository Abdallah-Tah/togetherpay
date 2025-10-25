# Architecture V2 Quick Start Guide

**For Developers** — Get started with the V2 payment request system in 5 minutes.

---

## What Changed?

V2 introduces **PR Codes** (Payment Request Codes) for deterministic payment tracking without API webhooks.

**Before (V1):** Member pays → Owner manually checks wallet → Owner marks paid
**After (V2):** Member pays → Member marks paid → Owner confirms (PR code matches)

---

## Quick Examples

### 1. Generate a Payment Request

```php
use App\Support\PRCode;
use App\Services\PaymentDriverFactory;

// Generate PR Code
$prCode = PRCode::make($group->id, $member->id, '2025-11');
// Result: PR_2025-11_G123_M42_a3f9c2

// Get payment driver
$driver = PaymentDriverFactory::make('cashapp'); // or 'venmo'

// Build payment link with PR code embedded
$links = $driver->buildLink(
    member: $member,
    amountCents: 5000, // $50.00
    prCode: $prCode,
    note: 'T-Mobile Nov 2025 cycle'
);

// Create payment request
$pr = PaymentRequest::create([
    'group_id' => $group->id,
    'member_id' => $member->id,
    'cycle_id' => $cycle->id,
    'amount_cents' => 5000,
    'method_hint' => 'cashapp',
    'pr_code' => $prCode,
    'note' => 'T-Mobile Nov 2025 cycle',
    'deep_link' => $links['uri'],
    'web_fallback_link' => $links['web_fallback'],
    'status' => 'pending',
]);
```

### 2. Member Marks Payment as Paid

```php
$pr = PaymentRequest::where('pr_code', $prCode)->first();
$pr->markAsPaid(); // Sets status to 'member_marked'
```

### 3. Owner Confirms Payment

```php
$pr = PaymentRequest::where('pr_code', $prCode)->first();
$pr->confirmPayment(); // Sets status to 'owner_confirmed', updates cycle total
```

### 4. Owner Bulk Confirms

```php
$prCodes = ['PR_2025-11_G123_M42_a3f9c2', 'PR_2025-11_G123_M43_b5d8e3'];

PaymentRequest::whereIn('pr_code', $prCodes)
    ->where('status', 'member_marked')
    ->each(fn($pr) => $pr->confirmPayment());
```

---

## API Endpoints (Mobile App)

### Get Payment Request by PR Code
```http
GET /api/v1/requests/by-code/{prCode}
Authorization: Bearer {token}
```

### Member Marks as Paid
```http
POST /api/v1/payment-requests/{id}/mark-paid
Authorization: Bearer {token}
Content-Type: application/json

{
  "evidence_url": "https://...",
  "evidence_type": "photo"
}
```

### Owner Confirms
```http
POST /api/v1/requests/by-code/{prCode}/confirm
Authorization: Bearer {token}
```

### Owner Bulk Confirms
```http
POST /api/v1/cycles/{cycleId}/bulk-confirm
Authorization: Bearer {token}
Content-Type: application/json

{
  "pr_codes": ["PR_2025-11_G123_M42_a3f9c2", "..."]
}
```

---

## Payment Request States

```
pending → sent → member_marked → owner_confirmed ✅
                      ↓
                  expired/failed ❌
```

**Check Status:**
```php
$pr->isPending()           // pending or sent
$pr->isMarkedByMember()    // member_marked
$pr->isConfirmed()         // owner_confirmed or auto_verified
$pr->isPaid()              // any confirmed state
```

---

## Payment Drivers

### Cash App
```php
$driver = PaymentDriverFactory::make('cashapp');
$links = $driver->buildLink($member, 5000, $prCode, 'Note');

// Result:
// uri: https://cash.app/$JohnDoe/50.00?note=Note+PR_2025-11_G123_M42_a3f9c2
// web_fallback: (same as uri)
```

### Venmo
```php
$driver = PaymentDriverFactory::make('venmo');
$links = $driver->buildLink($member, 5000, $prCode, 'Note');

// Result:
// uri: venmo://pay?recipients=johndoe&amount=50.00&note=Note+PR_2025-11_G123_M42_a3f9c2
// web_fallback: https://venmo.com/johndoe?txn=pay&...
```

---

## Authorization

**Policies automatically enforced via `$this->authorize()` in controllers.**

### What Members Can Do
- ✅ View their own payment requests
- ✅ Mark their own payments as paid
- ❌ View other members' requests
- ❌ Confirm payments

### What Owners Can Do
- ✅ View all requests in their groups
- ✅ Confirm payments marked by members
- ✅ Bulk confirm multiple payments
- ✅ Send nudges/reminders
- ❌ View requests from groups they don't own

---

## Database Queries

### Get Requests Awaiting Confirmation
```php
PaymentRequest::where('group_id', $groupId)
    ->awaitingConfirmation()
    ->with('member.user')
    ->get();
```

### Get Member's Pending Payments
```php
PaymentRequest::whereHas('member', fn($q) => $q->where('user_id', $userId))
    ->status(['pending', 'sent'])
    ->with('group', 'cycle')
    ->get();
```

### Get Cycle Summary
```php
$cycle->load(['paymentRequests' => fn($q) => $q->with('member.user')]);

$summary = [
    'total_expected' => $cycle->total_expected_cents,
    'total_collected' => $cycle->total_collected_cents,
    'pending' => $cycle->paymentRequests->where('status', 'sent')->count(),
    'awaiting_confirmation' => $cycle->paymentRequests->where('status', 'member_marked')->count(),
    'confirmed' => $cycle->paymentRequests->where('status', 'owner_confirmed')->count(),
];
```

---

## Configuration

### Environment Variables
```env
# Payment Request Configuration
PR_DRIVER=link
CASHAPP_TAG_REQUIRED=true
VENMO_USERNAME_REQUIRED=true

# Dunning Schedule
DUNNING_RETRY1_DAYS_BEFORE=2
DUNNING_RETRY2_DAYS_AFTER=2
DUNNING_FINAL_DAYS_AFTER=5

# Cycle Management
CYCLE_GRACE_PERIOD_DAYS=7
```

### Access Config Values
```php
config('payment_requests.dunning.retry1_days_before')
config('payment_requests.grace_period_days')
config('payment_requests.statuses')
```

---

## Testing

### Test PR Code Generation
```php
use App\Support\PRCode;

test('generates valid PR code', function () {
    $code = PRCode::make(123, 456, '2025-11');

    expect($code)->toMatch('/^PR_2025-11_G123_M456_[a-f0-9]{6}$/');
    expect(PRCode::isValid($code))->toBeTrue();
    expect(PRCode::extractGroupId($code))->toBe(123);
});
```

### Test Payment Flow
```php
test('member can mark payment as paid', function () {
    $member = User::factory()->create();
    $pr = PaymentRequest::factory()->create(['status' => 'sent']);

    actingAs($member)
        ->postJson("/api/v1/payment-requests/{$pr->id}/mark-paid")
        ->assertOk();

    expect($pr->fresh()->status)->toBe('member_marked');
});
```

---

## Common Patterns

### Validate Owner Has Payment Account
```php
if (! $group->owner->cash_tag && ! $group->owner->venmo_username) {
    throw new \Exception('Owner must configure payment account first');
}
```

### Build Smart Pay Fallback Link
```php
$smartPayUrl = route('smartpay.show', $pr->id);
// https://yourapp.com/pay/{id}
```

### Parse PR Code in Webhook/Callback
```php
$prCode = request()->input('note'); // Extract from webhook payload
$parsed = PRCode::parse($prCode);

if ($parsed) {
    $pr = PaymentRequest::where([
        'group_id' => $parsed['group_id'],
        'pr_code' => $prCode,
    ])->first();

    $pr->confirmPayment();
}
```

---

## Troubleshooting

### "PR Code not found"
- Ensure PR code format is correct: `PR_YYYY-MM_G{id}_M{id}_{random}`
- Check database: `PaymentRequest::where('pr_code', $code)->exists()`

### "Unauthorized" when confirming
- Check user is group owner: `$pr->group->owner_id === auth()->id()`
- Check status is `member_marked`: `$pr->status === 'member_marked'`

### Payment link not working
- Verify owner has payment account configured
- Check deep link format in database
- Try web fallback link instead of deep link

---

## Next Steps

1. **Read Full Documentation:** [ARCHITECTURE_V2.md](ARCHITECTURE_V2.md)
2. **Check Implementation Status:** [IMPLEMENTATION_V2_SUMMARY.md](IMPLEMENTATION_V2_SUMMARY.md)
3. **Create Tests:** See testing examples above
4. **Implement Jobs:** GeneratePaymentRequestsJob, SendInitialRemindersJob
5. **Build UI:** Livewire bulk-confirm component

---

**Need Help?**
- See code examples in [ARCHITECTURE_V2.md](ARCHITECTURE_V2.md)
- Check existing tests for patterns
- Review API controller for endpoint details

---

**Version:** 2.0
**Last Updated:** October 25, 2025
