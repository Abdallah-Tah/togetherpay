# TogetherPay - Device Testing Steps

## ✅ Installation Complete!

Your TogetherPay app has been installed on device **A059** (Android 15).

**API Endpoint:** `https://www.togetherpay.app/api/v1`

---

## 📱 Testing Steps

### 1. Launch the App

Look for the **TogetherPay** app icon on your Android device and tap to open it.

### 2. Test Registration Flow

**Create a new account:**

1. On the login screen, tap **"Register"** or **"Sign Up"**
2. Fill in:
   - **Name:** Your Full Name
   - **Email:** your-email@example.com
   - **Password:** Create a strong password (min 8 chars)
3. Tap **"Register"** or **"Sign Up"**
4. Should show success and redirect to onboarding

**Expected Result:** ✓ Account created successfully, redirected to onboarding

### 3. Test Onboarding Flow

**Complete the onboarding:**

1. **Welcome Screen:** Tap "Get Started" or "Next"
2. **Billing Mode Selection:**
   - Choose **"Manual"** (for testing without Square)
   - Or **"Automatic"** (if you have Square configured)
3. **Payment Methods:**
   - Add Cash App handle (e.g., `$yourcashtag`)
   - Add Venmo handle (e.g., `@yourvenmo`)
4. Tap **"Complete"** or **"Finish"**

**Expected Result:** ✓ Onboarding completed, redirected to main app

### 4. Test Groups Creation

**Create your first group:**

1. Tap the **"Groups"** tab (bottom navigation)
2. Tap the **"+"** or **"Create Group"** button
3. Fill in:
   - **Group Name:** "Test Family Group"
   - **Billing Amount:** $100
   - **Billing Cycle:** Monthly
4. **Add Members:**
   - Add yourself
   - Add a test member (or friend)
   - Set share percentages (e.g., 50% each)
5. Tap **"Create"** or **"Save"**

**Expected Result:** ✓ Group created successfully, appears in groups list

### 5. Test Groups List

1. View the groups list (should show your new group)
2. Pull down to refresh
3. Tap on a group to view details

**Expected Result:** ✓ Groups display correctly, refresh works, details load

### 6. Test Payment Requests

1. Tap the **"Payments"** tab (bottom navigation)
2. View payment requests list
3. If you have pending requests:
   - Tap on a request
   - Try **"Mark as Paid"** (if you're a member)
   - Or **"Confirm Payment"** (if you're the owner)

**Expected Result:** ✓ Payment requests display, actions work

### 7. Test Profile & Settings

1. Tap the **"Profile"** tab (bottom navigation)
2. Verify your name and email display correctly
3. Test navigation to:
   - **Notifications** settings
   - **Payment Methods**
   - **Security** (change password)
4. Try **"Refresh"** button
5. Test **"Logout"**

**Expected Result:** ✓ Profile loads, settings accessible, logout works

### 8. Test Login Again

After logging out:

1. Enter your email and password
2. Tap **"Login"**
3. Should return to main app (not onboarding again)

**Expected Result:** ✓ Login successful, bypasses onboarding

### 9. Test Network Conditions

**WiFi Test:**
- Use app on WiFi
- Create a group, view requests
- Should work normally

**Mobile Data Test:**
- Switch to mobile data (4G/5G)
- Try the same actions
- Should work normally

**Offline Test:**
- Enable airplane mode
- Try to load data
- Should show appropriate offline message

**Expected Result:** ✓ Works on WiFi and mobile data, handles offline gracefully

### 10. Test Performance

**Check for:**
- [ ] App starts quickly (< 3 seconds)
- [ ] Screens load fast
- [ ] Scrolling is smooth
- [ ] No crashes or freezes
- [ ] Images load properly
- [ ] Animations are smooth

**Expected Result:** ✓ App performs well, no lag or crashes

---

## 🐛 Common Issues & Solutions

### "Unable to connect to server"

**Check:**
1. Is your device online? (Open browser, visit google.com)
2. Is the API running? Visit: https://www.togetherpay.app/api/health
3. Try both WiFi and mobile data

**Fix:**
```bash
# Test API from your computer
curl https://www.togetherpay.app/api/health

# Should return: {"status":"ok",...}
```

### "Registration failed" or validation errors

**Check:**
- Email is valid format
- Password is at least 8 characters
- All fields are filled

### App crashes on startup

**View logs:**
```bash
# From your Mac, with device connected
flutter logs
```

Look for error messages in red.

### Deep links don't work (Venmo/Cash App)

**Requirements:**
- Venmo/Cash App apps must be installed
- Test on real device (not emulator)
- Ensure handles are correct format:
  - Cash App: `$cashtag` (with $)
  - Venmo: `@username` (with @)

---

## 📊 View Real-time Logs

While the app is running on your device, you can view logs from your Mac:

```bash
flutter logs
```

Or more detailed logs:

```bash
flutter logs --verbose
```

To filter for errors only:

```bash
flutter logs | grep -i error
```

---

## 🔄 Reinstalling the App

If you need to reinstall (e.g., after code changes):

```bash
# From the mobile directory
flutter install
```

Or build and install fresh:

```bash
flutter build apk --debug && flutter install
```

---

## 🚀 Building for Production

When you're ready for a production build:

### 1. Create Release Keystore (One-time setup)

```bash
cd android
keytool -genkey -v -keystore ~/togetherpay-release-key.jks \
  -keyalg RSA -keysize 2048 -validity 10000 -alias togetherpay
```

### 2. Create key.properties

Create `android/key.properties`:

```properties
storePassword=your-keystore-password
keyPassword=your-key-password
keyAlias=togetherpay
storeFile=/Users/amohamed/togetherpay-release-key.jks
```

### 3. Build Release APK

```bash
flutter build apk --release
```

### 4. Build App Bundle (for Play Store)

```bash
flutter build appbundle --release
```

---

## ✅ Testing Checklist

Use this checklist as you test:

- [ ] App installs successfully
- [ ] App icon displays correctly
- [ ] Registration works
- [ ] Login works
- [ ] Onboarding completes
- [ ] Can create groups
- [ ] Groups list displays
- [ ] Payment requests work
- [ ] Profile displays correctly
- [ ] Logout works
- [ ] Login after logout works
- [ ] Works on WiFi
- [ ] Works on mobile data
- [ ] Handles offline gracefully
- [ ] No crashes
- [ ] Performance is good
- [ ] Scrolling is smooth
- [ ] Images load properly

---

## 📝 Taking Notes

As you test, note any issues:

**Issue Template:**
```
Issue: [Brief description]
Steps to reproduce:
1. [Step 1]
2. [Step 2]
3. [Step 3]
Expected: [What should happen]
Actual: [What actually happened]
Device: A059 (Android 15)
```

---

## 🎉 Next Steps After Testing

Once basic testing is complete:

1. **Internal Testing:**
   - Build signed release APK
   - Upload to Play Console Internal Testing
   - Invite friends/family to test

2. **Comprehensive Testing:**
   - Review [MOBILE_TESTING_GUIDE.md](MOBILE_TESTING_GUIDE.md)
   - Test all features thoroughly
   - Test on multiple devices

3. **Production Release:**
   - Build app bundle
   - Upload to Play Store
   - Submit for review

---

**App Installed:** ✅
**Device:** A059 (Android 15)
**API:** https://www.togetherpay.app/api/v1
**Build Type:** Debug

Happy Testing! 🚀
