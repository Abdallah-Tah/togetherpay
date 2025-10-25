# Architecture V2 Implementation Summary

**Date:** October 25, 2025
**Status:** ✅ Phase 1 Complete (Core Infrastructure)

---

## Overview

Successfully implemented the core infrastructure for TogetherPay Architecture V2, which introduces **Payment Request Codes (PR Codes)** for deterministic reconciliation, enhanced state machine for payment tracking, and production-ready payment drivers.

---

## What Was Implemented

### 1. Core Utilities & Support Classes ✅

#### PRCode Generator ([app/Support/PRCode.php](app/Support/PRCode.php))
- Generates unique payment request codes: `PR_{cycle_ym}_G{group_id}_M{member_id}_{random}`
- Parse and validate PR code format
- Extract components (group_id, member_id, cycle_ym) from codes
- Example: `PR_2025-11_G123_M42_a3f9c2`

**Key Methods:**
```php
PRCode::make($groupId, $memberId, $cycleYm)
PRCode::parse($prCode)
PRCode::isValid($prCode)
PRCode::extractGroupId($prCode)
```

---

### 2. Payment Driver System ✅

#### Contract Interface ([app/Contracts/PaymentRequestDriver.php](app/Contracts/PaymentRequestDriver.php))
Updated interface to support V2 requirements:
- `buildLink()` now returns both deep link URI and web fallback
- Added `$prCode` parameter for embedding in payment notes
- Added `validateAccount()` method for account format validation
- Kept `getName()` for driver identification

#### VenmoLinkDriver ([app/Drivers/VenmoLinkDriver.php](app/Drivers/VenmoLinkDriver.php))
- Generates Venmo deep links: `venmo://pay?recipients={username}&amount={amount}&note={note}+{prCode}`
- Web fallback: `https://venmo.com/{username}?txn=pay&...`
- Validates Venmo username format (@username or username)
- PR Code automatically embedded in payment note

#### CashAppLinkDriver ([app/Drivers/CashAppLinkDriver.php](app/Drivers/CashAppLinkDriver.php))
- Generates Cash App links: `https://cash.app/$cashtag/{amount}?note={note}+{prCode}`
- Same URL works as both deep link and web fallback
- Validates Cash App tag format ($cashtag)
- PR Code automatically embedded in payment note

#### PaymentDriverFactory ([app/Services/PaymentDriverFactory.php](app/Services/PaymentDriverFactory.php))
- Factory pattern for creating driver instances
- `make('cashapp')` → CashAppLinkDriver
- `make('venmo')` → VenmoLinkDriver
- `available()` → List of supported payment methods
- `supports($method)` → Check if method is supported

---

### 3. Database Schema Enhancements ✅

#### Payment Requests Table Enhancement
**Migration:** [database/migrations/2025_10_25_173045_enhance_payment_requests_table_for_v2.php](database/migrations/2025_10_25_173045_enhance_payment_requests_table_for_v2.php)

**New Columns Added:**
- `group_id` — Direct reference to group (with foreign key)
- `pr_code` — Unique payment request code (indexed)
- `note` — Payment note (max 160 chars)
- `method_hint` — Renamed from `channel`
- `deep_link` — Renamed from `link`
- `web_fallback_link` — Web fallback URL
- `evidence_url` — Receipt/proof URL
- `evidence_type` — Type of evidence (photo, screenshot, code)
- `initiated_at` — When request was initiated
- `marked_at` — When member marked as paid
- `confirmed_at` — When owner confirmed payment

**New Indexes:**
- `pr_code` (unique)
- `(group_id, cycle_id, status)` (composite)

#### Dunning Events Table Enhancement
**Migration:** [database/migrations/2025_10_25_173203_enhance_dunning_events_table_for_v2.php](database/migrations/2025_10_25_173203_enhance_dunning_events_table_for_v2.php)

