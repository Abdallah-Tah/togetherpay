# Cash App Deep Link Format - Working Solution ✅

## The Correct Format

```
https://cash.app/$IkramBarkad/5.00
```

## Format Breakdown

```
https://cash.app/$<cashtag>/<amount>
```

- **Protocol:** `https://` (required)
- **Domain:** `cash.app` (official domain)
- **Cashtag:** `$IkramBarkad` (with dollar sign)
- **Amount:** `5.00` (decimal format, 2 places)

## What Works ✅

- Direct amount in URL path: `/5.00`
- Opens Cash App on mobile devices
- Opens in browser then redirects on desktop
- Shows recipient's profile immediately
- Amount is visible in the URL (helpful for user)

## What Doesn't Work ❌

```
❌ https://cash.app/$IkramBarkad/5.00?note=Payment
❌ https://cash.app/$IkramBarkad/5.00?memo=Test
❌ https://cash.app/$IkramBarkad?amount=5.00
❌ cashapp://send?amount=5.00&cashtag=IkramBarkad
```

**Cash App ignores ALL query parameters for security reasons.**

## Implementation

### Backend (Laravel)

**File:** `app/Support/PaymentLinks.php`
```php
public static function cashApp(string $recipient, int $amountCents, string $note = ''): string
{
    $amount = number_format($amountCents / 100, 2, '.', '');
    $cashtag = ltrim($recipient, '$');
    
    return "https://cash.app/\${$cashtag}/{$amount}";
}
```

**File:** `app/Services/PaymentDrivers/CashAppDriver.php`
```php
public function buildLink(GroupMember $member, int $amountCents, string $prCode, string $note): array
{
    $amount = number_format($amountCents / 100, 2, '.', '');
    
    if ($member->cash_tag) {
        $cashtag = ltrim($member->cash_tag, '$');
        $link = sprintf('https://cash.app/$%s/%s', $cashtag, $amount);
        
        return [
            'uri' => $link,
            'web_fallback' => $link,
        ];
    }
    
    // Fallback for phone/email...
}
```

### Mobile (Flutter)

**File:** `mobile/lib/screens/payments/payment_request_details_sheet.dart`
```dart
if (app == 'Cash App') {
  var cashtag = handle.startsWith('\$') ? handle.substring(1) : handle;
  cashtag = cashtag.replaceAll(' ', '').trim();
  
  final cashAppUrl = Uri.parse('https://cash.app/\$$cashtag/$amountStr');
  
  final launched = await launchUrl(
    cashAppUrl, 
    mode: LaunchMode.platformDefault
  );
}
```

## Testing

### Test in Browser
```bash
# Open this in your browser
open "https://cash.app/\$AbdallahTM/5.00"
```

### Test with curl
```bash
curl -I "https://cash.app/\$AbdallahTM/5.00"
# Should return 200 or redirect
```

### Test in Mobile App
1. Send link via SMS/Email: `https://cash.app/$AbdallahTM/5.00`
2. Tap link on phone
3. Cash App should open
4. User sees: "Pay $AbdallahTM"
5. User manually enters: $5.00
6. User taps Send

## Expected Log Output

When working correctly, you should see:

```
I/UrlLauncher: component name for https://cash.app/$AbdallahTM/5.00 is {com.squareup.cash/com.squareup.cash.ui.MainActivity}
```

**No more of this:**
```
❌ I/UrlLauncher: component name for cashapp://send?amount=... is null
❌ https://cash.app/$AbdallahTM/5.00?note=...&pr=... (query params ignored)
```

## User Experience

1. **User receives link:** `https://cash.app/$IkramBarkad/5.00`
2. **User clicks link** → Cash App opens
3. **User sees:** "Send to $IkramBarkad"
4. **Amount field:** Empty (user must enter $5.00)
5. **User enters amount** from memory/message
6. **User taps Send** → Payment complete! 🎉

## Future Enhancement: Cash App API

To actually pre-fill the amount (not just show it in URL), you need:

1. **Cash App Business Account**
2. **API Access** (2-3 week approval)
3. **OAuth Flow** to get customer IDs
4. **Customer Request API:**

```php
POST https://api.cash.app/customer-requests/v1
{
  "idempotency_key": "unique-id",
  "amount": {
    "amount": 500,  // $5.00 in cents
    "currency": "USD"
  },
  "scope_id": "customer_id_from_oauth",
  "actions": {
    "payment_action": {
      "scope_id": "recipient_id"
    }
  },
  "note": "T-Mobile Family Plan - Nov 2025"
}
```

**See:** `CASHAPP_API_REGISTRATION_GUIDE.md` for full API setup.

## Summary

✅ **Current solution (Production Ready):**
- Format: `https://cash.app/$cashtag/amount`
- Works immediately, no API needed
- User manually enters amount

🚀 **Future solution (Requires API):**
- Customer Request API
- Amount truly pre-filled
- Push notifications
- Webhook confirmations

---

**Last Updated:** November 8, 2025  
**Status:** ✅ Working in Production
