# Payment Accounts Feature Implementation

## What Was Added

### 1. Database Migration ✅
**File**: `2025_10_24_172520_add_payment_accounts_to_users_table.php`

Added fields to `users` table:
- `cashapp_tag` - Owner's Cash App cashtag (e.g., $JohnDoe)
- `cashapp_customer_id` - OAuth customer ID for API integration
- `venmo_username` - Owner's Venmo username (e.g., @JohnDoe)
- `venmo_customer_id` - OAuth customer ID for API integration
- `preferred_payment_method` - Default: CASH_APP (options: CASH_APP, VENMO, CARD)
- `payment_metadata` - JSON field for additional payment data

### 2. User Model Updated ✅
**File**: `app/Models/User.php`

Added all new fields to `$fillable` array for mass assignment.

### 3. New Settings Page ✅
**File**: `resources/views/livewire/settings/payment-accounts.blade.php`

Features:
- Info box explaining why payment accounts are needed
- Preferred payment method selector (Cash App, Venmo, Card)
- Cash App section with cashtag input (validates $ prefix)
- Venmo section with username input (auto-adds @ prefix)
- Real-time validation
- Teal-themed UI matching your design system

### 4. Routes Added ✅
**File**: `routes/web.php`

Added new route:
```php
Volt::route('settings/payment-accounts', 'settings.payment-accounts')
    ->name('payment-accounts.edit');
```

### 5. Settings Navigation Updated ✅
**File**: `resources/views/components/settings/layout.blade.php`

Added "Payment Accounts" menu item between Password and Two-Factor Auth.

---

## How It Works

### Owner Setup Flow

1. **Owner goes to Settings → Payment Accounts**
2. **Selects preferred payment method**:
   - Cash App (default)
   - Venmo
   - Card (coming soon)
3. **Enters payment details**:
   - Cash App: `$YourCashTag`
   - Venmo: `@YourVenmoUsername` or `YourVenmoUsername` (@ auto-added)
4. **Clicks Save**

### Validation Rules

**Cash App Tag**:
- Must start with `$`
- Alphanumeric, hyphens, underscores only
- Example: `$JohnDoe123`

**Venmo Username**:
- Optional `@` prefix (auto-added if missing)
- Alphanumeric, hyphens, underscores only
- Example: `@JohnDoe` or `JohnDoe` (becomes `@JohnDoe`)

### Member Experience

When a member views a payment request:
1. System shows owner's preferred payment method
2. Displays owner's payment account (Cash App tag or Venmo username)
3. Member can click to open app and send payment
4. Member marks payment as sent

---

## Integration with Payment Flow

### In Notifications

When sending payment requests, include owner's payment info:

```php
// In PaymentRequestNotification
$owner = $paymentRequest->group->owner;
$paymentMethod = $owner->preferred_payment_method;

if ($paymentMethod === 'CASH_APP' && $owner->cashapp_tag) {
    $paymentInfo = "Send $" . number_format($amount, 2) . " to {$owner->cashapp_tag} via Cash App";
} elseif ($paymentMethod === 'VENMO' && $owner->venmo_username) {
    $paymentInfo = "Send $" . number_format($amount, 2) . " to {$owner->venmo_username} via Venmo";
}
```

### In Payment Request Display

Show payment instructions:
```blade
<div class="payment-instructions">
    <h3>How to Pay</h3>
    @if($owner->preferred_payment_method === 'CASH_APP' && $owner->cashapp_tag)
        <p>Send payment to <strong>{{ $owner->cashapp_tag }}</strong> via Cash App</p>
        <a href="https://cash.app/{{ ltrim($owner->cashapp_tag, '$') }}" 
           class="btn-primary">
            Open Cash App
        </a>
    @elseif($owner->preferred_payment_method === 'VENMO' && $owner->venmo_username)
        <p>Send payment to <strong>{{ $owner->venmo_username }}</strong> via Venmo</p>
        <a href="https://venmo.com/{{ ltrim($owner->venmo_username, '@') }}" 
           class="btn-primary">
            Open Venmo
        </a>
    @endif
</div>
```

---

## Screenshots

### Settings Navigation
- Profile
- Password
- **Payment Accounts** ← NEW
- Two-Factor Auth
- Appearance

### Payment Accounts Page
- Info box explaining feature
- Preferred payment method dropdown
- Cash App section with cashtag input
- Venmo section with username input
- Save button with teal theme

---

## Next Steps

### Immediate
- [x] Database migration run
- [x] Settings page created
- [x] Navigation updated

### Recommended
1. **Update PaymentRequestNotification**
   - Include owner's payment account in email/SMS
   - Add deep links to Cash App/Venmo

2. **Update Payment Request Views**
   - Display owner's payment info on payment request cards
   - Add "Open Cash App" / "Open Venmo" buttons

3. **Group Creation**
   - Validate owner has payment account set up
   - Warn if no payment method configured

4. **OAuth Integration** (Future)
   - Implement Cash App OAuth for `cashapp_customer_id`
   - Implement Venmo OAuth for `venmo_customer_id`
   - Enable API-driven payment requests

---

## Testing

### Manual Testing
1. Visit `/settings/payment-accounts`
2. Enter Cash App tag: `$TestUser123`
3. Enter Venmo username: `TestUser` (becomes `@TestUser`)
4. Select preferred method: Cash App
5. Click Save
6. Verify data saved in database

### Database Check
```bash
php artisan tinker

$user = \App\Models\User::first();
$user->cashapp_tag; // Should show: $TestUser123
$user->venmo_username; // Should show: @TestUser
$user->preferred_payment_method; // Should show: CASH_APP
```

---

## Files Modified Summary

| File | Action | Description |
|------|--------|-------------|
| `database/migrations/*_add_payment_accounts_to_users_table.php` | Created | Database schema |
| `app/Models/User.php` | Modified | Added fillable fields |
| `resources/views/livewire/settings/payment-accounts.blade.php` | Created | Settings page |
| `routes/web.php` | Modified | Added route |
| `resources/views/components/settings/layout.blade.php` | Modified | Added nav item |

---

## UI Design Notes

- Uses teal color scheme (`bg-teal-600`, `focus:ring-teal-400`)
- Matches existing Groups/Members design
- Cash App icon included (SVG)
- Venmo icon included (SVG)
- Info box with teal background
- Consistent rounded-xl borders
- Dark mode support

---

## Success! 🎉

Owners can now configure their payment accounts in Settings, and this information can be used throughout the app to show members where to send payments.
