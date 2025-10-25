# TogetherPay Architecture V2 - Production-Ready P2P System

**Status:** Ready for Implementation
**Created:** October 2025
**Stack:** Laravel 12 + Livewire 3 (Volt) + Flutter (mobile)

---

## Executive Summary

**CODEX AGENT (TogetherPay)** — Group bill management using **fee-free P2P** (Venmo/Cash App) with **push-triggered deep links**, **verifiable PR codes**, and **owner bulk-confirm**. No money custody. Optional card/Apple/Google Pay fallback in future premium tier.

### Core Principles

1. **No forced URLs in SMS/email** — Primary trigger is **push notification** → app opens → deep link launches wallet
2. **Deterministic reconciliation** — Every request carries a **Payment Request Code (PRC)** embedded in wallet note for instant owner confirmation
3. **Tight access control** — Members see only their groups and requests; owners see full group status
4. **Fee-free MVP** — P2P transfers incur zero processing fees

---

## Domain Model

### Entity Relationships

```
Users ─┬─< GroupMembers >─┬─ Groups ──< BillingCycles >──< PaymentRequests >──< DunningEvents
       │                   │
       └───────────────────┘ (owner is also a GroupMember with role='owner')
```

### Core Entities

- **Users** — Authenticated users with payment account preferences
- **Groups** — Bill-splitting groups owned by one user
- **GroupMembers** — Users participating in groups with share allocation and wallet preferences
- **BillingCycles** — Recurring periods (monthly/4-week) for bill collection
- **PaymentRequests** — Individual payment requests with PR codes for tracking
- **DunningEvents** — Reminders and retry attempts for pending requests

---

## Database Schema

### Groups Table

```sql
CREATE TABLE groups (
    id BIGSERIAL PRIMARY KEY,
    owner_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    total_amount_cents INT NOT NULL,
    due_day INT NOT NULL CHECK (due_day BETWEEN 1 AND 31),
    timezone VARCHAR(50) DEFAULT 'America/Chicago',
    status VARCHAR(20) DEFAULT 'active' CHECK (status IN ('active', 'paused', 'closed')),
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);

CREATE INDEX idx_groups_owner_status ON groups(owner_id, status);
```

### Group Members Table

```sql
CREATE TABLE group_members (
    id BIGSERIAL PRIMARY KEY,
    group_id BIGINT NOT NULL REFERENCES groups(id) ON DELETE CASCADE,
    user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    role VARCHAR(20) DEFAULT 'member' CHECK (role IN ('owner', 'member')),
    share_amount_cents INT NOT NULL,
    wallet_pref VARCHAR(20) CHECK (wallet_pref IN ('cashapp', 'venmo', 'card', 'applepay', 'googlepay')),
    cash_tag VARCHAR(50),
    venmo_username VARCHAR(50),
    status VARCHAR(20) DEFAULT 'active' CHECK (status IN ('active', 'left', 'removed')),
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW(),

    UNIQUE(group_id, user_id)
);

CREATE INDEX idx_group_members_user ON group_members(user_id, status);
CREATE INDEX idx_group_members_group ON group_members(group_id, status);
```

### Billing Cycles Table

```sql
CREATE TABLE billing_cycles (
    id BIGSERIAL PRIMARY KEY,
    group_id BIGINT NOT NULL REFERENCES groups(id) ON DELETE CASCADE,
    cycle_month DATE NOT NULL,
    period_start DATE NOT NULL,
    period_end DATE NOT NULL,
    due_date DATE NOT NULL,
    status VARCHAR(20) DEFAULT 'open' CHECK (status IN ('open', 'closing', 'closed', 'carried_forward')),
    total_expected_cents INT NOT NULL,
    total_collected_cents INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW(),

    UNIQUE(group_id, cycle_month)
);

CREATE INDEX idx_billing_cycles_group_status ON billing_cycles(group_id, status);
CREATE INDEX idx_billing_cycles_due_date ON billing_cycles(due_date, status);
```

### Payment Requests Table (Enhanced)

```sql
CREATE TABLE payment_requests (
    id BIGSERIAL PRIMARY KEY,
    group_id BIGINT NOT NULL REFERENCES groups(id) ON DELETE CASCADE,
    member_id BIGINT NOT NULL REFERENCES group_members(id) ON DELETE CASCADE,
    cycle_id BIGINT NOT NULL REFERENCES billing_cycles(id) ON DELETE CASCADE,

    -- Amount and method
    amount_cents INT NOT NULL,
    method_hint VARCHAR(20) CHECK (method_hint IN ('cashapp', 'venmo', 'card', 'applepay', 'googlepay')),

    -- PR Code for reconciliation
    pr_code VARCHAR(64) UNIQUE NOT NULL,
    note VARCHAR(160),

    -- Enhanced status tracking
    status VARCHAR(30) DEFAULT 'pending' CHECK (status IN (
        'pending', 'sent', 'member_marked', 'owner_confirmed',
        'auto_verified', 'failed', 'expired'
    )),

    -- Payment evidence
    evidence_url TEXT,
    evidence_type VARCHAR(20) CHECK (evidence_type IN ('photo', 'screenshot', 'confirmation_code')),

    -- Timestamps for state transitions
    initiated_at TIMESTAMP,
    sent_at TIMESTAMP,
    marked_at TIMESTAMP,
    confirmed_at TIMESTAMP,

    -- Deep link storage
    deep_link TEXT,
    web_fallback_link TEXT,

    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);

CREATE INDEX idx_payment_requests_group_cycle ON payment_requests(group_id, cycle_id, status);
CREATE INDEX idx_payment_requests_member ON payment_requests(member_id, cycle_id);
CREATE INDEX idx_payment_requests_pr_code ON payment_requests(pr_code);
CREATE INDEX idx_payment_requests_status_sent ON payment_requests(status, sent_at);
```

### Dunning Events Table

```sql
CREATE TABLE dunning_events (
    id BIGSERIAL PRIMARY KEY,
    payment_request_id BIGINT NOT NULL REFERENCES payment_requests(id) ON DELETE CASCADE,
    kind VARCHAR(20) NOT NULL CHECK (kind IN ('initial', 'retry1', 'retry2', 'final', 'late_notice')),
    channel VARCHAR(20) NOT NULL CHECK (channel IN ('push', 'sms', 'email')),
    sent_at TIMESTAMP NOT NULL DEFAULT NOW(),
    delivered_at TIMESTAMP,
    opened_at TIMESTAMP,
    meta JSONB,

    created_at TIMESTAMP DEFAULT NOW()
);

CREATE INDEX idx_dunning_events_pr ON dunning_events(payment_request_id, kind);
CREATE INDEX idx_dunning_events_sent ON dunning_events(sent_at, channel);
```

