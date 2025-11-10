# Square Integration Guide

This guide explains how to set up and use Square payment integration with TogetherPay for automatic recurring payments.

## Overview

The Square integration enables **automatic billing mode** alongside the existing manual payment collection. When a group owner connects their Square account, they can create "auto-Square" groups where members are automatically charged each billing cycle via credit/debit card.

### Key Features

- **Dual Billing Modes**: Manual (existing P2P) and Auto-Square (new recurring payments) run side-by-side
- **OAuth Connection**: Secure Square merchant account connection
- **Automatic Subscriptions**: Members are enrolled in recurring subscriptions
- **Webhook Integration**: Real-time payment status updates
- **No Code Changes to Manual Flow**: Existing Cash App/Venmo logic remains untouched

## Architecture

### Billing Modes

Groups can be created in one of two modes:

1. **`manual`** (default): Traditional P2P payments via Cash App/Venmo
2. **`auto_square`**: Recurring card payments via Square subscriptions

The mode is set when creating a group and cannot be changed later.

### Database Schema

**`groups` table**:
- `billing_mode` ENUM('manual', 'auto_square') DEFAULT 'manual'

**`users` table** (for group owners):
- `square_merchant_id` VARCHAR - Square merchant identifier
- `square_access_token` TEXT - Encrypted OAuth access token
- `square_location_id` VARCHAR - Square business location
- `square_connected_at` TIMESTAMP - Connection timestamp

**`group_members` table**:
- `square_customer_id` VARCHAR - Square customer ID
- `square_subscription_id` VARCHAR - Square subscription ID

### Flow Diagram

```
Owner connects Square → OAuth flow → Store encrypted token
                                          ↓
Owner creates auto_square group → Members invited
                                          ↓
Member accepts invitation → Square customer created → Subscription created
                                          ↓
Square charges member monthly → Webhook received → PaymentRequest marked as paid
```

## Setup Instructions

### 1. Create Square Developer Account

1. Go to https://developer.squareup.com
2. Sign up for a developer account
3. Create a new application

### 2. Configure OAuth

In your Square application settings:

**OAuth Redirect URLs**:
- Development: `http://localhost/square/callback`
- Production: `https://yourdomain.com/square/callback`

**OAuth Scopes Required**:
- `MERCHANT_PROFILE_READ`
- `PAYMENTS_READ`
- `PAYMENTS_WRITE`
- `CUSTOMERS_READ`
- `CUSTOMERS_WRITE`
- `SUBSCRIPTIONS_READ`
- `SUBSCRIPTIONS_WRITE`
- `INVOICES_READ`
- `INVOICES_WRITE`

### 3. Environment Variables

Add to your `.env` file:

```env
# Square Payment Integration
SQUARE_ENVIRONMENT=sandbox              # or 'production'
SQUARE_APPLICATION_ID=your-app-id       # From Square dashboard
SQUARE_ACCESS_TOKEN=your-access-token   # From Square dashboard (for app-level operations)
SQUARE_LOCATION_ID=your-location-id     # Your default location ID
SQUARE_OAUTH_REDIRECT_URI=http://localhost/square/callback
SQUARE_WEBHOOK_SIGNATURE_KEY=your-webhook-key  # From Square webhook settings
```

### 4. Run Migrations

```bash
php artisan migrate
```

This creates:
- `billing_mode` column on groups
- Square connection fields on users
- Square tracking fields on group_members

### 5. Set Up Webhooks

In Square Dashboard → Webhooks:

**Webhook URL**: `https://yourdomain.com/square/webhook`

**Subscribe to Events**:
- `subscription.created`
- `subscription.updated`
- `subscription.canceled`
- `payment.created`
- `payment.updated`
- `invoice.published`
- `invoice.payment_made` (CRITICAL - this marks payments as paid)
- `invoice.payment_failed`

**Important**: Copy the Webhook Signature Key to `SQUARE_WEBHOOK_SIGNATURE_KEY` in your .env file.

## Usage

### For Group Owners

