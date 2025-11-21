# Google Play Store - Closed Testing Setup Guide

## ✅ Release Bundle Ready

**File Location:** `/Users/amohamed/Herd/togetherpay/mobile/build/app/outputs/bundle/release/app-release.aab`
**Size:** 47.0 MB
**Version:** 1.0.0 (Build 3)

---

## Step-by-Step: Upload to Closed Testing

### 1. Upload the App Bundle

1. Go to **Testing** → **Closed testing** in Google Play Console
2. Click **Create new release**
3. Click **Upload** and select the AAB file at:
   ```
   /Users/amohamed/Herd/togetherpay/mobile/build/app/outputs/bundle/release/app-release.aab
   ```
4. Wait for upload to complete

### 2. Fix Permission Declaration Error

The error about "photo and video permissions" needs to be addressed:

**Option A: If you don't need these permissions**
- Check your dependencies (Firebase, notifications plugins)
- These might be requesting permissions you don't use
- In Play Console, go to **App content** → **App permissions**
- Declare that you don't use sensitive permissions

**Option B: If a library requests them**
1. Go to **App content** → **App permissions**
2. Click **Manage permissions**
3. For any photo/video/media permissions:
   - Select "No" if you don't use them
   - OR provide justification if a library needs them
4. Save

### 3. Add Release Notes

In the release form, add release notes:

```
Initial beta release for closed testing.

New features:
• User authentication and registration
• Group creation and management
• Payment request tracking
• Push notifications
• Cash App and Venmo integration
• Billing cycle management

This is a beta version for testing purposes.
```

### 4. Review and Start Rollout

1. Review all release details
2. Click **Save** (not "Start rollout" yet)
3. Fix any remaining errors
4. Once all errors are resolved, click **Start rollout to Closed testing**

---

## Fixing Common Errors

### Error: "This release does not add or remove any app bundles"

**Solution:** Make sure you uploaded the AAB file in step 1

### Error: "Can't rollout this release because it doesn't allow existing users to upgrade"

**Solutions:**
- ✅ Version updated to 1.0.0+3 (higher than any previous version)
- Make sure you haven't already uploaded version code 3
- If you have, increment to version 1.0.0+4:
  ```bash
  # Edit pubspec.yaml version line to: version: 1.0.0+4
  flutter build appbundle --release
  ```

### Error: "Photo and video permissions declaration required"

**Solution:**

1. **Go to App content → App permissions:**
   - https://play.google.com/console → Your App → Policy → App content

2. **Find these permissions and declare:**
   - READ_EXTERNAL_STORAGE
   - READ_MEDIA_IMAGES
   - READ_MEDIA_VIDEO

3. **For each permission:**
   - Click "Manage"
   - Select "No, my app does not request this permission" (if true)
   - OR provide justification and explain core functionality

4. **If Firebase Messaging or other plugins request these:**
   - Explain: "Required by Firebase Cloud Messaging for notification attachments"
   - Core functionality: "Push notifications"

5. **Save and submit declaration**

---

## Adding Testers

### Create Test Email List

1. **Testing** → **Closed testing** → **Testers** tab
2. Click **Create email list**
3. Name: "Beta Testers"
4. Add emails:
   ```
   your-email@example.com
   tester1@example.com
   tester2@example.com
   ```
5. Save

### Share Opt-In URL

1. After publishing to closed testing, get the opt-in URL
2. Share with testers via email
3. Testers click link → Accept invitation → Install from Play Store

---

## Complete App Content Requirements

Before you can publish to closed testing, complete these sections in **App content**:

### 1. Privacy Policy ✅
- URL: https://www.togetherpay.app/privacy-policy
- Already added (if not, add it)

### 2. App Access
- Does app require login? **Yes**
- Provide test credentials:
  ```
  Email: test@togetherpay.app
  Password: TestPassword123!
  ```

### 3. Ads
- Does app contain ads? **No**

### 4. Content Rating
- Complete questionnaire
- Select: Finance/Personal Finance
- Answer questions honestly
- Submit for rating

### 5. Target Audience
- Age range: 18+
- Not designed for children

### 6. News App
- Is this a news app? **No**

### 7. COVID-19 Contact Tracing
- Is this a contact tracing app? **No**

### 8. Data Safety
**Data collected:**
- Name, email (required for account)
- Phone number (optional)
- Payment handles (Cash App, Venmo)
- Financial info (payment requests, amounts)

**Data sharing:**
- None (data not shared with third parties)

**Security:**
- Data encrypted in transit (HTTPS)
- Data encrypted at rest (database encryption)
- Users can request data deletion

**Complete the Data Safety form:**
1. Click "Start"
2. Select data types collected
3. Explain how data is used
4. Submit

---

## App Store Listing (for future Production release)

