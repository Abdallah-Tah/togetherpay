# TogetherPay - Screenshot Capture Guide

This guide will help you capture all required screenshots for App Store and Play Store submission.

## 📋 Required Screenshots

### Apple App Store
You need **6 screenshots** in **3 different sizes**:

1. **iPhone 6.7" Display (1290 x 2796)** - iPhone 15 Pro Max
2. **iPhone 6.5" Display (1242 x 2688)** - iPhone 14 Plus
3. **iPhone 5.5" Display (1242 x 2208)** - iPhone 8 Plus *(optional but recommended)*

### Google Play Store
You need **6 screenshots** for:

- **Phone (1080 x 1920)** - Min 2, Max 8 screenshots

---

## 🎬 The 6 Key Screens to Capture

### 1. **Onboarding/Welcome Screen**
- **What to show**: The first screen users see when opening the app
- **Key elements**: App branding, welcome message, sign-in/sign-up options
- **Navigation**: Launch app without being logged in

### 2. **Dashboard with Groups**
- **What to show**: Main dashboard showing list of billing groups
- **Key elements**: Multiple groups visible, clear group names, member counts, amounts
- **Navigation**: Log in → Dashboard/Home
- **Demo data needed**: 3-4 groups with descriptive names like "Family Phone Plan", "Netflix Subscription", "Roommate Utilities"

### 3. **Group Details with Members**
- **What to show**: Inside a group showing all members
- **Key elements**: Group name, member list with names and amounts, payment status indicators
- **Navigation**: Dashboard → Click on a group
- **Demo data needed**: 4-5 members with varied payment statuses (paid, pending)

### 4. **Add Member Screen**
- **What to show**: Form to add a new member to a group
- **Key elements**: Name field, email field, amount input, **Venmo/Cash App selection** (this is critical!)
- **Navigation**: Group Details → Add Member button
- **Important**: Show the payment channel options (Venmo/Cash App)

### 5. **Payment Requests List**
- **What to show**: List of all payment requests
- **Key elements**: Multiple requests with dates, amounts, status badges, recipient names
- **Navigation**: Main menu → Payment Requests
- **Demo data needed**: Mix of statuses (Paid ✓, Pending ⏱, Overdue ⚠️)

### 6. **Payment Request Detail**
- **What to show**: Individual payment request with action buttons
- **Key elements**: Amount, group name, due date, **Venmo and Cash App buttons**, payment status
- **Navigation**: Payment Requests → Click on a request
- **Important**: This screen MUST show the Venmo/Cash App payment buttons clearly

---

## 🛠 Method 1: Automated Script (Recommended)

We've created a helper script to streamline the process:

```bash
cd mobile
chmod +x scripts/capture_screenshots.sh
./scripts/capture_screenshots.sh
```

The script will:
1. Prompt you to select platform (iOS, Android, or both)
2. Launch the appropriate simulator/emulator
3. Start the app
4. Pause for you to navigate and capture screenshots
5. Organize screenshots into proper folders

---

## 📱 Method 2: Manual Capture (iOS)

### Step 1: Launch iOS Simulator

```bash
# For 6.7" - iPhone 15 Pro Max
open -a Simulator
# Then select: File → Open Simulator → iPhone 15 Pro Max

# For 6.5" - iPhone 14 Plus
# Select: File → Open Simulator → iPhone 14 Plus

# For 5.5" - iPhone 8 Plus
# Select: File → Open Simulator → iPhone 8 Plus
```

### Step 2: Run the App

```bash
cd mobile
flutter run -d "iPhone 15 Pro Max"  # Or whichever simulator is open
```

### Step 3: Capture Screenshots

1. Navigate to each of the 6 screens listed above
2. Press **⌘ + S** (or File → New Screen Shot)
3. Screenshots save to your Desktop
4. Rename them descriptively:
   - `ios-6.7-01-onboarding.png`
   - `ios-6.7-02-dashboard.png`
   - `ios-6.7-03-group-details.png`
   - `ios-6.7-04-add-member.png`
   - `ios-6.7-05-payment-requests.png`
   - `ios-6.7-06-request-detail.png`