**Changes:**
- Renamed `type` → `kind` for consistency
- Added `channel` — push, sms, email
- Added `sent_at` — When notification was sent
- Added `delivered_at` — Delivery confirmation
- Added `opened_at` — Open tracking
- Added `updated_at` — Standard timestamp
- Updated indexes for performance

---

### 4. Eloquent Models ✅

#### PaymentRequest Model ([app/Models/PaymentRequest.php](app/Models/PaymentRequest.php))

**New Fillable Fields:**
- All V2 fields added to `$fillable`
- Enhanced `$casts` with all timestamp fields

**New Relationships:**
- `group()` — BelongsTo Group (added)
- Existing: `cycle()`, `member()`, `dunningEvents()`

**New Methods:**
- `isMarkedByMember()` — Check if member marked as paid
- `isConfirmed()` — Check if owner confirmed
- `confirmPayment()` — Owner confirms receipt (updates cycle total)
- `markAsExpired()` — Mark request as expired
- `scopeStatus($status)` — Filter by status
- `scopeAwaitingConfirmation()` — Get requests awaiting owner confirmation

**Enhanced Methods:**
- `isPaid()` — Now includes `owner_confirmed` and `auto_verified`
- `markAsPaid()` — Now sets status to `member_marked` (not `paid`)

#### DunningEvent Model ([app/Models/DunningEvent.php](app/Models/DunningEvent.php))

**Updated Fields:**
- Replaced `attempt_no` and `type` with V2 structure
- Added all new timestamp fields
- Enabled standard `timestamps` (removed `$timestamps = false`)

**New Scopes:**
- `scopeKind($kind)` — Filter by dunning kind
- `scopeChannel($channel)` — Filter by notification channel

---

### 5. Authorization & Policies ✅

#### PaymentRequestPolicy ([app/Policies/PaymentRequestPolicy.php](app/Policies/PaymentRequestPolicy.php))

**Implemented Methods:**
- `view($user, $pr)` — Owner sees all group requests, members see only theirs
- `markPaid($user, $pr)` — Member can mark their own payment (status: sent/pending)
- `confirm($user, $pr)` — Owner can confirm (status: member_marked)
- `manage($user, $pr)` — Owner can manage requests in their groups
- `bulkConfirm($user, $pr)` — Owner can bulk confirm payments

**Security:**
- Strict access control: members only see their own requests
- Owners have full visibility of their group's requests
- State-based permissions (e.g., can only confirm if `member_marked`)

---

### 6. API Endpoints ✅

#### PaymentRequestController ([app/Http/Controllers/Api/PaymentRequestController.php](app/Http/Controllers/Api/PaymentRequestController.php))

**New V2 Endpoints:**

| Method | Endpoint | Description | Authorization |
|--------|----------|-------------|---------------|
| GET | `/api/v1/requests/by-code/{prCode}` | Get payment request by PR code | Member or Owner |
| POST | `/api/v1/requests/by-code/{prCode}/confirm` | Owner confirms payment received | Owner only |
| POST | `/api/v1/requests/by-code/{prCode}/nudge` | Owner sends reminder to member | Owner only |
| POST | `/api/v1/cycles/{cycleId}/bulk-confirm` | Owner bulk confirms multiple payments | Owner only |

**Existing Endpoints (Preserved):**
- `GET /api/v1/payment-requests` — List user's requests
- `GET /api/v1/payment-requests/stats` — Payment statistics
- `GET /api/v1/payment-requests/{id}` — Get request by ID
- `POST /api/v1/payment-requests/{id}/mark-paid` — Mark as paid (old flow)
- `POST /api/v1/payment-requests/{id}/retry` — Retry failed request

