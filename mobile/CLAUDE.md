# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**TogetherPay Mobile** - Flutter mobile app for P2P bill splitting with push notifications, deep-link wallet integration (Cash App/Venmo), and secure authentication with the Laravel backend.

**Architecture:**
- **Platform:** Flutter 3.0+ (iOS + Android)
- **Backend Integration:** Laravel Sanctum API authentication via Dio HTTP client
- **Push Notifications:** Firebase Cloud Messaging (FCM) with APNs for iOS
- **Deep Links:** Cash App/Venmo wallet launching with prefilled amounts
- **Storage:** Flutter Secure Storage for auth tokens
- **State Management:** Standard Flutter StatefulWidget patterns

## Development Commands

### Setup & Dependencies
```bash
# Install Flutter dependencies
flutter pub get

# Clean and reinstall dependencies
flutter clean
flutter pub get

# Check Flutter doctor status
flutter doctor
```

### Running the App
```bash
# Run on iOS simulator
flutter run -d iPhone

# Run on Android emulator
flutter run -d emulator-5554

# Run with custom API base (development)
flutter run --dart-define=API_BASE=http://localhost:8000/api/v1

# Run with production API
flutter run --dart-define=API_BASE=https://api.togetherpay.app/api/v1 --dart-define=DEBUG=false
```

### Building for Release
```bash
# Android APK
flutter build apk --release --dart-define=API_BASE=https://api.togetherpay.app/api/v1

# Android App Bundle (for Play Store)
flutter build appbundle --release --dart-define=API_BASE=https://api.togetherpay.app/api/v1

# iOS (requires Xcode)
flutter build ios --release --dart-define=API_BASE=https://api.togetherpay.app/api/v1
```

### Testing
```bash
# Run all tests
flutter test

# Run specific test file
flutter test test/widget_test.dart

# Run integration tests
flutter test integration_test/

# Generate coverage report
flutter test --coverage
```

### Code Quality
```bash
# Analyze code for issues
flutter analyze

# Format code
dart format .

# Fix auto-fixable analysis issues
dart fix --apply
```

## Architecture Patterns

### Environment Configuration
- **Compile-time Environment Variables:** Use `--dart-define` to set `API_BASE` and `DEBUG` flags
- **Configuration Class:** `lib/env.dart` provides centralized environment access using `String.fromEnvironment()`
- **Local vs Production:** Default to localhost for development, override for production builds

### Firebase Integration
- **Initialization:** Firebase Core initialized in `main()` with platform checks (excludes web)
- **Background Messages:** Top-level `_firebaseMessagingBackgroundHandler` for FCM background processing
- **Token Registration:** Automatic FCM token registration with Laravel backend via `/devices/register`
- **Permission Handling:** Request notification permissions on app startup

### API Communication
- **HTTP Client:** Global Dio instance configured with base URL from environment
- **Authentication:** Bearer token stored in Flutter Secure Storage, automatically added to headers
- **Error Handling:** Try-catch blocks around API calls with debug logging
- **Endpoints Used:**
  - `POST /devices/register` - FCM token registration
  - `GET /requests/by-code/{prCode}` - Payment request details
  - `POST /requests/by-code/{prCode}/mark` - Mark payment as completed

### Navigation Flow
- **Push Notification → App:** FCM data contains `pr_code` and `deep_link` for direct navigation
- **Deep Navigation:** Global navigator key for push-triggered navigation to PayScreen
- **Route Structure:** MaterialPageRoute with standard push/pop navigation

### Payment Workflow
1. **Notification Reception:** FCM delivers payment request with PR code
2. **App Launch/Focus:** User taps notification, app navigates to PayScreen
3. **Payment Details:** Screen fetches details from API using PR code
4. **Wallet Launch:** Deep link opens Cash App/Venmo with prefilled amount
5. **Payment Completion:** User returns to app, taps "Mark as Paid"
6. **Status Update:** API call updates payment status to `member_marked`

## Key Files and Structure

### Core Application Files
- **`lib/main.dart`** - App initialization, Firebase setup, push notification handling, navigation
- **`lib/pay_screen.dart`** - Payment request UI, wallet launching, mark-as-paid workflow
- **`lib/env.dart`** - Environment configuration with compile-time variables

### Platform Configuration
- **`ios/Runner/Info.plist`** - iOS URL schemes for Venmo deep links, notification permissions
- **`android/app/build.gradle`** - Firebase plugin configuration, minimum SDK requirements
- **`pubspec.yaml`** - Dependencies including Firebase, Dio, Secure Storage, URL Launcher

