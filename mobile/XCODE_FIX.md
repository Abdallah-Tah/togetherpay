# Fix "unsupported option '-G'" Error in Xcode

## The Problem
The `-G` flag is being passed to the compiler for gRPC/BoringSSL pods, which causes the build to fail.

## Solution: Build via Xcode with Manual Fix

### Step 1: Open Project in Xcode
```bash
cd /Users/amohamed/Herd/togetherpay/mobile
open ios/Runner.xcworkspace
```

### Step 2: Configure Build Settings in Xcode

1. **Select "Runner" project** (blue icon) in left sidebar
2. Click on **"Runner" target** (under TARGETS)
3. Click on **"Build Settings"** tab
4. In the search box, type: **"Other C Flags"**
5. Find **"Other C Flags"** setting
6. Click to expand all configurations (Debug, Release, Profile)
7. **Remove** any `-G` flags if present

### Step 3: Fix Pods Build Settings

1. Click on **"Pods" project** (white icon with boxes) in left sidebar
2. For **EACH target** that has gRPC or BoringSSL in the name:
   - Click the target
   - Go to **"Build Settings"**
   - Search for **"Other C Flags"**
   - Remove any `-G` flags
   - Search for **"Other C++ Flags"**
   - Remove any `-G` flags

### Step 4: Clean Build Folder

1. In Xcode menu: **Product** → **Clean Build Folder** (Shift+Cmd+K)
2. Wait for clean to complete

### Step 5: Build and Run

1. Select your **iPhone** from device dropdown (top toolbar)
2. Click **Run button** (▶) or press Cmd+R
3. Wait for build (~2-5 minutes)
4. App will install and launch on your iPhone

---

## Alternative: Try Older Firebase Version

If the above doesn't work, we can downgrade Firebase to an older version that doesn't have this issue.

### Edit pubspec.yaml

Change Firebase versions to older, stable versions:

```yaml
dependencies:
  firebase_core: ^2.24.2  # Downgrade from 2.32.0
  firebase_auth: ^4.16.0  # Downgrade from 4.20.0
  firebase_messaging: ^14.7.10  # Downgrade from 14.9.4
  cloud_firestore: ^4.14.0  # Downgrade from 4.17.5
  cloud_functions: ^4.5.12  # Downgrade from 4.7.6
```

Then:
```bash
flutter clean
flutter pub get
cd ios
rm -rf Pods Podfile.lock
pod install
cd ..
flutter run -d 00008140-0002208C1E11801C
```

---

## Alternative: Use Release Mode

Sometimes debug mode has issues that release mode doesn't:

```bash
flutter run -d 00008140-0002208C1E11801C --release --dart-define=API_BASE=https://georgiann-semiacademic-never.ngrok-free.dev/api/v1
```

---

## Let's Try Via Xcode Now

Since Flutter CLI keeps failing, let's build directly in Xcode where we have more control over build settings.