**Response Format (showByCode example):**
```json
{
  "id": 123,
  "pr_code": "PR_2025-11_G42_M17_a3f9c2",
  "group": {
    "id": 42,
    "name": "T-Mobile Family Plan",
    "owner": {
      "name": "John Doe",
      "cash_tag": "$JohnDoe",
      "venmo_username": "@johndoe"
    }
  },
  "amount_cents": 5000,
  "amount_display": "$50.00",
  "status": "sent",
  "method_hint": "cashapp",
  "deep_link": "https://cash.app/$JohnDoe/50.00?note=...",
  "web_fallback_link": "https://cash.app/$JohnDoe/50.00?note=...",
  "due_date": "2025-11-15T00:00:00Z",
  "sent_at": "2025-11-13T09:00:00Z"
}
```

---

### 7. Routes Configuration ✅

#### API Routes ([routes/api.php](routes/api.php))

**Added V2 Routes:**
```php
// Payment Requests V2 (PR Code based endpoints)
Route::get('/requests/by-code/{prCode}', [PaymentRequestController::class, 'showByCode']);
Route::post('/requests/by-code/{prCode}/confirm', [PaymentRequestController::class, 'confirmPayment']);
Route::post('/requests/by-code/{prCode}/nudge', [PaymentRequestController::class, 'nudge']);
Route::post('/cycles/{cycleId}/bulk-confirm', [PaymentRequestController::class, 'bulkConfirm']);
```

All routes protected with `auth:sanctum` middleware.

---

### 8. Configuration ✅

#### Payment Requests Config ([config/payment_requests.php](config/payment_requests.php))

**Added V2 Configuration:**
- `grace_period_days` — Days before closing cycle (default: 7)
- `dunning.retry1_days_before` — D-2 friendly reminder
- `dunning.retry2_days_after` — D+2 gentle nudge
- `dunning.final_days_after` — D+5 final notice
- `statuses` — Array of valid payment request statuses
- `evidence_types` — Valid evidence types (photo, screenshot, code)
- `shortener` — Link shortener configuration (future feature)

**Environment Variables:**
```env
# V2 Configuration
CYCLE_GRACE_PERIOD_DAYS=7
DUNNING_RETRY1_DAYS_BEFORE=2
DUNNING_RETRY2_DAYS_AFTER=2
DUNNING_FINAL_DAYS_AFTER=5
LINK_SHORTENER_ENABLED=false
LINK_SHORTENER_BASE=https://tpay.app/p/
```

---

## State Machine

### Payment Request States

```
pending → sent → member_marked → owner_confirmed
                      ↓
                  expired/failed
```

**Status Definitions:**
- `pending` — Created, not yet sent
- `sent` — Notification delivered to member
- `member_marked` — Member marked as paid (awaiting owner confirmation)
- `owner_confirmed` — Owner confirmed receipt ✅
- `auto_verified` — Automatically verified (future: Plaid integration)
- `failed` — Payment failed
- `expired` — Request expired without payment

---

## Usage Examples

### 1. Generate Payment Request with PR Code

```php
use App\Support\PRCode;
use App\Services\PaymentDriverFactory;
use App\Models\PaymentRequest;

// Generate PR Code
$prCode = PRCode::make(
    groupId: $group->id,
    memberId: $member->id,
    cycleYm: $cycle->cycle_month->format('Y-m')
);

// Build payment link
$driver = PaymentDriverFactory::make($member->wallet_pref ?? 'cashapp');
$links = $driver->buildLink(
    member: $member,
    amountCents: $member->share_amount_cents,
    prCode: $prCode,
    note: "{$group->name} {$cycle->cycle_month->format('M Y')} cycle"
);

// Create payment request
$pr = PaymentRequest::create([
    'group_id' => $group->id,
    'member_id' => $member->id,
    'cycle_id' => $cycle->id,
    'amount_cents' => $member->share_amount_cents,
    'method_hint' => $member->wallet_pref,
    'pr_code' => $prCode,
    'note' => $links['note'] ?? '',
    'deep_link' => $links['uri'],
    'web_fallback_link' => $links['web_fallback'],
    'status' => 'pending',
]);
```

### 2. Member Marks Payment as Paid (API Call)

