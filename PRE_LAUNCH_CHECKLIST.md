# Pre-Launch Checklist - TogetherPay Mobile App

## 🎯 Before You Start Testing

### Backend Readiness
- [ ] Production backend deployed at `https://www.togetherpay.app`
- [ ] API accessible at `https://www.togetherpay.app/api/v1`
- [ ] Health check returns 200: `curl https://www.togetherpay.app/api/health`
- [ ] SSL certificate is valid (no warnings in browser)
- [ ] Database migrations are up to date
- [ ] Environment variables configured correctly
- [ ] CORS configured for mobile app
- [ ] Rate limiting configured

### Firebase Setup
- [ ] Firebase project created
- [ ] `google-services.json` in `mobile/android/app/`
- [ ] `GoogleService-Info.plist` in `mobile/ios/Runner/`
- [ ] FCM configured for push notifications
- [ ] Firebase Cloud Messaging enabled

### App Signing (Android)
- [ ] Release keystore created (`togetherpay-release-key.jks`)
- [ ] `key.properties` file created (NOT in git!)
- [ ] `key.properties` added to `.gitignore`
- [ ] `build.gradle` updated to use release signing
- [ ] Keystore password saved securely

### App Signing (iOS)
- [ ] Apple Developer account active
- [ ] App ID registered
- [ ] Certificates created
- [ ] Provisioning profiles configured
- [ ] Xcode project configured

---

## 📱 Android Testing

### Build Phase
- [ ] Dependencies installed: `flutter pub get`
- [ ] Code analyzed: `flutter analyze` (no errors)
- [ ] Build successful: `flutter build apk --release`
- [ ] APK size reasonable (< 50MB)

### Installation Phase
- [ ] APK installed on test device
- [ ] App icon displays correctly
- [ ] App name is "TogetherPay"
- [ ] No installation errors

### Functional Testing
- [ ] **Registration:**
  - [ ] Can create new account
  - [ ] Email validation works
  - [ ] Password requirements enforced
  - [ ] Success redirects to onboarding

- [ ] **Login:**
  - [ ] Can login with valid credentials
  - [ ] Invalid credentials show error
  - [ ] Auto-login on app restart

- [ ] **Onboarding:**
  - [ ] Welcome screen displays
  - [ ] Billing mode selection works
  - [ ] Payment method setup works
  - [ ] Square connection works (if enabled)
  - [ ] Completion redirects to main app

- [ ] **Groups:**
  - [ ] Can create new group
  - [ ] Groups list displays
  - [ ] Can view group details
  - [ ] Can add members
  - [ ] Can edit group
  - [ ] Can delete group

- [ ] **Payment Requests:**
  - [ ] List displays correctly
  - [ ] Can mark payment as sent
  - [ ] Can confirm payment (owner)
  - [ ] Deep links work (Venmo/Cash App)
  - [ ] Status updates correctly

- [ ] **Profile:**
  - [ ] User info displays
  - [ ] Can update profile
  - [ ] Can change password
  - [ ] Settings save correctly

- [ ] **Logout:**
  - [ ] Logout clears session
  - [ ] Returns to login screen
  - [ ] Can't access protected screens

### Network Testing
- [ ] Works on WiFi
- [ ] Works on mobile data (4G/5G)
- [ ] Handles poor connection gracefully
- [ ] Shows offline message when no network
- [ ] Retries when connection restored

### Performance Testing
- [ ] Cold start < 3 seconds
- [ ] Warm start < 1 second
- [ ] Scrolling is smooth (60fps)
- [ ] No memory leaks
- [ ] No excessive battery drain
- [ ] Images load quickly

### Error Handling
- [ ] Invalid API responses handled
- [ ] Network errors show user-friendly messages
- [ ] Validation errors display correctly
- [ ] No app crashes during testing
- [ ] Error messages are helpful

### UI/UX Testing
- [ ] All text is readable
- [ ] Buttons are tappable
- [ ] Forms are easy to fill
- [ ] Navigation is intuitive
- [ ] Loading indicators show
- [ ] Success/error toasts display

### Device Testing
- [ ] Tested on small screen device
- [ ] Tested on large screen device
- [ ] Tested on Android 10+
- [ ] Tested on Android 12+
- [ ] Portrait orientation works
- [ ] Landscape orientation works (if supported)

### Security Testing
- [ ] Auth tokens stored securely
- [ ] Passwords not visible in logs
- [ ] HTTPS used for all API calls
- [ ] No sensitive data in screenshots
- [ ] Session expires appropriately

---

## 🍎 iOS Testing (Requires macOS)

### Build Phase
- [ ] Xcode project opens without errors
- [ ] Dependencies resolved
- [ ] Build successful: `flutter build ios --release`
- [ ] Archive successful in Xcode

### TestFlight Phase
- [ ] Build uploaded to App Store Connect
- [ ] Processing completed (10-30 min)
- [ ] TestFlight build available
- [ ] Internal testers invited
- [ ] External testers invited (if ready)

### Installation Phase
- [ ] TestFlight app installed
- [ ] Can install TogetherPay from TestFlight
- [ ] App icon correct
- [ ] App name correct

### Functional Testing
- [ ] Complete same tests as Android above
- [ ] iOS-specific gestures work
- [ ] iOS notifications work
- [ ] iOS deep links work
- [ ] iOS permissions requested correctly

