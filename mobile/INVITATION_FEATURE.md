# Mobile App Invitation Feature

## Overview

The mobile app now supports a complete invitation flow that allows members to:
- Receive email invitations with deep links
- Accept invitations directly from the app
- Create accounts automatically through invitation acceptance
- Join groups seamlessly after accepting

## Features Implemented

### 1. Invitation Acceptance Screen

**Location:** `lib/screens/invitations/accept_invitation_screen.dart`

Two screens are included:

#### AcceptInvitationScreen
- Displays invitation details (group name, invited by, member info)
- Shows expiration status
- Two modes based on authentication status:
  - **Authenticated users:** Can directly accept or decline
  - **Guest users:** Prompted to create account or login

**Features:**
- Real-time invitation validation
- Expiration warnings
- Beautiful UI showing group details
- Member details preview (name, share count, payment methods)

#### InvitationSignupScreen
- Dedicated signup flow for invitation acceptance
- Pre-fills user data from invitation
- Creates account and auto-accepts invitation
- Automatically logs user in after signup

**Features:**
- Password validation (min 8 characters)
- Password confirmation matching
- Optional payment method setup
- Auto-login after successful registration

### 2. Deep Linking Support

**Configuration Files:**
- [mobile/android/app/src/main/AndroidManifest.xml](android/app/src/main/AndroidManifest.xml)

**Supported URL patterns:**
```
https://togetherpay.app/accept-invitation/{token}
https://www.togetherpay.app/accept-invitation/{token}
```

**How it works:**
1. User clicks invitation link in email
2. Android opens TogetherPay app automatically
3. App navigates to AcceptInvitationScreen with token
4. User sees invitation details and can accept/decline