```bash
curl -X POST https://api.togetherpay.com/v1/requests/by-code/PR_2025-11_G42_M17_a3f9c2/mark-paid \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "evidence_url": "https://s3.../receipt.jpg",
    "evidence_type": "photo"
  }'
```

### 3. Owner Confirms Payment (API Call)

```bash
curl -X POST https://api.togetherpay.com/v1/requests/by-code/PR_2025-11_G42_M17_a3f9c2/confirm \
  -H "Authorization: Bearer {token}"
```

### 4. Owner Bulk Confirms (API Call)

```bash
curl -X POST https://api.togetherpay.com/v1/cycles/123/bulk-confirm \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "pr_codes": [
      "PR_2025-11_G42_M17_a3f9c2",
      "PR_2025-11_G42_M18_b4e8d3",
      "PR_2025-11_G42_M19_c5f9e4"
    ]
  }'
```

---

## Testing Checklist

### Unit Tests (To Be Created)
- [ ] PRCode generation and parsing
- [ ] VenmoLinkDriver link generation
- [ ] CashAppLinkDriver link generation
- [ ] PaymentDriverFactory driver selection

### Feature Tests (To Be Created)
- [ ] Member can mark payment as paid
- [ ] Owner can confirm payment
- [ ] Owner can bulk confirm payments
- [ ] Member cannot confirm payment (authorization)
- [ ] Member cannot see other member's requests
- [ ] Owner can see all group requests

### Integration Tests (To Be Created)
- [ ] Full payment flow: pending → sent → member_marked → owner_confirmed
- [ ] PR Code is embedded in payment link note
- [ ] Cycle total_collected_cents updates on confirmation

---

## What's Next (Phase 2)

### Scheduled Jobs (Not Yet Implemented)
- [ ] `CreateUpcomingCyclesJob` — Generate billing cycles
- [ ] `GeneratePaymentRequestsJob` — Create payment requests with PR codes
- [ ] `SendInitialRemindersJob` — Send push notifications
- [ ] `SendRetryRemindersJob` — Dunning cadence (D-2, D+2, D+5)
- [ ] `CloseCyclesJob` — Close cycles and handle unpaid requests

### Livewire Components (Not Yet Implemented)
- [ ] Bulk confirm component ([resources/views/livewire/cycles/confirm-payments.blade.php](resources/views/livewire/cycles/confirm-payments.blade.php))
- [ ] Payment request dashboard
- [ ] Group cycle overview with status indicators

### Notifications (Not Yet Implemented)
- [ ] `PaymentRequestNotification` — FCM push notification
- [ ] `PaymentMarkedNotification` — Notify owner when member marks paid
- [ ] `PaymentReminderNotification` — Dunning reminders

### Flutter/Mobile App (Not Yet Implemented)
- [ ] Deep link handling
- [ ] Push notification handling
- [ ] Mark as paid workflow
- [ ] Evidence upload

---

## Migration Notes

### Database Migration Status
✅ All migrations run successfully on SQLite (development)
- `2025_10_25_173045_enhance_payment_requests_table_for_v2` — DONE
- `2025_10_25_173203_enhance_dunning_events_table_for_v2` — DONE

### Backward Compatibility
- ✅ All existing API endpoints preserved
- ✅ Old payment flow (`markAsPaid`) still works
- ✅ Database columns renamed (not dropped)
- ✅ Config file enhanced (not replaced)

### Breaking Changes
- ⚠️ `PaymentRequest::markAsPaid()` now sets status to `member_marked` (not `paid`)
- ⚠️ To fully complete payment, must call `confirmPayment()` after `markAsPaid()`
- ⚠️ `channel` column renamed to `method_hint` in database
- ⚠️ `link` column renamed to `deep_link` in database

---

## Files Created

