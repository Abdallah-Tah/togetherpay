# Cash App Pay Recurring Billing Analysis

## Executive Summary

After extensive research, here's the reality of Cash App Pay recurring billing:

### ✅ What Works (Direct Cash App Partner API)
- **On-file grants** - Customer authorizes merchant to save payment method
- **Recurring charges** - Merchant can charge saved grant on schedule
- **Customer approval** - Each charge requires Cash App push notification approval
- **True recurring billing** - Like Claude.ai, Netflix, Spotify

### ❌ What Doesn't Work (Square + Cash App Pay)
- **Square CANNOT save Cash App accounts on file**
- **Square Subscriptions API does NOT support Cash App Pay**
- **Only one-time Cash App payments via Square**

---

## Three Integration Paths

### Path 1: Direct Cash App Pay Partner API ✅ (RECOMMENDED)
**Pros:**
- Native recurring billing with Cash App
- Customer authorizes once, approves each charge
- Exactly like Claude.ai experience
- Money goes directly to owner's Cash App

**Cons:**
- Requires Cash App Partner approval process
- Separate integration from Square
- More complex implementation

**Flow:**
```
1. Member authorizes recurring billing (creates grant)
2. Each billing cycle:
   - App charges grant via Cash App API
   - Member gets Cash App push notification
   - Member approves payment
   - Money transfers to owner's Cash App
   - Webhook confirms payment
```

---

### Path 2: Square + Cards Only ⚠️
**Pros:**
- Simple Square integration
- True automatic recurring (no approval needed)
- Works with existing Square account

**Cons:**
- NO Cash App support
- Members must use credit/debit cards
- Defeats the purpose of "fee-free" P2P

**Flow:**
```
1. Member saves card via Square Checkout
2. Each billing cycle:
   - Square auto-charges card
   - Money goes to owner's Square merchant account
   - Owner transfers to bank
```

---

### Path 3: Hybrid Manual (Current Implementation) ⚠️
**Pros:**
- Works today with existing code
- Supports both Cash App and cards
- No special approvals needed

**Cons:**
- NOT automatic/recurring
- Member must manually pay each cycle
- Owner must manually confirm
- High friction

**Flow:**
```
1. Each billing cycle:
   - App sends notification with deep link
   - Member clicks link
   - Cash App opens with pre-filled payment
   - Member completes payment
   - Member marks as "paid"
   - Owner confirms receipt
```

---

## Cash App Pay Partner API Details

### How "On-File Payments" Work

**Customer Request Flow:**
1. Merchant creates customer request with `ON_FILE_PAYMENT` action
2. Customer receives Cash App notification
3. Customer approves request in Cash App
4. Grant is created and saved
5. Merchant can now charge grant repeatedly

**Charging Saved Grant:**
```
POST /partner/v1/payments
{
  "grant_id": "grant_xxx",
  "amount": {"amount": 1999, "currency": "USD"},
  "note": "Netflix - November 2025",
  "idempotency_key": "payment_123"
}
```

**Customer Experience:**
- Receives push notification: "Netflix is requesting $19.99"
- Opens Cash App
- Reviews charge details
- Taps "Approve" or "Decline"
- Payment processes immediately if approved

### Key Requirements

1. **Partner Approval**: Must apply at https://developers.cash.app/
2. **Brand Account**: Need Cash App Business account
3. **API Credentials**: Client ID and API Key
4. **Webhook Endpoint**: To receive payment confirmations
5. **Customer Consent**: Must display authorization terms

### Authorization Language Required

> "By continuing, you authorize [Your App Name] to debit your Cash App account for this payment and future payments in accordance with [Your App Name]'s terms, until this authorization is revoked. You may cancel this authorization at any time in your Cash App settings."

---

## Comparison Matrix

| Feature | Direct Cash App API | Square + Cards | Hybrid Manual |
|---------|-------------------|----------------|---------------|
| **Cash App Support** | ✅ Yes | ❌ No | ✅ Yes |
| **Recurring Billing** | ✅ Yes (with approval) | ✅ Yes (automatic) | ❌ No |
| **Auto-charge** | ⚠️ Needs approval | ✅ Fully automatic | ❌ Manual |
| **Fee-free** | ✅ Yes | ❌ No (card fees) | ✅ Yes |
| **Setup Complexity** | 🔴 High | 🟢 Low | 🟢 Low |
| **Partner Approval** | ✅ Required | ❌ Not needed | ❌ Not needed |
| **User Friction** | 🟡 Medium (approve) | 🟢 Low (none) | 🔴 High (manual) |
| **Implementation Time** | 2-3 weeks | 1 week | ✅ Done |

---

## Recommendation

### For Production App: Direct Cash App Pay Partner API

**Why:**
1. Matches your vision (Claude.ai-style recurring billing)
2. Supports Cash App (fee-free P2P payments)
3. Better user experience than manual
4. Professional, scalable solution

