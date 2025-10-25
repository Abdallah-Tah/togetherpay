# How to Get Cash App API Credentials (Client ID & Secret)

## Step-by-Step Guide to Register for Cash App Business API

---

## Prerequisites

Before you start, you'll need:
- ✅ A business (LLC, Corporation, or Sole Proprietorship)
- ✅ Business Tax ID (EIN) or SSN for sole proprietors
- ✅ Business bank account
- ✅ Valid government-issued ID
- ✅ Business website or app (your TogetherPay app)

---

## Step 1: Create Cash App Business Account

### 1.1 Download Cash App
- **iOS**: https://apps.apple.com/us/app/cash-app/id711923939
- **Android**: https://play.google.com/store/apps/details?id=com.squareup.cash

### 1.2 Sign Up for Business Account
1. Open Cash App
2. Tap your profile icon (top-right)
3. Select "Business" or "Switch to Business"
4. Follow prompts to:
   - Enter business name: "TogetherPay" or your business name
   - Provide business details
   - Verify your identity (ID + selfie)
   - Add business bank account

---

## Step 2: Register as Cash App Developer

### 2.1 Visit Cash App Developer Portal
🔗 **URL**: https://developers.cash.app

### 2.2 Create Developer Account
1. Click **"Get Started"** or **"Sign Up"**
2. Sign in with your Cash App Business account
3. Fill out developer registration form:
   - Company name
   - Business type
   - Tax ID (EIN)
   - Business address
   - Contact information
   - Technical contact email

### 2.3 Verify Your Business
- Upload required documents:
  - Business license
  - Articles of Incorporation (for LLCs/Corps)
  - Bank verification letter
  - Government-issued ID

---

## Step 3: Apply for API Access

### 3.1 Complete Application Form

The application will ask about:

**Business Information**
- What does your business do?
  > "TogetherPay is a bill-splitting platform that helps groups manage recurring payments (subscriptions, phone plans, etc.) by coordinating P2P payments between members."

**API Use Case**
- How will you use Cash App API?
  > "We use Cash App's Customer Request API to send payment requests from group owners to members for their share of recurring bills (phone plans, streaming services, etc.). Members pay directly through Cash App."

**Expected Volume**
- Number of transactions per month: Start with 100-1000
- Average transaction amount: $10-50
- Peak transaction volume: Estimate based on your user base

**Technical Details**
- Integration type: **Customer Request API**
- Webhook URL: `https://yourdomain.com/api/webhooks/cashapp`
- Redirect URL: `https://yourdomain.com/payment/callback`

**Compliance Questions**
- Are you handling money transmission? **No** (P2P only)
- Do you store payment card data? **No**
- Are you PCI DSS compliant? **N/A** (not handling cards)

### 3.2 Submit Application
- Review all information
- Accept Terms of Service
- Submit for review

### 3.3 Wait for Approval
- **Timeline**: 3-10 business days
- **Status**: Check email for updates
- **Follow-up**: You may be contacted for additional info

---

## Step 4: Get API Credentials (After Approval)

### 4.1 Access Developer Dashboard

Once approved:
1. Log in to https://developers.cash.app
2. Go to **Dashboard** or **API Credentials**

### 4.2 Create New Application

1. Click **"Create Application"** or **"New App"**
2. Fill in details:
   - **App Name**: "TogetherPay"
   - **Description**: "Bill-splitting payment coordination platform"
   - **Environment**: Start with **Sandbox** (testing)
   - **Redirect URIs**: 
     - `https://yourdomain.com/payment/callback`
     - `http://localhost:8000/payment/callback` (for testing)
   - **Webhook URL**: `https://yourdomain.com/api/webhooks/cashapp`

3. Click **"Create"** or **"Generate Credentials"**

### 4.3 Get Your Credentials

You'll receive:

```
CLIENT_ID: ca_live_xxxxxxxxxxxxxxxxxxxx
CLIENT_SECRET: sk_live_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
```

**⚠️ IMPORTANT**: 
- Copy these immediately - **CLIENT_SECRET is shown only once!**
- Store securely (password manager, environment variables)
- Never commit to Git/GitHub

### 4.4 Get Webhook Secret

1. Go to **Webhooks** section in dashboard
2. Configure webhook endpoint: `https://yourdomain.com/api/webhooks/cashapp`
3. Select events:
   - ✅ `PAYMENT_COMPLETED`
   - ✅ `PAYMENT_FAILED`
   - ✅ `REQUEST_EXPIRED`
