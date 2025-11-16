# Mobile App Testing - Quick Start Guide

## 🚀 Quick Testing Steps

### 1. Test Your Production API

```bash
# Test basic connectivity
curl https://www.togetherpay.app/api/health

# Should return: {"status":"ok","timestamp":"..."}
```

### 2. Build Android Release APK

```bash
cd mobile
flutter build apk --release
```

**Output:** `mobile/build/app/outputs/flutter-apk/app-release.apk`

### 3. Install on Android Device

**Option A - USB:**
```bash
# Enable USB debugging on your Android phone
# Connect via USB cable
adb devices  # Verify device connected
adb install build/app/outputs/flutter-apk/app-release.apk
```

**Option B - Manual:**
1. Email the APK to yourself
2. Open on your Android phone
3. Enable "Install from Unknown Sources"
4. Install the APK

### 4. Test Basic Flow

1. **Open TogetherPay app**
2. **Register new account:**
   - Email: your-email@example.com
   - Password: TestPassword123!
   - Name: Your Name

3. **Complete onboarding:**
   - Choose billing mode (Manual or Auto)
   - Add payment handles (Cash App: $yourcashtag, Venmo: @yourvenmo)

4. **Create a test group:**
   - Name: "Test Group"
   - Add yourself as a member
   - Set monthly billing

5. **Check payment requests:**
   - Navigate to Payments tab
   - Should see payment requests list

6. **Test logout/login:**
   - Go to Profile → Logout
   - Login again with same credentials

### 5. Common Issues & Fixes

**"Can't connect to server"**
```bash
# Verify API is accessible
curl https://www.togetherpay.app/api/v1/auth/login

# Check your phone's network (try both WiFi and mobile data)
```

**"Installation failed"**
```bash
# Uninstall existing version first
adb uninstall com.togetherpay.mobile

# Then reinstall
adb install build/app/outputs/flutter-apk/app-release.apk
```

**App crashes on startup**
```bash
# Check logs
adb logcat | grep -i flutter

# Look for errors in the output
```

### 6. Build for Play Store (When Ready)

```bash
# Create signed app bundle
flutter build appbundle --release

# Output: mobile/build/app/outputs/bundle/release/app-release.aab
```

Upload this `.aab` file to Google Play Console.

---

## 📱 iOS Testing (Requires macOS)

### 1. Build iOS Release

```bash
cd mobile
flutter build ios --release
```

### 2. Open in Xcode

```bash
open ios/Runner.xcworkspace
```

### 3. Archive & Export

1. In Xcode: Product → Archive
2. Once archived, click "Distribute App"
3. Choose "App Store Connect"
4. Upload to TestFlight

### 4. TestFlight Testing

1. Go to [App Store Connect](https://appstoreconnect.apple.com)
2. Navigate to TestFlight
3. Add internal/external testers
4. Testers install via TestFlight app

---

## ✅ Critical Test Checklist

**Authentication (5 min):**
- [ ] Register works
- [ ] Login works
- [ ] Logout works

**Groups (5 min):**
- [ ] Create group
- [ ] View groups list
- [ ] Add member
- [ ] Delete group

**Payment Requests (5 min):**
- [ ] View payment requests
- [ ] Mark as paid (member)
- [ ] Confirm payment (owner)

**Network (3 min):**
- [ ] Works on WiFi
- [ ] Works on mobile data
- [ ] Shows error when offline

**Performance (2 min):**
- [ ] App starts in < 3 seconds
- [ ] Scrolling is smooth
- [ ] No crashes

---

## 🔧 Configuration Files

### API Endpoint

File: `mobile/lib/env.dart`
```dart
static const apiBase = String.fromEnvironment(
  'API_BASE',
  defaultValue: 'https://www.togetherpay.app/api/v1',
);
```

To change API during development:
```bash
flutter run --dart-define=API_BASE=http://localhost:8000/api/v1
```

### App Version

File: `mobile/pubspec.yaml`
```yaml
version: 1.0.0+1  # Format: major.minor.patch+buildNumber
```

Increment before each release!

---

## 📊 Monitoring Production App

### Check Logs (Android)

```bash
# Real-time logs
adb logcat -s flutter

# Filter for errors
adb logcat | grep -E 'ERROR|Exception|Fatal'
```

### Check Logs (iOS)

In Xcode:
1. Window → Devices and Simulators
2. Select your device
3. View Device Logs

---

## 🆘 Getting Help

**API not responding?**
- Check [https://www.togetherpay.app/api/health](https://www.togetherpay.app/api/health)
- Verify backend is running
- Check server logs

**App crashes?**
- Check `adb logcat` for errors
- Verify Flutter version: `flutter doctor`
- Clean rebuild: `flutter clean && flutter build apk`

**Build failures?**
- Update dependencies: `flutter pub get`
- Clear build cache: `flutter clean`
- Check Java/Gradle versions

---

## 📦 File Locations

| File | Purpose | Location |
|------|---------|----------|
| APK (unsigned) | Debug testing | `build/app/outputs/flutter-apk/app-debug.apk` |
| APK (signed) | Release testing | `build/app/outputs/flutter-apk/app-release.apk` |
| AAB (Play Store) | Store submission | `build/app/outputs/bundle/release/app-release.aab` |
| Keystore | Signing key | `~/togetherpay-release-key.jks` |

---

## 🎯 Next Steps

1. ✅ Complete this quick test
2. 📋 Review [MOBILE_TESTING_GUIDE.md](MOBILE_TESTING_GUIDE.md) for comprehensive testing
3. 🚀 Upload to Play Store Internal Testing
4. 👥 Invite testers
5. 🎉 Collect feedback and iterate

**Estimated Time:**
- Quick testing: **30 minutes**
- Comprehensive testing: **2-3 hours**
- Internal testing setup: **1 hour**

Good luck! 🚀
