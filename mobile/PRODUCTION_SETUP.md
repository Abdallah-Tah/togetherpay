# Production Setup Guide - TogetherPay Mobile

This guide walks through all the steps needed to prepare the TogetherPay mobile app for production deployment to the App Store and Google Play Store.

## Table of Contents

1. [Prerequisites](#prerequisites)
2. [iOS Production Setup](#ios-production-setup)
3. [Android Production Setup](#android-production-setup)
4. [Building for Production](#building-for-production)
5. [Pre-Release Testing Checklist](#pre-release-testing-checklist)
6. [Deployment](#deployment)

---

## Prerequisites

### Required Accounts
- [ ] Apple Developer Account ($99/year) - https://developer.apple.com
- [ ] Google Play Console Account ($25 one-time) - https://play.google.com/console
- [ ] Firebase Project with iOS and Android apps configured
- [ ] Production API server running at production URL

### Required Tools
- [ ] Flutter SDK 3.0+ installed
- [ ] Xcode 14+ (for iOS builds, macOS required)
- [ ] Android Studio with Android SDK
- [ ] Git for version control

### API Configuration
- [ ] Production API base URL (e.g., `https://api.togetherpay.app/api/v1`)
- [ ] API endpoints tested and working
- [ ] SSL certificates configured on API server

---

## iOS Production Setup

### 1. App Store Connect Setup

#### Create App in App Store Connect
1. Log in to [App Store Connect](https://appstoreconnect.apple.com)
2. Click **My Apps** → **+** → **New App**
3. Fill in app information:
   - **Platform**: iOS
   - **Name**: TogetherPay
   - **Primary Language**: English (or your language)
   - **Bundle ID**: Select or create `com.togetherpay.togetherpay`
   - **SKU**: `togetherpay-mobile` (or unique identifier)
   - **User Access**: Full Access

4. Complete app details:
   - App Information
   - Pricing and Availability
   - App Privacy details
   - Age Rating

#### Create App Screenshots
Required screenshot sizes (use physical devices or simulators):
- 6.7" Display (iPhone 14 Pro Max): 1290 x 2796 pixels
- 6.5" Display (iPhone 11 Pro Max): 1284 x 2778 pixels
- 5.5" Display (iPhone 8 Plus): 1242 x 2208 pixels

Capture screenshots of:
1. Login/Register screen
2. Groups list
3. Group details with members
4. Payment requests screen
5. Payment screen with wallet integration

### 2. Push Notification Certificates

#### Generate APNs Authentication Key
1. Go to [Apple Developer Portal](https://developer.apple.com)
2. Navigate to **Certificates, Identifiers & Profiles**
3. Click **Keys** → **+** (Create a key)
4. Name: "TogetherPay Push Notifications"
5. Enable **Apple Push Notifications service (APNs)**
6. Click **Continue** → **Register**
7. Download the `.p8` key file (save it securely - you can't download it again!)
8. Note the **Key ID** (10 characters)
9. Note your **Team ID** (found in top-right of developer portal)

#### Upload to Firebase
1. Go to Firebase Console → Your Project
2. Navigate to **Project Settings** → **Cloud Messaging**
3. Under **Apple app configuration**, click **Upload**
4. Provide:
   - APNs Auth Key (.p8 file)
   - Key ID
   - Team ID

### 3. Configure Associated Domains (Universal Links)

#### Apple Developer Portal
1. Go to **Identifiers** → Select your App ID
2. Scroll to **Associated Domains** → Enable it
3. Save changes

#### Create apple-app-site-association File
On your web server at `https://tpay.app/.well-known/apple-app-site-association`:

```json
{
  "applinks": {
    "apps": [],
    "details": [
      {
        "appID": "TEAM_ID.com.togetherpay.togetherpay",
        "paths": ["/p/*", "/pay/*"]
      }
    ]
  }
}
```

Replace `TEAM_ID` with your Apple Team ID.

Also create at `https://togetherpay.app/.well-known/apple-app-site-association` with the same content.

**Important**: File must be served with:
- Content-Type: `application/json`
- No `.json` extension
- HTTPS only
- No redirects

#### Verify Associated Domains
Test with Apple's validation tool:
```bash
curl -v https://tpay.app/.well-known/apple-app-site-association
```

### 4. Xcode Project Configuration

#### Open Project
```bash
cd ios
open Runner.xcworkspace
```

#### Configure Signing
1. Select **Runner** in project navigator
2. Select **Runner** target
3. Go to **Signing & Capabilities** tab
4. **Team**: Select your Apple Developer team
5. **Automatically manage signing**: ✓ (enabled)
6. **Bundle Identifier**: `com.togetherpay.togetherpay`

#### Add Capabilities (If Not Already Present)
1. Click **+ Capability**
2. Add **Push Notifications**
3. Add **Associated Domains**
   - Add: `applinks:tpay.app`
   - Add: `applinks:togetherpay.app`
4. Add **Background Modes**
   - Enable: Remote notifications

#### Verify Entitlements
The following files should exist:
- `ios/Runner/Runner.entitlements` (development - aps-environment: development)
- `ios/Runner/Runner-Release.entitlements` (production - aps-environment: production)

Both should contain:
```xml
<key>aps-environment</key>
<string>production</string>
<key>com.apple.developer.associated-domains</key>
<array>
    <string>applinks:tpay.app</string>
    <string>applinks:togetherpay.app</string>
</array>
```

### 5. iOS Build Configuration

#### Update Build Settings
In Xcode, select Runner target → **Build Settings**:
- **iOS Deployment Target**: 12.0 or higher
- **Swift Language Version**: Swift 5
- **Architectures**: arm64 (for physical devices)

#### Update Info.plist
Already configured, but verify these keys exist in `ios/Runner/Info.plist`:
- `CFBundleDisplayName`: TogetherPay
- `LSApplicationQueriesSchemes`: venmo
- `CFBundleURLTypes`: togetherpay scheme
- `UIBackgroundModes`: remote-notification
- `FirebaseAppDelegateProxyEnabled`: false

---

## Android Production Setup

### 1. Google Play Console Setup

#### Create App in Play Console
1. Log in to [Google Play Console](https://play.google.com/console)
2. Click **Create app**
3. Fill in details:
   - **App name**: TogetherPay
   - **Default language**: English (or your language)
   - **App or game**: App
   - **Free or paid**: Free
4. Accept declarations and click **Create app**

#### Set Up Store Listing
Complete all required sections:
1. **App details**
   - Short description (80 chars max)
   - Full description (4000 chars max)
   - App icon (512 x 512 px)
   - Feature graphic (1024 x 500 px)

2. **Screenshots**
   - Phone screenshots (minimum 2):
     - 1080 x 1920 to 1080 x 2340 pixels
   - 7-inch tablet screenshots (optional)
   - 10-inch tablet screenshots (optional)

3. **Categorization**
   - App category: Finance
   - Tags: bill splitting, payment requests, group payments

4. **Contact details**
   - Email, phone, website
   - Privacy policy URL (required)

5. **Content rating**
   - Complete questionnaire
   - Get rating certificate

### 2. Configure App Signing

#### Generate Upload Key
```bash
cd android/app
keytool -genkey -v -keystore upload-keystore.jks -keyalg RSA -keysize 2048 -validity 10000 -alias upload
```

Enter secure password and details when prompted.

**IMPORTANT**: Back up this keystore file and password securely! You'll need it for all future releases.

#### Create key.properties File
Create `android/key.properties` (DO NOT commit to git):

```properties
storePassword=YOUR_STORE_PASSWORD
keyPassword=YOUR_KEY_PASSWORD
keyAlias=upload
storeFile=/path/to/upload-keystore.jks
```

Add to `.gitignore`:
```
android/key.properties
android/app/upload-keystore.jks
```

#### Update build.gradle.kts
The signing configuration should already be set up. Verify `android/app/build.gradle.kts` contains:

```kotlin
// Load keystore properties
val keystorePropertiesFile = rootProject.file("key.properties")
val keystoreProperties = Properties()
if (keystorePropertiesFile.exists()) {
    keystoreProperties.load(FileInputStream(keystorePropertiesFile))
}

android {
    // ... other config

    signingConfigs {
        create("release") {
            keyAlias = keystoreProperties["keyAlias"] as String
            keyPassword = keystoreProperties["keyPassword"] as String
            storeFile = file(keystoreProperties["storeFile"] as String)
            storePassword = keystoreProperties["storePassword"] as String
        }
    }
    
    buildTypes {
        release {
            signingConfig = signingConfigs.getByName("release")
            // Enable R8 code shrinking
            isMinifyEnabled = true
            proguardFiles(
                getDefaultProguardFile("proguard-android-optimize.txt"),
                "proguard-rules.pro"
            )
        }
    }
}
```

If not present, you'll need to add this configuration.

### 3. Configure SHA-256 Fingerprints for App Links

#### Generate SHA-256 Certificate Fingerprint
```bash
# For release keystore
keytool -list -v -keystore android/app/upload-keystore.jks -alias upload

# For debug keystore (testing)
keytool -list -v -keystore ~/.android/debug.keystore -alias androiddebugkey -storepass android -keypass android
```

Look for the **SHA-256** line in the output, e.g.:
```
SHA-256: AB:CD:EF:12:34:56:78:90:AB:CD:EF:12:34:56:78:90:AB:CD:EF:12:34:56:78:90:AB:CD:EF:12:34:56:78
```

#### Add to Google Play Console
1. In Play Console, go to **Setup** → **App integrity**
2. Under **App signing**, view the **App signing key certificate**
3. Note the **SHA-256 certificate fingerprint**

#### Add to Firebase
1. Firebase Console → Project Settings
2. Select Android app
3. Add SHA-256 fingerprint(s):
   - App signing key SHA-256 (from Play Console)
   - Debug keystore SHA-256 (for testing)

#### Configure Digital Asset Links (App Links)
On your web server at `https://tpay.app/.well-known/assetlinks.json`:

```json
[{
  "relation": ["delegate_permission/common.handle_all_urls"],
  "target": {
    "namespace": "android_app",
    "package_name": "com.togetherpay.togetherpay",
    "sha256_cert_fingerprints": [
      "AB:CD:EF:12:34:56:78:90:AB:CD:EF:12:34:56:78:90:AB:CD:EF:12:34:56:78:90:AB:CD:EF:12:34:56:78"
    ]
  }
}]
```

Also create at `https://togetherpay.app/.well-known/assetlinks.json`.

Replace the SHA-256 fingerprint with your actual app signing key fingerprint.

**Important**: File must be served with:
- Content-Type: `application/json`
- HTTPS only
- Accessible without authentication

#### Verify Digital Asset Links
Test with Google's verification tool:
```bash
curl https://tpay.app/.well-known/assetlinks.json
```

Or use: https://developers.google.com/digital-asset-links/tools/generator

### 4. Firebase Configuration

Verify `android/app/google-services.json` exists and contains correct configuration:
- Package name: `com.togetherpay.togetherpay`
- API keys for FCM
- Google App ID

### 5. ProGuard Rules (Optional)

If using code shrinking, create `android/app/proguard-rules.pro`:

```proguard
# Flutter wrapper
-keep class io.flutter.app.** { *; }
-keep class io.flutter.plugin.** { *; }
-keep class io.flutter.util.** { *; }
-keep class io.flutter.view.** { *; }
-keep class io.flutter.** { *; }
-keep class io.flutter.plugins.** { *; }

# Firebase
-keep class com.google.firebase.** { *; }
-keep class com.google.android.gms.** { *; }

# Dio HTTP client
-keep class com.google.gson.** { *; }
-dontwarn com.google.gson.**

# URL Launcher
-keep class io.flutter.plugins.urllauncher.** { *; }
```

---

## Building for Production

### Environment Variables

Set production API base URL at build time:

```bash
# For iOS
flutter build ios --release --dart-define=API_BASE=https://api.togetherpay.app/api/v1

# For Android
flutter build apk --release --dart-define=API_BASE=https://api.togetherpay.app/api/v1
```

### iOS Production Build

#### Build IPA for App Store

1. **Clean previous builds**:
```bash
flutter clean
cd ios
rm -rf Pods Podfile.lock
pod install
cd ..
```

2. **Build archive**:
```bash
flutter build ios --release --dart-define=API_BASE=https://api.togetherpay.app/api/v1
```

3. **Open in Xcode**:
```bash
open ios/Runner.xcworkspace
```

4. **Archive and Upload**:
   - In Xcode: **Product** → **Archive**
   - Once archive completes, Organizer window opens
   - Select archive → Click **Distribute App**
   - Choose **App Store Connect**
   - Select distribution options:
     - Upload → Strip Swift symbols ✓
     - Include bitcode (deprecated in Xcode 14)
   - Click **Upload**

5. **Wait for processing** (10-30 minutes)
   - App Store Connect will process the build
   - You'll receive email when ready for submission

#### Submit for Review

1. In App Store Connect → Your App → **App Store** tab
2. Click **+** next to **iOS App** to create new version
3. Fill in:
   - Version number (e.g., 1.0.0)
   - What's New in This Version
4. Select the uploaded build
5. Complete all sections with warnings/errors
6. Click **Submit for Review**

Review times: 1-3 days typically

### Android Production Build

#### Build App Bundle (Recommended)

1. **Clean previous builds**:
```bash
flutter clean
flutter pub get
```

2. **Build App Bundle**:
```bash
flutter build appbundle --release --dart-define=API_BASE=https://api.togetherpay.app/api/v1
```

Output: `build/app/outputs/bundle/release/app-release.aab`

#### Alternative: Build APK

```bash
flutter build apk --release --dart-define=API_BASE=https://api.togetherpay.app/api/v1
```

Output: `build/app/outputs/flutter-apk/app-release.apk`

**Note**: Google Play requires App Bundle (AAB) for new apps since August 2021.

#### Upload to Play Console

1. Go to Play Console → Your App → **Release** → **Production**
2. Click **Create new release**
3. Upload the AAB file (`app-release.aab`)
4. Fill in:
   - Release name (e.g., "1.0.0 (1)")
   - Release notes
5. Review release → **Start rollout to Production**

#### Submit for Review

1. Complete all sections in Play Console:
   - Store listing
   - Content rating
   - Pricing & distribution
   - App content (privacy policy, ads, etc.)
2. Click **Send for review**

Review times: Several hours to a few days

---

## Pre-Release Testing Checklist

Test on **physical devices** (simulators/emulators don't support all features):

### iOS Testing

#### Device Requirements
- [ ] Test on iPhone with iOS 12.0+ (multiple versions if possible)
- [ ] Test on iPad (if supporting tablets)

#### Authentication & Authorization
- [ ] Register new account successfully
- [ ] Login with existing account
- [ ] Logout and confirm token cleared
- [ ] Auth token persists after app restart
- [ ] API calls include correct Authorization header
- [ ] Handle 401 Unauthorized (force logout)

#### Groups Management
- [ ] View groups list (with data and empty state)
- [ ] Pull to refresh groups list
- [ ] Tap group → Navigate to group details
- [ ] View group members with correct shares
- [ ] View group payment requests
- [ ] Display owner badge correctly
- [ ] Display "You" indicator for current user

#### Payment Requests
- [ ] View payment requests organized by status
- [ ] See Overdue payments (red badge)
- [ ] See Pending payments (yellow badge)
- [ ] See Paid payments (green badge)
- [ ] Pull to refresh payment list
- [ ] Tap payment → Navigate to PayScreen

#### Payment Flow - Cash App
- [ ] PayScreen loads payment details from API
- [ ] Cash App launches automatically with prefilled amount
- [ ] Amount is correct in Cash App
- [ ] Note/memo includes PR code
- [ ] Recipient tag is correct
- [ ] Complete payment in Cash App
- [ ] Return to app → Auto-confirmation dialog appears
- [ ] Tap "Yes, I paid" → API call succeeds
- [ ] Payment status updates to "member_marked"
- [ ] Success message displays
- [ ] Navigate back to payment list

#### Payment Flow - Venmo
- [ ] Venmo deep link launches Venmo app
- [ ] Amount prefilled correctly
- [ ] Note includes PR code
- [ ] Complete payment flow similar to Cash App

#### Wallet App Not Installed
- [ ] Tap "Open Wallet" when Cash App not installed
- [ ] Fallback to web URL or show helpful error
- [ ] User can install Cash App and retry

#### Push Notifications
- [ ] Receive push notification when app closed
- [ ] Notification shows correct title and body
- [ ] Tap notification → App opens to PayScreen
- [ ] PR code from notification loads correct payment
- [ ] Deep link in notification data works
- [ ] Receive notification when app in background
- [ ] Foreground notification shows local notification

#### Deep Links / Universal Links
- [ ] Tap short link (`https://tpay.app/p/abCDef`) in Safari
- [ ] App opens to correct PayScreen
- [ ] Payment details load from API
- [ ] Universal link works from email client
- [ ] Universal link works from Messages app

#### Associated Domains
- [ ] Universal links open app (not Safari)
- [ ] Verify `applinks:tpay.app` working
- [ ] Verify `applinks:togetherpay.app` working

#### Error Handling
- [ ] Offline mode - Show appropriate error messages
- [ ] API error - Display error with retry option
- [ ] Invalid PR code - Show error message
- [ ] Network timeout - Handle gracefully

#### UI/UX
- [ ] Splash screen displays on startup
- [ ] Bottom navigation works correctly
- [ ] Pull-to-refresh animations smooth
- [ ] Loading indicators display appropriately
- [ ] Empty states show helpful messages
- [ ] Logout confirmation dialog works

### Android Testing

#### Device Requirements
- [ ] Test on device with Android 6.0+ (API 23+)
- [ ] Test on Android 13+ for notification permissions
- [ ] Test on different screen sizes/densities

#### All Tests from iOS Section
Run the same tests as iOS (authentication, groups, payments, etc.)

#### Android-Specific Tests
- [ ] Request notification permission on Android 13+
- [ ] Permission denied → Show rationale and retry
- [ ] App Links verified and working
- [ ] Verify `https://tpay.app/p/*` opens app
- [ ] Verify `https://togetherpay.app/pay/*` opens app

#### Deep Links
- [ ] Intent filter handles App Links correctly
- [ ] `autoVerify="true"` working
- [ ] Digital Asset Links verified

#### Cash App / Venmo on Android
- [ ] Cash App package detected: `com.squareup.cash`
- [ ] Venmo package detected: `com.venmo`
- [ ] Query permissions allow detection
- [ ] Launch external apps correctly

### Both Platforms

#### Complete Payment Flow End-to-End
1. [ ] Backend sends push notification via FCM
2. [ ] Mobile app receives notification
3. [ ] User taps notification
4. [ ] App opens to PayScreen
5. [ ] Payment details fetched from API
6. [ ] Wallet app launches automatically
7. [ ] User completes payment
8. [ ] Returns to app
9. [ ] Auto-confirmation dialog appears
10. [ ] User taps "Yes, I paid"
11. [ ] API `/requests/by-code/{prCode}/mark` called
12. [ ] Backend updates status to `member_marked`
13. [ ] Success message shown
14. [ ] Payment list updates status
15. [ ] Owner sees updated status on web/mobile

#### API Integration
- [ ] All API endpoints return expected responses
- [ ] Auth token included in all requests
- [ ] Handle 401 Unauthorized gracefully
- [ ] Handle 404 Not Found (invalid PR code)
- [ ] Handle 500 Server Error
- [ ] API base URL set to production

#### Production Configuration
- [ ] API_BASE environment variable set to production URL
- [ ] No debug logging in production build
- [ ] Firebase production configuration files present
- [ ] Push notifications use production APNs/FCM
- [ ] Code signing configured correctly
- [ ] ProGuard/R8 enabled (Android)
- [ ] Bitcode enabled (iOS) - deprecated in Xcode 14

#### Security
- [ ] Auth token stored securely (FlutterSecureStorage)
- [ ] No sensitive data logged
- [ ] HTTPS enforced for all API calls
- [ ] Deep links validated before processing
- [ ] No hardcoded credentials in code

#### Performance
- [ ] App launches quickly (<3 seconds)
- [ ] List scrolling is smooth
- [ ] Image loading optimized
- [ ] API responses cached appropriately
- [ ] No memory leaks

#### Offline Behavior
- [ ] App shows cached data when offline
- [ ] Appropriate error messages when offline
- [ ] Retry mechanism when connection restored
- [ ] Queue actions for when online returns (if applicable)

---

## Deployment

### iOS Deployment

#### Phased Release (Recommended)
1. In App Store Connect → Pricing and Availability
2. Enable **Phased Release** (releases to 1% → 100% over 7 days)
3. Monitor crash reports and reviews
4. Pause release if critical issues found

#### Post-Submission
- Monitor **App Store Connect** for review status
- Respond to any review rejection reasons
- Check TestFlight for crashes/feedback
- Monitor Firebase Crashlytics (if configured)

### Android Deployment

#### Staged Rollout (Recommended)
1. In Play Console → Release dashboard
2. Create release → Select **Staged rollout**
3. Start with 5% → 20% → 50% → 100%
4. Monitor crash reports between stages
5. Halt rollout if critical issues found

#### Post-Submission
- Monitor **Google Play Console** for review status
- Check **Android vitals** for performance metrics
- Monitor crash reports in Play Console
- Respond to user reviews

---

## Monitoring & Analytics

### Firebase Analytics (Optional)
Set up Firebase Analytics to track:
- User acquisition
- User engagement
- Payment completion rates
- Wallet app launch success rates
- Notification open rates

### Crash Reporting
- iOS: Crashlytics or App Store Connect
- Android: Firebase Crashlytics or Play Console

### Performance Monitoring
- App launch time
- API response times
- Screen load times
- Network request failures

---

## Post-Launch Checklist

### Week 1
- [ ] Monitor crash reports daily
- [ ] Check user reviews and ratings
- [ ] Verify analytics data flowing
- [ ] Test push notifications in production
- [ ] Monitor API server logs for errors

### Week 2-4
- [ ] Analyze user behavior patterns
- [ ] Identify most common user flows
- [ ] Check payment completion rates
- [ ] Review support tickets/feedback
- [ ] Plan improvements for next release

### Ongoing
- [ ] Release updates regularly (bug fixes, features)
- [ ] Keep dependencies updated
- [ ] Monitor security advisories
- [ ] Maintain >4.0 star rating
- [ ] Respond to user reviews

---

## Troubleshooting Production Issues

### iOS Push Notifications Not Working
- Verify APNs certificate uploaded to Firebase
- Check device token registration in backend
- Verify `aps-environment` entitlement is `production`
- Test with Firebase Console test notification
- Check device logs in Xcode Console

### Android Push Notifications Not Working
- Verify `google-services.json` has correct package name
- Check FCM server key in backend
- Request notification permission on Android 13+
- Test with Firebase Console test notification

### Universal/App Links Not Working
- Verify AASA file served correctly (iOS)
- Verify Digital Asset Links file served correctly (Android)
- Check SHA-256 fingerprints match
- Test links in Safari/Chrome (not in-app browsers)
- Use Apple/Google validation tools

### Wallet Apps Not Launching
- Verify URL schemes in Info.plist (iOS)
- Verify queries in AndroidManifest.xml (Android)
- Check `canLaunchUrl()` returns true
- Test deep link format is correct
- Verify wallet apps installed on device

### App Crashes on Launch
- Check Firebase configuration files
- Verify all required dependencies included
- Review crash logs in App Store Connect / Play Console
- Test on multiple device models/OS versions

---

## Support & Resources

### Official Documentation
- [Flutter Docs](https://docs.flutter.dev)
- [Firebase for Flutter](https://firebase.google.com/docs/flutter/setup)
- [Apple Developer](https://developer.apple.com)
- [Google Play Console](https://support.google.com/googleplay/android-developer)

### TogetherPay Documentation
- [Main README](./README.md)
- [Architecture](../ARCHITECTURE_V2.md)
- [API Integration](./README_API_INTEGRATION.md)

### Contact
For production issues or questions:
- GitHub Issues: https://github.com/Abdallah-Tah/togetherpay/issues
- Email: [Your support email]

---

**Last Updated**: November 2025
**Version**: 1.0.0
