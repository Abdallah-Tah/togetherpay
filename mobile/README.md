# TogetherPay Mobile

Complete Flutter mobile app for TogetherPay - P2P bill splitting with authentication, groups management, and push notifications.

## Features

### Core Functionality
- 🔐 **Full Authentication System** - Login, registration, secure token storage
- 👥 **Groups Management** - View groups, group details, members, shares
- 💰 **Payment Requests** - View all payments organized by status (Overdue, Pending, Paid)
- 💸 **Deep Link Wallet Launch** (Cash App, Venmo) with prefilled amounts
- ✅ **Auto-Confirmation Dialog** - Prompts user after returning from wallet app
- 📱 **iOS + Android** support
- 🎨 **Material Design 3** - Modern, polished UI

**Note**: Push notifications have been removed from the mobile app. The app now relies entirely on Laravel API for all functionality.

### User Experience
- Pull-to-refresh on all list screens
- Empty states with helpful messaging
- Loading states and error handling
- Bottom navigation (Groups, Payments, Profile)
- Splash screen on app startup
- Logout with confirmation

---

## Architecture

### Complete Payment Flow

```
1. User opens app → Splash screen → Auth check
2. If not authenticated → Login/Register screen
3. After login → Groups list (bottom nav: Groups, Payments, Profile)
4. PayScreen can be accessed via payment list or direct link
5. PayScreen fetches details from API → Auto-launches wallet
6. User completes payment in Cash App/Venmo
7. User returns to app → AUTO-DIALOG: "Did you complete the payment?"
   - "Yes, I paid" → Calls API to mark as paid → Success → Navigate back
   - "Remind me later" → Shows reminder message
   - "I'll pay later" → Dismisses, navigates back
8. Payment status updated to `member_marked` in backend
9. Owner confirms via web/mobile → Status updated to `confirmed`
```

### App Structure

```
TogetherPayApp (root)
├── SplashScreen (initializing)
├── LoginScreen (not authenticated)
└── MainScaffold (authenticated)
    ├── Bottom Navigation
    │   ├── Tab 1: GroupsListScreen
    │   ├── Tab 2: PaymentRequestsScreen
    │   └── Tab 3: ProfileScreen
    └── Push notification → PayScreen (deep link)
```

### Key Components

- **`lib/main.dart`** - App entry, auth flow, navigation, profile screen
- **`lib/pay_screen.dart`** - Enhanced payment UI with auto-confirmation dialog
- **`lib/env.dart`** - Environment configuration
- **`lib/screens/auth/`** - Login and registration screens
- **`lib/screens/groups/`** - Groups list and group details screens
- **`lib/screens/payments/`** - Payment requests screen with status filtering
- **`lib/widgets/`** - Reusable components (GroupCard, PaymentRequestCard)

---

## Setup

### Prerequisites

- Flutter SDK 3.0+
- Xcode 14+ (iOS)
- Android Studio (Android)

### 1. Install Dependencies

```bash
cd mobile
flutter pub get
```

### 2. Configure API Base URL

#### Development (localhost)
```bash
flutter run --dart-define=API_BASE=http://localhost:8000/api/v1
```

#### Production
```bash
flutter build apk --dart-define=API_BASE=https://api.togetherpay.app/api/v1
```

Or edit `lib/env.dart` to change the default.

### 3. iOS-Specific Setup
#### URL Schemes
Already configured in `Info.plist`:
- Venmo deep link query: `venmo`
- Universal Links: `applinks:tpay.app`, `applinks:togetherpay.app`

#### Associated Domains (Optional)
If using universal links, add in Xcode:
```
applinks:tpay.app
applinks:togetherpay.app
```

### 4. Android-Specific Setup

#### Notification Icons
Place notification icons in:
- `android/app/src/main/res/mipmap-*/ic_launcher.png`

---

## Running the App

### Development
```bash
# iOS
flutter run -d iPhone

# Android
flutter run -d emulator-5554

# With API override
flutter run --dart-define=API_BASE=http://10.0.2.2:8000/api/v1
```

