# Production Fixes - Quick Action List

**⚠️ CRITICAL: Complete these before ANY production release**

---

## 🔴 CRITICAL FIXES (BLOCKING RELEASE)

### 1. Fix DEBUG Default (5 minutes)

**File:** `lib/env.dart`

**Current:**
```dart
static const debug = bool.fromEnvironment('DEBUG', defaultValue: true);
```

**Change to:**
```dart
static const debug = bool.fromEnvironment('DEBUG', defaultValue: false);
```

**Why:** Currently logs sensitive data (tokens, emails, API responses) in production builds.

---

### 2. Update Build Scripts (10 minutes)

**Files:** `build_android_release.sh` and `build_ios_release.sh`

Add these lines at the top of both scripts:

```bash
# Enforce production settings
export API_BASE="https://api.togetherpay.app/api/v1"
export DEBUG="false"
```

Then update the `flutter build` commands to include:
```bash
--dart-define=API_BASE=$API_BASE \
--dart-define=DEBUG=$DEBUG
```

**Why:** Ensures production builds always use production API and disable debug mode.

---

### 3. Generate App Icons (2 hours)

**Steps:**

1. Create app icon image (1024x1024 PNG)
2. Add to dependencies:
```bash
flutter pub add --dev flutter_launcher_icons
```

3. Configure in `pubspec.yaml`:
```yaml
flutter_launcher_icons:
  android: "launcher_icon"
  ios: true
  image_path: "assets/icon/app_icon.png"
  min_sdk_android: 21
  adaptive_icon_background: "#14B8A6"
  adaptive_icon_foreground: "assets/icon/app_icon_foreground.png"
```

4. Create `assets/icon/` folder and add your icon
5. Generate:
```bash
flutter pub run flutter_launcher_icons
```

**Why:** App stores require app icons. Cannot submit without them.

---

### 4. Generate Android Keystore (30 minutes)

**Steps:**

1. Generate keystore:
```bash
cd android/app
keytool -genkey -v -keystore upload-keystore.jks \
  -keyalg RSA -keysize 2048 -validity 10000 -alias upload
```

2. Create `android/key.properties`:
```properties
storePassword=YOUR_PASSWORD_FROM_STEP_1
keyPassword=YOUR_PASSWORD_FROM_STEP_1
keyAlias=upload
storeFile=upload-keystore.jks
```

3. **CRITICAL:** Add to `.gitignore`:
```
# Signing files
*.jks
*.keystore
key.properties
```

4. **CRITICAL:** Back up keystore and password to secure location (password manager)

**Why:** Cannot build release APK/AAB without keystore. Cannot publish to Google Play.

---

### 5. Configure iOS Signing (30 minutes)

**Steps:**

1. Run entitlements script:
```bash
cd mobile
./configure_entitlements.sh
```

2. Open in Xcode:
```bash
cd ios
open Runner.xcworkspace
```

3. Select Runner project → Runner target → "Signing & Capabilities"
4. Select your Team
5. Enable "Automatically manage signing"
6. Verify Bundle ID: `com.togetherpay.togetherpay`
7. Verify capabilities:
   - Push Notifications
   - Associated Domains
   - Background Modes → Remote notifications

**Why:** Cannot build release IPA without proper signing. Cannot submit to App Store.

---

### 6. Deploy Deep Link Verification Files (1 hour)

**iOS - Create `apple-app-site-association`:**

Deploy to: `https://tpay.app/.well-known/apple-app-site-association`

```json
{
  "applinks": {
    "apps": [],
    "details": [{
      "appID": "TEAM_ID.com.togetherpay.togetherpay",
      "paths": ["/p/*", "/pay/*"]
    }]
  }
}
```

Replace `TEAM_ID` with your Apple Developer Team ID.

**Android - Create `assetlinks.json`:**

Deploy to: `https://tpay.app/.well-known/assetlinks.json`

```json
[{
  "relation": ["delegate_permission/common.handle_all_urls"],
  "target": {
    "namespace": "android_app",
    "package_name": "com.togetherpay.togetherpay",
    "sha256_cert_fingerprints": ["YOUR_SHA256_FINGERPRINT"]
  }
}]
```

Get SHA256 fingerprint:
```bash
keytool -list -v -keystore android/app/upload-keystore.jks -alias upload
```

**Both files must:**
- Be served over HTTPS
- Have `Content-Type: application/json`
- Be publicly accessible (no authentication)

**Why:** Deep links won't work without these files. Users can't open payment requests.

---

## ⚠️ HIGH PRIORITY FIXES (Should Fix Before Release)

### 7. Add Crash Reporting (2 hours)

**Option A: Sentry (Recommended)**

1. Create account at sentry.io
2. Add dependency:
```bash
flutter pub add sentry_flutter
```

3. Update `lib/main.dart`:
```dart
import 'package:sentry_flutter/sentry_flutter.dart';

Future<void> main() async {
  await SentryFlutter.init(
    (options) {
      options.dsn = 'https://YOUR_SENTRY_DSN';
      options.environment = Env.debug ? 'development' : 'production';
      options.tracesSampleRate = 1.0;
    },
    appRunner: () => runApp(const TogetherPayApp()),
  );
}
```

**Why:** Need to track crashes in production. Can't fix bugs you don't know about.

---

### 8. Implement Forgot Password (1 day)

**Backend (Laravel):**

Add routes in `routes/api.php`:
```php
Route::post('/auth/forgot-password', [PasswordController::class, 'sendResetLink']);
Route::post('/auth/reset-password', [PasswordController::class, 'reset']);
```

**Mobile:**

Create screens:
- `lib/screens/auth/forgot_password_screen.dart`
- `lib/screens/auth/reset_password_screen.dart`

Update login screen to link to forgot password.

