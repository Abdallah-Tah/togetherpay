# TogetherPay Codebase Analysis

Date: November 10, 2025
Project: TogetherPay - Group Bill Management Platform
Stack: Laravel 12 + Livewire 3 (Volt) + PostgreSQL/SQLite + Redis

## 1. DATABASE SCHEMA - CORE TABLES

### Groups Table
- owner_id (FK), name, timezone, currency (USD, EUR, GBP, CAD)
- cadence (MONTHLY, EVERY_4_WEEKS), due_day (1-28), grace_days (0-30)
- total_amount_cents (nullable), status (active, paused, archived)
- Soft deletes enabled

### Group Members Table
- group_id, user_id (unique combination)
- preferred_channel (VENMO, CASH_APP, CARD, UNKNOWN)
- venmo_username, cash_tag ($cashtag format), cash_phone, cash_email
- share_type (AMOUNT, PERCENT), share_value (decimal 10,2)
- status (active, paused, left), verified_at (datetime nullable)
- Soft deletes enabled

### Billing Cycles Table
- group_id, period_start, period_end, due_at
- status (draft, collecting, closed, cancelled)
- total_amount_cents, collected_amount_cents (tracks confirmed payments)

### Payment Requests Table (WITH V2 ENHANCEMENTS)
- group_id, cycle_id, member_id
- amount_cents (required)
- method_hint (formerly channel: VENMO, CASH_APP, CARD)
- pr_code (varchar 64, unique, deterministic code for offline reconciliation)
- note (varchar 160, wallet-compatible)
- deep_link (formerly link), web_fallback_link
- Status lifecycle: pending -> sent -> member_marked -> owner_confirmed -> auto_verified (or paid)
  Alternative: pending -> sent -> failed, or -> expired
- Timestamps: initiated_at, sent_at, marked_at (member claim), confirmed_at (owner confirmation), paid_at (legacy)
- evidence_url (nullable), evidence_type (screenshot, etc.)
- metadata (json), fail_reason (text)
- Soft deletes enabled

### Dunning Events Table
- payment_request_id
- kind (reminder type: initial, first_reminder, second_reminder, etc.)
- channel (push, sms, email, default: push)
- Tracking: sent_at, delivered_at, opened_at
- meta (json for notification details)

### Users Table (EXTENDED)
Base fields + payment accounts:
- cashapp_tag (normalized with $ prefix)
- cashapp_customer_id (OAuth ID)
- venmo_username (normalized with @ prefix)
- venmo_customer_id (OAuth ID)
- preferred_payment_method (default: CASH_APP)
- payment_metadata (json)
- role (owner, member, admin)
- onboarding_completed_at
- notification_preferences (json)
- Invitation fields: invitation_token, invitation_sent_at, invitation_accepted_at, is_placeholder

### Supporting Tables
- exports (with format: csv/pdf, params for filtering)
- devices (fcm_token for push notifications, platform: ios/android/web)
- group_invitations (email, token, accepted_at tracking)
- notifications (Laravel notifications table)

## 2. MODEL RELATIONSHIPS

User:
- ownedGroups() -> hasMany(Group, 'owner_id')
- groupMemberships() -> hasMany(GroupMember)
- exports(), devices()

Group:
- owner() -> belongsTo(User, 'owner_id')
- members() -> hasMany(GroupMember)
- cycles() -> hasMany(BillingCycle)
- currentCycle() method returns active/draft cycle

GroupMember:
- group(), user(), paymentRequests()
- isVerified() returns true if verified_at set OR has payment handle
- calculateAmount(totalCents) applies share_type (PERCENT or AMOUNT)
- getPaymentHandle() returns handle based on preferred_channel

BillingCycle:
- group(), paymentRequests()
- getCollectionRateAttribute() calculates percentage

PaymentRequest:
- group(), cycle(), member(), dunningEvents()
- isPaid() checks status in [paid, owner_confirmed, auto_verified]
- isPending() checks status in [pending, sent]
- markAsPaid() sets member_marked status
- confirmPayment() sets owner_confirmed, increments cycle.total_collected_cents
- markAsFailed(reason), markAsExpired()

DunningEvent:
- paymentRequest()

