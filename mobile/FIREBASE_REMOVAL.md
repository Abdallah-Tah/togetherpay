# Firebase Removal from Mobile App

## Overview

The TogetherPay mobile app has been updated to remove all Firebase SDK dependencies. The app now relies entirely on the Laravel backend API for all functionality, including push notifications.

## Changes Made

### 1. Dependencies Removed
- `firebase_core` (^2.24.2)
- `firebase_messaging` (^14.7.10)

### 2. Code Changes
- Removed Firebase initialization from `main.dart`
- Removed FCM token management and notification handling code
- Removed Firebase environment variables from `env.dart`
- Removed Firebase imports from all files

### 3. Configuration Files Removed
- `android/app/google-services.json`
- `ios/Runner/GoogleService-Info.plist`
- `firebase.json`
- `firebase_options.dart.disabled`
- All `*.firebase_disabled` directories and files

### 4. Build Configuration Updates
- **Android**: Removed Google Services plugin and Firebase dependencies from build files
  - `android/app/build.gradle`
  - `android/build.gradle.kts`
  - `android/settings.gradle.kts`

## Push Notifications

### How It Works Now

Push notifications are now **fully managed by the Laravel backend** using Firebase Cloud Messaging (FCM) HTTP API:

1. **No Firebase SDK needed in mobile app**: The mobile app does not need Firebase SDK to receive push notifications
2. **Backend sends notifications**: Laravel backend uses `app/Channels/FcmChannel.php` to send push notifications via FCM HTTP API
3. **Device registration**: The mobile app no longer registers FCM tokens since Firebase SDK has been removed
4. **Alternative approach**: To re-enable push notifications, implement native platform notification handling or use a lighter alternative package

### Backend Implementation

The Laravel backend still sends push notifications through:
- **FCM Channel**: `app/Channels/FcmChannel.php`
- **Notifications**: 
  - `app/Notifications/PaymentRequestNotification.php`
  - `app/Notifications/PaymentReminderNotification.php`
  - `app/Notifications/CycleClosedNotification.php`
- **Device Registration**: `/api/v1/devices/register` endpoint (currently expects FCM tokens)

### Re-enabling Push Notifications (Future)

If push notifications are needed in the future, there are several options:

1. **Native Platform APIs**: Implement platform-specific notification handling using Flutter's platform channels
2. **Lighter Package**: Use a package like `flutter_local_notifications` for local notifications
3. **Alternative Push Service**: Use a different push notification service (OneSignal, Pusher, etc.)
4. **Keep Backend FCM**: The backend FCM implementation can remain unchanged regardless of client choice

## API Endpoints

The mobile app continues to use these Laravel API endpoints:

### Authentication
- `POST /api/v1/auth/register`
- `POST /api/v1/auth/login`
- `POST /api/v1/auth/logout` (protected)
- `GET /api/v1/auth/me` (protected)

### Groups
- `GET /api/v1/groups` (protected)
- `POST /api/v1/groups` (protected)
- `GET /api/v1/groups/{id}` (protected)
- `PUT /api/v1/groups/{id}` (protected)
- `DELETE /api/v1/groups/{id}` (protected)
- `POST /api/v1/groups/{group}/members` (protected)
- `PUT /api/v1/groups/{group}/members/{member}` (protected)
- `DELETE /api/v1/groups/{group}/members/{member}` (protected)

### Payment Requests
- `GET /api/v1/payment-requests` (protected)
- `GET /api/v1/payment-requests/{id}` (protected)
- `GET /api/v1/requests/by-code/{prCode}` (public)
- `POST /api/v1/requests/by-code/{prCode}/mark` (public)
- `POST /api/v1/requests/by-code/{prCode}/confirm` (protected)
- `POST /api/v1/payment-requests/{id}/mark-paid` (protected)

### Device Registration (Currently Unused)
- `POST /api/v1/devices/register` (public)

**Note**: The device registration endpoint is no longer used by the mobile app since Firebase has been removed. If push notifications are re-implemented, this endpoint may need to be updated or replaced.

## Testing

To verify the app works without Firebase:

```bash
# Navigate to mobile directory
cd mobile

# Get dependencies
flutter pub get

# Run the app (with your API base URL)
flutter run --dart-define=API_BASE=https://your-api-url.com/api/v1
```

Expected behavior:
- ✅ App launches successfully
- ✅ Authentication works (login/register)
- ✅ Groups management works
- ✅ Payment requests work
- ⚠️ Push notifications will not work (requires re-implementation if needed)

## Breaking Changes

### For Users
- Push notifications will not work until re-implemented
- All other features continue to work normally

### For Developers
- No Firebase SDK initialization required
- No Firebase configuration files needed
- Simpler build process without Firebase dependencies
- Backend FCM implementation can continue to work independently

## Future Considerations

1. **Push Notifications**: Decide if push notifications are needed and choose implementation approach
2. **Device Registration**: Update or remove `/api/v1/devices/register` endpoint if not needed
3. **Backend Cleanup**: Consider if backend FCM channel is still needed without mobile push support
4. **Alternative Notifications**: Consider using email/SMS as primary notification channels

## Migration Guide

For other developers working on this project:

1. **Pull latest changes**: `git pull origin <branch>`
2. **Clean Flutter**: `flutter clean`
3. **Get dependencies**: `flutter pub get`
4. **Clean Android**: `cd android && ./gradlew clean && cd ..`
5. **Run app**: `flutter run --dart-define=API_BASE=<your-api-url>`

## Rollback (If Needed)

To rollback to Firebase-based push notifications:

1. Revert the commit that removed Firebase
2. Add back Firebase configuration files (google-services.json, GoogleService-Info.plist)
3. Run `flutter pub get`
4. Clean and rebuild the app

## Questions?

Contact the development team or refer to:
- Laravel API documentation: `routes/api.php`
- Backend notification implementation: `app/Channels/FcmChannel.php`
- Mobile API integration guide: `mobile/README_API_INTEGRATION.md`