### Production Build

#### Android
```bash
flutter build apk --release --dart-define=API_BASE=https://api.togetherpay.app/api/v1
```

#### iOS
```bash
flutter build ios --release --dart-define=API_BASE=https://api.togetherpay.app/api/v1
```

---

## API Integration

### Required Laravel Endpoints

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/auth/login` | POST | Login with email/password, returns auth token |
| `/auth/register` | POST | Register new user, returns auth token |
| `/auth/logout` | POST | Logout and invalidate token |
| `/groups` | GET | List user's groups |
| `/groups/{id}` | GET | Get group details with members and payment requests |
| `/payment-requests` | GET | List all user's payment requests |
| `/requests/by-code/{prCode}` | GET | Get payment request details by code |
| `/requests/by-code/{prCode}/mark` | POST | Member marks payment as completed |

### Example API Responses

#### GET /requests/by-code/{prCode}
```json
{
  "id": 123,
  "pr_code": "PR_2025-11_G42_M17_a3f9c2",
  "group": {
    "id": 42,
    "name": "T-Mobile Family Plan",
    "owner": {
      "name": "John Doe",
      "cash_tag": "$JohnDoe"
    }
  },
  "amount_cents": 5000,
  "amount_display": "$50.00",
  "status": "sent",
  "method_hint": "cashapp",
  "note": "T-Mobile Nov 2025 cycle",
  "deep_link": "https://cash.app/$JohnDoe/50.00?note=...",
  "web_fallback_link": "https://cash.app/$JohnDoe/50.00?note=..."
}
```

---

## Deep Link Handling

### Supported URL Schemes

#### Cash App
```
https://cash.app/$JohnDoe/50.00?note=T-Mobile+Nov+2025+PR_2025-11_G42_M17_a3f9c2
```

#### Venmo
```
venmo://pay?recipients=johndoe&amount=50.00&note=T-Mobile+Nov+2025+PR_2025-11_G42_M17_a3f9c2
```

#### Short Links (from backend)
```
https://tpay.app/p/abCDef
→ Redirects to Cash App or Venmo deep link
```

### Wallet Detection

```dart
final uri = Uri.parse('venmo://pay?...');
if (await canLaunchUrl(uri)) {
  await launchUrl(uri, mode: LaunchMode.externalApplication);
} else {
  // Fallback to web URL or show "Install Venmo" message
}
```

---

## Testing

### Test Wallet Launching

```dart
// In PayScreen, add debug button:
ElevatedButton(
  onPressed: () async {
    final testUri = Uri.parse('https://cash.app/\$TestUser/5.00?note=Test');
    await launchUrl(testUri, mode: LaunchMode.externalApplication);
  },
  child: Text('Test Cash App'),
)
```

---

## Troubleshooting

### iOS

#### Deep links not opening
- Verify `LSApplicationQueriesSchemes` includes `venmo` in Info.plist
- Check URL can be opened: `await canLaunchUrl(uri)`

#### Build errors
- Run `cd ios && pod install`
- Clean build folder: Product → Clean Build Folder in Xcode

### Android

#### Deep links not opening
- Verify `<queries>` section in AndroidManifest.xml
- Check package names: `com.squareup.cash`, `com.venmo`

#### Build errors
```bash
cd android
./gradlew clean
cd ..
flutter clean
flutter pub get
```

---

## Production Checklist

**📋 For complete production setup instructions, see [PRODUCTION_SETUP.md](./PRODUCTION_SETUP.md)**

**🚀 For quick build commands, see [BUILD_DEPLOY.md](./BUILD_DEPLOY.md)**

**✅ For pre-release testing, see [TESTING_CHECKLIST.md](./TESTING_CHECKLIST.md)**

### iOS
- [x] Add associated domains for universal links ✓ (configured in Info.plist)
- [ ] Set up App Store Connect app (see [PRODUCTION_SETUP.md](./PRODUCTION_SETUP.md#app-store-connect-setup))
- [ ] Test on physical device (not simulator)
- [ ] Verify deep link opens Cash App/Venmo

### Android
- [x] Configure signing for release builds ✓ (build.gradle.kts updated)
- [x] Add ProGuard rules ✓ (proguard-rules.pro created)
- [ ] Set up Google Play Console app (see [PRODUCTION_SETUP.md](./PRODUCTION_SETUP.md#google-play-console-setup))
- [ ] Generate release keystore (see [PRODUCTION_SETUP.md](./PRODUCTION_SETUP.md#generate-upload-key))
- [ ] Configure SHA-256 fingerprints for App Links (see [PRODUCTION_SETUP.md](./PRODUCTION_SETUP.md#configure-sha-256-fingerprints-for-app-links))
- [ ] Test on physical device
- [ ] Verify deep link opens Cash App/Venmo

### Both Platforms
- [ ] Test payment flow end-to-end (use [TESTING_CHECKLIST.md](./TESTING_CHECKLIST.md))
- [ ] Verify API base URL is production (set via `--dart-define=API_BASE=...`)
- [ ] Test "Mark as Paid" API call
- [ ] Verify auth token storage/retrieval
- [ ] Test offline behavior
- [ ] Test wallet app not installed scenario

### Quick Build Scripts
```bash
# iOS Release Build
./build_ios_release.sh