**Implementation Steps:**

1. **Apply for Cash App Partner Status** (1-2 weeks)
   - Go to https://developers.cash.app/
   - Submit partnership application
   - Describe use case: recurring group billing

2. **Get Cash App Business Account**
   - Owner needs Cash App Business account
   - Links to bank account for payouts

3. **Integrate Cash App Pay Partner API** (1-2 weeks)
   - Implement customer request flow
   - Build grant management system
   - Create scheduled charge jobs
   - Set up webhook handler

4. **Update UI/UX**
   - Add "Connect Cash App" flow
   - Display authorization terms
   - Show recurring payment status

### Timeline
- **Week 1-2**: Partner approval process
- **Week 3-4**: API integration
- **Week 5**: Testing & refinement
- **Week 6**: Production launch

---

## Technical Architecture

### Database Changes Needed

```sql
-- Add to group_members table
ALTER TABLE group_members ADD COLUMN cash_app_grant_id VARCHAR;
ALTER TABLE group_members ADD COLUMN cash_app_customer_id VARCHAR;
ALTER TABLE group_members ADD COLUMN cash_app_grant_status VARCHAR; -- 'pending', 'active', 'revoked'
ALTER TABLE group_members ADD COLUMN cash_app_grant_created_at TIMESTAMP;

-- Add to payment_requests table
ALTER TABLE payment_requests ADD COLUMN cash_app_payment_id VARCHAR;
ALTER TABLE payment_requests ADD COLUMN cash_app_request_id VARCHAR;
ALTER TABLE payment_requests ADD COLUMN cash_app_approved_at TIMESTAMP;
```

### New Services Required

1. **CashAppService.php**
   - `createCustomerRequest()` - Initialize grant request
   - `chargeGrant()` - Charge saved grant
   - `verifyWebhook()` - Verify webhook signatures
   - `revokeGrant()` - Handle revocations

2. **Jobs**
   - `CreateCashAppGrantsJob` - Request grants for new members
   - `ChargeCashAppGrantsJob` - Monthly scheduled charges
   - `ProcessCashAppWebhookJob` - Handle payment confirmations

3. **Webhook Controller**
   - Handle `payment.created` events
   - Handle `payment.approved` events
   - Handle `grant.revoked` events

---

## API Endpoints Needed

### Cash App Pay Partner API

```
POST /partner/v1/customer-requests
POST /partner/v1/payments
GET  /partner/v1/payments/{id}
POST /partner/v1/refunds
```

### Your Backend API

```
POST /api/v1/cash-app/connect       - Initialize grant request
GET  /api/v1/cash-app/status        - Check grant status
POST /api/v1/cash-app/disconnect    - Revoke grant
POST /webhooks/cash-app              - Handle webhooks
```

---

## Environment Variables

```bash
# Cash App Partner API
CASH_APP_CLIENT_ID=your_client_id
CASH_APP_API_KEY=your_api_key
CASH_APP_BRAND_ID=your_brand_id
CASH_APP_ENVIRONMENT=sandbox  # or production
CASH_APP_WEBHOOK_SECRET=your_webhook_secret

# Endpoints
CASH_APP_API_URL=https://sandbox.api.cash.app  # or production URL
CASH_APP_WEBHOOK_URL=https://your-app.com/webhooks/cash-app
```

---

## Testing Strategy

### Sandbox Testing
1. Use Cash App sandbox environment
2. Create test customer accounts
3. Test grant creation flow
4. Test scheduled charges
5. Test webhook delivery
6. Test error scenarios

### Production Readiness
- [ ] Partner approval received
- [ ] API credentials configured
- [ ] Webhook endpoint secured
- [ ] Grant management UI complete
- [ ] Scheduled jobs configured
- [ ] Error handling implemented
- [ ] Logging and monitoring active
- [ ] Customer support docs ready

---

## Next Steps

1. **Decision**: Approve Cash App Partner API integration approach
2. **Apply**: Submit partner application to Cash App
3. **Plan**: Create detailed implementation timeline
4. **Build**: Implement Cash App integration
5. **Test**: Comprehensive sandbox testing
6. **Launch**: Production rollout with beta users

---

## Questions to Resolve

1. Do owners need individual Cash App Business accounts?
2. How are chargebacks/disputes handled?
3. What are the transaction limits?
4. Is there a monthly volume limit?
5. What support does Cash App provide partners?

---

## Conclusion

The **Direct Cash App Pay Partner API** is the only path that delivers the Claude.ai-style recurring billing experience you want. While it requires more setup (partner approval + integration), it's the professional solution that matches your product vision.

The alternative (Square + cards only) works but defeats the purpose of fee-free P2P payments that Cash App provides.

**Recommendation**: Proceed with Cash App Partner application and plan for 4-6 week implementation timeline.
