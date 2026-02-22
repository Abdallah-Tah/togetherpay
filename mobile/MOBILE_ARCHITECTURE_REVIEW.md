# Flutter Mobile App Architecture Review

**Review Date:** 2026-02-22
**Scope:** Multi-Channel Payment Request Integration
**Backend Review:** See `/ARCHITECTURE_REVIEW.md`

---

## Executive Summary

The Flutter mobile app has **partial implementation** of the multi-channel payment request system. While owner payout configuration and payment link launching work correctly, **member payment channel preferences are completely missing** from the app.

### Health Score: 5.5/10
**Critical Issue:** Members cannot configure their payment channels (Venmo/Cash App preferences)

---

## ✅ What's Working Well

### 1. Owner Payout Channel Configuration

**File:** [lib/screens/settings/payment_methods_screen.dart](lib/screens/settings/payment_methods_screen.dart)

The PaymentMethodsScreen correctly implements owner payout channel configuration:

```dart
// Lines 62-65: Loads owner's payment accounts
_venmoUsernameController.text = _paymentAccounts?['venmo_username'] ?? '';
_cashTagController.text = _paymentAccounts?['cash_tag'] ?? '';
_cashPhoneController.text = _paymentAccounts?['cash_phone'] ?? '';
_cashEmailController.text = _paymentAccounts?['cash_email'] ?? '';

// Lines 97-111: Saves to backend
if (_venmoUsernameController.text.trim().isNotEmpty) {
  data['venmo_username'] = _venmoUsernameController.text.trim();
}
if (_cashTagController.text.trim().isNotEmpty) {
  data['cash_tag'] = _cashTagController.text.trim();
}
// ... etc
await dio.put('/auth/me', data: data);
```

**Status:** ✅ Fully implemented and matches backend User model fields

---

### 2. Payment Request Display & Launching

**File:** [lib/pay_screen.dart](lib/pay_screen.dart)

The PayScreen correctly handles multi-channel payment requests:

**API Integration:**
```dart
// Lines 84-108: Loads payment request
final response = await _dio.get('/requests/by-code/${widget.prCode}');
final data = response.data as Map<String, dynamic>;

_deepLinkUri = data['deep_link'] as String?;
_webFallback = data['web_fallback_link'] as String?;

// Extract payment_options from metadata
final metadata = data['metadata'];
if (metadata is Map<String, dynamic>) {
  final rawOptions = metadata['payment_options'];
  if (rawOptions is List) {
    _paymentOptions = rawOptions
        .whereType<Map>()
        .map((option) => Map<String, dynamic>.from(option))
        .toList();
  }
}
```

**Multi-Channel Support:**
```dart
// Lines 118-122: Auto-launch if only one option
if (_paymentOptions.length == 1) {
  await Future.delayed(const Duration(milliseconds: 400));
  if (mounted) {
    _launchPaymentOption(_paymentOptions.first);
  }
}

// Lines 304-340: Launch specific payment option
Future<void> _launchPaymentOption(Map<String, dynamic> option) async {
  final targetUri = _optionUri(option);
  if (targetUri == null) return;

  final launched = await launchUrl(
    targetUri,
    mode: LaunchMode.inAppBrowserView,
  );
  if (launched) {
    setState(() {
      _hasLaunchedWallet = true;
    });
  }
}
```

**Status:** ✅ Correctly integrates with backend's new payment_options structure

---

### 3. Deep-Link Handling

**Universal Link Strategy:**
```dart
// Lines 250-252: Prefers external browser for Cash App universal links
final launched = await launchPreferExternal(targetUri);
```

**Status:** ✅ Handles both Venmo (`venmo://`) and Cash App (`https://cash.app/`) schemes

---

### 4. Payment Confirmation Flow

**Lines 363-396: Two-step confirmation**
```dart
Future<void> _showPaymentConfirmationDialog() async {
  final result = await showDialog<String>(
    context: context,
    builder: (context) => AlertDialog(
      title: const Text('Did you complete the payment?'),
      actions: [
        TextButton(
          onPressed: () => Navigator.pop(context, 'later'),
          child: const Text('Not yet'),
        ),
        FilledButton(
          onPressed: () => Navigator.pop(context, 'yes'),
          child: const Text('Yes, I paid'),
        ),
      ],
    ),
  );

  if (result == 'yes') {
    await _confirmPayment();
  }
}
```

