# Mobile App Testing Guide - Pre-Production

This guide walks you through testing the TogetherPay mobile app before submitting to Google Play Store and Apple App Store.

## Prerequisites

- [ ] Backend deployed at https://www.togetherpay.app/api/v1
- [ ] Backend API is accessible and returning 200 responses
- [ ] SSL certificate is valid
- [ ] Database migrations are up to date
- [ ] Environment variables are properly configured

## 1. Create Production Signing Key (Android)

### Generate Keystore

```bash
cd mobile/android
keytool -genkey -v -keystore ~/togetherpay-release-key.jks \
  -keyalg RSA -keysize 2048 -validity 10000 -alias togetherpay
```

**IMPORTANT:** Save the passwords securely - you'll need them for every release!

### Create key.properties

Create `mobile/android/key.properties` (DO NOT commit to git):

```properties
storePassword=<your-keystore-password>
keyPassword=<your-key-password>
keyAlias=togetherpay
storeFile=/Users/yourusername/togetherpay-release-key.jks
```

### Add to .gitignore

Ensure `mobile/android/key.properties` is in your `.gitignore`!

## 2. Build Release Builds

### Android APK (for direct installation)

```bash
cd mobile
flutter build apk --release
```

Output: `build/app/outputs/flutter-apk/app-release.apk`

### Android App Bundle (for Play Store)

```bash
cd mobile
flutter build appbundle --release
```

Output: `build/app/outputs/bundle/release/app-release.aab`

### iOS (requires macOS and Xcode)

```bash
cd mobile
flutter build ios --release
```

Then use Xcode to archive and export for TestFlight.

## 3. Install on Test Devices

### Android - USB Installation

1. Enable Developer Options on your Android device
2. Enable USB Debugging
3. Connect via USB
4. Install:

```bash
cd mobile
adb install build/app/outputs/flutter-apk/app-release.apk
```

### Android - Manual Installation

1. Copy `app-release.apk` to device (Google Drive, email, etc.)
2. Open on device
3. Enable "Install from Unknown Sources" if prompted
4. Install

### iOS - TestFlight

1. Upload build to App Store Connect
2. Create TestFlight beta
3. Invite testers via email
4. Testers install via TestFlight app

## 4. Comprehensive Testing Checklist

### Authentication Flow

- [ ] **Register new account**
  - Valid email format enforced
  - Password requirements enforced
  - Success toast shown
  - Redirects to onboarding

- [ ] **Login**
  - Correct credentials work
  - Invalid credentials show error
  - "Remember me" functionality
  - Auto-login on app restart

- [ ] **Logout**
  - Clears session
  - Returns to login screen
  - Subsequent API calls fail with 401

### Onboarding Flow

- [ ] **Welcome screen displays correctly**
- [ ] **Billing mode selection**
  - Manual mode can be selected
  - Auto mode can be selected (if Square enabled)
  - Validation works

- [ ] **Payment method setup**
  - Cash App handle can be entered
  - Venmo handle can be entered
  - Validation works

- [ ] **Square connection** (if auto mode)
  - OAuth flow works
  - Returns to app after auth
  - Square credentials saved

### Groups Management

- [ ] **Create new group**
  - Name validation
  - Member addition works
  - Share allocation works
  - Billing cycle configuration
  - Success creates group

- [ ] **View groups list**
  - All groups display
  - Pull to refresh works
  - Tap opens group details

- [ ] **Group details**
  - Shows correct info
  - Member list displays
  - Edit group works
  - Delete group works (with confirmation)

- [ ] **Add/Remove members**
  - Add member form works
  - Share recalculation works
  - Remove member confirmation
  - Member removed successfully

### Payment Requests

- [ ] **View payment requests list**
  - Pending requests show
  - Paid requests show
  - Filters work (status, group)
  - Pull to refresh works

- [ ] **Member: Pay request**
  - Deep link to Cash App works
  - Deep link to Venmo works
  - Mark as paid button works
  - Status updates

- [ ] **Owner: Confirm payment**
  - Can see member's marked payments
  - Confirm button works
  - Status changes to confirmed
  - Rejected button works

- [ ] **Square auto-payment** (if enabled)
  - Invoice received via email
  - Payment via Square works
  - Webhook confirms automatically
  - Status shows as auto_verified

### Push Notifications

- [ ] **Registration**
  - FCM token generated
  - Token sent to backend
  - Stored in database

- [ ] **Receive notifications**
  - New payment request notification
  - Reminder notification
  - Payment confirmed notification
  - Tap notification opens relevant screen

### Profile & Settings

- [ ] **View profile**
  - User info displays correctly
  - Email shows
  - Name shows

- [ ] **Notification preferences**
  - Toggle push notifications
  - Toggle email notifications
  - Preferences saved

- [ ] **Payment methods**
  - Cash App handle displays
  - Venmo handle displays
  - Can edit handles
  - Validation works

- [ ] **Security**
  - Change password works
  - Old password required
  - New password validation
  - Success/error messages

### Reports & Analytics

- [ ] **Group reports**
  - Collection rates display
  - Charts render correctly
  - Export CSV works
  - Export PDF works

- [ ] **Member reports**
  - Payment history shows
  - On-time percentage correct
  - Graphs display

### Network & Edge Cases

- [ ] **Poor network**
  - Loading indicators show
  - Error messages display
  - Retry functionality works

- [ ] **Offline mode**
  - Cached data displays
  - Appropriate offline message
  - Auto-retry when online

- [ ] **Network switch**
  - WiFi → Mobile data works
  - Mobile data → WiFi works
  - No crashes