### Users Table (Enhanced)

```sql
-- Add these columns to existing users table
ALTER TABLE users ADD COLUMN IF NOT EXISTS cash_tag VARCHAR(50);
ALTER TABLE users ADD COLUMN IF NOT EXISTS venmo_username VARCHAR(50);
ALTER TABLE users ADD COLUMN IF NOT EXISTS preferred_payment_method VARCHAR(20) DEFAULT 'cashapp';
ALTER TABLE users ADD COLUMN IF NOT EXISTS fcm_token VARCHAR(255);
ALTER TABLE users ADD COLUMN IF NOT EXISTS apn_token VARCHAR(255);
```

---

## Payment Request Code (PRC) System

### Purpose

PR Codes solve the "how do we know they paid?" problem by providing deterministic reconciliation without API webhooks.

### Format

```
PR_{cycle_ym}_G{group_id}_M{member_id}_{random}

Examples:
PR_2025-11_G123_M42_a3f9c2
PR_2025-12_G456_M89_7b2e1d
```

### Implementation

```php
<?php

namespace App\Support;

final class PRCode
{
    /**
     * Generate a unique Payment Request Code
     */
    public static function make(int $groupId, int $memberId, string $cycleYm): string
    {
        $random = substr(bin2hex(random_bytes(3)), 0, 6);

        return sprintf(
            'PR_%s_G%d_M%d_%s',
            $cycleYm,
            $groupId,
            $memberId,
            $random
        );
    }

    /**
     * Parse PR Code back into components
     */
    public static function parse(string $prCode): ?array
    {
        $pattern = '/^PR_(\d{4}-\d{2})_G(\d+)_M(\d+)_([a-f0-9]{6})$/';

        if (!preg_match($pattern, $prCode, $matches)) {
            return null;
        }

        return [
            'cycle_ym' => $matches[1],
            'group_id' => (int) $matches[2],
            'member_id' => (int) $matches[3],
            'random' => $matches[4],
        ];
    }

    /**
     * Validate PR Code format
     */
    public static function isValid(string $prCode): bool
    {
        return self::parse($prCode) !== null;
    }
}
```

### Usage in Payment Flow

```php
use App\Support\PRCode;

// When creating payment request
$prCode = PRCode::make(
    groupId: $group->id,
    memberId: $member->id,
    cycleYm: $cycle->cycle_month->format('Y-m')
);

$paymentRequest = PaymentRequest::create([
    'group_id' => $group->id,
    'member_id' => $member->id,
    'cycle_id' => $cycle->id,
    'amount_cents' => $member->share_amount_cents,
    'pr_code' => $prCode,
    'note' => "T-Mobile Nov 2025 - {$prCode}",
    'status' => 'pending',
]);
```

---

## Payment Driver Pattern (Enhanced)

### Contract Interface

```php
<?php

namespace App\Contracts;

use App\Models\GroupMember;

interface PaymentRequestDriver
{
    /**
     * Build deep link and web fallback for payment request
     *
     * @return array ['uri' => 'venmo://...', 'web_fallback' => 'https://...']
     */
    public function buildLink(
        GroupMember $member,
        int $amountCents,
        string $prCode,
        string $note
    ): array;

    /**
     * Validate payment account format
     */
    public function validateAccount(string $account): bool;
}
```

### Venmo Driver Implementation

```php
<?php

namespace App\Drivers;

use App\Contracts\PaymentRequestDriver;
use App\Models\GroupMember;

final class VenmoLinkDriver implements PaymentRequestDriver
{
    public function buildLink(
        GroupMember $member,
        int $amountCents,
        string $prCode,
        string $note
    ): array {
        $amount = number_format($amountCents / 100, 2, '.', '');
        $owner = $member->group->owner;
        $recipient = ltrim($owner->venmo_username, '@');

        // Include PR code in note for reconciliation
        $fullNote = $note . ' ' . $prCode;
        $encodedNote = rawurlencode($fullNote);

        $uri = sprintf(
            'venmo://pay?recipients=%s&amount=%s&note=%s',
            $recipient,
            $amount,
            $encodedNote
        );

        $webFallback = sprintf(
            'https://venmo.com/%s?txn=pay&amount=%s&note=%s',
            $recipient,
            $amount,
            $encodedNote
        );

        return [
            'uri' => $uri,
            'web_fallback' => $webFallback,
        ];
    }

    public function validateAccount(string $account): bool
    {
        // Venmo username: @username or username (alphanumeric, hyphen, underscore)
        $account = ltrim($account, '@');
        return preg_match('/^[a-zA-Z0-9_-]{1,30}$/', $account);
    }
}
```

### Cash App Driver Implementation

```php
<?php

namespace App\Drivers;

use App\Contracts\PaymentRequestDriver;
use App\Models\GroupMember;

final class CashAppLinkDriver implements PaymentRequestDriver
{
    public function buildLink(
        GroupMember $member,
        int $amountCents,
        string $prCode,
        string $note
    ): array {
        $amount = number_format($amountCents / 100, 2, '.', '');
        $owner = $member->group->owner;
        $cashTag = ltrim($owner->cash_tag, '$');

        // Include PR code in note
        $fullNote = $note . ' ' . $prCode;
        $encodedNote = rawurlencode($fullNote);

        $uri = sprintf(
            'https://cash.app/$%s/%s?note=%s',
            $cashTag,
            $amount,
            $encodedNote
        );

        return [
            'uri' => $uri,
            'web_fallback' => $uri, // Same URL works for web
        ];
    }

    public function validateAccount(string $account): bool
    {
        // Cash tag: $cashtag (alphanumeric only, 3-20 chars)
        $account = ltrim($account, '$');
        return preg_match('/^[a-zA-Z0-9]{3,20}$/', $account);
    }
}
```

### Driver Factory

```php
<?php

namespace App\Services;

use App\Contracts\PaymentRequestDriver;
use App\Drivers\CashAppLinkDriver;
use App\Drivers\VenmoLinkDriver;

final class PaymentDriverFactory
{
    public static function make(string $method): PaymentRequestDriver
    {
        return match ($method) {
            'cashapp' => new CashAppLinkDriver(),
            'venmo' => new VenmoLinkDriver(),
            default => throw new \InvalidArgumentException("Unsupported payment method: {$method}"),
        };
    }
}
```

---

## Push Notification Strategy

### Why Push-First?

1. **Better UX** — One tap from notification → wallet opens with prefilled payment
2. **No URL issues** — Avoids SMS character limits and spam filters
3. **Higher engagement** — Push notifications have 7x higher open rates than email
4. **Instant delivery** — No carrier delays

### FCM Push Payload