### Required Assets

**App Icon:**
- ✅ Already configured: 512x512 PNG
- Shows: Teal circle with white wallet icon

**Feature Graphic:**
- Size: 1024 x 500 px
- Create one showing the app interface

**Screenshots (Required):**
- Phone: 2-8 screenshots
- Minimum dimensions: 320 x 640 px
- Recommended: 1080 x 1920 px

**Take screenshots of:**
1. Login/Registration screen
2. Groups list
3. Group details with members
4. Payment requests list
5. Profile/Settings

**Tablet Screenshots (Optional but recommended):**
- 7-inch: 1080 x 1920 px
- 10-inch: 1200 x 1920 px

### App Description

**Short description (80 chars max):**
```
Split bills together with automatic payment requests and reminders
```

**Full description (4000 chars max):**
```
TogetherPay makes group bill splitting effortless!

EASY GROUP MANAGEMENT
• Create groups for families, roommates, or friends
• Add members with custom share percentages
• Set up monthly or 4-week billing cycles
• Track who pays and who's pending

AUTOMATIC PAYMENT REQUESTS
• Generate payment requests for all members automatically
• Send via Cash App or Venmo with one tap
• Get notified when members mark payments as sent
• Confirm receipts with ease

SMART REMINDERS
• Automatic reminders for pending payments
• Push notifications keep everyone on track
• Never miss a payment deadline

DETAILED REPORTING
• View payment history for all groups
• Track on-time payment rates
• Export data to CSV for records
• See who's reliable and who needs a nudge

SECURE & PRIVATE
• End-to-end encrypted connections
• Secure authentication with 2FA support
• Your payment data stays private
• No ads, no data selling

Perfect for:
• Splitting Netflix, Spotify, and streaming services
• Roommate rent and utilities
• Family phone bills
• Shared car insurance
• Any recurring group expense!

Download TogetherPay and say goodbye to awkward payment reminders!
```

**Category:** Finance
**Tags:** bill splitting, group payments, payment requests, venmo, cash app

---

## Testing Checklist

Before releasing to testers, verify:

- [ ] App bundle uploaded (version 1.0.0+3)
- [ ] Release notes added
- [ ] All errors resolved
- [ ] Privacy policy accessible
- [ ] Test account credentials provided
- [ ] Content rating completed
- [ ] Data safety form completed
- [ ] App access information submitted
- [ ] Tester email list created
- [ ] Opt-in URL ready to share

---

## After Publishing to Closed Testing

### Monitor Feedback

1. **Pre-launch report:**
   - Check for crashes
   - Review compatibility issues
   - Address critical bugs

2. **Tester feedback:**
   - Review comments from testers
   - Track crash reports
   - Iterate and improve

3. **Update if needed:**
   - Fix bugs
   - Increment version (1.0.0+4)
   - Upload new bundle
   - Publish update

### Promote to Production

Once testing is successful:

1. **Create production release**
2. **Promote from closed testing**
3. **Complete Store Listing**
4. **Add screenshots and graphics**
5. **Submit for review**
6. **Wait for approval (1-7 days)**
7. **Release to public!**

---

## Troubleshooting

### Upload Fails
- Check file size (AAB should be ~47MB)
- Ensure proper signing (key.properties configured)
- Try re-building: `flutter clean && flutter build appbundle --release`

### Version Code Conflict
- Increment version in pubspec.yaml
- Build new bundle
- Upload new file

### Permission Errors Persist
- Complete App Content → App permissions section
- Provide justification for any permissions
- Wait 24 hours for review

### Can't Find AAB File
- Location: `mobile/build/app/outputs/bundle/release/app-release.aab`
- If missing, rebuild: `flutter build appbundle --release`

---

## Quick Commands Reference

```bash
# Navigate to project
cd /Users/amohamed/Herd/togetherpay/mobile

# Increment version in pubspec.yaml (currently 1.0.0+3)
# Edit line 4: version: 1.0.0+4

# Clean build
flutter clean

# Build release bundle
flutter build appbundle --release

# Find the AAB
ls -lh build/app/outputs/bundle/release/app-release.aab
```

---

## Next Steps

1. ✅ Upload `app-release.aab` to Play Console
2. 🔧 Fix permission declaration in App Content → App permissions
3. ✅ Add release notes
4. ✅ Add testers
5. 🚀 Start rollout to Closed testing
6. 📧 Share opt-in URL with testers
7. 📊 Monitor feedback and crashes
8. 🔄 Iterate and improve
9. 🎉 Promote to Production when ready

---

**Current Status:**
- Bundle: ✅ Ready (47.0 MB)
- Version: 1.0.0+3
- Signing: ✅ Configured
- Next: Upload to Play Console

Good luck with your closed testing! 🚀
