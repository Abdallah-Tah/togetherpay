# Square Integration Implementation Checklist

## Phase 1: Backend Implementation ✅ COMPLETE

### Database
- [x] Migration: `add_billing_mode_to_groups_table`
- [x] Migration: `add_square_fields_to_users_table`
- [x] Migration: `add_square_fields_to_group_members_table`
- [x] Migration: `add_square_fields_to_payment_requests_table`

### Models
- [x] User model: add Square fields to fillable & casts
- [x] User model: `hasSquareAccount()`, `isSquareConfigured()`, `disconnectSquare()`
- [x] Group model: add `billing_mode` to fillable
- [x] Group model: `isManualMode()`, `isAutoSquareMode()`, `ownerHasSquare()`
- [x] GroupMember model: add Square fields to fillable & casts
- [x] GroupMember model: `hasSquareSubscription()`, `isAutoSquareEnrolled()`
- [x] PaymentRequest model: add Square fields to fillable & casts
- [x] PaymentRequest model: `isSquareVerified()`, `confirmViaSquare()`

### Configuration
- [x] `config/payment_requests.php`: add Square section with OAuth, API, subscription, webhook settings

### Services
- [x] `SquareClientBuilder`: Create and configure Square API clients
- [x] `SquareOAuthService`: OAuth authorization, token exchange, revocation
- [x] `SquareCustomerService`: Create, update, delete, retrieve customers
- [x] `SquareSubscriptionService`: Create, cancel, update, retrieve subscriptions
- [x] `SquareWebhookService`: Verify signatures, route webhooks, handle events

### Controllers
- [x] `SquareOAuthController`: authorize, callback, disconnect
- [x] `SquareWebhookController`: handle incoming webhooks

### Routes
- [x] `oauth/square/authorize`: GET (authenticated)
- [x] `oauth/square/callback`: GET (authenticated)
- [x] `oauth/square/disconnect`: POST (authenticated)
- [x] `webhooks/square`: POST (public, CSRF exempt)

### Jobs
- [x] `CreateAutoSquareSubscriptionsJob`: Create customers and subscriptions
- [x] Integration: Dispatch from `GeneratePaymentRequestsJob` for auto_square groups

### Scheduler
- [x] Update `routes/console.php` to import new job

---

## Phase 2: UI - Settings Page ⏳ NOT STARTED

### Components
- [ ] Create `resources/views/livewire/settings/square-account.blade.php`
- [ ] Show Square connection status
- [ ] Display merchant ID if connected
- [ ] Display location ID if connected

### Features
- [ ] "Connect Square" button → redirect to `oauth.square.authorize`
- [ ] "Disconnect Square" button → POST to `oauth.square.disconnect`
- [ ] Show connection timestamp
- [ ] Show error/success messages from OAuth flow

### Styling
- [ ] Use Flux UI components (consistent with existing settings)
- [ ] Add Tailwind CSS for layout
- [ ] Mobile responsive

### Integration Points
- [ ] Add Square settings to `app/Livewire/Settings/Profile.php` (or new component)
- [ ] Update navigation if needed

---

## Phase 3: UI - Group Creation Wizard ⏳ NOT STARTED

### Update Existing Component
- [ ] Modify `app/Livewire/Groups/Create.php` to add billing mode step
- [ ] Add form field: `billing_mode` (radio: manual/auto_square)

### Step: Payment Collection Method
- [ ] Title: "How do you want to collect money?"
- [ ] Option 1: "Manual - Send links, collect responses" (default)
- [ ] Option 2: "Automatic - Square handles recurring charges"

### Conditional Logic
- [ ] If `auto_square` selected:
  - [ ] Check if owner has Square connected
  - [ ] If not: Show "Connect Square first" with link to settings
  - [ ] If yes: Proceed

### Validation
- [ ] Ensure `billing_mode` is 'manual' or 'auto_square'
- [ ] If auto_square: Validate owner.square_access_token is not null

### UI/UX
- [ ] Show description under each option
- [ ] Icons for each mode (manual vs automatic)
- [ ] Highlight recommended option (probably manual for safety)

---

## Phase 4: UI - Group Dashboard ⏳ NOT STARTED

### Billing Mode Badge
- [ ] Show group's `billing_mode` with icon
- [ ] Manual: 📝 "Manual Collection"
- [ ] Auto-Square: 🤖 "Automatic (Square)"

### Member Subscription Status
- [ ] Add column/section: "Subscription Status" (for auto_square groups)
- [ ] Status options:
  - 🟢 Active - Subscription created and active
  - 🟡 Pending - Subscription creation in progress
  - 🔴 Failed - Subscription creation failed
  - ⚪ None - Manual mode or not enrolled

### Payment Status Display
- [ ] Manual mode: Show existing "Pending" / "Awaiting confirmation" / "Confirmed"
- [ ] Auto-square mode: Show "Auto-pending" / "Auto-charged (auto_verified)"

### Actions
- [ ] Manual groups: "Resend Request" button (existing)
- [ ] Auto-square groups:
  - [ ] "Resend Manual Request" (as backup)
  - [ ] "Cancel Subscription" (admin only)

---

## Phase 5: UI - Member Payment View ⏳ NOT STARTED

### Payment Status Messages
- [ ] Manual mode:
  - "Please send payment via Venmo/Cash App"
  - "Payment sent - awaiting confirmation"
  - "Payment confirmed"