```php
<?php

namespace App\Notifications;

use App\Models\PaymentRequest;
use Illuminate\Notifications\Notification;
use NotificationChannels\Fcm\FcmChannel;
use NotificationChannels\Fcm\FcmMessage;
use NotificationChannels\Fcm\Resources\Notification as FcmNotification;

class PaymentRequestNotification extends Notification
{
    public function __construct(
        private PaymentRequest $paymentRequest
    ) {}

    public function via($notifiable): array
    {
        return [FcmChannel::class, 'mail', 'database'];
    }

    public function toFcm($notifiable): FcmMessage
    {
        $amount = number_format($this->paymentRequest->amount_cents / 100, 2);
        $group = $this->paymentRequest->group;

        return FcmMessage::create()
            ->setNotification(
                FcmNotification::create()
                    ->setTitle("Pay \${$amount} for {$group->name}")
                    ->setBody("Due today • Tap to pay")
                    ->setImage($group->logo_url)
            )
            ->setData([
                'type' => 'payment_request',
                'pr_id' => (string) $this->paymentRequest->id,
                'pr_code' => $this->paymentRequest->pr_code,
                'deep_link' => $this->paymentRequest->deep_link,
                'amount_cents' => (string) $this->paymentRequest->amount_cents,
                'group_id' => (string) $group->id,
            ]);
    }

    public function toMail($notifiable): \Illuminate\Notifications\Messages\MailMessage
    {
        $amount = number_format($this->paymentRequest->amount_cents / 100, 2);

        return (new \Illuminate\Notifications\Messages\MailMessage)
            ->subject("Payment Due: \${$amount}")
            ->line("Your payment for {$this->paymentRequest->group->name} is due.")
            ->line("Amount: \${$amount}")
            ->line("PR Code: {$this->paymentRequest->pr_code}")
            ->action('View Payment', route('smartpay.show', $this->paymentRequest->id));
    }
}
```

### Flutter App Handling

```dart
// lib/services/notification_service.dart

class NotificationService {
  static Future<void> handleNotification(RemoteMessage message) async {
    final data = message.data;
    final type = data['type'];

    if (type == 'payment_request') {
      final deepLink = data['deep_link'];
      final prId = int.parse(data['pr_id']);

      // Show in-app dialog first
      final shouldPay = await _showPaymentDialog(prId);

      if (shouldPay && deepLink != null) {
        // Launch wallet app with deep link
        await _launchDeepLink(deepLink);

        // After returning, show "Mark as Paid" prompt
        await _showMarkPaidPrompt(prId);
      }
    }
  }

  static Future<void> _launchDeepLink(String url) async {
    final uri = Uri.parse(url);

    if (await canLaunchUrl(uri)) {
      await launchUrl(uri, mode: LaunchMode.externalApplication);
    } else {
      // Fallback to web page
      final prId = _extractPrIdFromUrl(url);
      final fallback = Uri.parse('https://app.togetherpay.com/pay/$prId');
      await launchUrl(fallback);
    }
  }

  static Future<void> _showMarkPaidPrompt(int prId) async {
    await showDialog(
      context: navigatorKey.currentContext!,
      builder: (context) => AlertDialog(
        title: Text('Did you complete the payment?'),
        content: Text('Mark this payment as sent so your group owner can confirm it.'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: Text('Not Yet'),
          ),
          ElevatedButton(
            onPressed: () async {
              await _markAsPaid(prId);
              Navigator.pop(context);
            },
            child: Text('Mark as Paid'),
          ),
        ],
      ),
    );
  }
}
```

---

## Payment Request State Machine

### States

```
pending → sent → member_marked → owner_confirmed → (done)
                      ↓
                  expired/failed
```

### State Transitions

| From | To | Trigger | Who |
|------|-----|---------|-----|
| `pending` | `sent` | Push notification sent | System |
| `sent` | `member_marked` | Member marks as paid | Member |
| `sent` | `expired` | Deadline passed | Scheduler |
| `member_marked` | `owner_confirmed` | Owner confirms payment | Owner |
| `member_marked` | `auto_verified` | Plaid/Stripe confirms (future) | System |
| Any | `failed` | Error occurred | System |

### API Endpoints for State Transitions

```php
<?php

namespace App\Http\Controllers\Api;

use App\Models\PaymentRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PaymentRequestController extends Controller
{
    /**
     * Member marks payment as paid
     * POST /api/v1/requests/{prCode}/mark
     */
    public function markPaid(string $prCode, Request $request)
    {
        $pr = PaymentRequest::where('pr_code', $prCode)->firstOrFail();

        Gate::authorize('markPaid', $pr);

        $validated = $request->validate([
            'evidence_url' => 'nullable|url',
            'evidence_type' => 'nullable|in:photo,screenshot,confirmation_code',
        ]);

        $pr->update([
            'status' => 'member_marked',
            'marked_at' => now(),
            'evidence_url' => $validated['evidence_url'] ?? null,
            'evidence_type' => $validated['evidence_type'] ?? null,
        ]);

        // Notify owner
        $pr->group->owner->notify(new \App\Notifications\PaymentMarkedNotification($pr));

        return response()->json([
            'ok' => true,
            'pr' => $pr,
        ]);
    }

    /**
     * Owner confirms payment received
     * POST /api/v1/requests/{prCode}/confirm
     */
    public function confirm(string $prCode)
    {
        $pr = PaymentRequest::where('pr_code', $prCode)->firstOrFail();

        Gate::authorize('confirm', $pr);

        $pr->update([
            'status' => 'owner_confirmed',
            'confirmed_at' => now(),
        ]);

        // Update cycle collected amount
        $pr->cycle->increment('total_collected_cents', $pr->amount_cents);

        return response()->json([
            'ok' => true,
            'pr' => $pr,
        ]);
    }

    /**
     * Owner bulk confirms multiple payments
     * POST /api/v1/cycles/{cycleId}/bulk-confirm
     */
    public function bulkConfirm(int $cycleId, Request $request)
    {
        $cycle = \App\Models\BillingCycle::findOrFail($cycleId);

        Gate::authorize('manage', $cycle->group);

        $validated = $request->validate([
            'pr_codes' => 'required|array',
            'pr_codes.*' => 'required|string',
        ]);

        $confirmed = PaymentRequest::whereIn('pr_code', $validated['pr_codes'])
            ->where('cycle_id', $cycleId)
            ->where('status', 'member_marked')
            ->update([
                'status' => 'owner_confirmed',
                'confirmed_at' => now(),
            ]);

        // Update cycle total
        $totalConfirmed = PaymentRequest::whereIn('pr_code', $validated['pr_codes'])
            ->sum('amount_cents');

        $cycle->increment('total_collected_cents', $totalConfirmed);

        return response()->json([
            'ok' => true,
            'confirmed_count' => $confirmed,
            'total_cents' => $totalConfirmed,
        ]);
    }

    /**
     * Owner sends reminder/nudge
     * POST /api/v1/requests/{prCode}/nudge
     */
    public function nudge(string $prCode)
    {
        $pr = PaymentRequest::where('pr_code', $prCode)->firstOrFail();

        Gate::authorize('manage', $pr->group);

        // Dispatch reminder job
        \App\Jobs\SendPaymentReminderJob::dispatch($pr);

        return response()->json([
            'ok' => true,
            'message' => 'Reminder sent',
        ]);
    }
}
```

