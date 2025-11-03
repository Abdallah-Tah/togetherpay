# Quick Build & Deploy Reference

Quick commands for building and deploying TogetherPay mobile app.

## Prerequisites

Set production API URL:
```bash
export API_BASE="https://api.togetherpay.app/api/v1"
```

Or pass inline with each command using `--dart-define=API_BASE=$API_BASE`

---

## iOS

### Clean Build
```bash
flutter clean
cd ios
rm -rf Pods Podfile.lock
pod install --repo-update
cd ..
```

### Development Build
```bash
# Run on connected device
flutter run -d [device-id]

# Run on simulator
flutter run -d "iPhone 14 Pro"
```

### Release Build (Local Testing)
```bash
flutter build ios --release --dart-define=API_BASE=$API_BASE
open ios/Runner.xcworkspace
```
Then in Xcode: Product → Run (with Release configuration)

### App Store Build
```bash
flutter build ios --release --dart-define=API_BASE=$API_BASE
open ios/Runner.xcworkspace
```
Then in Xcode: Product → Archive → Distribute App → App Store Connect

### Verify Signing
```bash
cd ios
open Runner.xcworkspace
```
Check: Runner target → Signing & Capabilities → Team selected

---

## Android

### Clean Build
```bash
flutter clean
flutter pub get
cd android
./gradlew clean
cd ..
```

### Development Build
```bash
# Run on connected device
flutter run -d [device-id]

# Run on emulator
flutter run -d emulator-5554
```

### Release Build - App Bundle (Play Store)
```bash
# Configure signing first (see PRODUCTION_SETUP.md)
flutter build appbundle --release --dart-define=API_BASE=$API_BASE
```
Output: `build/app/outputs/bundle/release/app-release.aab`

### Release Build - APK (Direct Distribution)
```bash
flutter build apk --release --dart-define=API_BASE=$API_BASE
```
Output: `build/app/outputs/flutter-apk/app-release.apk`

### Split APKs by Architecture (Smaller Size)
```bash
flutter build apk --release --split-per-abi --dart-define=API_BASE=$API_BASE
```
Outputs:
- `app-armeabi-v7a-release.apk` (32-bit ARM)
- `app-arm64-v8a-release.apk` (64-bit ARM)
- `app-x86_64-release.apk` (Intel 64-bit)

### Verify Signing
```bash
# Check if signed with release keystore
jarsigner -verify -verbose -certs build/app/outputs/bundle/release/app-release.aab
```

---

## Testing

### Run Tests
```bash
flutter test
```

### Run Integration Tests
```bash
# iOS
flutter drive --target=test_driver/app.dart -d iPhone

# Android
flutter drive --target=test_driver/app.dart -d emulator-5554
```

### Analyze Code
```bash
flutter analyze
```

### Check Dependencies
```bash
flutter pub outdated
flutter pub upgrade --dry-run
```

---

## Firebase

### Test Push Notification
```bash
# Get device token
flutter run
# Look in console logs for: "FCM Token: [token]"

# Send test via curl
curl -X POST https://fcm.googleapis.com/fcm/send \
  -H "Authorization: key=YOUR_SERVER_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "to": "DEVICE_TOKEN",
    "notification": {
      "title": "Test Payment",
      "body": "Tap to test"
    },
    "data": {
      "pr_code": "PR_TEST",
      "deep_link": "https://cash.app/$TestUser/5.00"
    }
  }'
```

---

## Deployment

### iOS - Submit to App Store

1. **Build and Archive** (see above)

2. **Upload via Xcode Organizer**:
   - Select archive → Distribute App → App Store Connect → Upload

3. **Submit in App Store Connect**:
   - https://appstoreconnect.apple.com
   - Select your app → TestFlight or App Store tab
   - Complete all required metadata
   - Submit for review

4. **Monitor Status**:
   - Check email for status updates
   - Review times: 1-3 days typically

### Android - Submit to Play Store

1. **Build App Bundle** (see above)

2. **Upload to Play Console**:
   - https://play.google.com/console
   - Select your app → Release → Production
   - Create new release → Upload AAB
   - Add release notes

3. **Submit for Review**:
   - Complete all required sections:
     - Store listing
     - Content rating
     - Pricing & distribution
     - App content
   - Click "Send for review"

4. **Monitor Status**:
   - Review times: Hours to a few days
   - Check Android vitals for crashes

### Staged Rollout (Recommended)