**Status:** ✅ Implements member-side confirmation flow

---

## ❌ Critical Gaps

### 1. Member Payment Channel Configuration - MISSING

**File:** [lib/screens/groups/add_member_sheet.dart](lib/screens/groups/add_member_sheet.dart)

**Problem:** When adding/editing members, the app **only** collects:
- Name
- Email
- Phone
- Share type/value

**Lines 285-292: Member payload (incomplete)**
```dart
final payload = <String, dynamic>{
  'share_type': _shareType,
  'share_value': double.tryParse(_shareValueController.text) ?? 0,
};

if (email.isNotEmpty) payload['email'] = email;
if (phone.isNotEmpty) payload['phone'] = phone;
if (name.isNotEmpty) payload['name'] = name;

// ❌ MISSING:
// - preferred_channel (VENMO or CASH_APP)
// - venmo_username
// - cash_tag
// - cash_phone (for Cash App)
// - cash_email (for Cash App)
```

**Impact:**
- Members have **no way** to specify their payment channel preference
- Backend won't know which channel the member prefers
- Payment requests will include all channels instead of prioritizing the member's choice
- Poor UX: members see all options instead of their preferred one first

**Expected Behavior:**
The AddMemberSheet should include:
```dart
// Payment Channel Selection Section
String? _preferredChannel; // 'VENMO' or 'CASH_APP'
final _memberVenmoController = TextEditingController();
final _memberCashTagController = TextEditingController();
final _memberCashPhoneController = TextEditingController();

// In payload:
if (_preferredChannel != null) {
  payload['preferred_channel'] = _preferredChannel;
}
if (_memberVenmoController.text.isNotEmpty) {
  payload['venmo_username'] = _memberVenmoController.text;
}
if (_memberCashTagController.text.isNotEmpty) {
  payload['cash_tag'] = _memberCashTagController.text;
}
// etc...
```

---

### 2. Member Payment Preferences Screen - MISSING

**Problem:** There's **no screen** for members to view/edit their payment channel preferences within a group.

**Current Structure:**
```
lib/screens/settings/
  ├── payment_methods_screen.dart  (✅ Owner payout channels)
  ├── notification_preferences_screen.dart
  ├── security_screen.dart
  └── ❌ member_payment_preferences_screen.dart (MISSING!)
```

**Expected Screen:**
A screen accessible from group details where members can:
1. Select preferred payment channel (Venmo or Cash App)
2. Enter their Venmo username
3. Enter their Cash App $cashtag/phone/email
4. See which channels the owner supports
5. Save changes to `/groups/{id}/members/{memberId}`

---

### 3. Payment Channel Validation - MISSING

**Problem:** No client-side validation for payment channel identifiers.

**Expected Validation:**
```dart
String? _validateVenmoUsername(String? value) {
  if (value == null || value.trim().isEmpty) return null;

  final cleaned = value.trim().replaceAll('@', '');
  if (cleaned.length < 5 || cleaned.length > 30) {
    return 'Venmo username must be 5-30 characters';
  }
  if (!RegExp(r'^[a-zA-Z0-9_-]+$').hasMatch(cleaned)) {
    return 'Venmo username can only contain letters, numbers, _, -';
  }
  return null;
}

String? _validateCashTag(String? value) {
  if (value == null || value.trim().isEmpty) return null;

  final cleaned = value.trim().replaceAll('\$', '');
  if (cleaned.length > 20) {
    return 'Cash App \$cashtag must be 1-20 characters';
  }
  if (!RegExp(r'^[a-zA-Z0-9]+$').hasMatch(cleaned)) {
    return 'Cash App \$cashtag can only contain letters and numbers';
  }
  return null;
}
```

---

## ⚠️ Potential Issues

### 1. Channel Routing Logic Not Client-Side

**Current:** PayScreen displays all available payment options equally
**Expected:** PayScreen should prioritize member's preferred_channel

