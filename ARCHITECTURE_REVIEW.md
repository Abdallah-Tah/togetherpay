# Architecture Review: Multi-Channel Payment Request System

**Review Date:** 2026-02-22
**Reviewer:** Claude Code
**Application:** TogetherPay - Group Bill Management Platform

---

## Executive Summary

This document reviews the multi-channel payment request architecture for TogetherPay, a platform that manages shared bills by sending payment requests through Venmo and Cash App without processing payments directly (deep-link/request intent strategy).

### Current State
- ✅ Driver pattern implemented with `PaymentRequestDriver` contract
- ✅ Two-step payment confirmation system (member marks → owner confirms)
- ✅ Trust scoring and automatic verification for reliable members
- ✅ Square integration for auto-billing mode (optional)
- ⚠️ Critical bugs in factory pattern and routing logic
- ❌ Recurring billing jobs not implemented
- ❌ Owner payout channel configuration incomplete

### Health Score: 6.5/10
**Production Ready:** No (P0 bugs block core functionality)
**After P0/P1 Fixes:** Yes (8.5/10)

---

## System Requirements Review

### 1️⃣ Users and Payment Channels

#### ✅ What Works
- **Member Channels:** Members can configure Venmo (`venmo_username`) and Cash App (`cash_tag`, `cash_phone`, `cash_email`)
- **Channel Selection:** `group_members.preferred_channel` stores default payment method
- **Validation:** Drivers validate account formats (`@username`, `$cashtag`, phone, email)
- **Normalization:** Automatic prefix handling (`$` for Cash App, `@` for Venmo)

#### ❌ Critical Gap: Owner Payout Channels
**Problem:** Payment links currently built with member's handle instead of owner's receiving identifier.

**Current Bug in VenmoDriver.php:28-33:**
```php
$webFallback = sprintf(
    'https://venmo.com/u/%s?txn=pay&amount=%s&note=%s&pr=%s',
    $handle,  // ← Member's handle, but should be OWNER's handle
    ...
);
```

**Impact:** Payment links send money to wrong recipient or fail entirely.

**Database Schema:**
```
users table:
  - venmo_username (owner's Venmo handle) ✓
  - cashapp_tag (owner's Cash App $cashtag) ✓

group_members table:
  - preferred_channel (VENMO, CASH_APP) ✓
  - venmo_username (member's Venmo) ✓
  - cash_tag (member's Cash App) ✓
  - cash_phone (member's phone for Cash App) ✓
  - cash_email (member's email for Cash App) ✓
```

**Solution Required:** See Priority Actions → P0-2

---

### 2️⃣ Routing Rules (Automatic)

#### ✅ What Works
- Member's `preferred_channel` stored in database
- `PaymentRequestService` generates payment options for all channels
- "Smart Pay" page (`/pay/{id}`) presents multiple options

#### ❌ Gap: Automatic Routing Not Enforced
**Current Behavior:** System builds links for ALL channels regardless of preference

