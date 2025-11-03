# Pre-Release Testing Checklist

Complete this checklist on physical devices before submitting to App Store / Play Store.

**Version**: _________  
**Build Number**: _________  
**Test Date**: _________  
**Tester**: _________

---

## Test Devices

### iOS
- [ ] iPhone model: _________ (iOS version: ________)
- [ ] iPad model: _________ (iOS version: ________)

### Android
- [ ] Device model: _________ (Android version: ________)
- [ ] Device model: _________ (Android version: ________)

---

## 1. Installation & Setup

### iOS
- [ ] App installs successfully from TestFlight
- [ ] App icon displays correctly
- [ ] Splash screen displays on first launch
- [ ] Permissions requested appropriately (notifications)

### Android
- [ ] App installs successfully from Play Console Internal Testing
- [ ] App icon displays correctly
- [ ] Splash screen displays on first launch
- [ ] Notification permission requested (Android 13+)
- [ ] Notification permission can be granted

---

## 2. Authentication

- [ ] Register new account with valid credentials
  - Email: _________________
  - Password: Strong password (8+ chars)
- [ ] Registration success message displayed
- [ ] Automatically logged in after registration
- [ ] Auth token stored securely (app restart works)
- [ ] Logout functionality works
- [ ] Logout confirmation dialog appears
- [ ] Login with existing account
- [ ] Login with incorrect password shows error
- [ ] Login with non-existent email shows error
- [ ] "Remember me" persists after app restart
- [ ] Token refresh works (if applicable)
- [ ] Force logout on 401 Unauthorized error

**Notes**: _________________________________________________

---

## 3. Groups Management

### Groups List
- [ ] Empty state displays when no groups exist
- [ ] Empty state message is helpful
- [ ] Pull-to-refresh works correctly
- [ ] Pull-to-refresh shows loading indicator
- [ ] Groups list populated with test data
- [ ] Group cards display correct information:
  - [ ] Group name
  - [ ] Number of members
  - [ ] Number of pending payments
- [ ] Tap group card navigates to group details

### Group Details
- [ ] Group name displays correctly
- [ ] Group description displays (if present)
- [ ] Owner name displays with badge
- [ ] Current user shows "You" indicator
- [ ] Members list displays all group members
- [ ] Member avatars display (or initials)
- [ ] Member share amounts display correctly
- [ ] Recent payment requests show below members
- [ ] Can navigate back to groups list
- [ ] Pull-to-refresh works on details screen

**Notes**: _________________________________________________

---

## 4. Payment Requests

### Payment List
- [ ] Pull-to-refresh works correctly
- [ ] Payments organized by status sections:
  - [ ] Overdue (red badge)
  - [ ] Pending (yellow badge)
  - [ ] Paid (green badge)
- [ ] Empty state for each section when no payments
- [ ] Payment cards display correct information:
  - [ ] Amount
  - [ ] Group name
  - [ ] Due date
  - [ ] Status badge
- [ ] Due date formatting correct
- [ ] Overdue payments highlighted appropriately
- [ ] Tap payment navigates to PayScreen

**Notes**: _________________________________________________

---

## 5. Payment Flow - Cash App

### PayScreen
- [ ] Payment details load from API
- [ ] Loading indicator displays while fetching
- [ ] Payment information displays correctly:
  - [ ] Amount
  - [ ] Group name
  - [ ] Owner name
  - [ ] Owner Cash tag
  - [ ] PR code reference
  - [ ] Due date
- [ ] "Open Wallet" button visible
- [ ] Instructions clear and helpful

### Cash App Integration
- [ ] Tap "Open Wallet" launches Cash App automatically
- [ ] Cash App launches on initial screen load (auto-launch)
- [ ] Cash App not installed: Shows appropriate message
- [ ] Amount prefilled correctly in Cash App
- [ ] Recipient Cash tag correct
- [ ] Note/memo includes PR code
- [ ] Complete payment in Cash App
- [ ] Return to TogetherPay app

### Auto-Confirmation Dialog
- [ ] Dialog appears automatically upon return
- [ ] Dialog shows three options:
  - [ ] "Yes, I paid"
  - [ ] "Remind me later"
  - [ ] "I'll pay later"
- [ ] Tap "Yes, I paid":
  - [ ] API call executes (`/mark` endpoint)
  - [ ] Success message displays
  - [ ] Navigate back to payment list
  - [ ] Payment status updated in list
- [ ] Tap "Remind me later":
  - [ ] Shows reminder message
  - [ ] Dialog dismisses
- [ ] Tap "I'll pay later":
  - [ ] Dialog dismisses
  - [ ] Navigate back to previous screen

**Test Amount**: $___________  
**Test PR Code**: _________________  
**Test Cash Tag**: _________________