### Policies

```php
<?php

namespace App\Policies;

use App\Models\User;
use App\Models\PaymentRequest;

class PaymentRequestPolicy
{
    /**
     * Member can mark their own payment as paid
     */
    public function markPaid(User $user, PaymentRequest $pr): bool
    {
        return $pr->member->user_id === $user->id
            && in_array($pr->status, ['sent', 'pending']);
    }

    /**
     * Owner can confirm payment received
     */
    public function confirm(User $user, PaymentRequest $pr): bool
    {
        return $pr->group->owner_id === $user->id
            && $pr->status === 'member_marked';
    }

    /**
     * View payment request details
     */
    public function view(User $user, PaymentRequest $pr): bool
    {
        // Owner can see all requests in their groups
        if ($pr->group->owner_id === $user->id) {
            return true;
        }

        // Member can only see their own request
        return $pr->member->user_id === $user->id;
    }
}
```

---

## Scheduled Jobs

### Job 1: Create Upcoming Cycles

```php
<?php

namespace App\Jobs;

use App\Models\Group;
use App\Models\BillingCycle;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CreateUpcomingCyclesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $nextMonth = now()->addMonth()->startOfMonth();

        Group::where('status', 'active')
            ->chunk(100, function ($groups) use ($nextMonth) {
                foreach ($groups as $group) {
                    // Idempotent: only create if doesn't exist
                    BillingCycle::firstOrCreate(
                        [
                            'group_id' => $group->id,
                            'cycle_month' => $nextMonth,
                        ],
                        [
                            'period_start' => $nextMonth,
                            'period_end' => $nextMonth->copy()->endOfMonth(),
                            'due_date' => $nextMonth->copy()->day($group->due_day),
                            'status' => 'open',
                            'total_expected_cents' => $group->total_amount_cents,
                        ]
                    );
                }
            });
    }
}
```

### Job 2: Generate Payment Requests

```php
<?php

namespace App\Jobs;

use App\Models\BillingCycle;
use App\Models\PaymentRequest;
use App\Support\PRCode;
use App\Services\PaymentDriverFactory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class GeneratePaymentRequestsJob implements ShouldQueue
{
    use Dispatchable, Queueable;

    public function handle(): void
    {
        $cyclesReadyForGeneration = BillingCycle::where('status', 'open')
            ->whereDate('period_start', '<=', now()->addDays(7))
            ->whereDoesntHave('paymentRequests')
            ->get();

        foreach ($cyclesReadyForGeneration as $cycle) {
            $group = $cycle->group;
            $cycleYm = $cycle->cycle_month->format('Y-m');

            foreach ($group->members()->where('status', 'active')->get() as $member) {
                // Generate PR code
                $prCode = PRCode::make($group->id, $member->id, $cycleYm);

                // Build note
                $note = sprintf(
                    '%s %s cycle',
                    $group->name,
                    $cycle->cycle_month->format('M Y')
                );

                // Generate deep link
                $driver = PaymentDriverFactory::make($member->wallet_pref ?? 'cashapp');
                $links = $driver->buildLink(
                    $member,
                    $member->share_amount_cents,
                    $prCode,
                    $note
                );

                // Create request
                PaymentRequest::create([
                    'group_id' => $group->id,
                    'member_id' => $member->id,
                    'cycle_id' => $cycle->id,
                    'amount_cents' => $member->share_amount_cents,
                    'method_hint' => $member->wallet_pref,
                    'pr_code' => $prCode,
                    'note' => $note,
                    'deep_link' => $links['uri'],
                    'web_fallback_link' => $links['web_fallback'],
                    'status' => 'pending',
                ]);
            }
        }
    }
}
```

### Job 3: Send Initial Reminders

```php
<?php

namespace App\Jobs;

use App\Models\PaymentRequest;
use App\Notifications\PaymentRequestNotification;

class SendInitialRemindersJob
{
    public function handle(): void
    {
        // Get requests that are ready to send (due within 3 days)
        PaymentRequest::where('status', 'pending')
            ->whereHas('cycle', function ($q) {
                $q->whereDate('due_date', '<=', now()->addDays(3));
            })
            ->chunk(100, function ($requests) {
                foreach ($requests as $pr) {
                    // Send push notification
                    $pr->member->user->notify(new PaymentRequestNotification($pr));

                    // Update status
                    $pr->update([
                        'status' => 'sent',
                        'sent_at' => now(),
                    ]);
                }
            });
    }
}
```

### Job 4: Send Retry Reminders (Dunning)

```php
<?php

namespace App\Jobs;

use App\Models\PaymentRequest;
use App\Notifications\PaymentReminderNotification;
use Carbon\Carbon;

class SendRetryRemindersJob
{
    public function handle(): void
    {
        $this->sendRetry1(); // D-2 (friendly)
        $this->sendRetry2(); // D+2 (gentle)
        $this->sendFinal();  // D+5 (final)
    }

    private function sendRetry1(): void
    {
        // 2 days before due date
        PaymentRequest::where('status', 'sent')
            ->whereHas('cycle', fn($q) =>
                $q->whereDate('due_date', '=', now()->addDays(2)->toDateString())
            )
            ->whereDoesntHave('dunningEvents', fn($q) => $q->where('kind', 'retry1'))
            ->chunk(100, function ($requests) {
                foreach ($requests as $pr) {
                    $pr->member->user->notify(new PaymentReminderNotification($pr, 'friendly'));
                    $pr->dunningEvents()->create(['kind' => 'retry1', 'channel' => 'push', 'sent_at' => now()]);
                }
            });
    }

    private function sendRetry2(): void
    {
        // 2 days after due date
        PaymentRequest::where('status', 'sent')
            ->whereHas('cycle', fn($q) =>
                $q->whereDate('due_date', '=', now()->subDays(2)->toDateString())
            )
            ->whereDoesntHave('dunningEvents', fn($q) => $q->where('kind', 'retry2'))
            ->chunk(100, function ($requests) {
                foreach ($requests as $pr) {
                    $pr->member->user->notify(new PaymentReminderNotification($pr, 'gentle'));
                    $pr->dunningEvents()->create(['kind' => 'retry2', 'channel' => 'push', 'sent_at' => now()]);
                }
            });
    }

    private function sendFinal(): void
    {
        // 5 days after due date
        PaymentRequest::where('status', 'sent')
            ->whereHas('cycle', fn($q) =>
                $q->whereDate('due_date', '=', now()->subDays(5)->toDateString())
            )
            ->whereDoesntHave('dunningEvents', fn($q) => $q->where('kind', 'final'))
            ->chunk(100, function ($requests) {
                foreach ($requests as $pr) {
                    $pr->member->user->notify(new PaymentReminderNotification($pr, 'final'));
                    $pr->dunningEvents()->create(['kind' => 'final', 'channel' => 'push', 'sent_at' => now()]);

                    // Mark as expired after final attempt
                    $pr->update(['status' => 'expired']);
                }
            });
    }
}
```

