# Cash App OAuth Testing Guide

## Overview

This guide provides step-by-step instructions for testing the Cash App OAuth member onboarding flow that was implemented to satisfy the CASHAPP_INTEGRATION.md requirements.

---

## Prerequisites

1. **Cash App Business Account**
   - Registered at https://developers.cash.app
   - API credentials configured in `.env`

2. **Backend Running**
   ```bash
   cd /Users/amohamed/Herd/togetherpay
   composer dev
   ```

3. **Public URL** (for OAuth callback)
   - Use ngrok: `ngrok http https://togetherpay.test`
   - Or use production URL

4. **User Account with Group Membership**
   - User must be registered
   - User must be a member of at least one group

---

## OAuth Flow Methods

### Method 1: Full OAuth Flow (Recommended for Production)

**Step 1: Initiate OAuth**

```bash
curl -X GET "https://api.togetherpay.app/api/v1/cashapp/oauth/authorize?member_id=1" \
  -H "Authorization: Bearer YOUR_AUTH_TOKEN" \
  -H "Accept: application/json"
```

**Response:**
```json
{
  "authorization_url": "https://cash.app/oauth/authorize?client_id=...&scope=customer_profile+payments&redirect_uri=...&state=...&response_type=code",
  "state": "abc123..."
}
```

**Step 2: Redirect User to Cash App**

Open the `authorization_url` in a browser or mobile app. User will:
1. Log into Cash App
2. Review permissions request
3. Approve the connection

**Step 3: Handle Callback**

Cash App will redirect to:
```
https://api.togetherpay.app/api/v1/cashapp/oauth/callback?code=AUTH_CODE&state=abc123
```

The backend automatically:
1. Verifies the state token
2. Exchanges code for access token
3. Extracts customer_id from response
4. Updates group_members table
5. Redirects to `togetherpay://cashapp/success?member_id=1`

**Step 4: Verify Connection**

```bash
curl -X GET "https://api.togetherpay.app/api/v1/cashapp/status?member_id=1" \
  -H "Authorization: Bearer YOUR_AUTH_TOKEN" \
  -H "Accept: application/json"
```

**Expected Response:**
```json
{
  "connected": true,
  "customer_id": "CUST_abc123...",
  "cashtag": null,
  "connected_at": "2025-10-31T14:30:00.000000Z"
}
```

---

### Method 2: $cashtag Resolution (Alternative)

This method resolves a user's $cashtag to their customer_id without full OAuth.

**Request:**
```bash
curl -X POST "https://api.togetherpay.app/api/v1/cashapp/resolve-cashtag" \
  -H "Authorization: Bearer YOUR_AUTH_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "member_id": 1,
    "cashtag": "$JohnDoe"
  }'
```

**Successful Response:**
```json
{
  "message": "Cash App account connected successfully",
  "customer_id": "CUST_def456...",
  "cashtag": "$JohnDoe"
}
```

**Error Response (Invalid $cashtag):**
```json
{
  "message": "Could not resolve Cash App customer ID from cashtag",
  "cashtag": "$InvalidTag"
}
```

**Notes:**
- Cashtag can include or omit the `$` prefix
- Requires valid Cash App account with matching cashtag
- Uses app-level OAuth token, not user OAuth

---

## Testing Payment Request Creation

Once a member has a `cash_app_customer_id`, test sending a payment request:

```bash
php artisan tinker

# Get a member with customer_id
$member = \App\Models\GroupMember::where('cash_app_customer_id', '!=', null)->first();

# Get a payment request for that member
$pr = $member->paymentRequests()->first();

# Dispatch Cash App payment request job
dispatch(new \App\Jobs\SendCashAppPaymentRequestJob($pr));
```

**Check Logs:**
```bash
tail -f storage/logs/laravel.log | grep -i cashapp
```

**Expected Log Entries:**
```
[INFO] Cash App payment request created
[INFO] request_id: GRR_abc123
[INFO] reference_id: PR_123
```

**Verify Database:**
```sql
SELECT
  id,
  status,
  metadata_json->>'$.cashapp_request_id' as cashapp_id,
  metadata_json->>'$.cashapp_mobile_url' as mobile_url
FROM payment_requests
WHERE id = 123;
```

---

## Testing Webhook Events

Simulate a Cash App webhook to test payment completion:

```bash
curl -X POST "https://api.togetherpay.app/api/webhooks/cashapp" \
  -H "Content-Type: application/json" \
  -H "X-CashApp-Signature: HMAC_SIGNATURE_HERE" \
  -d '{
    "event_type": "PAYMENT_COMPLETED",
    "data": {
      "request": {
        "id": "GRR_abc123",
        "reference_id": "PR_123"
      },
      "payment": {
        "id": "PMT_def456"
      }
    }
  }'
```

**Generate HMAC Signature (for testing):**
```php
php artisan tinker

$payload = json_encode([
    'event_type' => 'PAYMENT_COMPLETED',
    'data' => [
        'request' => ['id' => 'GRR_abc123', 'reference_id' => 'PR_123'],
        'payment' => ['id' => 'PMT_def456']
    ]
]);

$secret = config('services.cashapp.webhook_secret');
$signature = hash_hmac('sha256', $payload, $secret);
echo $signature;
```

