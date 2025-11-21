# Quick Test Guide: Invitation Feature

## Prerequisites

- Mobile app installed on Android device
- Backend running at https://www.togetherpay.app/api/v1
- Test user account (owner)

## Step-by-Step Test

### 1. Create an Invitation (Backend)

From your Laravel backend or API client:

```bash
# As group owner, create invitation
POST /api/v1/groups/{groupId}/invitations
{
  "email": "test-member@example.com",
  "name": "Test Member",
  "share_count": 1,
  "venmo_username": "testuser",
  "cashapp_cashtag": "testuser"
}
```

**Response will include:**
```json
{
  "invitation_url": "https://togetherpay.app/accept-invitation/{token}"
}
```

Copy the token from the URL.

### 2. Open Invitation on Device

#### Option A: Using ADB (Easiest)

```bash
# Replace {token} with actual invitation token
adb shell am start -a android.intent.action.VIEW \
  -d "https://togetherpay.app/accept-invitation/{token}"
```

#### Option B: Browser on Device

1. Open Chrome on Android
2. Type: `https://togetherpay.app/accept-invitation/{token}`
3. Chrome will prompt: "Open with TogetherPay"
4. Tap "Open"

#### Option C: Actual Email

1. Send invitation through backend (it emails the link)
2. Open email on device
3. Tap the invitation link
4. App opens automatically

### 3. Test New User Flow

**If app is logged out:**

1. ✅ Invitation screen appears
2. ✅ Shows group name and inviter
3. ✅ Shows your member details (name, share, payment methods)
4. ✅ Tap "Create Account & Join"
5. ✅ Signup form appears with pre-filled email and name
6. ✅ Enter password (min 8 chars)
7. ✅ Confirm password
8. ✅ Optionally update payment methods
9. ✅ Tap "Create Account & Join Group"
10. ✅ App auto-logs in and shows home screen
11. ✅ Navigate to groups and verify you're in the group

### 4. Test Existing User Flow

**If already logged in:**

1. ✅ Invitation screen appears
2. ✅ Shows "Accept Invitation" button
3. ✅ Tap "Accept Invitation"
4. ✅ Success message appears
5. ✅ Redirects to group details
6. ✅ You appear as a member in the group

### 5. Test Edge Cases

#### Expired Invitation
```bash
# Use an old token or wait 7 days
# Should show: "This invitation has expired"
```

#### Already Accepted
```bash
# Use same token twice
# Should show: "This invitation has already been accepted"
```

#### Invalid Token
```bash
# Use random token
adb shell am start -a android.intent.action.VIEW \
  -d "https://togetherpay.app/accept-invitation/invalid-token-here"
# Should show: "Invalid Invitation" error
```

### 6. Verify Member Access

After accepting invitation:

1. ✅ Open app
2. ✅ Go to Groups tab
3. ✅ Verify the group appears in your list
4. ✅ Tap the group
5. ✅ Verify you can see group details
6. ✅ Verify you appear in the members list
7. ✅ Check that you can view payment requests

### 7. Test Deep Link When App is Closed

1. Force close TogetherPay app (swipe away from recent apps)
2. Use adb or browser to open invitation link
3. ✅ App launches and shows invitation screen directly

## Expected Behavior

### Invitation Screen Components

- **Icon:** Large circular icon with mail symbol
- **Title:** "You're Invited!"
- **Subtitle:** "{Inviter name} has invited you to join"
- **Group Card:** Shows group name with group icon
- **Details Card:** Shows member info:
  - Name
  - Share count
  - Venmo username (if provided)
  - Cash App tag (if provided)
- **Expiration:** Orange banner showing "Expires in X days/hours"
- **Actions:**
  - If logged in: "Accept Invitation" + "Decline" buttons
  - If not logged in: "Create Account & Join" + "Already have an account? Login"

### Success States

**New User Signup:**
- ✅ Account created
- ✅ Auto-logged in
- ✅ Added to group
- ✅ Can see group in list

**Existing User Acceptance:**
- ✅ Added to group
- ✅ Can view group details
- ✅ Can see payment requests

## Testing Checklist

- [ ] Deep link opens app from browser
- [ ] Deep link opens app from email
- [ ] Deep link works when app is closed
- [ ] Deep link works when app is open
- [ ] Invitation details display correctly
- [ ] New user can create account
- [ ] New user is auto-logged in after signup
- [ ] Existing user can accept invitation
- [ ] User is added to group after acceptance
- [ ] Group appears in user's groups list
- [ ] Expired invitations show correct error
- [ ] Invalid tokens show correct error
- [ ] Already accepted invitations show message
- [ ] Decline works correctly
- [ ] Member can see only their groups (not all groups)

## Common Issues & Fixes

### "Failed to load invitation"

**Cause:** API not accessible or token invalid

**Fix:**
- Check network connectivity
- Verify token is correct
- Check backend logs for errors

### App doesn't open from link

**Cause:** Deep linking not configured properly

**Fix:**
```bash
# Verify app can handle deep links
adb shell dumpsys package com.togetherpay.mobile | grep -A 5 "togetherpay.app"

# Should show intent filters for togetherpay.app
```

### "Email doesn't match"

**Cause:** Logged in with different email than invitation

**Fix:**
- Logout and create new account with invitation email
- Or have owner resend invitation to logged-in email

## Test Data Examples

### Test Invitation Data

```json
{
  "email": "member1@test.com",
  "name": "Alice Johnson",
  "share_count": 2,
  "venmo_username": "alice-j",
  "cashapp_cashtag": "alicejohnson"
}
```

### Test User Credentials

```
Email: member1@test.com
Password: Test1234!
```

## Success Criteria

The invitation feature is working correctly if:

✅ Members receive email with clickable link
✅ Link opens mobile app automatically
✅ Invitation details are clearly displayed
✅ New users can create account through invitation
✅ Existing users can accept with one tap
✅ Members are successfully added to group
✅ Members can see group and payment requests
✅ Invalid/expired invitations show appropriate errors

## Next Steps

After testing invitations:

1. **Test payment request notifications** with deep links
2. **Verify member-only access** (can't see other groups)
3. **Test complete payment flow** (request → notification → pay → confirm)
4. **Build release APK** for Play Store testing
5. **Setup closed testing** with real testers

## APK Location

Debug APK with invitation feature:
```
/Users/amohamed/Herd/togetherpay/mobile/build/app/outputs/flutter-apk/app-debug.apk
```

Install:
```bash
adb install -r build/app/outputs/flutter-apk/app-debug.apk
```

## Questions?

Check [INVITATION_FEATURE.md](INVITATION_FEATURE.md) for complete documentation.