### Job 5: Close Cycles

```php
<?php

namespace App\Jobs;

use App\Models\BillingCycle;

class CloseCyclesJob
{
    public function handle(): void
    {
        // Close cycles 7 days after due date
        BillingCycle::where('status', 'open')
            ->whereDate('due_date', '<', now()->subDays(7))
            ->chunk(100, function ($cycles) {
                foreach ($cycles as $cycle) {
                    // Carry forward unpaid requests
                    $unpaidRequests = $cycle->paymentRequests()
                        ->whereIn('status', ['sent', 'member_marked', 'pending'])
                        ->get();

                    if ($unpaidRequests->isNotEmpty()) {
                        $cycle->update(['status' => 'carried_forward']);

                        // Create next cycle with carried amounts
                        // (implementation depends on business rules)
                    } else {
                        $cycle->update(['status' => 'closed']);
                    }
                }
            });
    }
}
```

### Scheduler Registration

```php
<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        // Create upcoming cycles (1st of each month at midnight)
        $schedule->job(new \App\Jobs\CreateUpcomingCyclesJob)
            ->monthlyOn(1, '00:00')
            ->timezone('America/Chicago');

        // Generate payment requests (daily at 6 AM)
        $schedule->job(new \App\Jobs\GeneratePaymentRequestsJob)
            ->dailyAt('06:00')
            ->timezone('America/Chicago');

        // Send initial reminders (daily at 9 AM)
        $schedule->job(new \App\Jobs\SendInitialRemindersJob)
            ->dailyAt('09:00')
            ->timezone('America/Chicago');

        // Send retry reminders (daily at 10 AM and 4 PM)
        $schedule->job(new \App\Jobs\SendRetryRemindersJob)
            ->twiceDaily(10, 16)
            ->timezone('America/Chicago');

        // Close cycles (daily at 2 AM)
        $schedule->job(new \App\Jobs\CloseCyclesJob)
            ->dailyAt('02:00')
            ->timezone('America/Chicago');
    }
}
```

---

## API Reference (Mobile App)

### Base URL

```
Production: https://api.togetherpay.com/v1
Staging: https://staging-api.togetherpay.com/v1
```

### Authentication

All API endpoints require Laravel Sanctum token authentication:

```
Authorization: Bearer {token}
```

### Endpoints

#### Groups

```http
GET    /groups                      # List user's groups
POST   /groups                      # Create group (owner)
GET    /groups/{id}                 # Get group details
PATCH  /groups/{id}                 # Update group
DELETE /groups/{id}                 # Delete group
POST   /groups/{id}/members         # Add member
DELETE /groups/{id}/members/{memberId}  # Remove member
```

#### Billing Cycles

```http
GET    /groups/{id}/cycles          # List cycles for group
GET    /cycles/{id}                 # Get cycle details with rollup
```

#### Payment Requests

```http
GET    /requests                    # List user's payment requests
GET    /requests/{prCode}           # Get request by PR code
POST   /requests/{prCode}/mark      # Member marks as paid
POST   /requests/{prCode}/confirm   # Owner confirms payment
POST   /requests/{prCode}/nudge     # Owner sends reminder
POST   /cycles/{id}/bulk-confirm    # Owner bulk confirms
```

#### Example Responses

**GET /requests/{prCode}**

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
  "deep_link": "https://cash.app/$JohnDoe/50.00?note=T-Mobile+Nov+2025+PR_2025-11_G42_M17_a3f9c2",
  "web_fallback_link": "https://cash.app/$JohnDoe/50.00?note=T-Mobile+Nov+2025+PR_2025-11_G42_M17_a3f9c2",
  "due_date": "2025-11-15",
  "sent_at": "2025-11-13T09:00:00Z"
}
```

**POST /requests/{prCode}/mark** (200 OK)

```json
{
  "ok": true,
  "pr": {
    "id": 123,
    "pr_code": "PR_2025-11_G42_M17_a3f9c2",
    "status": "member_marked",
    "marked_at": "2025-11-14T14:32:00Z"
  }
}
```

---

## Owner Dashboard (Livewire Components)

### Bulk Confirm UI

```php
<?php
// resources/views/livewire/cycles/confirm-payments.blade.php

use Livewire\Volt\Component;
use App\Models\BillingCycle;

new class extends Component {
    public BillingCycle $cycle;
    public array $selectedPrCodes = [];

    public function mount(int $cycleId)
    {
        $this->cycle = BillingCycle::with([
            'paymentRequests' => fn($q) => $q->where('status', 'member_marked')
        ])->findOrFail($cycleId);
    }

    public function confirmSelected()
    {
        if (empty($this->selectedPrCodes)) {
            return;
        }

        $confirmed = \App\Models\PaymentRequest::whereIn('pr_code', $this->selectedPrCodes)
            ->where('cycle_id', $this->cycle->id)
            ->update([
                'status' => 'owner_confirmed',
                'confirmed_at' => now(),
            ]);

        $totalConfirmed = \App\Models\PaymentRequest::whereIn('pr_code', $this->selectedPrCodes)
            ->sum('amount_cents');

        $this->cycle->increment('total_collected_cents', $totalConfirmed);

        $this->dispatch('confirmed', count: $confirmed, total: $totalConfirmed);
        $this->selectedPrCodes = [];
        $this->mount($this->cycle->id); // Refresh
    }
}; ?>

