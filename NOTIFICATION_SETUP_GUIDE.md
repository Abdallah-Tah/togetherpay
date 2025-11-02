# Notification Setup and Testing Guide

## ✅ What's Been Implemented

### Mobile App (Flutter)
- ✅ Firebase Cloud Messaging integration
- ✅ Notification permissions request (iOS)
- ✅ FCM token registration with backend
- ✅ Foreground, background, and terminated state handlers
- ✅ Sound enabled for both iOS and Android
- ✅ Tap handler that opens Cash App/Venmo directly
- ✅ Android notification channel configured

### Backend (Laravel)
- ✅ FCM notification channel (`app/Channels/FcmChannel.php`)
- ✅ Notification payload with sound and deep links
- ✅ Device registration endpoint
- ✅ Notification sending via FCM HTTP API

## 📋 Setup Checklist

### 1. Firebase Configuration

#### **Add Firebase Config Files** (REQUIRED)
```bash
# iOS
cp path/to/GoogleService-Info.plist mobile/ios/Runner/

# Android
cp path/to/google-services.json mobile/android/app/
```

#### **Get FCM Server Key**
1. Go to [Firebase Console](https://console.firebase.google.com)
2. Select your project
3. Navigate to **Project Settings** → **Cloud Messaging**
4. Copy the **Server key**

### 2. Backend Configuration

Add to `.env`:
```bash
FCM_SERVER_KEY=your_firebase_server_key_here
```

Add to `config/services.php`:
```php
'fcm' => [
    'server_key' => env('FCM_SERVER_KEY'),
],
```

### 3. Testing Notifications

#### **Test Push Notifications**

**From Tinker:**
```php
php artisan tinker

// Get a payment request
$pr = \App\Models\PaymentRequest::first();

// Get the member user
$user = $pr->member->user;

// Send notification
$user->notify(new \App\Notifications\PaymentRequestNotification($pr));
```

**Expected Behavior:**
1. Phone plays notification sound 🔔
2. Notification appears with payment details
3. Tapping notification opens Cash App/Venmo
4. If wallet app not installed, opens PayScreen in app

#### **Test Email Notifications**

Configure mail in `.env`:
```bash
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=hello@togetherpay.app
MAIL_FROM_NAME="TogetherPay"
```

Test:
```php
php artisan tinker

$pr = \App\Models\PaymentRequest::first();
$user = $pr->member->user;

// This should send both email AND push notification
$user->notify(new \App\Notifications\PaymentRequestNotification($pr));
```

## 🐛 Troubleshooting

### iOS Notifications Not Working

**Check Permissions:**
```dart
// In main.dart, this should print "authorized"
final settings = await messaging.requestPermission(...);
print('iOS permission: ${settings.authorizationStatus}');
```

**Check FCM Token:**
```dart
final token = await messaging.getToken();
print('FCM Token: $token');
// Should print a long string like: "dH5K3..."
```

**Check Xcode Capabilities:**
1. Open `ios/Runner.xcworkspace` in Xcode
2. Select Runner target
3. Go to **Signing & Capabilities**
4. Ensure **Push Notifications** capability is enabled
5. Ensure **Background Modes** is enabled with "Remote notifications" checked

**Check Info.plist:**
Verify these keys exist in `ios/Runner/Info.plist`:
```xml
<key>UIBackgroundModes</key>
<array>
    <string>fetch</string>
    <string>remote-notification</string>
</array>
```

### Android Notifications Not Working

**Check Google Services:**
```bash
# Verify google-services.json exists
ls -la android/app/google-services.json
```

**Check Notification Channel:**
The channel is auto-created in `MainActivity.kt`. If notifications don't have sound:
1. Uninstall the app
2. Reinstall to recreate notification channel
3. Or manually delete channel in Android settings

**Check Permissions:**
Verify in `AndroidManifest.xml`:
```xml
<uses-permission android:name="android.permission.POST_NOTIFICATIONS"/>
```

### Email Not Sending

**Check Queue:**
```bash
# Make sure queue worker is running
php artisan queue:work

# OR use sync for testing
# In .env:
QUEUE_CONNECTION=sync
```

**Check Mail Config:**
```bash
php artisan config:clear
php artisan config:cache

# Test mail
php artisan tinker
Mail::raw('Test email', function($msg) {
    $msg->to('your@email.com')->subject('Test');
});
```

**Check Logs:**
```bash
tail -f storage/logs/laravel.log
```

### FCM Token Not Registering

**Check API Endpoint:**
```bash
# Test device registration
curl -X POST http://localhost/api/v1/devices/register \
  -H "Authorization: Bearer YOUR_AUTH_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "fcm_token": "test_token",
    "platform": "ios"
  }'
```

**Check User Relationship:**
Ensure `User` model has `devices()` relationship:
```php
public function devices()
{
    return $this->hasMany(Device::class);
}
```

## 📱 Testing Deep Links

### Cash App Deep Link Format
```
cashapp://send?amount=5.00&cashtag=YourCashTag&note=Payment+for+Group
```

### Venmo Deep Link Format
```
venmo://paycharge?amount=5.00&recipients=YourVenmoUsername&note=Payment+for+Group
```

### Testing on Device:
```bash
# iOS (from Terminal)
xcrun simctl openurl booted "cashapp://send?amount=5.00&cashtag=YourCashTag"

# Android (from Terminal)
adb shell am start -a android.intent.action.VIEW -d "cashapp://send?amount=5.00&cashtag=YourCashTag"
```

## ✅ Final Verification

Test the complete flow:
1. **Send request** from web/backend
2. **Receive notification** on phone with sound
3. **Tap notification** → Opens Cash App/Venmo
4. **Complete payment** in wallet app
5. **Return to app** → Mark as paid
6. **Refresh** group details → Shows "Sent" or "Awaiting confirm" status
7. **Owner confirms** → Status changes to "Paid"

## 🎯 All Features Working

- ✅ Mark as Paid (persists after refresh)
- ✅ Cancel Request (owners only)
- ✅ Push Notifications with sound
- ✅ Email Notifications
- ✅ Deep link to Cash App/Venmo
- ✅ Owner can edit all members
- ✅ Cash App inspired UI design

Need help? Check the Laravel logs and Flutter console for debug output!
