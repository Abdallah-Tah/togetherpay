# Install TogetherPay on iPhone - Complete Guide

## 🎯 Quick Overview

There are 3 ways to install the app on your iPhone:

1. **Direct USB Connection** (Recommended for testing) - 5 minutes ⚡
2. **TestFlight** (Best for beta testing) - 30 minutes
3. **Ad-hoc Distribution** (For team testing) - 20 minutes

---

## ⚡ Method 1: Direct USB Connection (Fastest)

This method is perfect for immediate testing. You'll need:
- ✅ Mac computer (you have one!)
- ✅ iPhone with USB cable
- ✅ Apple ID (free)
- ✅ Xcode installed

### Step 1: Install Xcode (if not already installed)

```bash
# Check if Xcode is installed
xcode-select --version

# If not installed, download from App Store
# Or install via command line:
xcode-select --install
```

**Download Xcode from App Store:**
- Open App Store
- Search "Xcode"
- Click "Get" (it's free, ~15GB download)
- Wait for installation to complete

### Step 2: Set Up Apple Developer Account (Free)

1. Open Xcode
2. Go to **Xcode > Settings > Accounts**
3. Click the **"+"** button
4. Select **"Apple ID"**
5. Sign in with your Apple ID
6. Click **"Manage Certificates"**
7. Click **"+"** → **"Apple Development"**
8. Click **"Done"**

### Step 3: Connect Your iPhone

1. **Connect iPhone to Mac** via USB cable
2. **Unlock your iPhone**
3. **Trust this computer** (popup will appear on iPhone)
   - Tap "Trust"
   - Enter your iPhone passcode

### Step 4: Configure the Project

```bash
cd /Users/amohamed/Herd/togetherpay/mobile

# Open in Xcode
open ios/Runner.xcworkspace
```

**In Xcode:**

1. **Select your iPhone** from the device dropdown (top toolbar)
2. Click on **"Runner"** in the left sidebar (project navigator)
3. Go to **"Signing & Capabilities"** tab
4. **Uncheck** "Automatically manage signing" first
5. **Check** "Automatically manage signing" again
6. Select your **Team** (your Apple ID)
7. **Bundle Identifier**: Change to unique ID
   - Click on `com.example.togetherpay`
   - Change to: `com.yourname.togetherpay` (e.g., `com.amohamed.togetherpay`)
8. Xcode will automatically create a provisioning profile

### Step 5: Build and Run

**Option A: From Flutter CLI (Recommended)**

```bash
cd /Users/amohamed/Herd/togetherpay/mobile

# List connected devices
flutter devices

# Run on your iPhone (will build and install)
flutter run --dart-define=API_BASE=https://georgiann-semiacademic-never.ngrok-free.dev/api/v1
```

**Option B: From Xcode**

1. Click the **Play button** (▶) in Xcode
2. Wait for build to complete (~2-5 minutes first time)
3. App will install and launch on your iPhone

### Step 6: Trust the Developer Certificate (First Time Only)

When you first launch the app, you'll see:
```
"Untrusted Developer"
This app cannot be opened because the developer cannot be verified.
```

**Fix this:**
1. Go to **Settings** on iPhone
2. Tap **General**
3. Tap **VPN & Device Management** (or **Profiles & Device Management**)
4. Tap your email/Apple ID under "Developer App"
5. Tap **"Trust [Your Apple ID]"**
6. Tap **"Trust"** again to confirm
7. Return to home screen and open the app

### Step 7: Configure API URL (if needed)

If you want to change the API URL later without rebuilding:

```bash
flutter run --dart-define=API_BASE=https://your-api-url.com/api/v1 --dart-define=DEBUG=true
```

---

## 📱 Method 2: TestFlight (Best for Beta Testing)

TestFlight is Apple's official beta testing platform. Great for sharing with team or testers.

### Prerequisites

- Apple Developer Program membership ($99/year)
- OR free Apple Developer account (limited to 100 devices)

### Step 1: Enroll in Apple Developer Program

**Option A: Paid ($99/year) - Full features**
1. Go to https://developer.apple.com/programs/
2. Click "Enroll"
3. Sign in with Apple ID
4. Complete enrollment (credit card required)
5. Wait for approval (~24-48 hours)

**Option B: Free - Limited to personal testing**
1. Sign in to https://developer.apple.com
2. Agree to terms
3. Can test on your own devices only (no external testers)

### Step 2: Create App Store Connect Record

1. Go to https://appstoreconnect.apple.com
2. Click **"My Apps"**
3. Click **"+"** → **"New App"**
4. Fill in:
   - **Platform:** iOS
   - **Name:** TogetherPay
   - **Primary Language:** English
   - **Bundle ID:** Create new → `com.yourname.togetherpay`
   - **SKU:** `togetherpay-001`
   - **User Access:** Full Access
5. Click **"Create"**

### Step 3: Configure iOS Project

```bash
cd /Users/amohamed/Herd/togetherpay/mobile
```

**Update `ios/Runner/Info.plist`:**

Already configured! Just verify the app name is correct.

**Update Bundle ID in Xcode:**
```bash
open ios/Runner.xcworkspace
```

1. Select "Runner" project
2. Go to "Signing & Capabilities"
3. Change Bundle Identifier to match App Store Connect
4. Select your Team

### Step 4: Archive and Upload

**Option A: Using Flutter CLI**

```bash
# Clean previous builds
flutter clean
flutter pub get

# Build for release
flutter build ipa --dart-define=API_BASE=https://your-production-api.com/api/v1

# The IPA will be at: build/ios/ipa/togetherpay.ipa
```

**Option B: Using Xcode**

1. Select **"Any iOS Device (arm64)"** from device dropdown
2. Go to **Product** → **Archive**
3. Wait for archive to complete (~5-10 minutes)
4. Click **"Distribute App"**
5. Select **"TestFlight & App Store"**
6. Click **"Next"** through all steps
7. Click **"Upload"**

### Step 5: Add Testers in TestFlight

1. Go to https://appstoreconnect.apple.com
2. Click **"My Apps"** → **"TogetherPay"**
3. Click **"TestFlight"** tab
4. Wait for build to process (~15-30 minutes)
5. Click on the build number
6. Add testers:
   - **Internal Testers:** Up to 100 (your team)
   - **External Testers:** Up to 10,000 (requires beta app review)

### Step 6: Install via TestFlight

**On iPhone:**
1. Install **"TestFlight"** app from App Store
2. Open email invitation from Apple
3. Click **"View in TestFlight"**
4. Tap **"Install"**
5. App installs and appears on home screen

---

## 🔧 Method 3: Ad-hoc Distribution (Team Testing)

For distributing to specific devices without TestFlight.

### Step 1: Register Devices

1. Connect iPhone and get UDID:
   ```bash
   # Get iPhone UDID
   idevice_id -l

   # Or use Xcode
   # Window > Devices and Simulators > Copy Identifier
   ```

2. Register in Apple Developer Portal:
   - Go to https://developer.apple.com/account/resources/devices
   - Click **"+"**
   - Enter Device Name and UDID
   - Click **"Continue"**

### Step 2: Create Ad-hoc Provisioning Profile

1. Go to https://developer.apple.com/account/resources/profiles
2. Click **"+"**
3. Select **"Ad Hoc"**
4. Select App ID
5. Select Certificate
6. Select Devices
7. Download profile

### Step 3: Build Ad-hoc IPA

```bash
cd /Users/amohamed/Herd/togetherpay/mobile

# Build ad-hoc
flutter build ipa --release --export-options-plist=ios/ExportOptions.plist
```

### Step 4: Distribute IPA

**Option A: Via Diawi (Easiest)**
1. Go to https://www.diawi.com
2. Upload your IPA file
3. Share the link with testers
4. They open link on iPhone → Install

**Option B: Via Email**
1. Email IPA file to tester
2. They open on iPhone
3. Tap to install

**Option C: Via Apple Configurator**
1. Install Apple Configurator 2 from App Store
2. Connect iPhone
3. Drag IPA to device

---

## 🚀 Quick Start (Recommended for You)

Since you want to test NOW, here's the fastest way:

### 1. One-Time Setup (5 minutes)

```bash
# Make sure you're in the mobile directory
cd /Users/amohamed/Herd/togetherpay/mobile

# Clean and get dependencies
flutter clean
flutter pub get

# Open Xcode
open ios/Runner.xcworkspace
```

**In Xcode:**
1. Connect your iPhone via USB
2. Select your iPhone from device dropdown
3. Go to Runner → Signing & Capabilities
4. Check "Automatically manage signing"
5. Select your Apple ID as Team
6. Change Bundle ID to: `com.amohamed.togetherpay` (or your name)

### 2. Build and Run (2 minutes)

```bash
# Run on connected iPhone
flutter run --dart-define=API_BASE=https://georgiann-semiacademic-never.ngrok-free.dev/api/v1

# Or for production API
flutter run --dart-define=API_BASE=https://your-production-url.com/api/v1
```

### 3. Trust Developer (1 minute - First time only)

On iPhone:
1. Settings → General → VPN & Device Management
2. Tap your email
3. Tap "Trust"
4. Open the app!

**Total time: ~8 minutes! ⚡**

---

## 🔥 Hot Reload (During Development)

Once installed, you can make changes and see them instantly:

```bash
# Keep this terminal open
flutter run --dart-define=API_BASE=your-api-url

# Make changes to your code
# Press 'r' in terminal to hot reload
# Press 'R' to hot restart
# Press 'q' to quit
```

---

## 🐛 Troubleshooting

### "No iOS devices found"

```bash
# Install ios-deploy
brew install ios-deploy

# Check devices
flutter devices

# Restart iPhone and Mac
```

### "Could not find an option named 'dart-define'"

```bash
# Update Flutter
flutter upgrade

# Or use older syntax
flutter run --release
```

### "Signing certificate not found"

1. Open Xcode → Settings → Accounts
2. Select your Apple ID
3. Click "Download Manual Profiles"
4. Try building again

### "The maximum number of apps for free development profiles has been reached"

**Solution 1:** Delete old test apps from iPhone
1. Settings → General → VPN & Device Management
2. Delete old profiles

**Solution 2:** Join Apple Developer Program ($99/year)

### "Untrusted Developer"

1. Settings → General → VPN & Device Management
2. Trust your developer certificate

### Build fails with "Pod install failed"

```bash
cd ios
pod deintegrate
pod install
cd ..
flutter clean
flutter pub get
flutter run
```

---

## 📝 Configuration Checklist

Before building, make sure:

- [ ] Xcode is installed and updated
- [ ] iPhone is connected via USB
- [ ] iPhone is unlocked and trusted
- [ ] Apple ID is signed in to Xcode
- [ ] Bundle ID is unique (`com.yourname.togetherpay`)
- [ ] Signing is configured (automatic or manual)
- [ ] API_BASE is set correctly
- [ ] Flutter dependencies are installed (`flutter pub get`)

---

## 🎯 Production Build Checklist

When ready for production:

- [ ] Update version in `pubspec.yaml`
- [ ] Set API_BASE to production URL
- [ ] Set DEBUG=false
- [ ] Update app icons
- [ ] Update splash screen
- [ ] Test on multiple iOS versions
- [ ] Test on different iPhone models
- [ ] Run `flutter analyze` (no errors)
- [ ] Run `flutter test` (all pass)
- [ ] Build release IPA
- [ ] Upload to TestFlight
- [ ] Test TestFlight build
- [ ] Submit for App Store review

---

## 📱 Device Requirements

### Minimum Requirements
- **iOS:** 12.0 or later (check `ios/Podfile` → platform :ios)
- **iPhone:** iPhone 6S or newer
- **Storage:** 50MB+ free space

### Recommended
- **iOS:** 15.0 or later
- **iPhone:** iPhone X or newer
- **Storage:** 100MB+ free space

---

## 🔐 Security Notes

### Development Build
- Includes debug symbols
- Larger file size
- Hot reload enabled
- Not optimized

### Release Build
- Optimized and minified
- Smaller file size
- No debug symbols
- Production ready

**For testing:** Use development build
**For distribution:** Use release build

---

## 📞 Need Help?

### Common Commands

```bash
# Check Flutter setup
flutter doctor -v

# List devices
flutter devices

# Clean build
flutter clean

# Get dependencies
flutter pub get

# Run on iPhone
flutter run

# Build release
flutter build ios --release

# Check for issues
flutter analyze

# Run tests
flutter test
```

### Useful Resources

- [Flutter iOS Deployment](https://docs.flutter.dev/deployment/ios)
- [Apple Developer Portal](https://developer.apple.com)
- [TestFlight Guide](https://developer.apple.com/testflight/)

---

## ✅ You're Ready!

**Quick Command to Install Now:**

```bash
cd /Users/amohamed/Herd/togetherpay/mobile
flutter run --dart-define=API_BASE=https://georgiann-semiacademic-never.ngrok-free.dev/api/v1
```

**That's it! The app will install on your iPhone in ~2 minutes!** 🚀

Need help with any step? Let me know!