**Suggested Enhancement:**
```dart
// In PayScreen._loadPaymentRequest()
if (_paymentOptions.isNotEmpty) {
  // Sort by is_preferred flag from backend
  _paymentOptions.sort((a, b) {
    final aPreferred = a['is_preferred'] as bool? ?? false;
    final bPreferred = b['is_preferred'] as bool? ?? false;
    return bPreferred.compareTo(aPreferred);
  });

  // Auto-launch preferred channel if exists
  final preferred = _paymentOptions.firstWhere(
    (opt) => opt['is_preferred'] == true,
    orElse: () => _paymentOptions.first,
  );
  _launchPaymentOption(preferred);
}
```

---

### 2. No Owner Channel Availability Indicator

**Problem:** Members don't see which payment channels the owner supports when configuring preferences.

**Suggested Enhancement:**
```dart
// In member payment preferences screen
Widget _buildChannelSelector() {
  return FutureBuilder<Map<String, bool>>(
    future: _loadOwnerSupportedChannels(),
    builder: (context, snapshot) {
      final ownerSupportsVenmo = snapshot.data?['VENMO'] ?? false;
      final ownerSupportsCashApp = snapshot.data?['CASH_APP'] ?? false;

      return Column(
        children: [
          ListTile(
            title: Text('Venmo'),
            subtitle: ownerSupportsVenmo
              ? null
              : Text('Owner doesn\'t accept Venmo',
                  style: TextStyle(color: Colors.orange)),
            leading: Radio<String>(
              value: 'VENMO',
              groupValue: _preferredChannel,
              onChanged: ownerSupportsVenmo ? (val) => setState(() {
                _preferredChannel = val;
              }) : null,
            ),
          ),
          // Similar for Cash App
        ],
      );
    },
  );
}
```

---

### 3. Square Invoice Handling

