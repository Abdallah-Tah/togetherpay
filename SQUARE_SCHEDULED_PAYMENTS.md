# Square + Cash App Pay Scheduled Payments Architecture

## Overview

Use **Laravel Scheduler** + **Square Invoices** + **Cash App Pay** to achieve recurring billing WITHOUT needing Cash App Partner API approval.

### Key Insight
- Square supports **one-time** Cash App Pay payments ✅
- Laravel Scheduler triggers payment requests **monthly** ✅
- Result: **Scheduled recurring billing** with Cash App Pay ✅

---

## How It Works

### Setup Phase (One-time per group)
1. Owner creates group with `billing_mode = 'auto_square'`
2. Owner connects Square merchant account (OAuth)
3. Members join group with Cash App as preferred method
4. Square customers created for each member

### Monthly Billing Cycle (Automated via Laravel Scheduler)

**Daily at 9:00 AM** - Laravel runs scheduled job:

```php
// app/Console/Kernel.php
protected function schedule(Schedule $schedule)
{
    // Check for billing cycles that need invoices generated
    $schedule->call(function () {
        $cycles = BillingCycle::where('due_at', '<=', now()->addDays(2))
            ->where('status', 'active')
            ->whereDoesntHave('paymentRequests')
            ->get();

        foreach ($cycles as $cycle) {
            GenerateSquareInvoicesJob::dispatch($cycle);
        }
    })->daily()->at('09:00');
}
```

**Job Flow:**

1. **GenerateSquareInvoicesJob**
   - For each active member in the group
   - Create Square invoice with Cash App Pay enabled
   - Amount = member's calculated share
   - Due date = cycle due date
   - Store `square_invoice_id` in payment_requests table

2. **Square Invoice Creation**
   ```php
   // Create invoice via Square API
   $invoice = new Invoice();
   $invoice->setCustomerId($member->square_customer_id);
   $invoice->setAcceptedPaymentMethods([
       'card' => true,
       'cash_app_pay' => true  // Enable Cash App Pay
   ]);

   // Publish invoice
   $invoicesApi->publishInvoice($invoiceId);
   ```

3. **Square sends email/notification to member**
   - Member receives email with payment link
   - Link opens Square-hosted payment page
   - Member can pay with Cash App Pay (one-time)

4. **Member pays**
   - Clicks "Pay with Cash App"
   - Cash App opens
   - Completes one-time payment
   - Money goes to owner's Square account

5. **Square Webhook confirms**
   - `invoice.payment_made` event received
   - Update payment_request status to `auto_verified`
   - Update cycle collected amount

---

## Database Schema

```sql
-- Groups table (already has billing_mode)
billing_mode ENUM('manual', 'auto_square') DEFAULT 'manual'

-- Group members (already has Square fields)
square_customer_id VARCHAR(255)

-- Payment requests (already has Square fields)
square_invoice_id VARCHAR(255)
square_payment_id VARCHAR(255)
square_confirmed_at TIMESTAMP

-- Users (already has Square OAuth fields)
square_merchant_id VARCHAR(255)
square_access_token TEXT
square_location_id VARCHAR(255)
square_connected_at TIMESTAMP
```

---

## Implementation Plan

### Phase 1: Square Invoice Generation ✅ (Already Started)

Files created:
- `app/Services/Square/SquareClientBuilder.php` ✅
- `app/Services/Square/SquareCustomerService.php` ✅
- `app/Services/Square/SquareWebhookService.php` ✅
- `app/Http/Controllers/Square/SquareOAuthController.php` ✅
- `app/Http/Controllers/Square/SquareWebhookController.php` ✅

### Phase 2: Laravel Scheduled Jobs (NEW)

**Jobs to create:**

1. **`GenerateSquareInvoicesJob.php`**
   ```php
   class GenerateSquareInvoicesJob implements ShouldQueue
   {
       public function __construct(
           private BillingCycle $cycle
       ) {}

       public function handle()
       {
           $group = $this->cycle->group;

           if (!$group->isAutoSquareMode()) return;
           if (!$group->ownerHasSquare()) return;

           $members = $group->activeMembers;

           foreach ($members as $member) {
               // Create Square invoice
               $invoiceId = $this->createSquareInvoice($member);

               // Create payment request record
               PaymentRequest::create([
                   'group_id' => $group->id,
                   'cycle_id' => $this->cycle->id,
                   'member_id' => $member->id,
                   'amount_cents' => $member->calculateAmount($group->total_amount_cents),
                   'method_hint' => $member->preferred_channel,
                   'pr_code' => 'PR-' . strtoupper(Str::random(8)),
                   'note' => "{$group->name} - {$this->cycle->period_start->format('M Y')}",
                   'status' => 'sent',
                   'square_invoice_id' => $invoiceId,
                   'initiated_at' => now(),
                   'sent_at' => now(),
               ]);
           }
       }
   }
   ```