4. Click **"Generate Secret"** or **"Create Webhook"**
5. Copy the **Webhook Signing Secret**

```
WEBHOOK_SECRET: whsec_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
```

---

## Step 5: Configure Your Application

### 5.1 Add to `.env` File

Open `/Users/amohamed/Herd/togetherpay/.env` and add:

```env
# Cash App API - Production
CASHAPP_CLIENT_ID=ca_live_xxxxxxxxxxxxxxxxxxxx
CASHAPP_CLIENT_SECRET=sk_live_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
CASHAPP_API_URL=https://api.cash.app
CASHAPP_WEBHOOK_SECRET=whsec_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx

# Cash App API - Sandbox (for testing)
CASHAPP_SANDBOX_CLIENT_ID=ca_sandbox_xxxxxxxxxxxxxxxxxxxx
CASHAPP_SANDBOX_CLIENT_SECRET=sk_sandbox_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
CASHAPP_SANDBOX_API_URL=https://sandbox.api.cash.app
CASHAPP_SANDBOX_WEBHOOK_SECRET=whsec_sandbox_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
```

### 5.2 Update `config/services.php`

Already configured! Just verify:

```php
'cashapp' => [
    'client_id' => env('CASHAPP_CLIENT_ID'),
    'client_secret' => env('CASHAPP_CLIENT_SECRET'),
    'api_url' => env('CASHAPP_API_URL', 'https://api.cash.app'),
    'webhook_secret' => env('CASHAPP_WEBHOOK_SECRET'),
],
```

---

## Step 6: Test in Sandbox Mode

### 6.1 Use Sandbox Credentials First

Before going live, test with sandbox:

```env
CASHAPP_CLIENT_ID=${CASHAPP_SANDBOX_CLIENT_ID}
CASHAPP_CLIENT_SECRET=${CASHAPP_SANDBOX_CLIENT_SECRET}
CASHAPP_API_URL=${CASHAPP_SANDBOX_API_URL}
```

### 6.2 Get Test Customer IDs

In sandbox, Cash App provides test customer IDs:
- `MMI_sandbox_customer_1`
- `MMI_sandbox_customer_2`

Update your test script:
```php
'cashapp_customer_id' => 'MMI_sandbox_customer_1', // Use sandbox test ID
```

### 6.3 Run Test Script

```bash
php test-cashapp.php
```

Expected output:
```
✅ SUCCESS! Cash App request created!
Request ID: GRR_sandbox_xxxxx
Status: PENDING
Mobile URL: https://sandbox.cash.app/pay/...
```

### 6.4 Test the Flow

1. Use sandbox mobile URL to simulate payment
2. Check webhook received (in logs)
3. Verify payment status updates to PAID

---

## Step 7: Go Live (Production)

### 7.1 Request Production Access

Once testing is complete:
1. Go to Developer Dashboard
2. Click **"Request Production Access"**
3. Provide:
   - Test results
   - Security audit results
   - Privacy policy URL
   - Terms of service URL

### 7.2 Switch to Production Credentials

Update `.env`:
```env
CASHAPP_CLIENT_ID=${CASHAPP_LIVE_CLIENT_ID}
CASHAPP_CLIENT_SECRET=${CASHAPP_LIVE_CLIENT_SECRET}
CASHAPP_API_URL=https://api.cash.app
```

### 7.3 Implement OAuth Flow

For production, implement OAuth to get real customer IDs:

**OAuth Flow**:
1. User clicks "Connect Cash App"
2. Redirect to Cash App OAuth:
   ```
   https://cash.app/oauth/authorize?
     client_id=YOUR_CLIENT_ID&
     scope=customer_profile,payments&
     redirect_uri=https://yourdomain.com/auth/cashapp/callback
   ```
3. User authorizes
4. Cash App redirects back with authorization code
5. Exchange code for access token:
   ```php
   POST https://api.cash.app/oauth/token
   {
     "client_id": "YOUR_CLIENT_ID",
     "client_secret": "YOUR_CLIENT_SECRET",
     "code": "AUTH_CODE",
     "grant_type": "authorization_code"
   }
   ```
6. Get customer_id from response
7. Store in `users.cashapp_customer_id`

---

## Alternative: Venmo API (if Cash App is unavailable)

If Cash App API access is difficult, consider **Venmo**:

### Venmo Developer Portal
🔗 **URL**: https://developer.venmo.com