**Expected Result:**
- Payment request status updated to `PAID`
- `paid_at` timestamp set
- Metadata includes `cashapp_payment_id`

---

## Testing Disconnection

```bash
curl -X DELETE "https://api.togetherpay.app/api/v1/cashapp/disconnect?member_id=1" \
  -H "Authorization: Bearer YOUR_AUTH_TOKEN" \
  -H "Accept: application/json"
```

**Response:**
```json
{
  "message": "Cash App account disconnected successfully"
}
```

**Verify:**
```sql
SELECT
  id,
  cash_app_customer_id,
  payment_provider_metadata->>'$.cashapp_disconnected_at' as disconnected_at
FROM group_members
WHERE id = 1;
```

---

## Mobile App Integration (Future)

### Recommended Mobile Flow

1. **User Profile Settings → Connect Cash App**

2. **Mobile app calls OAuth authorize endpoint:**
   ```dart
   final response = await dio.get(
     '/cashapp/oauth/authorize',
     queryParameters: {'member_id': memberId},
   );

   final authUrl = response.data['authorization_url'];
   ```

3. **Launch external browser or in-app browser:**
   ```dart
   await launchUrl(
     Uri.parse(authUrl),
     mode: LaunchMode.externalApplication,
   );
   ```

4. **Handle deep link callback:**
   ```dart
   // In app delegate or MainActivity
   // Handle: togetherpay://cashapp/success?member_id=1

   Future<void> _handleDeepLink(Uri uri) async {
     if (uri.path == '/cashapp/success') {
       final memberId = uri.queryParameters['member_id'];
       await _showSuccessDialog();
       await _refreshMemberStatus();
     }
   }
   ```

5. **Check connection status:**
   ```dart
   final response = await dio.get(
     '/cashapp/status',
     queryParameters: {'member_id': memberId},
   );

   final isConnected = response.data['connected'];
   ```

### Alternative: $cashtag Entry

```dart
// In a dialog or form
final cashtag = cashtagController.text;

try {
  final response = await dio.post(
    '/cashapp/resolve-cashtag',
    data: {
      'member_id': memberId,
      'cashtag': cashtag,
    },
  );

  ScaffoldMessenger.of(context).showSnackBar(
    SnackBar(content: Text('Cash App connected!')),
  );
} on DioException catch (e) {
  // Show error message
}
```

---

## Troubleshooting

### Issue: "Invalid or expired state token"

**Cause:** State token expired (>10 minutes) or already used

**Solution:** Generate a new authorization URL

---

### Issue: "Could not resolve customer ID from cashtag"

**Possible Causes:**
1. Invalid cashtag format
2. Cashtag doesn't exist in Cash App
3. Cash App API credentials invalid
4. API endpoint incorrect

**Debug:**
```bash
# Check logs
tail -f storage/logs/laravel.log | grep "Cash App customer ID resolution"

# Verify API credentials
php artisan tinker
config('services.cashapp.client_id')
config('services.cashapp.api_url')
```

---

### Issue: Webhook signature verification fails

**Cause:** Incorrect webhook secret or payload modification

**Solution:**
1. Verify webhook secret in `.env` matches Cash App dashboard
2. Check that payload isn't being modified by middleware
3. Test with generated HMAC signature (see above)

---

### Issue: Payment request job fails

**Check:**
1. Member has `cash_app_customer_id` populated
2. OAuth token is valid (not expired)
3. API credentials are correct
4. Amount is in cents (e.g., 2500 = $25.00)

**Debug:**
```bash
# Check queue worker logs
php artisan queue:listen --verbose

# Check failed jobs
php artisan queue:failed
```

---

## Production Checklist

Before deploying to production:

- [ ] Configure Production Cash App credentials in `.env`
- [ ] Register webhook URL in Cash App dashboard
- [ ] Test full OAuth flow in Cash App sandbox
- [ ] Verify webhook signature verification works
- [ ] Test $cashtag resolution with real accounts
- [ ] Configure proper redirect URIs in Cash App dashboard
- [ ] Set up queue monitoring (Laravel Horizon recommended)
- [ ] Enable proper error logging and alerting
- [ ] Test mobile deep link handling
- [ ] Verify HTTPS is enforced for all endpoints

---

## API Endpoints Summary

| Method | Endpoint | Auth | Purpose |
|--------|----------|------|---------|
| GET | `/api/v1/cashapp/oauth/authorize` | Required | Start OAuth flow |
| GET | `/api/v1/cashapp/oauth/callback` | Public | Handle OAuth redirect |
| POST | `/api/v1/cashapp/resolve-cashtag` | Required | Resolve $cashtag to ID |
| DELETE | `/api/v1/cashapp/disconnect` | Required | Disconnect account |
| GET | `/api/v1/cashapp/status` | Required | Check connection status |
| POST | `/api/webhooks/cashapp` | Public (signed) | Receive webhook events |

---

## Support

For issues with the Cash App API or OAuth flow:
- **Cash App API Docs:** https://developers.cash.app/docs
- **OAuth Documentation:** https://developers.cash.app/docs/oauth
- **Support:** support@cash.app

For issues with the TogetherPay implementation:
- Check `storage/logs/laravel.log` for detailed error messages
- Review `IMPLEMENTATION_SUMMARY.md` for architecture details
- Check `CASHAPP_INTEGRATION.md` for integration overview