# Android Release Build (App Bundle)
./build_android_release.sh
```

---

## Environment Variables

Set at build/run time with `--dart-define`:

| Variable | Default | Description |
|----------|---------|-------------|
| `API_BASE` | `http://localhost:8000/api/v1` | Laravel API base URL |
| `DEBUG` | `true` | Enable debug logging |

Example:
```bash
flutter run --dart-define=API_BASE=https://api.togetherpay.app/api/v1 --dart-define=DEBUG=false
```

---

## Project Structure

```
mobile/
├── lib/
│   ├── main.dart                              # App entry, navigation, auth flow, profile
│   ├── pay_screen.dart                        # Enhanced payment screen with auto-confirm
│   ├── env.dart                               # Environment config
│   ├── screens/
│   │   ├── auth/
│   │   │   ├── login_screen.dart             # Login with email/password
│   │   │   └── register_screen.dart          # User registration
│   │   ├── groups/
│   │   │   ├── groups_list_screen.dart       # List of user's groups
│   │   │   └── group_details_screen.dart     # Group info, members, payments
│   │   └── payments/
│   │       └── payment_requests_screen.dart  # All payment requests by status
│   └── widgets/
│       ├── group_card.dart                    # Reusable group card widget
│       └── payment_request_card.dart          # Reusable payment request card
├── ios/
│   └── Runner/
│       └── Info.plist                         # iOS config, URL schemes
├── android/
│   └── app/
│       ├── build.gradle                       # App build configuration
│       └── src/main/
│           └── AndroidManifest.xml           # Permissions, deep links
└── pubspec.yaml                               # Dependencies
```

---

## Testing the Complete App

### Manual Testing Flow

1. **Authentication**
   ```bash
   flutter run
   ```
   - Register new account
   - Verify token stored
   - Logout and login again
   - Test "Remember me" functionality

2. **Groups Management**
   - View groups list (empty state if no groups)
   - Tap a group → View details
   - See members list with shares
   - See recent payment requests

3. **Payment Requests**
   - Switch to "Payments" tab in bottom nav
   - See requests grouped by status (Overdue, Pending, Paid)
   - Tap a payment request → Opens PayScreen

4. **Payment Flow**
   - From PayScreen, tap "Open Wallet"
   - Cash App/Venmo should open with prefilled amount
   - Complete payment in wallet app
   - Return to TogetherPay app
   - **NEW:** Dialog appears: "Did you complete the payment?"
   - Tap "Yes, I paid" → API call → Success message → Navigate back
   - Verify payment status updated in lists

5. **Push Notifications**
   - Send test notification from Firebase Console
   - Tap notification → App opens PayScreen
   - Complete payment flow as above