#### 1. Connect Square Account

1. Navigate to **Settings → Payment Accounts**
2. Click **"Connect Square Account"**
3. Authorize TogetherPay to access your Square account
4. You'll be redirected back with a success message

#### 2. Create Auto-Square Group

When creating a new group:

1. Select **"Auto-Square Billing"** as the billing mode
2. Members will be enrolled in automatic recurring payments
3. You must have a Square account connected to create auto-Square groups

#### 3. Disconnect Square (Optional)

If you need to disconnect:

1. Navigate to **Settings → Payment Accounts**
2. Click **"Disconnect"** button
3. Note: You cannot disconnect if you have active auto-Square groups

### For Group Members

#### Joining Auto-Square Groups

1. Accept invitation via email/link
2. Complete onboarding (name, email, payment info)
3. Square customer profile is automatically created
4. Recurring subscription is set up for your share amount
5. You'll be charged automatically each billing cycle

#### Payment Flow

1. Square sends invoice based on subscription schedule
2. Payment is automatically processed from saved card
3. Webhook notifies TogetherPay of successful payment
4. Your payment request is marked as **"paid"**
5. Group owner sees payment in their dashboard

## API Reference

### SquareService Methods

#### OAuth Methods

```php
// Generate OAuth authorization URL
$squareService = new SquareService();
$authUrl = $squareService->getAuthorizationUrl($state);

// Exchange authorization code for access token
$tokenData = $squareService->obtainAccessToken($code);
// Returns: ['access_token' => '...', 'merchant_id' => '...', 'location_id' => '...']
```

#### Customer Management

```php
// Create Square customer for a group member
$squareService = SquareService::forUser($owner);
$customerId = $squareService->createCustomer($member);
```

#### Subscription Management

```php
// Create recurring subscription
$subscriptionId = $squareService->createSubscription(
    customerId: $customerId,
    amountCents: 2500,  // $25.00
    billingCycle: 'MONTHLY',
    groupName: 'Netflix Group',
    memberName: 'John Doe'
);

// Cancel subscription
$success = $squareService->cancelSubscription($subscriptionId);

// Pause subscription
$success = $squareService->pauseSubscription($subscriptionId);

// Resume subscription
$success = $squareService->resumeSubscription($subscriptionId);
```

#### Webhook Verification

```php
$isValid = $squareService->verifyWebhookSignature(
    body: $request->getContent(),
    signature: $request->header('X-Square-Hmacsha256-Signature'),
    url: $request->fullUrl()
);
```

### Model Methods

#### User Model

```php
// Check if user has Square connected
$user->hasSquareConnected(); // bool

// Connect Square account
$user->connectSquare(
    accessToken: $token,
    merchantId: $merchantId,
    locationId: $locationId
);

// Disconnect Square account
$user->disconnectSquare();

// Get decrypted access token
$token = $user->getSquareAccessToken();
```

#### Group Model

```php
// Check billing mode
$group->isManualBilling();      // bool
$group->isAutoSquareBilling();  // bool

// Check if owner has Square connected
$group->ownerHasSquareConnected(); // bool
```

#### GroupMember Model

```php
// Check Square connection
$member->hasSquareCustomer();      // bool
$member->hasSquareSubscription();  // bool

// Check if in auto-Square group
$member->isInAutoSquareGroup();    // bool

// Update Square IDs
$member->updateSquareIds(
    customerId: $customerId,
    subscriptionId: $subscriptionId
);

// Clear Square IDs (on disconnect/cancel)
$member->clearSquareIds();

// Get owner's Square access token
$token = $member->getOwnerSquareAccessToken();
```

## Security

### Token Encryption

Square access tokens are encrypted using Laravel's encryption before storage:

```php
'square_access_token' => encrypt($accessToken),
```

And decrypted when needed:

```php
return decrypt($this->square_access_token);
```

### Webhook Signature Verification

All incoming webhooks are verified using HMAC-SHA256:

```php
$signature = hash_hmac('sha256', $body . $url, $webhookKey);
return hash_equals($signature, $receivedSignature);
```