<div class="space-y-4">
    <div class="flex justify-between items-center">
        <h2 class="text-xl font-bold">Payments to Confirm</h2>
        <flux:button
            wire:click="confirmSelected"
            :disabled="empty($selectedPrCodes)"
            variant="primary">
            Confirm {{ count($selectedPrCodes) }} Payment(s)
        </flux:button>
    </div>

    <div class="space-y-2">
        @forelse($cycle->paymentRequests as $pr)
            <flux:card>
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <input
                            type="checkbox"
                            wire:model="selectedPrCodes"
                            value="{{ $pr->pr_code }}"
                            class="rounded border-gray-300">

                        <div class="flex items-center gap-3">
                            <img
                                src="{{ $pr->member->user->avatar_url }}"
                                class="w-10 h-10 rounded-full">
                            <div>
                                <div class="font-medium">{{ $pr->member->user->name }}</div>
                                <div class="text-sm text-gray-500">{{ $pr->pr_code }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="text-right">
                        <div class="text-lg font-bold">${{ number_format($pr->amount_cents / 100, 2) }}</div>
                        <div class="text-sm text-gray-500">
                            Marked {{ $pr->marked_at->diffForHumans() }}
                        </div>
                    </div>

                    @if($pr->evidence_url)
                        <flux:button
                            size="sm"
                            variant="ghost"
                            onclick="window.open('{{ $pr->evidence_url }}', '_blank')">
                            View Receipt
                        </flux:button>
                    @endif
                </div>
            </flux:card>
        @empty
            <flux:card>
                <p class="text-gray-500 text-center py-8">
                    No payments waiting for confirmation
                </p>
            </flux:card>
        @endforelse
    </div>
</div>
```

---

## Testing Strategy

### Unit Tests (Pest)

```php
<?php
// tests/Unit/PRCodeTest.php

use App\Support\PRCode;

test('generates valid PR code', function () {
    $code = PRCode::make(groupId: 123, memberId: 456, cycleYm: '2025-11');

    expect($code)->toMatch('/^PR_2025-11_G123_M456_[a-f0-9]{6}$/');
});

test('parses PR code correctly', function () {
    $code = 'PR_2025-11_G123_M456_a3f9c2';
    $parsed = PRCode::parse($code);

    expect($parsed)->toBe([
        'cycle_ym' => '2025-11',
        'group_id' => 123,
        'member_id' => 456,
        'random' => 'a3f9c2',
    ]);
});

test('validates PR code format', function () {
    expect(PRCode::isValid('PR_2025-11_G123_M456_a3f9c2'))->toBeTrue();
    expect(PRCode::isValid('invalid_code'))->toBeFalse();
});
```

```php
<?php
// tests/Unit/PaymentDriverTest.php

use App\Drivers\CashAppLinkDriver;
use App\Drivers\VenmoLinkDriver;
use App\Models\GroupMember;

test('CashApp driver builds correct link', function () {
    $member = GroupMember::factory()
        ->for(User::factory()->state(['cash_tag' => '$TestUser']))
        ->create();

    $driver = new CashAppLinkDriver();
    $links = $driver->buildLink($member, 5000, 'PR_TEST', 'Test Payment');

    expect($links['uri'])
        ->toContain('cash.app/$TestUser/50.00')
        ->toContain('Test+Payment+PR_TEST');
});

test('Venmo driver builds correct link', function () {
    $member = GroupMember::factory()
        ->for(User::factory()->state(['venmo_username' => '@testuser']))
        ->create();

    $driver = new VenmoLinkDriver();
    $links = $driver->buildLink($member, 5000, 'PR_TEST', 'Test Payment');

    expect($links['uri'])
        ->toStartWith('venmo://pay')
        ->toContain('recipients=testuser')
        ->toContain('amount=50.00');
});
```

### Feature Tests

```php
<?php
// tests/Feature/PaymentRequestFlowTest.php

use App\Models\User;
use App\Models\Group;
use App\Models\BillingCycle;
use App\Models\PaymentRequest;

test('member can mark payment as paid', function () {
    $member = User::factory()->create();
    $pr = PaymentRequest::factory()->create(['status' => 'sent']);

    actingAs($member)
        ->postJson("/api/v1/requests/{$pr->pr_code}/mark")
        ->assertOk()
        ->assertJson(['ok' => true]);

    expect($pr->fresh()->status)->toBe('member_marked');
    expect($pr->fresh()->marked_at)->not->toBeNull();
});

test('owner can confirm payment', function () {
    $owner = User::factory()->create();
    $group = Group::factory()->for($owner, 'owner')->create();
    $pr = PaymentRequest::factory()
        ->for($group)
        ->state(['status' => 'member_marked'])
        ->create();

    actingAs($owner)
        ->postJson("/api/v1/requests/{$pr->pr_code}/confirm")
        ->assertOk();

    expect($pr->fresh()->status)->toBe('owner_confirmed');
});

test('member cannot confirm payment', function () {
    $member = User::factory()->create();
    $pr = PaymentRequest::factory()->state(['status' => 'member_marked'])->create();

    actingAs($member)
        ->postJson("/api/v1/requests/{$pr->pr_code}/confirm")
        ->assertForbidden();
});

test('owner can bulk confirm payments', function () {
    $owner = User::factory()->create();
    $group = Group::factory()->for($owner, 'owner')->create();
    $cycle = BillingCycle::factory()->for($group)->create();

    $pr1 = PaymentRequest::factory()->for($cycle)->state(['status' => 'member_marked'])->create();
    $pr2 = PaymentRequest::factory()->for($cycle)->state(['status' => 'member_marked'])->create();

    actingAs($owner)
        ->postJson("/api/v1/cycles/{$cycle->id}/bulk-confirm", [
            'pr_codes' => [$pr1->pr_code, $pr2->pr_code],
        ])
        ->assertOk()
        ->assertJson(['confirmed_count' => 2]);

    expect($pr1->fresh()->status)->toBe('owner_confirmed');
    expect($pr2->fresh()->status)->toBe('owner_confirmed');
});
```

### Job Tests

```php
<?php
// tests/Feature/Jobs/GeneratePaymentRequestsJobTest.php

use App\Jobs\GeneratePaymentRequestsJob;
use App\Models\BillingCycle;
use App\Models\PaymentRequest;

test('job generates payment requests for upcoming cycle', function () {
    $cycle = BillingCycle::factory()
        ->has(GroupMember::factory()->count(3), 'group.members')
        ->state(['period_start' => now()->addDays(5)])
        ->create();

    GeneratePaymentRequestsJob::dispatch();

    expect(PaymentRequest::where('cycle_id', $cycle->id)->count())->toBe(3);
});

test('job is idempotent', function () {
    $cycle = BillingCycle::factory()
        ->has(GroupMember::factory()->count(3), 'group.members')
        ->state(['period_start' => now()->addDays(5)])
        ->create();

    GeneratePaymentRequestsJob::dispatch();
    GeneratePaymentRequestsJob::dispatch(); // Run twice

    // Should still only have 3 requests
    expect(PaymentRequest::where('cycle_id', $cycle->id)->count())->toBe(3);
});
```

---

## Configuration

### Environment Variables

```env
# App
APP_NAME=TogetherPay
APP_ENV=production
APP_URL=https://togetherpay.com

# Database
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=togetherpay
DB_USERNAME=postgres
DB_PASSWORD=secret

# Queue & Cache
QUEUE_CONNECTION=redis
CACHE_STORE=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Notifications
FCM_SERVER_KEY=your_firebase_server_key
PUSH_ENABLED=true
SMS_ENABLED=false
EMAIL_ENABLED=true

MAIL_MAILER=ses
MAIL_FROM_ADDRESS=noreply@togetherpay.com
MAIL_FROM_NAME="${APP_NAME}"

# Payment Links
PR_DRIVER=link
CASHAPP_TAG_REQUIRED=true
VENMO_USERNAME_REQUIRED=true
LINK_SHORTENER_BASE=https://tpay.app/p/

# Feature Flags
FF_RECEIPT_UPLOAD=true
FF_PLAID_READONLY=false
FF_STRIPE_FALLBACK=false
FF_BULK_CONFIRM=true

# Storage
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=togetherpay-prod
```

### Config File: payment_requests.php

```php
<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Payment Driver
    |--------------------------------------------------------------------------
    |
    | Supported: "link" (deep links), "api" (future)
    |
    */
    'driver' => env('PR_DRIVER', 'link'),

    /*
    |--------------------------------------------------------------------------
    | Payment Method Requirements
    |--------------------------------------------------------------------------
    */
    'require_cashapp_tag' => env('CASHAPP_TAG_REQUIRED', true),
    'require_venmo_username' => env('VENMO_USERNAME_REQUIRED', true),

    /*
    |--------------------------------------------------------------------------
    | Link Shortener
    |--------------------------------------------------------------------------
    */
    'shortener' => [
        'enabled' => env('LINK_SHORTENER_ENABLED', false),
        'base_url' => env('LINK_SHORTENER_BASE', 'https://tpay.app/p/'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Dunning Schedule
    |--------------------------------------------------------------------------
    |
    | Days relative to due date for sending reminders
    |
    */
    'dunning' => [
        'retry1_days_before' => 2,  // D-2 (friendly reminder)
        'retry2_days_after' => 2,   // D+2 (gentle nudge)
        'final_days_after' => 5,    // D+5 (final notice)
    ],

    /*
    |--------------------------------------------------------------------------
    | Grace Period
    |--------------------------------------------------------------------------
    |
    | Days after due date before marking cycle as closed
    |
    */
    'grace_period_days' => 7,
];
```

---

## 2-Sprint Rollout Plan

### Sprint 1: Core Infrastructure (2 weeks)

**Week 1: Database & Models**
- [ ] Create migrations for all tables (groups, group_members, billing_cycles, payment_requests, dunning_events)
- [ ] Add indexes for performance
- [ ] Create/update Eloquent models with relationships
- [ ] Write model factories for testing
- [ ] Implement PRCode utility class
- [ ] Write unit tests for PRCode

**Week 2: Payment Drivers & Links**
- [ ] Create PaymentRequestDriver contract
- [ ] Implement CashAppLinkDriver
- [ ] Implement VenmoLinkDriver
- [ ] Create PaymentDriverFactory
- [ ] Write unit tests for drivers
- [ ] Implement link generation in PaymentRequest model

**Deliverables:**
- Complete database schema
- Working PR Code system
- Deep link generation for Cash App and Venmo
- 90% test coverage on core utilities

### Sprint 2: Jobs, API & UI (2 weeks)

**Week 3: Scheduled Jobs & Notifications**
- [ ] Implement CreateUpcomingCyclesJob
- [ ] Implement GeneratePaymentRequestsJob
- [ ] Implement SendInitialRemindersJob
- [ ] Implement SendRetryRemindersJob
- [ ] Implement CloseCyclesJob
- [ ] Register jobs in Kernel scheduler
- [ ] Create PaymentRequestNotification (FCM)
- [ ] Create PaymentReminderNotification
- [ ] Write job tests (idempotency, scheduling)

**Week 4: API & Dashboard UI**
- [ ] Create PaymentRequestController with routes
- [ ] Implement mark-paid endpoint
- [ ] Implement confirm-payment endpoint
- [ ] Implement bulk-confirm endpoint
- [ ] Implement nudge/reminder endpoint
- [ ] Create PaymentRequestPolicy
- [ ] Create Livewire bulk-confirm component
- [ ] Write feature tests for API
- [ ] Update Smart Pay page with PR codes

**Deliverables:**
- Fully functional payment request lifecycle
- Push notifications working
- Owner dashboard with bulk confirm
- Member mobile app can mark as paid
- End-to-end feature tests passing

---

## Migration from Current Implementation

### Current State

Based on existing documentation:
- ✅ P2P deep links working (Cash App, Venmo)
- ✅ Smart Pay page implemented
- ✅ Payment accounts in user settings
- ❌ No PR Code system
- ❌ No push notifications
- ❌ Manual payment tracking (no states)
- ❌ No bulk confirm UI

### Migration Steps

**Phase 1: Add PR Codes to Existing System (Non-Breaking)**

```sql
-- Add pr_code column to payment_requests
ALTER TABLE payment_requests
ADD COLUMN pr_code VARCHAR(64) UNIQUE;

-- Backfill existing requests with generated codes
UPDATE payment_requests
SET pr_code = CONCAT('PR_',
    TO_CHAR(created_at, 'YYYY-MM'),
    '_G', group_id,
    '_M', member_id,
    '_', SUBSTRING(MD5(RANDOM()::text), 1, 6)
)
WHERE pr_code IS NULL;

-- Make pr_code NOT NULL after backfill
ALTER TABLE payment_requests
ALTER COLUMN pr_code SET NOT NULL;
```

**Phase 2: Enhance State Machine**

```sql
-- Update status enum to include new states
ALTER TABLE payment_requests
DROP CONSTRAINT IF EXISTS payment_requests_status_check;

ALTER TABLE payment_requests
ADD CONSTRAINT payment_requests_status_check
CHECK (status IN ('pending', 'sent', 'member_marked', 'owner_confirmed',
                  'auto_verified', 'failed', 'expired'));

-- Add new timestamp columns
ALTER TABLE payment_requests
ADD COLUMN marked_at TIMESTAMP,
ADD COLUMN confirmed_at TIMESTAMP;
```

**Phase 3: Deploy Scheduled Jobs**

1. Deploy job classes
2. Register in scheduler
3. Monitor first run in staging
4. Enable in production

**Phase 4: Deploy Mobile App Updates**

1. Add FCM integration
2. Implement push notification handling
3. Add "Mark as Paid" workflow
4. Deploy to TestFlight/Play Beta
5. Gradual rollout to production

**Phase 5: Deploy Owner Dashboard**

1. Create bulk-confirm Livewire component
2. Update groups/cycles views
3. Test in staging
4. Deploy to production

---

## Security Considerations

### Access Control

- **GroupPolicy** — Only owner can manage group, members can view
- **PaymentRequestPolicy** — Members see only their requests, owners see all group requests
- **BillingCyclePolicy** — Only group owner can close/modify cycles

### Rate Limiting

```php
// In routes/api.php
Route::middleware(['auth:sanctum', 'throttle:100,1'])->group(function () {
    Route::post('/requests/{prCode}/mark', [PaymentRequestController::class, 'markPaid']);
    Route::post('/requests/{prCode}/confirm', [PaymentRequestController::class, 'confirm']);
});

Route::middleware(['auth:sanctum', 'throttle:10,1'])->group(function () {
    Route::post('/cycles/{id}/bulk-confirm', [PaymentRequestController::class, 'bulkConfirm']);
    Route::post('/requests/{prCode}/nudge', [PaymentRequestController::class, 'nudge']);
});
```

### Data Privacy

- PR Codes don't expose sensitive information (use random suffix)
- Evidence URLs stored in private S3 bucket with signed URLs
- FCM tokens encrypted at rest
- Member phone/email only visible to group owner

### Audit Logging

```php
// Log all state transitions
activity()
    ->causedBy($user)
    ->performedOn($paymentRequest)
    ->withProperties([
        'old_status' => $oldStatus,
        'new_status' => $newStatus,
        'ip_address' => request()->ip(),
    ])
    ->log('payment_request_status_changed');
```

---

## Performance Optimization

### Database Indexes (Applied)

All critical queries covered:
- `payment_requests(group_id, cycle_id, status)`
- `payment_requests(member_id, cycle_id)`
- `payment_requests(pr_code)`
- `group_members(user_id, status)`
- `billing_cycles(group_id, status)`

### Query Optimization

```php
// Eager load to prevent N+1
$cycle = BillingCycle::with([
    'paymentRequests.member.user',
    'group.owner'
])->findOrFail($id);

// Use chunk for large datasets
PaymentRequest::where('status', 'sent')
    ->chunk(100, function ($requests) {
        // Process batch
    });
```

### Caching Strategy

```php
// Cache group summary for 5 minutes
$summary = Cache::remember("group.{$groupId}.summary", 300, function () use ($groupId) {
    return Group::with(['members', 'currentCycle'])
        ->findOrFail($groupId)
        ->toArray();
});
```

### Queue Optimization

```php
// Use different queue priorities
SendInitialRemindersJob::dispatch()->onQueue('high');
SendRetryRemindersJob::dispatch()->onQueue('default');
CloseCyclesJob::dispatch()->onQueue('low');
```

---

## Monitoring & Observability

### Key Metrics to Track

1. **Payment Request Success Rate** — % of `sent` requests that reach `owner_confirmed`
2. **Time to Confirmation** — Avg hours between `sent_at` and `confirmed_at`
3. **Dunning Effectiveness** — Conversion rate after each retry
4. **Push Notification Delivery** — FCM success rate
5. **Job Failure Rate** — Failed jobs per day

### Laravel Horizon Dashboard

Monitor queues in real-time:
- Job throughput
- Failed jobs
- Wait times
- Longest running jobs

### Sentry Integration

```php
// Log errors to Sentry
if (app()->bound('sentry')) {
    app('sentry')->captureException($exception, [
        'tags' => [
            'job' => class_basename($this),
            'pr_code' => $pr->pr_code ?? null,
        ],
    ]);
}
```

---

## Future Enhancements (Post-MVP)

### Phase 1: Auto-Verification (Q2 2026)

- **Plaid Read-Only Integration** — Detect payments via transaction matching
- **Stripe Identity** — Optional member verification
- **Auto-confirm** when payment detected

### Phase 2: Premium Autopay (Q3 2026)

- **Card on File** — Store payment methods via Stripe
- **Auto-charge** on due date
- **Virtual Card** for owner to pay carrier
- **Braintree/Adyen** integration

### Phase 3: Advanced Features (Q4 2026)

- **Multi-currency** support
- **Split across carriers** (T-Mobile + Verizon in one group)
- **Group admin roles** (delegate owner responsibilities)
- **Custom dunning schedules**
- **SMS fallback** for members without app

---

## Resources

### Documentation Files

- [CLAUDE.md](./CLAUDE.md) — Project overview and conventions
- [P2P_DEEP_LINKS_GUIDE.md](./P2P_DEEP_LINKS_GUIDE.md) — Current deep link implementation
- [PAYMENT_ACCOUNTS_FEATURE.md](./PAYMENT_ACCOUNTS_FEATURE.md) — User payment settings
- [CASHAPP_INTEGRATION.md](./CASHAPP_INTEGRATION.md) — API integration reference (future)

### Code Locations

- **Models:** `app/Models/`
- **Jobs:** `app/Jobs/`
- **Drivers:** `app/Drivers/`
- **Policies:** `app/Policies/`
- **API Controllers:** `app/Http/Controllers/Api/`
- **Livewire Components:** `resources/views/livewire/`
- **Tests:** `tests/Feature/`, `tests/Unit/`

### External APIs

- **Firebase Cloud Messaging (FCM):** [https://firebase.google.com/docs/cloud-messaging](https://firebase.google.com/docs/cloud-messaging)
- **Cash App API Docs:** [https://developers.cash.app](https://developers.cash.app)
- **Venmo API Docs:** [https://developer.venmo.com](https://developer.venmo.com)

---

## Success Criteria

### MVP Launch Checklist

- [ ] All database migrations run successfully
- [ ] PR Code generation working and unique
- [ ] Deep links open Cash App/Venmo correctly
- [ ] Push notifications deliver to members
- [ ] Members can mark payments as paid
- [ ] Owners can confirm payments (single + bulk)
- [ ] Scheduled jobs run on schedule without errors
- [ ] 85%+ test coverage on critical paths
- [ ] Mobile app can complete full payment flow
- [ ] Owner dashboard shows real-time status
- [ ] Performance under 200ms for API endpoints
- [ ] Zero data loss in production

### Post-Launch KPIs (30 days)

- **Activation:** 80%+ of members receive and open first push notification
- **Conversion:** 70%+ of payment requests reach `member_marked` within 3 days
- **Owner Satisfaction:** 90%+ of owners confirm payments within 24 hours
- **Technical:** 99.5% uptime, <1% job failure rate

---

**Ready for implementation! 🚀**

Questions or clarifications? Contact the engineering team.

---

**Document Version:** 2.0
**Last Updated:** October 25, 2025
**Maintained By:** Engineering Team