6. **Profile & Settings**
   - Switch to "Profile" tab
   - View user info
   - Tap settings options (coming soon placeholders)
   - Tap "Logout" → Confirm → Returns to login screen

### API Endpoint Testing

Test backend endpoints with curl:

```bash
# Register new user
curl -X POST http://localhost:8000/api/v1/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Test User",
    "email": "test@example.com",
    "password": "password123",
    "password_confirmation": "password123"
  }'

# Login
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com",
    "password": "password123"
  }'

# Get groups (with auth token)
curl http://localhost:8000/api/v1/groups \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"

# Get payment requests
curl http://localhost:8000/api/v1/payment-requests \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

## Next Steps

1. **Set up Firebase** - Add iOS/Android apps, download config files
2. **Configure backend** - Implement all API endpoints (auth, groups, payment-requests)
3. **Test locally** - Run with `--dart-define=API_BASE=http://localhost:8000/api/v1`
4. **Test authentication** - Register → Login → Logout flow
5. **Test groups** - View groups list and details
6. **Test payments** - View payment requests, tap to pay
7. **Test push** - Send test notification from Firebase Console
8. **Test complete payment flow** - Push notification → Pay → Auto-confirm → Success
9. **Deploy** - Build release APK/IPA with production API URL

## Implemented Features Summary

### Authentication System ✅
- Login screen with email/password
- Registration screen with validation
- Secure token storage (FlutterSecureStorage)
- Auto-login on app startup
- Logout with confirmation
- Auth header injection in all API calls

### Groups Management ✅
- Groups list with pull-to-refresh
- Group cards showing member count and pending payments
- Group details with:
  - Group info (name, description, owner)
  - Members list with avatars and share amounts
  - Recent payment requests
  - Owner badge
  - "You" indicator for current user

### Payment Requests ✅
- Payment requests list with pull-to-refresh
- Organized by status: Overdue, Pending, Paid
- Color-coded status badges
- Due dates display
- Quick "Pay" button
- Navigate to PayScreen on tap

### Enhanced PayScreen ✅
- Auto-launch wallet on load
- Manual "Open Wallet" button
- **Auto-confirmation dialog** when returning from wallet
- Three dialog options:
  - "Yes, I paid" - Confirms via API
  - "Remind me later" - Shows reminder message
  - "I'll pay later" - Dismisses
- Payment details display
- Step-by-step instructions
- PR code reference

### Navigation & UX ✅
- Splash screen during initialization
- Conditional routing (Login vs Main App)
- Bottom navigation (Groups, Payments, Profile)
- Profile screen with settings and logout
- Pull-to-refresh on all lists
- Loading states (CircularProgressIndicator)
- Error states with "Try Again" buttons
- Empty states with helpful messaging
- Material Design 3 theming

### Push Notifications ✅
- FCM background message handler
- Foreground local notifications
- App opened from notification handler
- Deep link navigation to PayScreen
- FCM token registration with backend

### Reusable Components ✅
- GroupCard widget
- PaymentRequestCard widget
- Consistent styling and theming
- Proper Material Design 3 usage

## Future Enhancements

- [ ] Create group functionality
- [ ] Edit group settings
- [ ] Invite members to groups
- [ ] Local reminder notifications (5 min after "Remind me later")
- [ ] User profile editing
- [ ] Password reset flow
- [ ] Payment method management
- [ ] Search/filter groups and payments
- [ ] Payment history and receipts
- [ ] Export reports (CSV/PDF)
- [ ] State management with Riverpod
- [ ] Unit and widget tests
- [ ] Biometric authentication

---

## Support

- **Backend API:** See [../ARCHITECTURE_V2.md](../ARCHITECTURE_V2.md)
- **Quick Start:** See [../V2_QUICKSTART.md](../V2_QUICKSTART.md)
- **Flutter Docs:** https://docs.flutter.dev
- **Firebase Docs:** https://firebase.google.com/docs/flutter/setup

---

**Version:** 1.0.0
**Platform:** Flutter 3.0+
**Min iOS:** 12.0
**Min Android:** 23 (Android 6.0)