### OAuth State Parameter

CSRF protection via random state parameter:

```php
$state = Str::random(40);
session(['square_oauth_state' => $state]);
```

## Testing

### Sandbox Mode

Use Square's sandbox environment for testing:

```env
SQUARE_ENVIRONMENT=sandbox
```

Sandbox features:
- No real money transactions
- Test card numbers available in Square docs
- Webhook events can be simulated

### Test Card Numbers

In sandbox mode, use these test cards:

| Card Number         | Result  |
|---------------------|---------|
| 4111 1111 1111 1111 | Success |
| 4000 0000 0000 0002 | Decline |
| 4000 0000 0000 0341 | CVV fail|

CVV: Any 3 digits  
Expiry: Any future date  
Postal Code: Any 5 digits

### Manual Webhook Testing

Trigger webhook events manually:

```bash
curl -X POST https://yourdomain.com/square/webhook \
  -H "Content-Type: application/json" \
  -H "X-Square-Hmacsha256-Signature: YOUR_SIGNATURE" \
  -d '{
    "type": "invoice.payment_made",
    "merchant_id": "YOUR_MERCHANT_ID",
    "data": {
      "object": {
        "invoice": {
          "id": "inv_123",
          "subscription_id": "sub_123"
        }
      }
    }
  }'
```

## Troubleshooting

### Common Issues

#### 1. "Square connection failed: Invalid state"

**Cause**: OAuth state mismatch (CSRF protection)

**Solution**: Clear browser cookies and try connecting again

#### 2. "Cannot disconnect Square while you have X active auto-Square groups"

**Cause**: Safety check prevents disconnecting with active groups

**Solution**: Close or convert all auto-Square groups to manual first

#### 3. Webhook signature verification fails

**Cause**: Incorrect webhook signature key in .env

**Solution**: 
1. Go to Square Dashboard → Webhooks
2. Copy the Webhook Signature Key
3. Update `SQUARE_WEBHOOK_SIGNATURE_KEY` in .env
4. Restart your application

#### 4. Payment request not marked as paid

**Cause**: Webhook not received or subscription ID mismatch

**Solution**:
1. Check Square webhook logs for delivery failures
2. Verify subscription ID in database matches Square
3. Check application logs for webhook processing errors

### Debug Mode

Enable detailed logging:

```php
// In SquareService methods
\Log::debug('Square API call', [
    'method' => 'createSubscription',
    'customer_id' => $customerId,
    'amount_cents' => $amountCents,
]);
```

View logs:

```bash
tail -f storage/logs/laravel.log | grep Square
```

## Production Checklist

Before going live:

- [ ] Switch to production Square environment: `SQUARE_ENVIRONMENT=production`
- [ ] Update OAuth redirect URL in Square dashboard
- [ ] Update webhook URL in Square dashboard
- [ ] Test OAuth connection flow
- [ ] Test subscription creation with real card
- [ ] Test webhook delivery (use Square webhook logs)
- [ ] Verify payment request marking logic
- [ ] Set up monitoring/alerts for webhook failures
- [ ] Document runbook for common issues
- [ ] Train support team on Square-related issues

## Support

### Square Support

- Developer Docs: https://developer.squareup.com/docs
- API Reference: https://developer.squareup.com/reference/square
- Developer Forums: https://developer.squareup.com/forums

### TogetherPay Support

For Square integration issues:

1. Check application logs: `storage/logs/laravel.log`
2. Check Square webhook logs in dashboard
3. Review this documentation
4. Contact development team with logs and error details

## Roadmap

Planned enhancements:

- [ ] Pro-rated billing for mid-cycle joins
- [ ] Multi-currency support
- [ ] Invoice customization
- [ ] Payment failure retry logic
- [ ] Member portal for payment method management
- [ ] Analytics dashboard for Square transactions
- [ ] Refund handling
- [ ] Dunning management (failed payment recovery)

## License

Square integration code is part of TogetherPay and subject to the same license terms.
