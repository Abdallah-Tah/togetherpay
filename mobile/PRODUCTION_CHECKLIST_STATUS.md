# Production Checklist - Quick Start

This is a quick reference for completing the mobile production checklist. For detailed instructions, see [PRODUCTION_SETUP.md](./PRODUCTION_SETUP.md).

## ✅ Already Completed

The following items are already configured in the repository:

### iOS
- ✅ Associated domains configured in `Info.plist`
- ✅ URL schemes configured (Venmo deep links)
- ✅ Background modes for push notifications
- ✅ Development entitlements file (`Runner.entitlements`)
- ✅ Production entitlements file (`Runner-Release.entitlements`)
- ✅ Firebase configuration file present (`GoogleService-Info.plist`)

### Android
- ✅ Firebase configuration file present (`google-services.json`)
- ✅ Google Services plugin configured
- ✅ Signing configuration in `build.gradle.kts`
- ✅ ProGuard rules created
- ✅ Deep link handling configured
- ✅ Notification permissions configured
- ✅ Query permissions for Cash App/Venmo

### Documentation
- ✅ Production setup guide (`PRODUCTION_SETUP.md`)
- ✅ Build & deploy guide (`BUILD_DEPLOY.md`)
- ✅ Testing checklist (`TESTING_CHECKLIST.md`)
- ✅ Build scripts created and executable

## 🔧 Setup Required

### 1. Verify Configuration

Run the verification script to ensure everything is configured correctly:

```bash
./verify_setup.sh
```

This will check all configuration files and dependencies.

### 2. iOS Setup (Requires macOS)

#### A. Configure Entitlements in Xcode

```bash
./configure_entitlements.sh
```

Or manually:
1. Open `ios/Runner.xcworkspace` in Xcode
2. Select Runner project → Runner target
3. Go to "Build Settings" tab
4. Search for "Code Signing Entitlements"
5. Set for Debug: `Runner/Runner.entitlements`
6. Set for Release: `Runner/Runner-Release.entitlements`

#### B. Configure Signing

1. In Xcode, select Runner target → "Signing & Capabilities"
2. Select your Team
3. Enable "Automatically manage signing"
4. Verify Bundle Identifier: `com.togetherpay.togetherpay`

#### C. Verify Capabilities

Ensure these capabilities are added:
- ✅ Push Notifications
- ✅ Associated Domains (with `applinks:tpay.app` and `applinks:togetherpay.app`)
- ✅ Background Modes → Remote notifications

### 3. Android Setup

#### A. Generate Keystore (First Time Only)

```bash
cd android/app
keytool -genkey -v -keystore upload-keystore.jks \
  -keyalg RSA -keysize 2048 -validity 10000 -alias upload
```

**Important**: Save the password securely! You'll need it for all future releases.

#### B. Configure Keystore

```bash
cd android
cp key.properties.example key.properties
# Edit key.properties with your keystore details
```

Example `key.properties`:
```properties
storePassword=YOUR_PASSWORD
keyPassword=YOUR_PASSWORD
keyAlias=upload
storeFile=upload-keystore.jks
```

**CRITICAL**: Never commit `key.properties` or `*.jks` files to git!

#### C. Get SHA-256 Fingerprint

```bash
keytool -list -v -keystore android/app/upload-keystore.jks -alias upload
```

Note the SHA-256 fingerprint - you'll need it for App Links configuration.

### 4. External Services Setup

#### A. App Store Connect (iOS)