- [ ] **API errors**
  - 401 logs user out
  - 404 shows appropriate message
  - 500 shows user-friendly error
  - Network errors handled

### Device-Specific Testing

- [ ] **Android versions**
  - Test on Android 10 (API 29)
  - Test on Android 11 (API 30)
  - Test on Android 12+ (API 31+)

- [ ] **iOS versions**
  - Test on iOS 13
  - Test on iOS 14
  - Test on iOS 15+

- [ ] **Screen sizes**
  - Small phone (iPhone SE, Android small)
  - Medium phone (iPhone 13, Pixel)
  - Large phone (iPhone Pro Max, Galaxy S)
  - Tablet (iPad, Android tablet)

- [ ] **Orientations**
  - Portrait mode
  - Landscape mode (if supported)
  - Rotation transitions

### Performance Testing

- [ ] **App size**
  - APK/IPA < 50MB
  - Download size acceptable

- [ ] **Memory usage**
  - No memory leaks
  - Scrolling is smooth
  - No excessive memory warnings

- [ ] **Battery usage**
  - No excessive battery drain
  - Background processes optimized

- [ ] **Startup time**
  - Cold start < 3 seconds
  - Warm start < 1 second

### Security Testing

- [ ] **Authentication tokens**
  - Stored securely (FlutterSecureStorage)
  - Not logged in debug mode
  - Cleared on logout

- [ ] **HTTPS**
  - All API calls use HTTPS
  - Certificate validation works
  - No mixed content

- [ ] **Sensitive data**
  - Passwords not visible
  - Payment info masked
  - No sensitive data in logs

## 5. Internal Testing (Play Store)

### Upload to Play Console

1. Go to [Google Play Console](https://play.google.com/console)
2. Select "TogetherPay" app (or create new app)
3. Navigate to **Testing** → **Internal testing**
4. Click **Create new release**
5. Upload `app-release.aab`
6. Add release notes
7. Save and review
8. **Publish to internal testing**

### Add Testers

1. In Internal testing, click **Testers** tab
2. Create email list of testers
3. Add emails (yourself, team members, friends)
4. Save
5. Share opt-in URL with testers

### Test Installation

1. Testers receive email invitation
2. Click opt-in link
3. Install app from Play Store
4. Run through testing checklist above

## 6. TestFlight (iOS)

### Upload to App Store Connect

1. Archive build in Xcode
2. Upload to App Store Connect
3. Wait for processing (10-30 minutes)
4. Build appears in TestFlight

### Add Testers

1. Go to **TestFlight** tab
2. Add internal testers (App Store Connect users)
3. Add external testers (beta testers)
4. Invite testers

### Test Installation

1. Testers install TestFlight app
2. Accept invite
3. Install TogetherPay
4. Run through testing checklist

## 7. Final Pre-Launch Checks

### Backend Validation

- [ ] API endpoints all working
- [ ] Database backups configured
- [ ] Monitoring/logging in place
- [ ] Rate limiting configured
- [ ] CORS configured correctly

### App Store Compliance

- [ ] Privacy policy URL set
- [ ] Terms of service URL set
- [ ] App description written
- [ ] Screenshots prepared (multiple sizes)
- [ ] App icon meets requirements
- [ ] Age rating set appropriately
- [ ] Content rating questionnaire completed

### Legal & Business

- [ ] Privacy policy reviewed
- [ ] Terms of service reviewed
- [ ] App Store developer agreement signed
- [ ] Play Store developer agreement signed
- [ ] Payment processing compliance verified

## 8. Common Issues & Solutions

### "Installation Failed" on Android

- Uninstall previous version first
- Check storage space
- Verify APK signature

### "Unable to connect to server"

- Verify backend URL in `mobile/lib/env.dart`
- Check firewall/network settings
- Test API with curl: `curl https://www.togetherpay.app/api/v1/health`

### Push notifications not working

- Verify FCM configuration
- Check `google-services.json` (Android)
- Check `GoogleService-Info.plist` (iOS)
- Test with Firebase Console

### Deep links not working

- Verify URL schemes configured
- Check Cash App/Venmo handles format
- Test on real devices (not simulators)

### Square OAuth fails

- Verify Square app credentials
- Check redirect URIs configured
- Ensure production Square app approved

## 9. Build Version Management

### Increment Version Numbers

Before each release build:

**Android (`android/app/build.gradle`):**
```gradle
versionCode 2  // Increment for each release
versionName "1.1.0"  // Semantic versioning
```

**iOS (`ios/Runner.xcodeproj/project.pbxproj`):**
- Version: 1.1.0
- Build: 2

**Or set via command line:**
```bash
flutter build apk --build-number=2 --build-name=1.1.0
```

## 10. Production Release Process

### Google Play Store

1. Complete all testing
2. Create production release
3. Upload signed AAB
4. Fill in release notes
5. Submit for review
6. Wait for approval (1-7 days)
7. Release to production

### Apple App Store

1. Complete all testing
2. Submit build for review
3. Fill in App Store metadata
4. Submit for review
5. Wait for approval (1-7 days)
6. Release to App Store

## Support & Resources

- **Flutter Docs:** https://flutter.dev/docs
- **Play Console Help:** https://support.google.com/googleplay/android-developer
- **App Store Connect Help:** https://developer.apple.com/help/app-store-connect
- **Firebase Docs:** https://firebase.google.com/docs

## Emergency Rollback

If issues arise after release:

**Play Store:**
- Create new release with fix
- OR halt rollout and revert

**App Store:**
- Remove from sale temporarily
- Submit hotfix update

---

**Last Updated:** 2025-11-14
**App Version:** 1.0.0
**Backend:** https://www.togetherpay.app/api/v1