```
app/
├── Support/
│   └── PRCode.php                              ✅ NEW
├── Drivers/
│   ├── VenmoLinkDriver.php                     ✅ NEW
│   └── CashAppLinkDriver.php                   ✅ NEW
├── Services/
│   └── PaymentDriverFactory.php                ✅ NEW
└── Policies/
    └── PaymentRequestPolicy.php                ✅ CREATED

database/migrations/
├── 2025_10_25_173045_enhance_payment_requests_table_for_v2.php  ✅ NEW
└── 2025_10_25_173203_enhance_dunning_events_table_for_v2.php    ✅ NEW
```

## Files Modified

```
app/
├── Contracts/
│   └── PaymentRequestDriver.php                ✅ UPDATED
├── Models/
│   ├── PaymentRequest.php                      ✅ UPDATED
│   └── DunningEvent.php                        ✅ UPDATED
└── Http/Controllers/Api/
    └── PaymentRequestController.php            ✅ UPDATED (added V2 endpoints)

config/
└── payment_requests.php                        ✅ UPDATED (added V2 config)

routes/
└── api.php                                     ✅ UPDATED (added V2 routes)
```

---

## Performance Considerations

### Indexes Added
- `payment_requests(pr_code)` — Unique lookup by PR code
- `payment_requests(group_id, cycle_id, status)` — Fast group/cycle queries
- `dunning_events(payment_request_id, kind)` — Fast dunning lookup
- `dunning_events(sent_at, channel)` — Fast channel analytics

### Query Optimization
- Eager loading relationships in API endpoints
- Composite indexes for common query patterns
- Status-based scopes for efficient filtering

---

## Security Enhancements

### Authorization
- ✅ Policy-based access control
- ✅ Members can only see their own requests
- ✅ Owners can see all group requests
- ✅ State-based permissions (e.g., confirm only if member_marked)

### Data Privacy
- ✅ PR Codes don't expose sensitive information
- ✅ Random suffix prevents guessing
- ✅ Evidence URLs will use signed URLs (when implemented)

---

## Documentation

- ✅ [ARCHITECTURE_V2.md](ARCHITECTURE_V2.md) — Complete architecture specification
- ✅ [IMPLEMENTATION_V2_SUMMARY.md](IMPLEMENTATION_V2_SUMMARY.md) — This document
- ✅ Code comments in all new files
- ✅ PHPDoc blocks for all public methods

---

## Success Metrics

### Phase 1 Completion ✅
- [x] Core utilities implemented
- [x] Payment drivers implemented
- [x] Database schema enhanced
- [x] Models updated
- [x] Policies created
- [x] API endpoints added
- [x] Routes configured
- [x] Configuration updated
- [x] Documentation complete

### Phase 2 Goals (Upcoming)
- [ ] Scheduled jobs implemented
- [ ] Push notifications working
- [ ] Livewire components created
- [ ] Mobile app integration complete
- [ ] End-to-end testing complete
- [ ] Production deployment ready

---

## Questions & Next Steps

### Immediate Actions
1. **Test the new API endpoints** — Use Postman or curl to verify V2 endpoints
2. **Create unit tests** — Test PRCode and payment drivers
3. **Implement GeneratePaymentRequestsJob** — Core job for creating requests with PR codes
4. **Create Livewire bulk-confirm component** — Owner dashboard feature
5. **Implement push notifications** — FCM integration for payment requests

### Future Considerations
- **Plaid Integration** — Auto-verify payments via read-only bank connection
- **Link Shortener** — Implement URL shortening for SMS
- **Receipt OCR** — Automatically extract confirmation codes from receipts
- **Analytics Dashboard** — Track payment success rates, latency, dunning effectiveness

---

**Status:** ✅ Phase 1 Complete — Ready for Phase 2 (Scheduled Jobs & UI)

**Next Milestone:** Implement GeneratePaymentRequestsJob to create payment requests with PR codes

---

**Last Updated:** October 25, 2025
**Implemented By:** Claude Code
**Review Status:** Awaiting code review and testing