**Why:** Users need a way to recover accounts. Will reduce support burden.

---

### 9. Prepare App Store Assets (1 day)

**iOS Screenshots Needed:**
- iPhone 6.7" (iPhone 15 Pro Max) - at least 3 screenshots
- iPhone 6.5" (iPhone 14 Plus) - at least 3 screenshots
- iPhone 5.5" (iPhone 8 Plus) - at least 3 screenshots
- iPad Pro 12.9" - at least 2 screenshots

**Android Screenshots Needed:**
- Phone - at least 2 screenshots
- 7" tablet - at least 2 screenshots
- 10" tablet - at least 2 screenshots
- Feature graphic (1024 x 500 PNG)
- App icon (512 x 512 PNG)

**Descriptions:**
- Short description (80 chars)
- Full description (4000 chars)
- See `PRODUCTION_READINESS_AUDIT.md` for templates

**Legal:**
- Privacy Policy URL (required)
- Terms of Service URL (recommended)

**Why:** Cannot submit to app stores without these assets.

---

### 10. Physical Device Testing (1 day)

**Test on real devices:**

**iOS Testing:**
- [ ] Test on iPhone (iOS 15+)
- [ ] Test on iPad (iPadOS 15+)
- [ ] Deep link from Safari → App opens
- [ ] Payment request → Cash App launches
- [ ] Payment request → Venmo launches
- [ ] Auto-dialog appears after returning

**Android Testing:**
- [ ] Test on Android phone (Android 10+)
- [ ] Test on Android tablet (Android 10+)
- [ ] Deep link from Chrome → App opens
- [ ] Payment request → Cash App launches
- [ ] Payment request → Venmo launches
- [ ] Auto-dialog appears after returning

**Both Platforms:**
- [ ] Register new account
- [ ] Login with existing account
- [ ] View groups list
- [ ] View group details
- [ ] View payment requests
- [ ] Filter by status (Overdue, Pending, Paid)
- [ ] Mark payment as paid
- [ ] Logout
- [ ] Test on slow network (3G simulation)

**Why:** Critical to test on physical devices. Simulators/emulators don't test everything.

---

## ℹ️ OPTIONAL IMPROVEMENTS (Nice to Have)

### 11. Add Analytics (4 hours)

**Option: Mixpanel or Firebase Analytics**

Track key events:
- User registration
- Login/logout
- Payment request viewed
- Payment marked as paid
- Group created
- Wallet app launched

**Why:** Measure user engagement and feature usage.

---

### 12. Implement Certificate Pinning (4 hours)

Add SSL certificate pinning for additional security.

**Why:** Prevents man-in-the-middle attacks.

---

### 13. Add Biometric Authentication (1 day)

Allow users to use Face ID/Touch ID/Fingerprint instead of password.

**Why:** Better UX, faster login.

---

## 🚀 Build & Release Process

Once all critical fixes are complete:

### Android Release

1. **Build:**
```bash
./build_android_release.sh
```

2. **Upload to Play Console:**
   - Go to Google Play Console
   - Create new release
   - Upload `build/app/outputs/bundle/release/app-release.aab`
   - Add release notes
   - Start staged rollout (5% → 20% → 50% → 100%)

### iOS Release

1. **Build:**
```bash
./build_ios_release.sh
open ios/Runner.xcworkspace
```

2. **Archive:**
   - Product → Archive
   - Distribute App → App Store Connect

3. **Submit:**
   - Go to App Store Connect
   - Fill in app information
   - Add screenshots
   - Submit for review
   - Enable phased release (1% → 100% over 7 days)

---

## 📋 Final Checklist

### Before Submitting to App Stores

**Configuration:**
- [ ] DEBUG defaults to false
- [ ] Build scripts enforce production API
- [ ] App icons generated
- [ ] Splash screen configured

**Signing:**
- [ ] Android keystore generated and backed up
- [ ] Android key.properties configured
- [ ] iOS signing configured in Xcode
- [ ] iOS entitlements linked

**Deep Links:**
- [ ] apple-app-site-association deployed
- [ ] assetlinks.json deployed
- [ ] Both files accessible via HTTPS
- [ ] Tested on physical devices

**Testing:**
- [ ] All features tested on iOS device
- [ ] All features tested on Android device
- [ ] Deep links work end-to-end
- [ ] Wallet integration works (Cash App, Venmo)
- [ ] Tested on slow network

**App Store:**
- [ ] Screenshots prepared (all sizes)
- [ ] App descriptions written
- [ ] Privacy policy URL configured
- [ ] Terms of service URL configured
- [ ] App Store Connect/Play Console app created

**Monitoring:**
- [ ] Crash reporting configured
- [ ] Analytics configured (optional)
- [ ] Alerts set up

---

## ⏱️ Estimated Timeline

**Critical Fixes:** 1 day
- Environment config: 30 minutes
- Build scripts: 30 minutes
- App icons: 2 hours
- Android keystore: 1 hour
- iOS signing: 1 hour
- Deep link files: 2 hours

**High Priority:** 2-3 days
- Crash reporting: 2 hours
- Forgot password: 1 day
- App store assets: 1 day
- Physical device testing: 1 day

**Total to Production:** 1 week

---

## 🆘 Need Help?

- **Full details:** See `PRODUCTION_READINESS_AUDIT.md`
- **Setup guides:** See `PRODUCTION_SETUP.md` and `BUILD_DEPLOY.md`
- **Testing:** See `TESTING_CHECKLIST.md`
- **Security:** See `../SECURITY.md`

---

## 📞 Support

**Technical Issues:** Check existing documentation  
**Security Concerns:** security@togetherpay.app  
**Build Problems:** Verify all dependencies with `./verify_setup.sh`

---

**Last Updated:** 2025-01-XX  
**Next Review:** After completing critical fixes