**iOS**: In App Store Connect → Pricing and Availability → Phased Release
- 1% → 100% over 7 days

**Android**: In Play Console → Release dashboard → Staged rollout
- Start: 5% → 20% → 50% → 100%

---

## Troubleshooting

### iOS Build Fails
```bash
# Clean everything
flutter clean
cd ios
rm -rf Pods Podfile.lock
pod deintegrate
pod install
cd ..
flutter pub get

# Rebuild
flutter build ios --release --dart-define=API_BASE=$API_BASE
```

### Android Build Fails
```bash
# Clean everything
flutter clean
cd android
./gradlew clean
./gradlew cleanBuildCache
cd ..
flutter pub get

# Rebuild
flutter build appbundle --release --dart-define=API_BASE=$API_BASE
```

### Keystore Issues (Android)
```bash
# Verify keystore exists and is valid
keytool -list -v -keystore android/app/upload-keystore.jks

# Check key.properties exists
cat android/key.properties

# Ensure paths are correct in key.properties
```

### Code Signing Issues (iOS)
```bash
# Open Xcode and check:
# 1. Runner target → Signing & Capabilities
# 2. Team selected
# 3. Bundle ID matches: com.togetherpay.togetherpay
# 4. Provisioning profile exists
open ios/Runner.xcworkspace
```

### Push Notifications Not Working
```bash
# iOS: Check entitlements
cat ios/Runner/Runner.entitlements
cat ios/Runner/Runner-Release.entitlements

# Android: Check google-services.json exists
cat android/app/google-services.json

# Verify Firebase configuration
flutter run
# Look for Firebase initialization logs
```

---

## Environment Variables

### Set at Build Time
```bash
# Single variable
flutter build apk --release --dart-define=API_BASE=https://api.togetherpay.app/api/v1

# Multiple variables
flutter build ios --release \
  --dart-define=API_BASE=https://api.togetherpay.app/api/v1 \
  --dart-define=DEBUG=false \
  --dart-define=ENABLE_ANALYTICS=true
```

### Check Current Configuration
```dart
// In lib/env.dart or any Dart file
print('API Base: ${const String.fromEnvironment('API_BASE')}');
```

---

## Version Management

### Update Version Number

Edit `pubspec.yaml`:
```yaml
version: 1.0.1+2  # version+build_number
```

- **Version**: 1.0.1 (shown to users)
- **Build Number**: 2 (internal, must increment)

### iOS Version in Xcode
Also update in `ios/Runner.xcodeproj` (or Xcode GUI):
- CFBundleShortVersionString = 1.0.1
- CFBundleVersion = 2

### Android Version in Gradle
Automatically synced from `pubspec.yaml` via Flutter plugin.

---

## Health Checks

### Before Release
```bash
# Analyze code
flutter analyze

# Run tests
flutter test

# Check outdated packages
flutter pub outdated

# Build for both platforms
flutter build ios --release --dart-define=API_BASE=$API_BASE
flutter build appbundle --release --dart-define=API_BASE=$API_BASE
```

### After Release
- Monitor crash reports (App Store Connect / Play Console)
- Check user reviews and ratings
- Monitor Firebase Analytics (if configured)
- Verify push notifications working in production

---

## Useful Commands

### Device Management
```bash
# List connected devices
flutter devices

# List emulators
flutter emulators

# Launch emulator
flutter emulators --launch [emulator-id]
```

### Logs
```bash
# iOS logs
flutter logs -d [ios-device-id]

# Android logs
flutter logs -d [android-device-id]
adb logcat
```

### Cache Management
```bash
# Clear Flutter cache
flutter clean

# Clear pub cache
flutter pub cache repair

# Clear Gradle cache (Android)
cd android
./gradlew cleanBuildCache
```

---

## Resources

- [Full Production Setup Guide](./PRODUCTION_SETUP.md)
- [Main README](./README.md)
- [Flutter Deployment Docs](https://docs.flutter.dev/deployment)
- [iOS Deployment Guide](https://docs.flutter.dev/deployment/ios)
- [Android Deployment Guide](https://docs.flutter.dev/deployment/android)

---

**Quick Links**:
- App Store Connect: https://appstoreconnect.apple.com
- Google Play Console: https://play.google.com/console
- Firebase Console: https://console.firebase.google.com

**Support**: See [PRODUCTION_SETUP.md](./PRODUCTION_SETUP.md) for troubleshooting and detailed instructions.