2. **`app/Console/Kernel.php`** - Schedule configuration
   ```php
   protected function schedule(Schedule $schedule)
   {
       // Generate invoices 2 days before due date
       $schedule->call(function () {
           BillingCycle::whereBetween('due_at', [
               now()->addDays(2)->startOfDay(),
               now()->addDays(2)->endOfDay(),
           ])
           ->where('status', 'active')
           ->whereDoesntHave('paymentRequests')
           ->each(fn($cycle) => GenerateSquareInvoicesJob::dispatch($cycle));
       })->daily()->at('09:00');

       // Send reminders for pending invoices
       $schedule->call(function () {
           // Find payment requests that are still pending
           PaymentRequest::where('status', 'sent')
               ->where('sent_at', '<', now()->subDays(3))
               ->each(fn($pr) => SendPaymentReminderJob::dispatch($pr));
       })->daily()->at('10:00');
   }
   ```

### Phase 3: Square Invoice Service (NEW)

**`app/Services/Square/SquareInvoiceService.php`**
```php
class SquareInvoiceService
{
    public function createInvoiceForMember(
        GroupMember $member,
        BillingCycle $cycle
    ): string {
        $group = $cycle->group;
        $owner = $group->owner;
        $client = SquareClientBuilder::buildForUser($owner);

        // Calculate member's share
        $amountCents = $member->calculateAmount($group->total_amount_cents);

        // Create invoice
        $invoice = new Invoice();
        $invoice->setLocationId($owner->square_location_id);

        // Set customer
        $recipient = new InvoiceRecipient();
        $recipient->setCustomerId($member->square_customer_id);
        $invoice->setPrimaryRecipient($recipient);

        // Create payment request
        $paymentRequest = new InvoicePaymentRequest();
        $paymentRequest->setRequestType('BALANCE');
        $paymentRequest->setDueDate($cycle->due_at->format('Y-m-d'));

        // Set amount
        $money = new Money();
        $money->setAmount($amountCents);
        $money->setCurrency('USD');
        $paymentRequest->setComputedAmountMoney($money);

        $invoice->setPaymentRequests([$paymentRequest]);

        // Enable Cash App Pay
        $acceptedMethods = new InvoiceAcceptedPaymentMethods();
        $acceptedMethods->setCard(true);
        $acceptedMethods->setCashAppPay(true);
        $invoice->setAcceptedPaymentMethods($acceptedMethods);

        // Set details
        $invoice->setTitle($group->name);
        $invoice->setDescription("Payment for {$cycle->period_start->format('M Y')}");

        // Create invoice
        $request = new CreateInvoiceRequest($invoice);
        $request->setIdempotencyKey('invoice_' . time() . '_' . $member->id);

        $response = $client->invoices->createInvoice($request);
        $createdInvoice = $response->getInvoice();

        // Publish invoice (sends to customer)
        $publishRequest = new PublishInvoiceRequest($createdInvoice->getVersion());
        $publishRequest->setIdempotencyKey('publish_' . time() . '_' . $member->id);

        $client->invoices->publishInvoice(
            $createdInvoice->getId(),
            $publishRequest
        );

        return $createdInvoice->getId();
    }

    public function cancelInvoice(string $invoiceId, User $owner): void
    {
        $client = SquareClientBuilder::buildForUser($owner);

        $request = new CancelInvoiceRequest($version);
        $client->invoices->cancelInvoice($invoiceId, $request);
    }
}
```

### Phase 4: Webhook Handling (ENHANCE EXISTING)

