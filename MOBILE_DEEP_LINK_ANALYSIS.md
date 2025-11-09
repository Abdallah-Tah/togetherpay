# Mobile Deep Link Analysis

## Executive Summary

The mobile Flutter app has **two different payment flows** with different deep link handling strategies:

1. ✅ **Pay Screen** (`lib/pay_screen.dart`) - Uses backend API deep links (benefits from improved `CashAppDriver`)
2. ⚠️ **Payment Request Details Sheet** (`lib/screens/payments/payment_request_details_sheet.dart`) - Manually builds URLs (doesn't use backend improvements)

## Current Implementation

### 1. Pay Screen (✅ Using Backend API)

**File**: `mobile/lib/pay_screen.dart`

**Flow**:
```dart
// Lines 72-82: Fetches payment request from API
final response = await _dio.get('/requests/by-code/${widget.prCode}');
final data = response.data as Map<String, dynamic>;

_deepLinkUri = data['deep_link'] as String?;        // Backend-generated
_webFallback = data['web_fallback_link'] as String?; // Backend-generated
```

**Deep Link Handling** (lines 114-184):
```dart
// Try deep link first
if (_deepLinkUri != null) {
  final uri = Uri.tryParse(_deepLinkUri!);
  if (uri != null && await canLaunchUrl(uri)) {
    targetUri = uri;
  }
}

// Fallback to web
if (targetUri == null && _webFallback != null) {
  final uri = Uri.tryParse(_webFallback!);
  if (uri != null && await canLaunchUrl(uri)) {
    targetUri = uri;
  }
}

// Launch with external application mode
await launchUrl(targetUri, mode: LaunchMode.externalApplication);
```

**Benefits**:
- ✅ Uses improved backend `CashAppDriver` with smart deep links
- ✅ Proper fallback chain (deep link → web fallback)
- ✅ Amount and recipient prefilled from backend
- ✅ Consistent with backend logic

### 2. Payment Request Details Sheet (⚠️ Manual URL Building)

**File**: `mobile/lib/screens/payments/payment_request_details_sheet.dart`

**Flow**:
```dart
// Lines 233-337: Manually builds Cash App URLs
Future<void> _openPaymentApp(String app, String? handle) async {
  final amount = (widget.paymentRequest['amount_cents'] as int? ?? 0) / 100.0;
  final amountStr = amount.toStringAsFixed(2);
  final note = 'Payment for ${widget.paymentRequest['group']?['name'] ?? 'TogetherPay'}';

  if (app == 'Cash App') {
    // Manually builds URL - doesn't use backend API
    var cashtag = handle.startsWith('\$') ? handle.substring(1) : handle;
    cashtag = cashtag.replaceAll(' ', '').trim();

    final cashAppUrl = Uri.parse('https://cash.app/\$${cashtag}/${amountStr}');

    // Uses platformDefault mode
    await launchUrl(cashAppUrl, mode: LaunchMode.platformDefault);
  }
}
```

**Issues**:
- ❌ Doesn't use backend API deep links
- ❌ No deep link URI generation (`cashapp://send?...`)
- ❌ Only generates web URL
- ❌ Misses out on improved `CashAppDriver` features
- ❌ No note parameter in URL
- ❌ Different behavior than Pay Screen

## Recommendations

### Option 1: Fetch Deep Links from API (Recommended)

Update `payment_request_details_sheet.dart` to fetch deep links from the backend API instead of building them manually.

**Implementation**:

```dart
Future<void> _openPaymentApp(String app, String? handle) async {
  if (handle == null || handle.isEmpty) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text('No $app handle found for this member')),
    );
    return;
  }

  // Fetch deep links from backend API
  final prId = widget.paymentRequest['id'];
  try {
    final response = await dio.get('/payment-requests/$prId/payment-link');
    final deepLink = response.data['uri'] as String?;
    final webFallback = response.data['web_fallback'] as String?;

    // Try deep link first
    Uri? targetUri;
    if (deepLink != null) {
      final uri = Uri.tryParse(deepLink);
      if (uri != null && await canLaunchUrl(uri)) {
        targetUri = uri;
      }
    }

    // Fallback to web
    if (targetUri == null && webFallback != null) {
      final uri = Uri.tryParse(webFallback);
      if (uri != null) {
        targetUri = uri;
      }
    }

    if (targetUri != null) {
      await launchUrl(targetUri, mode: LaunchMode.externalApplication);
    }
  } catch (e) {
    // Handle error
  }
}
```

**Benefits**:
- ✅ Uses improved backend deep link generation
- ✅ Consistent with Pay Screen behavior
- ✅ Single source of truth for deep links
- ✅ Automatic benefit from backend improvements
- ✅ Proper deep link + web fallback chain

**Backend API Endpoint Needed**:
```php
// routes/api.php
Route::get('/payment-requests/{paymentRequest}/payment-link', [PaymentRequestController::class, 'getPaymentLink'])
    ->middleware('auth:sanctum');
```

### Option 2: Use Consistent Manual URL Building

If API approach isn't feasible, at least make the manual URL building consistent with backend logic.

**Implementation**:

```dart
Future<void> _openPaymentApp(String app, String? handle) async {
  final amount = (widget.paymentRequest['amount_cents'] as int? ?? 0) / 100.0;
  final amountStr = amount.toStringAsFixed(2);
  final note = 'Payment for ${widget.paymentRequest['group']?['name'] ?? 'TogetherPay'}';

  if (app == 'Cash App') {
    var cashtag = handle.startsWith('\$') ? handle.substring(1) : handle;
    cashtag = cashtag.replaceAll(' ', '').trim();

    // Build deep link (like backend CashAppDriver)
    final recipient = '\$${cashtag}';
    final deepLink = Uri.parse(
      'cashapp://send?recipient=${Uri.encodeComponent(recipient)}&amount=$amountStr&note=${Uri.encodeComponent(note)}'
    );

    // Build web fallback
    final webFallback = Uri.parse('https://cash.app/\$$cashtag/$amountStr');

    // Try deep link first
    bool launched = false;
    try {
      launched = await launchUrl(deepLink, mode: LaunchMode.externalApplication);
    } catch (e) {
      // Deep link failed
    }

    // Fallback to web
    if (!launched) {
      await launchUrl(webFallback, mode: LaunchMode.platformDefault);
    }
  }
}
```

**Benefits**:
- ✅ Adds deep link support to manual implementation
- ✅ Proper fallback chain
- ⚠️ Still duplicates backend logic
- ⚠️ Manual sync required when backend changes

## Android Manifest Configuration

The Android manifest is properly configured to:

1. **Query Cash App and Venmo** (lines 72-79):
```xml
<queries>
  <package android:name="com.squareup.cash"/>
  <package android:name="com.venmo"/>
</queries>
```

2. **Handle Deep Links** (lines 36-53):
```xml
<intent-filter android:autoVerify="true">
  <action android:name="android.intent.action.VIEW"/>
  <category android:name="android.intent.category.DEFAULT"/>
  <category android:name="android.intent.category.BROWSABLE"/>

  <!-- Handle payment links -->
  <data android:scheme="https" android:host="tpay.app" android:pathPrefix="/p"/>
  <data android:scheme="https" android:host="togetherpay.app" android:pathPrefix="/pay"/>
</intent-filter>
```

## Testing Checklist

### Pay Screen (Already Working)
- [x] Deep link generation from backend
- [x] Web fallback support
- [x] Amount prefilling
- [x] Recipient prefilling
- [x] Note inclusion

### Payment Request Details Sheet (Needs Updates)
- [ ] Use backend API for deep links (Option 1)
  - [ ] Create `/payment-requests/{id}/payment-link` API endpoint
  - [ ] Update Flutter code to fetch from API
  - [ ] Test deep link → web fallback chain
- [ ] OR implement consistent manual deep links (Option 2)
  - [ ] Add `cashapp://send` deep link generation
  - [ ] Add proper fallback logic
  - [ ] Test on Android and iOS

### Both Screens
- [ ] Test on Android with Cash App installed
- [ ] Test on Android without Cash App installed
- [ ] Test on iOS with Cash App installed
- [ ] Test on iOS without Cash App installed
- [ ] Verify amount prefills correctly
- [ ] Verify recipient prefills correctly
- [ ] Verify note appears in transaction

## Priority Recommendation

**HIGH PRIORITY**: Implement Option 1 (Fetch from API)

**Reasons**:
1. Single source of truth for deep link logic
2. Automatic benefit from backend improvements
3. Consistent behavior across all payment flows
4. Easier to maintain and test
5. Better separation of concerns

**Timeline**:
- Backend API endpoint: 1-2 hours
- Mobile integration: 2-3 hours
- Testing: 2-4 hours
- **Total**: 1-2 days

## Related Files

### Backend
- `app/Services/PaymentDrivers/CashAppDriver.php` - Improved deep link generation
- `app/Services/PaymentDrivers/VenmoDriver.php` - Venmo deep link generation
- `app/Contracts/PaymentRequestDriver.php` - Payment driver interface
- `app/Http/Controllers/API/PaymentRequestController.php` - API controller (needs new endpoint)

### Mobile
- `mobile/lib/pay_screen.dart` - Pay screen (already using API)
- `mobile/lib/screens/payments/payment_request_details_sheet.dart` - Details sheet (needs update)
- `mobile/android/app/src/main/AndroidManifest.xml` - Android deep link config
- `mobile/ios/Runner/Info.plist` - iOS deep link config (not reviewed)

### Documentation
- `PAYMENT_DEEP_LINKS_IMPROVED.md` - Backend improvements summary
- `P2P_DEEP_LINKS_GUIDE.md` - P2P deep link reference
- `CASHAPP_DEEPLINK_FORMAT.md` - Cash App specific documentation