**Notes**: _________________________________________________

---

## 6. Payment Flow - Venmo

### Venmo Integration
- [ ] Venmo deep link works (venmo://pay?...)
- [ ] Venmo app launches correctly
- [ ] Amount prefilled correctly
- [ ] Recipient username correct
- [ ] Note includes PR code
- [ ] Complete payment in Venmo
- [ ] Return to app triggers auto-confirmation dialog
- [ ] "Yes, I paid" flow works same as Cash App

### Venmo Not Installed
- [ ] Fallback behavior works (web URL or install prompt)
- [ ] User can complete action

**Test Amount**: $___________  
**Test Venmo Username**: _________________

**Notes**: _________________________________________________

---

## 7. Push Notifications

### App Closed
- [ ] Send test notification via Firebase Console
- [ ] Notification received on device
- [ ] Notification title displays correctly
- [ ] Notification body displays correctly
- [ ] Notification icon displays correctly
- [ ] Tap notification opens app
- [ ] App navigates to correct PayScreen
- [ ] Payment details load from notification data
- [ ] Payment flow works from notification

### App in Background
- [ ] Notification received while app backgrounded
- [ ] Tap notification brings app to foreground
- [ ] Navigates to PayScreen correctly

### App in Foreground
- [ ] Notification received while app in use
- [ ] Local notification displayed (iOS/Android)
- [ ] Tap foreground notification navigates to PayScreen

### Notification Content
- [ ] PR code from notification data correct
- [ ] Deep link from notification data correct
- [ ] Notification data parsed correctly

**Test Notification Title**: _________________________  
**Test PR Code**: _________________  
**Test Deep Link**: _________________

**Notes**: _________________________________________________

---

## 8. Deep Links / Universal Links

### Universal Links (iOS)
- [ ] Open `https://tpay.app/p/abCDef` in Safari
- [ ] Link opens app (not Safari)
- [ ] Navigates to correct PayScreen
- [ ] Payment details load from API
- [ ] Test from Messages app
- [ ] Test from Mail app
- [ ] Test from third-party app

### App Links (Android)
- [ ] Open `https://tpay.app/p/abCDef` in Chrome
- [ ] Link opens app (not browser)
- [ ] Navigates to correct PayScreen
- [ ] Test from Gmail app
- [ ] Test from Messages app
- [ ] Test with `autoVerify="true"` working

### Custom Scheme
- [ ] `togetherpay://` custom scheme works (if implemented)

**Test Short Link**: _________________  
**Test Payment Details**: _________________

**Notes**: _________________________________________________

---

## 9. Error Handling

### Network Errors
- [ ] Enable Airplane mode
- [ ] Try to load groups list → Error message displays
- [ ] Error message is user-friendly
- [ ] "Retry" or "Try Again" button available
- [ ] Disable Airplane mode
- [ ] Retry loads data successfully

### API Errors
- [ ] Test with invalid PR code
- [ ] Shows appropriate error message
- [ ] Test with expired auth token (if possible)
- [ ] Forces logout on 401 error
- [ ] Test with 500 server error
- [ ] Shows generic error with retry option

### Validation Errors
- [ ] Login with empty fields shows validation
- [ ] Registration with weak password shows error
- [ ] Registration with mismatched passwords shows error

**Notes**: _________________________________________________

---

## 10. Wallet App Scenarios

### Cash App Not Installed
- [ ] Shows appropriate message
- [ ] Option to install Cash App (link to App/Play Store)
- [ ] Can continue without Cash App (web fallback if available)

### Venmo Not Installed
- [ ] Shows appropriate message
- [ ] Option to install Venmo
- [ ] Fallback behavior works

### Wallet App Installed But Login Required
- [ ] User can login in wallet app
- [ ] Return to TogetherPay works correctly

**Notes**: _________________________________________________

---

## 11. UI/UX

### General UI
- [ ] App follows Material Design 3 guidelines
- [ ] Color scheme consistent throughout
- [ ] Typography readable and consistent
- [ ] Spacing and padding appropriate
- [ ] Touch targets adequate size (44x44 min)
- [ ] Buttons have visual feedback on press

### Navigation
- [ ] Bottom navigation works correctly
- [ ] Tab transitions smooth
- [ ] Back button works as expected
- [ ] Can navigate back from all screens
- [ ] Deep navigation doesn't break tab state

### Animations
- [ ] Splash screen animation smooth
- [ ] Screen transitions smooth
- [ ] Pull-to-refresh animation smooth
- [ ] Loading indicators visible and appropriate
- [ ] No janky or stuttering animations

### Empty States
- [ ] All empty states have helpful messages
- [ ] Icons/illustrations appropriate
- [ ] Call-to-action buttons where relevant

### Loading States
- [ ] Loading indicators display during API calls
- [ ] Skeleton loaders or spinners appropriate
- [ ] UI doesn't block while loading
- [ ] Timeout handled gracefully

**Notes**: _________________________________________________

---

## 12. Performance

### App Launch
- [ ] Cold start: App launches in < 3 seconds
- [ ] Warm start: App resumes quickly
- [ ] Splash screen doesn't linger too long

### List Scrolling
- [ ] Groups list scrolls smoothly
- [ ] Payment list scrolls smoothly
- [ ] No lag or stutter during scroll
- [ ] Images load without blocking scroll

### Memory
- [ ] No memory warnings during testing
- [ ] App doesn't crash under normal use
- [ ] Background doesn't cause issues

### Battery
- [ ] App doesn't drain battery excessively
- [ ] No continuous background activity (unless expected)

**Notes**: _________________________________________________

---

## 13. Offline Behavior

- [ ] Enable Airplane mode
- [ ] Previously loaded data displays (cached)
- [ ] Appropriate offline message shown
- [ ] Can navigate within cached data
- [ ] Disable Airplane mode
- [ ] Data refreshes automatically or on pull-to-refresh
- [ ] Queued actions execute when back online (if applicable)

**Notes**: _________________________________________________

---

## 14. Security

### Data Storage
- [ ] Auth token stored securely (FlutterSecureStorage)
- [ ] No sensitive data in logs (check with device logs)
- [ ] No hardcoded credentials in app
- [ ] No API keys exposed in client code

### Network
- [ ] All API calls use HTTPS
- [ ] Certificate pinning (if implemented)
- [ ] No data transmitted over HTTP

### Authentication
- [ ] Logout clears all stored tokens
- [ ] Session expires appropriately
- [ ] Re-authentication required after logout

**Notes**: _________________________________________________

---

## 15. Configuration

### Production Configuration
- [ ] API base URL set to production: `https://api.togetherpay.app/api/v1`
- [ ] Debug logging disabled
- [ ] Firebase production configuration active
- [ ] Push notifications use production certificates/keys

### iOS Specific
- [ ] Bundle ID: `com.togetherpay.togetherpay`
- [ ] Version number correct in Info.plist
- [ ] Build number incremented
- [ ] Entitlements correct:
  - [ ] Push Notifications (production)
  - [ ] Associated Domains
  - [ ] Background Modes

### Android Specific
- [ ] Package name: `com.togetherpay.togetherpay`
- [ ] Version code incremented
- [ ] Version name correct
- [ ] Signed with release keystore
- [ ] ProGuard/R8 enabled
- [ ] Code shrinking working correctly

**Notes**: _________________________________________________

---

## 16. Platform-Specific Features

### iOS
- [ ] Haptic feedback (if implemented)
- [ ] Dark mode support (if implemented)
- [ ] Safe area handling (notch/home indicator)
- [ ] Status bar style appropriate
- [ ] Keyboard handling correct

### Android
- [ ] Material Design components correct
- [ ] Navigation bar handling
- [ ] Multi-window support (if applicable)
- [ ] Back button behavior correct
- [ ] Android 13+ notification permission flow

**Notes**: _________________________________________________

---

## 17. Accessibility (Optional but Recommended)

- [ ] VoiceOver/TalkBack navigation works
- [ ] All images have content descriptions
- [ ] Text scales appropriately
- [ ] Color contrast sufficient
- [ ] Touch targets adequate size
- [ ] Focus order logical

**Notes**: _________________________________________________

---

## 18. Critical Issues Found

List any critical issues that must be fixed before release:

1. _______________________________________________
2. _______________________________________________
3. _______________________________________________

---

## 19. Minor Issues Found

List any minor issues that can be addressed in future updates:

1. _______________________________________________
2. _______________________________________________
3. _______________________________________________

---

## 20. Overall Assessment

### Ready for Release?
- [ ] **YES** - All critical items passed, minor issues documented
- [ ] **NO** - Critical issues must be fixed first

### Tested By
Name: ___________________  
Signature: ___________________  
Date: ___________________

### Reviewed By
Name: ___________________  
Signature: ___________________  
Date: ___________________

---

## Next Steps

If ready for release:
1. [ ] Complete store listing (App Store / Play Store)
2. [ ] Upload build to App Store Connect / Play Console
3. [ ] Submit for review
4. [ ] Monitor status and respond to any issues
5. [ ] Plan phased/staged rollout

If NOT ready:
1. [ ] Document all critical issues
2. [ ] Create tickets/tasks for fixes
3. [ ] Fix issues and rebuild
4. [ ] Re-test affected areas
5. [ ] Complete this checklist again

---

**Additional Notes**: 

_______________________________________________
_______________________________________________
_______________________________________________
_______________________________________________
_______________________________________________