See [PRODUCTION_SETUP.md - App Store Connect Setup](./PRODUCTION_SETUP.md#app-store-connect-setup)

1. Create app in App Store Connect
2. Generate APNs authentication key
3. Upload APNs key to Firebase Console
4. Configure app details and screenshots
5. Set up pricing and availability

#### B. Google Play Console (Android)

See [PRODUCTION_SETUP.md - Google Play Console Setup](./PRODUCTION_SETUP.md#google-play-console-setup)

1. Create app in Play Console
2. Configure store listing
3. Add SHA-256 fingerprint to Firebase
4. Set up content rating
5. Configure pricing and distribution

#### C. Configure App Links / Universal Links

**iOS - apple-app-site-association**

Create at `https://tpay.app/.well-known/apple-app-site-association`:
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

**Android - assetlinks.json**

Create at `https://tpay.app/.well-known/assetlinks.json`:
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

Both files must be:
- Served over HTTPS
- Served as `application/json`
- Accessible without authentication

## 🚀 Building for Production

### Set Production API URL

```bash
export API_BASE="https://api.togetherpay.app/api/v1"
```

### Build iOS

```bash
./build_ios_release.sh
```

Then in Xcode: Product → Archive → Distribute App → App Store Connect

### Build Android

```bash
./build_android_release.sh
```

Upload the generated `build/app/outputs/bundle/release/app-release.aab` to Play Console.

## ✅ Pre-Release Testing

Use the comprehensive testing checklist:

```bash
# View the checklist
cat TESTING_CHECKLIST.md

# Or open in your editor
open TESTING_CHECKLIST.md
```

### Critical Tests on Physical Devices

1. **Authentication Flow**
   - Register → Login → Logout
   - Token persistence

2. **Push Notifications**
   - Receive notification
   - Tap notification → Opens correct screen
   - Background and foreground handling

3. **Payment Flow**
   - View payment request
   - Launch wallet app (Cash App/Venmo)
   - Complete payment
   - Mark as paid

4. **Deep Links**
   - Universal links open app (not browser)
   - Navigate to correct screen
   - Load payment details

5. **Wallet Integration**
   - Cash App launches with prefilled amount
   - Venmo launches with prefilled amount
   - Fallback when app not installed

## 📊 Checklist Status

### iOS
- [x] Associated domains configured
- [x] Entitlements files created
- [x] Firebase config present
- [ ] Entitlements linked in Xcode project
- [ ] Signing configured in Xcode
- [ ] App Store Connect app created
- [ ] APNs certificate uploaded to Firebase
- [ ] Tested on physical device
- [ ] Deep links verified
- [ ] Push notifications verified

### Android
- [x] Firebase config present
- [x] Signing configuration added
- [x] ProGuard rules created
- [ ] Release keystore generated
- [ ] key.properties configured
- [ ] SHA-256 fingerprint obtained
- [ ] Play Console app created
- [ ] SHA-256 added to Firebase
- [ ] Digital Asset Links configured
- [ ] Tested on physical device
- [ ] Deep links verified
- [ ] Push notifications verified

### Both Platforms
- [x] Documentation complete
- [x] Build scripts created
- [x] Verification script created
- [ ] API base URL configured for production
- [ ] Apple-app-site-association file deployed
- [ ] Digital Asset Links file deployed
- [ ] End-to-end testing complete
- [ ] Ready for submission

## 📚 Documentation References

- **Full Setup**: [PRODUCTION_SETUP.md](./PRODUCTION_SETUP.md)
- **Build Commands**: [BUILD_DEPLOY.md](./BUILD_DEPLOY.md)
- **Testing**: [TESTING_CHECKLIST.md](./TESTING_CHECKLIST.md)
- **Main README**: [README.md](./README.md)

## 🆘 Common Issues

### iOS Build Fails

```bash
flutter clean
cd ios
rm -rf Pods Podfile.lock
pod install --repo-update
cd ..
flutter pub get
```

### Android Build Fails

```bash
flutter clean
cd android
./gradlew clean
cd ..
flutter pub get
```

### Signing Issues

**iOS**: Check Xcode → Runner target → Signing & Capabilities → Team selected

**Android**: Verify `android/key.properties` exists and paths are correct

### Push Notifications Not Working

**iOS**: 
- Verify APNs key uploaded to Firebase
- Check entitlements: `cat ios/Runner/Runner-Release.entitlements`
- Verify aps-environment is `production` for release builds

**Android**:
- Verify `google-services.json` has correct package name
- Check notification permission granted (Android 13+)

## 🎯 Next Steps

1. ✅ Run `./verify_setup.sh` to confirm configuration
2. 🔧 Complete iOS/Android setup steps above
3. 🏗️ Build for production using build scripts
4. ✅ Complete pre-release testing (TESTING_CHECKLIST.md)
5. 🚀 Submit to App Store and Play Store
6. 📊 Monitor crash reports and user feedback
7. 🔄 Plan phased rollout (iOS) or staged rollout (Android)

## 💡 Tips

- Test on multiple device models and OS versions
- Use phased/staged rollout to catch issues early
- Monitor crash reports closely in first 48 hours
- Keep development and production builds separate
- Never commit signing keys or passwords to git
- Back up keystores and passwords securely
- Test offline behavior thoroughly
- Verify all deep links work in production

---

**Need Help?** See detailed instructions in [PRODUCTION_SETUP.md](./PRODUCTION_SETUP.md) or check [troubleshooting section](./PRODUCTION_SETUP.md#troubleshooting-production-issues).