---

## 🚀 Play Store Preparation

### App Listing
- [ ] App name: "TogetherPay"
- [ ] Short description written (< 80 chars)
- [ ] Full description written (< 4000 chars)
- [ ] Feature graphic created (1024 x 500)
- [ ] Screenshots prepared:
  - [ ] 2-8 phone screenshots
  - [ ] 2-8 tablet screenshots (optional)
- [ ] App icon uploaded (512 x 512)
- [ ] Privacy policy URL: Added
- [ ] Category selected: Finance

### App Content
- [ ] Content rating questionnaire completed
- [ ] Target audience selected
- [ ] App content declaration completed
- [ ] Data safety form completed
- [ ] Permissions explained

### Store Listing
- [ ] Promo video (optional)
- [ ] Contact email set
- [ ] Website URL set
- [ ] Support email set

### Release Build
- [ ] App bundle built: `flutter build appbundle --release`
- [ ] AAB size < 150MB
- [ ] Version code incremented
- [ ] Version name updated (e.g., 1.0.0)

---

## 🍎 App Store Preparation

### App Store Connect
- [ ] App created in App Store Connect
- [ ] Bundle ID matches
- [ ] App name: "TogetherPay"
- [ ] SKU set
- [ ] Primary language set

### App Information
- [ ] Name (30 chars max)
- [ ] Subtitle (30 chars max)
- [ ] Description (4000 chars max)
- [ ] Keywords (100 chars max)
- [ ] Support URL
- [ ] Marketing URL (optional)
- [ ] Privacy policy URL

### Screenshots
- [ ] iPhone 6.7" (1290 x 2796) - 2-10 screenshots
- [ ] iPhone 6.5" (1284 x 2778) - 2-10 screenshots
- [ ] iPad Pro 12.9" (2048 x 2732) - 2-10 screenshots

### App Review Information
- [ ] Contact information
- [ ] Demo account (if needed)
- [ ] Notes for reviewer
- [ ] Age rating questionnaire

---

## 🧪 Internal Testing

### Play Console Internal Testing
- [ ] Internal testing track created
- [ ] Release created
- [ ] AAB uploaded
- [ ] Release notes added
- [ ] Published to internal testing
- [ ] Test email list created
- [ ] Testers invited
- [ ] Opt-in URL shared
- [ ] At least 2 testers installed
- [ ] Feedback collected

### TestFlight Internal Testing
- [ ] Build uploaded
- [ ] Internal testers added
- [ ] Testers invited
- [ ] At least 2 testers installed
- [ ] Feedback collected

---

## ✅ Final Pre-Launch Checks

### Legal & Compliance
- [ ] Privacy policy published
- [ ] Terms of service published
- [ ] GDPR compliance verified (if EU users)
- [ ] COPPA compliance verified (if under 13)
- [ ] Data collection disclosed

### Backend Monitoring
- [ ] Error tracking configured (Sentry/Bugsnag)
- [ ] Performance monitoring configured
- [ ] Analytics configured (optional)
- [ ] Database backups automated
- [ ] Server monitoring alerts set up

### Support Infrastructure
- [ ] Support email configured
- [ ] FAQ/Help docs created
- [ ] Social media accounts created (optional)
- [ ] Community forum set up (optional)

### Post-Launch Plan
- [ ] Marketing plan ready
- [ ] Launch announcement prepared
- [ ] Social media posts scheduled
- [ ] Press release ready (if applicable)
- [ ] User onboarding emails configured

---

## 📊 Success Metrics to Track

### Week 1
- [ ] Total installs
- [ ] Active users (DAU/MAU)
- [ ] Crash rate (target: < 1%)
- [ ] Registration completion rate
- [ ] First group creation rate

### Week 2-4
- [ ] Retention rate (Day 1, Day 7, Day 30)
- [ ] Payment request success rate
- [ ] Average groups per user
- [ ] User feedback/ratings
- [ ] Support ticket volume

---

## 🎉 Launch Day Checklist

### Final Verification
- [ ] Production backend stable
- [ ] Database backed up
- [ ] Monitoring alerts active
- [ ] Support team briefed
- [ ] Emergency contacts ready

### Store Submission
- [ ] **Google Play:**
  - [ ] Review release
  - [ ] Submit for review
  - [ ] Estimated review time: 1-7 days

- [ ] **App Store:**
  - [ ] Review metadata
  - [ ] Submit for review
  - [ ] Estimated review time: 1-7 days

### Post-Submission
- [ ] Monitor review status daily
- [ ] Respond to reviewer questions promptly
- [ ] Test live app after approval
- [ ] Announce launch
- [ ] Monitor crash reports
- [ ] Respond to user reviews

---

## 🆘 Emergency Rollback Plan

If critical issues arise after launch:

### Google Play
- [ ] Halt rollout at current percentage
- [ ] Or create new release with fix
- [ ] Or deactivate release

### App Store
- [ ] Remove app from sale temporarily
- [ ] Submit hotfix update (expedited review available)
- [ ] Or wait for review of fixed version

---

**Last Updated:** 2025-11-14
**Target Launch:** _________
**Person Responsible:** _________

## Notes:

_Use this space for launch-specific notes, decisions, or blockers._
