# Quick Start: Testing Invitations

## 🚀 Fast Track (5 Minutes)

### 1. Install Latest APK

```bash
# Connect your Android device via USB
# Build is already complete at:
# /Users/amohamed/Herd/togetherpay/mobile/build/app/outputs/flutter-apk/app-debug.apk

# Install when device connected:
flutter install
```

### 2. Create Test Invitation

**Option A: Using curl**

```bash
# Login as owner
TOKEN=$(curl -X POST https://www.togetherpay.app/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"your-email@example.com","password":"your-password"}' \
  | jq -r '.token')

# Create invitation (replace GROUP_ID)
curl -X POST https://www.togetherpay.app/api/v1/groups/GROUP_ID/invitations \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{
    "email": "test@example.com",
    "name": "Test User",
    "share_count": 1
  }' | jq -r '.invitation.invitation_url'
```

Copy the token from the URL output.

**Option B: Using web interface**

1. Login to https://www.togetherpay.app
2. Go to your group
3. Click "Add Member" or "Invite"
4. Fill in member details
5. Copy invitation token from success message

### 3. Open on Device

**Fastest method (ADB):**

```bash
# Replace {TOKEN} with actual token
adb shell am start -a android.intent.action.VIEW \
  -d "https://togetherpay.app/accept-invitation/{TOKEN}"
```

**Alternative (Browser on device):**

1. Open Chrome
2. Type: `https://togetherpay.app/accept-invitation/{TOKEN}`
3. Tap enter
4. Select "TogetherPay" app

### 4. Test Flow

#### New User Path
1. ✅ Invitation screen appears
2. ✅ Tap "Create Account & Join"
3. ✅ Enter password (min 8 chars)
4. ✅ Tap "Create Account & Join Group"
5. ✅ Auto-logs in and shows groups

#### Existing User Path
1. ✅ Invitation screen appears
2. ✅ Tap "Accept Invitation"
3. ✅ Success! Navigate to group

## 📱 What You Should See

### Invitation Screen

```
┌─────────────────────────┐
│   📧 (Mail Icon)        │
│                         │
│  You're Invited!        │
│  John invited you to    │
│  join                   │
│                         │
│  ┌────────────────────┐ │
│  │  👥 Family Netflix │ │
│  └────────────────────┘ │
│                         │
│  Your Details:          │
│  • Name: Test User      │
│  • Share: 1 share(s)    │
│  • Venmo: @testuser     │
│                         │
│  ⏰ Expires in 7 days   │
│                         │
│  [Accept Invitation]    │
│  [Decline]              │
└─────────────────────────┘
```

## 🔍 Quick Checks

### Is Deep Linking Working?

```bash
# Test if app handles the domain
adb shell pm get-app-links com.togetherpay.mobile

# Should show:
# com.togetherpay.mobile:
#   togetherpay.app: verified
```

### View App Logs

```bash
# Real-time logs
flutter logs

# Or Android logs
adb logcat | grep -i "together\|invitation\|deep"
```

### Test Invalid Token

```bash
adb shell am start -a android.intent.action.VIEW \
  -d "https://togetherpay.app/accept-invitation/invalid-token"

# Should show "Invalid Invitation" error
```

## 🐛 Quick Fixes

### App doesn't open from link

```bash
# Reinstall APK
flutter install

# Verify domain associations
adb shell pm set-app-links --package com.togetherpay.mobile 1 togetherpay.app
```

### "Failed to load invitation"

- Check internet connection
- Verify token is valid (not expired)
- Check backend is running: `curl https://www.togetherpay.app/api/v1`

### App crashes on deep link

```bash
# Check crash logs
adb logcat -c && adb logcat | grep -E "FATAL|AndroidRuntime"
```

## 📋 Complete Testing Checklist

Quick checklist for full testing:

```
[ ] Build APK: flutter build apk --debug
[ ] Install: flutter install
[ ] Create invitation via API/web
[ ] Copy invitation token
[ ] Test adb deep link
[ ] Verify invitation details show
[ ] Test new user signup
[ ] Verify auto-login works
[ ] Verify user added to group
[ ] Test existing user acceptance
[ ] Verify member in group list
[ ] Test expired invitation
[ ] Test invalid token
[ ] Test decline action
```

## 📚 Documentation

- **Full docs:** [INVITATION_FEATURE.md](INVITATION_FEATURE.md)
- **Testing guide:** [TEST_INVITATION.md](TEST_INVITATION.md)
- **Summary:** [INVITATION_IMPLEMENTATION_SUMMARY.md](INVITATION_IMPLEMENTATION_SUMMARY.md)

## 🎯 Key Endpoints

```
Backend API base: https://www.togetherpay.app/api/v1

POST /auth/login
POST /groups/{id}/invitations
GET  /invitations/{token}
POST /invitations/{token}/accept
POST /invitations/{token}/accept-magic
```

## 💡 Pro Tips

1. **Test with real email:** Send actual invitation to your email and click link on device
2. **Use incognito:** Test new user flow by logging out or using app in fresh state
3. **Check groups list:** After accepting, verify group appears in list
4. **Test edge cases:** Try expired tokens, already-accepted invitations
5. **Monitor logs:** Keep `flutter logs` running to catch errors

## ⚡ One-Liner Tests

```bash
# Build + Install + Test in one go
flutter build apk --debug && flutter install && \
adb shell am start -a android.intent.action.VIEW \
  -d "https://togetherpay.app/accept-invitation/YOUR_TOKEN_HERE"
```

## 🔗 Quick Links

- APK: `/Users/amohamed/Herd/togetherpay/mobile/build/app/outputs/flutter-apk/app-debug.apk`
- Backend: `https://www.togetherpay.app/api/v1`
- Mobile dir: `/Users/amohamed/Herd/togetherpay/mobile`

## ✅ Success Criteria

Your invitation system is working if:

✅ Email link opens app automatically
✅ Invitation details display correctly
✅ New users can create accounts
✅ Existing users can accept with one tap
✅ Members appear in group after acceptance

---

**Status:** ✅ Ready to test
**APK Built:** Yes (47 MB debug)
**Next:** Connect device and test!