**File:** [lib/pay_screen.dart:110-124](lib/pay_screen.dart#L110-L124)

The app correctly detects Square invoices but the flow is incomplete:

```dart
if (_isSquareInvoice) {
  await _loadSquareInvoiceDetails();
} else {
  // ... launch payment option
}
```

**Status:** ⚠️ Square invoice UI may need enhancement (check build method)

---

## 📋 Implementation Priority

### P0: Critical (Blocks Member Onboarding)

| ID | Task | File | Impact | Effort |
|----|------|------|--------|--------|
| **P0-1** | Add member payment channel fields to AddMemberSheet | `add_member_sheet.dart` | Members can't configure channels | 2 hours |
| **P0-2** | Add payment channel validation | `add_member_sheet.dart` | Prevents invalid data | 1 hour |
| **P0-3** | Update member payload to include payment channels | `add_member_sheet.dart` | Sync with backend | 30 min |

**Total P0 Time:** ~3.5 hours

---

### P1: High Priority (UX Enhancement)

| ID | Task | File | Impact | Effort |
|----|------|------|--------|--------|
| **P1-1** | Create MemberPaymentPreferencesScreen | New file | Members can update preferences | 3 hours |
| **P1-2** | Add navigation to preferences from group details | `group_details_screen.dart` | Discoverability | 30 min |
| **P1-3** | Sort payment options by preference in PayScreen | `pay_screen.dart` | Better UX | 45 min |
| **P1-4** | Show owner supported channels indicator | `add_member_sheet.dart` | Prevents errors | 1 hour |

**Total P1 Time:** ~5.25 hours

---

### P2: Medium Priority (Polish)

| ID | Task | File | Impact | Effort |
|----|------|------|--------|--------|
| **P2-1** | Add payment channel icons (Venmo/Cash App logos) | `widgets/` | Visual clarity | 1.5 hours |
| **P2-2** | Add tooltips for payment channel fields | Various | User guidance | 1 hour |
| **P2-3** | Implement channel verification flow | New flow | Reduces errors | 3 hours |
| **P2-4** | Add payment channel preview in member list | `group_details_screen.dart` | Quick reference | 1.5 hours |

**Total P2 Time:** ~7 hours

---

## 🚀 Recommended Implementation Plan

### Phase 1: Member Channel Configuration (P0) - Week 1
**Goal:** Enable members to configure payment channels

**Day 1:**
1. Update AddMemberSheet form with channel selection UI
2. Add Venmo and Cash App input fields
3. Implement client-side validation

**Day 2:**
1. Update API payload to include channel data
2. Test add/edit member flow end-to-end
3. Test backend integration

**Deliverable:** Members can configure payment channels when added to groups

---

### Phase 2: Member Preferences Management (P1) - Week 2
**Goal:** Allow members to update preferences post-onboarding

**Day 1-2:**
1. Create MemberPaymentPreferencesScreen
2. Implement API integration with `/groups/{id}/members/{memberId}`
3. Add navigation from group details

**Day 3:**
1. Update PayScreen to prioritize preferred channel
2. Add owner channel support indicators
3. Test full payment flow

**Deliverable:** Complete member channel management

---

### Phase 3: Polish & Refinement (P2) - Week 3
**Goal:** Professional UX

**Day 1-2:**
1. Add payment channel icons and branding
2. Implement verification flow
3. Add helpful tooltips

**Day 3:**
1. Channel preview in member lists
2. Comprehensive testing
3. Bug fixes

**Deliverable:** Production-ready mobile experience

---

## 📂 File Structure Recommendations

### New Files Needed

```
lib/
├── screens/
│   ├── groups/
│   │   ├── add_member_sheet.dart (UPDATE)
│   │   ├── member_payment_preferences_screen.dart (NEW)
│   │   └── group_details_screen.dart (UPDATE - add navigation)
│   └── settings/
│       └── payment_methods_screen.dart (✅ Already good)
├── widgets/
│   ├── payment_channel_selector.dart (NEW - reusable component)
│   ├── payment_channel_icon.dart (NEW)
│   └── channel_availability_badge.dart (NEW)
└── utils/
    └── payment_validators.dart (NEW)
```

---

## 🔍 Testing Checklist

### Unit Tests Needed
- [ ] Payment channel validation (Venmo username format)
- [ ] Payment channel validation (Cash App $cashtag format)
- [ ] Member payload construction with channels
- [ ] Payment option sorting by preference

### Integration Tests Needed
- [ ] Add member with Venmo preference
- [ ] Add member with Cash App preference
- [ ] Update member payment preferences
- [ ] Load payment request with multiple options
- [ ] Auto-launch preferred payment channel

### E2E Tests Needed
- [ ] Complete member onboarding flow with channel selection
- [ ] Member updates payment preferences mid-cycle
- [ ] Payment request displays correct channel first
- [ ] Deep-link launches correct payment app

---

## 🔐 Security Considerations

### 1. Payment Handle Privacy
**Concern:** Member payment handles should not be visible to other members

**Status:** ⚠️ Need to verify API responses don't leak other members' handles

**Check:**
```dart
// In group_details_screen.dart when showing member list
// Ensure member.venmo_username is only visible to:
// 1. The member themselves
// 2. The group owner
// 3. NOT to other members
```

### 2. Owner Channel Verification
**Concern:** Prevent members from sending payments to unverified owner accounts

**Recommendation:**
```dart
// Add verification status to owner payment channels
if (!owner.isVenmoVerified && member.preferredChannel == 'VENMO') {
  showWarning('Owner\'s Venmo account is not verified. Contact them before sending payment.');
}
```

---

## 📊 API Endpoints Used

### Current Implementation
```
✅ GET  /auth/me                           (Load owner payment accounts)
✅ PUT  /auth/me                           (Update owner payment accounts)
✅ GET  /requests/by-code/{prCode}         (Load payment request)
✅ POST /groups/{id}/members               (Add member)
✅ PUT  /groups/{id}/members/{memberId}    (Update member)
❌ GET  /groups/{id}/owner/channels        (Get owner supported channels) - MISSING
```

### Needed Endpoints
```
GET  /groups/{id}/owner/channels
Response:
{
  "supported_channels": {
    "VENMO": {
      "enabled": true,
      "verified": true,
      "handle": "@ownervenmo"
    },
    "CASH_APP": {
      "enabled": true,
      "verified": false,
      "handle": "$ownercash"
    }
  }
}
```

---

## 🎨 UI/UX Recommendations

### 1. Add Member Sheet Enhancement

**Before (Current):**
```
┌─────────────────────────────┐
│ Add Group Member            │
├─────────────────────────────┤
│ Email: ____________________ │
│ Phone: ____________________ │
│ Share: ____________________ │
│                             │
│           [Add Member]      │
└─────────────────────────────┘
```

**After (Recommended):**
```
┌─────────────────────────────┐
│ Add Group Member            │
├─────────────────────────────┤
│ Contact Info                │
│ Email: ____________________ │
│ Phone: ____________________ │
│                             │
│ Payment Preferences         │
│ ○ Venmo  ● Cash App         │
│                             │
│ Cash App $cashtag:          │
│ __________________________  │
│ (or phone/email)            │
│                             │
│ Share Allocation            │
│ Amount: ___________________ │
│                             │
│           [Add Member]      │
└─────────────────────────────┘
```

### 2. Payment Request Enhancement

**Current:** All channels shown equally
**Recommended:** Preferred channel highlighted

```
┌─────────────────────────────┐
│ Payment Request             │
│ Amount: $50.00              │
├─────────────────────────────┤
│ Your Preferred Method       │
│ ┌─────────────────────────┐ │
│ │  [💸] Cash App          │ │
│ │  Tap to pay with $cashtag│ │
│ └─────────────────────────┘ │
│                             │
│ Other Options               │
│ ○ Venmo                     │
│ ○ Square Invoice (if owner  │
│   has Square)               │
└─────────────────────────────┘
```

---

## 🐛 Known Issues & Limitations

### 1. No Offline Mode
**Issue:** Payment channel configuration requires internet connection
**Impact:** Medium
**Recommendation:** Cache last known configuration

### 2. No Channel Switch Mid-Request
**Issue:** Once a payment request is generated, member can't switch channels
**Impact:** Low (can request new payment link from owner)
**Recommendation:** Add "Request different payment method" button

### 3. No Payment History per Channel
**Issue:** Can't see which payments were made via Venmo vs Cash App
**Impact:** Low (tracked on backend)
**Recommendation:** Add filter in payment history screen

---

## 📈 Metrics to Track

### Key Performance Indicators
1. **Channel Configuration Rate:** % of members who configure payment channels
2. **Preferred Channel Usage:** % of payments using member's preferred channel
3. **Payment Success Rate by Channel:** Venmo vs Cash App completion rates
4. **Time to First Payment:** From member added → first successful payment
5. **Channel Switch Rate:** How often members change their preference

### Analytics Events to Add
```dart
// When member configures channel
analytics.logEvent('member_channel_configured', {
  'channel': preferredChannel,
  'group_id': groupId,
});

// When payment request is opened
analytics.logEvent('payment_request_opened', {
  'has_preferred_channel': hasPreferredChannel,
  'options_count': paymentOptions.length,
});

// When payment link is launched
analytics.logEvent('payment_link_launched', {
  'channel': channelType,
  'is_preferred': isPreferred,
});
```

---

## 🎯 Success Criteria

### Phase 1 (P0) Complete When:
- [x] Backend P0 fixes deployed (PaymentDriverFactory, owner handle routing)
- [ ] Members can select preferred channel when being added
- [ ] Member payload includes all channel fields
- [ ] Client-side validation prevents invalid handles
- [ ] Payment requests successfully generated with member's channel

### Phase 2 (P1) Complete When:
- [ ] Members can update payment preferences after joining
- [ ] PayScreen prioritizes member's preferred channel
- [ ] Owner channel availability shown during configuration
- [ ] End-to-end payment flow works smoothly

### Phase 3 (P2) Complete When:
- [ ] Professional UI with payment channel branding
- [ ] Verification flow implemented
- [ ] Payment history shows channel breakdown
- [ ] All analytics events firing correctly

---

## 🔗 Related Documents

- **Backend Review:** [/ARCHITECTURE_REVIEW.md](../ARCHITECTURE_REVIEW.md)
- **Backend P0 Fixes:** Completed (see backend review)
- **API Documentation:** [README_API_INTEGRATION.md](README_API_INTEGRATION.md)
- **Mobile Implementation Summary:** [MOBILE_IMPLEMENTATION_COMPLETE.md](MOBILE_IMPLEMENTATION_COMPLETE.md)

---

## 📞 Next Steps

1. **Immediate:** Implement P0 fixes (member channel configuration in AddMemberSheet)
2. **This Week:** Create MemberPaymentPreferencesScreen
3. **Next Week:** Polish and testing
4. **Deploy:** Coordinate mobile + backend deployment

---

**Last Updated:** 2026-02-22
**Next Review:** After P0/P1 completion or 2026-03-08 (whichever comes first)