### Step 4: Repeat for Other Sizes

Repeat steps 1-3 for iPhone 14 Plus (6.5") and iPhone 8 Plus (5.5")

---

## 🤖 Method 3: Manual Capture (Android)

### Step 1: Launch Android Emulator

```bash
cd mobile
flutter emulators --launch Pixel_9_Pro
```

### Step 2: Run the App

```bash
flutter run -d Pixel_9_Pro
```

### Step 3: Capture Screenshots

1. Navigate to each of the 6 screens
2. Click the **camera icon** in the emulator toolbar (or press **⌘ + S**)
3. Screenshots save to Desktop
4. Rename them:
   - `android-01-onboarding.png`
   - `android-02-dashboard.png`
   - etc.

---

## 📝 Screenshot Checklist

Create this file structure in `mobile/screenshots/`:

```
screenshots/
├── ios/
│   ├── 6.7-inch/
│   │   ├── 01-onboarding.png
│   │   ├── 02-dashboard.png
│   │   ├── 03-group-details.png
│   │   ├── 04-add-member.png
│   │   ├── 05-payment-requests.png
│   │   └── 06-request-detail.png
│   ├── 6.5-inch/
│   │   └── (same 6 files)
│   └── 5.5-inch/
│       └── (same 6 files)
└── android/
    └── phone/
        ├── 01-onboarding.png
        ├── 02-dashboard.png
        ├── 03-group-details.png
        ├── 04-add-member.png
        ├── 05-payment-requests.png
        └── 06-request-detail.png
```

---

## ✅ Quality Checklist

Before submitting, verify each screenshot:

- [ ] **No personal data**: No real names, emails, or payment handles
- [ ] **Status bar clean**: Time shows 9:41, full battery, good signal
- [ ] **Realistic demo data**: Use believable group names and amounts
- [ ] **All UI visible**: No cut-off buttons or text
- [ ] **Correct orientation**: All screenshots in portrait mode
- [ ] **Feature highlight**: Payment buttons (Venmo/Cash App) clearly visible in screenshots #4 and #6
- [ ] **Consistent branding**: App icon, colors, and branding consistent across all screens
- [ ] **No errors**: No error messages or empty states visible

---

## 🎨 Pro Tips

1. **Use realistic demo data**:
   - Group names: "Family Phone Plan ($180/mo)", "Netflix Premium", "Apartment Utilities"
   - Member names: Common first names
   - Amounts: Realistic splits ($30, $45, $60)

2. **Status variety**:
   - Show mix of paid (green ✓) and pending (yellow ⏱) statuses
   - Makes the app look active and functional

3. **Clean UI**:
   - Close any debug banners
   - Ensure good contrast and readability
   - Use light mode for consistency (unless you want to show dark mode)

4. **Screenshot #4 (Add Member)**:
   - This is CRITICAL - must clearly show Venmo/Cash App channel selection
   - Helps reviewers understand the payment flow

5. **Screenshot #6 (Request Detail)**:
   - Must show the payment action buttons prominently
   - This demonstrates the app's core functionality

---

## 🚀 After Capture

1. Review all screenshots for quality
2. Move to `mobile/screenshots/` organized by platform and size
3. Upload to App Store Connect:
   - Go to "App Store" tab
   - Select "Prepare for Submission"
   - Scroll to "App Previews and Screenshots"
   - Drag and drop screenshots for each device size

4. Upload to Play Console:
   - Go to "Store presence" → "Main store listing"
   - Scroll to "Screenshots"
   - Upload phone screenshots (you can upload up to 8)

---

## 📞 Need Help?

If you encounter issues:
- Check simulator is fully booted: `xcrun simctl list devices | grep Booted`
- Verify app builds: `flutter doctor`
- Screenshot location on Desktop: `~/Desktop/`

---

**Next Step**: Run the capture script or follow manual instructions above! 🎬
