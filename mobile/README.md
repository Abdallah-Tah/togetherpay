# TogetherPay Mobile

Flutter mobile app for TogetherPay - P2P bill splitting with push notifications.

## Features

- 🔔 **Push Notifications** (FCM/APNs) for payment requests
- 💰 **Deep Link Wallet Launch** (Cash App, Venmo) with prefilled amounts
- ✅ **Mark as Paid** workflow with owner confirmation
- 🔐 **Secure Token Storage** for Laravel Sanctum authentication
- 📱 **iOS + Android** support

---

## Architecture

### Payment Flow

```
1. Push notification →
2. App opens →
3. Fetch payment details from API →
4. Launch wallet (Cash App/Venmo) with prefilled amount + PR code →
5. Member completes payment →
6. Returns to app →
7. Taps "Mark as Paid" →
8. API updates status to `member_marked` →
9. Owner confirms via web/mobile →
10. Status updated to `owner_confirmed`
```

### Key Components

- **`lib/main.dart`** - App initialization, FCM setup, navigation
- **`lib/pay_screen.dart`** - Payment request UI with wallet launcher
- **`lib/env.dart`** - Environment configuration

---

## Setup

### Prerequisites

- Flutter SDK 3.0+
- Xcode 14+ (iOS)
- Android Studio (Android)
- Firebase project with iOS/Android apps configured

### 1. Install Dependencies

```bash
cd mobile
flutter pub get
```

### 2. Firebase Configuration

#### iOS
1. Download `GoogleService-Info.plist` from Firebase Console
2. Place in `ios/Runner/`
3. Open `ios/Runner.xcworkspace` in Xcode
4. Add `GoogleService-Info.plist` to the Runner target

#### Android
1. Download `google-services.json` from Firebase Console
2. Place in `android/app/`

### 3. Configure API Base URL

#### Development (localhost)
```bash
flutter run --dart-define=API_BASE=http://localhost:8000/api/v1
```

#### Production
```bash
flutter build apk --dart-define=API_BASE=https://api.togetherpay.app/api/v1
```

Or edit `lib/env.dart` to change the default.

### 4. iOS-Specific Setup

#### Push Notifications
1. Open `ios/Runner.xcworkspace` in Xcode
2. Select Runner target → Signing & Capabilities
3. Add **Push Notifications** capability
4. Add **Background Modes** → Enable "Remote notifications"

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

### 5. Android-Specific Setup

#### Build Configuration
Edit `android/build.gradle` (project level):

```gradle
buildscript {
    dependencies {
        classpath 'com.google.gms:google-services:4.4.0'
    }
}
```

Already configured in `android/app/build.gradle`:
```gradle
plugins {
    id 'com.google.gms.google-services'
}
```

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
| `/devices/register` | POST | Register FCM token |
| `/requests/by-code/{prCode}` | GET | Get payment request details |
| `/requests/by-code/{prCode}/mark` | POST | Member marks as paid |

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

#### POST /devices/register
```json
{
  "fcm_token": "cXyz...",
  "platform": "ios"
}
```

---

## Push Notification Format

### From Laravel (via FCM)

```json
{
  "to": "<FCM_TOKEN>",
  "notification": {
    "title": "Pay $50 for T-Mobile",
    "body": "Due today • Tap to pay"
  },
  "data": {
    "pr_code": "PR_2025-11_G42_M17_a3f9c2",
    "deep_link": "https://tpay.app/p/abCDef"
  }
}
```

### Handling in Flutter

```dart
FirebaseMessaging.onMessage.listen((message) {
  // Foreground: show local notification
});

FirebaseMessaging.onMessageOpenedApp.listen((message) {
  // Background: navigate to PayScreen
  final prCode = message.data['pr_code'];
  final deepLink = message.data['deep_link'];
  Navigator.push(context, MaterialPageRoute(
    builder: (_) => PayScreen(prCode: prCode, deepLink: deepLink)
  ));
});
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

### Test Push Notifications

#### Using Firebase Console
1. Go to Firebase Console → Cloud Messaging
2. Send test notification to device token
3. Include custom data: `{ "pr_code": "PR_TEST", "deep_link": "..." }`

#### Using curl (with FCM Server Key)
```bash
curl -X POST https://fcm.googleapis.com/fcm/send \
  -H "Authorization: key=YOUR_SERVER_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "to": "FCM_DEVICE_TOKEN",
    "notification": {
      "title": "Test Payment",
      "body": "Tap to test"
    },
    "data": {
      "pr_code": "PR_2025-10_G5_M7_test",
      "deep_link": "https://cash.app/$TestUser/5.00?note=Test"
    }
  }'
```

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

#### Push notifications not working
- Check **Capabilities → Push Notifications** enabled in Xcode
- Verify APNs key uploaded to Firebase
- Check device token registered: `print(await FirebaseMessaging.instance.getToken())`

#### Deep links not opening
- Verify `LSApplicationQueriesSchemes` includes `venmo` in Info.plist
- Check URL can be opened: `await canLaunchUrl(uri)`

#### Build errors
- Run `cd ios && pod install`
- Clean build folder: Product → Clean Build Folder in Xcode

### Android

#### Push notifications not working
- Verify `google-services.json` in `android/app/`
- Check `com.google.gms.google-services` plugin applied
- Request notification permission for Android 13+

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

### iOS
- [ ] Set up App Store Connect app
- [ ] Configure push notification certificates
- [ ] Add associated domains for universal links
- [ ] Test on physical device (not simulator)
- [ ] Verify deep link opens Cash App/Venmo
- [ ] Test background notification handling

### Android
- [ ] Set up Google Play Console app
- [ ] Configure SHA-256 fingerprints for App Links
- [ ] Upload `google-services.json`
- [ ] Test on physical device
- [ ] Verify deep link opens Cash App/Venmo
- [ ] Test notification permissions (Android 13+)

### Both Platforms
- [ ] Test payment flow end-to-end
- [ ] Verify API base URL is production
- [ ] Test "Mark as Paid" API call
- [ ] Verify auth token storage/retrieval
- [ ] Test offline behavior
- [ ] Test wallet app not installed scenario

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
│   ├── main.dart              # App entry, FCM setup, navigation
│   ├── pay_screen.dart        # Payment request UI
│   └── env.dart               # Environment config
├── ios/
│   └── Runner/
│       ├── Info.plist         # iOS config, URL schemes
│       └── GoogleService-Info.plist
├── android/
│   └── app/
│       ├── build.gradle       # Firebase plugin
│       ├── src/main/
│       │   └── AndroidManifest.xml  # Permissions, deep links
│       └── google-services.json
└── pubspec.yaml               # Dependencies
```

---

## Next Steps

1. **Set up Firebase** - Add iOS/Android apps, download config files
2. **Configure backend** - Implement `/devices/register` endpoint
3. **Test locally** - Run with `--dart-define=API_BASE=http://localhost:8000/api/v1`
4. **Test push** - Send test notification from Firebase Console
5. **Test payment flow** - Open app from push → launch wallet → mark as paid
6. **Deploy** - Build release APK/IPA with production API URL

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