### Firebase Configuration
- **`ios/Runner/GoogleService-Info.plist`** - iOS Firebase configuration (not committed)
- **`android/app/google-services.json`** - Android Firebase configuration (not committed)

### Local Notifications Plugin
- **`plugins/flutter_local_notifications/`** - Custom local notifications implementation for foreground messaging

## Dependencies

### Core Dependencies
- **`firebase_core`** ^2.24.2 - Firebase initialization
- **`firebase_messaging`** ^14.8.0 - Push notifications via FCM
- **`dio`** ^5.4.0 - HTTP client for API communication
- **`flutter_secure_storage`** ^9.0.0 - Secure token storage
- **`url_launcher`** ^6.2.2 - Deep link launching for wallet apps
- **`flutter_local_notifications`** (local plugin) - Foreground notification display

### Development Dependencies
- **`flutter_lints`** ^3.0.0 - Standard Flutter linting rules
- **`integration_test`** - End-to-end testing framework

## Testing Strategy

### Unit Tests
- Test environment configuration and API client setup
- Test payment request data parsing and validation
- Test deep link URL generation and validation

### Widget Tests
- Test PayScreen UI with mock payment data
- Test navigation flows and button interactions
- Test error states and loading indicators

### Integration Tests
- Test complete payment flow from notification to completion
- Test FCM token registration and API authentication
- Test wallet app launching and fallback behavior

## Configuration Requirements

### Environment Variables
Set at build/run time with `--dart-define`:

| Variable | Default | Description |
|----------|---------|-------------|
| `API_BASE` | `http://localhost:8000/api/v1` | Laravel backend API base URL |
| `DEBUG` | `true` | Enable debug logging and Firebase debug info |

### Connecting to Local Laravel Backend

When developing locally with Laravel Valet on macOS:

**The Problem:** Android emulators can't resolve `.test` domains or access `localhost` directly.

**Solutions:**

1. **Use your machine's network IP** (Recommended for network testing):
   ```bash
   # Find your IP
   ifconfig | grep "inet " | grep -v 127.0.0.1
   # Example output: inet 192.168.1.100

   # Run Flutter with your IP
   flutter run --dart-define=API_BASE=http://192.168.1.100/api/v1
   ```
   **Note:** Your Laravel Valet must be accessible on your network IP. For most Valet setups, this works by default.

2. **Use ngrok** (Recommended for quick testing):
   ```bash
   # In one terminal: Start ngrok
   ngrok http https://togetherpay.test

   # In another terminal: Run Flutter with ngrok URL
   flutter run --dart-define=API_BASE=https://abc123.ngrok.io/api/v1
   ```

3. **Use production API**:
   ```bash
   flutter run --dart-define=API_BASE=https://api.togetherpay.app/api/v1
   ```

**For iOS Simulator:** Can access `localhost` directly:
```bash
flutter run -d iPhone --dart-define=API_BASE=http://localhost/api/v1
```

### Firebase Setup Required
1. **iOS:** Add `GoogleService-Info.plist` to `ios/Runner/` and Xcode project
2. **Android:** Add `google-services.json` to `android/app/`
3. **FCM Server Key:** Configure in Laravel backend for push notifications

### Platform Permissions
- **iOS:** Push Notifications, Background App Refresh capabilities in Xcode
- **Android:** INTERNET, notification permissions automatically requested

## Common Development Tasks

### Adding New API Endpoints
1. Update Dio base configuration in `main.dart` if needed
2. Add endpoint calls in relevant screens/services
3. Handle authentication headers automatically via global Dio setup
4. Add error handling and debug logging

### Modifying Payment Flow
1. Update PayScreen UI and state management
2. Modify API integration for new endpoints
3. Update deep link URL construction for wallet apps
4. Test end-to-end flow with actual wallet apps

### Firebase Configuration Updates
1. Download new config files from Firebase Console
2. Replace existing `GoogleService-Info.plist` and `google-services.json`
3. Update iOS bundle ID or Android package name if changed
4. Test push notifications with new configuration

### Platform-Specific Debugging
- **iOS:** Use Xcode console for native logs and Firebase debug output
- **Android:** Use `flutter logs` or Android Studio logcat for debug information
- **FCM Testing:** Use Firebase Console "Cloud Messaging" for test notifications

## Security Considerations

- **Token Storage:** Auth tokens stored in platform keychain via Flutter Secure Storage
- **API Communication:** HTTPS required for production, bearer token authentication
- **Deep Link Validation:** Validate PR codes and amounts before launching wallet apps
- **Debug Logging:** Disable debug output in production builds
- **Firebase Security:** FCM tokens properly registered and managed server-side