**Update `SquareWebhookService.php`:**
```php
public function handleInvoicePaymentMade(array $data): void
{
    $invoice = $data['data']['object']['invoice'] ?? null;
    if (!$invoice) return;

    $invoiceId = $invoice['id'];
    $paymentId = $invoice['payment_requests'][0]['payment_ids'][0] ?? null;

    // Find payment request by invoice ID
    $paymentRequest = PaymentRequest::where('square_invoice_id', $invoiceId)->first();

    if (!$paymentRequest) {
        Log::warning('Payment request not found for Square invoice', [
            'invoice_id' => $invoiceId
        ]);
        return;
    }

    // Update payment request as verified
    $paymentRequest->update([
        'status' => 'auto_verified',
        'square_payment_id' => $paymentId,
        'square_confirmed_at' => now(),
    ]);

    // Update cycle collected amount
    $paymentRequest->cycle->increment(
        'total_collected_cents',
        $paymentRequest->amount_cents
    );

    Log::info('Square invoice payment confirmed', [
        'payment_request_id' => $paymentRequest->id,
        'invoice_id' => $invoiceId,
        'payment_id' => $paymentId,
    ]);
}
```

---

## User Flow Comparison

### Manual Mode (Existing)
1. Owner manually initiates cycle
2. App generates deep links
3. Member receives notification
4. Member clicks → Cash App opens
5. Member completes payment
6. **Member marks as paid** ← Manual step
7. **Owner confirms** ← Manual step

### Auto Square Mode (New)
1. **Laravel scheduler auto-triggers** ← Automatic
2. Square invoice generated
3. Member receives Square email/notification
4. Member clicks → Square payment page opens
5. Member selects "Cash App Pay"
6. Cash App opens → completes payment
7. **Square webhook auto-confirms** ← Automatic
8. Status updated to `auto_verified` ← Automatic

---

## Configuration

**`.env`**
```bash
# Square credentials
SQUARE_ENVIRONMENT=sandbox
SQUARE_APPLICATION_ID=your_app_id
SQUARE_ACCESS_TOKEN=your_access_token
SQUARE_LOCATION_ID=your_location_id

# OAuth
SQUARE_OAUTH_REDIRECT_URI="${APP_URL}/square/callback"

# Webhook
SQUARE_WEBHOOK_SIGNATURE_KEY=your_webhook_key
SQUARE_WEBHOOK_ENDPOINT="${APP_URL}/webhooks/square"
```

**`config/payment_requests.php`** (already exists)
```php
'square' => [
    'enabled' => env('SQUARE_ENABLED', true),
    'mode' => env('SQUARE_MODE', 'sandbox'),
    // ... rest of config
],
```

---

## Testing Plan

### Local Testing
```bash
# Generate test invoice manually
php artisan tinker
> $cycle = BillingCycle::find(1);
> GenerateSquareInvoicesJob::dispatch($cycle);

# Check scheduled jobs
php artisan schedule:list

# Run scheduler manually
php artisan schedule:run

# Test webhook locally (use ngrok)
php artisan serve
ngrok http 8000
# Configure Square webhook to ngrok URL
```

### Production Checklist
- [ ] Square production credentials configured
- [ ] OAuth flow working
- [ ] Webhook endpoint registered with Square
- [ ] Webhook signature verification working
- [ ] Laravel scheduler running (cron configured)
- [ ] Email notifications enabled
- [ ] Error handling and logging
- [ ] Member onboarding flow complete

---

## Advantages of This Approach

1. **No Partner Approval**: Uses standard Square API
2. **Cash App Pay Support**: One-time payments work
3. **Automatic Scheduling**: Laravel handles timing
4. **Scalable**: Queued jobs handle load
5. **Webhook Confirmation**: Auto-verified payments
6. **Owner Control**: Manual trigger option available
7. **Flexible**: Works with cards too

---

## Owner Manual Trigger Option

Owners can also manually trigger payment requests:

```php
// In controller
public function triggerPaymentRequest(BillingCycle $cycle)
{
    if ($cycle->group->owner_id !== auth()->id()) {
        abort(403);
    }

    GenerateSquareInvoicesJob::dispatch($cycle);

    return back()->with('success', 'Payment requests sent to all members');
}
```

---

## Next Steps

1. ✅ Complete Square services (mostly done)
2. ⏳ Create `GenerateSquareInvoicesJob`
3. ⏳ Create `SquareInvoiceService`
4. ⏳ Configure Laravel scheduler
5. ⏳ Test invoice generation
6. ⏳ Test webhook handling
7. ⏳ Add UI for manual triggers
8. ⏳ Production deployment

---

## Conclusion

This architecture gives you **the best of both worlds**:
- ✅ Automatic scheduled billing (like Claude.ai)
- ✅ Cash App Pay support (fee-free)
- ✅ No Cash App Partner approval needed
- ✅ Uses existing Square integration
- ✅ Can launch immediately

The key insight: **Laravel schedules the one-time payments**, creating the illusion of recurring billing while using Square's one-time Cash App Pay capability.