**Similar process**:
1. Create PayPal Business Account
2. Link to Venmo
3. Apply for Venmo API access
4. Get Client ID & Secret
5. Implement OAuth flow

---

## Alternative: Manual Process (No API)

If API access is delayed, you can still launch with **manual process**:

### How It Works:
1. **System generates payment request** (email/SMS)
2. **Email includes**:
   - Amount due
   - Owner's Cash App tag: `$AbdallahMohamed`
   - Deep link: `https://cash.app/$AbdallahMohamed?amount=5.00&note=T-Mobile-Oct`
3. **Member clicks link** → Opens Cash App
4. **Member confirms payment**
5. **Member clicks "Mark as Paid"** in your app
6. **Owner verifies** payment received

**Pros**: No API needed, works immediately
**Cons**: Manual confirmation required

---

## Troubleshooting

### Issue: Application Rejected

**Reasons**:
- Incomplete business information
- Unclear use case
- High-risk business model

**Solution**:
- Clarify you're NOT processing payments (P2P only)
- Emphasize member-to-owner direct payments
- Show compliance with KYC/AML

### Issue: Can't Get Sandbox Access

**Solution**:
- Some APIs require production approval first
- Use manual process while waiting
- Contact Cash App support

### Issue: Webhook Not Receiving Events

**Checklist**:
- ✅ Webhook URL is publicly accessible (not localhost)
- ✅ Endpoint returns 200 OK
- ✅ SSL certificate is valid
- ✅ Signature verification is correct

**Test webhook**:
```bash
curl -X POST https://yourdomain.com/api/webhooks/cashapp \
  -H "Content-Type: application/json" \
  -d '{"event_type":"PAYMENT_COMPLETED","data":{"request":{"id":"GRR_test"}}}'
```

---

## Security Best Practices

### 1. Protect Credentials
```bash
# Never commit to Git
echo ".env" >> .gitignore

# Use environment variables
# Rotate secrets regularly
```

### 2. Verify Webhook Signatures
```php
// In CashAppWebhookController
$signature = $request->header('X-Cash-Signature');
$payload = $request->getContent();
$expected = hash_hmac('sha256', $payload, config('services.cashapp.webhook_secret'));

if (!hash_equals($signature, $expected)) {
    return response()->json(['error' => 'Invalid signature'], 401);
}
```

### 3. Use HTTPS Only
```php
// In AppServiceProvider
if (app()->environment('production')) {
    URL::forceScheme('https');
}
```

---

## Timeline Summary

| Step | Timeline | Status |
|------|----------|--------|
| Cash App Business signup | 1-2 days | ⏳ Waiting |
| Developer account creation | Immediate | ✅ |
| API application submission | 1 hour | ✅ |
| Approval wait | 3-10 days | ⏳ Waiting |
| Sandbox testing | 1-3 days | ⏳ After approval |
| Production request | 2-5 days | ⏳ After testing |
| **Total** | **~2-3 weeks** | |

---

## Next Steps

### Today
1. ✅ Create Cash App Business account
2. ✅ Register at developers.cash.app
3. ✅ Submit API application

### While Waiting for Approval
1. ✅ Test with manual process (deep links)
2. ✅ Set up webhook endpoint
3. ✅ Implement OAuth flow
4. ✅ Prepare documentation

### After Approval
1. ✅ Get credentials
2. ✅ Add to `.env`
3. ✅ Test in sandbox
4. ✅ Go live! 🚀

---

## Support & Resources

- **Cash App Developers**: https://developers.cash.app
- **API Docs**: https://developers.cash.app/docs/api
- **Support Email**: developers@cash.app
- **Community**: https://community.square.dev

---

## Quick Start (For Testing Now)

While waiting for API credentials, you can test the **manual flow**:

```php
// Generate deep link
$cashTag = '$AbdallahMohamed';
$amount = 5.00;
$note = 'T-Mobile Family Plan - Oct 2025';

$deepLink = "https://cash.app/{$cashTag}?" . http_build_query([
    'amount' => $amount,
    'note' => $note,
]);

// Send in email/SMS
echo "Pay here: {$deepLink}";
```

**Member experience**:
1. Clicks link → Opens Cash App
2. Amount pre-filled: $5.00
3. Note pre-filled: "T-Mobile Family Plan - Oct 2025"
4. Taps "Pay" → Done! ✅

This works **TODAY** - no API needed! 🎉