**Code Location:** [PaymentRequestService.php:218-255](app/Services/PaymentRequestService.php#L218-L255)

```php
protected function buildPaymentOptions(...): array {
    $options = [];

    // Loops through ALL available channels
    foreach (PaymentDriverFactory::available() as $method) {
        $driver = PaymentDriverFactory::make($method);
        // Builds link even if not preferred
        $options[] = [...];
    }

    return $options; // Returns all channels, not preferred first
}
```

**Expected Behavior:**
1. Check member's `preferred_channel`
2. Verify owner supports that channel
3. Use preferred channel for primary deep-link
4. Include fallback channels as secondary options
5. Mark preferred option in metadata

**Solution Required:** See Priority Actions → P1-2

---

### 3️⃣ Recurring Billing + Reminders

#### ✅ What Works
- **Database:** `billing_cycles` table with `period_start`, `period_end`, `due_at`, `status`
- **Configuration:** Dunning schedule defined in `config/payment_requests.php:60-72`
- **Reminders:** `dunning_events` table tracks all reminder attempts
- **Grace Period:** Configurable grace period (default 7 days)

#### ❌ Critical Gap: Scheduler Jobs Not Implemented
**From CLAUDE.md:**
> "Scheduled Jobs (Planned): Daily/hourly Laravel scheduler jobs"

**Missing Jobs:**

| Job | Schedule | Purpose | Status |
|-----|----------|---------|--------|
| `CreateUpcomingCyclesJob` | Daily | Generate new billing cycles | ❌ Not created |
| `GeneratePaymentRequestsJob` | Daily 8am | Create payment requests for active cycles | ❌ Not created |
| `SendInitialRemindersJob` | Daily | Send first reminder on due date | ❌ Not created |
| `SendRetryRemindersJob` | Hourly | Send follow-up reminders per schedule | ❌ Not created |
| `CloseCyclesJob` | Daily | Close cycles after grace period | ❌ Not created |
| `AutoConfirmPaymentsJob` | Hourly | Auto-confirm high-trust payments | ❌ Not created |

**Impact:** Manual cycles only; no automatic recurring billing

**Solution Required:** See Priority Actions → P1-1

---

### 4️⃣ Tracking (Without Handling Payments)

#### ✅ Excellent Implementation

**Status Lifecycle:**
```
pending → sent → member_marked → owner_confirmed
                      ↓
                  auto_verified (Square webhook or trust-based)
                      ↓
                  disputed → resolved
```

**Key Features:**
- ✅ Two-step confirmation with undo window (`undo_available_until`)
- ✅ Trust scoring (`trust_score`, `on_time_payments`, `late_payments`)
- ✅ Automatic verification for high-trust members (95%+ score)
- ✅ Dispute handling with reason tracking
- ✅ Evidence uploads (`evidence_url`, `evidence_type`)
- ✅ Comprehensive timestamps (`initiated_at`, `sent_at`, `marked_at`, `confirmed_at`)
- ✅ Square webhook auto-verification via `confirmViaSquare()`

**Models:** [PaymentRequest.php:89-266](app/Models/PaymentRequest.php#L89-L266)

**Minor Enhancement Needed:** Analytics/reporting methods (P3)

---

### 5️⃣ Extensibility

#### ✅ What Works
- Clean driver interface: `PaymentRequestDriver` contract
- Driver factory pattern for channel selection
- Configuration-based driver setup in `config/payment_requests.php`

#### ❌ Critical Bug: Factory Class Names Wrong

**PaymentDriverFactory.php:21-23:**
```php
return match (strtolower($method)) {
    'cashapp', 'cash_app' => new CashAppLinkDriver,  // ← Class doesn't exist
    'venmo' => new VenmoLinkDriver,                  // ← Class doesn't exist
    ...
};
```

**Actual Classes:**
- `App\Services\PaymentDrivers\VenmoDriver`
- `App\Services\PaymentDrivers\CashAppDriver`

**Impact:** Application crashes when trying to create payment requests

**Solution Required:** See Priority Actions → P0-1

#### 🔄 Enhancement: Plugin Registration System (P3)

Future-proof the system for adding Zelle, PayPal, etc.:

```php
// config/payment_requests.php
'channels' => [
    'venmo' => [
        'driver' => \App\Services\PaymentDrivers\VenmoDriver::class,
        'enabled' => true,
        'display_name' => 'Venmo',
    ],
    'cash_app' => [
        'driver' => \App\Services\PaymentDrivers\CashAppDriver::class,
        'enabled' => true,
        'display_name' => 'Cash App',
    ],
    'zelle' => [
        'driver' => \App\Services\PaymentDrivers\ZelleDriver::class,
        'enabled' => env('ZELLE_ENABLED', false),
        'display_name' => 'Zelle',
    ],
],
```

---

## Priority Action Items

### P0: Critical Bugs (Blocks Production)

| ID | Task | Files | Impact | Effort |
|----|------|-------|--------|--------|
| **P0-1** | Fix PaymentDriverFactory class names | `app/Services/PaymentDriverFactory.php` | App crashes creating requests | 5 min |
| **P0-2** | Fix payment links to use owner's handle | `VenmoDriver.php`, `CashAppDriver.php`, `PaymentRequestService.php` | Links send to wrong recipient | 30 min |

**Estimated Time:** 35 minutes

---

### P1: Core Features (Required for MVP)

| ID | Task | Files | Impact | Effort |
|----|------|-------|--------|--------|
| **P1-1** | Implement recurring billing scheduler jobs | `app/Jobs/*`, `routes/console.php` | No automatic recurring billing | 2-3 hours |
| **P1-2** | Enforce preferred channel routing | `PaymentRequestService.php` | Poor UX, inconsistent behavior | 45 min |
| **P1-3** | Update driver interface for owner/member separation | `PaymentRequestDriver.php`, drivers | Better API design | 1 hour |

**Estimated Time:** 4-5 hours

---

### P2: Owner Experience (Important)

| ID | Task | Files | Impact | Effort |
|----|------|-------|--------|--------|
| **P2-1** | Add owner payout channel configuration UI | Livewire components | Owner can't manage channels | 2 hours |
| **P2-2** | Add channel verification flow | Controllers, notifications | Reduces payment failures | 1.5 hours |
| **P2-3** | Multi-channel validation on group creation | `GroupController`, validation | Prevents setup errors | 30 min |

**Estimated Time:** 4 hours

---

### P3: Enhancements (Nice to Have)

| ID | Task | Files | Impact | Effort |
|----|------|-------|--------|--------|
| **P3-1** | Build analytics/reporting methods | Models, API controllers | Better insights | 3 hours |
| **P3-2** | Implement plugin registration system | Config, factory | Easier to add channels | 2 hours |
| **P3-3** | Add channel-specific error handling | Drivers, services | Better debugging | 1.5 hours |
| **P3-4** | Create channel availability matrix | UI components | Show owner/member compatibility | 2 hours |

**Estimated Time:** 8.5 hours

---

## Implementation Plan

### Phase 1: Critical Fixes (Week 1)
**Goal:** Production-ready core functionality

1. **Day 1 Morning:** Fix P0-1 and P0-2 (35 min)
2. **Day 1 Afternoon:** Test payment link generation end-to-end
3. **Day 2-3:** Implement P1-1 (scheduler jobs)
4. **Day 4:** Implement P1-2 and P1-3 (routing + interface)
5. **Day 5:** Integration testing and bug fixes

**Deliverable:** Working multi-channel payment requests with automatic recurring billing

---

### Phase 2: Owner Experience (Week 2)
**Goal:** Complete owner configuration flow

1. **Day 1-2:** P2-1 (Owner payout channel UI)
2. **Day 3:** P2-2 (Verification flow)
3. **Day 4:** P2-3 (Validation)
4. **Day 5:** End-to-end testing

**Deliverable:** Full owner setup and channel management

---

### Phase 3: Polish & Scale (Week 3)
**Goal:** Production-grade quality

1. **Days 1-2:** P3-1 (Analytics)
2. **Day 3:** P3-2 (Plugin system)
3. **Day 4:** P3-3 (Error handling)
4. **Day 5:** Documentation and deployment prep

**Deliverable:** Scalable, maintainable system ready for growth

---

## Technical Debt

### Current Issues
1. **Mixed naming conventions:** Some files use `cash_app`, others `cashapp`
2. **Driver location:** Drivers in `app/Services/PaymentDrivers/` but factory references `App\Drivers\`
3. **Missing tests:** No feature tests for payment link generation
4. **Hard-coded URLs:** Deep-link schemes hard-coded in drivers
5. **No rate limiting:** Payment request generation not rate-limited

### Recommended Refactors (Post-MVP)
1. Consolidate payment channel naming to `CASH_APP` everywhere
2. Move drivers to `app/Drivers/PaymentChannels/` for clarity
3. Extract link builders to separate services
4. Add comprehensive test suite (Pest browser tests for deep-links)
5. Implement rate limiting middleware

---

## Testing Strategy

### Unit Tests Required
- ✅ `PaymentRequest` model methods (already tested)
- ❌ Driver `buildLink()` methods (missing)
- ❌ Driver `validateAccount()` methods (missing)
- ❌ Factory `make()` method (missing)

### Feature Tests Required
- ❌ End-to-end payment request creation
- ❌ Channel routing logic
- ❌ Recurring billing cycle generation
- ❌ Dunning event scheduling
- ❌ Auto-confirmation for high-trust members

### Browser Tests Required (Pest 4)
- ❌ Deep-link generation and click-through
- ❌ Smart Pay page channel selection
- ❌ Member payment marking flow
- ❌ Owner confirmation flow

**Test Coverage Goal:** 80%+ for core payment logic

---

## Security Considerations

### ✅ Already Implemented
- HTTPS-only payment links
- CSRF protection on all forms
- Rate limiting on API endpoints
- Two-factor authentication support
- Activity logging via Spatie

### ⚠️ Additional Recommendations
1. **Link Expiration:** Add `expires_at` to payment requests
2. **Webhook Signature Verification:** Validate Square webhook signatures (already configured)
3. **PCI Compliance:** Document that no card data is stored/processed
4. **Channel Verification:** Require verification before allowing owner to receive payments
5. **Fraud Detection:** Monitor unusual payment patterns per member

---

## Performance Considerations

### Current Bottlenecks
1. **N+1 Queries:** Payment request listing may not eager-load relationships
2. **Queue Jobs:** Scheduler jobs should be queued, not run synchronously
3. **Link Generation:** Building links for all channels on every request

### Optimizations Recommended
1. Eager-load `member.user`, `cycle.group` in payment request queries
2. Queue all dunning jobs via Horizon
3. Cache owner payout channel configuration
4. Only build links for preferred channel + one fallback
5. Add database indexes on `status`, `sent_at`, `auto_confirm_at`

---

## API Design (Future Mobile App)

### Endpoints Needed
```
GET    /api/v1/payment-requests/{id}
POST   /api/v1/payment-requests/{id}/mark-paid
POST   /api/v1/payment-requests/{id}/confirm
POST   /api/v1/payment-requests/{id}/dispute
GET    /api/v1/groups/{id}/payment-requests
GET    /api/v1/members/{id}/payment-history
POST   /api/v1/owner/payout-channels
GET    /api/v1/owner/payout-channels
```

### Response Format
```json
{
  "id": 123,
  "amount": 50.00,
  "status": "sent",
  "preferred_channel": "VENMO",
  "payment_options": [
    {
      "method": "venmo",
      "uri": "venmo://paycharge?...",
      "web_fallback": "https://venmo.com/...",
      "is_preferred": true
    },
    {
      "method": "cash_app",
      "uri": "https://cash.app/...",
      "web_fallback": "https://cash.app/...",
      "is_preferred": false
    }
  ],
  "member": { ... },
  "cycle": { ... }
}
```

---

## Monitoring & Observability

### Metrics to Track
1. **Payment Success Rate:** `confirmed / sent` by channel
2. **Channel Preference Distribution:** Which channels members prefer
3. **Time to Payment:** Average days from `sent_at` to `confirmed_at`
4. **Dunning Effectiveness:** Payment rate by reminder attempt
5. **Trust Score Distribution:** Member reliability metrics
6. **Channel Failure Rate:** Deep-link open failures by platform

### Logs to Capture
- Payment request generation failures
- Driver validation errors
- Square API errors
- Webhook signature mismatches
- Auto-confirmation decisions

### Alerts Needed
- Payment success rate drops below 80%
- Unusually high dispute rate
- Square API downtime
- Failed scheduler jobs
- Webhook delivery failures

---

## Conclusion

### Summary
The payment request architecture is **well-designed** but has **critical implementation gaps** that prevent production deployment. The driver pattern is solid, the database schema is comprehensive, and the tracking system is sophisticated.

### Immediate Action Required
Focus on **P0 and P1 fixes** to achieve a production-ready MVP:
1. Fix factory class names (5 min)
2. Fix payment link routing to owner (30 min)
3. Implement scheduler jobs (3 hours)
4. Enforce channel preferences (45 min)

**Total Time to MVP:** ~5 hours of focused development

### Long-Term Vision
With P2 and P3 enhancements, this system can scale to:
- Additional payment channels (Zelle, PayPal, Apple Pay)
- International markets (different P2P apps)
- Business accounts and larger groups
- White-label deployment for different verticals

---

## Appendix: File Reference

### Core Files
- **Contracts:** `app/Contracts/PaymentRequestDriver.php`
- **Drivers:** `app/Services/PaymentDrivers/{Venmo,CashApp}Driver.php`
- **Factory:** `app/Services/PaymentDriverFactory.php`
- **Service:** `app/Services/PaymentRequestService.php`
- **Models:** `app/Models/{User,Group,GroupMember,PaymentRequest,BillingCycle}.php`
- **Config:** `config/payment_requests.php`

### Database Tables
- `users` - Owner payout channels
- `groups` - Group settings and billing mode
- `group_members` - Member channel preferences
- `billing_cycles` - Recurring billing periods
- `payment_requests` - Individual payment tracking
- `dunning_events` - Reminder history

### Documentation
- `CLAUDE.md` - Project overview and conventions
- `SQUARE_INTEGRATION_SUMMARY.md` - Square OAuth architecture
- This file: `ARCHITECTURE_REVIEW.md`

---

**Last Updated:** 2026-02-22
**Next Review:** After P0/P1 completion or 2026-03-22 (whichever comes first)