**Deep link handling in:** [lib/main.dart:104-157](lib/main.dart#L104-L157)

### 3. API Integration

The mobile app integrates with these backend endpoints:

#### GET /invitations/{token}
Fetches invitation details including:
- Group information
- Inviter name
- Member data (name, share count, payment methods)
- Expiration status
- Validation state

#### POST /invitations/{token}/accept
Accepts invitation for authenticated users
- Requires: `{ "accept": true }`
- Creates group member record
- Marks invitation as accepted
- Returns group and member data

#### POST /invitations/{token}/accept-magic
Magic link acceptance for new users
- Creates user account with provided credentials
- Auto-accepts invitation
- Returns auth token for immediate login
- Supports:
  - Name
  - Password (with confirmation)
  - Optional Venmo username
  - Optional Cash App tag

### 4. User Flows

#### Flow 1: Existing User (Already Logged In)
```
Email → Click Link → App Opens → AcceptInvitationScreen
→ Review Details → Tap "Accept Invitation"
→ API Call → Success → Navigate to GroupDetailsScreen
```

#### Flow 2: New User (No Account)
```
Email → Click Link → App Opens → AcceptInvitationScreen
→ Tap "Create Account & Join" → InvitationSignupScreen
→ Fill Form → Tap "Create Account & Join Group"
→ API Call (accept-magic) → Auto-login → Navigate to Groups
```

#### Flow 3: Existing User (Not Logged In)
```
Email → Click Link → App Opens → AcceptInvitationScreen
→ Tap "Already have an account? Login" → LoginScreen
→ Login → Return to app → Click link again → Accept invitation
```

## Installation & Testing

### Build and Install

```bash
# Navigate to mobile directory
cd /Users/amohamed/Herd/togetherpay/mobile

# Build debug APK
flutter build apk --debug

# Connect Android device via USB
# Enable USB debugging on device

# Install APK
flutter install
# OR manually:
adb install -r build/app/outputs/flutter-apk/app-debug.apk
```

### Testing the Invitation Flow

#### 1. Create Test Invitation (Backend)

Use the owner account to invite a member:

```bash
# Login as group owner
curl -X POST https://www.togetherpay.app/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "owner@example.com",
    "password": "password"
  }'

# Create invitation (replace {token} and {groupId})
curl -X POST https://www.togetherpay.app/api/v1/groups/{groupId}/invitations \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer {token}" \
  -d '{
    "email": "newmember@example.com",
    "name": "John Doe",
    "share_count": 1,
    "venmo_username": "johndoe",
    "cashapp_cashtag": "johndoe"
  }'
```

Response includes:
```json
{
  "invitation_url": "https://togetherpay.app/accept-invitation/{invitationToken}"
}
```

#### 2. Test Deep Link

##### Option A: Using adb (Recommended)

```bash
# Open invitation link directly on device
adb shell am start -a android.intent.action.VIEW \
  -d "https://togetherpay.app/accept-invitation/{invitationToken}"
```

##### Option B: Email Testing

1. Send actual invitation email from backend
2. Open email on device
3. Tap invitation link
4. App should open automatically

##### Option C: Manual URL Entry

1. Open Chrome on Android device
2. Enter: `https://togetherpay.app/accept-invitation/{token}`
3. Chrome will prompt to open in TogetherPay app

#### 3. Verify Invitation Screen

Check that the screen shows:
- ✅ Invitation icon and "You're Invited!" heading
- ✅ Inviter name ("John invited you to join")
- ✅ Group name in card
- ✅ Member details (name, share count, payment methods)
- ✅ Expiration warning
- ✅ Accept/Decline buttons (if logged in)
- ✅ Create Account button (if not logged in)

#### 4. Test Acceptance

**As New User:**
1. Tap "Create Account & Join"
2. Fill in password (name pre-filled)
3. Optionally update payment methods
4. Tap "Create Account & Join Group"
5. Should auto-login and see app home screen
6. Verify you're added to the group

**As Existing User:**
1. Tap "Accept Invitation"
2. Should see success message
3. Navigate to group details
4. Verify you appear as member

#### 5. Test Edge Cases

- **Expired invitation:** Should show "Invitation Unavailable"
- **Already accepted:** Should show message and prevent re-acceptance
- **Invalid token:** Should show "Invalid Invitation" error
- **Network error:** Should show error with retry option

## Backend Requirements

The invitation flow requires these backend features (already implemented):

### Database Tables
- `group_invitations` - Stores invitation tokens and metadata
- `group_members` - Created when invitation accepted

### Email Notifications
- `GroupInvitationNotification` - Sends invitation email with deep link

### API Endpoints
- ✅ `POST /groups/{id}/invitations` - Create invitation
- ✅ `GET /invitations/{token}` - Get invitation details
- ✅ `POST /invitations/{token}/accept` - Accept (authenticated)
- ✅ `POST /invitations/{token}/accept-magic` - Accept (new users)

## Access Control

### Member-Only View

Currently, the groups list shows all groups. To restrict members to see only their groups:

**Update:** [lib/screens/groups/groups_list_screen.dart](lib/screens/groups/groups_list_screen.dart)

The groups API already filters by user:
- Owners see groups they created
- Members see groups they're added to

No mobile changes needed - backend handles this automatically.

### Notification Deep Links

When members receive payment request notifications, they should deep link to the specific request.

**To implement:**
1. Update notification payload to include payment request ID
2. Add deep link handler for `/payment-requests/{id}`
3. Navigate to `PaymentRequestDetailsSheet` on notification tap

**In main.dart:**
```dart
// Add to _handleDeepLink method
else if (uri.path.startsWith('/payment-requests/')) {
  final requestId = int.tryParse(uri.pathSegments.last);
  if (requestId != null && globalNavigatorKey.currentContext != null) {
    // Navigate to payment request details
    showModalBottomSheet(
      context: globalNavigatorKey.currentContext!,
      builder: (_) => PaymentRequestDetailsSheet(requestId: requestId),
    );
  }
}
```

## Security Considerations

### Token Security
- Invitation tokens are unique, random, and expire in 7 days
- Tokens are single-use (marked as accepted after use)
- Backend validates token ownership and email matching

### Email Verification
- New users created via invitation are auto-verified
- Email matches invitation recipient

### Access Control
- Only group owners can send invitations
- Only invitation recipient (by email) can accept
- Backend enforces authorization checks

## Troubleshooting

### Deep Links Not Working

**Issue:** App doesn't open when clicking link

**Solutions:**
1. Verify AndroidManifest.xml has correct intent filters
2. Check Android App Links verification:
   ```bash
   adb shell pm get-app-links com.togetherpay.mobile
   ```
3. Manually verify domain:
   ```bash
   adb shell pm verify-app-links --re-verify com.togetherpay.mobile
   ```
4. Test with adb directly:
   ```bash
   adb shell am start -a android.intent.action.VIEW \
     -d "https://togetherpay.app/accept-invitation/test"
   ```

### App Crashes on Deep Link

**Issue:** App crashes when opening invitation link

**Debug:**
1. Check logs: `flutter logs` or `adb logcat`
2. Verify token is valid and not expired
3. Ensure API endpoint is accessible
4. Check for Dio errors in debug output

### Invitation Shows as Invalid

**Issue:** Valid invitation shows "Invalid Invitation" error

**Check:**
1. Token hasn't expired (7 days from creation)
2. Token hasn't been accepted already
3. API endpoint returns 200 status
4. Network connectivity is working

### Can't Accept Invitation

**Issue:** Accept button doesn't work or shows error

**Debug:**
1. Check if user email matches invitation email
2. Verify user is authenticated (if using accept endpoint)
3. Check API response for validation errors
4. Ensure group still exists

## Future Enhancements

### Recommended Improvements

1. **Push Notification Deep Links**
   - Link notifications directly to payment requests
   - Handle app state (foreground/background/terminated)

2. **Member Dashboard**
   - Dedicated view for members (non-owners)
   - Show only groups where they're members
   - Hide owner-only features

3. **Invitation History**
   - Show pending invitations for user
   - Ability to decline/accept from list
   - Notification when new invitation received

4. **Resend Invitation**
   - Owner can resend invitation emails
   - Extends expiration date
   - Useful for spam folder issues

5. **Multiple Payment Methods**
   - Allow members to add both Venmo and Cash App
   - Let owner choose preferred method per request

## APK Location

The built APK with invitation feature is located at:
```
/Users/amohamed/Herd/togetherpay/mobile/build/app/outputs/flutter-apk/app-debug.apk
```

**Size:** ~47 MB (debug build)

To install when device is connected:
```bash
flutter install
```

Or manually:
```bash
adb install -r build/app/outputs/flutter-apk/app-debug.apk
```

## Summary

✅ **Complete invitation flow implemented**
✅ **Deep linking configured for Android**
✅ **Auto-registration for new members**
✅ **Beautiful UI with invitation details**
✅ **API integration with backend**
✅ **APK built and ready to test**

The invitation system is now fully functional and ready for testing. Members can be invited via email, click the link, and join groups seamlessly through the mobile app!
