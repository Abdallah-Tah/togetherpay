# Member Payment Channels - Mobile App Update

## Overview
Added payment channel configuration to the "Add/Edit Group Member" modal in the Flutter app.

**Date**: 2026-02-22
**Issue**: P0 Critical - Payment channel fields missing from member configuration
**File Modified**: `lib/screens/groups/add_member_sheet.dart`

## Changes Made

### 1. New Form Fields Added

**Payment Channel Selection:**
- Dropdown for preferred payment channel (Not set / Venmo / Cash App)
- Helper text: "How they prefer to receive payment requests"

**Conditional Fields (shown based on selection):**
- **Venmo**: Username field with `@` prefix
- **Cash App**: $cashtag field with `$` prefix

### 2. UI Updates

**Form Structure:**
```
┌─────────────────────────────────────┐
│ Full name                           │
│ Member email                        │
│ Phone (optional)                    │
│                                     │
│ Payment Information                 │ ← NEW SECTION
│ ├─ Preferred payment channel        │
│ ├─ Venmo username (if Venmo)        │
│ └─ Cash App $cashtag (if Cash App)  │
│                                     │
│ Contribution Details                │ ← NEW SECTION HEADER
│ ├─ Contribution type                │
│ └─ Amount                           │
└─────────────────────────────────────┘
```

### 3. Validation Rules

- **Venmo username**: Required when Venmo is selected as preferred channel
- **Cash App $cashtag**: Required when Cash App is selected as preferred channel
- Auto-formats handles:
  - Adds `@` prefix to Venmo username if not present
  - Adds `$` prefix to Cash App tag if not present

### 4. API Payload Updates

**New fields sent to backend:**
```json
{
  "email": "member@example.com",
  "name": "Member Name",
  "phone": "+1234567890",
  "share_type": "AMOUNT",
  "share_value": 100,
  "preferred_channel": "VENMO",        // ← NEW
  "venmo_username": "@membervenmo",    // ← NEW
  "cash_tag": "$membercash"           // ← NEW
}
```

### 5. Code Changes Summary

**New Controllers:**
```dart
final _venmoUsernameController = TextEditingController();
final _cashAppTagController = TextEditingController();
```

**New State:**
```dart
String? _preferredChannel; // VENMO, CASH_APP, or null
```

**Initialization (Edit Mode):**
```dart
_preferredChannel = member['preferred_channel'] as String?;
_venmoUsernameController.text = (member['venmo_username'] ?? '').toString();
_cashAppTagController.text = (member['cash_tag'] ?? '').toString();
```

## Testing Checklist

### Manual Testing Required:

- [ ] **Add New Member**
  - [ ] Select "Venmo" → Venmo field appears
  - [ ] Enter Venmo username without @ → Saves with @ prefix
  - [ ] Select "Cash App" → Cash App field appears
  - [ ] Enter cashtag without $ → Saves with $ prefix
  - [ ] Select "Not set" → No payment fields shown
  - [ ] Submit form → Verify API receives all fields

- [ ] **Edit Existing Member**
  - [ ] Member with Venmo → Fields pre-populate correctly
  - [ ] Member with Cash App → Fields pre-populate correctly
  - [ ] Member without payment info → Shows "Not set"
  - [ ] Change from Venmo to Cash App → Fields update correctly
  - [ ] Save changes → Verify API receives updated fields

- [ ] **Validation**
  - [ ] Select Venmo, leave username empty → Shows error
  - [ ] Select Cash App, leave tag empty → Shows error
  - [ ] Enter invalid username format → Validates correctly
  - [ ] Form submission prevents if validation fails

## Backend Compatibility

This update is **fully compatible** with the existing Laravel backend:

✅ **API Endpoints Support These Fields:**
- `POST /api/v1/groups/{id}/members` (create)
- `PUT /api/v1/groups/{id}/members/{memberId}` (update)

✅ **Database Schema:**
- `group_members.preferred_channel` (string, nullable)
- `group_members.venmo_username` (string, nullable)
- `group_members.cash_tag` (string, nullable)

✅ **Payment Request Generation:**
- Backend uses these fields in `PaymentRequestService::buildPaymentOptions()`
- Generates correct deep-links based on member preferences
- Falls back to owner's available channels

## User Experience Flow

### Adding a New Member with Payment Channel:

1. Owner taps "Add Member"
2. Fills in name, email
3. Scrolls to "Payment Information" section
4. Selects "Venmo" from dropdown
5. Venmo username field appears
6. Enters `@membervenmo`
7. Sets contribution amount
8. Taps "Add member"
9. Member saved with payment preference

### Editing Payment Channel:

1. Owner taps on existing member
2. Modal shows current payment channel
3. Can change from Venmo to Cash App
4. Corresponding field updates dynamically
5. Saves changes
6. Next payment request uses new preference

## Screenshots Location

Screenshots showing the new UI are in your phone:
- Before: Member edit with missing payment fields
- After: Member edit with payment channel section

## Next Steps

1. **Test on iPhone** with real data
2. **Verify API integration** - check network tab
3. **Test payment request generation** - ensure correct links
4. **Deploy to production** once tested

## Related Files

**Backend:**
- `app/Services/PaymentRequestService.php` - Uses payment channel data
- `app/Models/GroupMember.php` - Model with payment fields
- `database/migrations/*_add_payment_channels_to_group_members.php`

**Mobile:**
- `lib/screens/groups/add_member_sheet.dart` - This file (modified)
- `lib/services/api_service.dart` - API communication

## Migration Notes

**Existing members without payment channels:**
- Will show "Not set" in the dropdown
- Owner can edit and add payment info
- Payment requests will use all owner's available channels until preference is set

## Impact Assessment

**Before this change:**
- ❌ Members couldn't specify payment preferences
- ❌ Payment requests sent to all channels without preference
- ❌ No way to configure member's payment handles

**After this change:**
- ✅ Members can specify preferred channel (Venmo/Cash App)
- ✅ Payment requests prioritize member's preference
- ✅ Owner can configure member payment handles
- ✅ Correct deep-links generated based on preferences

## Security Considerations

- ✅ Input sanitization on backend
- ✅ Handle validation (@ and $ prefixes)
- ✅ No sensitive data stored
- ✅ Payment handles are for deep-link generation only
- ✅ No actual payment processing in app

---

**Status**: ✅ Implementation Complete
**Testing Status**: ⏳ Pending manual testing on device
**Deployment**: 🚀 Ready for staging deployment
