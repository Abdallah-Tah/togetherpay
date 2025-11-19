# TogetherPay - Pre-Launch Checklist

## ✅ Before Building Release

### Firebase Configuration
- [ ] Production Firebase project created
- [ ] `google-services.json` in `android/app/`
- [ ] `GoogleService-Info.plist` in `ios/Runner/`
- [ ] FCM server key configured in Laravel backend
- [ ] Test push notifications working

### API Configuration
- [ ] Backend API deployed at `https://www.togetherpay.app/api/v1`
- [ ] API endpoints tested and working
- [ ] SSL certificate valid
- [ ] CORS configured correctly
- [ ] Rate limiting configured

### App Configuration
- [ ] `lib/env.dart` points to production API
- [ ] Debug logging disabled
- [ ] App version set in `pubspec.yaml`
- [ ] App name and bundle ID correct

### Legal & Privacy
- [ ] Privacy policy published at `/privacy`
- [ ] Terms of service published at `/terms`
- [ ] Contact page working
- [ ] Privacy policy reviewed for compliance

## 🔐 Release Signing

- [ ] Keystore created (`~/togetherpay-release-key.jks`)
- [ ] Keystore password saved securely (password manager)
- [ ] `android/key.properties` created with correct passwords
- [ ] Keystore backed up to secure location
- [ ] Key alias: `togetherpay`

## 🏗️ Build Process

- [ ] Run `flutter clean`
- [ ] Run `flutter pub get`
- [ ] Build succeeds: `flutter build appbundle --release`
- [ ] AAB file generated: `build/app/outputs/bundle/release/app-release.aab`
- [ ] AAB size reasonable (<50MB)

## 📱 Testing

- [ ] Install debug APK on physical device
- [ ] Test registration flow
- [ ] Test login flow
- [ ] Test group creation
- [ ] Test payment requests (Venmo/Cash App links work)
- [ ] Test push notifications
- [ ] Test Square OAuth connection (if applicable)
- [ ] Test offline behavior
- [ ] Test on Android 8+ devices
- [ ] No crashes in recent session

## 🎨 Play Store Listing

### Required Info
- [ ] App name: "TogetherPay"
- [ ] Short description (80 chars)
- [ ] Full description (copy from play-store-listing.md)
- [ ] App category: Finance
- [ ] Contact email: support@togetherpay.com
- [ ] Privacy policy URL: https://www.togetherpay.app/privacy
- [ ] Website: https://www.togetherpay.app

### Graphics
- [ ] App icon (512 x 512 px)
- [ ] Feature graphic (1024 x 500 px)
- [ ] Phone screenshots (2-8 images, 1242x2688 or 1080x1920)
- [ ] Tablet screenshots (optional)
- [ ] Promo video (optional)

### Content Rating
- [ ] Content rating questionnaire completed
- [ ] Selected: Finance/Personal Finance
- [ ] Age rating: 18+

### Data Safety
- [ ] Data collection declared:
  - Email address
  - Name
  - Payment channel handles
  - Phone number (optional)
- [ ] Data sharing declared (Square for autopay)
- [ ] Data security: Encrypted in transit & at rest
- [ ] Data deletion available

### Pricing & Distribution
- [ ] Free app
- [ ] Countries: All available countries (or selected list)
- [ ] Contains ads: No
- [ ] In-app purchases: No (unless adding premium tier)

## 🚀 Final Steps

- [ ] Test pre-launch report (Google runs automated tests)
- [ ] Review all warnings/suggestions
- [ ] Submit for review
- [ ] Monitor Play Console for approval status
- [ ] Prepare for user support inquiries

## 📝 Post-Launch

- [ ] Monitor crash reports in Play Console
- [ ] Monitor user reviews
- [ ] Set up alerts for app health metrics
- [ ] Prepare update plan for bug fixes
- [ ] Plan feature roadmap

## 🆘 Rollback Plan

- [ ] Keep previous version AAB backed up
- [ ] Know how to halt rollout in Play Console
- [ ] Have hotfix process ready
- [ ] Support email monitored

---

## Important Notes

1. **First approval takes 2-7 days** - Plan accordingly
2. **Keystore loss = cannot update app** - Back it up securely!
3. **Privacy policy must be accessible** - Test the URL
4. **Test on real devices** - Emulators don't catch everything
5. **Monitor first 24 hours closely** - Check for crash reports

## Support Contacts

- **Google Play Support**: play.google.com/console/about/support
- **Firebase Console**: console.firebase.google.com
- **Laravel Backend**: Check server logs and uptime