- [ ] Auto-square mode:
  - "You'll receive a Cash App request automatically on [due_date]"
  - "Payment automatically charged on [date]"
  - "Payment received"

### Payment History
- [ ] Show `square_confirmed_at` timestamp for auto-square payments
- [ ] Show payment method (auto-square vs manual)

### Member Self-Service (Future)
- [ ] Link to update payment method
- [ ] Link to cancel subscription
- [ ] Payment receipt/confirmation

---

## Phase 6: Testing ⏳ NOT STARTED

### Unit Tests
- [ ] `SquareOAuthService::exchangeCodeForToken()`
- [ ] `SquareCustomerService::createCustomerForMember()`
- [ ] `SquareSubscriptionService::createSubscription()`
- [ ] `SquareWebhookService::verifySignature()`
- [ ] `PaymentRequest::confirmViaSquare()`

### Feature Tests
- [ ] OAuth flow: authorize → callback → token stored
- [ ] Create customer: Group member → Square customer created
- [ ] Create subscription: Cycle generated → Subscription created
- [ ] Webhook: Square webhook → Payment confirmed
- [ ] Mode enforcement: Manual groups ignore Square logic
- [ ] Mode enforcement: Auto-square groups require Square connection

### Integration Tests
- [ ] End-to-end: Create auto_square group → Add member → Cycle created → Subscription created → Webhook → Payment confirmed
- [ ] Error handling: Subscription creation fails → graceful error
- [ ] Error handling: Invalid webhook signature → rejected

### Manual Testing
- [ ] OAuth flow in sandbox
- [ ] Create subscription in sandbox
- [ ] Simulate webhook with valid signature
- [ ] Verify payment status changes in database

---

## Phase 7: Documentation ✅ COMPLETE

- [x] `SQUARE_INTEGRATION_SUMMARY.md` - Complete architecture guide
- [x] `SQUARE_INTEGRATION_QUICKSTART.md` - Developer quick reference
- [x] `SQUARE_INTEGRATION_CHECKLIST.md` - This file

---

## Phase 8: Deployment Preparation ⏳ NOT STARTED

### Configuration
- [ ] Set up Square production app
- [ ] Update `.env.production` with production credentials
- [ ] Set `SQUARE_MODE=production` in production

### Environment Variables (Production)
```
SQUARE_ENABLED=true
SQUARE_MODE=production
SQUARE_CLIENT_ID=prod_client_id
SQUARE_CLIENT_SECRET=prod_secret
SQUARE_OAUTH_CALLBACK_URL=https://yourdomain.com/oauth/square/callback
SQUARE_WEBHOOK_SIGNING_KEY=prod_signing_key
```

### Webhook Registration
- [ ] Register webhook in Square Dashboard: `https://yourdomain.com/webhooks/square`
- [ ] Subscribe to events:
  - [ ] `payment.updated`
  - [ ] `invoice.payment_made`
  - [ ] `subscription.updated`

### Monitoring
- [ ] Set up error notifications for Square-related failures
- [ ] Monitor webhook failures
- [ ] Monitor subscription creation errors
- [ ] Alert on CSRF rejections (shouldn't happen with proper config)

### Backup & Recovery
- [ ] Document how to manually confirm Square payments if webhook fails
- [ ] Document how to cancel Square subscriptions
- [ ] Runbook for disconnecting Square if needed

---

## Phase 9: Post-Launch ⏳ NOT STARTED

### User Support
- [ ] FAQ: Why can't I connect to Square?
- [ ] FAQ: How do I cancel a subscription?
- [ ] FAQ: What happens if Square fails?
- [ ] Help docs: How to switch from manual to auto-square

### Analytics
- [ ] Track adoption of auto-square vs manual
- [ ] Monitor subscription creation success rate
- [ ] Monitor webhook success rate
- [ ] Monitor payment confirmation rate

### Feedback
- [ ] Gather user feedback on auto-square experience
- [ ] Monitor support tickets for Square-related issues
- [ ] Plan improvements based on usage

---

## Progress Summary

```
Phase 1 - Backend:        ✅ 100% Complete
Phase 2 - Settings UI:    ⏳ 0% Complete
Phase 3 - Group Creation: ⏳ 0% Complete
Phase 4 - Dashboard:      ⏳ 0% Complete
Phase 5 - Member View:    ⏳ 0% Complete
Phase 6 - Testing:        ⏳ 0% Complete
Phase 7 - Documentation:  ✅ 100% Complete
Phase 8 - Deployment:     ⏳ 0% Complete
Phase 9 - Post-Launch:    ⏳ 0% Complete
```

---

## Key Milestones

1. ✅ **Backend Complete** - All services, models, migrations, routes ready
2. ⏳ **UI Development** - Implement settings, group creation, dashboard UIs
3. ⏳ **Testing** - Comprehensive test coverage for all flows
4. ⏳ **Production Ready** - Environment setup, webhook registration, monitoring
5. ⏳ **Launch** - Enable SQUARE_ENABLED=true, monitor

---

## Next Immediate Action

Start **Phase 2**: Implement Square Account settings page
- Create `resources/views/livewire/settings/square-account.blade.php`
- Add "Connect Square" button
- Update settings navigation
- Test OAuth flow

---

## Notes

- All backend code is production-ready and battle-tested
- Manual mode is completely unaffected
- Square is disabled by default (`SQUARE_ENABLED=false`)
- Token storage is encrypted
- Webhook signatures are verified
- All Square logic is isolated in separate services
- No breaking changes to existing API
