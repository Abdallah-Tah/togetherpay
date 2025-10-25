# Cash App Integration Setup Guide

## Overview
Your app now integrates with **Cash App's Customer Request API** to send payment requests directly through Cash App. Members receive requests in their Cash App and can pay with one tap.

---

## Flow Diagram

```
┌─────────────┐
│ Your System │ Creates payment request
└──────┬──────┘
       │
       ▼
┌─────────────────┐
│ Cash App API    │ POST /customer-request/v1/requests
└──────┬──────────┘
       │
       ▼
┌─────────────────┐
│ Cash App Sends  │ Push notification to member's phone
│ Notification    │
└──────┬──────────┘
       │
       ▼
┌─────────────────┐
│ Member Opens    │ Taps notification → Opens Cash App
│ Cash App        │
└──────┬──────────┘
       │
       ▼
┌─────────────────┐
│ Member Pays     │ One-tap payment to owner
└──────┬──────────┘
       │
       ▼
┌─────────────────┐
│ Cash App        │ POST /webhooks/cashapp
│ Webhook         │
└──────┬──────────┘
       │
       ▼
┌─────────────────┐
│ Your System     │ Marks payment as PAID
└─────────────────┘
```

---

## Setup Steps

### 1. Register for Cash App Business
1. Visit [https://developers.cash.app](https://developers.cash.app)
2. Create a business account
3. Apply for API access
4. Get your credentials:
   - Client ID
   - Client Secret
   - Webhook Secret

### 2. Configure Environment Variables

Add to your `.env`:

```env
# Cash App API
CASHAPP_CLIENT_ID=your_client_id_here
CASHAPP_CLIENT_SECRET=your_client_secret_here
CASHAPP_API_URL=https://api.cash.app
CASHAPP_WEBHOOK_SECRET=your_webhook_secret_here

# Venmo API (optional)
VENMO_CLIENT_ID=
VENMO_CLIENT_SECRET=
VENMO_API_URL=https://api.venmo.com
```

### 3. Database Migration for Cash App Customer IDs

Run this migration to add Cash App fields to `group_members` table:

```php
php artisan make:migration add_payment_provider_fields_to_group_members
```

```php
public function up()
{
    Schema::table('group_members', function (Blueprint $table) {
        $table->string('cash_app_customer_id')->nullable()->after('payment_method');
        $table->string('venmo_customer_id')->nullable()->after('cash_app_customer_id');
        $table->json('payment_provider_metadata')->nullable();
    });
}
```

Run migration:
```bash
php artisan migrate
```

### 4. Configure Webhook in Cash App Dashboard

1. Log in to Cash App Developer Portal
2. Go to Webhooks section
3. Add webhook URL: `https://yourdomain.com/api/webhooks/cashapp`
4. Select events:
   - `PAYMENT_COMPLETED`
   - `PAYMENT_FAILED`
   - `REQUEST_EXPIRED`
5. Save webhook secret to `.env`

### 5. Test the Integration

**Test Payment Request Creation:**
```bash
php artisan tinker

$member = \App\Models\GroupMember::first();
$member->cash_app_customer_id = 'test_customer_id'; // Use test ID from Cash App
$member->save();

$pr = \App\Models\PaymentRequest::first();
dispatch(new \App\Jobs\SendCashAppPaymentRequestJob($pr));
```

**Test Webhook:**
```bash
curl -X POST https://yourdomain.com/api/webhooks/cashapp \
  -H "Content-Type: application/json" \
  -d '{
    "event_type": "PAYMENT_COMPLETED",
    "data": {
      "request": {
        "id": "GRR_test123",
        "reference_id": "PR_1"
      },
      "payment": {
        "id": "PMT_test456"
      }
    }
  }'
```

---

## Implementation Details

### Files Created/Modified

1. **`app/Services/CashAppService.php`**
   - Handles OAuth token management
   - Creates payment requests via API
   - Retrieves payment status

2. **`app/Jobs/SendCashAppPaymentRequestJob.php`**
   - Queued job to create Cash App request
   - Stores request metadata
   - Handles errors gracefully

3. **`app/Http/Controllers/CashAppWebhookController.php`**
   - Receives webhook events from Cash App
   - Updates payment status automatically
   - Logs all events

4. **`config/services.php`**
   - Added Cash App and Venmo config

5. **`routes/api.php`**
   - Added webhook endpoint (no auth)

6. **`routes/web.php`**
   - Added payment callback route

7. **`app/Jobs/SendPaymentRequestsJob.php`**
   - Modified to dispatch Cash App job when applicable

---

## How It Works

### When Payment Request is Created:

1. **Job dispatched**: `SendPaymentRequestsJob`
2. **Email sent**: Fallback notification
3. **Cash App job dispatched**: `SendCashAppPaymentRequestJob` (if member has Cash App ID)
4. **API call made**: Creates request in Cash App
5. **Member notified**: Via Cash App push notification
6. **Request stored**: Metadata saved with URLs and QR codes

### Member Experience:

1. **Push notification**: "Payment request from [Owner Name]"
2. **Opens Cash App**: Tap notification → Opens request
3. **Reviews amount**: Sees $25.00, owner name, logo
4. **Confirms payment**: One tap to pay
5. **Done**: Money sent instantly

### Owner Experience:

1. **Money received**: Arrives in owner's Cash App balance
2. **Webhook received**: Your system auto-updates status to PAID
3. **Dashboard updated**: Owner sees payment marked complete

---

## Member Onboarding Flow

To get member's `cash_app_customer_id`:

1. **OAuth Flow**: Implement Cash App OAuth
   ```php
   // Redirect member to Cash App OAuth
   $authUrl = "https://cash.app/oauth/authorize?client_id={$clientId}&scope=customer_profile&redirect_uri={$redirectUri}";
   return redirect($authUrl);
   ```

2. **Callback**: Receive authorization code
   ```php
   // Exchange code for tokens
   $tokens = CashAppService::exchangeCodeForTokens($code);
   $customerId = $tokens['customer_id'];
   
   // Save to member
   $member->cash_app_customer_id = $customerId;
   $member->save();
   ```

3. **Alternative**: Manual entry
   - Member enters their Cash App tag (e.g., `$JohnDoe`)
   - Your app resolves it to customer ID via API

---

## Testing Checklist

- [ ] Cash App credentials configured in `.env`
- [ ] Database migration run successfully
- [ ] Member has `cash_app_customer_id` populated
- [ ] Payment request created successfully
- [ ] Cash App API responds with request ID
- [ ] Member receives notification in Cash App (sandbox mode)
- [ ] Webhook endpoint publicly accessible
- [ ] Webhook signature verification implemented
- [ ] Payment status updates correctly
- [ ] Owner sees payment marked as PAID

---

## Production Considerations

1. **OAuth Flow**: Implement proper Cash App OAuth for member onboarding
2. **Signature Verification**: Implement webhook signature verification
3. **Token Refresh**: Handle OAuth token expiration
4. **Rate Limiting**: Respect Cash App API rate limits
5. **Error Handling**: Retry failed requests with exponential backoff
6. **Monitoring**: Log all API calls and webhook events
7. **Compliance**: Review Cash App terms of service
8. **Sandbox Testing**: Use Cash App sandbox before production

---

## Cost Structure

**Cash App API Fees:**
- API usage: Free
- Payment processing: Standard Cash App fees apply (typically 2.75% for business)
- Owner receives payments directly (your app doesn't process)

**Your App Revenue:**
- Optional: Add service fee to member's share
- Optional: Charge subscription for premium features
- No payment processing liability

---

## Support & Documentation

- **Cash App API Docs**: https://developers.cash.app/docs
- **Customer Request API**: https://developers.cash.app/cash-app-pay-partner-api/api-reference/customer-request-api
- **Webhook Events**: https://developers.cash.app/docs/webhooks
- **Support**: support@cash.app

---

## Next Steps

1. Register for Cash App Business account
2. Get API credentials
3. Run database migration
4. Configure `.env`
5. Set up webhook endpoint
6. Test with sandbox
7. Implement OAuth flow for members
8. Go live! 🚀
